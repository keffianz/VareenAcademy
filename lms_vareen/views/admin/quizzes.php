<?php
requireRole('admin');
require_once 'src/classes/Database.php';
$db = (new Database())->connect();
$quizzes = $db->query('SELECT q.*, c.title AS course_title FROM quizzes q JOIN courses c ON c.id = q.course_id ORDER BY q.created_at DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="dashboard-wrapper">
    <?php $admin_active='quizzes'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Quizzes</h1><p>Manage course quizzes</p></div>
            <button class="btn btn-logout" id="adminLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <div class="dashboard-section">
            <div class="section-header"><h2>All Quizzes</h2></div>
            <?php if(empty($quizzes)): ?><div class="empty-state">No quizzes found</div><?php else: ?>
            <table class="admin-table"><thead><tr><th>Title</th><th>Course</th><th>Questions</th><th>Pass Score</th><th>Date</th></tr></thead><tbody>
                <?php foreach($quizzes as $q): ?>
                <tr><td><?php echo htmlspecialchars($q['title']); ?></td><td><?php echo htmlspecialchars($q['course_title']); ?></td><td><?php echo $q['total_questions']??0; ?></td><td><?php echo $q['pass_score']??0; ?>%</td><td><?php echo date('M j', strtotime($q['created_at'])); ?></td></tr>
                <?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>(function(){var s=document.getElementById('adminSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();</script>