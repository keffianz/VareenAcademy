<?php
// One-time seed script to create a default admin user.
// Usage: visit http://localhost/lms_vareen/admin_seed.php once.
// After success, the script will self-disable by writing a flag file.

// IMPORTANT: This script must be executed via a browser request.

header('Content-Type: text/plain; charset=utf-8');

$flagFile = __DIR__ . '/storage/admin_seed.done';

if (file_exists($flagFile)) {
    echo "admin seed already completed\n";
    exit;
}

// Prevent accidental execution in production-like environments
$allowed = ['localhost', '127.0.0.1'];
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (!in_array(explode(':', $host)[0], $allowed, true)) {
    http_response_code(403);
    echo "Forbidden: host not allowed\n";
    exit;
}

require_once __DIR__ . '/src/classes/Database.php';

$email = 'admin@email.com';
$plainPassword = 'admin123';
$role = 'admin';
$firstName = 'Admin';
$lastName = 'Account';

$hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

$db = (new Database())->connect();

try {
    // Ensure users table exists
    $db->exec("SELECT 1 FROM users LIMIT 1");

    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing admin
        $upd = $db->prepare('UPDATE users SET password = :password, role = :role, first_name = :first_name, last_name = :last_name, is_active = 1 WHERE email = :email');
        $upd->execute([
            ':password' => $hashedPassword,
            ':role' => $role,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $email
        ]);
        echo "Updated admin user: {$existing['id']}\n";
    } else {
        $ins = $db->prepare('INSERT INTO users (role, email, password, first_name, last_name, is_active) VALUES (:role, :email, :password, :first_name, :last_name, 1)');
        $ins->execute([
            ':role' => $role,
            ':email' => $email,
            ':password' => $hashedPassword,
            ':first_name' => $firstName,
            ':last_name' => $lastName
        ]);
        echo "Created admin user: " . $db->lastInsertId() . "\n";
    }

    // mark done
    $dir = __DIR__ . '/storage';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($flagFile, date('c'));

    echo "Done\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage() . "\n";
    exit;
}

