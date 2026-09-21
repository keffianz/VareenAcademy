<?php
/**
 * Lint every PHP file in the repo (excluding archive/vendor/node_modules)
 * and write a report to tools/lint_result.txt
 */
ini_set('display_errors', '1');
error_reporting(E_ALL);

$root = dirname(__DIR__);
$excludeDirs = ['vendor', 'node_modules', 'archive'];
$phpFiles = [];

// Collect files defensively
try {
    $dirIt = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            function ($file, $key, $iterator) use ($excludeDirs) {
                try {
                    if ($file->isDir()) {
                        return !in_array($file->getFilename(), $excludeDirs, true);
                    }
                    return $file->isFile() && $file->getExtension() === 'php';
                } catch (Throwable $e) {
                    return false;
                }
            }
        )
    );
    foreach ($dirIt as $f) {
        try {
            if ($f->isFile() && $f->getExtension() === 'php') {
                $phpFiles[] = $f->getPathname();
            }
        } catch (Throwable $e) {
            // skip unreadable
        }
    }
} catch (Throwable $e) {
    // ignore
}

$result = [];
$result[] = 'PHP ' . PHP_VERSION;
$result[] = 'Files found: ' . count($phpFiles);

$errors = [];
$checked = 0;
foreach ($phpFiles as $file) {
    $checked++;
    $cmd = 'php -l ' . escapeshellarg($file) . ' 2>&1';
    $output = [];
    $rc = 0;
    try {
        exec($cmd . ' 2>&1', $output, $rc);
    } catch (Throwable $e) {
        $output = ['EXCEPTION: ' . $e->getMessage()];
        $rc = 999;
    }
    $line = trim(implode("\n", $output));
    if ($rc !== 0 || strpos($line, 'No syntax errors') === false) {
        $errors[] = str_replace($root . DIRECTORY_SEPARATOR, '', $file) . ' => ' . $line;
    }
}

$result[] = 'Files checked: ' . $checked;
$result[] = 'Files with syntax errors: ' . count($errors);
$result[] = str_repeat('=', 60);
foreach ($errors as $e) {
    $result[] = $e;
}

file_put_contents(__DIR__ . '/lint_result.txt', implode("\n", $result) . "\n");
echo "WROTE lint_result.txt checked=" . $checked . " errors=" . count($errors) . "\n";