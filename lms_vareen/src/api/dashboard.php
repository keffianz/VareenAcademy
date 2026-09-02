<?php
/**
 * Dashboard API Endpoints
 */

header('Content-Type: application/json');

require_once '../classes/Enrollment.php';
require_once '../classes/Course.php';
require_once '../classes/Notification.php';
require_once '../middleware/auth.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'message' => ''];
$action = $_GET['action'] ?? '';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// CSRF: every POST (enroll, mark notification, profile updates) must carry the token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
}

$user_id = $_SESSION['user_id'];
$enrollment = new Enrollment();
$course = new Course();
$notification = new Notification();

switch ($action) {
    case 'enroll':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $course_id = $data['course_id'] ?? null;
            
            if (!$course_id) {
                $response = ['success' => false, 'message' => 'Course ID required'];
            } else {
                $response = $enrollment->enrollStudent($user_id, $course_id);
            }
        }
        break;

    case 'update_progress':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $course_id = $data['course_id'] ?? null;
            $progress = $data['progress'] ?? 0;
            
            if (!$course_id) {
                $response = ['success' => false, 'message' => 'Course ID required'];
            } else {
                $response = $enrollment->updateProgress($user_id, $course_id, $progress);
            }
        }
        break;

    case 'get_dashboard':
        $dashboard = $enrollment->getStudentDashboard($user_id);
        $response = ['success' => true, 'data' => $dashboard];
        break;

    case 'mark_notification_read':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $notification_id = $data['notification_id'] ?? null;
            
            if (!$notification_id) {
                $response = ['success' => false, 'message' => 'Notification ID required'];
            } else {
                $response = $notification->markAsRead($notification_id);
            }
        }
        break;

    case 'mark_all_notifications_read':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $response = $notification->markAllAsRead($user_id);
        }
        break;

    case 'get_unread_count':
        $count = $notification->getUnreadCount($user_id);
        $response = ['success' => true, 'count' => $count];
        break;

    default:
        $response = ['success' => false, 'message' => 'Invalid action'];
}

echo json_encode($response);
