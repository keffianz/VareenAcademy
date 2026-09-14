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
} catch (Throwable $e) {
    error_log('LMS health check failed: ' . $e->getMessage());
    $response['success'] = false;
    $response['database'] = 'down';
    http_response_code(503);
    header('Retry-After: 30');
}

echo json_encode($response);
