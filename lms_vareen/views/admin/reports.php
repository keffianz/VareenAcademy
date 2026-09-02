<?php
/**
 * Admin Reports — real platform statistics (no fabricated numbers).
 */
requireRole('admin');
$admin_active = 'reports';
?>
<link rel="stylesheet" href="<?php echo appBasePath(); ?>/public/css/dashboard.css">
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <main class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" type="button">☰ Menu</button>
            <div class="topbar-title">
                <h1>Reports</h1>
                <p>Platform totals and per-course academic performance.</p>
            </div>
            <button class="btn-logout" id="logoutBtn" type="button">Logout</button>
        </div>

        <div class="notice" id="notice"></div>

        <div class="stats-cards" id="totalsCards">
            <div class="stat-card"><div class="empty-state" style="width:100%">Loading reports…</div></div>
        </div>

        <div class="panel">
            <h2>Per-Course Report</h2>
            <div class="table-wrap">
                <table class="admin-table" id="courseTable">
                    <thead>
                        <tr>
                            <th>Course</th><th>Enrolled</th><th>Completed</th><th>Assignments</th>
                            <th>Submissions</th><th>Graded</th><th>Quizzes</th><th>Attempts</th>
                            <th>Avg Quiz %</th><th>Attendance</th>
                        </tr>
                    </thead>
                    <tbody><tr><td colspan="10" class="empty-state">Loading…</td></tr></tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
const API = '<?php echo appBasePath(); ?>/src/api/admin.php';
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const TOTAL_LABELS = [
    ['users','Total Users','fa-users','#667eea'],
    ['students','Students','fa-user-graduate','#28a745'],
    ['teachers','Teachers','fa-chalkboard-teacher','#4facfe'],
    ['courses','Courses','fa-book','#764ba2'],
    ['enrollments','Enrollments','fa-user-plus','#f5576c'],
    ['assignments','Assignments','fa-tasks','#f09819'],
    ['quizzes','Quizzes','fa-question-circle','#38f9d7'],
    ['certificates','Certificates','fa-certificate','#43e97b']
];

function showNotice(msg, ok) {
    const n = document.getElementById('notice');
    n.textContent = msg;
    n.className = 'notice ' + (ok ? 'notice-ok' : 'notice-err');
    n.style.display = 'block';
    setTimeout(() => { n.style.display = 'none'; }, 4000);
}

async function loadReports() {
    try {
        const res = await fetch(API + '?action=reports');
        const data = await res.json();
        if (!data.success) { showNotice(data.message || 'Failed to load reports', false); return; }

        const t = data.totals || {};
        document.getElementById('totalsCards').innerHTML = TOTAL_LABELS.map(([k, label, icon, color]) =>
            `<div class="stat-card">
                <div class="stat-icon" style="background:${color}"><i class="fas ${icon}"></i></div>
                <div class="stat-info"><p class="stat-label">${esc(label)}</p><h3>${esc(t[k] ?? 0)}</h3></div>
            </div>`).join('');

        const rows = data.per_course || [];
        const tbody = document.querySelector('#courseTable tbody');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="10" class="empty-state">No courses yet.</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(r => `<tr>
            <td><strong>${esc(r.title)}</strong></td>
            <td>${esc(r.enrolled)}</td><td>${esc(r.completed)}</td><td>${esc(r.assignments)}</td>
            <td>${esc(r.submissions)}</td><td>${esc(r.graded)}</td><td>${esc(r.quizzes)}</td>
            <td>${esc(r.quiz_attempts)}</td><td>${Number(r.avg_quiz_score || 0).toFixed(1)}%</td>
            <td>${esc(r.attendance_records)}</td>
        </tr>`).join('');
    } catch (e) {
        showNotice('Network error loading reports', false);
    }
}

document.getElementById('sidebarToggle').addEventListener('click', () =>
    document.getElementById('adminSidebar').classList.toggle('active'));
document.getElementById('logoutBtn').addEventListener('click', async () => {
    await fetch('<?php echo appBasePath(); ?>/src/api/auth.php?action=logout', { method: 'POST' });
    location.href = '<?php echo appBasePath(); ?>/index.php?page=login';
});

loadReports();
</script>
