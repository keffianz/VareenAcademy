<?php
/**
 * Resource Class - Handle lesson resources (downloadable files)
 */

require_once 'Database.php';

class Resource {
    private $db;
    private $upload_dir = 'assets/uploads/resources/';

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        
        // Ensure upload directory exists
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }

    /**
     * Add resource to lesson
     */
    public function addResource($lesson_id, $title, $file_path, $file_type = '') {
        if (!$lesson_id || !$title || !$file_path) {
            return ['success' => false, 'message' => 'Required fields missing'];
        }

        try {
            $sql = "INSERT INTO resources (lesson_id, title, file_path, file_type)
                    VALUES (:lesson_id, :title, :file_path, :file_type)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':lesson_id' => $lesson_id,
                ':title' => $title,
                ':file_path' => $file_path,
                ':file_type' => $file_type
            ]);

            return ['success' => true, 'message' => 'Resource added', 'resource_id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to add resource'];
        }
    }

    /**
     * Get resources by lesson
     */
    public function getResourcesByLesson($lesson_id) {
        try {
            $sql = "SELECT * FROM resources WHERE lesson_id = :lesson_id ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':lesson_id' => $lesson_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get resources by course
     */
    public function getResourcesByCourse($course_id) {
        try {
            $sql = "SELECT r.* FROM resources r
                    JOIN lessons l ON r.lesson_id = l.id
                    WHERE l.course_id = :course_id
                    ORDER BY r.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':course_id' => $course_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Handle file upload
     */
    public function uploadFile($file, $lesson_id = null) {
        // Allowed file types
        $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'zip'];
        $max_size = 50 * 1024 * 1024; // 50MB

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'No file uploaded'];
        }

        // Check file size
        if ($file['size'] > $max_size) {
            return ['success' => false, 'message' => 'File too large (max 50MB)'];
        }

        // Get file extension
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_types)) {
            return ['success' => false, 'message' => 'File type not allowed'];
        }

        // Generate unique filename
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
        $filepath = $this->upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => true,
                'message' => 'File uploaded',
                'filename' => $filename,
                'filepath' => $filepath,
                'original_name' => $file['name'],
                'file_type' => $file_ext
            ];
        }

        return ['success' => false, 'message' => 'Failed to upload file'];
    }

    /**
     * Delete resource
     */
    public function deleteResource($resource_id) {
        try {
            // Get resource info first
            $sql = "SELECT file_path FROM resources WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $resource_id]);
            $resource = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resource) {
                return ['success' => false, 'message' => 'Resource not found'];
            }

            // Delete file if it exists
            if (file_exists($resource['file_path'])) {
                unlink($resource['file_path']);
            }

            // Delete database record
            $sql = "DELETE FROM resources WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $resource_id]);

            return ['success' => true, 'message' => 'Resource deleted'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Delete failed'];
        }
    }

    /**
     * Get file icon based on type
     */
    public static function getFileIcon($file_type) {
        $icons = [
            'pdf' => 'fa-file-pdf',
            'doc' => 'fa-file-word',
            'docx' => 'fa-file-word',
            'ppt' => 'fa-file-powerpoint',
            'pptx' => 'fa-file-powerpoint',
            'xls' => 'fa-file-excel',
            'xlsx' => 'fa-file-excel',
            'zip' => 'fa-file-zipper',
            'jpg' => 'fa-file-image',
            'jpeg' => 'fa-file-image',
            'png' => 'fa-file-image',
            'gif' => 'fa-file-image'
        ];

        return $icons[$file_type] ?? 'fa-file';
    }

    /**
     * Format file size
     */
    public static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
