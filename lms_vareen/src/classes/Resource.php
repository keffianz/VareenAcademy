<?php
/**
 * Resource Class - Handle lesson resources (downloadable files)
 *
 * Uploads/deletes are delegated to the centralized Uploader (Phase 4 storage
 * abstraction) — organized folders, MIME/extension validation, collision-proof
 * filenames, absolute paths. DB stores paths relative to lms_vareen/ root.
 */

require_once 'Database.php';
require_once __DIR__ . '/Uploader.php';

class Resource {
    private $db;
    private $uploader;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        $this->uploader = new Uploader();
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
     * Handle file upload — delegated to Uploader (document whitelist: PDF, DOC(X),
     * PPT(X), XLS(X), ZIP, TXT + images; 50MB max; organized folders).
     * Return keys kept identical to the legacy implementation so API callers
     * (resources.php) need no changes.
     */
    public function uploadFile($file, $lesson_id = null) {
        $upload = $this->uploader->upload($file, 'document', (int)($lesson_id ?? 0));
        if (!$upload['success']) {
            return ['success' => false, 'message' => $upload['message']];
        }
        return [
            'success'       => true,
            'message'       => 'File uploaded',
            'filename'      => $upload['filename'],
            'filepath'      => $upload['path'],
            'original_name' => $upload['original'],
            'file_type'     => $upload['ext'],
        ];
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

            // Delete file via Uploader (path-validated, cleans empty dirs);
            // legacy rows may hold bare 'filename' — fall back to the old dir.
            $filePath = (string)$resource['file_path'];
            if ($filePath !== '' && strpos($filePath, 'assets/uploads/') !== 0) {
                $filePath = 'assets/uploads/resources/' . ltrim($filePath, '/\\');
            }
            if ($filePath !== '' && $this->uploader->removeStoredFile($filePath) === false && is_file($filePath)) {
                @unlink($filePath); // legacy CWD-relative row
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
