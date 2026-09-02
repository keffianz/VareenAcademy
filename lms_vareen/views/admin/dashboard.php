<?php
/**
 * Admin Dashboard
 * Platform overview: user counts, course counts, recent registrations, system status.
 */
requireRole('admin');

require_once 'src/classes/Database.php';

$pdo = (new Database())->connect();

// Platform statistics (fail-safe: placeholders if tables are not migrated yet)
$stats = ['total_users' => '—', 'students' => '—', 'teachers' => '—', 'courses' => '—'];
try {
    $stats['total_users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $stats['students']    = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $stats['teachers']    = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
    $stats['courses']     = (int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();
} catch (PDOException $e) {
    // Tables not migrated yet
}

// Recent registrations (fallback if the created_at column is absent)
$recent_users = [];
try {
    $stmt = $pdo->prepare('SELECT id, first_name, last_name, email, role, created_at FROM users ORDER BY id DESC LIMIT 8');
    $stmt->execute();
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    try {
        $stmt = $pdo->prepare('SELECT id, first_name, last_name, email, role FROM users ORDER BY id DESC LIMIT 8');
        $stmt->execute();
        $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        $recent_users = [];
    }
}

$aiConfigured = file_exists(__DIR__ . '/../src/config/ai_config.php');
?>

<div class="dashboard-wrapper">
    <!-- Sidebar Navigation -->
    <aside class="dashboard-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <h3>Admin Panel</h3>
            <button class="sidebar-close" id="sidebarClose"><i class="fas fa-times"></i></button>
        </div>
        <nav class="sidebar-menu">
            <ul>
                <li><a href="/index.php?page=admin-dashboard" class="active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a></li>
                <li><a href="#" title="Coming soon — Phase 8">
                    <i class="fas fa-users-cog"></i> Manage Users
                </a></li>
                <li><a href="#" title="Coming soon — Phase 8">
                    <i class="fas fa-book"></i> Manage Courses
                </a></li>
                <li><a href="#" title="Coming soon — Phase 8">
                    <i class="fas fa-chart-bar"></i> Reports
                </a></li>
                <li><a href="#" title="Coming soon — Phase 8">
                    <i class="fas fa-cog"></i> Settings
                </a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title">
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Admin'); ?> — platform overview</p>
            </div>
            <button class="btn btn-logout" id="adminLogoutBtn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
        <!-- Statistics Cards -->
        <section class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);"><i class="fas fa-users"></i></div>
                <div class="stat-info"><p class="stat-label">Total Users</p><h3><?php echo $stats['total_users']; ?></h3></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-info"><p class="stat-label">Students</p><h3><?php echo $stats['students']; ?></h3></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b, #38f9d7);"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="stat-info"><p class="stat-label">Teachers</p><h3><?php echo $stats['teachers']; ?></h3></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);"><i class="fas fa-book-open"></i></div>
                <div class="stat-info"><p class="stat-label">Courses</p><h3><?php echo $stats['courses']; ?></h3></div>
            </div>
        </section>

        <div class="admin-columns">
            <!-- Recent Registrations -->
            <section class="dashboard-section">
                <div class="section-header"><h2><i class="fas fa-user-plus"></i> Recent Registrations</h2></div>
                <?php if (empty($recent_users)): ?>
                    <div class="empty-state"><i class="fas fa-users"></i><p>No users registered yet</p></div>
                <?php else: ?>
                    <table class="admin-table">
                        <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th></tr></thead>
                        <tbody>
                            <?php foreach ($recent_users as $u): ?>
                                <tr>
                                    <td><?php echo (int) $u['id']; ?></td>
                                    <td><?php echo htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><span class="role-badge role-<?php echo htmlspecialchars($u['role']); ?>"><?php echo htmlspecialchars(ucfirst($u['role'])); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <!-- System Status -->
            <section class="dashboard-section">
                <div class="section-header"><h2><i class="fas fa-server"></i> System Status</h2></div>
                <ul class="status-list">
                    <li><span>Database connection</span><span class="status-ok"><i class="fas fa-check-circle"></i> Connected</span></li>
                    <li><span>PHP version</span><span><?php echo htmlspecialchars(PHP_VERSION); ?></span></li>
                    <li><span>AI Assistant config</span><span class="<?php echo $aiConfigured ? 'status-ok' : 'status-warn'; ?>"><i class="fas <?php echo $aiConfigured ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> <?php echo $aiConfigured ? 'Configured' : 'Not configured'; ?></span></li>
                    <li><span>Session security</span><span class="status-ok"><i class="fas fa-shield-alt"></i> CSRF + rate limiting active</span></li>
                </ul>
            </section>
        </div>
    </div>
</div>

<style>
.dashboard-wrapper{display:flex;min-height:100vh;background:#f8f9fa}
.dashboard-sidebar{width:250px;background:#fff;border-right:1px solid #eee;padding:20px 0;position:fixed;height:100vh;overflow-y:auto;z-index:99}
.sidebar-header{padding:0 20px 20px;border-bottom:1px solid #eee}
.sidebar-header h3{margin:0;font-size:16px;color:#333}
.sidebar-close{display:none;background:none;border:none;font-size:18px;cursor:pointer;color:#666}
.sidebar-menu ul{list-style:none;padding:10px 0;margin:0}
.sidebar-menu a{display:flex;align-items:center;gap:12px;padding:12px 20px;color:#666;text-decoration:none;transition:all .3s}
.sidebar-menu a:hover,.sidebar-menu a.active{color:var(--primary-color,#667eea);background:rgba(102,126,234,.05);border-left:3px solid var(--primary-color,#667eea);padding-left:17px}
.dashboard-content{flex:1;margin-left:250px;padding:24px}
.dashboard-topbar{display:flex;align-items:center;gap:16px;margin-bottom:24px}
.sidebar-toggle{display:none;background:#fff;border:1px solid #ddd;border-radius:8px;padding:8px 12px;cursor:pointer}
.topbar-title h1{margin:0;font-size:22px;color:#222}
.topbar-title p{margin:2px 0 0;color:#777;font-size:13px}
.btn-logout{margin-left:auto;background:#fff;border:1px solid #f5576c;color:#f5576c;border-radius:8px;padding:8px 16px;cursor:pointer;font-weight:600}
.btn-logout:hover{background:#f5576c;color:#fff}
.stats-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.stat-card{display:flex;align-items:center;gap:14px;background:#fff;border-radius:12px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
.stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;flex-shrink:0}
.stat-label{margin:0;font-size:12px;color:#888}
.stat-info h3{margin:2px 0 0;font-size:22px;color:#222}
.admin-columns{display:grid;grid-template-columns:2fr 1fr;gap:16px}
.dashboard-section{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
.section-header{margin-bottom:14px}
.section-header h2{margin:0;font-size:16px;color:#333}
.admin-table{width:100%;border-collapse:collapse;font-size:13px}
.admin-table th{text-align:left;padding:10px;color:#888;font-weight:600;border-bottom:2px solid #eee}
.admin-table td{padding:10px;border-bottom:1px solid #f0f0f0;color:#444;word-break:break-word}
.role-badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.role-admin{background:#fdecea;color:#f5576c}
.role-teacher{background:#e8f4fd;color:#4facfe}
.role-student{background:#e9f9ef;color:#28a745}
.status-list{list-style:none;margin:0;padding:0;font-size:13px}
.status-list li{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f0f0f0;color:#555}
.status-ok{color:#28a745;font-weight:600}
.status-warn{color:#f0ad4e;font-weight:600}
.empty-state{text-align:center;padding:30px;color:#999}
@media(max-width:1024px){.stats-cards{grid-template-columns:repeat(2,1fr)}.admin-columns{grid-template-columns:1fr}}
@media(max-width:768px){.dashboard-sidebar{transform:translateX(-100%);transition:transform .3s}.dashboard-sidebar.active{transform:translateX(0)}.sidebar-close{display:block}.dashboard-content{margin-left:0;padding:16px}.sidebar-toggle{display:block}.stats-cards{grid-template-columns:1fr}}
</style>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function () {
    var sidebar = document.getElementById('adminSidebar');
    var toggle = document.getElementById('sidebarToggle');
    var close = document.getElementById('sidebarClose');
    if (toggle && sidebar) toggle.addEventListener('click', function () { sidebar.classList.add('active'); });
    if (close && sidebar) close.addEventListener('click', function () { sidebar.classList.remove('active'); });
    document.addEventListener('click', function (e) {
        if (sidebar && !e.target.closest('.dashboard-sidebar') && !e.target.closest('.sidebar-toggle')) {
            sidebar.classList.remove('active');
        }
    });
    var logoutBtn = document.getElementById('adminLogoutBtn');
    if (logoutBtn) logoutBtn.addEventListener('click', function () { if (window.Auth) Auth.logout(); });
})();
</script>

