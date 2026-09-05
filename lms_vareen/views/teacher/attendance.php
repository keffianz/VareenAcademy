<?php
/**
 * Teacher - Attendance
 * Lists the teacher's live classes and lets them take attendance (present/absent)
 * using the live_classes.php API (attendance_view / attendance_mark).
 */
requireRole('teacher');
require_once 'src/classes/Database.php';

$student_id = (int) getCurrentUserId();
$pdo = (new Database())->connect();

// Fetch teacher's live classes
$stmt = $pdo->prepare(
    "SELECT lc.id, lc.title, lc.scheduled_at, lc.status, c.id AS course_id, c.title AS course_title
     FROM live_classes lc
     JOIN courses c ON lc.course_id = c.id
     WHERE lc.teacher_id = :tid
     ORDER BY lc.scheduled_at DESC"
);
$stmt->execute([':tid' => $student_id]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$apiBase = appBasePath() . '/src/api/live_classes.php';
?>
<div class="dashboard-wrapper">
    <div class="dashboard-content" style="margin-left:0;">
        <div class="dashboard-topbar">
            <div class="topbar-title">
                <h1>Attendance</h1>
                <p>Mark which students attended each live class</p>
            </div>
        </div>
        <section class="dashboard-section">
            <?php if (empty($classes)): ?>
                <div class="empty-state">
                    <p>You have no live classes scheduled.</p>
                    <p style="font-size:13px;color:#999;">Create a live class from your course dashboard to take attendance.</p>
                </div>
            <?php else: ?>
                <?php foreach ($classes as $cls): ?>
                    <div class="attendance-card" data-class-id="<?= $cls['id'] ?>">
                        <div class="attendance-header">
                            <h3><?= htmlspecialchars($cls['title']) ?></h3>
                            <span class="class-meta">
                                Course: <?= htmlspecialchars($cls['course_title']) ?> ·
                                Scheduled: <?= $cls['scheduled_at'] ? htmlspecialchars(date('M j, Y g:i a', strtotime($cls['scheduled_at']))) : '—' ?> ·
                                <span class="status-badge status-<?= $cls['status'] ?>"><?= ucfirst($cls['status']) ?></span>
                            </span>
                        </div>
                        <div class="attendance-body">
                            <div class="attendance-loading">Loading roster…</div>
                            <table class="attendance-table" style="display:none;">
                                <thead><tr><th>Student</th><th>Email</th><th>Attended</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
<style>
    .attendance-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.05);margin-bottom:16px}
    .attendance-header h3{margin:0 0 4px;font-size:18px;color:#333}
    .class-meta{font-size:12px;color:#888;margin-bottom:12px}
    .status-badge{padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600}
    .status-scheduled{background:#e8f4fd;color:#4a90d9}
    .status-completed{background:#e6f6ec;color:#2e9e5b}
    .status-cancelled{background:#fdecea;color:#d9534f}
    .attendance-loading{padding:16px;color:#999;font-size:13px}
    .attendance-table{width:100%;border-collapse:collapse;font-size:13px}
    .attendance-table th{text-align:left;padding:8px;color:#888;font-weight:600;border-bottom:2px solid #eee}
    .attendance-table td{padding:8px;border-bottom:1px solid #f0f0f0}
    .attend-check{width:16px;height:16px;cursor:pointer}
    .empty-state{text-align:center;padding:30px;color:#999}
    @media(max-width:480px){.class-meta{flex-direction:column;display:block}}
</style>
<script>
(function () {
    var apiBase = '<?= $apiBase ?>';
    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }
    var cards = document.querySelectorAll('.attendance-card');
    cards.forEach(function (card) {
        var classId = card.dataset.classId;
        var loading = card.querySelector('.attendance-loading');
        var table = card.querySelector('.attendance-table');
        var tbody = table.querySelector('tbody');
        var fd = new FormData();
        fd.append('live_class_id', classId);
        fetch(apiBase + '?action=attendance_view', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrf() },
            body: fd
        }).then(function(r){return r.json();}).then(function(r){
            loading.style.display = 'none';
            if (!r.success || !r.data) { loading.textContent = 'Could not load roster.'; return; }
            var students = r.data.students || [];
            if (!students.length) { loading.textContent = 'No enrolled students for this class.'; return; }
            table.style.display = 'table';
            tbody.innerHTML = students.map(function(s) {
                var attended = s.joined_at ? 'checked' : '';
                return '<tr data-student="' + s.student_id + '">'
                    + '<td>' + (s.first_name || '') + ' ' + (s.last_name || '') + '</td>'
                    + '<td>' + (s.email || '') + '</td>'
                    + '<td><input type="checkbox" class="attend-check" ' + attended + '></td>'
                    + '</tr>';
            }).join('');
            // Mark attendance on change
            table.querySelectorAll('.attend-check').forEach(function(cb) {
                cb.addEventListener('change', function() {
                    var tr = cb.closest('tr');
                    var sid = tr.dataset.student;
                    var present = cb.checked ? '1' : '0';
                    var fd2 = new FormData();
                    fd2.append('live_class_id', classId);
                    fd2.append('student_id', sid);
                    fd2.append('present', present);
                    fetch(apiBase + '?action=attendance_mark', {
                        method: 'POST',
                        headers: { 'X-CSRF-Token': csrf() },
                        body: fd2
                    }).then(function(r){return r.json();}).then(function(r){
                        if (!r.success) alert(r.message || 'Failed to mark attendance');
                    }).catch(function(){ alert('Network error'); });
                });
            });
        }).catch(function(){ loading.textContent = 'Could not load roster.'; });
    });
})();
</script>

    </div>
</div>
