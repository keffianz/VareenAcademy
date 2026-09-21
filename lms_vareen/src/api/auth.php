<?php
/**
 * Authentication API Endpoints
 */

header('Content-Type: application/json');

require_once '../classes/User.php';
require_once '../middleware/auth.php';

$request_method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Start session via the unified bootstrap (same cookie attributes as the router)
vaBootSession();

// CSRF protection for all state-changing POST requests
if ($request_method === 'POST') {
    requireCsrf();
}

try {
    $user = new User();
} catch (Throwable $e) {
    error_log('Authentication database initialization failed: ' . $e->getMessage());
    http_response_code(503);
    header('Retry-After: 30');
    echo json_encode([
        'success' => false,
        'message' => 'The authentication service is temporarily unavailable.'
    ]);
    exit();
}

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

            // Intended role is used ONLY to select the account (email+password+role
            // must all match server-side); authorization always comes from the
            // verified account's role stored in the session, never from this input.
            $intendedRole = $data['intended_role'] ?? null;
            if (!in_array($intendedRole, ['admin', 'teacher', 'student'], true)) {
                $intendedRole = null;
            }

            $response = $user->login(
                $data['email'] ?? '',
                $data['password'] ?? '',
                $intendedRole
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

    case 'update_profile':
        // Authenticated self-service profile edit (VX-009).
        // Identity/authorization fields are never editable through this endpoint.
        if ($request_method === 'POST') {
            if (!isset($_SESSION['user_id'])) {
                $response = ['success' => false, 'message' => 'Not authenticated'];
            } else {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!is_array($data)) {
                    $data = [];
                }
                foreach (['id', 'email', 'role', 'password', 'is_active', 'created_at', 'profile_image'] as $protected) {
                    unset($data[$protected]);
                }
                $response = $user->updateProfile($_SESSION['user_id'], $data);
            }
        }
        break;

    case 'upload_avatar':
        // Profile photo upload (VX-009) — images only, replaces the previous photo.
        if ($request_method === 'POST') {
            if (!isset($_SESSION['user_id'])) {
                $response = ['success' => false, 'message' => 'Not authenticated'];
            } elseif (empty($_FILES['avatar'])) {
                $response = ['success' => false, 'message' => 'No image uploaded'];
            } else {
                require_once '../classes/Uploader.php';
                $uploader = new Uploader();
                $upload = $uploader->upload($_FILES['avatar'], 'image', (int)$_SESSION['user_id']);

                if (empty($upload['success'])) {
                    $response = ['success' => false, 'message' => $upload['message']];
                } else {
                    // Replace: remove the previous avatar if we are the ones storing it
                    $current = $user->getUserById($_SESSION['user_id']);
                    $old = (string)($current['profile_image'] ?? '');
                    if ($old !== '' && strpos($old, 'assets/uploads/images/') === 0) {
                        $uploader->removeStoredFile($old);
                    }

                    $saved = $user->updateProfile($_SESSION['user_id'], ['profile_image' => $upload['path']]);
                    $response = empty($saved['success'])
                        ? $saved
                        : [
                            'success'       => true,
                            'message'       => 'Profile photo updated',
                            'profile_image' => $upload['path'],
                        ];
                }
            }
        }
        break;

    default:
        $response = ['success' => false, 'message' => 'Invalid action'];
}

echo json_encode($response);
