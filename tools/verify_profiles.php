<?php
$root = dirname(__DIR__);
$out = [];

function ok($c) { return $c ? 'PASS' : '*** FAIL ***'; }

/* ---- teacher/profile.php ---- */
$tp = file_get_contents($root . '/lms_vareen/views/teacher/profile.php');
$out[] = '=== views/teacher/profile.php (' . (substr_count($tp, "\n") + 1) . ' lines, ' . strlen($tp) . ' bytes) ===';
$checks = [
    'form id="pwForm"'            => strpos($tp, 'id="pwForm"') !== false,
    'input id="pwOld"'            => strpos($tp, 'id="pwOld"') !== false,
    'input id="pwNew"'            => strpos($tp, 'id="pwNew"') !== false,
    'input id="pwConfirm"'        => strpos($tp, 'id="pwConfirm"') !== false,
    'button id="btnPwSave"'       => strpos($tp, 'id="btnPwSave"') !== false,
    'pwMsg div'                   => strpos($tp, 'id="pwMsg"') !== false,
    'avatar input'                => strpos($tp, 'id="teacherAvatarInput"') !== false,
    'avatar preview'              => strpos($tp, 'id="teacherAvatarPreview"') !== false,
    'avatar file size span'       => strpos($tp, 'id="teacherAvFileSize"') !== false,
    'Auth.changePassword used'    => strpos($tp, 'Auth.changePassword') !== false,
    'Auth.uploadAvatar used'      => strpos($tp, 'Auth.uploadAvatar') !== false,
    'auth.js included'            => strpos($tp, 'js/auth.js') !== false,
    'NO dead window.CSRF_TOKEN'   => strpos($tp, 'window.CSRF_TOKEN') === false,
    'NO AUTH_API const'           => strpos($tp, 'const AUTH_API') === false,
    'NO admin-msg class'          => strpos($tp, 'admin-msg') === false,
    'NO null-id getElementById'   => strpos($tp, "getElementById(\"pwForm\")") === false,
];
foreach ($checks as $k => $v) { $out[] = sprintf('  %-28s %s', $k, ok($v)); }
$out[] = '  script tags: ' . substr_count($tp, '<script');
$out[] = '  </script> tags: ' . substr_count($tp, '</script>');

/* ---- admin/profile.php ---- */
$ap = file_get_contents($root . '/lms_vareen/views/admin/profile.php');
$out[] = '';
$out[] = '=== views/admin/profile.php (' . (substr_count($ap, "\n") + 1) . ' lines, ' . strlen($ap) . ' bytes) ===';
$achecks = [
    'requireRole(admin)'          => strpos($ap, "requireRole('admin')") !== false,
    'require Database.php'        => strpos($ap, "require_once 'src/classes/Database.php'") !== false,
    'form id="profForm"'          => strpos($ap, 'id="profForm"') !== false,
    'form id="pwForm"'            => strpos($ap, 'id="pwForm"') !== false,
    'input id="pwOld"'            => strpos($ap, 'id="pwOld"') !== false,
    'button id="btnPwSave"'       => strpos($ap, 'id="btnPwSave"') !== false,
    'adminAvatarInput'            => strpos($ap, 'id="adminAvatarInput"') !== false,
    'adminAvatarPreview'          => strpos($ap, 'id="adminAvatarPreview"') !== false,
    'admin_profile_update call'   => strpos($ap, 'admin_profile_update') !== false,
    'change_password call'        => strpos($ap, 'change_password') !== false,
    'upload_avatar call'          => strpos($ap, 'upload_avatar') !== false,
    'admin_active=profile'        => strpos($ap, "\$admin_active = 'profile'") !== false,
];
foreach ($achecks as $k => $v) { $out[] = sprintf('  %-28s %s', $k, ok($v)); }
$out[] = '  script tags: ' . substr_count($ap, '<script');
$out[] = '  </script> tags: ' . substr_count($ap, '</script>');

/* ---- balanced-brace / tag sanity ---- */
$out[] = '';
$out[] = '=== Sanity ===';
$out[] = '  teacher <script>/<\/script> balanced: ' . ok(substr_count($tp, '<script') === substr_count($tp, '</script>'));
$out[] = '  admin   <script>/<\/script> balanced: ' . ok(substr_count($ap, '<script') === substr_count($ap, '</script>'));
$out[] = '  teacher <style>/<\/style> balanced: ' . ok(substr_count($tp, '<style') === substr_count($tp, '</style>'));
$out[] = '  admin   <style>/<\/style> balanced: ' . ok(substr_count($ap, '<style') === substr_count($ap, '</style>'));

file_put_contents($root . '/tools/_verify_profiles.txt', implode("\n", $out) . "\n");
echo implode("\n", $out) . "\n";
