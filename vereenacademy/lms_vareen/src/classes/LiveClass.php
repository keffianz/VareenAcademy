<?php
/**
 * LiveClass Class
 * (Lightweight helper; API uses direct SQL for now)
 */

require_once 'Database.php';

class LiveClass {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    public function getLiveClassesByCourse($course_id, $limit = 50) {
        $stmt = $this->db->prepare("SELECT * FROM live_classes WHERE course_id = :course_id ORDER BY scheduled_at DESC LIMIT :limit");
        $stmt->bindValue(':course_id', (int)$course_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

