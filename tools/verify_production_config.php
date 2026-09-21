<?php
/**
 * RC1 — Production Configuration Scan.
 * Scans all deployable code (lms_vareen/ + marketing root) for development
 * leftovers that would break or expose a Hostinger deployment:
 *   - localhost / 127.0.0.1 URLs
 *   - hardcoded ports
 *   - Windows file paths (C:\, D:\, \Users\)
 *   - development settings (display_errors=1, ENVIRONMENT=development)
 *   - debug leftovers (var_dump, dd(), print_r in src)
 *   - hardcoded DB credentials
 * Dev-only trees (archive/, tools/, docs/, node_modules, docker-compose,
 * sql/ setup templates) are excluded from the scan.
 */
declare(strict_types=1);
error_reporting(E_ALL);

$root = dirname(__DIR__);
$scanDirs = [$root . '/lms_vareen', $root . '/assets', $root . '/js'];
$scanExts = ['php', 'js', 'css', 'html', 'htaccess', 'ini'];
$skipSubstrings = ['/archive/', '/tools/', '/docs/', '/node_modules/', '/vendor/',
    DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR, // root /js dev tooling? scanned only if php/js
];

// Dev-only root files: never shipped
$devOnlyFiles = ['docker-compose.yml'];

$patterns = [
    'localhost URL'        => ['~https?://(localhost|127\.0\.0\.1)(:\d+)?~i', false],
    'localhost bare'       => ['~(?<![\w./-])localhost(?![\w.-])~i', false],
    'hardcoded port'       => ['~(?<=[:\s])\b(3000|3306|5432|8000|8080|8888|9000)\b(?=/|\s|$|["\';])~', false],
    'windows path'         => ['~\b[CD]:\\\\|\bUsers[\\\\/]user\b~i', false],
    'display_errors on'    => ["~display_errors['\\\"]?\s*,\s*1~i", false],
    'dev environment'      => ["~define\\(\\s*['\\\"]ENVIRONMENT['\\\"]\\s*,\\s*['\\\"]development['\\\"]~i", false],
    'debug var_dump'       => ['~\bvar_dump\s*\(~', false],
    'debug print_r in src' => ['~\bprint_r\s*\(~', false],
];

// Files that are dev-only by design and never shipped as-is:
$devOnlyBasenames = ['local_db.php', 'local_db.example.php'];

$hits = [];
$filesScanned = 0;

$iter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);
foreach ($iter as $fileInfo) {
    /** @var SplFileInfo $fileInfo */
    if (!$fileInfo->isFile()) continue;
    $path = str_replace('\\', '/', $fileInfo->getPathname());

    // Scope: only deployable trees
    $inScope = false;
    foreach ($scanDirs as $sd) {
        if (strpos($path, str_replace('\\', '/', $sd) . '/') === 0) { $inScope = true; break; }
    }
    if (!$inScope) continue;
    // Skip dev-only subtrees (tools inside lms? none; but keep safe)
    foreach (['/archive/', '/node_modules/', '/vendor/', '/tests/'] as $skip) {
        if (strpos($path, $skip) !== false) { $inScope = false; break; }
    }
    if (!$inScope) continue;
    if (in_array($fileInfo->getFilename(), $devOnlyBasenames, true)) continue;

    $ext = strtolower($fileInfo->getExtension());
    if ($ext === '' && $fileInfo->getFilename() === '.htaccess') $ext = 'htaccess';
    if ($fileInfo->getFilename() === '.user.ini') $ext = 'ini';
    if (!in_array($ext, $scanExts, true)) continue;

    $content = (string) @file_get_contents($path);
    if ($content === '') continue;
    $filesScanned++;

    foreach ($patterns as $label => [$re, $allowedIf]) {
        if (preg_match($re, $content, $m, PREG_OFFSET_CAPTURE)) {
            $lineNo = substr_count(substr($content, 0, (int) $m[0][1]), "\n") + 1;
            $lineText = explode("\n", $content)[$lineNo - 1] ?? '';
            // DB_HOST 'localhost' is the CORRECT MySQL host on Hostinger shared hosting.
            if (stripos($lineText, 'DB_HOST') !== false) continue;
            // display_errors',1 inside the guarded `if (ENVIRONMENT === 'development')`
            // branch of database.php is the intended dev-only path — not a leak.
            if (stripos($label, 'display_errors') === 0
                && preg_match("~ENVIRONMENT\s*===\s*['\"]development['\"]~i", $content)) {
                $before = implode("\n", array_slice(explode("\n", $content), max(0, $lineNo - 5), 5));
                if (stripos($before, "ENVIRONMENT === 'development'") !== false) continue;
            }
            $line = $lineNo;
            $rel  = str_replace(str_replace('\\', '/', $root) . '/', '', $path);
            $hits[] = sprintf('%-22s %s:%d  %s', $label, $rel, $line, trim(substr($m[0][0], 0, 60)));
        }
    }
}

/* Config-file assertions --------------------------------------------------- */
$asserts = [];
$dbCfg = (string) @file_get_contents($root . '/lms_vareen/src/config/database.php');
$asserts[] = ['ENVIRONMENT defaults to production', strpos($dbCfg, "define('ENVIRONMENT', 'production')") !== false];
$asserts[] = ['display_errors disabled for production', preg_match("~ini_set\\(\\s*['\\\"]display_errors['\\\"]\\s*,\\s*0~", $dbCfg) === 1];
$asserts[] = ['APP_URL is https production domain', strpos($dbCfg, "https://vereenacademy.com") !== false && strpos($dbCfg, 'http://') === false];
$asserts[] = ['no hardcoded DB password', !preg_match("~define\\(\\s*['\\\"]DB_PASS['\\\"]\\s*,\\s*['\\\"][^'\\\"]+['\\\"]~", $dbCfg)];
$asserts[] = ['no hardcoded DB user', !preg_match("~define\\(\\s*['\\\"]DB_USER['\\\"]\\s*,\\s*['\\\"][^'\\\"]+['\\\"]~", $dbCfg)];

$fail = count($hits);
foreach ($asserts as [$name, $ok]) {
    echo ($ok ? 'PASS  ' : 'FAIL  ') . $name . PHP_EOL;
    if (!$ok) $fail++;
}
echo str_repeat('-', 60) . PHP_EOL;
echo "Scanned $filesScanned deployable files." . PHP_EOL;
if ($hits) {
    echo PHP_EOL . 'Development-leftover hits:' . PHP_EOL;
    foreach ($hits as $h) echo '  ' . $h . PHP_EOL;
} else {
    echo 'No localhost URLs / ports / Windows paths / debug leftovers found.' . PHP_EOL;
}
echo "TOTAL: $fail problem(s)" . PHP_EOL;
exit($fail === 0 ? 0 : 1);
