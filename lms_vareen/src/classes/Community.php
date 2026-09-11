<?php
/**
 * Community Class — manage communities, channels, posts, comments, likes.
 */
require_once 'Database.php';

class Community {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    /* ========== COMMUNITIES ========== */

    public function getAll() {
        $stmt = $this->db->prepare(
            'SELECT c.*, COUNT(DISTINCT ch.id) AS channel_count,
                    (SELECT COUNT(*) FROM community_posts p WHERE p.community_id = c.id AND p.is_deleted = 0) AS post_count
             FROM communities c
             LEFT JOIN community_channels ch ON ch.community_id = c.id AND ch.is_active = 1
             GROUP BY c.id ORDER BY c.sort_order, c.name'
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare('SELECT * FROM communities WHERE id = :id');
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($name, $slug, $description, $icon = 'fa-users', $category = 'general') {
        $stmt = $this->db->prepare(
            'INSERT INTO communities (name, slug, description, icon, category) VALUES (:n, :s, :d, :i, :c)'
        );
        $stmt->execute([':n' => $name, ':s' => $slug, ':d' => $description, ':i' => $icon, ':c' => $category]);
        return (int)$this->db->lastInsertId();
    }

    public function update($id, $name, $description, $icon, $category, $isActive) {
        $stmt = $this->db->prepare(
            'UPDATE communities SET name = :n, description = :d, icon = :i, category = :c, is_active = :a WHERE id = :id'
        );
        return $stmt->execute([':n' => $name, ':d' => $description, ':i' => $icon, ':c' => $category, ':a' => $isActive, ':id' => (int)$id]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare('DELETE FROM communities WHERE id = :id');
        return $stmt->execute([':id' => (int)$id]);
    }

    /* ========== CHANNELS ========== */

    public function getChannels($communityId) {
        $stmt = $this->db->prepare(
            'SELECT * FROM community_channels WHERE community_id = :cid ORDER BY sort_order, name'
        );
        $stmt->execute([':cid' => (int)$communityId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createChannel($communityId, $name, $slug, $description = '') {
        $stmt = $this->db->prepare(
            'INSERT INTO community_channels (community_id, name, slug, description) VALUES (:c, :n, :s, :d)'
        );
        $stmt->execute([':c' => (int)$communityId, ':n' => $name, ':s' => $slug, ':d' => $description]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteChannel($id) {
        $stmt = $this->db->prepare('DELETE FROM community_channels WHERE id = :id');
        return $stmt->execute([':id' => (int)$id]);
    }

    public function getPosts($communityId = null, $channelId = null, $limit = 30, $offset = 0) {
        $sql = 'SELECT p.*, CONCAT(u.first_name, " ", u.last_name) AS author_name, u.role AS author_role,
                       c.name AS community_name, ch.name AS channel_name
                FROM community_posts p
                JOIN users u ON u.id = p.user_id
                JOIN communities c ON c.id = p.community_id
                LEFT JOIN community_channels ch ON ch.id = p.channel_id
                WHERE p.is_deleted = 0';
        $params = [];
        if ($communityId) { $sql .= ' AND p.community_id = :cid'; $params[':cid'] = (int)$communityId; }
        if ($channelId) { $sql .= ' AND p.channel_id = :chid'; $params[':chid'] = (int)$channelId; }
        $sql .= ' ORDER BY p.is_pinned DESC, p.created_at DESC LIMIT ' . (int)$offset . ', ' . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPost($postId) {
        $stmt = $this->db->prepare(
            'SELECT p.*, CONCAT(u.first_name, " ", u.last_name) AS author_name, u.role AS author_role,
                    c.name AS community_name, ch.name AS channel_name
             FROM community_posts p JOIN users u ON u.id = p.user_id
             JOIN communities c ON c.id = p.community_id
             LEFT JOIN community_channels ch ON ch.id = p.channel_id
             WHERE p.id = :id AND p.is_deleted = 0'
        );
        $stmt->execute([':id' => (int)$postId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createPost($communityId, $channelId, $userId, $title, $content) {
        $stmt = $this->db->prepare(
            'INSERT INTO community_posts (community_id, channel_id, user_id, title, content) VALUES (:c, :ch, :u, :t, :co)'
        );
        $stmt->execute([':c' => (int)$communityId, ':ch' => $channelId ? (int)$channelId : null, ':u' => (int)$userId, ':t' => $title, ':co' => $content]);
        return (int)$this->db->lastInsertId();
    }

    public function deletePost($postId) {
        $stmt = $this->db->prepare('UPDATE community_posts SET is_deleted = 1 WHERE id = :id');
        return $stmt->execute([':id' => (int)$postId]);
    }

    public function pinPost($postId, $pinned) {
        $stmt = $this->db->prepare('UPDATE community_posts SET is_pinned = :p WHERE id = :id');
        return $stmt->execute([':p' => $pinned ? 1 : 0, ':id' => (int)$postId]);
    }

    public function totalCount() {
        return (int)$this->db->query('SELECT COUNT(*) FROM community_posts WHERE is_deleted = 0')->fetchColumn();
    }

    public function getComments($postId) {
        $stmt = $this->db->prepare(
            'SELECT cm.*, CONCAT(u.first_name, " ", u.last_name) AS author_name, u.role AS author_role
             FROM community_comments cm JOIN users u ON u.id = cm.user_id
             WHERE cm.post_id = :pid AND cm.is_deleted = 0 ORDER BY cm.created_at ASC'
        );
        $stmt->execute([':pid' => (int)$postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addComment($postId, $userId, $content, $parentCommentId = null) {
        // Validate parent comment belongs to the same post (prevents cross-post replies)
        if ($parentCommentId !== null) {
            $chk = $this->db->prepare('SELECT id FROM community_comments WHERE id = :pc AND post_id = :p AND is_deleted = 0');
            $chk->execute([':pc' => (int)$parentCommentId, ':p' => (int)$postId]);
            if (!$chk->fetch()) {
                $parentCommentId = null; // fall back to top-level comment
            }
        }

        $stmt = $this->db->prepare(
            'INSERT INTO community_comments (post_id, parent_comment_id, user_id, content) VALUES (:p, :pc, :u, :c)'
        );
        $stmt->execute([
            ':p' => (int)$postId,
            ':pc' => $parentCommentId !== null ? (int)$parentCommentId : null,
            ':u' => (int)$userId,
            ':c' => $content
        ]);
        // Capture the new comment id BEFORE the UPDATE query below — on this
        // PDO/mysqlnd setup a native query() between INSERT and lastInsertId()
        // resets mysql_insert_id and makes lastInsertId() return 0.
        $newId = (int)$this->db->lastInsertId();
        $this->db->query('UPDATE community_posts SET comments_count = comments_count + 1 WHERE id = ' . (int)$postId);
        return $newId;
    }

    public function toggleLike($postId, $userId) {
        $stmt = $this->db->prepare('SELECT id FROM community_likes WHERE post_id = :p AND user_id = :u');
        $stmt->execute([':p' => (int)$postId, ':u' => (int)$userId]);
        if ($stmt->fetch()) {
            $del = $this->db->prepare('DELETE FROM community_likes WHERE post_id = :p AND user_id = :u');
            $del->execute([':p' => (int)$postId, ':u' => (int)$userId]);
            $this->db->query('UPDATE community_posts SET likes_count = GREATEST(likes_count - 1, 0) WHERE id = ' . (int)$postId);
            return ['liked' => false];
        } else {
            $ins = $this->db->prepare('INSERT IGNORE INTO community_likes (post_id, user_id) VALUES (:p, :u)');
            $ins->execute([':p' => (int)$postId, ':u' => (int)$userId]);
            $this->db->query('UPDATE community_posts SET likes_count = likes_count + 1 WHERE id = ' . (int)$postId);
            return ['liked' => true];
        }
    }

    public function reportPost($postId, $userId, $reason) {
        $stmt = $this->db->prepare('INSERT INTO community_reports (post_id, reported_by, reason) VALUES (:p, :u, :r)');
        $stmt->execute([':p' => (int)$postId, ':u' => (int)$userId, ':r' => $reason]);
        return (int)$this->db->lastInsertId();
    }

    public function getReports($status = 'pending') {
        $stmt = $this->db->prepare(
            'SELECT r.*, p.title AS post_title, p.content AS post_content,
                    CONCAT(u.first_name, " ", u.last_name) AS reporter_name
             FROM community_reports r
             LEFT JOIN community_posts p ON p.id = r.post_id
             JOIN users u ON u.id = r.reported_by
             WHERE r.status = :s ORDER BY r.created_at DESC'
        );
        $stmt->execute([':s' => $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateReportStatus($reportId, $status, $reviewedBy) {
        $stmt = $this->db->prepare('UPDATE community_reports SET status = :s, reviewed_by = :r WHERE id = :id');
        return $stmt->execute([':s' => $status, ':r' => (int)$reviewedBy, ':id' => (int)$reportId]);
    }

    public function totalReports($status = 'pending') {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM community_reports WHERE status = :s');
        $stmt->execute([':s' => $status]);
        return (int)$stmt->fetchColumn();
    }

    public function suspendUser($userId) {
        $stmt = $this->db->prepare('UPDATE users SET is_active = 0 WHERE id = :id');
        return $stmt->execute([':id' => (int)$userId]);
    }
}
