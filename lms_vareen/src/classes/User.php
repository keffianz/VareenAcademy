<?php
/**
 * User Class - Handle user operations
 */

require_once 'Database.php';

class User {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Register a new user
     *
     * SECURITY: Public registration is restricted to the 'student' role.
     * 'teacher' and 'admin' accounts must be created by an administrator,
     * never via a client-supplied role parameter.
     */
    public function register($first_name, $last_name, $email, $password, $role = 'student') {
        // Whitelist roles - prevent privilege escalation via crafted requests
        $allowedRoles = ['student'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'student';
        }

        // Validate input
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }

        // Check if email already exists
        if ($this->emailExists($email)) {
            return ['success' => false, 'message' => 'Email already registered'];
        }

        // Validate password strength
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        try {
            $sql = "INSERT INTO users (first_name, last_name, email, password, role) 
                    VALUES (:first_name, :last_name, :email, :password, :role)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':email' => $email,
                ':password' => $hashed_password,
                ':role' => $role
            ]);

            $user_id = $this->db->lastInsertId();
            return ['success' => true, 'message' => 'Registration successful', 'user_id' => $user_id];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    /**
     * Login user
     *
     * SECURITY: Email alone does not uniquely identify an account — the
     * system supports one account per role per email (e.g. demo/test
     * accounts). When $expectedRole is provided, only a user with that
     * role can log in (email + password + intended role are all verified
     * server-side before a session is created).
     *
     * @param string|null $expectedRole One of 'admin', 'teacher', 'student' or null for legacy behaviour.
     */
    public function login($email, $password, $expectedRole = null) {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password required'];
        }

        // Brute-force protection: lock after 5 failed attempts per 15 minutes
        $now = time();
        $failures = $_SESSION['login_failures'] ?? 0;
        $lockUntil = $_SESSION['login_lock_until'] ?? 0;

        if ($lockUntil > $now) {
            $mins = (int)ceil(($lockUntil - $now) / 60);
            return ['success' => false, 'message' => 'Too many failed attempts. Please try again in ' . $mins . ' minute(s).'];
        }

        // Generic message used for both unknown email and wrong password to prevent user enumeration
        $genericError = ['success' => false, 'message' => 'Invalid email or password'];

        try {
            $sql = "SELECT id, first_name, last_name, email, password, role, is_active
                    FROM users WHERE email = :email";
            $params = [':email' => $email];

            // Role-scoped login: never trust the client role for authorization,
            // but require that the verified account actually has that role.
            if ($expectedRole !== null && $expectedRole !== '') {
                if (!in_array($expectedRole, ['admin', 'teacher', 'student'], true)) {
                    return $genericError;
                }
                $sql .= " AND role = :role";
                $params[':role'] = $expectedRole;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Normally at most one candidate. If several share the email+role
            // (legacy data), require exactly one match to avoid ambiguity.
            $user = null;
            if (count($users) === 1) {
                $user = $users[0];
            } elseif (count($users) > 1) {
                // Degrade safely: never pick "the first row" at random.
                foreach ($users as $candidate) {
                    if (!empty($candidate['password']) && password_verify($password, $candidate['password'])) {
                        return ['success' => false, 'message' => 'Multiple accounts found for this email. Please contact support.'];
                    }
                }
                $failures++;
                $_SESSION['login_failures'] = $failures;
                if ($failures >= 5) {
                    $_SESSION['login_lock_until'] = $now + 900; // 15 minutes
                }
                return $genericError;
            }

            $passwordOk = $user && !empty($user['password']) && password_verify($password, $user['password']);

            if (!$passwordOk) {
                $failures++;
                $_SESSION['login_failures'] = $failures;
                if ($failures >= 5) {
                    $_SESSION['login_lock_until'] = $now + 900; // 15 minutes
                }
                return $genericError;
            }

            if (!(int)$user['is_active']) {
                return ['success' => false, 'message' => 'Your account has been deactivated'];

            }

            // Login successful - clear failure counter and regenerate session id to prevent fixation
            unset($_SESSION['login_failures'], $_SESSION['login_lock_until']);
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();

            // Strip password hash before returning the user object to the client
            unset($user['password']);
            return ['success' => true, 'message' => 'Login successful', 'user' => $user];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Login failed. Please try again later.'];
        }
    }

    /**
     * Check if email exists
     *
     * @param string      $email Email address
     * @param string|null $role  When provided, only that role's account is matched.
     *                           Used so one email can exist once per role
     *                           (demo/test accounts) without breaking the
     *                           unique-email rule for normal registrations.
     */
    public function emailExists($email, $role = null) {
        try {
            if ($role !== null && in_array($role, ['admin', 'teacher', 'student'], true)) {
                $sql = "SELECT id FROM users WHERE email = :email AND role = :role";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':email' => $email, ':role' => $role]);
            } else {
                $sql = "SELECT id FROM users WHERE email = :email";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':email' => $email]);
            }
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get user by ID
     */
    public function getUserById($user_id) {
        try {
            $sql = "SELECT id, first_name, last_name, email, role, profile_image, bio, 
                           phone, city, country, created_at FROM users WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile($user_id, $data) {
        try {
            $updates = [];
            $params = [':id' => $user_id];

            if (isset($data['first_name'])) {
                $updates[] = "first_name = :first_name";
                $params[':first_name'] = $data['first_name'];
            }
            if (isset($data['last_name'])) {
                $updates[] = "last_name = :last_name";
                $params[':last_name'] = $data['last_name'];
            }
            if (isset($data['bio'])) {
                $updates[] = "bio = :bio";
                $params[':bio'] = $data['bio'];
            }
            if (isset($data['phone'])) {
                $updates[] = "phone = :phone";
                $params[':phone'] = $data['phone'];
            }
            if (isset($data['city'])) {
                $updates[] = "city = :city";
                $params[':city'] = $data['city'];
            }
            if (isset($data['country'])) {
                $updates[] = "country = :country";
                $params[':country'] = $data['country'];
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'No data to update'];
            }

            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Profile updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
        }
    }

    /**
     * Change password
     */
    public function changePassword($user_id, $old_password, $new_password) {
        if (strlen($new_password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }

        try {
            // Get current password
            $sql = "SELECT password FROM users WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($old_password, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }

            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET password = :password WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':password' => $hashed_password, ':id' => $user_id]);

            return ['success' => true, 'message' => 'Password changed successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Password change failed: ' . $e->getMessage()];
        }
    }

    /**
     * Request password reset
     *
     * Security: always returns a generic success to prevent user enumeration,
     * stores a hashed token, invalidates previous tokens, and never exposes
     * the raw token in the JSON response.
     */
    public function requestPasswordReset($email) {
        try {
            $sql = "SELECT id FROM users WHERE email = :email AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $genericMessage = 'If a matching account exists, a password reset link has been generated.';

            if (!$user) {
                return ['success' => true, 'message' => $genericMessage];
            }

            // Invalidate previous tokens for this user
            $del = $this->db->prepare("DELETE FROM password_resets WHERE user_id = :user_id");
            $del->execute([':user_id' => $user['id']]);

            // Generate token; store only its SHA-256 hash in the database
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $sql = "INSERT INTO password_resets (user_id, token, expires_at) 
                    VALUES (:user_id, :token, :expires_at)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $user['id'],
                ':token' => $tokenHash,
                ':expires_at' => $expires
            ]);

            // Attempt to email the reset token to the user
            $emailSent = false;
            if (!empty($email)) {
                $subject = 'VAREEN Academy - Password Reset';
                $body = "Hello,\n\nUse this token to reset your VAREEN Academy password:\n\n$token\n\nThis token expires in 1 hour.\n\nIf you did not request this, you can safely ignore this email.\n";
                $headers = 'From: ' . (defined('MAIL_FROM') && MAIL_FROM ? MAIL_FROM : 'noreply@vereenacademy.com') . "\r\n";
                $emailSent = @mail($email, $subject, $body, $headers);
            }

            // If email delivery is not configured, write the token to a protected log for support recovery
            if (!$emailSent) {
                $logDir = __DIR__ . '/../../storage';
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0755, true);
                }
                @file_put_contents(
                    $logDir . '/password_resets.log',
                    date('c') . " user={$user['id']} token=$token\n",
                    FILE_APPEND
                );
            }

            return ['success' => true, 'message' => $genericMessage];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to process reset. Please try again later.'];
        }
    }

    /**
     * Reset password with token
     */
    public function resetPasswordWithToken($token, $new_password) {
        if (strlen($new_password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }

        try {
            $tokenHash = hash('sha256', $token);

            $sql = "SELECT user_id, expires_at FROM password_resets WHERE token = :token";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => $tokenHash]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                return ['success' => false, 'message' => 'Invalid or already-used reset token'];
            }

            if (strtotime($reset['expires_at']) < time()) {
                return ['success' => false, 'message' => 'Reset token has expired'];
            }

            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET password = :password WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':password' => $hashed_password, ':id' => $reset['user_id']]);

            // Delete token (single-use)
            $sql = "DELETE FROM password_resets WHERE token = :token";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => $tokenHash]);

            return ['success' => true, 'message' => 'Password reset successful'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Password reset failed'];
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
}
