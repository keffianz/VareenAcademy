<?php
/**
 * Lightweight health probe for the LMS API.
 *
 * Returns ONLY a boolean service/database status — never credentials,
 * hostnames, or error details (those go to the PHP error log instead).
 * Use this to diagnose "service unavailable" reports from the outside:
 *   200 {"success":true,"database":"up"}    everything is fine
 *   503 {"success":false,"database":"down"} DB config missing / DB unreachable
 */

header('Content-Type: application/json');

require_once '../middleware/auth.php';
require_once '../classes/Database.php';

vaBootSession();

$response = ['success' => true, 'service' => 'lms-api', 'database' => 'up'];

try {
    $db = new Database();
    $pdo = $db->connect();
    $pdo->query('SELECT 1');
} catch (RuntimeException $e) {
    // Config problem: DB_USER/DB_NAME not set on this server.
    error_log('LMS health check failed: ' . $e->getMessage());
    $response['success'] = false;
    $response['database'] = 'down';
    $response['reason'] = 'db_config_missing';
    $response['hint'] = 'Create src/config/local_db.php on this server (copy from local_db.example.php) or set DB_USER/DB_NAME/DB_PASS environment variables.';
    http_response_code(503);
    header('Retry-After: 30');
} catch (PDOException $e) {
    // Connection problem: classify by SQLSTATE / driver error code.
    // Only the code is exposed — never the message (it can contain usernames/hosts).
    error_log('LMS health check failed: ' . $e->getMessage());
    $response['success'] = false;
    $response['database'] = 'down';
    $state = $e->getCode();
    $driverCode = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;
    if ($driverCode === 1045 || $state === '28000') {
        $response['reason'] = 'db_access_denied';
        $response['hint'] = 'Wrong DB username or password in local_db.php.';
    } elseif ($driverCode === 1049) {
        $response['reason'] = 'db_unknown_database';
        $response['hint'] = 'The configured database name does not exist on this server.';
    } elseif ($driverCode === 2002 || $driverCode === 2003) {
        $response['reason'] = 'db_unreachable';
        $response['hint'] = 'MySQL server did not accept the connection (wrong host or MySQL stopped).';
    } else {
        $response['reason'] = 'db_error';
        $response['hint'] = 'Unexpected database error; check the PHP error log.';
    }
    http_response_code(503);
    header('Retry-After: 30');
}

echo json_encode($response);
