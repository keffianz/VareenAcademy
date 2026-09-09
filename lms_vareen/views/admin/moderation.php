<?php
requireRole('admin');
require_once 'src/classes/Database.php';
$db = (new Database())->connect();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    requireCsrf();
    if ($_POST['action'] === 'resolve_report') {
        $db->prepare('UPDATE community_reports SET status = :s, reviewed_by = :rb WHERE id = :id')->execute([':s' => $_POST['status'], ':rb' => $_SESSION['user_id'], ':id' => (int)$_POST['id']]);
        header('Location: /index.php?page=admin-moderation'); exit;
    }
}
$reports = $db->query('SELECT r.*, p.title AS post_title, CONCAT(u.first_name, " ", u.last_name) AS reporter_name FROM community_reports r LEFT JOIN community_posts p ON p.id = r.post_id JOIN users u ON u.id = r.reported_by WHERE r.status = "pending" ORDER BY r.created_at DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
$pendingCount = (int)$db->query("SELECT COUNT(*) FROM community_reports WHERE status = 'pending'")->fetchColumn();
$actionedCount = (int)$db->query("SELECT COUNT(*) FROM community_reports WHERE status = 'actioned'")->fetchColumn();
?>
<div class="dashboard-wrapper">
    <?php $admin_active='moderation'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>AI Moderation</h1><p>Review reported content</p></div>
            <button class="btn btn-logout" id="adminLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <div class="kpi-grid" style="grid-template-columns:repeat(2,1fr)">
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-flag"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $pendingCount; ?></span><span class="kpi-label">Pending</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-check"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $actionedCount; ?></span><span class="kpi-label">Actioned</span></div></div>
        </div>
        <div class="dashboard-section">
            <div class="section-header"><h2>Pending Reports</h2></div>
            <?php if(empty($reports)): ?><div class="empty-state">No pending reports</div><?php else: ?>
            <table class="admin-table"><thead><tr><th>Post</th><th>Reporter</th><th>Reason</th><th>Date</th><th>Actions</th></tr></thead><tbody>
                <?php foreach($reports as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['post_title']??'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($r['reporter_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['reason']); ?></td>
                    <td><?php echo date('M j', strtotime($r['created_at'])); ?></td>
                    <td>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="resolve_report">
                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                            <input type="hidden" name="status" value="actioned">
                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                            <button type="submit" class="btn btn-sm btn-warning">Action</button>
                        </form>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="resolve_report">
                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                            <input type="hidden" name="status" value="dismissed">
                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                            <button type="submit" class="btn btn-sm btn-secondary">Dismiss</button>
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