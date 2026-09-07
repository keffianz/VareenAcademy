<?php
requireRole('admin');
require_once 'src/classes/Database.php';
$db = (new Database())->connect();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    requireCsrf();
    $action = $_POST['action'];
    if ($action === 'create_community') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if ($name && $slug) {
            $stmt = $db->prepare('INSERT INTO communities (name, slug, description, icon) VALUES (:n, :s, :d, :i)');
            $stmt->execute([':n' => $name, ':s' => $slug, ':d' => trim($_POST['description'] ?? ''), ':i' => trim($_POST['icon'] ?? 'fa-users')]);
            $message = 'Community created.';
        }
    } elseif ($action === 'delete_community') {
        $db->prepare('DELETE FROM communities WHERE id = :id')->execute([':id' => (int)$_POST['id']]);
        $message = 'Community deleted.';
    }
}
$communities = $db->query('SELECT c.*, COUNT(DISTINCT ch.id) AS channel_count, (SELECT COUNT(*) FROM community_posts p WHERE p.community_id = c.id AND p.is_deleted = 0) AS post_count FROM communities c LEFT JOIN community_channels ch ON ch.community_id = c.id GROUP BY c.id ORDER BY c.sort_order')->fetchAll(PDO::FETCH_ASSOC);
$totalPosts = (int)$db->query('SELECT COUNT(*) FROM community_posts WHERE is_deleted = 0')->fetchColumn();
$totalComments = (int)$db->query('SELECT COUNT(*) FROM community_comments WHERE is_deleted = 0')->fetchColumn();
$pendingReports = (int)$db->query("SELECT COUNT(*) FROM community_reports WHERE status = 'pending'")->fetchColumn();
?>
<div class="dashboard-wrapper">
    <?php $admin_active='community'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Community Hub</h1><p>Manage communities and channels</p></div>
        </div>
        <?php if($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <div class="kpi-grid" style="grid-template-columns:repeat(4,1fr)">
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-comments"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo count($communities); ?></span><span class="kpi-label">Communities</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-file-alt"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalPosts; ?></span><span class="kpi-label">Total Posts</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-comment-dots"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalComments; ?></span><span class="kpi-label">Comments</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-flag"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $pendingReports; ?></span><span class="kpi-label">Pending Reports</span></div></div>
        </div>
        <div class="dashboard-section">
            <div class="section-header"><h2>Communities</h2></div>
            <table class="admin-table"><thead><tr><th>Name</th><th>Channels</th><th>Posts</th><th>Actions</th></tr></thead><tbody>
                <?php foreach($communities as $c): ?>
                <tr>
                    <td><i class="fas <?php echo htmlspecialchars($c['icon']); ?>"></i> <?php echo htmlspecialchars($c['name']); ?></td>
                    <td><?php echo $c['channel_count']; ?></td>
                    <td><?php echo $c['post_count']; ?></td>
                    <td>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
                            <input type="hidden" name="action" value="delete_community">
                            <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody></table>
        </div>
        <div class="dashboard-section" style="margin-top:20px">
            <div class="section-header"><h2>Create Community</h2></div>
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="create_community">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <div class="form-group"><label>Name</label><input type="text" name="name" required class="form-input"></div>
                <div class="form-group"><label>Slug</label><input type="text" name="slug" required class="form-input"></div>
                <div class="form-group"><label>Icon</label><input type="text" name="icon" value="fa-users" class="form-input"></div>
                <div class="form-group" style="grid-column:1/-1"><label>Description</label><textarea name="description" class="form-input" rows="2"></textarea></div>
                <div class="form-group"><button type="submit" class="btn btn-primary">Create</button></div>
            </form>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>