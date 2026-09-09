<?php
requireRole('admin');
require_once 'src/classes/Database.php';
$db = (new Database())->connect();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'send') {
    requireCsrf();
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $target = $_POST['target_type'] ?? 'global';
    if ($title && $content) {
        $stmt = $db->prepare('INSERT INTO announcements (admin_id, title, content, target_type, target_id) VALUES (:a, :t, :c, :tg, :td)');
        $stmt->execute([':a' => $_SESSION['user_id'], ':t' => $title, ':c' => $content, ':tg' => $target, ':td' => (int)($_POST['target_id'] ?? 0) ?: null]);
        $message = 'Announcement sent.';
    }
}
$announcements = $db->query('SELECT a.*, CONCAT(u.first_name, " ", u.last_name) AS admin_name FROM announcements a JOIN users u ON u.id = a.admin_id ORDER BY a.created_at DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="dashboard-wrapper">
    <?php $admin_active='notifications'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Notifications</h1><p>Send announcements to users</p></div>
            <button class="btn btn-logout" id="adminLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <?php if($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <div class="dashboard-section">
            <div class="section-header"><h2>Send Announcement</h2></div>
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="send">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <div class="form-group"><label>Title</label><input type="text" name="title" required class="form-input"></div>
                <div class="form-group"><label>Target</label>
                    <select name="target_type" class="form-input">
                        <option value="global">Global</option>
                        <option value="student">Students</option>
                        <option value="teacher">Teachers</option>
                        <option value="course">Course</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column:1/-1"><label>Content</label><textarea name="content" class="form-input" rows="3" required></textarea></div>
                <div class="form-group"><button type="submit" class="btn btn-primary">Send</button></div>
            </form>
        </div>
        <div class="dashboard-section" style="margin-top:20px">
            <div class="section-header"><h2>Past Announcements</h2></div>
            <table class="admin-table"><thead><tr><th>Title</th><th>Target</th><th>Sent By</th><th>Date</th></tr></thead><tbody>
                <?php foreach($announcements as $a): ?>
                <tr>
                    <td><?php echo htmlspecialchars($a['title']); ?></td>
                    <td><span class="role-badge role-teacher"><?php echo $a['target_type']; ?></span></td>
                    <td><?php echo htmlspecialchars($a['admin_name']); ?></td>
                    <td><?php echo date('M j, g:i A', strtotime($a['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody></table>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('adminSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>