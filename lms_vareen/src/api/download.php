<?php
/**
 * Secure file download endpoint.
 *
 * Serves uploaded files (lesson resources, assignment submissions) that are
 * blocked from direct web access by .htaccess. Every request is:
 *   1. Authenticated (any logged-in role),
 *   2. Authorized:
 *        - resource   → students must be enrolled in the course;
 *                       teachers/admins allowed (teacher must own course)
 *        - submission → the owning student, the course's teacher, or an admin
 *   3. Path-checked (realpath must resolve inside assets/uploads/, never
 *      storage/ or uploads/payment_proofs/).
 */

require_once '../classes/Database.php';
require_once '../classes/Enrollment.php';
require_once '../middleware/auth.php';

$user = checkAuth();
$userId = (int) ($user['id'] ?? 0);
$role = $user['role'] ?? '';

$type = $_GET['type'] ?? '';
$id = (int) ($_GET['id'] ?? 0);

if (!in_array($type, ['resource', 'submission'], true) || $id <= 0) {
    http_response_code(400);
    exit('Invalid request');
}

$pdo = (new Database())->connect();
$filePath = null;

if ($type === 'resource') {
    $stmt = $pdo->prepare('SELECT id, course_id, title, file_path, file_type FROM resources WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['file_path'])) {
        http_response_code(404);
        exit('File not found');
    }

    if ($role === 'student') {
        $enrollment = new Enrollment();
        if (!$enrollment->isEnrolled($userId, (int) $row['course_id'])) {
            http_response_code(403);
            exit('Access denied — enroll in the course to download this resource');
        }
    } elseif ($role === 'teacher') {
        $stmt = $pdo->prepare('SELECT teacher_id FROM courses WHERE id = :c');
        $stmt->execute([':c' => (int) $row['course_id']]);
        if ((int) $stmt->fetchColumn() !== $userId) {
            http_response_code(403);
            exit('Access denied');
        }
    }
    $filePath = $row['file_path'];
    $downloadName = $row['title'] ?? basename($filePath);
} else { // submission
    $stmt = $pdo->prepare(
        'SELECT s.id, s.student_id, s.file_path, a.course_id, c.teacher_id
         FROM submissions s
         JOIN assignments a ON a.id = s.assignment_id
         JOIN courses c ON c.id = a.course_id
         WHERE s.id = :id'
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['file_path'])) {
        http_response_code(404);
        exit('File not found');
    }

    $allowed = ($role === 'admin')
        || ((int) $row['student_id'] === $userId)
        || ($role === 'teacher' && (int) $row['teacher_id'] === $userId);
    if (!$allowed) {
        http_response_code(403);
        exit('Access denied');
    }
    $filePath = $row['file_path'];
    $downloadName = basename($filePath);
}

// Path safety: resolve and confine to the allowed upload roots
$roots = [
    realpath(__DIR__ . '/../../assets/uploads/resources'),
    realpath(__DIR__ . '/../../assets/uploads/assignments'),
];
$real = realpath(__DIR__ . '/../../' . ltrim(str_replace('\\', '/', $filePath), '/'));
$inRoot = false;
foreach ($roots as $root) {
    if ($root !== false && $real !== false && strpos($real, $root . DIRECTORY_SEPARATOR) === 0) {
        $inRoot = true;
        break;
    }
}
if (!$inRoot || !is_file($real)) {
    http_response_code(404);
    exit('File not found');
}

// Never serve anything sensitive even if a path accidentally points there
if (strpos($real, 'payment_proofs') !== false || strpos($real, DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR) !== false) {
    http_response_code(403);
    exit('Access denied');
}

$ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
$mimeMap = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'zip'  => 'application/zip',
    'txt'  => 'text/plain',
    'mp4'  => 'video/mp4',
    'mp3'  => 'audio/mpeg',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($real));
header('Content-Disposition: inline; filename="' . rawurlencode($downloadName) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, no-cache');
readfile($real);
exit;
