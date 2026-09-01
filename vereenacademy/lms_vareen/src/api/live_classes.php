<?php
/**
 * Live Classes API - Teacher CRUD + Student join (no attendance tracking)
 */

require_once '../classes/Enrollment.php';
require_once '../classes/Course.php';
require_once '../classes/Notification.php';
require_once '../middleware/auth.php';

header('Content-Type: application/json');

$user = checkAuth();
$role = $user['role'] ?? null;

if (!in_array($role, ['teacher', 'admin', 'student'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Helper: normalize status update if provided
if (!isset($_POST['status']) && isset($_POST['live_class_id']) && ($action === 'teacher_update')) {
    $_POST['status'] = 'scheduled';
}


$action = $_GET['action'] ?? '';

// Inline DB usage via Database class patterns
require_once '../classes/Database.php';
$db = (new Database())->connect();

try {
    switch ($action) {
        case 'teacher_list':
            require_once '../classes/Course.php';
            if (!in_array($role, ['teacher', 'admin'], true)) throw new Exception('Access denied');

            $course_id = (int)($_POST['course_id'] ?? 0);
            if ($role === 'teacher') {
                if (!$course_id) throw new Exception('course_id required');
                // verify teacher owns course
                $course = (new Course())->getCourseById($course_id);
                if (!$course || (int)($course['teacher_id'] ?? 0) !== (int)$user['id']) {
                    throw new Exception('Access denied');
                }
            }

            if (!$course_id) {
                // list all live classes
                $stmt = $db->query("SELECT lc.*, c.title as course_title, u.first_name, u.last_name
                                    FROM live_classes lc
                                    JOIN courses c ON lc.course_id = c.id
                                    JOIN users u ON lc.teacher_id = u.id
                                    ORDER BY lc.scheduled_at DESC");
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                break;
            }

            $stmt = $db->prepare("SELECT lc.*, c.title as course_title, u.first_name, u.last_name
                                  FROM live_classes lc
                                  JOIN courses c ON lc.course_id = c.id
                                  JOIN users u ON lc.teacher_id = u.id
                                  WHERE lc.course_id = :course_id
                                  ORDER BY lc.scheduled_at DESC");
            $stmt->execute([':course_id' => $course_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'teacher_create':
            if (!in_array($role, ['teacher', 'admin'], true)) throw new Exception('Access denied');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid request method');

            $course_id = (int)($_POST['course_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $scheduled_at = $_POST['scheduled_at'] ?? '';
            $meeting_url = trim($_POST['meeting_url'] ?? '');
            $meeting_platform = trim($_POST['meeting_platform'] ?? '');
            $duration_minutes = (int)($_POST['duration_minutes'] ?? 0);
            $recording_url = trim($_POST['recording_url'] ?? '');

            if (!$course_id || $title === '' || $scheduled_at === '' || $meeting_url === '') {
                throw new Exception('Missing required fields');
            }

            $course = (new Course())->getCourseById($course_id);
            if (!$course) throw new Exception('Course not found');
            if ($role === 'teacher' && (int)($course['teacher_id'] ?? 0) !== (int)$user['id']) {
                throw new Exception('Access denied');
            }

            $stmt = $db->prepare("INSERT INTO live_classes
                    (course_id, teacher_id, title, description, scheduled_at, meeting_url, meeting_platform, duration_minutes, recording_url)
                    VALUES (:course_id, :teacher_id, :title, :description, :scheduled_at, :meeting_url, :meeting_platform, :duration_minutes, :recording_url)");
            $stmt->execute([
                ':course_id' => $course_id,
                ':teacher_id' => (int)$user['id'],
                ':title' => $title,
                ':description' => $description,
                ':scheduled_at' => $scheduled_at,
                ':meeting_url' => $meeting_url,
                ':meeting_platform' => $meeting_platform,
                ':duration_minutes' => $duration_minutes,
                ':recording_url' => $recording_url
            ]);

            echo json_encode(['success' => true, 'message' => 'Live class created', 'id' => $db->lastInsertId()]);
            break;

        case 'teacher_update':
            if (!in_array($role, ['teacher', 'admin'], true)) throw new Exception('Access denied');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid request method');

            $live_class_id = (int)($_POST['live_class_id'] ?? 0);
            if (!$live_class_id) throw new Exception('live_class_id required');

            $stmt = $db->prepare("SELECT * FROM live_classes WHERE id = :id");
            $stmt->execute([':id' => $live_class_id]);
            $lc = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$lc) throw new Exception('Live class not found');

            $course = (new Course())->getCourseById((int)$lc['course_id']);
            if (!$course) throw new Exception('Course not found');
            if ($role === 'teacher' && (int)($course['teacher_id'] ?? 0) !== (int)$user['id']) {
                throw new Exception('Access denied');
            }

            $fields = [];
            $params = [':id' => $live_class_id];
            foreach (['title','description','scheduled_at','meeting_url','meeting_platform','duration_minutes','recording_url','status'] as $f) {
                if (isset($_POST[$f])) {
                    $key = ':'.$f;
                    $fields[] = $f.' = '.$key;
                    $params[$key] = $_POST[$f];
                }
            }
            if (empty($fields)) throw new Exception('No data to update');

            $sql = "UPDATE live_classes SET ".implode(', ', $fields)." WHERE id = :id";
            $upd = $db->prepare($sql);
            $upd->execute($params);

            echo json_encode(['success' => true, 'message' => 'Live class updated']);
            break;

        case 'teacher_delete':
            if (!in_array($role, ['teacher', 'admin'], true)) throw new Exception('Access denied');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid request method');

            $live_class_id = (int)($_POST['live_class_id'] ?? 0);
            if (!$live_class_id) throw new Exception('live_class_id required');

            $stmt = $db->prepare("SELECT * FROM live_classes WHERE id = :id");
            $stmt->execute([':id' => $live_class_id]);
            $lc = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$lc) throw new Exception('Live class not found');

            if ($role === 'teacher' && (int)$lc['teacher_id'] !== (int)$user['id']) throw new Exception('Access denied');

            $del = $db->prepare("DELETE FROM live_classes WHERE id = :id");
            $del->execute([':id' => $live_class_id]);

            echo json_encode(['success' => true, 'message' => 'Live class deleted']);
            break;

        case 'student_get_join':
            // Student join UI just returns meeting url
            if ($role !== 'student') throw new Exception('Access denied');

            $live_class_id = (int)($_POST['live_class_id'] ?? 0);
            if (!$live_class_id) throw new Exception('live_class_id required');

            $stmt = $db->prepare("SELECT lc.*, c.title as course_title FROM live_classes lc
                                  JOIN courses c ON lc.course_id = c.id
                                  WHERE lc.id = :id AND lc.status IN ('scheduled','ongoing','completed')");
            $stmt->execute([':id' => $live_class_id]);
            $lc = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$lc) throw new Exception('Live class not found');

            $enrollment = new Enrollment();
            if (!$enrollment->isEnrolled((int)$user['id'], (int)$lc['course_id'])) {
                throw new Exception('You are not enrolled in this course');
            }

            echo json_encode(['success' => true, 'data' => ['meeting_url' => $lc['meeting_url'], 'recording_url' => $lc['recording_url'], 'title' => $lc['title']]]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

