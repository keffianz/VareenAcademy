<?php
/**
 * VAREEN-X route coverage checker (VX-024).
 *
 * 1. Extracts every routed page slug from lms_vareen/index.php  (case 'x':)
 * 2. Extracts every "page=slug" link referenced from views/ and JS
 * 3. Reports: broken links (used but not routed), orphan routes (routed but
 *    never linked), and API endpoint files referenced from JS but missing.
 *
 * Report -> tools/route_coverage.txt
 */

$root = dirname(__DIR__);
$lms  = $root . DIRECTORY_SEPARATOR . 'lms_vareen';

function rglob(string $dir, string $ext): array {
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && strtolower($f->getExtension()) === $ext) {
            $out[] = $f->getPathname();
        }
    }
    sort($out);
    return $out;
}

/* ---------- 1. routed slugs ---------- */
$router = file_get_contents($lms . '/index.php');
preg_match_all("/case\s+'([a-z0-9\-_]+)'\s*:/i", $router, $m);
$routed = array_values(array_unique($m[1]));

// Pages routed via `$page === 'slug'` guards outside the switch (auth/public pages)
preg_match_all("/\\\$page\s*===\s*'([a-z0-9\-_]+)'/i", $router, $g);
$routed = array_values(array_unique(array_merge($routed, $g[1])));

/* ---------- 2. referenced slugs ---------- */
$referenced = [];
$apiRefs = [];

foreach (array_merge(rglob($lms . '/views', 'php'), rglob($lms . '/public', 'js')) as $file) {
    $src = file_get_contents($file);
    $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $file);

    if (preg_match_all('/[?&]page=([a-z0-9\-_]+)/i', $src, $mm, PREG_OFFSET_CAPTURE)) {
        foreach ($mm[1] as [$slug, $pos]) {
            // skip JS-built dynamic slugs like page=' + var or page=${...}
            $after = substr($src, $pos + strlen($slug), 3);
            if (strpos($after, "'") === 0 || strpos($after, '"') === 0 || strpos($after, '+') === 0) {
                continue;
            }
            $referenced[$slug][] = $rel;
        }
    }

    if (preg_match_all('#src/api/([a-z0-9\-_]+\.php)#i', $src, $am)) {
        foreach ($am[1] as $api) {
            $apiRefs[$api][] = $rel;
        }
    }
}

/* ---------- 3. compare ---------- */
$broken = [];
foreach ($referenced as $slug => $files) {
    if (!in_array($slug, $routed, true)) {
        $broken[$slug] = array_values(array_unique($files));
    }
}

$orphans = [];
foreach ($routed as $slug) {
    if (!isset($referenced[$slug]) && $slug !== 'default') {
        $orphans[] = $slug;
    }
}

$missingApi = [];
foreach (array_keys($apiRefs) as $api) {
    if (!is_file($lms . '/src/api/' . $api)) {
        $missingApi[$api] = array_values(array_unique($apiRefs[$api]));
    }
}

/* ---------- report ---------- */
$out = "VAREEN-X ROUTE COVERAGE\n" . str_repeat('=', 60) . "\n";
$out .= 'Routed pages: ' . count($routed) . "   Referenced pages: " . count($referenced) . "\n";
$out .= str_repeat('-', 60) . "\n";

$out .= "\nBROKEN LINKS (referenced but NOT routed): " . count($broken) . "\n";
foreach ($broken as $slug => $files) {
    $out .= "  ✗ page={$slug}\n      from: " . implode(', ', $files) . "\n";
}
if (!$broken) $out .= "  none\n";

$out .= "\nMISSING API FILES (referenced from JS but file absent): " . count($missingApi) . "\n";
foreach ($missingApi as $api => $files) {
    $out .= "  ✗ src/api/{$api}\n      from: " . implode(', ', $files) . "\n";
}
if (!$missingApi) $out .= "  none\n";

$out .= "\nORPHAN ROUTES (routed but never linked — informational): " . count($orphans) . "\n";
$out .= '  ' . implode(', ', $orphans) . "\n";

file_put_contents($root . '/tools/route_coverage.txt', $out);
echo "WROTE route_coverage.txt  broken=" . count($broken) . " missingApi=" . count($missingApi) . " orphans=" . count($orphans) . "\n";
