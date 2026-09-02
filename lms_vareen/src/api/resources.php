<?php
/**
 * Resources API - Teacher/Admin upload/delete resources
 */

require_once '../classes/Resource.php';
require_once '../classes/Course.php';
require_once '../classes/Lesson.php';
require_once '../middleware/auth.php';

header('Content-Type: application/json');

$user = checkAuth();
$role = $user['role'] ?? null;

if (!in_array($role, ['teacher', 'admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$action = $_GET['action'] ?? '';

// CSRF: every POST (upload/delete) must carry the session token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
}

try {
    $resource = new Resource();
    $lesson = new Lesson();
    $course = new Course();

    switch ($action) {
        case 'upload':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            // Multipart
            $lesson_id = (int)($_POST['lesson_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            if (!$lesson_id) throw new Exception('Lesson ID required');
            if ($title === '') throw new Exception('Title required');
            if (!isset($_FILES['resource_file'])) throw new Exception('File required');

            $lesson_data = $lesson->getLessonById($lesson_id);
            if (!$lesson_data) throw new Exception('Lesson not found');

            if ($role === 'teacher') {
                $course_data = $course->getCourseById((int)$lesson_data['course_id']);
                if (!$course_data || (int)($course_data['teacher_id'] ?? 0) !== (int)$user['id']) {
                    throw new Exception('Access denied');
                }
            }

            $upload = $resource->uploadFile($_FILES['resource_file'], $lesson_id);
            if (!$upload['success']) {
                throw new Exception($upload['message'] ?? 'Upload failed');
            }

            $res = $resource->addResource(
                $lesson_id,
                $title,
                $upload['filepath'],
                $upload['file_type'] ?? ''
            );

            echo json_encode($res);
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $resource_id = (int)($_POST['resource_id'] ?? 0);
            if (!$resource_id) throw new Exception('Resource ID required');

            // Only allow deletion for admins; for teachers keep it safe until we add a getResourceOwnership check.
            if ($role !== 'admin') {
                throw new Exception('Access denied');
            }

            $result = $resource->deleteResource($resource_id);
            echo json_encode($result);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

