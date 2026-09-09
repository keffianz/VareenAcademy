<?php
requireRole('admin');
require_once 'src/classes/Database.php';
$db = (new Database())->connect();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    requireCsrf();
    $action = $_POST['action'];
    $tid = (int)($_POST['teacher_id'] ?? 0);
    if ($action === 'toggle_active' && $tid) {
        $isActive = (int)!empty($_POST['is_active']);
        $stmt = $db->prepare('UPDATE users SET is_active = :a WHERE id = :id AND role = "teacher"');
        $stmt->execute([':a' => $isActive, ':id' => $tid]);
        $message = $isActive ? 'Teacher activated.' : 'Teacher deactivated.';
    }
}
$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$sql = 'SELECT id, first_name, last_name, email, specialization, is_active, created_at FROM users WHERE role = "teacher"';
$params = [];
if ($search !== '') { $sql .= ' AND (email LIKE :q OR CONCAT(first_name, " ", last_name) LIKE :q)'; $params[':q'] = '%' . $search . '%'; }
if ($statusFilter === 'active') $sql .= ' AND is_active = 1';
elseif ($statusFilter === 'inactive') $sql .= ' AND is_active = 0';
$sql .= ' ORDER BY created_at DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalT = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
$activeT = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='teacher' AND is_active=1")->fetchColumn();
?>
<div class="dashboard-wrapper">
    <?php $admin_active='teachers'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Teachers</h1><p>Manage instructors</p></div>
            <button class="btn btn-logout" id="adminLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <?php if($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr)">
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-chalkboard-teacher"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalT; ?></span><span class="kpi-label">Total Teachers</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-user-check"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $activeT; ?></span><span class="kpi-label">Active</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-user-times"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalT-$activeT; ?></span><span class="kpi-label">Inactive</span></div></div>
        </div>
        <div class="dashboard-section">
            <div class="section-header">
                <h2>All Teachers</h2>
                <form method="GET" class="search-form">
                    <input type="hidden" name="page" value="admin-teachers">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search..." class="search-input">
                    <select name="status" class="filter-select">
                        <option value="all" <?php echo $statusFilter==='all'?'selected':''; ?>>All</option>
                        <option value="active" <?php echo $statusFilter==='active'?'selected':''; ?>>Active</option>
                        <option value="inactive" <?php echo $statusFilter==='inactive'?'selected':''; ?>>Inactive</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </div>
            <?php if(empty($teachers)): ?><div class="empty-state">No teachers found</div>
            <?php else: ?>
            <table class="admin-table"><thead><tr><th>Name</th><th>Email</th><th>Specialization</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead><tbody>
                <?php foreach($teachers as $t): ?>
                <tr>
                    <td><?php echo htmlspecialchars($t['first_name'].' '.$t['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($t['email']); ?></td>
                    <td><?php echo htmlspecialchars($t['specialization']??'—'); ?></td>
                    <td><span class="role-badge <?php echo $t['is_active']?'role-student':'role-admin'; ?>"><?php echo $t['is_active']?'Active':'Inactive'; ?></span></td>
                    <td><?php echo date('M j, Y', strtotime($t['created_at'])); ?></td>
                    <td>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="teacher_id" value="<?php echo $t['id']; ?>">
                            <input type="hidden" name="is_active" value="<?php echo $t['is_active']?0:1; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                            <button type="submit" class="btn btn-sm <?php echo $t['is_active']?'btn-warning':'btn-success'; ?>"><?php echo $t['is_active']?'Deactivate':'Activate'; ?></button>
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
<script>
(function(){var s=document.getElementById('adminSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>