<?php
// Teacher Quiz Attempts - MVP helper page (optional)
requireRoles(['teacher','admin']);
?>
<div class="dashboard-wrapper">
    <?php $teacher_active='quiz-attempts'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Quiz Attempts</h1><p>Grading overview — results are auto-graded for multiple-choice and true/false.</p></div>
            <button class="btn btn-logout" id="teacherLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <div class="dashboard-section">
            <div class="empty-state"><i class="fas fa-question-circle"></i><p>MVP: grading/results are auto-graded for MC/TF only.</p></div>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('teacherSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>

