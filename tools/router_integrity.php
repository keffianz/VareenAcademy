<?php
/**
 * VAREEN-X Router Integrity Checker
 *
 * Finds routes that are declared in $knownPages (or linked from views) but have
 * no `case` in the router switch — those render a silent 404 / blank page.
 * Also finds switch cases missing from the allowlist (unreachable = 404).
 *
 * Usage: php tools/router_integrity.php
 */

$root = dirname(__DIR__);
$indexPath = "$root/lms_vareen/index.php";
$src = file_get_contents($indexPath);

// 1. Extract $knownPages array entries
$known = [];
if (preg_match('/\$knownPages\s*=\s*\[(.*?)\];/s', $src, $m)) {
    preg_match_all("/'([a-z0-9\-]+)'/i", $m[1], $mm);
    $known = $mm[1];
}

// 2. Extract `case 'x':` names from the switch
preg_match_all("/case\s+'([a-z0-9\-]+)'\s*:/i", $src, $cm);
$cases = $cm[1];

// 3. Extract standalone `$page === 'x'` early-return routes
preg_match_all("/\\\$page\s*===\s*'([a-z0-9\-]+)'/i", $src, $pm);
$standalone = array_unique($pm[1]);

$allRouted = array_values(array_unique(array_merge($cases, $standalone)));

echo "VAREEN-X ROUTER INTEGRITY\n";
echo str_repeat('=', 60) . "\n";
echo "Allowlist entries : " . count($known) . "\n";
echo "Switch cases      : " . count($cases) . "\n";
echo "Standalone routes : " . implode(', ', $standalone) . "\n\n";

// A. Allowlisted but no case and not standalone => allowlist passes, switch falls to default => 404
$allowNoCase = array_values(array_diff($known, $allRouted));
echo "A. ALLOWLISTED BUT NO ROUTE (silent 404): " . count($allowNoCase) . "\n";
foreach ($allowNoCase as $p) {
    echo "   ✗ page=$p\n";
}

// B. Case exists but missing from allowlist => blocked by allowlist => 404
$caseNoAllow = array_values(array_diff($cases, $known));
echo "\nB. ROUTED BUT NOT ALLOWLISTED (blocked before switch): " . count($caseNoAllow) . "\n";
foreach ($caseNoAllow as $p) {
    echo "   ✗ page=$p\n";
}

// C. Routed pages whose view file is missing
$viewMap = [];
preg_match_all("/case\s+'([a-z0-9\-]+)'\s*:\s*(?:.*?)\n(?:.*?)render_page\(\s*'([^']+)'/i", $src, $rm, PREG_SET_ORDER);
foreach ($rm as $r) {
    $viewMap[$r[1]] = $r[2];
}
$viewMissing = [];
foreach ($viewMap as $page => $view) {
    $abs = "$root/lms_vareen/$view";
    if (!is_file($abs)) $viewMissing[$page] = $view;
}
echo "\nC. ROUTE VIEW FILES MISSING ON DISK: " . count($viewMissing) . "\n";
foreach ($viewMissing as $page => $view) {
    echo "   ✗ page=$page => $view\n";
}

// D. Links in views pointing at page=x where x is not routed at all
$viewsDir = "$root/lms_vareen/views";
$refs = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
foreach ($it as $f) {
    if (!$f->isFile() || $f->getExtension() !== 'php') continue;
    $body = file_get_contents($f->getPathname());
    if (preg_match_all("/page=([a-z0-9\-]+)/i", $body, $lm)) {
        foreach ($lm[1] as $target) {
            $rel = str_replace("$root/lms_vareen/views" . DIRECTORY_SEPARATOR, '', $f->getPathname());
            $refs[$target][] = $rel;
        }
    }
}
$brokenRefs = [];
foreach ($refs as $target => $sources) {
    if (!in_array($target, $allRouted, true)) {
        $brokenRefs[$target] = array_unique($sources);
    }
}
echo "\nD. LINKS TO NON-ROUTED PAGES (dead links): " . count($brokenRefs) . "\n";
foreach ($brokenRefs as $target => $sources) {
    echo "   ✗ page=$target\n      from: " . implode(', ', $sources) . "\n";
}

// Round-trip: pages referenced by redirectTo() inside index.php
preg_match_all("/redirectTo\(\s*'[^']*page=([a-z0-9\-]+)/i", $src, $rd);
echo "\nE. INTERNAL REDIRECT TARGETS NOT ROUTED: ";
$badRedirects = [];
foreach (array_unique($rd[1]) as $t) {
    if (!in_array($t, $allRouted, true)) $badRedirects[] = $t;
}
echo count($badRedirects) . "\n";
foreach ($badRedirects as $t) echo "   ✗ redirect => page=$t\n";

$critical = count($allowNoCase) + count($caseNoAllow) + count($viewMissing) + count($brokenRefs) + count($badRedirects);
echo "\n" . str_repeat('=', 60) . "\n";
echo ($critical === 0 ? "PASS: router is consistent\n" : "FAIL: $critical routing defects\n");
