<?php
/**
 * VAREEN Academy - DEMO / TEST ACCOUNT & SAMPLE CONTENT SEEDER
 *
 * CLI ONLY:  php lms_vareen/database/seed_demo.php
 *
 * - Idempotent: safe to run repeatedly; existing rows are updated, never duplicated.
 * - Creates the three labeled DEMO/TEST accounts (same email, one per role).
 * - Seeds one sample course (module, lessons, quiz, assignment) taught by the
 *   demo teacher, enrolled by the demo student, so every LMS area can be tested.
 * - Applies pending schema migrations (see migration_batch2.sql).
 * - NEVER outputs passwords. Passwords come from the untracked
 *   database/demo_passwords.php file (copy demo_passwords.example.php).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script may only be run from the command line.\n");
}

require_once __DIR__ . '/../src/config/database.php';

$pwFile = __DIR__ . '/demo_passwords.php';
if (!is_file($pwFile)) {
    fwrite(STDERR, "ERROR: database/demo_passwords.php not found.\n");
    fwrite(STDERR, "Copy database/demo_passwords.example.php to demo_passwords.php first.\n");
    exit(1);
}
$demoPasswords = require $pwFile;
foreach (['admin', 'teacher', 'student'] as $k) {
    if (empty($demoPasswords[$k]) || !is_string($demoPasswords[$k])) {
        fwrite(STDERR, "ERROR: demo_passwords.php must define 'admin', 'teacher' and 'student' passwords.\n");
        exit(1);
    }
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "ERROR: cannot connect to database '" . DB_NAME . "': " . $e->getMessage() . "\n");
    exit(1);
}

/**
 * Small schema helpers - every migration step checks information_schema
 * first so the whole script is safe to run any number of times.
 */
function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = :t');
    $stmt->execute([':t' => $table]);
    return (bool)$stmt->fetchColumn();
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c');
    $stmt->execute([':t' => $table, ':c' => $column]);
    return (bool)$stmt->fetchColumn();
}

function indexExists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = :t AND index_name = :i');
    $stmt->execute([':t' => $table, ':i' => $index]);
    return (bool)$stmt->fetchColumn();
}

/* ----------------------------------------------------------------
 * 1. Schema migrations (idempotent)
 * ---------------------------------------------------------------- */

echo "== Schema migrations ==\n";

if (!tableExists($pdo, 'ai_conversations')) {
    $pdo->exec("CREATE TABLE ai_conversations (
        id INT PRIMARY KEY AUTO_INCREMENT,
        student_id INT NOT NULL,
        lesson_id INT NOT NULL,
        question TEXT NOT NULL,
        answer TEXT,
        success BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ai_student_date (student_id, created_at),
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  + created table ai_conversations\n";
}

if (!tableExists($pdo, 'instructor_applications')) {
    $pdo->exec("CREATE TABLE instructor_applications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(30),
        specialization VARCHAR(255) NOT NULL,
        experience_years INT DEFAULT 0,
        bio TEXT,
        cv_url VARCHAR(500),
        portfolio_url VARCHAR(500),
        sample_lesson_url VARCHAR(500),
        additional_info TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        reviewed_by INT NULL,
        reviewed_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_app_status (status),
        FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  + created table instructor_applications\n";
}

if (!tableExists($pdo, 'certificates')) {
    $pdo->exec("CREATE TABLE certificates (
        id INT PRIMARY KEY AUTO_INCREMENT,
        certificate_code VARCHAR(32) UNIQUE NOT NULL,
        student_id INT NOT NULL,
        course_id INT NOT NULL,
        issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        revoked BOOLEAN DEFAULT 0,
        revoked_at DATETIME NULL,
        UNIQUE KEY uniq_certificate_student_course (student_id, course_id),
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  + created table certificates\n";
}

if (!tableExists($pdo, 'settings')) {
    $pdo->exec("CREATE TABLE settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  + created table settings\n";
}

if (!columnExists($pdo, 'users', 'specialization')) {
    $pdo->exec('ALTER TABLE users ADD COLUMN specialization VARCHAR(255) NULL AFTER bio');
    echo "  + added users.specialization\n";
}

// Allow the same email once per role (demo accounts), keeping (email, role) unique.
if (indexExists($pdo, 'users', 'email')) {
    $pdo->exec('ALTER TABLE users DROP INDEX email');
    echo "  - dropped users UNIQUE(email)\n";
}
if (!indexExists($pdo, 'users', 'uniq_user_email_role')) {
    $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uniq_user_email_role (email, role)');
    echo "  + added users UNIQUE(email, role)\n";
}

/* ----------------------------------------------------------------
 * 2. Demo accounts (DEMO / TEST ACCOUNTS - labeled, hashed passwords)
 * ---------------------------------------------------------------- */

echo "== Demo accounts ==\n";

$demoEmail = 'abubakarabdulrahim663@gmail.com';

$demoAccounts = [
    'admin' => [
        'first_name'     => 'Demo',
        'last_name'      => 'Admin',
        'role'           => 'admin',
        'bio'            => 'DEMO / TEST ACCOUNT - for evaluating the admin LMS. Not a real account.',
        'specialization' => null,
    ],
    'teacher' => [
        'first_name'     => 'Demo',
        'last_name'      => 'Teacher',
        'role'           => 'teacher',
        'bio'            => 'DEMO / TEST ACCOUNT - for evaluating the teacher LMS. Not a real staff account.',
        'specialization' => 'Web Development & Programming',
    ],
    'student' => [
        'first_name'     => 'Demo',
        'last_name'      => 'Student',
        'role'           => 'student',
        'bio'            => 'DEMO / TEST ACCOUNT - for evaluating the student LMS. Not a real student.',
        'specialization' => null,
    ],
];

$demoIds = [];
$lookup = $pdo->prepare('SELECT id FROM users WHERE email = :email AND role = :role');
$insertUser = $pdo->prepare(
    'INSERT INTO users (first_name, last_name, email, password, role, bio, specialization, is_active, email_verified)
     VALUES (:first_name, :last_name, :email, :password, :role, :bio, :specialization, 1, 1)'
);
$updateUser = $pdo->prepare(
    'UPDATE users SET first_name = :first_name, last_name = :last_name, password = :password,
            bio = :bio, specialization = :specialization, is_active = 1
     WHERE id = :id'
);

foreach ($demoAccounts as $key => $acct) {
    $hash = password_hash($demoPasswords[$key], PASSWORD_BCRYPT);
    $lookup->execute([':email' => $demoEmail, ':role' => $acct['role']]);
    $existing = $lookup->fetchColumn();

    if ($existing) {
        $updateUser->execute([
            ':first_name'     => $acct['first_name'],
            ':last_name'      => $acct['last_name'],
            ':password'       => $hash,
            ':bio'            => $acct['bio'],
            ':specialization' => $acct['specialization'],
            ':id'             => $existing,
        ]);
        $demoIds[$key] = (int)$existing;
        echo "  ~ updated demo {$acct['role']} account (user #{$existing})\n";
    } else {
        $insertUser->execute([
            ':first_name'     => $acct['first_name'],
            ':last_name'      => $acct['last_name'],
            ':email'          => $demoEmail,
            ':password'       => $hash,
            ':role'           => $acct['role'],
            ':bio'            => $acct['bio'],
            ':specialization' => $acct['specialization'],
        ]);
        $demoIds[$key] = (int)$pdo->lastInsertId();
        echo "  + created demo {$acct['role']} account (user #{$demoIds[$key]})\n";
    }
}

/* ----------------------------------------------------------------
 * 3. Sample content: course + module + lessons (taught by demo teacher)
 * ---------------------------------------------------------------- */

echo "== Sample content ==\n";

$teacherId = $demoIds['teacher'];
$studentId = $demoIds['student'];

$courseStmt = $pdo->prepare('SELECT id FROM courses WHERE teacher_id = :t AND title = :title');
$courseStmt->execute([':t' => $teacherId, ':title' => 'Introduction to Web Development']);
$courseId = $courseStmt->fetchColumn();

if (!$courseId) {
    $pdo->prepare('INSERT INTO courses (teacher_id, title, description, category, price, is_active)
                   VALUES (:t, :title, :d, :cat, 0, 1)')
        ->execute([
            ':t'    => $teacherId,
            ':title' => 'Introduction to Web Development',
            ':d'    => 'A beginner-friendly introduction to building websites with HTML, CSS and JavaScript. '
                     . 'You will learn how web pages are structured, styled and made interactive, and finish '
                     . 'by building and publishing your own first web page. (Sample course seeded for demo testing.)',
            ':cat'  => 'Programming',
        ]);
    $courseId = (int)$pdo->lastInsertId();
    echo "  + created sample course #{$courseId}\n";
} else {
    $courseId = (int)$courseId;
    echo "  ~ sample course #{$courseId} already exists\n";
}

// Module
$moduleStmt = $pdo->prepare('SELECT id FROM modules WHERE course_id = :c AND title = :title');
$moduleStmt->execute([':c' => $courseId, ':title' => 'Getting Started']);
$moduleId = $moduleStmt->fetchColumn();

if (!$moduleId) {
    $pdo->prepare('INSERT INTO modules (course_id, title, description, position, is_active)
                   VALUES (:c, :title, :d, 1, 1)')
        ->execute([
            ':c'     => $courseId,
            ':title' => 'Getting Started',
            ':d'     => 'The building blocks of every website: HTML for structure, CSS for style, and JavaScript for behaviour.',
        ]);
    $moduleId = (int)$pdo->lastInsertId();
    echo "  + created module #{$moduleId}\n";
} else {
    $moduleId = (int)$moduleId;
}

// Lessons: title => [video, duration(s), content]
$lessons = [
    'HTML Basics: Structure of a Web Page' => [
        'https://www.youtube.com/embed/UB1O30fR-EE',
        1800,
        "HTML (HyperText Markup Language) describes the structure of every web page.\n\n"
        . "Elements are written as tags: a heading is <h1>...</h1>, a paragraph is <p>...</p>, "
        . "and a link is <a href=\"...\">...</a>. Tags usually come in pairs: an opening tag and a closing tag.\n\n"
        . "Every valid document starts with <!DOCTYPE html>, followed by an <html> element containing "
        . "a <head> (metadata, title, styles) and a <body> (the visible content).\n\n"
        . "Practice: create a file named index.html with a heading, two paragraphs and a link, then open it in your browser.",
    ],
    'CSS Basics: Styling Your Page' => [
        'https://www.youtube.com/embed/yfoY53QXEnI',
        1500,
        "CSS (Cascading Style Sheets) controls how HTML looks: colours, fonts, spacing and layout.\n\n"
        . "A rule has a selector and declarations: h1 { color: #667eea; font-size: 32px; }. "
        . "Selectors can target tags (p), classes (.intro) or ids (#header).\n\n"
        . "The box model wraps every element: content, padding, border and margin. "
        . "Mastering the box model is the key to predictable layouts.\n\n"
        . "Practice: add a <style> block to your index.html and change the heading colour, "
        . "font and paragraph spacing.",
    ],
    'JavaScript Basics: Making Pages Interactive' => [
        'https://www.youtube.com/embed/W6NZfCO5SIk',
        1200,
        "JavaScript makes web pages respond to the user: buttons, forms, animations and data.\n\n"
        . "You declare variables with let and const, store values, and write functions that run when "
        . "events happen, for example: button.addEventListener('click', () => { ... });\n\n"
        . "The document object model (DOM) lets JavaScript read and change the page: "
        . "document.querySelector('#demo').textContent = 'Hello!';\n\n"
        . "Practice: add a button to index.html that changes the heading text when clicked.",
    ],
];

$insertLesson = $pdo->prepare(
    'INSERT INTO lessons (module_id, course_id, title, description, video_url, video_duration, content, position, is_active)
     VALUES (:m, :c, :title, :d, :v, :dur, :content, :pos, 1)'
);
$lessonLookup = $pdo->prepare('SELECT id FROM lessons WHERE course_id = :c AND title = :title');
$lessonIds = [];
$pos = 1;
foreach ($lessons as $title => [$video, $duration, $content]) {
    $lessonLookup->execute([':c' => $courseId, ':title' => $title]);
    $lid = $lessonLookup->fetchColumn();
    if (!$lid) {
        $insertLesson->execute([
            ':m' => $moduleId, ':c' => $courseId, ':title' => $title,
            ':d' => 'Sample lesson seeded for demo testing.', ':v' => $video,
            ':dur' => $duration, ':content' => $content, ':pos' => $pos,
        ]);
        $lid = (int)$pdo->lastInsertId();
        echo "  + created lesson #{$lid}: {$title}\n";
    } else {
        $lid = (int)$lid;
        echo "  ~ lesson #{$lid} already exists\n";
    }
    $lessonIds[$pos] = $lid;
    $pos++;
}

/* ----------------------------------------------------------------
 * 4. Sample quiz (timed, 5 real MCQs) + sample assignment
 * ---------------------------------------------------------------- */

$quizStmt = $pdo->prepare('SELECT id FROM quizzes WHERE course_id = :c AND title = :title');
$quizStmt->execute([':c' => $courseId, ':title' => 'HTML & CSS Fundamentals']);
$quizId = $quizStmt->fetchColumn();

if (!$quizId) {
    $pdo->prepare('INSERT INTO quizzes (course_id, teacher_id, title, description, instructions, time_limit_minutes, pass_score, is_timed, is_active)
                   VALUES (:c, :t, :title, :d, :i, 10, 60, 1, 1)')
        ->execute([
            ':c' => $courseId, ':t' => $teacherId, ':title' => 'HTML & CSS Fundamentals',
            ':d' => 'A short timed check of your understanding of the first two lessons.',
            ':i' => '10 multiple-choice questions worth 1 point each. You have 10 minutes. '
                  . 'The AI assistant is locked while a quiz attempt is in progress.',
        ]);
    $quizId = (int)$pdo->lastInsertId();
    echo "  + created quiz #{$quizId}\n";
} else {
    $quizId = (int)$quizId;
    echo "  ~ quiz #{$quizId} already exists\n";
}

$quizQuestions = [
    ['What does HTML stand for?', [
        ['HyperText Markup Language', 1], ['HyperTransfer Markup Language', 0],
        ['HighText Machine Language', 0], ['Hyperlink Text Management Language', 0],
    ]],
    ['Which HTML tag is used to create a hyperlink?', [
        ['<a>', 1], ['<link>', 0], ['<href>', 0], ['<url>', 0],
    ]],
    ['Which element defines the largest heading in HTML?', [
        ['<h1>', 1], ['<heading>', 0], ['<h6>', 0], ['<title>', 0],
    ]],
    ['Which CSS property changes the text colour of an element?', [
        ['color', 1], ['text-color', 0], ['font-color', 0], ['text-style', 0],
    ]],
    ['Which CSS property controls the size of text?', [
        ['font-size', 1], ['text-size', 0], ['size', 0], ['font-scale', 0],
    ]],
];

$qLookup = $pdo->prepare('SELECT id FROM quiz_questions WHERE quiz_id = :q AND question_text = :text');
$oLookup = $pdo->prepare('SELECT COUNT(*) FROM quiz_options WHERE question_id = :q');
$insertQ = $pdo->prepare('INSERT INTO quiz_questions (quiz_id, question_text, question_type, points, position)
                          VALUES (:q, :text, :type, :points, :pos)');
$insertO = $pdo->prepare('INSERT INTO quiz_options (question_id, option_text, is_correct, position)
                          VALUES (:q, :text, :correct, :pos)');

$qPos = 1;
foreach ($quizQuestions as [$qText, $options]) {
    $qLookup->execute([':q' => $quizId, ':text' => $qText]);
    $qRow = $qLookup->fetchColumn();
    if (!$qRow) {
        $insertQ->execute([':q' => $quizId, ':text' => $qText, ':type' => 'multiple_choice', ':points' => 1, ':pos' => $qPos]);
        $qid = (int)$pdo->lastInsertId();
        $oPos = 1;
        foreach ($options as [$oText, $correct]) {
            $insertO->execute([':q' => $qid, ':text' => $oText, ':correct' => $correct, ':pos' => $oPos]);
            $oPos++;
        }
        echo "  + added quiz question {$qPos}\n";
    } else {
        $oLookup->execute([':q' => (int)$qRow]);
        if (!(int)$oLookup->fetchColumn()) {
            $qid = (int)$qRow;
            $oPos = 1;
            foreach ($options as [$oText, $correct]) {
                $insertO->execute([':q' => $qid, ':text' => $oText, ':correct' => $correct, ':pos' => $oPos]);
                $oPos++;
            }
            echo "  ~ restored options for quiz question {$qPos}\n";
        }
    }
    $qPos++;
}

/* ----------------------------------------------------------------
 * 5. Sample assignment, enrollment, progress, live class, settings
 * ---------------------------------------------------------------- */

$aLookup = $pdo->prepare('SELECT id FROM assignments WHERE course_id = :c AND title = :title');
$aLookup->execute([':c' => $courseId, ':title' => 'Build Your First Web Page']);
$assignmentId = $aLookup->fetchColumn();

if (!$assignmentId) {
    $pdo->prepare('INSERT INTO assignments (course_id, teacher_id, title, description, instructions, due_date, max_score, is_active)
                   VALUES (:c, :t, :title, :d, :i, :due, 100, 1)')
        ->execute([
            ':c' => $courseId, ':t' => $teacherId, ':title' => 'Build Your First Web Page',
            ':d' => 'Apply what you learned in the first three lessons to build a small page of your own.',
            ':i' => "Create a single HTML file (index.html) that contains:\n"
                  . "1. A page title in <title>\n"
                  . "2. One <h1> heading and one <h2> subheading\n"
                  . "3. At least two paragraphs styled with an internal CSS <style> block\n"
                  . "4. A list of three links to websites you like\n"
                  . "5. A button that uses JavaScript to change something on the page\n\n"
                  . "Submit your source code as text plus a short description of what each part does.",
            ':due' => date('Y-m-d 23:59:00', strtotime('+7 days')),
        ]);
    echo "  + created sample assignment\n";
} else {
    echo "  ~ sample assignment already exists\n";
}

// Enroll the demo student in the sample course
$eLookup = $pdo->prepare('SELECT id FROM enrollments WHERE student_id = :s AND course_id = :c');
$eLookup->execute([':s' => $studentId, ':c' => $courseId]);
if (!$eLookup->fetchColumn()) {
    $pdo->prepare("INSERT INTO enrollments (student_id, course_id, progress, status)
                   VALUES (:s, :c, 0, 'active')")
        ->execute([':s' => $studentId, ':c' => $courseId]);
    echo "  + enrolled demo student in sample course\n";
} else {
    echo "  ~ demo student already enrolled\n";
}

// Mark the first lesson completed so progress tracking has real data
$pLookup = $pdo->prepare('SELECT id FROM lesson_progress WHERE student_id = :s AND lesson_id = :l');
$pLookup->execute([':s' => $studentId, ':l' => $lessonIds[1]]);
if (!$pLookup->fetchColumn()) {
    $pdo->prepare('INSERT INTO lesson_progress (student_id, lesson_id, course_id, watched_duration, is_completed, completed_at)
                   VALUES (:s, :l, :c, 600, 1, NOW())')
        ->execute([':s' => $studentId, ':l' => $lessonIds[1], ':c' => $courseId]);
    echo "  + marked lesson 1 completed for demo student\n";
} else {
    echo "  ~ lesson 1 progress already exists\n";
}

// One upcoming live class (meeting link intentionally left for the teacher to add)
$lcLookup = $pdo->prepare('SELECT id FROM live_classes WHERE course_id = :c AND title = :title');
$lcLookup->execute([':c' => $courseId, ':title' => 'Live Q&A: HTML Basics']);
if (!$lcLookup->fetchColumn()) {
    $pdo->prepare("INSERT INTO live_classes (course_id, teacher_id, title, description, scheduled_at, meeting_url, meeting_platform, duration_minutes, status)
                   VALUES (:c, :t, :title, :d, :when, NULL, NULL, 60, 'scheduled')")
        ->execute([
            ':c' => $courseId, ':t' => $teacherId, ':title' => 'Live Q&A: HTML Basics',
            ':d' => 'Bring your questions from the first two lessons. '
                  . 'The meeting link will be added by the instructor before the session.',
            ':when' => date('Y-m-d 14:00:00', strtotime('+1 day')),
        ]);
    echo "  + scheduled sample live class\n";
} else {
    echo "  ~ sample live class already exists\n";
}

// Default site settings (used by the admin settings page)
foreach (['site_name' => 'VAREEN Academy', 'support_email' => 'support@vereenacademy.com'] as $k => $v) {
    $pdo->prepare('INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (:k, :v)')
        ->execute([':k' => $k, ':v' => $v]);
}

/* ----------------------------------------------------------------
 * 6. Summary (never prints passwords)
 * ---------------------------------------------------------------- */

echo "\n== Seeding complete ==\n";
echo "Demo / test accounts (same email, one per role):\n";
foreach ($demoAccounts as $key => $acct) {
    echo sprintf("  [%s] %s %s <%s> role=%s user_id=%d\n",
        strtoupper($key), $acct['first_name'], $acct['last_name'], $demoEmail, $acct['role'], $demoIds[$key]);
}
echo "Passwords are set from database/demo_passwords.php (see DEMO_CREDENTIALS.md).\n";
echo "Sample course: 'Introduction to Web Development' (#{$courseId}) - "
   . count($lessons) . " lessons, 1 timed quiz (5 questions), 1 assignment, 1 live class.\n";
echo "You can re-run this script safely at any time.\n";





