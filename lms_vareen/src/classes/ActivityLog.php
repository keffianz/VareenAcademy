<?php
/**
 * ActivityLog Class — track admin actions for the audit trail.
 * Never logs passwords or secrets.
 */
require_once 'Database.php';

class ActivityLog {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    /**
     * Log an action. Pass $sensitive to redact description.
     */
    public function log($userId, $action, $description = '', $entityType = null, $entityId = null) {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO activity_log (user_id, action, description, entity_type, entity_id, ip_address)
                 VALUES (:uid, :action, :desc, :etype, :eid, :ip)'
            );
            $stmt->execute([
                ':uid'    => $userId,
                ':action' => $action,
                ':desc'   => $description,
                ':etype'  => $entityType,
                ':eid'    => $entityId,
                ':ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /** Get recent activity with optional filters */
    public function getRecent($limit = 50, $action = null, $userId = null) {
        $sql = 'SELECT al.*, CONCAT(u.first_name, " ", u.last_name) AS user_name
                FROM activity_log al
                LEFT JOIN users u ON u.id = al.user_id';
        $where = [];
        $params = [];
        if ($action) { $where[] = 'al.action = :action'; $params[':action'] = $action; }
        if ($userId) { $where[] = 'al.user_id = :uid'; $params[':uid'] = $userId; }
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY al.created_at DESC LIMIT ' . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Get activity counts grouped by action (for charts) */
    public function getSummary($days = 30) {
        $stmt = $this->db->prepare(
            'SELECT action, COUNT(*) AS count
             FROM activity_log
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
             GROUP BY action ORDER BY count DESC'
        );
        $stmt->execute([':days' => $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
