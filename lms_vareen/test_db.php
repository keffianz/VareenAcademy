<?php
/**
 * Test database connection and queries
 */
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'student';

require_once __DIR__ . '/src/middleware/auth.php';
require_once __DIR__ . '/src/config/database.php';
require_once __DIR__ . '/src/classes/Database.php';
require_once __DIR__ . '/src/classes/Course.php';

echo "DB_HOST=" . DB_HOST . "\n";
echo "DB_USER=" . DB_USER . "\n";
echo "DB_PASS=" . DB_PASS . "\n";
echo "DB_NAME=" . DB_NAME . "\n";

try {
    $db = (new Database())->connect();
    echo "DB connected ok\n";
} catch (Exception $e) {
    echo "DB error: " . $e->getMessage() . "\n";
    exit(1);
}

$user_id = getCurrentUserId();
echo "user_id=$user_id\n";

// Test lessons.php query
try {
    $stmt = $db->prepare(
        'SELECT c.*, e.progress, e.enrolled_at,
                (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) AS lesson_count,
                (SELECT COUNT(*) FROM lessons l JOIN lesson_progress lp ON lp.lesson_id = l.id WHERE l.course_id = c.id AND lp.student_id = :sid AND lp.is_completed = 1) AS completed_lessons
         FROM enrollments e
         JOIN courses c ON c.id = e.course_id
         WHERE e.student_id = :sid
         ORDER BY e.enrolled_at DESC'
    );
    $stmt->execute([':sid' => $user_id]);
    $enrolled = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "lessons query ok, rows=" . count($enrolled) . "\n";
} catch (Exception $e) {
    echo "lessons query error: " . $e->getMessage() . "\n";
}

// Test quizzes.php query
try {
    $stmt = $db->prepare(
        'SELECT q.*, c.title as course_title,
                (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.id) as question_count,
                (SELECT score FROM quiz_attempts qa WHERE qa.quiz_id = q.id AND qa.student_id = :sid ORDER BY submitted_at DESC LIMIT 1) as last_score,
                (SELECT completed_at FROM quiz_attempts qa WHERE qa.quiz_id = q.id AND qa.student_id = :sid ORDER BY submitted_at DESC LIMIT 1) as last_attempt
         FROM quizzes q
         JOIN courses c ON c.id = q.course_id
         JOIN enrollments e ON e.course_id = c.id
         WHERE e.student_id = :sid AND q.is_active = 1
         ORDER BY q.created_at DESC'
    );
    $stmt->execute([':sid' => $user_id]);
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "quizzes query ok, rows=" . count($quizzes) . "\n";
} catch (Exception $e) {
    echo "quizzes query error: " . $e->getMessage() . "\n";
}

// Test assignments.php query
try {
    $stmt = $db->prepare("SELECT a.*, c.title as course_title,
        (SELECT status FROM submissions s WHERE s.assignment_id = a.id AND s.student_id = :student_id ORDER BY submitted_at DESC LIMIT 1) as submission_status,
        (SELECT file_path FROM submissions s WHERE s.assignment_id = a.id AND s.student_id = :student_id ORDER BY submitted_at DESC LIMIT 1) as submission_file
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        JOIN enrollments e ON e.course_id = c.id
        WHERE e.student_id = :student_id AND a.is_active = 1
        ORDER BY a.due_date ASC, a.created_at DESC");
    $stmt->execute([':student_id' => (int)$user_id]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "assignments query ok, rows=" . count($assignments) . "\n";
} catch (Exception $e) {
    echo "assignments query error: " . $e->getMessage() . "\n";
}

echo "All tests done\n";
