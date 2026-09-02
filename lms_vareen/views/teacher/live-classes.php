<?php
/**
 * Teacher Live Classes Management
 */

requireRoles(['teacher', 'admin']);

require_once 'src/classes/Course.php';
require_once 'src/classes/Module.php';
require_once 'src/classes/Database.php';

$db = (new Database())->connect();
$userId = getCurrentUserId();
$role = getCurrentUserRole();

$teacherCourses = [];

// Select teacher courses
$stmt = $db->prepare("SELECT * FROM courses WHERE is_active = 1 ORDER BY created_at DESC");
$stmt->execute();
$allCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($allCourses as $c) {
    if ($role === 'admin' || (int)($c['teacher_id'] ?? 0) === (int)$userId) {
        $teacherCourses[] = $c;
    }
}

// Fetch live classes for those courses
$courseIds = array_map(fn($c) => (int)$c['id'], $teacherCourses);
$liveClasses = [];

if (!empty($courseIds)) {
    $in = implode(',', array_fill(0, count($courseIds), '?'));
    $sql = "SELECT lc.*, c.title as course_title
            FROM live_classes lc
            JOIN courses c ON lc.course_id = c.id
            WHERE lc.course_id IN ($in)
            ORDER BY lc.scheduled_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($courseIds);
    $liveClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container">
    <div class="page-header">
        <h1>Live Class Management</h1>
        <p>Create, update and manage your scheduled live classes.</p>
    </div>

    <div class="card">
        <h2>Create Live Class</h2>
        <form id="liveClassCreateForm">
            <label>Course</label>
            <select name="course_id" required>
                <?php foreach ($teacherCourses as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['title']); ?></option>
                <?php endforeach; ?>
            </select>

            <label>Title</label>
            <input type="text" name="title" required />

            <label>Description</label>
            <textarea name="description"></textarea>

            <label>Scheduled At (YYYY-MM-DD HH:MM:SS)</label>
            <input type="text" name="scheduled_at" placeholder="2026-06-02 14:30:00" required />

            <label>Meeting Platform</label>
            <input type="text" name="meeting_platform" placeholder="zoom/meet" />

            <label>Meeting URL</label>
            <input type="text" name="meeting_url" required />

            <label>Duration Minutes</label>
            <input type="number" name="duration_minutes" value="60" />

            <label>Recording URL (optional)</label>
            <input type="text" name="recording_url" />

            <button type="submit" class="btn btn-primary">Create</button>
        </form>
    </div>

    <div class="card">
        <h2>Your Live Classes</h2>
        <?php if (empty($liveClasses)): ?>
            <p>No live classes yet.</p>
        <?php else: ?>
            <div class="live-classes-list">
                <?php foreach ($liveClasses as $lc): ?>
                    <div class="live-class-item" data-id="<?php echo (int)$lc['id']; ?>">
                        <div>
                            <strong><?php echo htmlspecialchars($lc['title']); ?></strong>
                            <div class="muted"><?php echo htmlspecialchars($lc['course_title']); ?></div>
                            <div class="muted">Scheduled: <?php echo htmlspecialchars($lc['scheduled_at']); ?></div>
                            <div class="muted">Status: <?php echo htmlspecialchars($lc['status']); ?></div>
                        </div>
                        <div class="actions">
                            <button class="btn btn-outline" onclick="loadEdit(<?php echo (int)$lc['id']; ?>)">Edit</button>
                            <button class="btn btn-danger" onclick="deleteLive(<?php echo (int)$lc['id']; ?>)">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function formToPayload(form) {
        const fd = new FormData(form);
        const payload = {};
        for (const [k,v] of fd.entries()) payload[k] = v;
        return payload;
    }

    document.getElementById('liveClassCreateForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const payload = formToPayload(form);

        const res = await fetch('<?php echo appBasePath(); ?>/src/api/live_classes.php?action=teacher_create', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams(payload)
        });

        const data = await res.json();
        if (data.success) location.reload();
        else showToast(data.message || 'Create failed', 'error');
    });

    async function deleteLive(id) {
        if (!confirm('Delete this live class?')) return;
        const res = await fetch('<?php echo appBasePath(); ?>/src/api/live_classes.php?action=teacher_delete', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ live_class_id: id })
        });
        const data = await res.json();
        if (data.success) location.reload();
        else showToast(data.message || 'Delete failed', 'error');
    }

    // Simple: edit not fully wired in this MVP UI; can be added next.
    function loadEdit(id) {
        showToast('Edit UI not implemented in this MVP live-class screen yet.', 'info');
    }
</script>

<style>
.container{padding:30px 15px;max-width:1000px;margin:0 auto;}
.page-header h1{margin:0 0 10px 0;font-size:28px;}
.page-header p{margin:0 0 20px 0;color:#666;}
.card{background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:20px;margin-bottom:20px;}
label{display:block;margin-top:12px;font-size:14px;color:#333;}
input[type="text"],input[type="number"],textarea,select{width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;margin-top:6px;}
textarea{min-height:90px;}
.btn{display:inline-block;border-radius:8px;padding:10px 14px;border:1px solid transparent;cursor:pointer;text-decoration:none;}
.btn-primary{background:#667eea;color:#fff;}
.btn-outline{background:transparent;border-color:#667eea;color:#667eea;}
.btn-danger{background:#dc3545;color:#fff;}
.live-classes-list{display:flex;flex-direction:column;gap:10px;}
.live-class-item{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border:1px solid #eee;border-radius:10px;}
.muted{color:#777;font-size:13px;margin-top:4px;}
.actions{display:flex;gap:10px;}
</style>

