<?php
requireRole('admin');
require_once 'src/classes/Database.php';
$db = (new Database())->connect();
$totalStudents = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalTeachers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
$totalCourses = (int)$db->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalEnrollments = (int)$db->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
$totalRevenue = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid'")->fetchColumn();
$totalCerts = (int)$db->query("SELECT COUNT(*) FROM certificates")->fetchColumn();
$totalPosts = (int)$db->query("SELECT COUNT(*) FROM community_posts WHERE is_deleted=0")->fetchColumn();
$liveToday = (int)$db->query("SELECT COUNT(*) FROM live_classes WHERE DATE(scheduled_at)=CURDATE()")->fetchColumn();
?>
<div class="dashboard-wrapper">
    <?php $admin_active='analytics'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Analytics</h1><p>Academy overview</p></div>
        </div>
        <div class="kpi-grid">
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-user-graduate"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalStudents; ?></span><span class="kpi-label">Students</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-chalkboard-teacher"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalTeachers; ?></span><span class="kpi-label">Teachers</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-book"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalCourses; ?></span><span class="kpi-label">Courses</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-user-check"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalEnrollments; ?></span><span class="kpi-label">Enrollments</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-naira-sign"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo number_format($totalRevenue); ?></span><span class="kpi-label">Revenue</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-certificate"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalCerts; ?></span><span class="kpi-label">Certificates</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-comments"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalPosts; ?></span><span class="kpi-label">Posts</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-video"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $liveToday; ?></span><span class="kpi-label">Live Today</span></div></div>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>(function(){var s=document.getElementById('adminSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();</script>