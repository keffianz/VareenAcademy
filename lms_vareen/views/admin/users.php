<?php
/**
 * Admin - Manage Users
 */
requireRole('admin');
require_once 'src/classes/Database.php';

$pdo = (new Database())->connect();

$roleFilter = in_array($_GET['role'] ?? '', ['student', 'teacher', 'admin'], true) ? $_GET['role'] : '';
$q = trim($_GET['q'] ?? '');

$where = [];
$params = [];
if ($roleFilter) { $where[] = 'role = :role'; $params[':role'] = $roleFilter; }
if ($q !== '') { $where[] = '(email LIKE :q OR CONCAT(first_name," ",last_name) LIKE :q)'; $params[':q'] = '%' . $q . '%'; }
$sql = 'SELECT id, first_name, last_name, email, role, is_active, specialization, created_at FROM users';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY created_at DESC LIMIT 500';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$apiBase = appBasePath() . '/src/api/admin.php';
?>
<div class="dashboard-wrapper">
    <?php $admin_active = 'users'; include __DIR__ . '/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Manage Users</h1><p>All registered accounts and their status</p></div>
            <button class="btn btn-logout" id="adminLogoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <form class="admin-filters" method="GET" action="">
            <input type="hidden" name="page" value="admin-users">
            <div class="form-row" style="flex:1">
                <input type="search" name="q" placeholder="Search by name or email…" value="<?= htmlspecialchars($q) ?>">
            </div>
            <div class="form-row" style="min-width:140px">
                <select name="role" onchange="this.form.submit()">
                    <option value="">All roles</option>
                    <option value="student" <?= $roleFilter === 'student' ? 'selected' : '' ?>>Students</option>
                    <option value="teacher" <?= $roleFilter === 'teacher' ? 'selected' : '' ?>>Teachers</option>
                    <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admins</option>
                </select>
            </div>
        </form>
        <section class="dashboard-section">
            <?php if (empty($users)): ?>
                <div class="empty-state">No users found matching your search.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th class="th-actions">Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr data-user-id="<?= $u['id'] ?>">
                                <td><?= $u['id'] ?></td>
                                <td><?= htmlspecialchars(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><span class="role-badge role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                                <td><span class="status-dot <?= !empty($u['is_active']) ? 'active' : 'inactive' ?>"><?= !empty($u['is_active']) ? 'Active' : 'Inactive' ?></span></td>
                                <td><?= htmlspecialchars($u['created_at'] ? date('M j, Y', strtotime($u['created_at'])) : '—') ?></td>
                                <td class="th-actions">
                                    <button class="btn btn-xs toggle-active" data-active="<?= !empty($u['is_active']) ? '1' : '0' ?>" type="button">
                                        <?= !empty($u['is_active']) ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<style>
    .admin-filters{display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap}
    .admin-filters input[type="search"]{flex:1;min-width:200px;padding:9px 12px;border:1px solid #d5daea;border-radius:8px;font-size:14px}
    .admin-filters select{padding:9px 12px;border:1px solid #d5daea;border-radius:8px;font-size:14px;background:#fff;cursor:pointer}
    .table-wrap{overflow-x:auto}
    .admin-table th{padding:10px;color:#888;font-weight:600;border-bottom:2px solid #eee}
    .admin-table td{padding:10px;border-bottom:1px solid #f0f0f0;word-break:break-word}
    .th-actions{width:130px;text-align:right}
    .btn-xs{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600;border:1px solid #ddd;background:#fff;cursor:pointer}
    .btn-xs:hover{background:#f5f5f5}
    .status-dot{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
    .status-dot.active{background:#e6f6ec;color:#2e9e5b}
    .status-dot.inactive{background:#fdeaea;color:#d9534f}
    @media(max-width:768px){.admin-filters{flex-direction:column}.th-actions{width:auto;text-align:center}}
</style>
<script>
(function () {
    var apiBase = '<?= $apiBase ?>';
    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }
    function setActive(btn, userId, isActive) {
        var fd = new FormData();
        fd.append('user_id', userId);
        fd.append('is_active', isActive ? '1' : '0');
        fetch(apiBase + '?action=user_set_active', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrf() },
            body: fd
        }).then(function(r){return r.json();}).then(function(r){
            if (r.success) {
                var row = btn.closest('tr');
                var dot = row.querySelector('.status-dot');
                var newActive = !isActive;
                dot.className = 'status-dot ' + (newActive ? 'active' : 'inactive');
                dot.textContent = newActive ? 'Active' : 'Inactive';
                btn.textContent = newActive ? 'Deactivate' : 'Activate';
                btn.dataset.active = newActive ? '1' : '0';
            } else { alert(r.message || 'Action failed'); }
        }).catch(function(){ alert('Network error'); });
    }
    document.querySelectorAll('.toggle-active').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.closest('tr');
            setActive(btn, row.dataset.userId, btn.dataset.active === '1');
        });
    });
    var logoutBtn = document.getElementById('adminLogoutBtn');
    if (logoutBtn) logoutBtn.addEventListener('click', function () { if (window.Auth) Auth.logout(); });
})();
</script>

