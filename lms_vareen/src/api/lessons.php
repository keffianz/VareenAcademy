<?php
/**
 * Lessons API - Handle all lesson-related operations
 */

require_once '../classes/Lesson.php';
require_once '../classes/LessonProgress.php';
require_once '../classes/Resource.php';
require_once '../classes/Certificate.php';
require_once '../classes/Notification.php';
require_once '../classes/Database.php';
require_once '../middleware/auth.php';

header('Content-Type: application/json');

// Check authentication
$user = checkAuth();

// CSRF: every POST (including lesson fetches posted with lesson_id) must carry the token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get':
            // Get lesson details with student progress
            $lesson_id = $_POST['lesson_id'] ?? 0;
            if (!$lesson_id) {
                throw new Exception('Lesson ID required');
            }

            $lesson = new Lesson();
            $lesson_data = $lesson->getLessonById($lesson_id, $user['id']);

            if (!$lesson_data) {
                throw new Exception('Lesson not found');
            }

            // Get resources
            $resource = new Resource();
            $resources = $resource->getResourcesByLesson($lesson_id);

            $lesson_data['resources'] = $resources;

            echo json_encode(['success' => true, 'data' => $lesson_data]);
            break;

        case 'get_by_module':
            // Get all lessons in a module
            $module_id = $_POST['module_id'] ?? 0;
            if (!$module_id) {
                throw new Exception('Module ID required');
            }

            $lesson = new Lesson();
            $lessons = $lesson->getLessonsByModule($module_id);

            echo json_encode(['success' => true, 'data' => $lessons]);
            break;

        case 'get_by_course':
            // Get all lessons organized by module
            $course_id = $_POST['course_id'] ?? 0;
            if (!$course_id) {
                throw new Exception('Course ID required');
            }

            $lesson = new Lesson();
            $lessons = $lesson->getLessonsByCourse($course_id);

            echo json_encode(['success' => true, 'data' => $lessons]);
            break;

        case 'record_progress':
            // Record student's watch progress
            $lesson_id = $_POST['lesson_id'] ?? 0;
            $watched_duration = $_POST['watched_duration'] ?? 0;

            if (!$lesson_id) {
                throw new Exception('Lesson ID required');
            }

            $progress = new LessonProgress();
            $result = $progress->recordWatch($user['id'], $lesson_id, $watched_duration);

            echo json_encode($result);
            break;

        case 'mark_complete':
            // Mark lesson as completed
            $lesson_id = $_POST['lesson_id'] ?? 0;
            if (!$lesson_id) {
                throw new Exception('Lesson ID required');
            }

            $progress = new LessonProgress();
            $result = $progress->markCompleted($user['id'], $lesson_id);

            // Auto-issue a certificate when this was the final active lesson of the course
            $result['certificate_issued'] = false;
            if (!empty($result['success'])) {
                $dbConn = (new Database())->connect();
                $stmtL = $dbConn->prepare('SELECT course_id FROM lessons WHERE id = :id');
                $stmtL->execute([':id' => (int)$lesson_id]);
                $certCourseId = (int)$stmtL->fetchColumn();
                if ($certCourseId) {
                    $certResult = (new Certificate())->issueIfEligible((int)$user['id'], $certCourseId);
                    if (!empty($certResult['success']) && !empty($certResult['issued'])) {
                        $result['certificate_issued'] = true;
                        $result['certificate_code'] = $certResult['certificate']['certificate_code'];
                        (new Notification())->create(
                            (int)$user['id'],
                            'certificate',
                            'Certificate earned!',
                            'Congratulations! You completed a course and earned certificate ' . $result['certificate_code'] . '.'
                        );
                    }
                }
            }

            echo json_encode($result);
            break;

        case 'get_progress':
            // Get student's progress on a lesson
            $lesson_id = $_POST['lesson_id'] ?? 0;
            if (!$lesson_id) {
                throw new Exception('Lesson ID required');
            }

            $progress = new LessonProgress();
            $lesson_progress = $progress->getProgress($user['id'], $lesson_id);

            echo json_encode(['success' => true, 'data' => $lesson_progress]);
            break;

        case 'get_course_progress':
            // Get student's progress in entire course
            $course_id = $_POST['course_id'] ?? 0;
            if (!$course_id) {
                throw new Exception('Course ID required');
            }

            $progress = new LessonProgress();
            $course_progress = $progress->getCourseProgress($user['id'], $course_id);

            echo json_encode(['success' => true, 'data' => $course_progress]);
            break;

        case 'get_next':
            // Get next lesson
            $lesson_id = $_POST['lesson_id'] ?? 0;
            $course_id = $_POST['course_id'] ?? 0;

            if (!$lesson_id || !$course_id) {
                throw new Exception('Lesson and Course ID required');
            }

            $lesson = new Lesson();
            $next_lesson = $lesson->getNextLesson($lesson_id, $course_id);

            echo json_encode(['success' => true, 'data' => $next_lesson]);
            break;

        case 'get_previous':
            // Get previous lesson
            $lesson_id = $_POST['lesson_id'] ?? 0;
            $course_id = $_POST['course_id'] ?? 0;

            if (!$lesson_id || !$course_id) {
                throw new Exception('Lesson and Course ID required');
            }

            $lesson = new Lesson();
            $prev_lesson = $lesson->getPreviousLesson($lesson_id, $course_id);

            echo json_encode(['success' => true, 'data' => $prev_lesson]);
            break;

        case 'create':
            // Teacher/Admin: create a lesson
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $course_id = (int)($_POST['course_id'] ?? 0);
            $module_id = (int)($_POST['module_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $video_url = trim($_POST['video_url'] ?? '');
            $video_duration = (int)($_POST['video_duration'] ?? 0);
            $position = (int)($_POST['position'] ?? 0);

            require_once '../classes/Course.php';
            require_once '../classes/Module.php';

            $course = new Course();
            $role = $user['role'] ?? null;
            if (!in_array($role, ['teacher', 'admin'], true)) {
                throw new Exception('Access denied');
            }

            if ($role === 'teacher') {
                $course_data = $course->getCourseById($course_id);
                if (!$course_data || (int)($course_data['teacher_id'] ?? 0) !== (int)$user['id']) {
                    throw new Exception('Access denied');
                }
            }

            $lessonModel = new Lesson();
            $result = $lessonModel->createLesson($module_id, $course_id, $title, $description, $video_url, $video_duration, $position);
            echo json_encode($result);
            break;

        case 'delete':
            // Teacher/Admin: soft delete lesson
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $lesson_id = (int)($_POST['lesson_id'] ?? 0);
            if (!$lesson_id) {
                throw new Exception('Lesson ID required');
            }

            require_once '../classes/Course.php';
            $role = $user['role'] ?? null;
            if (!in_array($role, ['teacher', 'admin'], true)) {
                throw new Exception('Access denied');
            }

            if ($role === 'teacher') {
                // Verify lesson belongs to teacher's course
                $lessonModel = new Lesson();
                $lesson_data = $lessonModel->getLessonById($lesson_id);
                if (!$lesson_data) throw new Exception('Lesson not found');
                $course_data = (new Course())->getCourseById((int)$lesson_data['course_id']);
                if (!$course_data || (int)($course_data['teacher_id'] ?? 0) !== (int)$user['id']) {
                    throw new Exception('Access denied');
                }
            }

            $progress = new LessonProgress();
            $lessonModel = new Lesson();
            $result = $lessonModel->deleteLesson($lesson_id);
            echo json_encode($result);
            break;

        case 'update':
            // Teacher/Admin: edit lesson fields (title, description, video_url, duration, position, publish state)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            require_once '../classes/Course.php';
            $role = $user['role'] ?? null;
            if (!in_array($role, ['teacher', 'admin'], true)) {
                throw new Exception('Access denied');
            }

            $lesson_id = (int)($_POST['lesson_id'] ?? 0);
            if (!$lesson_id) throw new Exception('Lesson ID required');

            $lessonModel = new Lesson();
            $lesson_data = $lessonModel->getLessonById($lesson_id);
            if (!$lesson_data) throw new Exception('Lesson not found');

            if ($role === 'teacher') {
                $course_data = (new Course())->getCourseById((int)$lesson_data['course_id']);
                if (!$course_data || (int)($course_data['teacher_id'] ?? 0) !== (int)$user['id']) {
                    throw new Exception('Access denied');
                }
            }

            $data = [];
            if (isset($_POST['title'])) {
                $t = trim($_POST['title']);
                if ($t === '') throw new Exception('Title cannot be empty');
                $data['title'] = $t;
            }
            if (isset($_POST['description'])) $data['description'] = trim($_POST['description']);
            if (isset($_POST['video_url'])) $data['video_url'] = trim($_POST['video_url']);
            if (isset($_POST['video_duration'])) $data['video_duration'] = max(0, (int)$_POST['video_duration']);
            if (isset($_POST['position'])) $data['position'] = max(0, (int)$_POST['position']);
            if (isset($_POST['is_active'])) $data['is_active'] = (int)$_POST['is_active'] ? 1 : 0;
            if (empty($data)) throw new Exception('Nothing to update');

            echo json_encode($lessonModel->updateLesson($lesson_id, $data));
            break;

        case 'upload_video':
            // Teacher/Admin: upload MP4/MOV/WEBM/AVI for a lesson (replaces previous upload)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            require_once '../classes/Course.php';
            require_once '../classes/Uploader.php';

            $role = $user['role'] ?? null;
            if (!in_array($role, ['teacher', 'admin'], true)) {
                throw new Exception('Access denied');
            }

            $lesson_id = (int)($_POST['lesson_id'] ?? 0);
            if (!$lesson_id) throw new Exception('Lesson ID required');
            if (!isset($_FILES['video'])) throw new Exception('Video file required');

            $lessonModel = new Lesson();
            $lesson_data = $lessonModel->getLessonById($lesson_id);
            if (!$lesson_data) throw new Exception('Lesson not found');

            if ($role === 'teacher') {
                $course_data = (new Course())->getCourseById((int)$lesson_data['course_id']);
                if (!$course_data || (int)($course_data['teacher_id'] ?? 0) !== (int)$user['id']) {
                    throw new Exception('Access denied');
                }
            }

            $uploader = new Uploader();
            $upload = $uploader->upload($_FILES['video'], 'video', (int)$user['id']);
            if (!$upload['success']) throw new Exception($upload['message']);

            // Replacement: remove the previous internally-stored video file (external URLs are left alone)
            $old = (string)($lesson_data['video_url'] ?? '');
            if ($old !== '' && strpos($old, 'assets/uploads/videos/') === 0) {
                $uploader->removeStoredFile($old);
            }

            $duration = (int)($_POST['video_duration'] ?? 0);
            $result = $lessonModel->updateLesson($lesson_id, [
                'video_url'      => $upload['path'],
                'video_duration' => $duration > 0 ? $duration : (int)($lesson_data['video_duration'] ?? 0),
            ]);
            if (empty($result['success'])) throw new Exception($result['message'] ?? 'Failed to attach video');

            echo json_encode([
                'success'    => true,
                'message'    => 'Video uploaded (' . Uploader::humanSize((int)$upload['size']) . ')',
                'video_url'  => $upload['path'],
                'human_size' => Uploader::humanSize((int)$upload['size']),
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
