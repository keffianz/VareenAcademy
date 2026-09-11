<?php
/** Final lint of all files edited in the bug-fix pass. Run: php tools/_lint_all.php */
chdir(__DIR__ . '/../');
$files = [
    'lms_vareen/views/admin/certificates.php','lms_vareen/views/admin/settings.php',
    'lms_vareen/views/admin/applications.php','lms_vareen/views/admin/messages.php',
    'lms_vareen/views/admin/users.php','lms_vareen/views/admin/ai.php','lms_vareen/views/admin/_sidebar.php',
    'lms_vareen/views/teacher/assignments-editor.php','lms_vareen/views/teacher/quiz-editor.php',
    'lms_vareen/views/teacher/live-classes.php','lms_vareen/views/teacher/attendance.php',
    'lms_vareen/views/teacher/lesson-editor.php','lms_vareen/views/teacher/resource-editor.php',
    'lms_vareen/views/teacher/dashboard.php',
    'lms_vareen/views/student/lessons.php','lms_vareen/views/student/quizzes.php',
    'lms_vareen/views/student/assignments.php','lms_vareen/views/student/post-detail.php',
    'lms_vareen/views/student/student-community.php',
    'lms_vareen/src/classes/Community.php','lms_vareen/src/api/community.php',
    'lms_vareen/src/api/admin.php','lms_vareen/src/middleware/auth.php',
    'lms_vareen/src/config/ai_config.php','lms_vareen/index.php',
];
$out = ['=== FINAL LINT ' . date('Y-m-d H:i:s') . ' ==='];
$bad = 0;
foreach ($files as $f) {
    if (!file_exists($f)) { $out[] = "[MISSING] $f"; $bad++; continue; }
    exec('php -l ' . escapeshellarg($f) . ' 2>&1', $r, $c);
    $line = end($r);
    $out[] = ($c === 0 ? '[OK]    ' : '[ERROR] ') . $f . ' — ' . trim($line);
    if ($c !== 0) $bad++;
    $r = [];
}
$out[] = $bad === 0 ? '=== RESULT: ALL ' . count($files) . ' FILES CLEAN ===' : "=== RESULT: $bad FILE(S) WITH PROBLEMS ===";
file_put_contents(__DIR__ . '/_lint_out.txt', implode(PHP_EOL, $out));
echo end($out) . PHP_EOL;