<?php
/**
 * VAREEN-X — consolidated verification runner.
 *
 * Runs every permanent verifier via exec() (shell_exec is unavailable in some
 * host configs) and writes a single consolidated report to
 * tools/verification_report.txt, echoing it to stdout as well.
 *
 * Usage:
 *   php tools/verify_all.php
 *
 * Exit code 0 when every verifier passes, 1 otherwise.
 */

ini_set('display_errors', '1');
error_reporting(E_ALL);

$root = dirname(__DIR__);
$php  = PHP_BINARY;

/** name => [script, human label] */
$suite = [
    'PHP lint (all files)'   => ['lint_all.php',               'Syntax check every .php file in the repo'],
    'Router integrity'       => ['router_integrity.php',       'Routes vs allowlist vs view files'],
    'Admin API contracts'    => ['verify_admin_contracts.php', 'Admin view action -> API case wiring'],
    'Student journey'        => ['verify_student_journey.php', 'VX-009 end-to-end student flow'],
    'Profile modules'        => ['verify_profiles.php',        'VX-048/049 teacher + admin profile wiring'],
    'Placeholder scan'       => ['scan_placeholders.php',      'Stub / "Coming Soon" detection'],
];

$report   = [];
$verdicts = [];

$report[] = str_repeat('=', 76);
$report[] = 'VAREEN-X CONSOLIDATED VERIFICATION REPORT';
$report[] = 'Generated: ' . date('Y-m-d H:i:s') . '   PHP ' . PHP_VERSION;
$report[] = str_repeat('=', 76);

foreach ($suite as $name => [$script, $label]) {
    $path = $root . '/tools/' . $script;
    $report[] = '';
    $report[] = str_repeat('-', 76);
    $report[] = "### {$name}   ({$script})";
    $report[] = "    {$label}";
    $report[] = str_repeat('-', 76);

    if (!file_exists($path)) {
        $report[] = '  ERROR: verifier not found on disk.';
        $verdicts[$name] = 'ERROR';
        continue;
    }

    $output = [];
    $rc     = 0;
    exec(escapeshellarg($php) . ' ' . escapeshellarg($path) . ' 2>&1', $output, $rc);
    $text = trim(implode("\n", $output));

    // VX-011 lint_all writes its own file; show that instead of its stdout.
    if ($script === 'lint_all.php') {
        $lf = $root . '/tools/lint_result.txt';
        if (file_exists($lf)) { $text = trim(file_get_contents($lf)); }
    }
    if ($script === 'check_routes.php') {
        $rf = $root . '/tools/route_coverage.txt';
        if (file_exists($rf)) { $text = trim(file_get_contents($rf)); }
    }

    $report[] = $text !== '' ? $text : '(no output)';

    // Verdict: explicit FAIL strings, "0 failed", or non-zero exit.
    $hasFail = preg_match('/\bFAIL\b|\bFAILED\b|defects|hit\(s\)|syntax errors:\s*[1-9]/i', $text);
    $isClean = preg_match('/\b0 failed\b|\b0 defects\b|PASS:|\b0 hit\(s\)|syntax errors:\s*0\b|0 routing defects/i', $text);
    $verdict = ($rc !== 0 || ($hasFail && !$isClean)) ? 'FAIL' : 'PASS';
    $verdicts[$name] = $verdict;
}

$report[] = '';
$report[] = str_repeat('=', 76);
$report[] = 'SUMMARY';
$report[] = str_repeat('=', 76);
foreach ($verdicts as $name => $v) {
    $report[] = sprintf('  %-6s %s', $v, $name);
}
$failed = count(array_filter($verdicts, fn($v) => $v !== 'PASS'));
$report[] = '';
$report[] = $failed === 0
    ? '  RESULT: ALL VERIFIERS PASS'
    : "  RESULT: {$failed} verifier(s) FAILED";

$text = implode("\n", $report) . "\n";
file_put_contents($root . '/tools/verification_report.txt', $text);
echo $text;

exit($failed === 0 ? 0 : 1);
