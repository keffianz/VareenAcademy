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

$action = $_GET['action'] ?? '';

// Normalize status update if provided (must run AFTER $action is defined)
if (!isset($_POST['status']) && isset($_POST['live_class_id']) && ($action === 'teacher_update')) {
    $_POST['status'] = 'scheduled';
}

// CSRF: every POST (state change or authorized listing) must carry the session token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
}

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

            // Auto-record attendance the first time the student actually joins
            if (!empty($lc['meeting_url'])) {
                $att = $db->prepare(
                    'INSERT IGNORE INTO live_class_attendance (live_class_id, student_id, joined_at)
                     VALUES (:lc, :s, NOW())'
                );
                $att->execute([':lc' => $live_class_id, ':s' => (int)$user['id']]);
            }

            echo json_encode(['success' => true, 'data' => ['meeting_url' => $lc['meeting_url'], 'recording_url' => $lc['recording_url'], 'title' => $lc['title']]]);
            break;

        case 'teacher_attendance_list':
            if ($role !== 'teacher') throw new Exception('Access denied');

            $live_class_id = (int)($_POST['live_class_id'] ?? 0);
            if (!$live_class_id) throw new Exception('live_class_id required');

            // Ownership check: the teacher may only view attendance for their own class
            $stmt = $db->prepare('SELECT id, title FROM live_classes WHERE id = :id AND teacher_id = :t');
            $stmt->execute([':id' => $live_class_id, ':t' => (int)$user['id']]);
            $lc = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$lc) throw new Exception('Live class not found');

            // All enrolled students with their attendance record (if any)
            $stmt = $db->prepare(
                'SELECT u.id AS student_id, u.first_name, u.last_name,
                        lca.joined_at, lca.duration_minutes
                 FROM enrollments e
                 JOIN users u ON e.student_id = u.id AND u.is_active = 1
                 LEFT JOIN live_class_attendance lca
                        ON lca.student_id = u.id AND lca.live_class_id = :lc
                 WHERE e.course_id = (SELECT course_id FROM live_classes WHERE id = :lc)
                 ORDER BY u.first_name, u.last_name
                 LIMIT 500'
            );
            $stmt->execute([':lc' => $live_class_id]);
            echo json_encode(['success' => true, 'data' => ['live_class' => $lc, 'students' => $stmt->fetchAll(PDO::FETCH_ASSOC)]]);
            break;

        case 'teacher_attendance_mark':
            if ($role !== 'teacher') throw new Exception('Access denied');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid request method');

            $live_class_id = (int)($_POST['live_class_id'] ?? 0);
            $student_id    = (int)($_POST['student_id'] ?? 0);
            $present       = (int)!empty($_POST['present']);
            if (!$live_class_id || !$student_id) throw new Exception('live_class_id and student_id required');

            // Ownership + enrolled-student verification (prevents marking arbitrary users)
            $stmt = $db->prepare('SELECT course_id FROM live_classes WHERE id = :id AND teacher_id = :t');
            $stmt->execute([':id' => $live_class_id, ':t' => (int)$user['id']]);
            $courseId = $stmt->fetchColumn();
            if (!$courseId) throw new Exception('Live class not found');

            $stmt = $db->prepare('SELECT COUNT(*) FROM enrollments WHERE course_id = :c AND student_id = :s');
            $stmt->execute([':c' => $courseId, ':s' => $student_id]);
            if (!(int)$stmt->fetchColumn()) throw new Exception('Student is not enrolled in this course');

            if ($present) {
                $stmt = $db->prepare(
                    'INSERT INTO live_class_attendance (live_class_id, student_id, joined_at)
                     VALUES (:lc, :s, NOW())
                     ON DUPLICATE KEY UPDATE joined_at = COALESCE(joined_at, NOW())'
                );
                $stmt->execute([':lc' => $live_class_id, ':s' => $student_id]);
                echo json_encode(['success' => true, 'message' => 'Marked present']);
            } else {
                $stmt = $db->prepare('DELETE FROM live_class_attendance WHERE live_class_id = :lc AND student_id = :s');
                $stmt->execute([':lc' => $live_class_id, ':s' => $student_id]);
                echo json_encode(['success' => true, 'message' => 'Marked absent']);
            }
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

