<?php
requireRoles(['teacher', 'admin']);
?>
<div class="dashboard-wrapper">
    <?php $teacher_active='showcase'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Student Showcase</h1><p>Feature outstanding student work</p></div>
            <button class="btn btn-logout" id="teacherLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <div class="dashboard-section">
            <div class="section-header"><h2>About Student Showcase</h2></div>
            <p style="color:#666; margin-bottom:20px">The Student Showcase is a dedicated space where students can post their projects, designs, code, and portfolios. As a teacher, you can feature outstanding work and leave feedback.</p>
            <div class="courses-grid">
                <div class="course-card"><div class="course-card-body"><h3><i class="fas fa-code"></i> Code Projects</h3><p>Students share their programming projects</p></div></div>
                <div class="course-card"><div class="course-card-body"><h3><i class="fas fa-palette"></i> Design Work</h3><p>Graphic design and UI/UX projects</p></div></div>
                <div class="course-card"><div class="course-card-body"><h3><i class="fas fa-briefcase"></i> Portfolios</h3><p>Professional student portfolios</p></div></div>
            </div>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('teacherSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>