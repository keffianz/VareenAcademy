<?php
// Scratch: dump json_encode response wrappers (dev tool, not shipped)
chdir(__DIR__ . '/../lms_vareen');
foreach (['src/api/admin.php', 'src/api/public.php', 'src/api/live_classes.php'] as $f) {
    echo '==== ', $f, ' ====', PHP_EOL;
    $lines = file($f);
    foreach ($lines as $i => $line) {
        if (strpos($line, 'json_encode') !== false) {
            echo $i + 1, ': ', trim($line), PHP_EOL;
        }
    }
}
