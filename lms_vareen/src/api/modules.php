<?php
/**
 * Modules API - Teacher/Admin CRUD for course modules
 */

require_once '../classes/Module.php';
require_once '../classes/Course.php';
require_once '../middleware/auth.php';

header('Content-Type: application/json');

$user = checkAuth();
$role = $user['role'] ?? null;

// Allow teacher and admin only
if (!in_array($role, ['teacher', 'admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$action = $_GET['action'] ?? '';

// CSRF: every POST (state change or authorized listing) must carry the session token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
}

try {
    $module = new Module();
    $course = new Course();

    switch ($action) {
        case 'list_by_course':
            $course_id = (int)($_POST['course_id'] ?? 0);
            if (!$course_id) {
                throw new Exception('Course ID required');
            }

            // Basic ownership check for teachers
            if ($role === 'teacher') {
                $course_data = $course->getCourseById($course_id);
                if (!$course_data || (int)($course_data['teacher_id'] ?? 0) !== (int)$user['id']) {
                    throw new Exception('Access denied');
                }
            }

            $modules = $module->getModulesByCourse($course_id);
            echo json_encode(['success' => true, 'data' => $modules]);
            break;

        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $course_id = (int)($_POST['course_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $position = (int)($_POST['position'] ?? 0);

            if (!$course_id) {
                throw new Exception('Course ID required');
            }

            if ($role === 'teacher') {
                $course_data = $course->getCourseById($course_id);
                if (!$course_data || (int)($course_data['teacher_id'] ?? 0) !== (int)$user['id']) {
                    throw new Exception('Access denied');
                }
            }

            $result = $module->createModule($course_id, $title, $description, $position);
            echo json_encode($result);
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $module_id = (int)($_POST['module_id'] ?? 0);
            if (!$module_id) {
                throw new Exception('Module ID required');
            }

            // Ownership check: fetch module with its course_id
            $mod = $module->getModulesByCourse(0); // no-op placeholder; we cannot fetch by id in current Module model
            // Safer: rely on DB constraint by letting teacher update only after verifying course ownership.
            // Since Module.php lacks getModuleById, we approximate by trying to fetch module in getModuleWithLessons.
            $module_data = $module->getModuleWithLessons($module_id);
            if (!$module_data) {
                throw new Exception('Module not found');
            }

            if ($role === 'teacher') {
                $course_data = $course->getCourseById((int)$module_data['course_id']);
                if (!$course_data || (int)($course_data['teacher_id'] ?? 0) !== (int)$user['id']) {
                    throw new Exception('Access denied');
                }
            }

            $data = [];
            if (isset($_POST['title'])) $data['title'] = trim($_POST['title']);
            if (isset($_POST['description'])) $data['description'] = trim($_POST['description']);
            if (isset($_POST['position'])) $data['position'] = (int)$_POST['position'];

            $result = $module->updateModule($module_id, $data);
            echo json_encode($result);
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $module_id = (int)($_POST['module_id'] ?? 0);
            if (!$module_id) {
                throw new Exception('Module ID required');
            }

            $module_data = $module->getModuleWithLessons($module_id);
            if (!$module_data) {
                throw new Exception('Module not found');
            }

            if ($role === 'teacher') {
                $course_data = $course->getCourseById((int)$module_data['course_id']);
                if (!$course_data || (int)($course_data['teacher_id'] ?? 0) !== (int)$user['id']) {
                    throw new Exception('Access denied');
                }
            }

            $result = $module->deleteModule($module_id);
            echo json_encode($result);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

