<?php
/**
 * Authentication API Endpoints
 */

header('Content-Type: application/json');

require_once '../classes/User.php';

$request_method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = new User();
$response = ['success' => false, 'message' => ''];

switch ($action) {
    case 'signup':
        if ($request_method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $response = $user->register(
                $data['first_name'] ?? '',
                $data['last_name'] ?? '',
                $data['email'] ?? '',
                $data['password'] ?? '',
                $data['role'] ?? 'student'
            );
        }
        break;

    case 'login':
        if ($request_method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $response = $user->login(
                $data['email'] ?? '',
                $data['password'] ?? ''
            );
        }
        break;

    case 'logout':
        $user->logout();
        $response = ['success' => true, 'message' => 'Logged out successfully'];
        break;

    case 'check_email':
        if ($request_method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $email = $data['email'] ?? '';
            
            if ($user->emailExists($email)) {
                $response = ['success' => false, 'message' => 'Email already registered'];
            } else {
                $response = ['success' => true, 'message' => 'Email available'];
            }
        }
        break;

    case 'change_password':
        if ($request_method === 'POST') {
            if (!isset($_SESSION['user_id'])) {
                $response = ['success' => false, 'message' => 'Not authenticated'];
            } else {
                $data = json_decode(file_get_contents('php://input'), true);
                $response = $user->changePassword(
                    $_SESSION['user_id'],
                    $data['old_password'] ?? '',
                    $data['new_password'] ?? ''
                );
            }
        }
        break;

    case 'request_reset':
        if ($request_method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $response = $user->requestPasswordReset($data['email'] ?? '');
        }
        break;

    case 'reset_password':
        if ($request_method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $response = $user->resetPasswordWithToken(
                $data['token'] ?? '',
                $data['new_password'] ?? ''
            );
        }
        break;

    default:
        $response = ['success' => false, 'message' => 'Invalid action'];
}

echo json_encode($response);
