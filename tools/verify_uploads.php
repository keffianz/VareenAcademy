<?php
/**
 * RC1 — Upload & Storage Verification (no DB required).
 * Checks every writable directory the app needs, protective .htaccess files,
 * PHP upload limits (.user.ini) vs Uploader rules, and exercises the Uploader
 * path-resolution / traversal-guard / delete round-trip on the real filesystem.
 */
declare(strict_types=1);
error_reporting(E_ALL);

$root = dirname(__DIR__);           // repo root
$lms  = $root . '/lms_vareen';
$pass = 0;
$fail = 0;
$results = [];

function check(string $name, bool $ok, string $note = ''): void {
    global $pass, $fail, $results;
    if ($ok) { $pass++; $results[] = "PASS  $name" . ($note !== '' ? " — $note" : ''); }
    else     { $fail++; $results[] = "FAIL  $name" . ($note !== '' ? " — $note" : ''); }
}

/* 1. Required writable directories ---------------------------------------- */
$requiredDirs = [
    'Uploader base (assets/uploads)'        => $lms . '/assets/uploads',
    'Uploader images'                       => $lms . '/assets/uploads/images',
    'Uploader resources'                    => $lms . '/assets/uploads/resources',
    'Uploader videos'                       => $lms . '/assets/uploads/videos',
    'Payment proofs (uploads/payment_proofs)' => $lms . '/uploads/payment_proofs',
    'Runtime storage (storage/)'            => $lms . '/storage',
    'src/config (ai_local_key.php target)'  => $lms . '/src/config',
];
foreach ($requiredDirs as $name => $dir) {
    check("dir exists: $name", is_dir($dir), $dir);
    check("dir writable: $name", is_dir($dir) && is_writable($dir));
}

/* 2. Protective .htaccess in every private dir ----------------------------- */
$guarded = [
    $lms . '/assets/uploads/.htaccess',
    $lms . '/uploads/payment_proofs/.htaccess',
    $lms . '/storage/.htaccess',
];
foreach ($guarded as $f) {
    check('.htaccess guard: ' . basename(dirname($f)), is_file($f));
}

/* 3. LMS .htaccess must still block direct web access to storage paths ----- */
$lmsHt = (string) @file_get_contents($lms . '/.htaccess');
foreach (['assets/uploads', 'storage', 'uploads'] as $needle) {
    check("lms_vareen/.htaccess blocks '$needle'", strpos($lmsHt, $needle) !== false);
}

/* 4. .user.ini upload limits vs Uploader rules ------------------------------ */
$userIni = (string) @file_get_contents($lms . '/.user.ini');
check('.user.ini present', $userIni !== '');
$parsed = [];
foreach (preg_split('/\r?\n/', $userIni) as $line) {
    if (preg_match('/^\s*([a-z_]+)\s*=\s*(.+)$/i', $line, $m)) {
        $parsed[strtolower($m[1])] = trim($m[2]);
    }
}
$iniToBytes = static function (string $v): int {
    $v = strtolower(trim($v));
    if (!preg_match('/^(\d+)\s*([kmg]?)$/', $v, $m)) return 0;
    $n = (int) $m[1];
    return $n * match ($m[2]) { 'g' => 1 << 30, 'm' => 1 << 20, 'k' => 1 << 10, default => 1 };
};
$videoMax = 500 * 1024 * 1024; // Uploader::TYPE_RULES['video']['max']
check('.user.ini upload_max_filesize >= 500M (video rule)',
    isset($parsed['upload_max_filesize']) && $iniToBytes($parsed['upload_max_filesize']) >= $videoMax,
    $parsed['upload_max_filesize'] ?? 'missing');
check('.user.ini post_max_size >= 500M',
    isset($parsed['post_max_size']) && $iniToBytes($parsed['post_max_size']) >= $videoMax,
    $parsed['post_max_size'] ?? 'missing');

/* 5. Uploader round-trip: resolve + traversal guard + delete ----------------- */
require_once $lms . '/src/classes/Uploader.php';
try {
    $up = new Uploader();
    $base = $up->getBasePath();
    check('Uploader base resolves', is_string($base) && $base !== '', $base);
    check('Uploader base is inside assets/uploads', str_replace('\\', '/', $base) === str_replace('\\', '/', realpath($lms . '/assets/uploads')));

    $probeName = 'rc1-probe-' . bin2hex(random_bytes(6)) . '.jpg';
    $y = (int) date('Y');
    $m = (int) date('n');
    $probeRel   = 'images/' . $y . '/' . $m . '/' . $probeName;              // base-relative form
    $probeRelDb = 'assets/uploads/' . $probeRel;                             // DB form (what callers pass)
    $probeAbs   = $base . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $y . DIRECTORY_SEPARATOR . $m . DIRECTORY_SEPARATOR . $probeName;
    @mkdir(dirname($probeAbs), 0755, true);
    check('probe written', (bool) @file_put_contents($probeAbs, 'RC1-UPLOAD-PROBE'), $probeAbs);

    $resolved = $up->resolveStoredPath($probeRel, 'image');
    check('resolveStoredPath resolves base-relative probe', is_string($resolved) && is_file($resolved), (string) $resolved);
    $resolvedDb = $up->resolveStoredPath($probeRelDb, 'image');
    check('resolveStoredPath resolves DB-form probe (caller contract)', is_string($resolvedDb) && is_file($resolvedDb), $probeRelDb);

    // Traversal guard: must refuse to escape the base / the type dir.
    check('traversal guard blocks ../ escape',
        $up->resolveStoredPath('../../src/config/database.php', 'image') === false);
    check('traversal guard blocks wrong type-dir',
        $up->resolveStoredPath('videos/' . $probeName, 'image') === false);

    check('removeStoredFile deletes probe (DB form)', $up->removeStoredFile($probeRelDb));
    check('probe really gone', !is_file($probeAbs));

    // Empty parent dirs are legitimately cleaned up to base; verify + restore for deployment.
    check('empty parent dirs cleaned after delete', !is_dir(dirname($probeAbs, 2)));
    if (!is_dir($base . DIRECTORY_SEPARATOR . 'images')) { @mkdir($base . DIRECTORY_SEPARATOR . 'images', 0755, true); }
    check('images/ dir restored after cleanup', is_dir($base . DIRECTORY_SEPARATOR . 'images'));
} catch (Throwable $e) {
    check('Uploader round-trip threw', false, $e->getMessage());
}

/* 6. Uploader type rules sane ------------------------------------------------ */
try {
    $ref = new ReflectionClass('Uploader');
    $rules = $ref->getConstant('TYPE_RULES');
    foreach (['video', 'document', 'image'] as $t) {
        check("TYPE_RULES[$t] present", isset($rules[$t]) && !empty($rules[$t]['ext']) && !empty($rules[$t]['mime']));
    }
    check('no .php/.phtml in any upload whitelist',
        !in_array('php', array_merge(...array_column($rules, 'ext')), true)
        && !in_array('phtml', array_merge(...array_column($rules, 'ext')), true));
} catch (Throwable $e) {
    check('TYPE_RULES reflection failed', false, $e->getMessage());
}

/* Output ------------------------------------------------------------------- */
foreach ($results as $r) { echo $r . PHP_EOL; }
echo str_repeat('-', 60) . PHP_EOL;
echo "TOTAL: $pass passed, $fail failed" . PHP_EOL;
exit($fail === 0 ? 0 : 1);
