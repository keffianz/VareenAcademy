<?php
/**
 * VEREEN Academy - Automated HTTP test suite
 * Tests: authentication, role authorization, CSRF, session fixation, logout, 404s.
 *
 * CLI ONLY:   php lms_vareen/tests/run_tests.php
 * Server:     php -S 127.0.0.1:8080 -t <repo root>   (or set env VAREEN_TEST_BASE)
 *
 * REQUIREMENTS (no credentials are stored in this repo):
 *   Create ONE real account per role in the app database, then export
 *   the credentials via environment variables before running:
 *     VAREEN_TEST_ADMIN_EMAIL    / VAREEN_TEST_ADMIN_PASS
 *     VAREEN_TEST_TEACHER_EMAIL  / VAREEN_TEST_TEACHER_PASS
 *     VAREEN_TEST_STUDENT_EMAIL  / VAREEN_TEST_STUDENT_PASS
 *   Any missing variable causes the suite to exit with an error.
 *
 * This suite never prints passwords or password hashes.
 */

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only\n"); }

$base = rtrim(getenv('VAREEN_TEST_BASE') ?: 'http://127.0.0.1:8080', '/');

// Credentials come from the environment — never from the repository.
$PW = [
    'admin'   => getenv('VAREEN_TEST_ADMIN_PASS'),
    'teacher' => getenv('VAREEN_TEST_TEACHER_PASS'),
    'student' => getenv('VAREEN_TEST_STUDENT_PASS'),
];
$EMAIL = [
    'admin'   => getenv('VAREEN_TEST_ADMIN_EMAIL'),
    'teacher' => getenv('VAREEN_TEST_TEACHER_EMAIL'),
    'student' => getenv('VAREEN_TEST_STUDENT_EMAIL'),
];
foreach (['admin', 'teacher', 'student'] as $role) {
    if ($PW[$role] === false || $EMAIL[$role] === false) {
        fwrite(STDERR, "ERROR: set VAREEN_TEST_{$role}_EMAIL and VAREEN_TEST_{$role}_PASS before running.\n");
        exit(1);
    }
}

$pass = 0; $fail = 0; $failures = [];
function check(string $name, bool $ok, string $info = ''): void {
    global $pass, $fail, $failures;
    if ($ok) { $pass++; echo "[PASS] {$name}\n"; }
    else { $fail++; $failures[] = $name . ($info ? " :: {$info}" : ''); echo "[FAIL] {$name}" . ($info ? " :: {$info}" : '') . "\n"; }
}

/** @return array{0:int,1:string} [status, body] */
function http(string $method, string $url, array $opts = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR      => $opts['jar'] ?? null,
        CURLOPT_COOKIEFILE     => $opts['jar'] ?? null,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER     => $opts['headers'] ?? [],
        CURLOPT_POSTFIELDS     => $opts['body'] ?? null,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    return $body === false ? [0, "CURL_ERROR: {$err}"] : [$code, (string)$body];
}

function newJar(): string {
    return sys_get_temp_dir() . '/va_test_' . bin2hex(random_bytes(4)) . '.jar';
}

function getCsrf(string $jar): string {
    [, $body] = http('GET', $GLOBALS['base'] . '/lms_vareen/index.php?page=login', ['jar' => $jar]);
    if (preg_match("/window\.CSRF_TOKEN\s*=\s*'([0-9a-f]+)'/", $body, $m)) return $m[1];
    if (preg_match('/name="csrf-token" content="([0-9a-f]+)"/', $body, $m)) return $m[1];
    return '';
}

function apiLogin(string $jar, string $role, string $password, string $intendedRole, ?string $csrf = null): array {
    $csrf = $csrf ?? getCsrf($jar);
    [$code, $body] = http('POST', $GLOBALS['base'] . '/lms_vareen/src/api/auth.php?action=login', [
        'jar'     => $jar,
        'headers' => ['Content-Type: application/json', 'X-CSRF-Token: ' . $csrf],
        'body'    => json_encode([
            'email'         => $GLOBALS['EMAIL'][$role],
            'password'      => $password,
            'intended_role' => $intendedRole,
        ]),
    ]);
    $data = json_decode($body, true) ?: [];
    return [$code, $data, $body];
}

function sessId(string $jar): string {
    if (!is_file($jar)) return '';
    foreach (file($jar) as $line) {
        $p = explode("\t", trim($line));
        if (count($p) >= 7 && strtolower($p[5]) === 'phpsessid') return $p[6];
    }
    return '';
}

function page(string $path, string $jar): array {
    return http('GET', $GLOBALS['base'] . '/lms_vareen/index.php?page=' . $path, ['jar' => $jar]);
}

/* ================= 1. Login page & CSRF ================= */
$jar = newJar();
[$code, $body] = page('login', $jar);
check('Login page returns 200', $code === 200, "got {$code}");
check('Login page embeds CSRF token', (bool)preg_match("/window\.CSRF_TOKEN\s*=\s*'/", $body));

/* ================= 2. CSRF enforcement ================= */
[$code, $body] = http('POST', $base . '/lms_vareen/src/api/auth.php?action=login', [
    'jar'     => $jar,
    'headers' => ['Content-Type: application/json'],
    'body'    => json_encode(['email' => $GLOBALS['EMAIL']['student'], 'password' => 'x']),
]);
check('POST without CSRF token rejected (403)', $code === 403, "got {$code}: " . substr($body, 0, 80));

/* ================= 3. Wrong password ================= */
$jar = newJar();
[$code, $data, $raw] = apiLogin($jar, 'student', strrev($PW['student']), 'student');
check('Wrong password rejected', empty($data['success']), 'code=' . $code);
check('Failed login does not leak password hash', strpos($raw, '$2y$') === false);
[$code] = page('student-dashboard', $jar);
check('Failed login cannot open dashboard (302)', $code === 302, "got {$code}");

/* ================= 4. Role mismatch (email+pw+role must match) ================= */
$jar = newJar();
[, $data, ] = apiLogin($jar, 'admin', $PW['admin'], 'student');   // admin credentials, student role
check('Role mismatch rejected (admin pw + student role)', empty($data['success']) || empty($data['user']['role']), substr(json_encode($data), 0, 100));
[, $data, ] = apiLogin($jar, 'teacher', $PW['teacher'], 'admin');   // teacher credentials, admin role
check('Role mismatch rejected (teacher pw + admin role)', empty($data['success']) || empty($data['user']['role']));
[$code] = page('student-dashboard', $jar);
check('Role-mismatched login has no dashboard access', $code === 302, "got {$code}");

/* ================= 5. Admin session + access matrix ================= */
$jarAdmin = newJar();
$csrf = getCsrf($jarAdmin);
$preSess = sessId($jarAdmin);
[, $data, $raw] = apiLogin($jarAdmin, 'admin', $PW['admin'], 'admin', $csrf);
check('Admin login succeeds (role=admin)', !empty($data['success']) && ($data['user']['role'] ?? '') === 'admin', substr(json_encode($data), 0, 120));
check('Login response contains no password/hash', strpos($raw, '$2y$') === false && !isset($data['user']['password']));
$postSess = sessId($jarAdmin);
check('Session ID regenerated on login (anti-fixation)', $preSess !== '' && $postSess !== '' && $preSess !== $postSess, "pre={$preSess} post={$postSess}");
[$code] = page('admin-dashboard', $jarAdmin);
check('Admin can open admin dashboard', $code === 200, "got {$code}");
[$code] = page('student-dashboard', $jarAdmin);
check('Admin denied student dashboard (403)', $code === 403, "got {$code}");
[$code] = page('teacher-dashboard', $jarAdmin);
check('Admin denied teacher dashboard (403)', $code === 403, "got {$code}");

/* ================= 6. Teacher session + access matrix ================= */
$jarTeacher = newJar();
[, $data, ] = apiLogin($jarTeacher, 'teacher', $PW['teacher'], 'teacher');
check('Teacher login succeeds (role=teacher)', !empty($data['success']) && ($data['user']['role'] ?? '') === 'teacher', substr(json_encode($data), 0, 120));
[$code] = page('teacher-dashboard', $jarTeacher);
check('Teacher can open teacher dashboard', $code === 200, "got {$code}");
[$code] = page('admin-dashboard', $jarTeacher);
check('Teacher denied admin dashboard (403)', $code === 403, "got {$code}");
[$code] = page('student-dashboard', $jarTeacher);
check('Teacher denied student dashboard (403)', $code === 403, "got {$code}");

/* ================= 7. Student session + access matrix ================= */
$jarStudent = newJar();
[, $data, ] = apiLogin($jarStudent, 'student', $PW['student'], 'student');
check('Student login succeeds (role=student)', !empty($data['success']) && ($data['user']['role'] ?? '') === 'student', substr(json_encode($data), 0, 120));
[$code] = page('student-dashboard', $jarStudent);
check('Student can open student dashboard', $code === 200, "got {$code}");
foreach (['courses', 'assignments', 'quizzes', 'notifications', 'profile'] as $p) {
    [$code] = page($p, $jarStudent);
    check("Student can open {$p}", $code === 200, "got {$code}");
}
[$code] = page('admin-dashboard', $jarStudent);
check('Student denied admin dashboard (403)', $code === 403, "got {$code}");
[$code] = page('teacher-dashboard', $jarStudent);
check('Student denied teacher dashboard (403)', $code === 403, "got {$code}");
[$code] = page('teacher-lesson-editor', $jarStudent);
check('Student denied teacher lesson editor (403)', $code === 403, "got {$code}");

/* ================= 8. Logout & session behavior ================= */
[$code, $data] = apiLogin($jarStudent, 'student', $PW['student'], 'student');
check('Student re-login after prior checks', !empty($data['success']));
[$code, $body] = http('POST', $base . '/lms_vareen/src/api/auth.php?action=logout', [
    'jar'     => $jarStudent,
    'headers' => ['X-CSRF-Token: ' . getCsrf($jarStudent)],
]);
$logoutOk = $code === 200 && (json_decode($body, true)['success'] ?? false);
check('Logout succeeds (200 + success)', $logoutOk, "got {$code}: " . substr($body, 0, 80));
[$code] = page('student-dashboard', $jarStudent);
check('Dashboard after logout redirects to login (302)', $code === 302, "got {$code}");

/* ================= 9. Unauthenticated access control ================= */
$jar = newJar();
foreach (['admin-dashboard', 'teacher-dashboard', 'student-dashboard', 'profile'] as $p) {
    [$code] = page($p, $jar);
    check("Unauthenticated /{$p} redirects (302)", $code === 302, "got {$code}");
}
[$code] = page('nonexistent-page-xyz', $jar);
check('Unknown page returns 404', $code === 404, "got {$code}");

/* ================= 10. API authorization ================= */
// APIs must reject unauthenticated callers and wrong roles server-side
[$code, $body] = http('GET', $base . '/lms_vareen/src/api/quizzes.php?action=student_list_quizzes&course_id=1', ['jar' => newJar()]);
$unauth = $code === 401 || $code === 403;
check('Quizzes API rejects unauthenticated caller', $unauth, "got {$code}: " . substr($body, 0, 80));

$jarT = newJar();
[$code, $data] = apiLogin($jarT, 'teacher', $PW['teacher'], 'teacher');
[$code, $body] = http('GET', $base . '/lms_vareen/src/api/ai_assistant.php?action=my_lessons', ['jar' => $jarT]);
check('AI API rejects teacher (student-only, 403)', $code === 403, "got {$code}");

/* ================= Summary ================= */
echo "\n================ RESULTS ================\n";
echo "PASS: {$pass}  FAIL: {$fail}\n";
if ($failures) {
    echo "\nFailed checks:\n";
    foreach ($failures as $f) echo " - {$f}\n";
}
exit($fail === 0 ? 0 : 1);



