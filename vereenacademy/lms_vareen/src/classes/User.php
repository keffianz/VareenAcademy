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
     */
    public function register($first_name, $last_name, $email, $password, $role = 'student') {
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
     */
    public function login($email, $password) {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password required'];
        }

        try {
            $sql = "SELECT id, first_name, last_name, email, password, role, is_active 
                    FROM users WHERE email = :email";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'Email not found'];
            }


            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'Your account has been deactivated'];
            }

            // Verify password (ensure we compare against the correct bcrypt hash column)
            $hash = $user['password'] ?? '';
            if (!is_string($hash) || $hash === '') {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            if (!password_verify($password, $hash)) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }


            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();

            return ['success' => true, 'message' => 'Login successful', 'user' => $user];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check if email exists
     */
    public function emailExists($email) {
        try {
            $sql = "SELECT id FROM users WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
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
     */
    public function requestPasswordReset($email) {
        try {
            $sql = "SELECT id FROM users WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'Email not found'];
            }

            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $sql = "INSERT INTO password_resets (user_id, token, expires_at) 
                    VALUES (:user_id, :token, :expires_at)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $user['id'],
                ':token' => $token,
                ':expires_at' => $expires
            ]);

            return ['success' => true, 'message' => 'Reset link sent', 'token' => $token];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to process reset'];
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
            $sql = "SELECT user_id, expires_at FROM password_resets WHERE token = :token";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => $token]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                return ['success' => false, 'message' => 'Invalid reset token'];
            }

            if (strtotime($reset['expires_at']) < time()) {
                return ['success' => false, 'message' => 'Reset token has expired'];
            }

            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET password = :password WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':password' => $hashed_password, ':id' => $reset['user_id']]);

            // Delete token
            $sql = "DELETE FROM password_resets WHERE token = :token";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => $token]);

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
