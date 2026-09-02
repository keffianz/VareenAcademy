<?php
/**
 * One-shot patch: rewrite root-absolute API fetch URLs in views to
 * deployment-correct paths using appBasePath() (e.g. /lms_vareen/src/api/...).
 * Idempotent: the replacement text no longer contains the search substring.
 */
$dir = __DIR__ . '/../lms_vareen/views';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$changed = [];
foreach ($rii as $f) {
    if ($f->getExtension() !== 'php') continue;
    $p = str_replace('\\', '/', $f->getPathname());
    $c = file_get_contents($p);
    $n = str_replace(
        ["'/src/api/", '"/src/api/', '`/src/api/'],
        ["'<?php echo appBasePath(); ?>/src/api/", '"<?php echo appBasePath(); ?>/src/api/', '`<?php echo appBasePath(); ?>/src/api/'],
        $c
    );
    if ($n !== $c) {
        file_put_contents($p, $n);
        $changed[] = basename($p);
    }
}
echo count($changed) . " file(s) patched:\n" . implode("\n", $changed) . "\n";
