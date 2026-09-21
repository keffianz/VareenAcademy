<?php
/**
 * VAREEN-X — Admin view↔API contract verification (VX-011).
 *
 * Extracts every `action=X` the admin views call (JS fetch + jpost() calls
 * against src/api/admin.php and src/api/payments.php) and verifies each one
 * exists as a `case 'X':` in the corresponding API file, plus that the two
 * server-rendered admin pages (teachers.php, coupons.php) define their POST
 * handlers. Catches dangling wiring (e.g. view calling a renamed action)
 * without needing a live DB.
 *
 * Usage: php tools/verify_admin_contracts.php
 */

$root = dirname(__DIR__);
$lms  = $root . '/lms_vareen';
$pass = 0; $fail = 0; $notes = [];

function adminCheck($label, $ok, &$pass, &$fail, &$notes) {
    if ($ok) { $pass++; echo "PASS  $label\n"; }
    else { $fail++; echo "FAIL  $label\n"; $notes[] = $label; }
}

// Cache of API-file => [cases...]
$apiCases = [];
function getCases($lms, $apiFile, &$apiCases) {
    if (!isset($apiCases[$apiFile])) {
        $src = file_exists($lms . '/src/api/' . $apiFile) ? file_get_contents($lms . '/src/api/' . $apiFile) : '';
        preg_match_all("/case\s+['\"]([^'\"]+)['\"]/", $src, $cm);
        $apiCases[$apiFile] = array_values(array_unique($cm[1]));
    }
    return $apiCases[$apiFile];
}
getCases($lms, 'admin.php', $apiCases);
getCases($lms, 'payments.php', $apiCases);
getCases($lms, 'auth.php', $apiCases);
echo 'admin.php cases (' . count($apiCases['admin.php']) . ")\n";
echo 'payments.php cases (' . count($apiCases['payments.php']) . ")\n";
echo 'auth.php cases (' . count($apiCases['auth.php']) . ")\n\n";

// Special-case files whose calls target a sibling API instead of admin.php
$fixedMap = [
    'views/admin/payments.php' => 'payments.php',
];

// Every admin view: extract called action names, then verify each against the
// API file that view's own JS targets (const API= / apiBase=), default admin.php.
$files = glob($lms . '/views/admin/*.php') ?: [];
foreach ($files as $file) {
    $rel = str_replace($root . '/', '', $file);
    $src = file_get_contents($file);
    if ($src === false) continue;

    // 1) Which API file does THIS view's JS hit?
    if (isset($fixedMap[$rel])) {
        $apiFiles = [$fixedMap[$rel]];
    } elseif (preg_match_all('#src/api/([a-z0-9_]+\.php)#i', $src, $m) && $m[1]) {
        // Keep EVERY API the view references. Views such as admin/profile.php
        // post profile data to admin.php but credentials/avatar to auth.php;
        // dropping auth.php here caused false failures. The matching loop below
        // accepts an action found in ANY of these files.
        $apiFiles = array_values(array_unique($m[1]));
    } else {
        $apiFiles = ['admin.php'];
    }

    // 2) Which action names does it call?
    $used = [];
    if (preg_match_all("/[?&]action=([a-z0-9_]+)/i", $src, $m)) {
        $used = array_merge($used, $m[1]);
    }
    if (preg_match_all("/jpost\(\s*['\"]([a-z0-9_]+)['\"]/i", $src, $m2)) {
        $used = array_merge($used, $m2[1]);
    }
    if (preg_match_all("/[^a-z_]act\(\s*['\"]([a-z0-9_]+)['\"]/i", $src, $m3)) {
        $used = array_merge($used, $m3[1]);
    }
    // Actions built dynamically then passed to jpost/fetch: ternary + dataset literals
    if (preg_match_all("/['\"]([a-z0-9_]+(?:_list|_status|_read|_approve|_reject|_review|_update|_create|_set_[a-z_]+|_get|_stats|_process))['\"]/", $src, $m4)) {
        $used = array_merge($used, $m4[1]);
    }
    // Literal 'logout' belongs to auth.php — verify separately, drop from main check
    $logoutUsed = in_array('logout', $used, true);
    $used = array_values(array_unique(array_diff($used, ['logout'])));
    if (!$used && !$logoutUsed) continue;

    foreach ($apiFiles as $apiFile) {
        if (!isset($apiCases[$apiFile])) {
            getCases($lms, $apiFile, $apiCases);
        }
    }

    // A view may legitimately talk to more than one API (e.g. admin/profile.php
    // posts personal data to admin.php but password/avatar to auth.php). Static
    // analysis cannot tell which fetch() targets which constant, so an action
    // passes when it exists in ANY API file the view references. Single-API
    // views stay strict because the union is then just that one file.
    foreach ($used as $action) {
        $matchedIn = null;
        foreach ($apiFiles as $apiFile) {
            if (in_array($action, $apiCases[$apiFile] ?? [], true)) { $matchedIn = $apiFile; break; }
        }
        adminCheck(
            "$rel -> action '$action' exists in " . ($matchedIn ?: implode('|', $apiFiles)),
            $matchedIn !== null, $pass, $fail, $notes
        );
    }
    if ($logoutUsed) {
        adminCheck("$rel -> action 'logout' exists in auth.php",
            in_array('logout', $apiCases['auth.php'] ?? [], true), $pass, $fail, $notes);
    }
}

echo "=== Server-rendered POST handlers ===\n";
$serverRendered = [
    'views/admin/teachers.php' => ['toggle_active'],
    'views/admin/coupons.php'  => ['create_coupon', 'delete_coupon'],
];
foreach ($serverRendered as $view => $handlers) {
    $src = file_get_contents($lms . '/' . $view);
    foreach ($handlers as $h) {
        adminCheck("$view handles POST '$h'",
            strpos($src, "'$h'") !== false || strpos($src, "\"$h\"") !== false,
            $pass, $fail, $notes);
    }
}

echo "\n=== Result: $pass passed, $fail failed ===\n";
if ($fail) { foreach ($notes as $n) echo "  - $n\n"; }
exit($fail ? 1 : 0);
