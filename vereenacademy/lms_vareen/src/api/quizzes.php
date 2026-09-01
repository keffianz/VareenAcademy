<?php
/**
 * Quizzes API - Phase 7 MVP
 */

require_once '../classes/Database.php';
require_once '../middleware/auth.php';
require_once '../classes/Notification.php';

header('Content-Type: application/json');

// Auth
$user = checkAuth();
$role = $user['role'] ?? null;
$action = $_GET['action'] ?? '';

$db = (new Database())->connect();
$notification = new Notification();

function fail($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

try {
    switch ($action) {
        /* =====================
         * Teacher CRUD
         * ===================== */
        case 'teacher_create_quiz':
            if (!in_array($role, ['teacher', 'admin'], true)) fail('Access denied');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Invalid request method');

            $course_id = (int)($_POST['course_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $instructions = trim($_POST['instructions'] ?? '');
            $time_limit_minutes = (int)($_POST['time_limit_minutes'] ?? 0);
            $pass_score = (int)($_POST['pass_score'] ?? 60);

            if (!$course_id) fail('Course ID required');
            if ($title === '') fail('Title required');

            if ($role === 'teacher') {
                $stmt = $db->prepare('SELECT teacher_id FROM courses WHERE id = :id');
                $stmt->execute([':id' => $course_id]);
                $course = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$course || (int)$course['teacher_id'] !== (int)$user['id']) fail('Access denied');
            }

            $stmt = $db->prepare('INSERT INTO quizzes (course_id, teacher_id, title, description, instructions, time_limit_minutes, pass_score) VALUES (:course_id,:teacher_id,:title,:description,:instructions,:time_limit_minutes,:pass_score)');
            $stmt->execute([
                ':course_id' => $course_id,
                ':teacher_id' => (int)$user['id'],
                ':title' => $title,
                ':description' => $description,
                ':instructions' => $instructions,
                ':time_limit_minutes' => $time_limit_minutes,
                ':pass_score' => $pass_score
            ]);

            echo json_encode(['success' => true, 'message' => 'Quiz created', 'quiz_id' => $db->lastInsertId()]);
            break;

        case 'teacher_update_quiz':
            if (!in_array($role, ['teacher', 'admin'], true)) fail('Access denied');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Invalid request method');

            $quiz_id = (int)($_POST['quiz_id'] ?? 0);
            if (!$quiz_id) fail('Quiz ID required');

            $stmt = $db->prepare('SELECT q.course_id, c.teacher_id FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = :id');
            $stmt->execute([':id' => $quiz_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) fail('Quiz not found');
            if ($role === 'teacher' && (int)$row['teacher_id'] !== (int)$user['id']) fail('Access denied');

            $fields = [];
            $params = [':id' => $quiz_id];
            foreach (['title','description','instructions','time_limit_minutes','pass_score','is_timed','is_active'] as $key) {
                if (isset($_POST[$key])) {
                    $fields[] = "$key = :$key";
                    $params[":".$key] = $_POST[$key];
                }
            }
            if (!$fields) fail('No data to update');

            $sql = 'UPDATE quizzes SET '.implode(', ', $fields).' WHERE id = :id';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['success' => true, 'message' => 'Quiz updated']);
            break;

        case 'teacher_delete_quiz':
            if (!in_array($role, ['teacher', 'admin'], true)) fail('Access denied');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Invalid request method');

            $quiz_id = (int)($_POST['quiz_id'] ?? 0);
            if (!$quiz_id) fail('Quiz ID required');

            $stmt = $db->prepare('SELECT c.teacher_id FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = :id');
            $stmt->execute([':id' => $quiz_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) fail('Quiz not found');
            if ($role === 'teacher' && (int)$row['teacher_id'] !== (int)$user['id']) fail('Access denied');

            $stmt = $db->prepare('UPDATE quizzes SET is_active = 0 WHERE id = :id');
            $stmt->execute([':id' => $quiz_id]);

            echo json_encode(['success' => true, 'message' => 'Quiz deleted']);
            break;

        case 'teacher_add_question':
            if (!in_array($role, ['teacher', 'admin'], true)) fail('Access denied');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Invalid request method');

            $quiz_id = (int)($_POST['quiz_id'] ?? 0);
            $question_text = trim($_POST['question_text'] ?? '');
            $question_type = trim($_POST['question_type'] ?? 'multiple_choice');
            $points = (int)($_POST['points'] ?? 1);
            $position = (int)($_POST['position'] ?? 0);

            if (!$quiz_id) fail('Quiz ID required');
            if ($question_text === '') fail('Question text required');

            if ($role === 'teacher') {
                $stmt = $db->prepare('SELECT c.teacher_id FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = :id');
                $stmt->execute([':id' => $quiz_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row || (int)$row['teacher_id'] !== (int)$user['id']) fail('Access denied');
            }

            $stmt = $db->prepare('INSERT INTO quiz_questions (quiz_id, question_text, question_type, points, position) VALUES (:quiz_id,:question_text,:question_type,:points,:position)');
            $stmt->execute([
                ':quiz_id' => $quiz_id,
                ':question_text' => $question_text,
                ':question_type' => $question_type,
                ':points' => $points,
                ':position' => $position
            ]);

            echo json_encode(['success' => true, 'message' => 'Question added', 'question_id' => $db->lastInsertId()]);
            break;

        case 'teacher_update_question':
            if (!in_array($role, ['teacher', 'admin'], true)) fail('Access denied');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Invalid request method');

            $question_id = (int)($_POST['question_id'] ?? 0);
            if (!$question_id) fail('Question ID required');

            if ($role === 'teacher') {
                $stmt = $db->prepare('SELECT c.teacher_id FROM quiz_questions qq JOIN quizzes q ON qq.quiz_id = q.id JOIN courses c ON q.course_id = c.id WHERE qq.id = :id');
                $stmt->execute([':id' => $question_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row || (int)$row['teacher_id'] !== (int)$user['id']) fail('Access denied');
            }

            $fields = [];
            $params = [':id' => $question_id];
            foreach (['question_text','question_type','points','position'] as $key) {
                if (isset($_POST[$key])) {
                    $fields[] = "$key = :$key";
                    $params[":".$key] = $_POST[$key];
                }
            }
            if (!$fields) fail('No data to update');

            $sql = 'UPDATE quiz_questions SET '.implode(', ', $fields).' WHERE id = :id';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['success' => true, 'message' => 'Question updated']);
            break;

        case 'teacher_delete_question':
            if (!in_array($role, ['teacher', 'admin'], true)) fail('Access denied');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Invalid request method');

            $question_id = (int)($_POST['question_id'] ?? 0);
            if (!$question_id) fail('Question ID required');

            if ($role === 'teacher') {
                $stmt = $db->prepare('SELECT c.teacher_id FROM quiz_questions qq JOIN quizzes q ON qq.quiz_id = q.id JOIN courses c ON q.course_id = c.id WHERE qq.id = :id');
                $stmt->execute([':id' => $question_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row || (int)$row['teacher_id'] !== (int)$user['id']) fail('Access denied');
            }

            $stmt = $db->prepare('DELETE FROM quiz_questions WHERE id = :id');
            $stmt->execute([':id' => $question_id]);

            echo json_encode(['success' => true, 'message' => 'Question deleted']);
            break;

        case 'teacher_add_option':
            if (!in_array($role, ['teacher', 'admin'], true)) fail('Access denied');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Invalid request method');

            $question_id = (int)($_POST['question_id'] ?? 0);
            $option_text = trim($_POST['option_text'] ?? '');
            $is_correct = isset($_POST['is_correct']) ? (int)$_POST['is_correct'] : 0;
            $position = (int)($_POST['position'] ?? 0);

            if (!$question_id) fail('Question ID required');
            if ($option_text === '') fail('Option text required');

            if ($role === 'teacher') {
                $stmt = $db->prepare('SELECT c.teacher_id FROM quiz_options o JOIN quiz_questions qq ON o.question_id = qq.id JOIN quizzes q ON qq.quiz_id = q.id JOIN courses c ON q.course_id = c.id WHERE qq.id = :qid LIMIT 1');
                $stmt->execute([':qid' => $question_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && (int)$row['teacher_id'] !== (int)$user['id']) fail('Access denied');
                if (!$row) {
                    $stmt = $db->prepare('SELECT c.teacher_id FROM quiz_questions qq JOIN quizzes q ON qq.quiz_id = q.id JOIN courses c ON q.course_id = c.id WHERE qq.id = :qid');
                    $stmt->execute([':qid' => $question_id]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$row || (int)$row['teacher_id'] !== (int)$user['id']) fail('Access denied');
                }
            }

            if ($is_correct === 1) {
                $stmt = $db->prepare('UPDATE quiz_options SET is_correct = 0 WHERE question_id = :qid');
                $stmt->execute([':qid' => $question_id]);
            }

            $stmt = $db->prepare('INSERT INTO quiz_options (question_id, option_text, is_correct, position) VALUES (:question_id,:option_text,:is_correct,:position)');
            $stmt->execute([
                ':question_id' => $question_id,
                ':option_text' => $option_text,
                ':is_correct' => $is_correct,
                ':position' => $position
            ]);

            echo json_encode(['success' => true, 'message' => 'Option added', 'option_id' => $db->lastInsertId()]);
            break;

        /* =====================
         * Student flows
         * ===================== */
        case 'student_list_quizzes':
            if ($role !== 'student') fail('Access denied');

            $stmt = $db->prepare('SELECT q.*,
                    c.title AS course_title,
                    c.description AS course_description
                FROM quizzes q
                JOIN courses c ON q.course_id = c.id
                JOIN enrollments e ON e.course_id = c.id
                WHERE e.student_id = :student_id AND q.is_active = 1 AND c.is_active = 1
                ORDER BY q.created_at DESC');
            $stmt->execute([':student_id' => (int)$user['id']]);
            $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $quizzes]);
            break;

        case 'student_get_quiz_with_questions':
            if ($role !== 'student') fail('Access denied');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Invalid request method');

            $quiz_id = (int)($_POST['quiz_id'] ?? 0);
            if (!$quiz_id) fail('Quiz ID required');

            $stmt = $db->prepare('SELECT q.id, q.title, q.description, q.instructions, q.time_limit_minutes, q.pass_score, q.course_id
                FROM quizzes q
                JOIN enrollments e ON e.course_id = q.course_id
                WHERE q.id = :id AND e.student_id = :student_id AND q.is_active = 1');
            $stmt->execute([':id' => $quiz_id, ':student_id' => (int)$user['id']]);
            $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$quiz) fail('Quiz not found');

            $stmt = $db->prepare('SELECT * FROM quiz_questions WHERE quiz_id = :quiz_id ORDER BY position, id');
            $stmt->execute([':quiz_id' => $quiz_id]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($questions as &$qst) {
                $stmt = $db->prepare('SELECT * FROM quiz_options WHERE question_id = :qid ORDER BY position, id');
                $stmt->execute([':qid' => (int)$qst['id']]);
                $qst['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($qst['options'] as &$opt) {
                    unset($opt['is_correct']);
                }
            }

            echo json_encode(['success' => true, 'data' => ['quiz' => $quiz, 'questions' => $questions]]);
            break;

        case 'student_start_attempt':
            if ($role !== 'student') fail('Access denied');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Invalid request method');

            $quiz_id = (int)($_POST['quiz_id'] ?? 0);
            if (!$quiz_id) fail('Quiz ID required');

            $stmt = $db->prepare('SELECT course_id, time_limit_minutes, pass_score, is_timed FROM quizzes WHERE id = :id AND is_active = 1');
            $stmt->execute([':id' => $quiz_id]);
            $quizRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$quizRow) fail('Quiz not found');

            $stmt = $db->prepare('SELECT id FROM enrollments WHERE student_id = :student_id AND course_id = :course_id');
            $stmt->execute([':student_id' => (int)$user['id'], ':course_id' => (int)$quizRow['course_id']]);
            if ($stmt->rowCount() === 0) fail('Not enrolled');

            $stmt = $db->prepare('SELECT COUNT(*) as total FROM quiz_questions WHERE quiz_id = :quiz_id');
            $stmt->execute([':quiz_id' => $quiz_id]);
            $totalQuestions = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            $stmt = $db->prepare('INSERT INTO quiz_attempts (quiz_id, student_id, status, started_at, max_score, score, percentage) VALUES (:quiz_id,:student_id, :status, NOW(), 0, 0, 0)');
            $stmt->execute([':quiz_id' => $quiz_id, ':student_id' => (int)$user['id'], ':status' => 'in_progress']);

            $attempt_id = (int)$db->lastInsertId();
            echo json_encode(['success' => true, 'message' => 'Attempt started', 'attempt_id' => $attempt_id, 'total_questions' => $totalQuestions]);
            break;

        case 'student_submit_attempt':
            if ($role !== 'student') fail('Access denied');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Invalid request method');

            $attempt_id = (int)($_POST['attempt_id'] ?? 0);
            $answers_json = $_POST['answers'] ?? '[]';
            if (!$attempt_id) fail('Attempt ID required');

            $answers = json_decode($answers_json, true);
            if (!is_array($answers)) $answers = [];

            $stmt = $db->prepare('SELECT qa.*, q.course_id, q.pass_score FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.id WHERE qa.id = :id');
            $stmt->execute([':id' => $attempt_id]);
            $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$attempt) fail('Attempt not found');
            if ((int)$attempt['student_id'] !== (int)$user['id']) fail('Access denied');

            $stmt = $db->prepare('SELECT * FROM quiz_questions WHERE quiz_id = :quiz_id ORDER BY position, id');
            $stmt->execute([':quiz_id' => (int)$attempt['quiz_id']]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $max_score = 0;
            $score = 0;

            $db->beginTransaction();

            $stmt = $db->prepare('DELETE FROM quiz_answers WHERE quiz_attempt_id = :aid');
            $stmt->execute([':aid' => $attempt_id]);

            foreach ($questions as $qst) {
                $qid = (int)$qst['id'];
                $qtype = $qst['question_type'];
                $points = (int)$qst['points'];
                $max_score += $points;

                $selected_option_id = null;
                $answer_text = null;

                foreach ($answers as $a) {
                    $aqid = (int)($a['question_id'] ?? 0);
                    if ($aqid === $qid) {
                        $selected_option_id = isset($a['selected_option_id']) ? (int)$a['selected_option_id'] : null;
                        $answer_text = isset($a['answer_text']) ? (string)$a['answer_text'] : null;
                        break;
                    }
                }

                if ($qtype === 'multiple_choice' || $qtype === 'true_false') {
                    $stmtOpt = $db->prepare('SELECT id, is_correct FROM quiz_options WHERE id = :opt_id AND question_id = :qid');
                    $stmtOpt->execute([':opt_id' => (int)$selected_option_id, ':qid' => $qid]);
                    $opt = $stmtOpt->fetch(PDO::FETCH_ASSOC);

                    $is_correct = ($opt && (int)$opt['is_correct'] === 1) ? 1 : 0;
                    if ($is_correct === 1) $score += $points;

                    $stmtAns = $db->prepare('INSERT INTO quiz_answers (quiz_attempt_id, question_id, selected_option_id, answer_text, is_correct, points_earned) VALUES (:aid,:qid,:selected_option_id,:answer_text,:is_correct,:points_earned)');
                    $stmtAns->execute([
                        ':aid' => $attempt_id,
                        ':qid' => $qid,
                        ':selected_option_id' => $selected_option_id,
                        ':answer_text' => $answer_text,
                        ':is_correct' => $is_correct,
                        ':points_earned' => $is_correct ? $points : 0
                    ]);
                } else {
                    // short_answer => MVP auto-grading not available
                    $stmtAns = $db->prepare('INSERT INTO quiz_answers (quiz_attempt_id, question_id, selected_option_id, answer_text, is_correct, points_earned) VALUES (:aid,:qid,:selected_option_id,:answer_text,:is_correct,:points_earned)');
                    $stmtAns->execute([
                        ':aid' => $attempt_id,
                        ':qid' => $qid,
                        ':selected_option_id' => null,
                        ':answer_text' => $answer_text,
                        ':is_correct' => 0,
                        ':points_earned' => 0
                    ]);
                }
            }

            $percentage = $max_score > 0 ? round(($score / $max_score) * 100, 2) : 0;

            $stmt = $db->prepare('UPDATE quiz_attempts SET score = :score, max_score = :max_score, percentage = :percentage, status = :status, submitted_at = NOW() WHERE id = :id');
            $stmt->execute([
                ':score' => $score,
                ':max_score' => $max_score,
                ':percentage' => $percentage,
                ':status' => 'graded',
                ':id' => $attempt_id
            ]);

            $db->commit();

            // Notifications: quiz graded
            $qtitleStmt = $db->prepare('SELECT title FROM quizzes WHERE id = :qid');
            $qtitleStmt->execute([':qid' => (int)$attempt['quiz_id']]);
            $qtitle = $qtitleStmt->fetch(PDO::FETCH_ASSOC);

            $msg = 'Your quiz has been graded. Score: ' . (int)$score . ' / ' . (int)$max_score . ' (' . $percentage . '%)';
            $notification->create(
                (int)$attempt['student_id'],
                'quiz',
                'Quiz Graded',
                $msg,
                (int)$attempt_id,
                'quiz_attempt'
            );

            echo json_encode([
                'success' => true,
                'message' => 'Attempt submitted and graded',
                'data' => [
                    'attempt_id' => $attempt_id,
                    'score' => (int)$score,
                    'max_score' => (int)$max_score,
                    'percentage' => $percentage,
                    'graded' => true
                ]
            ]);
            break;

        case 'student_get_last_attempt_for_quiz':
            if ($role !== 'student') fail('Access denied');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Invalid request method');

            $quiz_id = (int)($_POST['quiz_id'] ?? 0);
            if (!$quiz_id) fail('Quiz ID required');

            $stmt = $db->prepare('SELECT * FROM quiz_attempts WHERE quiz_id = :quiz_id AND student_id = :student_id ORDER BY id DESC LIMIT 1');
            $stmt->execute([':quiz_id' => $quiz_id, ':student_id' => (int)$user['id']]);
            $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $attempt]);
            break;

        case 'student_get_attempt_details':
            if ($role !== 'student') fail('Access denied');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Invalid request method');

            $attempt_id = (int)($_POST['attempt_id'] ?? 0);
            if (!$attempt_id) fail('Attempt ID required');

            // Ensure the attempt belongs to the current student
            $stmt = $db->prepare('SELECT qa.*, q.title AS quiz_title, q.pass_score AS pass_score, q.course_id AS course_id FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.id WHERE qa.id = :id AND qa.student_id = :student_id');
            $stmt->execute([':id' => $attempt_id, ':student_id' => (int)$user['id']]);
            $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$attempt) fail('Attempt not found');

            // Questions + correct answers + student's answers
            $stmt = $db->prepare('SELECT qq.id AS question_id, qq.question_text, qq.question_type, qq.points,
                                          qa.selected_option_id, qa.answer_text, qa.is_correct, qa.points_earned
                                    FROM quiz_questions qq
                                    LEFT JOIN quiz_answers qa ON qa.question_id = qq.id AND qa.quiz_attempt_id = :aid
                                    WHERE qq.quiz_id = :quiz_id
                                    ORDER BY qq.position, qq.id');
            $stmt->execute([':aid' => $attempt_id, ':quiz_id' => (int)$attempt['quiz_id']]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $questions = [];
            foreach ($rows as $row) {
                $qid = (int)$row['question_id'];

                $correct_option_id = null;
                $options = [];
                if ($row['question_type'] === 'multiple_choice' || $row['question_type'] === 'true_false') {
                    $stmtOpt = $db->prepare('SELECT id, option_text, is_correct FROM quiz_options WHERE question_id = :qid ORDER BY position, id');
                    $stmtOpt->execute([':qid' => $qid]);
                    $opts = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($opts as $o) {
                        $options[] = ['id' => (int)$o['id'], 'option_text' => $o['option_text'], 'is_correct' => (int)$o['is_correct']];
                        if ((int)$o['is_correct'] === 1) $correct_option_id = (int)$o['id'];
                    }
                }

                $questions[] = [
                    'id' => $qid,
                    'question_text' => $row['question_text'],
                    'question_type' => $row['question_type'],
                    'points' => (int)$row['points'],
                    'selected_option_id' => isset($row['selected_option_id']) ? (int)$row['selected_option_id'] : null,
                    'answer_text' => $row['answer_text'],
                    'is_correct' => isset($row['is_correct']) ? (int)$row['is_correct'] : 0,
                    'points_earned' => isset($row['points_earned']) ? (int)$row['points_earned'] : 0,
                    'correct_option_id' => $correct_option_id,
                    'options' => $options,
                ];
            }

            echo json_encode(['success' => true, 'data' => ['attempt' => $attempt, 'questions' => $questions]]);
            break;

        default:
            fail('Invalid action');
    }
} catch (Exception $e) {
    fail($e->getMessage(), 400);
}

