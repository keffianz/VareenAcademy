<?php
/**
 * VAREEN-X placeholder sweeper (Phase 2 / Phase 12 QA helper).
 * Flags stub UI: "coming soon", "not implemented", "TODO", "placeholder",
 * alert()/die() stubs, and disabled buttons that never do anything.
 * Read-only: reports file + line + matched text.
 */

$root = dirname(__DIR__) . '/lms_vareen';
$patterns = [
    'coming soon'      => '/coming\s+soon/i',
    'not implemented'  => '/not\s+implemented/i',
    'not yet'          => '/not\s+yet\s+(available|implemented|wired)/i',
    'under construction' => '/under\s+construction/i',
    'todo marker'      => '/\b(TODO|FIXME|XXX)\b/',
    'placeholder text' => '/\b(lorem ipsum|dummy text|sample text)\b/i',
    'stub alert'       => '/\balert\s*\(\s*[\'"][^\'"]*(soon|todo|not implemented)/i',
];

$results = [];
$dirs = ['views', 'src/api', 'src/classes', 'public/js'];
foreach ($dirs as $dir) {
    $base = $root . '/' . $dir;
    if (!is_dir($base)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile()) continue;
        $ext = strtolower($f->getExtension());
        if (!in_array($ext, ['php', 'js'], true)) continue;
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen(dirname($root)) + 1));
        $lines = file($f->getPathname()) ?: [];
        foreach ($lines as $i => $line) {
            foreach ($patterns as $label => $rx) {
                if (preg_match($rx, $line)) {
                    $results[] = sprintf('%s:%d [%s] %s', $rel, $i + 1, $label, trim($line));
                    break;
                }
            }
        }
    }
}

echo "PLACEHOLDER SCAN — " . count($results) . " hit(s)\n";
echo str_repeat('=', 60) . "\n";
foreach ($results as $r) echo $r . "\n";
