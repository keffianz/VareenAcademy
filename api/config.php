<?php
// api/config.php
// VAREEN Academy Database Configuration (marketing-site APIs)
//
// SECURITY: Credentials are loaded from environment variables so that no
// secret is ever committed to version control. Set these on your server
// (e.g. Hostinger > PHP configuration, or via .htaccess SetEnv):
//     DB_HOST, DB_USER, DB_PASS, DB_NAME, ADMIN_EMAIL
// A blank DB_USER/DB_PASS blocks the connection rather than falling back to
// hardcoded credentials. Rotate the database password that was previously
// exposed in this file's git history.

$__env = static function (string $key, string $default = ''): string {
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
};

define('DB_HOST', $__env('DB_HOST', 'localhost'));
define('DB_NAME', $__env('DB_NAME', 'u374397808_vereen_academy'));
define('DB_USER', $__env('DB_USER', ''));
define('DB_PASS', $__env('DB_PASS', ''));
define('ADMIN_EMAIL', $__env('ADMIN_EMAIL', 'VEREENacademy@gmail.com'));

function get_pdo() {
    if (defined('DB_USER') && DB_USER === '') {
        error_log('DB connection blocked: DB_USER is not configured.');
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Service temporarily unavailable.']);
        exit();
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed. See server logs.']);
        exit();
    }
}

