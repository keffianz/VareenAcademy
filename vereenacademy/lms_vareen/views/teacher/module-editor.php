<?php
/**
 * Teacher Module Editor - create/update/delete modules for a course
 */

require_once 'src/classes/Course.php';
require_once 'src/classes/Module.php';

// Only teacher/admin
requireRoles(['teacher', 'admin']);

$course_id = (int)($_GET['course_id'] ?? 0);
if (!$course_id) {
    header('Location: ' . BASE_URL . '?page=teacher-dashboard');
    exit;
}

$course = new Course();
$course_data = $course->getCourseById($course_id);
if (!$course_data) {
    header('Location: ' . BASE_URL . '?page=teacher-dashboard');
    exit;
}

// If teacher, ensure ownership
if (getCurrentUserRole() === 'teacher') {
    if ((int)($course_data['teacher_id'] ?? 0) !== (int)getCurrentUserId()) {
        http_response_code(403);
        exit('Access denied');
    }
}

$module = new Module();
$modules = $module->getModulesByCourse($course_id);
?>

<div class="container">
    <div class="page-header">
        <h1>Manage Modules</h1>
        <p>Course: <?php echo htmlspecialchars($course_data['title'] ?? ''); ?></p>
    </div>

    <div class="card">
        <h2>Add Module</h2>
        <form id="moduleCreateForm">
            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>" />

            <label>Title</label>
            <input type="text" name="title" required />

            <label>Description</label>
            <textarea name="description"></textarea>

            <label>Position</label>
            <input type="number" name="position" value="0" />

            <button type="submit" class="btn btn-primary">Create Module</button>
        </form>
    </div>

    <div class="card">
        <h2>Existing Modules</h2>
        <?php if (empty($modules)): ?>
            <p>No modules yet.</p>
        <?php else: ?>
            <div class="modules-list">
                <?php foreach ($modules as $m): ?>
                    <div class="module-item" data-module-id="<?php echo (int)$m['id']; ?>">
                        <div>
                            <strong><?php echo htmlspecialchars($m['title']); ?></strong>
                            <div class="muted">Position: <?php echo (int)($m['position'] ?? 0); ?></div>
                        </div>

                        <div class="module-actions">
                            <button class="btn btn-outline" onclick="openLessonEditor(<?php echo (int)$m['id']; ?>)">Edit Lessons</button>
                            <button class="btn btn-danger" onclick="deleteModule(<?php echo (int)$m['id']; ?>)">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    async function postForm(actionUrl, formData) {
        const res = await fetch(actionUrl, {
            method: 'POST',
            body: new URLSearchParams(formData)
        });
        return res.json();
    }

    document.getElementById('moduleCreateForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        const payload = {
            course_id: formData.get('course_id'),
            title: formData.get('title'),
            description: formData.get('description'),
            position: formData.get('position')
        };

        const data = await postForm('/src/api/modules.php?action=create', payload);
        if (data.success) {
            location.reload();
        } else {
            showToast(data.message || 'Failed to create module', 'error');
        }
    });

    function deleteModule(moduleId) {
        if (!confirm('Delete this module?')) return;
        fetch('/src/api/modules.php?action=delete', {
            method: 'POST',
            body: new URLSearchParams({ module_id: moduleId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
            else showToast(data.message || 'Delete failed', 'error');
        })
        .catch(() => showToast('Delete failed', 'error'));
    }

    function openLessonEditor(moduleId) {
        window.location.href = '/index.php?page=lesson-editor&module_id=' + encodeURIComponent(moduleId);
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
    .modules-list{display:flex;flex-direction:column;gap:10px;}
    .module-item{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border:1px solid #eee;border-radius:10px;}
    .muted{color:#777;font-size:13px;}
    .module-actions{display:flex;gap:10px;}
</style>

