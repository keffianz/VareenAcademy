<?php
requireRole('admin');
require_once 'src/classes/Database.php';
$db = (new Database())->connect();
$liveClasses = $db->query('SELECT l.*, c.title AS course_title, CONCAT(u.first_name, " ", u.last_name) AS teacher_name FROM live_classes l JOIN courses c ON c.id = l.course_id JOIN users u ON u.id = l.teacher_id ORDER BY l.scheduled_at DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
$todayCount = (int)$db->query('SELECT COUNT(*) FROM live_classes WHERE DATE(scheduled_at) = CURDATE()')->fetchColumn();
$upcomingCount = (int)$db->query("SELECT COUNT(*) FROM live_classes WHERE scheduled_at > NOW()")->fetchColumn();
?>
<div class="dashboard-wrapper">
    <?php $admin_active='live'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Live Classes</h1><p>Manage virtual classroom sessions</p></div>
        </div>
        <div class="kpi-grid" style="grid-template-columns:repeat(2,1fr)">
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-video"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $todayCount; ?></span><span class="kpi-label">Today</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-calendar"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $upcomingCount; ?></span><span class="kpi-label">Upcoming</span></div></div>
        </div>
        <div class="dashboard-section">
            <div class="section-header"><h2>All Live Classes</h2></div>
            <?php if(empty($liveClasses)): ?><div class="empty-state">No live classes scheduled</div>
            <?php else: ?>
            <table class="admin-table"><thead><tr><th>Title</th><th>Course</th><th>Teacher</th><th>Scheduled</th><th>Status</th></tr></thead><tbody>
                <?php foreach($liveClasses as $l): ?>
                <tr>
                    <td><?php echo htmlspecialchars($l['title']); ?></td>
                    <td><?php echo htmlspecialchars($l['course_title']); ?></td>
                    <td><?php echo htmlspecialchars($l['teacher_name']); ?></td>
                    <td><?php echo date('M j, g:i A', strtotime($l['scheduled_at'])); ?></td>
                    <td><span class="role-badge <?php echo $l['status']==='active'?'role-student':'role-teacher'; ?>"><?php echo $l['status']; ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('adminSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>