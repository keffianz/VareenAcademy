<?php
/**
 * Course Class - Handle course operations
 */

require_once 'Database.php';

class Course {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Get all courses (with pagination)
     */
    public function getAllCourses($page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;

            $sql = "SELECT c.*, u.first_name, u.last_name 
                    FROM courses c
                    JOIN users u ON c.teacher_id = u.id
                    WHERE c.is_active = 1
                    ORDER BY c.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get total courses count
     */
    public function getTotalCourses() {
        try {
            $sql = "SELECT COUNT(*) as total FROM courses WHERE is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get course by ID
     */
    public function getCourseById($course_id) {
        try {
            $sql = "SELECT c.*, u.first_name, u.last_name 
                    FROM courses c
                    JOIN users u ON c.teacher_id = u.id
                    WHERE c.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $course_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get student's enrolled courses
     */
    public function getEnrolledCourses($student_id) {
        try {
            $sql = "SELECT c.*, u.first_name, u.last_name, e.progress, e.enrolled_at, e.status
                    FROM courses c
                    JOIN users u ON c.teacher_id = u.id
                    JOIN enrollments e ON c.id = e.course_id
                    WHERE e.student_id = :student_id
                    ORDER BY e.enrolled_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':student_id' => $student_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Create new course
     */
    public function createCourse($teacher_id, $title, $description, $category, $price = 0) {
        if (empty($title) || empty($description)) {
            return ['success' => false, 'message' => 'Title and description required'];
        }

        try {
            $sql = "INSERT INTO courses (teacher_id, title, description, category, price)
                    VALUES (:teacher_id, :title, :description, :category, :price)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':teacher_id' => $teacher_id,
                ':title' => $title,
                ':description' => $description,
                ':category' => $category,
                ':price' => $price
            ]);

            $course_id = $this->db->lastInsertId();
            return ['success' => true, 'message' => 'Course created', 'course_id' => $course_id];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create course'];
        }
    }

    /**
     * Update course
     */
    public function updateCourse($course_id, $data) {
        try {
            $updates = [];
            $params = [':id' => $course_id];

            if (isset($data['title'])) {
                $updates[] = "title = :title";
                $params[':title'] = $data['title'];
            }
            if (isset($data['description'])) {
                $updates[] = "description = :description";
                $params[':description'] = $data['description'];
            }
            if (isset($data['category'])) {
                $updates[] = "category = :category";
                $params[':category'] = $data['category'];
            }
            if (isset($data['price'])) {
                $updates[] = "price = :price";
                $params[':price'] = $data['price'];
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'No data to update'];
            }

            $sql = "UPDATE courses SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Course updated'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Update failed'];
        }
    }

    /**
     * Delete course
     */
    public function deleteCourse($course_id) {
        try {
            $sql = "UPDATE courses SET is_active = 0 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $course_id]);

            return ['success' => true, 'message' => 'Course deleted'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Delete failed'];
        }
    }

    /**
     * Get course lessons
     */
    public function getCourseLessons($course_id) {
        try {
            $sql = "SELECT l.*, m.title as module_title
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
     * Get course modules
     */
    public function getCourseModules($course_id) {
        try {
            $sql = "SELECT * FROM modules 
                    WHERE course_id = :course_id AND is_active = 1
                    ORDER BY position";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':course_id' => $course_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Search courses
     */
    public function searchCourses($keyword, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            $search = "%{$keyword}%";

            $sql = "SELECT c.*, u.first_name, u.last_name
                    FROM courses c
                    JOIN users u ON c.teacher_id = u.id
                    WHERE c.is_active = 1 AND (c.title LIKE :search OR c.description LIKE :search)
                    ORDER BY c.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':search', $search);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
