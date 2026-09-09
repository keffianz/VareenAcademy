<?php
requireRole('admin');
require_once 'src/classes/Database.php';
$db = (new Database())->connect();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    requireCsrf();
    if ($_POST['action'] === 'delete_post') {
        $db->prepare('UPDATE community_posts SET is_deleted = 1 WHERE id = :id')->execute([':id' => (int)$_POST['id']]);
        $message = 'Post deleted.';
    } elseif ($_POST['action'] === 'pin_post') {
        $db->prepare('UPDATE community_posts SET is_pinned = :p WHERE id = :id')->execute([':p' => (int)!empty($_POST['pinned']), ':id' => (int)$_POST['id']]);
        $message = 'Post updated.';
    }
    // Refresh list
    header('Location: /index.php?page=admin-discussions'); exit;
}
$posts = $db->query('SELECT p.*, CONCAT(u.first_name, " ", u.last_name) AS author_name, c.name AS community_name FROM community_posts p JOIN users u ON u.id = p.user_id JOIN communities c ON c.id = p.community_id WHERE p.is_deleted = 0 ORDER BY p.created_at DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="dashboard-wrapper">
    <?php $admin_active='discussions'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Discussions</h1><p>All community posts</p></div>
            <button class="btn btn-logout" id="adminLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <div class="dashboard-section">
            <div class="section-header"><h2>Recent Posts</h2></div>
            <?php if(empty($posts)): ?><div class="empty-state">No posts yet</div><?php else: ?>
            <table class="admin-table"><thead><tr><th>Title</th><th>Author</th><th>Community</th><th>Date</th><th>Actions</th></tr></thead><tbody>
                <?php foreach($posts as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['title']); ?><?php if($p['is_pinned']): ?> <i class="fas fa-thumbtack"></i><?php endif; ?></td>
                    <td><?php echo htmlspecialchars($p['author_name']); ?></td>
                    <td><?php echo htmlspecialchars($p['community_name']); ?></td>
                    <td><?php echo date('M j', strtotime($p['created_at'])); ?></td>
                    <td>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="pin_post">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <input type="hidden" name="pinned" value="<?php echo $p['is_pinned']?0:1; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                            <button type="submit" class="btn btn-sm btn-secondary"><?php echo $p['is_pinned']?'Unpin':'Pin'; ?></button>
                        </form>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
                            <input type="hidden" name="action" value="delete_post">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>(function(){var s=document.getElementById('adminSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();</script>