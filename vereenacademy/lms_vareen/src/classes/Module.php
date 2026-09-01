<?php
/**
 * Module Class - Handle course modules
 */

require_once 'Database.php';

class Module {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Create a new module
     */
    public function createModule($course_id, $title, $description = '', $position = 0) {
        if (empty($title)) {
            return ['success' => false, 'message' => 'Title is required'];
        }

        try {
            $sql = "INSERT INTO modules (course_id, title, description, position)
                    VALUES (:course_id, :title, :description, :position)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':course_id' => $course_id,
                ':title' => $title,
                ':description' => $description,
                ':position' => $position
            ]);

            $module_id = $this->db->lastInsertId();
            return ['success' => true, 'message' => 'Module created', 'module_id' => $module_id];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create module'];
        }
    }

    /**
     * Get module by ID with lessons
     */
    public function getModuleWithLessons($module_id) {
        try {
            $sql = "SELECT * FROM modules WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $module_id]);
            $module = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($module) {
                // Get lessons in this module
                $sql = "SELECT * FROM lessons WHERE module_id = :module_id AND is_active = 1 ORDER BY position";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':module_id' => $module_id]);
                $module['lessons'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $module;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get all modules for a course
     */
    public function getModulesByCourse($course_id) {
        try {
            $sql = "SELECT * FROM modules WHERE course_id = :course_id AND is_active = 1 ORDER BY position";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':course_id' => $course_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Update module
     */
    public function updateModule($module_id, $data) {
        try {
            $updates = [];
            $params = [':id' => $module_id];

            if (isset($data['title'])) {
                $updates[] = "title = :title";
                $params[':title'] = $data['title'];
            }
            if (isset($data['description'])) {
                $updates[] = "description = :description";
                $params[':description'] = $data['description'];
            }
            if (isset($data['position'])) {
                $updates[] = "position = :position";
                $params[':position'] = $data['position'];
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'No data to update'];
            }

            $sql = "UPDATE modules SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Module updated'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Update failed'];
        }
    }

    /**
     * Delete module
     */
    public function deleteModule($module_id) {
        try {
            $sql = "UPDATE modules SET is_active = 0 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $module_id]);

            return ['success' => true, 'message' => 'Module deleted'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Delete failed'];
        }
    }

    /**
     * Get module progress for student
     */
    public function getModuleProgress($module_id, $student_id) {
        try {
            $sql = "SELECT 
                    COUNT(*) as total_lessons,
                    SUM(CASE WHEN lp.is_completed = 1 THEN 1 ELSE 0 END) as completed_lessons
                    FROM lessons l
                    LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.student_id = :student_id
                    WHERE l.module_id = :module_id AND l.is_active = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':module_id' => $module_id,
                ':student_id' => $student_id
            ]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['total_lessons' => 0, 'completed_lessons' => 0];
        }
    }
}
