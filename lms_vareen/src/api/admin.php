<?php
/**
 * Admin API - users, courses, enrollment, reports, settings,
 * instructor applications and certificate management.
 * Admin-only. CSRF enforced on every POST (server-side).
 */
require_once '../classes/Database.php';
require_once '../classes/Certificate.php';
require_once '../middleware/auth.php';
header('Content-Type: application/json');

try {
    $user = checkAuth();
    if (($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireCsrf();
    }

    $action = $_GET['action'] ?? '';
    $db = (new Database())->connect();

    switch ($action) {

        case 'users_list': {
            $roleFilter = in_array($_GET['role'] ?? '', ['student', 'teacher', 'admin'], true) ? $_GET['role'] : null;
            $q = trim($_GET['q'] ?? '');
            $sql = 'SELECT id, first_name, last_name, email, role, is_active, specialization, created_at FROM users';
            $where = [];
            $params = [];
            if ($roleFilter) {
                $where[] = 'role = :role';
                $params[':role'] = $roleFilter;
            }
            if ($q !== '') {
                $where[] = '(email LIKE :q OR CONCAT(first_name, " ", last_name) LIKE :q)';
                $params[':q'] = '%' . $q . '%';
            }
            if ($where) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= ' ORDER BY created_at DESC LIMIT 500';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
        }

        case 'user_set_active': {
            $targetId = (int)($_POST['user_id'] ?? 0);
            $isActive = (int)!empty($_POST['is_active']);
            if (!$targetId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'user_id required']);
                break;
            }
            if ($targetId === (int)$user['id'] && !$isActive) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account']);
                break;
            }
            $stmt = $db->prepare('UPDATE users SET is_active = :a WHERE id = :id');
            $stmt->execute([':a' => $isActive, ':id' => $targetId]);
            echo json_encode(['success' => true, 'message' => 'Account updated']);
            break;
        }

        case 'user_create': {
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName  = trim($_POST['last_name'] ?? '');
            $email     = strtolower(trim($_POST['email'] ?? ''));
            $role      = $_POST['role'] ?? '';
            $password  = $_POST['password'] ?? '';

            if ($firstName === '' || $lastName === '' || $email === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'First name, last name and email are required']);
                break;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid email address']);
                break;
            }
            if (!in_array($role, ['student', 'teacher', 'admin'], true)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid role']);
                break;
            }
            if (strlen($password) < 8) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
                break;
            }
            $dup = $db->prepare('SELECT id FROM users WHERE email = :email AND role = :role');
            $dup->execute([':email' => $email, ':role' => $role]);
            if ($dup->fetchColumn()) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'A ' . $role . ' account with this email already exists']);
                break;
            }
            $stmt = $db->prepare('INSERT INTO users (first_name, last_name, email, password, role, is_active, email_verified)
                                  VALUES (:f, :l, :e, :p, :r, 1, 1)');
            $stmt->execute([
                ':f' => $firstName, ':l' => $lastName, ':e' => $email,
                ':p' => password_hash($password, PASSWORD_BCRYPT), ':r' => $role,
            ]);
            echo json_encode(['success' => true, 'message' => ucfirst($role) . ' account created', 'id' => (int)$db->lastInsertId()]);
            break;
        }

        case 'courses_list': {
            $stmt = $db->prepare(
                'SELECT c.id, c.title, c.category, c.is_active, c.teacher_id,
                        CONCAT(u.first_name, " ", u.last_name) AS teacher_name,
                        (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS enrolled_count,
                        (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id AND l.is_active = 1) AS lesson_count
                 FROM courses c LEFT JOIN users u ON u.id = c.teacher_id
                 ORDER BY c.created_at DESC LIMIT 500'
            );
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
        }

        case 'course_set_active': {
            $courseId = (int)($_POST['course_id'] ?? 0);
            $isActive = (int)!empty($_POST['is_active']);
            if (!$courseId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'course_id required']);
                break;
            }
            $stmt = $db->prepare('UPDATE courses SET is_active = :a WHERE id = :id');
            $stmt->execute([':a' => $isActive, ':id' => $courseId]);
            echo json_encode(['success' => true, 'message' => 'Course updated']);
            break;
        }

        case 'course_assign_teacher': {
            $courseId  = (int)($_POST['course_id'] ?? 0);
            $teacherId = (int)($_POST['teacher_id'] ?? 0);
            if (!$courseId || !$teacherId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'course_id and teacher_id required']);
                break;
            }
            $chk = $db->prepare('SELECT id FROM users WHERE id = :id AND role = "teacher"');
            $chk->execute([':id' => $teacherId]);
            if (!$chk->fetchColumn()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Selected user is not a teacher']);
                break;
            }
            $stmt = $db->prepare('UPDATE courses SET teacher_id = :t WHERE id = :id');
            $stmt->execute([':t' => $teacherId, ':id' => $courseId]);
            echo json_encode(['success' => true, 'message' => 'Teacher assigned']);
            break;
        }

        case 'course_create': {
            $title = trim($_POST['title'] ?? '');
            $desc  = trim($_POST['description'] ?? '');
            $cat   = trim($_POST['category'] ?? '');
            $teacherId = (int)($_POST['teacher_id'] ?? 0);
            if ($title === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Title required']);
                break;
            }
            if ($teacherId) {
                $chk = $db->prepare('SELECT id FROM users WHERE id = :id AND role = "teacher"');
                $chk->execute([':id' => $teacherId]);
                if (!$chk->fetchColumn()) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Selected user is not a teacher']);
                    break;
                }
            }
            $stmt = $db->prepare('INSERT INTO courses (teacher_id, title, description, category, price, is_active)
                                  VALUES (:t, :title, :d, :c, 0, 1)');
            $stmt->execute([':t' => $teacherId ?: null, ':title' => $title, ':d' => $desc, ':c' => $cat]);
            echo json_encode(['success' => true, 'message' => 'Course created', 'id' => (int)$db->lastInsertId()]);
            break;
        }

        case 'enroll_student': {
            $studentId = (int)($_POST['student_id'] ?? 0);
            $courseId  = (int)($_POST['course_id'] ?? 0);
            if (!$studentId || !$courseId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'student_id and course_id required']);
                break;
            }
            $chk = $db->prepare('SELECT id FROM users WHERE id = :id AND role = "student"');
            $chk->execute([':id' => $studentId]);
            if (!$chk->fetchColumn()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Selected user is not a student']);
                break;
            }
            $chk = $db->prepare('SELECT id FROM courses WHERE id = :id');
            $chk->execute([':id' => $courseId]);
            if (!$chk->fetchColumn()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Course not found']);
                break;
            }
            $dup = $db->prepare('SELECT id FROM enrollments WHERE student_id = :s AND course_id = :c');
            $dup->execute([':s' => $studentId, ':c' => $courseId]);
            if ($dup->fetchColumn()) {
                echo json_encode(['success' => true, 'message' => 'Student already enrolled']);
                break;
            }
            $stmt = $db->prepare("INSERT INTO enrollments (student_id, course_id, progress, status) VALUES (:s, :c, 0, 'active')");
            $stmt->execute([':s' => $studentId, ':c' => $courseId]);
            echo json_encode(['success' => true, 'message' => 'Student enrolled']);
            break;
        }

        case 'reports': {
            // Platform totals (real counts, no fabricated numbers)
            $totals = [];
            foreach (['users', 'students', 'teachers', 'courses', 'enrollments', 'assignments', 'quizzes', 'certificates'] as $k) {
                $map = [
                    'users' => 'SELECT COUNT(*) FROM users',
                    'students' => 'SELECT COUNT(*) FROM users WHERE role = "student"',
                    'teachers' => 'SELECT COUNT(*) FROM users WHERE role = "teacher"',
                    'courses' => 'SELECT COUNT(*) FROM courses',
                    'enrollments' => 'SELECT COUNT(*) FROM enrollments',
                    'assignments' => 'SELECT COUNT(*) FROM assignments WHERE is_active = 1',
                    'quizzes' => 'SELECT COUNT(*) FROM quizzes WHERE is_active = 1',
                    'certificates' => 'SELECT COUNT(*) FROM certificates WHERE revoked = 0',
                ];
                $totals[$k] = (int)$db->query($map[$k])->fetchColumn();
            }

            // Per-course academic report: enrollment, completion, assignment, quiz, attendance
            $perCourse = $db->prepare(
                'SELECT c.id, c.title,
                        (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS enrolled,
                        (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id AND e.status = "completed") AS completed,
                        (SELECT COUNT(*) FROM assignments a WHERE a.course_id = c.id AND a.is_active = 1) AS assignments,
                        (SELECT COUNT(*) FROM submissions s JOIN assignments a ON s.assignment_id = a.id WHERE a.course_id = c.id) AS submissions,
                        (SELECT COUNT(*) FROM submissions s JOIN assignments a ON s.assignment_id = a.id WHERE a.course_id = c.id AND s.status = "graded") AS graded,
                        (SELECT COUNT(*) FROM quizzes q WHERE q.course_id = c.id AND q.is_active = 1) AS quizzes,
                        (SELECT COUNT(*) FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.id WHERE q.course_id = c.id AND qa.status != "in_progress") AS quiz_attempts,
                        (SELECT COALESCE(AVG(qa.percentage), 0) FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.id WHERE q.course_id = c.id AND qa.status != "in_progress") AS avg_quiz_score,
                        (SELECT COUNT(*) FROM live_class_attendance lca JOIN live_classes lc ON lca.live_class_id = lc.id WHERE lc.course_id = c.id) AS attendance_records
                 FROM courses c ORDER BY c.title LIMIT 200'
            );
            $perCourse->execute();

            echo json_encode([
                'success' => true,
                'totals' => $totals,
                'per_course' => $perCourse->fetchAll(PDO::FETCH_ASSOC),
            ]);
            break;
        }

        case 'settings_get': {
            $rows = $db->query('SELECT setting_key, setting_value FROM settings')->fetchAll(PDO::FETCH_ASSOC);
            $settings = [];
            foreach ($rows as $r) {
                $settings[$r['setting_key']] = $r['setting_value'];
            }
            echo json_encode(['success' => true, 'data' => $settings]);
            break;
        }

        case 'settings_update': {
            $allowed = ['site_name', 'support_email'];
            $stmt = $db->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
                                  ON DUPLICATE KEY UPDATE setting_value = :v2');
            $saved = 0;
            foreach ($allowed as $key) {
                if (isset($_POST[$key])) {
                    $val = trim($_POST[$key]);
                    if ($key === 'support_email' && $val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invalid support email']);
                        break 2;
                    }
                    $stmt->execute([':k' => $key, ':v' => $val, ':v2' => $val]);
                    $saved++;
                }
            }
            echo json_encode(['success' => true, 'message' => $saved . ' setting(s) saved']);
            break;
        }

        /* =====================
         * Instructor applications (admin review queue)
         * ===================== */
        case 'applications_list': {
            $status = in_array($_GET['status'] ?? '', ['pending', 'approved', 'rejected'], true) ? $_GET['status'] : null;
            $sql = 'SELECT a.*, CONCAT(r.first_name, " ", r.last_name) AS reviewer_name
                    FROM instructor_applications a
                    LEFT JOIN users r ON r.id = a.reviewed_by';
            $params = [];
            if ($status) {
                $sql .= ' WHERE a.status = :st';
                $params[':st'] = $status;
            }
            $sql .= ' ORDER BY a.created_at DESC LIMIT 300';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
        }

        case 'application_review': {
            $appId    = (int)($_POST['application_id'] ?? 0);
            $decision = $_POST['decision'] ?? '';
            $promote  = !empty($_POST['promote']);
            if (!$appId || !in_array($decision, ['approved', 'rejected'], true)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'application_id and a valid decision are required']);
                break;
            }

            $stmt = $db->prepare('SELECT * FROM instructor_applications WHERE id = :id AND status = "pending"');
            $stmt->execute([':id' => $appId]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$application) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Application not found or already reviewed']);
                break;
            }

            $stmt = $db->prepare(
                'UPDATE instructor_applications SET status = :d, reviewed_by = :r, reviewed_at = NOW() WHERE id = :id'
            );
            $stmt->execute([':d' => $decision, ':r' => (int)$user['id'], ':id' => $appId]);

            $promoted = 0;
            if ($decision === 'approved' && $promote) {
                // Promote the applicant's STUDENT account (matched by email + role) to teacher.
                $stmt = $db->prepare(
                    'UPDATE users SET role = "teacher",
                            specialization = COALESCE(specialization, :spec)
                     WHERE email = :e AND role = "student" AND is_active = 1'
                );
                $stmt->execute([
                    ':spec' => $application['specialization'] ?: null,
                    ':e'    => strtolower(trim($application['email'])),
                ]);
                $promoted = $stmt->rowCount();
            }

            echo json_encode([
                'success'  => true,
                'message'  => 'Application ' . $decision . ($promoted ? ", {$promoted} account(s) promoted to teacher" : ''),
                'promoted' => $promoted,
            ]);
            break;
        }

        /* =====================
         * Certificates
         * ===================== */
        case 'certificates_list': {
            $stmt = $db->prepare(
                'SELECT cert.id, cert.certificate_code, cert.issued_at, cert.revoked, cert.revoked_at,
                        CONCAT(u.first_name, " ", u.last_name) AS student_name, u.email AS student_email,
                        c.title AS course_title
                 FROM certificates cert
                 JOIN users u ON u.id = cert.student_id
                 JOIN courses c ON c.id = cert.course_id
                 ORDER BY cert.issued_at DESC
                 LIMIT 300'
            );
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
        }

        case 'certificate_issue': {
            $certificate = new Certificate();
            $result = $certificate->issueForStudentEmail($_POST['email'] ?? '', (int)($_POST['course_id'] ?? 0));
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
            break;
        }

        case 'certificate_revoke': {
            $certId  = (int)($_POST['certificate_id'] ?? 0);
            $revoke  = (int)!empty($_POST['revoke']);
            if (!$certId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'certificate_id required']);
                break;
            }
            $certificate = new Certificate();
            if ($certificate->setRevoked($certId, (bool)$revoke)) {
                echo json_encode(['success' => true, 'message' => $revoke ? 'Certificate revoked' : 'Certificate restored']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Certificate not found or already updated']);
            }
            break;
        }




        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
}
