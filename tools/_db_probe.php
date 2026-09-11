<?php
chdir(__DIR__ . '/../lms_vareen');
require 'src/config/database.php';
require 'src/classes/Community.php';
try {
    $db = Database::getInstance()->connect();
    echo "DB_OK\n";
    $cnt = $db->query("SELECT COUNT(*) FROM users WHERE role IN ('student','teacher') AND is_active=1")->fetchColumn();
    echo "seeded_student_teacher=$cnt\n";
    $cnt2 = $db->query("SELECT COUNT(*) FROM community_posts")->fetchColumn();
    echo "community_posts=$cnt2\n";
} catch (Throwable $e) {
    echo "DB_ERR: " . $e->getMessage() . " @ line " . $e->getLine() . "\n";
}
