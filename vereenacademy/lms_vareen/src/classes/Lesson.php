<?php
/**
 * Lesson Class - Handle course lessons
 */

require_once 'Database.php';

class Lesson {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Create a new lesson
     */
    public function createLesson($module_id, $course_id, $title, $description = '', $video_url = '', $video_duration = 0, $position = 0) {
        if (empty($title) || !$module_id || !$course_id) {
            return ['success' => false, 'message' => 'Required fields missing'];
        }

        try {
            $sql = "INSERT INTO lessons (module_id, course_id, title, description, video_url, video_duration, position)
                    VALUES (:module_id, :course_id, :title, :description, :video_url, :video_duration, :position)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':module_id' => $module_id,
                ':course_id' => $course_id,
                ':title' => $title,
                ':description' => $description,
                ':video_url' => $video_url,
                ':video_duration' => $video_duration,
                ':position' => $position
            ]);

            $lesson_id = $this->db->lastInsertId();
            return ['success' => true, 'message' => 'Lesson created', 'lesson_id' => $lesson_id];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create lesson'];
        }
    }

    /**
     * Get lesson by ID
     */
    public function getLessonById($lesson_id, $student_id = null) {
        try {
            $sql = "SELECT l.*, m.title as module_title, c.title as course_title
                    FROM lessons l
                    JOIN modules m ON l.module_id = m.id
                    JOIN courses c ON l.course_id = c.id
                    WHERE l.id = :id AND l.is_active = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $lesson_id]);
            $lesson = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($lesson && $student_id) {
                // Get student's progress on this lesson
                $sql = "SELECT * FROM lesson_progress WHERE lesson_id = :lesson_id AND student_id = :student_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':lesson_id' => $lesson_id,
                    ':student_id' => $student_id
                ]);
                $lesson['progress'] = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return $lesson;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get lessons by module
     */
    public function getLessonsByModule($module_id) {
        try {
            $sql = "SELECT * FROM lessons WHERE module_id = :module_id AND is_active = 1 ORDER BY position";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':module_id' => $module_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get lessons by course
     */
    public function getLessonsByCourse($course_id) {
        try {
            $sql = "SELECT l.*, m.id as module_id, m.title as module_title
                    FROM lessons l
                    JOIN modules m ON l.module_id = m.id
                    WHERE l.course_id = :course_id AND l.is_active = 1
                    ORDER BY m.position, l.position";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':course_id' => $course_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get next lesson
     */
    public function getNextLesson($lesson_id, $course_id) {
        try {
            $sql = "SELECT * FROM lessons 
                    WHERE course_id = :course_id AND is_active = 1 AND position > 
                    (SELECT position FROM lessons WHERE id = :lesson_id)
                    ORDER BY position ASC LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':course_id' => $course_id,
                ':lesson_id' => $lesson_id
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get previous lesson
     */
    public function getPreviousLesson($lesson_id, $course_id) {
        try {
            $sql = "SELECT * FROM lessons 
                    WHERE course_id = :course_id AND is_active = 1 AND position < 
                    (SELECT position FROM lessons WHERE id = :lesson_id)
                    ORDER BY position DESC LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':course_id' => $course_id,
                ':lesson_id' => $lesson_id
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Update lesson
     */
    public function updateLesson($lesson_id, $data) {
        try {
            $updates = [];
            $params = [':id' => $lesson_id];

            if (isset($data['title'])) {
                $updates[] = "title = :title";
                $params[':title'] = $data['title'];
            }
            if (isset($data['description'])) {
                $updates[] = "description = :description";
                $params[':description'] = $data['description'];
            }
            if (isset($data['video_url'])) {
                $updates[] = "video_url = :video_url";
                $params[':video_url'] = $data['video_url'];
            }
            if (isset($data['video_duration'])) {
                $updates[] = "video_duration = :video_duration";
                $params[':video_duration'] = $data['video_duration'];
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'No data to update'];
            }

            $sql = "UPDATE lessons SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Lesson updated'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Update failed'];
        }
    }

    /**
     * Delete lesson
     */
    public function deleteLesson($lesson_id) {
        try {
            $sql = "UPDATE lessons SET is_active = 0 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $lesson_id]);

            return ['success' => true, 'message' => 'Lesson deleted'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Delete failed'];
        }
    }

    /**
     * Format video duration
     */
    public static function formatDuration($seconds) {
        if (!$seconds) return '0:00';
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }
        return sprintf('%d:%02d', $minutes, $secs);
    }
}
