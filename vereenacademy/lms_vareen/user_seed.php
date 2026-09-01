<?php
// One-time seed script to create/update a default user.
// Usage: visit http://localhost/lms_vareen/user_seed.php once.
// After success, the script will self-disable by writing a flag file.

header('Content-Type: text/plain; charset=utf-8');

$flagFile = __DIR__ . '/storage/user_seed.done';

if (file_exists($flagFile)) {
    echo "user seed already completed\n";
    exit;
}

$allowed = ['localhost', '127.0.0.1'];
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (!in_array(explode(':', $host)[0], $allowed, true)) {
    http_response_code(403);
    echo "Forbidden: host not allowed\n";
    exit;
}

require_once __DIR__ . '/src/classes/Database.php';

// If DB name differs on your machine, set it here before running seeding.
// Example: $dbOverride = 'your_database_name';
$dbOverride = 'your_database_name_here';

if (is_string($dbOverride) && $dbOverride !== 'your_database_name_here') {
    // Monkey-patch by overriding the Database class property values is not supported,
    // so we simply stop seeding with a clear message.
    echo "Database override is not supported in this seed script. Please update `src/classes/Database.php` DB_NAME instead.\n";
    exit;
}


$email = 'user@email.com';
$plainPassword = 'user123';
$role = 'student';
$firstName = 'Default';
$lastName = 'User';

$hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

$db = (new Database())->connect();

try {
    // Ensure users table exists
    $db->exec("SELECT 1 FROM users LIMIT 1");

    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $upd = $db->prepare(
            'UPDATE users SET password = :password, role = :role, first_name = :first_name, last_name = :last_name, is_active = 1 WHERE email = :email'
        );
        $upd->execute([
            ':password' => $hashedPassword,
            ':role' => $role,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $email
        ]);
        echo "Updated user: {$existing['id']}\n";
    } else {
        $ins = $db->prepare(
            'INSERT INTO users (role, email, password, first_name, last_name, is_active) VALUES (:role, :email, :password, :first_name, :last_name, 1)'
        );
        $ins->execute([
            ':role' => $role,
            ':email' => $email,
            ':password' => $hashedPassword,
            ':first_name' => $firstName,
            ':last_name' => $lastName
        ]);
        echo "Created user: " . $db->lastInsertId() . "\n";
    }

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

