<?php
/**
 * Dashboard API Endpoints
 */

header('Content-Type: application/json');

require_once '../classes/Enrollment.php';
require_once '../classes/Course.php';
require_once '../classes/Notification.php';
require_once '../middleware/auth.php';

// Start session via the unified bootstrap (same cookie attributes as the router)
vaBootSession();

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
            $course_id = (int)($data['course_id'] ?? 0);

            if (!$course_id) {
                $response = ['success' => false, 'message' => 'Course ID required'];
            } elseif (($_SESSION['user_role'] ?? '') !== 'student') {
                // Only students hold enrollments (VX-009) — teachers/admins read courses, they don't join them
                http_response_code(403);
                $response = ['success' => false, 'message' => 'Only student accounts can enroll in courses'];
            } else {
                // Only published courses accept enrollments (VX-009)
                $course_data = $course->getCourseById($course_id);
                if (!$course_data || empty($course_data['is_active'])) {
                    http_response_code(404);
                    $response = ['success' => false, 'message' => 'This course is not available for enrollment'];
                } else {
                    $response = $enrollment->enrollStudent($user_id, $course_id);
                }
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
                // Ownership-scoped: users may only mark their own notifications (VX-012 IDOR fix)
                $response = $notification->markAsRead($notification_id, $user_id);
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
