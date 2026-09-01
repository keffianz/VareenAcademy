<?php
/**
 * Teacher Lesson Editor - create/update/delete lessons for a module
 */

require_once 'src/classes/Course.php';
require_once 'src/classes/Lesson.php';
require_once 'src/classes/Module.php';
require_once 'src/classes/Resource.php';

requireRoles(['teacher', 'admin']);

$module_id = (int)($_GET['module_id'] ?? 0);
if (!$module_id) {
    header('Location: ' . BASE_URL . '?page=teacher-dashboard');
    exit;
}

$lesson = new Lesson();
$resource = new Resource();

// Determine course ownership via module
$module = new Module();
$module_data = $module->getModuleWithLessons($module_id);
if (!$module_data) {
    http_response_code(404);
    exit('Module not found');
}

if (getCurrentUserRole() === 'teacher') {
    // Course ownership check
    $course = new Course();
    $course_data = $course->getCourseById((int)$module_data['course_id']);
    if (!$course_data || (int)($course_data['teacher_id'] ?? 0) !== (int)getCurrentUserId()) {
        http_response_code(403);
        exit('Access denied');
    }
}

$lessons = $lesson->getLessonsByModule($module_id);
?>

<div class="container">
    <div class="page-header">
        <h1>Manage Lessons</h1>
        <p>Module: <?php echo htmlspecialchars($module_data['title'] ?? ''); ?></p>
    </div>

    <div class="card">
        <h2>Add Lesson</h2>
        <form id="lessonCreateForm">
            <input type="hidden" name="module_id" value="<?php echo (int)$module_id; ?>" />
            <input type="hidden" name="course_id" value="<?php echo (int)$module_data['course_id']; ?>" />

            <label>Title</label>
            <input type="text" name="title" required />

            <label>Description</label>
            <textarea name="description"></textarea>

            <label>Video URL</label>
            <input type="text" name="video_url" placeholder="https://... or /path/file.mp4" />

            <label>Video Duration (seconds)</label>
            <input type="number" name="video_duration" value="0" />

            <label>Position</label>
            <input type="number" name="position" value="0" />

            <button type="submit" class="btn btn-primary">Create Lesson</button>
        </form>
    </div>

    <div class="card">
        <h2>Existing Lessons</h2>
        <?php if (empty($lessons)): ?>
            <p>No lessons yet.</p>
        <?php else: ?>
            <div class="lessons-list">
                <?php foreach ($lessons as $l): ?>
                    <div class="lesson-item" data-lesson-id="<?php echo (int)$l['id']; ?>">
                        <div>
                            <strong><?php echo htmlspecialchars($l['title']); ?></strong>
                            <div class="muted">Position: <?php echo (int)($l['position'] ?? 0); ?></div>
                        </div>

                        <div class="lesson-actions">
                            <button class="btn btn-outline" onclick="openResourceManager(<?php echo (int)$l['id']; ?>)">Resources</button>
                            <button class="btn btn-danger" onclick="deleteLesson(<?php echo (int)$l['id']; ?>)">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    async function postForm(actionUrl, payload) {
        const res = await fetch(actionUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        return res.json();
    }

    document.getElementById('lessonCreateForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const fd = new FormData(form);

        const payload = {
            module_id: fd.get('module_id'),
            course_id: fd.get('course_id'),
            title: fd.get('title'),
            description: fd.get('description'),
            video_url: fd.get('video_url'),
            video_duration: fd.get('video_duration'),
            position: fd.get('position')
        };

        const data = await postForm('/src/api/lessons.php?action=create', payload);
        if (data.success) location.reload();
        else showToast(data.message || 'Failed', 'error');
    });

    function deleteLesson(lessonId) {
        if (!confirm('Delete this lesson?')) return;
        fetch('/src/api/lessons.php?action=delete', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ lesson_id: lessonId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
            else showToast(data.message || 'Delete failed', 'error');
        })
        .catch(() => showToast('Delete failed', 'error'));
    }

    function openResourceManager(lessonId) {
        window.location.href = '/index.php?page=resource-editor&lesson_id=' + encodeURIComponent(lessonId);
    }
</script>

<style>
    .container{padding:30px 15px;max-width:1000px;margin:0 auto;}
    .page-header h1{margin:0 0 10px 0;font-size:28px;}
    .page-header p{margin:0 0 20px 0;color:#666;}
    .card{background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:20px;margin-bottom:20px;}
    label{display:block;margin-top:12px;font-size:14px;color:#333;}
    input[type="text"],input[type="number"],textarea{width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;margin-top:6px;}
    textarea{min-height:90px;}
    .btn{display:inline-block;border-radius:8px;padding:10px 14px;border:1px solid transparent;cursor:pointer;text-decoration:none;}
    .btn-primary{background:#667eea;color:#fff;}
    .btn-outline{background:transparent;border-color:#667eea;color:#667eea;}
    .btn-danger{background:#dc3545;color:#fff;}
    .lessons-list{display:flex;flex-direction:column;gap:10px;}
    .lesson-item{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border:1px solid #eee;border-radius:10px;}
    .muted{color:#777;font-size:13px;}
    .lesson-actions{display:flex;gap:10px;}
</style>

