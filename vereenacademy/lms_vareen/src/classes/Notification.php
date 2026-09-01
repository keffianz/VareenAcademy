<?php
/**
 * Notification Class - Handle notifications
 */

require_once 'Database.php';

class Notification {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Create notification
     */
    public function create($user_id, $type, $title, $message, $related_item_id = null, $related_type = null) {
        try {
            $sql = "INSERT INTO notifications (user_id, type, title, message, related_item_id, related_type)
                    VALUES (:user_id, :type, :title, :message, :related_item_id, :related_type)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $user_id,
                ':type' => $type,
                ':title' => $title,
                ':message' => $message,
                ':related_item_id' => $related_item_id,
                ':related_type' => $related_type
            ]);

            return ['success' => true, 'message' => 'Notification created'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed'];
        }
    }

    /**
     * Get unread notifications
     */
    public function getUnread($user_id, $limit = 10) {
        try {
            $sql = "SELECT * FROM notifications 
                    WHERE user_id = :user_id AND is_read = 0
                    ORDER BY created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get all notifications with pagination
     */
    public function getAll($user_id, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;

            $sql = "SELECT * FROM notifications 
                    WHERE user_id = :user_id
                    ORDER BY created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Mark as read
     */
    public function markAsRead($notification_id) {
        try {
            $sql = "UPDATE notifications 
                    SET is_read = 1, read_at = NOW()
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $notification_id]);

            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false];
        }
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead($user_id) {
        try {
            $sql = "UPDATE notifications 
                    SET is_read = 1, read_at = NOW()
                    WHERE user_id = :user_id AND is_read = 0";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $user_id]);

            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false];
        }
    }

    /**
     * Get unread count
     */
    public function getUnreadCount($user_id) {
        try {
            $sql = "SELECT COUNT(*) as count FROM notifications 
                    WHERE user_id = :user_id AND is_read = 0";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $user_id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Delete notification
     */
    public function delete($notification_id) {
        try {
            $sql = "DELETE FROM notifications WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $notification_id]);

            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false];
        }
    }

    /**
     * Notify class members about assignment
     */
    public function notifyAssignmentCreated($assignment_id, $course_id, $assignment_title) {
        try {
            // Get all enrolled students
            $sql = "SELECT DISTINCT e.student_id FROM enrollments e WHERE e.course_id = :course_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':course_id' => $course_id]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Create notification for each student
            foreach ($students as $student) {
                $this->create(
                    $student['student_id'],
                    'assignment',
                    'New Assignment',
                    'A new assignment has been posted: ' . $assignment_title,
                    $assignment_id,
                    'assignment'
                );
            }

            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false];
        }
    }

    /**
     * Notify about upcoming class
     */
    public function notifyUpcomingClass($live_class_id, $course_id, $class_title) {
        try {
            $sql = "SELECT DISTINCT e.student_id FROM enrollments e WHERE e.course_id = :course_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':course_id' => $course_id]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($students as $student) {
                $this->create(
                    $student['student_id'],
                    'class',
                    'Upcoming Live Class',
                    'Your class will start soon: ' . $class_title,
                    $live_class_id,
                    'live_class'
                );
            }

            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false];
        }
    }
}
