<?php
/**
 * Local login verification script (XAMPP).
 * Sets env vars via putenv() then exercises User::login() for all 3 demo accounts.
 * Not deployed to production — purely a local QA tool.
 */
putenv('DB_HOST=localhost');
putenv('DB_USER=root');
putenv('DB_PASS=');
putenv('DB_NAME=u374397808_vereen_academy');

require __DIR__ . '/../lms_vareen/src/config/database.php';
require __DIR__ . '/../lms_vareen/src/classes/Database.php';
require __DIR__ . '/../lms_vareen/src/classes/User.php';

$accounts = [
    ['admin',   'abubakarabdulrahim663+admin@gmail.com',   'VareenAdmin@2026!'],
    ['teacher', 'abubakarabdulrahim663+teacher@gmail.com', 'VareenTeacher@2026!'],
    ['student', 'abubakarabdulrahim663+student@gmail.com', 'VareenStudent@2026!'],
];

$allPass = true;
foreach ($accounts as [$role, $email, $pass]) {
    // Fresh session per account to isolate tests
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION = [];
    $_SESSION['csrf_token'] = 'test';

    $u = new User();
    $result = $u->login($email, $pass, $role);

    $ok = !empty($result['success']) && ($result['user']['role'] ?? null) === $role;
    $allPass = $allPass && $ok;

    printf("[%s] %-8s | success=%s | role=%s | msg=%s\n",
        $ok ? 'PASS' : 'FAIL',
        $role,
        var_export($result['success'] ?? false, true),
        $result['user']['role'] ?? 'NULL',
        $result['message'] ?? ''
    );

    // Test logout then re-login (PHASE 14)
    $u->logout();
    $u2 = new User();
    $re = $u2->login($email, $pass, $role);
    printf("       re-login after logout: %s\n", !empty($re['success']) ? 'PASS' : 'FAIL');

    // Negative test: wrong password must fail
    $u3 = new User();
    $bad = $u3->login($email, 'WrongPassword1!', $role);
    printf("       wrong password rejected: %s\n", empty($bad['success']) ? 'PASS' : 'FAIL');
}

echo $allPass ? "\nALL LOGIN TESTS PASSED\n" : "\nSOME TESTS FAILED\n";
