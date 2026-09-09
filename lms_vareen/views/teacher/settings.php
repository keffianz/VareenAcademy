<?php
requireRoles(['teacher', 'admin']);
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'save_settings') {
    requireCsrf();
    $message = 'Settings saved successfully.';
}
?>
<div class="dashboard-wrapper">
    <?php $teacher_active='settings'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Settings</h1><p>Manage your account preferences</p></div>
            <button class="btn btn-logout" id="teacherLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <?php if($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <div class="dashboard-section">
            <div class="section-header"><h2>Notification Preferences</h2></div>
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="save_settings">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <div class="form-group"><label><input type="checkbox" name="notify_new_student" checked> New student enrollment</label></div>
                <div class="form-group"><label><input type="checkbox" name="notify_submission" checked> Assignment submissions</label></div>
                <div class="form-group"><label><input type="checkbox" name="notify_community" checked> Community activity</label></div>
                <div class="form-group"><label><input type="checkbox" name="notify_live" checked> Live class reminders</label></div>
                <div class="form-group"><button type="submit" class="btn btn-primary">Save Settings</button></div>
            </form>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('teacherSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>