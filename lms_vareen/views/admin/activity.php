<?php
requireRole('admin');
require_once 'src/classes/Database.php';
require_once 'src/classes/ActivityLog.php';
$db = (new Database())->connect();
$activity = new ActivityLog();
$limit = (int)($_GET['limit'] ?? 50);
$activities = $activity->getRecent($limit);
?>
<div class="dashboard-wrapper">
    <?php $admin_active='activity'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Activity Log</h1><p>Audit trail of admin actions</p></div>
        </div>
        <div class="dashboard-section">
            <div class="section-header"><h2>Recent Activity</h2></div>
            <?php if(empty($activities)): ?><div class="empty-state">No activity recorded yet</div>
            <?php else: ?>
            <table class="admin-table"><thead><tr><th>User</th><th>Action</th><th>Description</th><th>IP</th><th>Date</th></tr></thead><tbody>
                <?php foreach($activities as $a): ?>
                <tr>
                    <td><?php echo htmlspecialchars($a['user_name']??'System'); ?></td>
                    <td><span class="role-badge role-admin"><?php echo htmlspecialchars($a['action']); ?></span></td>
                    <td><?php echo htmlspecialchars($a['description']??''); ?></td>
                    <td><?php echo htmlspecialchars($a['ip_address']??''); ?></td>
                    <td><?php echo date('M j, g:i A', strtotime($a['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('adminSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>