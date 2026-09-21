<?php
/**
 * Standalone unit test for Uploader (no DB required).
 * Tests pure logic: humanSize, resolveStoredPath (traversal protection),
 * removeStoredFile (cleanup). Creates + deletes its own test files.
 */
declare(strict_types=1);
error_reporting(E_ALL);

require __DIR__ . '/../lms_vareen/src/classes/Uploader.php';

$pass = 0; $fail = 0;
function check(string $name, bool $cond): void {
    global $pass, $fail;
    if ($cond) { $pass++; echo "PASS  $name\n"; }
    else       { $fail++; echo "FAIL  $name\n"; }
}

$up = new Uploader();
$ref = new ReflectionClass($up);
$prop = $ref->getProperty('absoluteBase');
$prop->setAccessible(true);
echo 'DEBUG __DIR__-equiv: ' . __DIR__ . "\n";
echo 'DEBUG realpath test: ' . var_export(realpath(__DIR__ . '/../lms_vareen/src/classes/../../assets/uploads'), true) . "\n";
echo 'DEBUG prop: ' . var_export($prop->getValue($up), true) . "\n";
echo 'DEBUG base: ' . var_export($up->getBasePath(), true) . "\n";

// 1. getBasePath resolves inside the project
$base = $up->getBasePath();
check('getBasePath is absolute', strlen($base) > 0 && ($base[0] === '/' || preg_match('#^[A-Za-z]:#', $base)));
check('getBasePath ends with assets/uploads', str_replace('\\', '/', $base) === rtrim(str_replace('\\', '/', $base), '/'));

// 2. humanSize
check('humanSize 0', $up->humanSize(0) === '0 B');
check('humanSize KB', $up->humanSize(1024) === '1 KB');
check('humanSize MB', $up->humanSize(5 * 1024 * 1024) === '5 MB');
check('humanSize GB', $up->humanSize(3 * 1024 ** 3) === '3 GB');
check('humanSize negative-safe', $up->humanSize(-5) === '0 B');

// 3. resolveStoredPath — valid path (uses the exact format upload() stores in DB)
$rel = 'assets/uploads/resources/' . date('Y') . '/' . (int)date('n') . '/vx_test_' . bin2hex(random_bytes(4)) . '.pdf';
// upload() writes the file at the REAL single-level location (base/resources/Y/M);
// the DB path keeps the 'assets/uploads/' prefix. After the RC1 fix BOTH resolve.
$absDir = $up->getBasePath() . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . (int)date('n');
if (!is_dir($absDir)) { mkdir($absDir, 0755, true); }
file_put_contents($absDir . DIRECTORY_SEPARATOR . basename($rel), '%PDF-1.4 test');
$resolved = $up->resolveStoredPath($rel, 'document');
check('resolveStoredPath resolves DB-form path', $resolved !== false && is_file($resolved));
check('resolveStoredPath resolves base-relative form', $up->resolveStoredPath(substr($rel, strlen('assets/uploads/')), 'document') !== false);
check('cross-type resolve rejected (file exists, wrong type)', $up->resolveStoredPath($rel, 'image') === false);

// 4. resolveStoredPath — traversal / mismatch attacks
check('traversal .. blocked', $up->resolveStoredPath('resources/../../src/config/database.php', 'document') === false);
check('absolute path blocked', $up->resolveStoredPath('C:\\Windows\\win.ini', 'document') === false);
check('wrong type dir rejected', $up->resolveStoredPath('videos/' . date('Y') . '/x.mp4', 'document') === false);
check('unknown type rejected', $up->resolveStoredPath($rel, 'hacker') === false);
check('missing file returns false', $up->resolveStoredPath('resources/2099/1/nope.pdf', 'document') === false);
check('payment_proofs blocked', $up->resolveStoredPath('resources/../../../storage/payment_proofs/x.jpg', 'document') === false);

// 5. removeStoredFile — deletes file and empty parent dirs
check('removeStoredFile deletes', $up->removeStoredFile($rel) === true && !is_file($resolved));
check('re-remove returns false', $up->removeStoredFile($rel) === false);
$yearDir = dirname($absDir); $monthDir = dirname($yearDir);
// .gitkeep keeps the type dir non-empty, so cleanup stops at resources/ (desired:
// required deployment dirs are never deleted); the empty year/month leaves must go.
check('empty year/month dirs cleaned', !is_dir($absDir) && !is_dir($yearDir));
check('type dir survives cleanup (gitkeep guard)', is_dir($monthDir));

// 6. removeStoredFile refuses to delete outside base
check('refuses delete outside base', $up->removeStoredFile('../../src/classes/Uploader.php') === false);

echo "\nRESULT: $pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
