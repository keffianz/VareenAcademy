<?php
/**
 * Enrollment Class - Handle course enrollments
 */

require_once 'Database.php';

class Enrollment {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Enroll student in course
     */
    public function enrollStudent($student_id, $course_id) {
        // Check if already enrolled
        if ($this->isEnrolled($student_id, $course_id)) {
            return ['success' => false, 'message' => 'Already enrolled in this course'];
        }

        try {
            $sql = "INSERT INTO enrollments (student_id, course_id, status, progress)
                    VALUES (:student_id, :course_id, 'active', 0)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':student_id' => $student_id,
                ':course_id' => $course_id
            ]);

            return ['success' => true, 'message' => 'Enrolled successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Enrollment failed'];
        }
    }

    /**
     * Check if student is enrolled
     */
    public function isEnrolled($student_id, $course_id) {
        try {
            $sql = "SELECT id FROM enrollments 
                    WHERE student_id = :student_id AND course_id = :course_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':student_id' => $student_id,
                ':course_id' => $course_id
            ]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Update progress
     */
    public function updateProgress($student_id, $course_id, $progress) {
        try {
            $sql = "UPDATE enrollments 
                    SET progress = :progress 
                    WHERE student_id = :student_id AND course_id = :course_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':progress' => $progress,
                ':student_id' => $student_id,
                ':course_id' => $course_id
            ]);

            return ['success' => true, 'message' => 'Progress updated'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Update failed'];
        }
    }

    /**
     * Get enrollment details
     */
    public function getEnrollment($student_id, $course_id) {
        try {
            $sql = "SELECT * FROM enrollments 
                    WHERE student_id = :student_id AND course_id = :course_id";
            
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
     * Get student dashboard data
     */
    public function getStudentDashboard($student_id) {
        try {
            $dashboard = [];

            // Enrolled courses with progress
            $sql = "SELECT c.*, u.first_name, u.last_name, e.progress, e.enrolled_at, e.status,
                           (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons,
                           (SELECT COUNT(*) FROM lesson_progress WHERE student_id = :student_id AND course_id = c.id AND is_completed = 1) as completed_lessons
                    FROM courses c
                    JOIN users u ON c.teacher_id = u.id
                    JOIN enrollments e ON c.id = e.course_id
                    WHERE e.student_id = :student_id AND c.is_active = 1
                    ORDER BY e.enrolled_at DESC
                    LIMIT 6";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':student_id' => $student_id]);
            $dashboard['courses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Upcoming live classes (next 7 days)
            $sql = "SELECT lc.*, c.title as course_title
                    FROM live_classes lc
                    JOIN courses c ON lc.course_id = c.id
                    JOIN enrollments e ON c.id = e.course_id
                    WHERE e.student_id = :student_id 
                    AND lc.scheduled_at >= NOW()
                    AND lc.scheduled_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)
                    AND lc.status IN ('scheduled', 'ongoing')
                    ORDER BY lc.scheduled_at ASC
                    LIMIT 5";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':student_id' => $student_id]);
            $dashboard['upcoming_classes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Recent recordings
            $sql = "SELECT lc.*, c.title as course_title
                    FROM live_classes lc
                    JOIN courses c ON lc.course_id = c.id
                    JOIN enrollments e ON c.id = e.course_id
                    WHERE e.student_id = :student_id
                    AND lc.recording_url IS NOT NULL
                    AND lc.status = 'completed'
                    ORDER BY lc.updated_at DESC
                    LIMIT 5";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':student_id' => $student_id]);
            $dashboard['recent_recordings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Pending assignments
            $sql = "SELECT a.*, c.title as course_title,
                           (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id AND student_id = :student_id) as submitted
                    FROM assignments a
                    JOIN courses c ON a.course_id = c.id
                    JOIN enrollments e ON c.id = e.course_id
                    WHERE e.student_id = :student_id
                    AND a.is_active = 1
                    AND (a.due_date > NOW() OR a.due_date IS NULL)
                    ORDER BY a.due_date ASC
                    LIMIT 5";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':student_id' => $student_id]);
            $dashboard['pending_assignments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Unread notifications
            $sql = "SELECT * FROM notifications 
                    WHERE user_id = :student_id AND is_read = 0
                    ORDER BY created_at DESC
                    LIMIT 5";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':student_id' => $student_id]);
            $dashboard['notifications'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Statistics
            $sql = "SELECT 
                    COUNT(DISTINCT e.course_id) as total_courses,
                    (SELECT COUNT(*) FROM assignments a 
                     JOIN enrollments e2 ON a.course_id = e2.course_id 
                     WHERE e2.student_id = :student_id AND a.due_date > NOW()) as pending_assignments,
                    (SELECT COUNT(*) FROM notifications WHERE user_id = :student_id AND is_read = 0) as unread_notifications
                    FROM enrollments e
                    WHERE e.student_id = :student_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':student_id' => $student_id]);
            $dashboard['stats'] = $stmt->fetch(PDO::FETCH_ASSOC);

            return $dashboard;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get course completion percentage
     */
    public function getCourseCompletionPercentage($student_id, $course_id) {
        try {
            $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed
                    FROM lesson_progress
                    WHERE student_id = :student_id AND course_id = :course_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':student_id' => $student_id,
                ':course_id' => $course_id
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['total'] == 0) {
                return 0;
            }
            
            return round(($result['completed'] / $result['total']) * 100, 2);
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Mark course as completed
     */
    public function markCourseCompleted($student_id, $course_id) {
        try {
            $sql = "UPDATE enrollments 
                    SET status = 'completed', completed_at = NOW(), progress = 100
                    WHERE student_id = :student_id AND course_id = :course_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':student_id' => $student_id,
                ':course_id' => $course_id
            ]);

            return ['success' => true, 'message' => 'Course marked as completed'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed'];
        }
    }
}
