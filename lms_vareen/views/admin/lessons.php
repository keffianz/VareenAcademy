<?php
requireRole('admin');
require_once 'src/classes/Database.php';
$db = (new Database())->connect();
$lessons = $db->query('SELECT l.*, c.title AS course_title FROM lessons l JOIN courses c ON c.id = l.course_id ORDER BY l.created_at DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="dashboard-wrapper">
    <?php $admin_active='lessons'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Lessons</h1><p>Manage course lessons</p></div>
        </div>
        <div class="dashboard-section">
            <div class="section-header"><h2>All Lessons</h2></div>
            <?php if(empty($lessons)): ?><div class="empty-state">No lessons found</div><?php else: ?>
            <table class="admin-table"><thead><tr><th>Title</th><th>Course</th><th>Type</th><th>Duration</th><th>Date</th></tr></thead><tbody>
                <?php foreach($lessons as $l): ?>
                <tr><td><?php echo htmlspecialchars($l['title']); ?></td><td><?php echo htmlspecialchars($l['course_title']); ?></td><td><?php echo !empty($l['video_url']) ? 'Video' : 'Reading'; ?></td><td><?php echo $l['video_duration'] ? (int)$l['video_duration'] . ' min' : '—'; ?></td><td><?php echo date('M j', strtotime($l['created_at'])); ?></td></tr>
                <?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>(function(){var s=document.getElementById('adminSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();</script>