<?php
// Scratch: dump API action names + request params for view-building (dev tool, not shipped)
chdir(__DIR__ . '/../lms_vareen');
$files = [
    'src/api/admin.php',
    'src/api/public.php',
    'src/api/live_classes.php',
    'src/api/quizzes.php',
    'src/api/assignments.php',
];
foreach ($files as $file) {
    echo PHP_EOL, "==== {$file} ====", PHP_EOL;
    $c = file_get_contents($file);
    if (preg_match_all("/case '([^']+)'/", $c, $m)) {
        echo "ACTIONS: ", implode(', ', $m[1]), PHP_EOL;
    }
    preg_match_all("/\\\$(_GET|_POST)\['([a-z_]+)'\]/", $c, $p, PREG_SET_ORDER);
    $params = [];
    foreach ($p as $x) {
        $params[$x[1]][] = $x[2];
    }
    foreach ($params as $k => $v) {
        echo $k, ': ', implode(', ', array_unique($v)), PHP_EOL;
    }
}
