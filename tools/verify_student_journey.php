<?php
/**
 * VAREEN-X — Student Journey Verification (VX-009)
 *
 * Static verification of the full student learning journey:
 * each step must have (a) a routed view, (b) a real API action,
 * (c) the data-layer method it depends on. Catches dangling wiring
 * without needing a live DB.
 *
 * Usage: php tools/verify_student_journey.php
 */

$root = dirname(__DIR__);
$lms  = $root . '/lms_vareen';

$pass = 0; $fail = 0; $notes = [];

function check($label, $ok, &$pass, &$fail, &$notes, $hint = '') {
    if ($ok) { $pass++; echo "PASS  $label\n"; }
    else { $fail++; echo "FAIL  $label" . ($hint ? "  [$hint]" : "") . "\n"; $notes[] = $label; }
}

function read($p) { return is_file($p) ? file_get_contents($p) : ''; }

// step => [view file, api file, api action, data-layer class, required method]
$journeys = [
    'Register'           => ['views/auth/signup.php',             'src/api/auth.php',        'signup',              'src/classes/User.php',        'register'],
    'Login'              => ['views/auth/login.php',              'src/api/auth.php',        'login',               'src/classes/User.php',        'login'],
    'Edit Profile'       => ['views/profile.php',                 'src/api/auth.php',        'update_profile',      'src/classes/User.php',        'updateProfile'],
    'Change Password'    => ['views/profile.php',                 'src/api/auth.php',        'change_password',     'src/classes/User.php',        'changePassword'],
    'Upload Photo'       => ['views/profile.php',                 'src/api/auth.php',        'upload_avatar',       'src/classes/Uploader.php',    'upload'],
    'Browse Courses'     => ['views/student/courses.php',         null,                      null,                  'src/classes/Course.php',      'getAllCourses'],
    'Course Detail'      => ['views/course-detail.php',           null,                      null,                  'src/classes/Course.php',      'getCourseById'],
    'Enroll'             => ['views/course-detail.php',           'src/api/dashboard.php',   'enroll',              'src/classes/Enrollment.php',  'enrollStudent'],
    'My Course List'     => ['views/student/dashboard.php',       'src/api/dashboard.php',   'get_dashboard',       'src/classes/Enrollment.php',  'getStudentDashboard'],
    'Watch Videos'       => ['views/lesson.php',                  'src/api/lessons.php',     'get',                 'src/classes/Lesson.php',      'getLessonById'],
    'Track Progress'     => ['views/lesson.php',                  'src/api/lessons.php',     'record_progress',     'src/classes/LessonProgress.php', 'recordWatch'],
    'Mark Complete'      => ['views/lesson.php',                  'src/api/lessons.php',     'mark_complete',       'src/classes/LessonProgress.php', 'markCompleted'],
    'Download Resources' => ['views/lesson.php',                  'src/api/download.php',    null,                  'src/classes/Uploader.php',    'resolveStoredPath'],
    'Assignments'        => ['views/student/assignments.php',     'src/api/assignments.php', null,                  null,                          null],
    'Quizzes'            => ['views/student/quizzes.php',         'src/api/quizzes.php',     null,                  null,                          null],
    'Certificates'       => ['views/student/certificates.php',    null,                      null,                  'src/classes/Certificate.php', null],
    'Notifications'      => ['views/notifications.php',           'src/api/dashboard.php',   'mark_notification_read', 'src/classes/Notification.php', 'markAsRead'],
    'Discussions'        => ['views/student/student-community.php', 'src/api/community.php', null,                  null,                          null],
    'Live Classes'       => ['views/student/live-classes.php',    'src/api/live_classes.php', null,                 null,                          null],
    'AI Assistant'       => ['views/student/student-ai.php',      'src/api/ai_assistant.php', null,                 null,                          null],
    'Payments'           => ['views/student/my-payments.php',     'src/api/payments.php',    null,                  'src/classes/Payment.php',     null],
];

echo "=== VAREEN-X Student Journey Verification (VX-009) ===\n\n";

foreach ($journeys as $step => $spec) {
    [$view, $api, $action, $classFile, $method] = $spec;

    $viewPath = $lms . '/' . $view;
    check("$step: view exists", is_file($viewPath), $pass, $fail, $notes, $view);

    if (is_file($viewPath)) {
        $viewSrc = read($viewPath);
        check("$step: view is non-trivial", strlen($viewSrc) > 800, $pass, $fail, $notes, 'suspiciously small');
        check("$step: no 'Coming Soon' placeholder",
            !preg_match('/coming\s+soon/i', $viewSrc), $pass, $fail, $notes);
    }

    if ($api) {
        $apiPath = $lms . '/' . $api;
        check("$step: api exists", is_file($apiPath), $pass, $fail, $notes, $api);
        if (is_file($apiPath) && $action) {
            $apiSrc = read($apiPath);
            check("$step: api action '$action' implemented",
                (bool)preg_match("/case\s+['\"]" . preg_quote($action, '/') . "['\"]/", $apiSrc),
                $pass, $fail, $notes);
        }
    }

    if ($classFile) {
        $clsPath = $lms . '/' . $classFile;
        check("$step: class exists", is_file($clsPath), $pass, $fail, $notes, $classFile);
        if (is_file($clsPath) && $method) {
            $clsSrc = read($clsPath);
            check("$step: method $method() exists",
                (bool)preg_match('/function\s+' . preg_quote($method, '/') . '\s*\(/', $clsSrc),
                $pass, $fail, $notes);
        }
    }
    echo "\n";
}

// ---------------------------------------------------------------- routing
echo "=== Router Coverage ===\n";
$router = read($lms . '/index.php');
$pages = [
    'login', 'signup', 'courses', 'course-detail', 'lesson',
    'student-dashboard', 'courses', 'assignments',
    'quizzes', 'certificates', 'student-community',
    'student-ai', 'live-classes', 'notifications', 'profile',
    'verify', 'checkout', 'payment-callback',
];
foreach ($pages as $p) {
    check("route: $p", (bool)preg_match("/['\"]" . preg_quote($p, '/') . "['\"]/", $router), $pass, $fail, $notes);
}

echo "\n=== Result: $pass passed, $fail failed ===\n";
if ($fail) {
    echo "\nFailing steps:\n";
    foreach ($notes as $n) echo "  - $n\n";
}
exit($fail ? 1 : 0);
