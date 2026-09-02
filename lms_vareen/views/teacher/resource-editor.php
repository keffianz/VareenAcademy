<?php
/**
 * Teacher Resource Editor - upload/delete resources for a lesson
 */

require_once 'src/classes/Lesson.php';
require_once 'src/classes/Resource.php';
require_once 'src/classes/Module.php';
require_once 'src/classes/Course.php';

requireRoles(['teacher', 'admin']);

$lesson_id = (int)($_GET['lesson_id'] ?? 0);
if (!$lesson_id) {
    header('Location: ' . BASE_URL . '?page=teacher-dashboard');
    exit;
}

$lesson = new Lesson();
$resource = new Resource();

$lesson_data = $lesson->getLessonById($lesson_id);
if (!$lesson_data) {
    http_response_code(404);
    exit('Lesson not found');
}

// Ownership check for teacher
if (getCurrentUserRole() === 'teacher') {
    $course = new Course();
    $course_data = $course->getCourseById((int)$lesson_data['course_id']);
    if (!$course_data || (int)($course_data['teacher_id'] ?? 0) !== (int)getCurrentUserId()) {
        http_response_code(403);
        exit('Access denied');
    }
}

$resources = $resource->getResourcesByLesson($lesson_id);
?>

<div class="container">
    <div class="page-header">
        <h1>Manage Resources</h1>
        <p>Lesson: <?php echo htmlspecialchars($lesson_data['title'] ?? ''); ?></p>
    </div>

    <div class="card">
        <h2>Upload Resource</h2>
        <form id="resourceUploadForm" enctype="multipart/form-data">
            <input type="hidden" name="lesson_id" value="<?php echo (int)$lesson_id; ?>" />

            <label>Title</label>
            <input type="text" name="title" required />

            <label>File</label>
            <input type="file" name="resource_file" required />

            <button type="submit" class="btn btn-primary">Upload</button>
        </form>
    </div>

    <div class="card">
        <h2>Existing Resources</h2>
        <?php if (empty($resources)): ?>
            <p>No resources yet.</p>
        <?php else: ?>
            <div class="resources-list">
                <?php foreach ($resources as $r): ?>
                    <div class="resource-item">
                        <div>
                            <strong><?php echo htmlspecialchars($r['title']); ?></strong>
                            <div class="muted">Type: <?php echo htmlspecialchars($r['file_type']); ?></div>
                        </div>
                        <div class="resource-actions">
                            <a class="btn btn-outline" href="<?php echo htmlspecialchars($r['file_path']); ?>" target="_blank">Open</a>
                            <button class="btn btn-danger" onclick="deleteResource(<?php echo (int)$r['id']; ?>)">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.getElementById('resourceUploadForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const fd = new FormData(form);

        try {
            const res = await fetch('<?php echo appBasePath(); ?>/src/api/resources.php?action=upload', {
                method: 'POST',
                body: fd
            });
            const data = await res.json();
            if (data.success) location.reload();
            else showToast(data.message || 'Upload failed', 'error');
        } catch (err) {
            showToast('Upload failed', 'error');
        }
    });

    function deleteResource(resourceId) {
        if (!confirm('Delete this resource?')) return;
        fetch('<?php echo appBasePath(); ?>/src/api/resources.php?action=delete', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ resource_id: resourceId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
            else showToast(data.message || 'Delete failed', 'error');
        })
        .catch(() => showToast('Delete failed', 'error'));
    }
</script>

<style>
    .container{padding:30px 15px;max-width:1000px;margin:0 auto;}
    .page-header h1{margin:0 0 10px 0;font-size:28px;}
    .page-header p{margin:0 0 20px 0;color:#666;}
    .card{background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:20px;margin-bottom:20px;}
    label{display:block;margin-top:12px;font-size:14px;color:#333;}
    input[type="text"],textarea{width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;margin-top:6px;}
    input[type="file"]{margin-top:8px;}
    .btn{display:inline-block;border-radius:8px;padding:10px 14px;border:1px solid transparent;cursor:pointer;text-decoration:none;}
    .btn-primary{background:#667eea;color:#fff;}
    .btn-outline{background:transparent;border-color:#667eea;color:#667eea;}
    .btn-danger{background:#dc3545;color:#fff;}
    .resources-list{display:flex;flex-direction:column;gap:10px;}
    .resource-item{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border:1px solid #eee;border-radius:10px;}
    .muted{color:#777;font-size:13px;}
    .resource-actions{display:flex;gap:10px;}
</style>

