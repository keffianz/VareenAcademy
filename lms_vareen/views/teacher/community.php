<?php
requireRoles(['teacher', 'admin']);
require_once 'src/classes/Database.php';
require_once 'src/classes/Community.php';

$userId = getCurrentUserId();
$db = (new Database())->connect();
$community = new Community();

$communities = $community->getAll();
$totalPosts = $community->totalCount();
$pendingReports = $community->totalReports('pending');
?>
<div class="dashboard-wrapper">
    <?php $teacher_active='community'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Community Hub</h1><p>Engage with students and fellow instructors</p></div>
        </div>
        <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr)">
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-comments"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo count($communities); ?></span><span class="kpi-label">Communities</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-file-alt"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalPosts; ?></span><span class="kpi-label">Total Posts</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-flag"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $pendingReports; ?></span><span class="kpi-label">Pending Reports</span></div></div>
        </div>
        <div class="dashboard-section">
            <div class="section-header"><h2>Communities</h2></div>
            <div class="courses-grid">
                <?php foreach($communities as $c): ?>
                <div class="course-card">
                    <div class="course-card-header"><i class="fas <?php echo htmlspecialchars($c['icon']); ?>"></i></div>
                    <div class="course-card-body">
                        <h3><?php echo htmlspecialchars($c['name']); ?></h3>
                        <p><?php echo htmlspecialchars($c['description'] ?? ''); ?></p>
                        <span class="role-badge role-teacher"><?php echo $c['channel_count']; ?> channels</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('teacherSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>