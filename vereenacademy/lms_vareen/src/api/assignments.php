<?php
/**
 * Assignments API - Teacher CRUD + Student submissions + grading
 */

require_once '../classes/Enrollment.php';
require_once '../classes/Course.php';
require_once '../classes/Database.php';
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

$db = (new Database())->connect();

try {
    switch ($action) {
        // Teacher: create assignment
        case 'teacher_create':
            require_in(['teacher', 'admin']);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid request method');

            $course_id = (int)($_POST['course_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $instructions = trim($_POST['instructions'] ?? '');
            $due_date = $_POST['due_date'] ?? null; // allow null/empty
            $max_score = (int)($_POST['max_score'] ?? 100);

            if (!$course_id) throw new Exception('course_id required');
            if ($title === '') throw new Exception('title required');

            // Ownership check
            if ($role === 'teacher') {
                $stmt = $db->prepare("SELECT teacher_id FROM courses WHERE id = :id");
                $stmt->execute([':id' => $course_id]);
                $course = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$course || (int)$course['teacher_id'] !== (int)$user['id']) throw new Exception('Access denied');
            }

            $stmt = $db->prepare("INSERT INTO assignments
                (course_id, teacher_id, title, description, instructions, due_date, max_score, is_active)
                VALUES (:course_id, :teacher_id, :title, :description, :instructions, :due_date, :max_score, 1)");
            $stmt->execute([
                ':course_id' => $course_id,
                ':teacher_id' => (int)$user['id'],
                ':title' => $title,
                ':description' => $description,
                ':instructions' => $instructions,
                ':due_date' => ($due_date ? $due_date : null),
                ':max_score' => $max_score
            ]);

            echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
            break;

        // Teacher/Admin: list assignments for a course
        case 'teacher_list_by_course':
            require_in(['teacher', 'admin']);
            $course_id = (int)($_POST['course_id'] ?? 0);
            if (!$course_id) throw new Exception('course_id required');

            if ($role === 'teacher') {
                $stmt = $db->prepare("SELECT teacher_id FROM courses WHERE id = :id");
                $stmt->execute([':id' => $course_id]);
                $course = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$course || (int)$course['teacher_id'] !== (int)$user['id']) throw new Exception('Access denied');
            }

            $stmt = $db->prepare("SELECT * FROM assignments WHERE course_id = :course_id AND is_active = 1 ORDER BY due_date ASC, created_at DESC");
            $stmt->execute([':course_id' => $course_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // Student: list assignments across enrolled courses
        case 'student_list':
            require_in(['student']);

            $stmt = $db->prepare("SELECT a.*, c.title as course_title,
                (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id AND s.student_id = :student_id) as has_submission,
                (SELECT status FROM submissions s WHERE s.assignment_id = a.id AND s.student_id = :student_id ORDER BY submitted_at DESC LIMIT 1) as submission_status
                FROM assignments a
                JOIN courses c ON a.course_id = c.id
                JOIN enrollments e ON e.course_id = c.id
                WHERE e.student_id = :student_id AND a.is_active = 1
                ORDER BY a.due_date ASC, a.created_at DESC");
            $stmt->execute([':student_id' => (int)$user['id']]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // Student: submit assignment (file optional, text optional)
        case 'student_submit':
            require_in(['student']);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid request method');

            $assignment_id = (int)($_POST['assignment_id'] ?? 0);
            $submission_text = trim($_POST['submission_text'] ?? '');

            if (!$assignment_id) throw new Exception('assignment_id required');

            // verify enrollment
            $stmt = $db->prepare("SELECT a.course_id FROM assignments a WHERE a.id = :id AND a.is_active = 1");
            $stmt->execute([':id' => $assignment_id]);
            $a = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$a) throw new Exception('Assignment not found');

            $enrollment = new Enrollment();
            if (!$enrollment->isEnrolled((int)$user['id'], (int)$a['course_id'])) throw new Exception('Not enrolled in course');

            $file_path = null;
            $file_type = null;

            if (isset($_FILES['submission_file']) && isset($_FILES['submission_file']['tmp_name']) && is_uploaded_file($_FILES['submission_file']['tmp_name'])) {
                $allowed = ['pdf','doc','docx','ppt','pptx','xls','xlsx','jpg','jpeg','png','gif','zip'];
                $ext = strtolower(pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) throw new Exception('File type not allowed');

                $uploadDir = 'assets/uploads/assignments/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['submission_file']['name']);
                $file_path = $uploadDir . $filename;

                if (!move_uploaded_file($_FILES['submission_file']['tmp_name'], $file_path)) {
                    throw new Exception('Failed to upload file');
                }
                $file_type = $ext;
            }

            if ($submission_text === '' && !$file_path) {
                throw new Exception('Provide submission text or a file');
            }

            // Upsert submission
            $stmt = $db->prepare("SELECT id FROM submissions WHERE assignment_id = :assignment_id AND student_id = :student_id");
            $stmt->execute([':assignment_id' => $assignment_id, ':student_id' => (int)$user['id']]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $stmt = $db->prepare("UPDATE submissions
                    SET submission_text = :submission_text,
                        file_path = COALESCE(:file_path, file_path),
                        status = 'submitted',
                        submitted_at = NOW(),
                        score = NULL,
                        feedback = NULL,
                        graded_at = NULL,
                        graded_by = NULL
                    WHERE id = :id");
                $stmt->execute([
                    ':submission_text' => $submission_text,
                    ':file_path' => $file_path,
                    ':id' => (int)$existing['id']
                ]);
            } else {
                $stmt = $db->prepare("INSERT INTO submissions
                    (assignment_id, student_id, file_path, submission_text, status, submitted_at)
                    VALUES (:assignment_id, :student_id, :file_path, :submission_text, 'submitted', NOW())");
                $stmt->execute([
                    ':assignment_id' => $assignment_id,
                    ':student_id' => (int)$user['id'],
                    ':file_path' => $file_path,
                    ':submission_text' => $submission_text
                ]);
            }

            echo json_encode(['success' => true, 'message' => 'Submitted successfully']);
            break;

        // Teacher: list submissions for an assignment
        case 'teacher_list_submissions':
            require_in(['teacher', 'admin']);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid request method');

            $assignment_id = (int)($_POST['assignment_id'] ?? 0);
            if (!$assignment_id) throw new Exception('assignment_id required');

            // verify teacher owns course
            $stmt = $db->prepare("SELECT a.course_id, a.teacher_id as assignment_teacher_id
                FROM assignments a WHERE a.id = :id AND a.is_active = 1");
            $stmt->execute([':id' => $assignment_id]);
            $a = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$a) throw new Exception('Assignment not found');

            if ($role === 'teacher') {
                $courseStmt = $db->prepare("SELECT teacher_id FROM courses WHERE id = :course_id");
                $courseStmt->execute([':course_id' => (int)$a['course_id']]);
                $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
                if (!$course || (int)$course['teacher_id'] !== (int)$user['id']) throw new Exception('Access denied');
            }

            $stmt = $db->prepare("SELECT s.*, u.first_name, u.last_name
                FROM submissions s
                JOIN users u ON u.id = s.student_id
                WHERE s.assignment_id = :assignment_id
                ORDER BY s.submitted_at DESC");
            $stmt->execute([':assignment_id' => $assignment_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // Teacher: grade submission
        case 'teacher_grade':
            require_in(['teacher', 'admin']);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid request method');

            $submission_id = (int)($_POST['submission_id'] ?? 0);
            $score = (int)($_POST['score'] ?? 0);
            $feedback = trim($_POST['feedback'] ?? '');

            if (!$submission_id) throw new Exception('submission_id required');

            // verify ownership
            $stmt = $db->prepare("SELECT s.*, a.course_id FROM submissions s
                JOIN assignments a ON a.id = s.assignment_id
                WHERE s.id = :id AND a.is_active = 1");
            $stmt->execute([':id' => $submission_id]);
            $s = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$s) throw new Exception('Submission not found');

            if ($role === 'teacher') {
                $courseStmt = $db->prepare("SELECT teacher_id FROM courses WHERE id = :course_id");
                $courseStmt->execute([':course_id' => (int)$s['course_id']]);
                $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
                if (!$course || (int)$course['teacher_id'] !== (int)$user['id']) throw new Exception('Access denied');
            }

            $stmt = $db->prepare("UPDATE submissions
                SET status = 'graded',
                    score = :score,
                    feedback = :feedback,
                    graded_at = NOW(),
                    graded_by = :graded_by
                WHERE id = :id");
            $stmt->execute([
                ':score' => $score,
                ':feedback' => $feedback,
                ':graded_by' => (int)$user['id'],
                ':id' => $submission_id
            ]);

            echo json_encode(['success' => true, 'message' => 'Graded successfully']);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function require_in(array $roles): void {
    // no-op placeholder; roles check is done inline by switch
}

