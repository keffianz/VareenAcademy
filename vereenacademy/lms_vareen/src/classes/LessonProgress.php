<?php
/**
 * LessonProgress Class - Track student lesson progress
 */

require_once 'Database.php';

class LessonProgress {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Get or create lesson progress record
     */
    public function getProgress($student_id, $lesson_id) {
        try {
            $sql = "SELECT * FROM lesson_progress WHERE student_id = :student_id AND lesson_id = :lesson_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':student_id' => $student_id,
                ':lesson_id' => $lesson_id
            ]);
            
            $progress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If no progress record, get lesson info to create one
            if (!$progress) {
                // Get lesson course_id
                $sql = "SELECT course_id FROM lessons WHERE id = :lesson_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':lesson_id' => $lesson_id]);
                $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($lesson) {
                    $progress = [
                        'student_id' => $student_id,
                        'lesson_id' => $lesson_id,
                        'course_id' => $lesson['course_id'],
                        'watched_duration' => 0,
                        'is_completed' => 0,
                        'completed_at' => null
                    ];
                }
            }
            
            return $progress;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Record lesson view/watch time
     */
    public function recordWatch($student_id, $lesson_id, $watched_duration) {
        try {
            $lesson = new Lesson();
            $lesson_data = $lesson->getLessonById($lesson_id);
            
            if (!$lesson_data) {
                return ['success' => false, 'message' => 'Lesson not found'];
            }

            // Check if progress record exists
            $sql = "SELECT id FROM lesson_progress WHERE student_id = :student_id AND lesson_id = :lesson_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':student_id' => $student_id,
                ':lesson_id' => $lesson_id
            ]);

            if ($stmt->rowCount() > 0) {
                // Update existing record
                $sql = "UPDATE lesson_progress 
                        SET watched_duration = :watched_duration, last_watched_at = NOW()
                        WHERE student_id = :student_id AND lesson_id = :lesson_id";
            } else {
                // Create new record
                $sql = "INSERT INTO lesson_progress (student_id, lesson_id, course_id, watched_duration)
                        VALUES (:student_id, :lesson_id, :course_id, :watched_duration)";
            }

            $stmt = $this->db->prepare($sql);
            
            $params = [
                ':student_id' => $student_id,
                ':lesson_id' => $lesson_id,
                ':watched_duration' => $watched_duration
            ];

            if (isset($lesson_data['course_id'])) {
                $params[':course_id'] = $lesson_data['course_id'];
            }

            $stmt->execute($params);

            return ['success' => true, 'message' => 'Progress recorded'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to record progress'];
        }
    }

    /**
     * Mark lesson as completed
     */
    public function markCompleted($student_id, $lesson_id) {
        try {
            // Get lesson info
            $lesson = new Lesson();
            $lesson_data = $lesson->getLessonById($lesson_id);
            
            if (!$lesson_data) {
                return ['success' => false, 'message' => 'Lesson not found'];
            }

            // Check if record exists
            $sql = "SELECT id FROM lesson_progress WHERE student_id = :student_id AND lesson_id = :lesson_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':student_id' => $student_id,
                ':lesson_id' => $lesson_id
            ]);

            if ($stmt->rowCount() > 0) {
                // Update existing
                $sql = "UPDATE lesson_progress 
                        SET is_completed = 1, completed_at = NOW(), watched_duration = :duration
                        WHERE student_id = :student_id AND lesson_id = :lesson_id";
            } else {
                // Create new
                $sql = "INSERT INTO lesson_progress (student_id, lesson_id, course_id, is_completed, completed_at, watched_duration)
                        VALUES (:student_id, :lesson_id, :course_id, 1, NOW(), :duration)";
            }

            $stmt = $this->db->prepare($sql);
            $params = [
                ':student_id' => $student_id,
                ':lesson_id' => $lesson_id,
                ':duration' => $lesson_data['video_duration'] ?? 0
            ];

            if (isset($lesson_data['course_id'])) {
                $params[':course_id'] = $lesson_data['course_id'];
            }

            $stmt->execute($params);

            return ['success' => true, 'message' => 'Lesson marked as completed'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to mark as completed'];
        }
    }

    /**
     * Get course progress
     */
    public function getCourseProgress($student_id, $course_id) {
        try {
            $sql = "SELECT 
                    COUNT(*) as total_lessons,
                    SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed_lessons,
                    ROUND((SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as progress_percentage
                    FROM lesson_progress
                    WHERE student_id = :student_id AND course_id = :course_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':student_id' => $student_id,
                ':course_id' => $course_id
            ]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['total_lessons' => 0, 'completed_lessons' => 0, 'progress_percentage' => 0];
        }
    }

    /**
     * Get last watched lesson in course
     */
    public function getLastWatchedLesson($student_id, $course_id) {
        try {
            $sql = "SELECT l.* FROM lesson_progress lp
                    JOIN lessons l ON lp.lesson_id = l.id
                    WHERE lp.student_id = :student_id AND l.course_id = :course_id
                    ORDER BY lp.last_watched_at DESC
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':student_id' => $student_id,
                ':course_id' => $course_id
            ]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get completed lessons in course
     */
    public function getCompletedLessons($student_id, $course_id) {
        try {
            $sql = "SELECT lesson_id FROM lesson_progress 
                    WHERE student_id = :student_id AND course_id = :course_id AND is_completed = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':student_id' => $student_id,
                ':course_id' => $course_id
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }
}
