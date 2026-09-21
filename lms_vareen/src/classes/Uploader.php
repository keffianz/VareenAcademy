<?php
/**
 * Uploader — centralized, secure file-storage abstraction for VAREEN Academy.
 * Phase 4 — Storage System. Goals:
 *  - Absolute base path from __DIR__ (never relative CWD — fixed Resource.php bug).
 *  - Organized subfolders: assets/uploads/{type}/{YYYY}/{MM}/{owner}/
 *  - Collision-proof filenames: random_bytes hex + sanitized slug.
 *  - Strict extension + MIME whitelist (defence in depth); per-type max sizes.
 *  - Returns DB-storable path relative to lms_vareen/ root (download.php-resolvable).
 *  - Single getBasePath()/resolveStoredPath() seam for future cloud migration.
 */
class Uploader
{
    private const TYPE_RULES = [
        'video'    => ['ext' => ['mp4','webm','mov','avi'],
                       'mime' => ['video/mp4','video/webm','video/quicktime','video/x-msvideo','application/octet-stream'],
                       'max'  => 500 * 1024 * 1024],
        'document' => ['ext' => ['pdf','doc','docx','ppt','pptx','xls','xlsx','zip','txt'],
                       'mime' => ['application/pdf','application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/zip','text/plain','application/octet-stream'],
                       'max'  => 50 * 1024 * 1024],
        'image'    => ['ext' => ['jpg','jpeg','png','gif','webp'],
                       'mime' => ['image/jpeg','image/png','image/gif','image/webp',
                        'image/x-icon','image/apng','application/octet-stream'],
                       'max'  => 10 * 1024 * 1024],
    ];

    private const TYPE_DIR = ['video' => 'videos', 'document' => 'resources', 'image' => 'images'];

    private string $absoluteBase;   // /abs/lms_vareen/assets/uploads
    private string $relativeBase;   // assets/uploads (stored in DB)

    public function __construct()
    {
        // __DIR__ = lms_vareen/src/classes → ../../assets/uploads
        // NOTE: realpath() result must be captured in a local var first — assigning
        // false directly to this typed string property coerces it to '' (weak mode).
        $real = realpath(__DIR__ . '/../../assets/uploads');
        if ($real === false) {
            $real = __DIR__ . '/../../assets/uploads';
            if (!is_dir($real)) {
                @mkdir($real, 0755, true);
                $canonical = realpath($real);
                if ($canonical !== false) {
                    $real = $canonical;
                }
            }
        }
        $this->absoluteBase = $real;
        $this->relativeBase = 'assets/uploads';
    }

    /** Absolute filesystem base for uploads (cloud-migration seam). */
    public function getBasePath(): string
    {
        return $this->absoluteBase;
    }

    /**
     * Upload a file from a $_FILES entry.
     * @return array{success:bool,message:string,path:string,filename:string,mime:string,size:int,ext:string}
     */
    public function upload(array $file, string $type, int $ownerId = 0): array
    {
        if (!isset(self::TYPE_RULES[$type])) {
            return ['success' => false, 'message' => 'Unsupported upload type', 'path' => '', 'filename' => '', 'mime' => '', 'size' => 0, 'ext' => ''];
        }
        $rule = self::TYPE_RULES[$type];

        if (!(empty($file['error']) || $file['error'] === UPLOAD_ERR_OK)) {
            return $this->fail($this->uploadErrorMessage((int)$file['error']));
        }
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return $this->fail('No file uploaded or invalid upload');
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            return $this->fail('Empty file');
        }
        if ($size > $rule['max']) {
            return $this->fail('File too large (max ' . self::humanSize($rule['max']) . ')');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $rule['ext'], true)) {
            return $this->fail('File type not allowed');
        }

        $mime = $this->sniffMime($file['tmp_name']);
        if ($mime !== false && $mime !== 'application/octet-stream' && !in_array($mime, $rule['mime'], true)) {
            return $this->fail('File content does not match its extension');
        }

        $year = (int)date('Y');
        $month = (int)date('m');
        $subDir = self::TYPE_DIR[$type];
        $dirParts = [$this->absoluteBase, $subDir, $year, $month];
        if ($ownerId > 0) {
            $dirParts[] = $ownerId;
        }
        $targetDir = implode(DIRECTORY_SEPARATOR, $dirParts);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $slug = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $slug = mb_substr($slug, 0, 60);
        $filename = $slug . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $absPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

        $relPath = $this->relativeBase . '/' . $subDir . '/' . $year . '/' . $month;
        if ($ownerId > 0) {
            $relPath .= '/' . $ownerId;
        }
        $relPath .= '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $absPath)) {
            return $this->fail('Failed to move uploaded file');
        }

        return [
            'success'   => true,
            'message'   => 'File uploaded successfully',
            'path'      => $relPath,
            'filename'  => $filename,
            'original'  => $file['name'],
            'mime'      => $mime ?: 'application/octet-stream',
            'size'      => $size,
            'ext'       => $ext,
        ];
    }

    
    private function sniffMime(string $tmpPath): ?string
    {
        if (class_exists('finfo')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($f, $tmpPath);
            finfo_close($f);
            return $mime ?: false;
        }
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($tmpPath);
            return $mime ?: false;
        }
        return false;
    }

        /**
     * Normalize a stored path to base-relative form.
     * Accepts BOTH stored forms:
     *   - DB-relative:   'assets/uploads/resources/2026/9/file.pdf' (what upload() returns)
     *   - base-relative: 'resources/2026/9/file.pdf'
     * Rejects absolute paths, drive letters and traversal attempts. Returns null when unsafe.
     */
    private function toBaseRelative(string $relPath): ?string
    {
        $p = str_replace('\\', '/', ltrim($relPath, '/\\'));
        $prefix = $this->relativeBase . '/'; // 'assets/uploads/'
        if (strpos($p, $prefix) === 0) {
            $p = substr($p, strlen($prefix));
        }
        if ($p === '' || preg_match('#^[a-zA-Z]:#', $p) || strpos($p, '..') !== false) {
            return null;
        }
        return $p;
    }

    /**
     * Validate a stored path for a given type; return abs path or false.
     * Accepts both DB-relative ('assets/uploads/...') and base-relative forms.
     */
    public function resolveStoredPath(string $relPath, string $type): string|false
    {
        if (!isset(self::TYPE_DIR[$type])) {
            return false;
        }
        $rel = $this->toBaseRelative($relPath);
        if ($rel === null) {
            return false;
        }
        $abs = realpath($this->absoluteBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel));
        $base = rtrim($this->absoluteBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($abs === false || !str_starts_with($abs, $base)) {
            return false;
        }
        if (strpos($rel, self::TYPE_DIR[$type] . '/') !== 0) {
            return false;
        }
        return $abs;
    }

    /**
     * Delete a previously stored file (and clean up now-empty parent dirs).
     * Accepts both DB-relative ('assets/uploads/...') and base-relative forms.
     */
    public function removeStoredFile(string $relPath): bool
    {
        $rel = $this->toBaseRelative($relPath);
        if ($rel === null) {
            return false;
        }
        $abs = realpath($this->absoluteBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel));
        $base = rtrim($this->absoluteBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($abs === false || !is_file($abs) || !str_starts_with($abs, $base)) {
            return false;
        }
        $ok = @unlink($abs);
        $dir = dirname($abs);
        while ($ok && $dir !== $base && str_starts_with($dir, rtrim($this->absoluteBase, DIRECTORY_SEPARATOR))) {
            if (@rmdir($dir)) {
                $dir = dirname($dir);
            } else {
                break;
            }
        }
        return $ok;
    }
public static function humanSize(int $bytes): string
    {
        $bytes = max(0, $bytes);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = $bytes > 0 ? (int)floor(log($bytes, 1024)) : 0;
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function uploadErrorMessage(int $code): string
    {
        $map = [
            UPLOAD_ERR_INI        => 'The uploaded file exceeds the server limit',
            UPLOAD_ERR_FORM       => 'The uploaded file exceeds the form limit',
            UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP     => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION  => 'A server extension blocked the upload',
        ];
        return $map[$code] ?? 'Unknown upload error';
    }
}
