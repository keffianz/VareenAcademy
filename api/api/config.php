<?php
// api/config.php
// VAREEN Academy Database Configuration
// Database: vereen_academy
// Username: vereenacademy
// Password: Abubakar11@

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'vereen_academy');
define('DB_USER', 'vereenacademy');
define('DB_PASS', 'Abubakar11@');
define('ADMIN_EMAIL', 'admin@vereenacademy.com');

function get_pdo() {
    static $pdo = null;
    if ($pdo) return $pdo;

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

?>
<?php
// This file has been archived to /archive/api/config.php
// The server-side backend is not required for the static site.

http_response_code(410);
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => 'API removed for static build. See /archive/api/ for original server code.'
]);
exit();

?>


