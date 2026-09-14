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

$userId = getCurrentUserId();
$role = getCurrentUserRole();
$teacherCourses = [];
$courseLessons = [];

if (!$lesson_id) {
    // No lesson selected — show a picker instead of bouncing to the dashboard.
    $db = (new Database())->connect();
    $allCourses = $db->query('SELECT id, title, teacher_id FROM courses WHERE is_active = 1 ORDER BY title LIMIT 500')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allCourses as $c) {
        if ($role === 'admin' || (int)($c['teacher_id'] ?? 0) === (int)$userId) {
            $teacherCourses[] = $c;
        }
    }
    $lessonModel = new Lesson();
    foreach ($teacherCourses as &$c) {
        $c['lessons'] = $lessonModel->getLessonsByCourse((int)$c['id']) ?: [];
    }
    unset($c);
    $courseLessons = $teacherCourses;
}

$lesson = new Lesson();
$resource = new Resource();

$lesson_data = [];
$resources = [];
if ($lesson_id) {
    $lesson_data = $lesson->getLessonById($lesson_id);
    if (!$lesson_data) {
        http_response_code(404);
        exit('Lesson not found');
    }

    // Ownership check for teacher
    if ($role === 'teacher') {
        $course = new Course();
        $course_data = $course->getCourseById((int)$lesson_data['course_id']);
        if (!$course_data || (int)($course_data['teacher_id'] ?? 0) !== (int)getCurrentUserId()) {
            http_response_code(403);
            exit('Access denied');
        }
    }

    $resources = $resource->getResourcesByLesson($lesson_id);
}
?>

<div class="dashboard-wrapper">
    <?php $teacher_active = 'lessons'; include __DIR__ . '/_sidebar.php'; ?>
    <main class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" type="button"><i class="fas fa-bars"></i></button>
            <div class="topbar-title">
                <h1><?php echo $lesson_id ? 'Manage Resources' : 'Resources — Select a Lesson'; ?></h1>
                <p><?php echo $lesson_id ? ('Lesson: ' . htmlspecialchars($lesson_data['title'] ?? '')) : 'Choose a lesson below to manage its resources.'; ?></p>
            </div>
            <button class="btn-logout" id="teacherLogoutBtnTop" type="button"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <div class="dashboard-section">
            <div class="container">

    <?php if (!$lesson_id): ?>
        <?php if (empty($courseLessons)): ?>
            <div class="card">
                <h2>No Courses Available</h2>
                <p>You do not have any assigned courses with lessons yet. Please contact an administrator to assign you a course before managing resources.</p>
                <a class="btn btn-primary" href="<?php echo appBasePath(); ?>/index.php?page=teacher-courses">Go to My Courses</a>
            </div>
        <?php else: ?>
            <?php foreach ($courseLessons as $c): ?>
                <div class="card">
                    <h2><?php echo htmlspecialchars($c['title']); ?></h2>
                    <?php if (empty($c['lessons'])): ?>
                        <p class="muted">No lessons yet in this course.</p>
                    <?php else: ?>
                        <div class="resources-list">
                            <?php foreach ($c['lessons'] as $l): ?>
                                <div class="resource-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($l['title']); ?></strong>
                                        <div class="muted">Manage file resources for this lesson</div>
                                    </div>
                                    <div class="resource-actions">
                                        <a class="btn btn-outline" href="<?php echo appBasePath(); ?>/index.php?page=teacher-resource-editor&lesson_id=<?php echo (int)$l['id']; ?>">Manage Resources</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php else: ?>

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
                            <a class="btn btn-outline" href="<?php echo appBasePath(); ?>/src/api/download.php?type=resource&id=<?php echo (int)$r['id']; ?>" target="_blank">Open</a>
                            <button class="btn btn-danger" onclick="deleteResource(<?php echo (int)$r['id']; ?>)">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

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
            </div>
        </div>
    </main>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('teacherSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>

