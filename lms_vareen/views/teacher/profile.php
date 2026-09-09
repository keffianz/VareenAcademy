<?php
requireRoles(['teacher', 'admin']);
require_once 'src/classes/Database.php';

$userId = getCurrentUserId();
$db = (new Database())->connect();

$stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'update_profile') {
    requireCsrf();
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    if ($firstName && $lastName) {
        $stmt = $db->prepare('UPDATE users SET first_name = :fn, last_name = :ln, specialization = :spec, bio = :bio WHERE id = :id');
        $stmt->execute([':fn' => $firstName, ':ln' => $lastName, ':spec' => $specialization, ':bio' => $bio, ':id' => $userId]);
        $message = 'Profile updated successfully.';
        $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<div class="dashboard-wrapper">
    <?php $teacher_active='profile'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Profile</h1><p>Manage your instructor profile</p></div>
            <button class="btn btn-logout" id="teacherLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <?php if($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <div class="dashboard-section">
            <div class="section-header"><h2>Personal Information</h2></div>
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <div class="form-group"><label>First Name</label><input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required class="form-input"></div>
                <div class="form-group"><label>Last Name</label><input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required class="form-input"></div>
                <div class="form-group"><label>Email</label><input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="form-input" disabled></div>
                <div class="form-group"><label>Specialization</label><input type="text" name="specialization" value="<?php echo htmlspecialchars($user['specialization'] ?? ''); ?>" class="form-input" placeholder="e.g. Web Development"></div>
                <div class="form-group" style="grid-column:1/-1"><label>Bio</label><textarea name="bio" class="form-input" rows="4" placeholder="Tell students about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea></div>
                <div class="form-group"><button type="submit" class="btn btn-primary">Save Changes</button></div>
            </form>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('teacherSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>