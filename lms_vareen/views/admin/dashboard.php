<?php
/* ------------------------------------------------------------------
 * Error visibility: log everything to the server error log (Hostinger),
 * display nothing to end users in production (prevents info leaks).
 * ------------------------------------------------------------------ */
ini_set('log_errors', 1);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

requireRole('admin');
require_once 'src/classes/Database.php';
$db = (new Database())->connect();

/**
 * Safe scalar query — returns $default instead of a fatal HTTP 500 when a
 * table is missing (e.g. optional migration not yet run on the live DB).
 */
function admin_scalar($db, string $sql, $default = 0) {
    try {
        $v = $db->query($sql)->fetchColumn();
        return ($v === false || $v === null) ? $default : $v;
    } catch (Throwable $e) {
        error_log('[admin-dashboard] scalar query failed: ' . $e->getMessage() . ' | ' . $sql);
        return $default;
    }
}

/** Safe fetch-all — returns [] instead of crashing. */
function admin_rows($db, string $sql, array $params = []) {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('[admin-dashboard] rows query failed: ' . $e->getMessage() . ' | ' . $sql);
        return [];
    }
}

$totalStudents = (int)admin_scalar($db, "SELECT COUNT(*) FROM users WHERE role='student'");
$totalTeachers = (int)admin_scalar($db, "SELECT COUNT(*) FROM users WHERE role='teacher'");
$totalCourses = (int)admin_scalar($db, "SELECT COUNT(*) FROM courses");
$publishedCourses = (int)admin_scalar($db, "SELECT COUNT(*) FROM courses WHERE status='published'");
$totalEnrollments = (int)admin_scalar($db, "SELECT COUNT(*) FROM enrollments");
$totalCertificates = (int)admin_scalar($db, "SELECT COUNT(*) FROM certificates");
$totalCommunityPosts = (int)admin_scalar($db, "SELECT COUNT(*) FROM community_posts WHERE is_deleted = 0");
$liveClassesToday = (int)admin_scalar($db, "SELECT COUNT(*) FROM live_classes WHERE DATE(scheduled_at) = CURDATE()");
$totalRevenue = (float)admin_scalar($db, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid'");
$pendingApplications = (int)admin_scalar($db, "SELECT COUNT(*) FROM instructor_applications WHERE status='pending'");

$weekAgo = date('Y-m-d', strtotime('-7 days'));
$studentsThisWeek = (int)admin_scalar($db, "SELECT COUNT(*) FROM users WHERE role='student' AND created_at >= '{$weekAgo}'");
$enrollmentsThisWeek = (int)admin_scalar($db, "SELECT COUNT(*) FROM enrollments WHERE enrolled_at >= '{$weekAgo}'");
$revenueThisWeek = (float)admin_scalar($db, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid' AND paid_at >= '{$weekAgo}'");

$recentUsers = admin_rows($db, "SELECT first_name, last_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$recentPayments = admin_rows($db, "SELECT p.amount, p.status, CONCAT(u.first_name, ' ', u.last_name) AS student_name FROM payments p JOIN users u ON u.id = p.user_id ORDER BY p.created_at DESC LIMIT 5");

$sslActive = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$phpVersion = phpversion();
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';

// Payment gateway status — config lives at lms_vareen/src/config/payments.php;
// from views/admin that is TWO levels up, and the file must exist.
$payConfigFile = __DIR__ . '/../../src/config/payments.php';
$payConfig = file_exists($payConfigFile) ? require $payConfigFile : [];
if (!is_array($payConfig)) { $payConfig = []; }
$paystackStatus = !empty($payConfig['paystack']['enabled']) && !empty($payConfig['paystack']['secret_key']);
$flutterwaveStatus = !empty($payConfig['flutterwave']['enabled']) && !empty($payConfig['flutterwave']['secret_key']);
$bankTransferStatus = !empty($payConfig['bank_transfer']['enabled']);
$gatewayOk = $paystackStatus || $flutterwaveStatus || $bankTransferStatus;
?>
<div class="dashboard-wrapper">
    <?php $admin_active='dashboard'; include __DIR__.'/_sidebar.php'; ?>
        <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title">
                <h1>Welcome, Admin</h1>
                <p><?php echo date('l, F j, Y'); ?> &bull; <span id="liveTime"><?php echo date('g:i A'); ?></span></p>
            </div>
            <button class="btn btn-logout" id="adminLogoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <div class="health-banner">
            <div class="health-item ok"><i class="fas fa-database"></i> Database Connected</div>
            <div class="health-item <?php echo $sslActive?'ok':'warn'; ?>"><i class="fas fa-lock"></i> SSL <?php echo $sslActive?'Active':'Inactive'; ?></div>
            <div class="health-item ok"><i class="fas fa-server"></i> Server: <?php echo htmlspecialchars($serverSoftware); ?></div>
            <div class="health-item ok"><i class="fab fa-php"></i> PHP <?php echo $phpVersion; ?></div>
            <div class="health-item ok"><i class="fas fa-robot"></i> AI Online</div>
            <div class="health-item <?php echo $gatewayOk?'ok':'warn'; ?>"><i class="fas fa-credit-card"></i> Payment Gateway <?php echo $gatewayOk?'Active':'Inactive'; ?></div>
        </div>
        <div class="kpi-grid">
            <div class="kpi-card"><div class="kpi-icon" style="background:#e8f4fd"><i class="fas fa-user-graduate" style="color:#4facfe"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalStudents; ?></span><span class="kpi-label">Students <small style="color:#28a745">+<?php echo $studentsThisWeek; ?></small></span></div></div>
            <div class="kpi-card"><div class="kpi-icon" style="background:#fdecea"><i class="fas fa-chalkboard-teacher" style="color:#f5576c"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalTeachers; ?></span><span class="kpi-label">Teachers</span></div></div>
            <div class="kpi-card"><div class="kpi-icon" style="background:#e9f9ef"><i class="fas fa-book" style="color:#28a745"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $publishedCourses; ?></span><span class="kpi-label">Published Courses</span></div></div>
            <div class="kpi-card"><div class="kpi-icon" style="background:#fff7e6"><i class="fas fa-user-plus" style="color:#b9770e"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalEnrollments; ?></span><span class="kpi-label">Enrollments <small style="color:#28a745">+<?php echo $enrollmentsThisWeek; ?></small></span></div></div>
            <div class="kpi-card"><div class="kpi-icon" style="background:#f3e8fd"><i class="fas fa-naira-sign" style="color:#764ba2"></i></div><div class="kpi-info"><span class="kpi-count">₦<?php echo number_format($totalRevenue, 0); ?></span><span class="kpi-label">Revenue <small style="color:#28a745">+₦<?php echo number_format($revenueThisWeek, 0); ?></small></span></div></div>
            <div class="kpi-card"><div class="kpi-icon" style="background:#e8f4fd"><i class="fas fa-certificate" style="color:#4facfe"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalCertificates; ?></span><span class="kpi-label">Certificates</span></div></div>
            <div class="kpi-card"><div class="kpi-icon" style="background:#e9f9ef"><i class="fas fa-comments" style="color:#28a745"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalCommunityPosts; ?></span><span class="kpi-label">Community Posts</span></div></div>
            <div class="kpi-card"><div class="kpi-icon" style="background:#fdecea"><i class="fas fa-video" style="color:#f5576c"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $liveClassesToday; ?></span><span class="kpi-label">Live Classes Today</span></div></div>
        </div>
        <div class="quick-actions">
            <h3>Quick Actions</h3>
            <div class="quick-grid">
                <a href="index.php?page=admin-users" class="quick-card"><i class="fas fa-user-plus"></i> Add Student</a>
                <a href="index.php?page=admin-teachers" class="quick-card"><i class="fas fa-chalkboard-teacher"></i> Add Teacher</a>
                <a href="index.php?page=admin-courses" class="quick-card"><i class="fas fa-book-open"></i> Create Course</a>
                <a href="index.php?page=admin-live" class="quick-card"><i class="fas fa-video"></i> Schedule Live Class</a>
                <a href="index.php?page=admin-notifications" class="quick-card"><i class="fas fa-bullhorn"></i> Send Announcement</a>
                <a href="index.php?page=admin-certificates" class="quick-card"><i class="fas fa-certificate"></i> Issue Certificate</a>
                <a href="index.php?page=admin-coupons" class="quick-card"><i class="fas fa-ticket-alt"></i> Create Coupon</a>
                <a href="index.php?page=admin-applications" class="quick-card"><i class="fas fa-user-check"></i> Review Applications <?php if($pendingApplications): ?><span class="badge-notify"><?php echo $pendingApplications; ?></span><?php endif; ?></a>
            </div>
        </div>
        <div class="dashboard-grid">
            <div class="dashboard-section">
                <div class="section-header"><h2>Recent Users</h2></div>
                <?php if(empty($recentUsers)): ?><div class="empty-state">No users yet</div><?php else: ?>
                <table class="admin-table"><tbody>
                    <?php foreach($recentUsers as $u): ?>
                    <tr><td><?php echo htmlspecialchars($u['first_name'].' '.$u['last_name']); ?></td><td><span class="role-badge role-<?php echo $u['role']; ?>"><?php echo $u['role']; ?></span></td><td><?php echo date('M j', strtotime($u['created_at'])); ?></td></tr>
                    <?php endforeach; ?>
                </tbody></table>
                <?php endif; ?>
            </div>
            <div class="dashboard-section">
                <div class="section-header"><h2>Recent Payments</h2></div>
                <?php if(empty($recentPayments)): ?><div class="empty-state">No payments yet</div><?php else: ?>
                <table class="admin-table"><tbody>
                    <?php foreach($recentPayments as $p): ?>
                    <tr><td><?php echo htmlspecialchars($p['student_name']); ?></td><td>₦<?php echo number_format($p['amount'], 0); ?></td><td><span class="status-pill pill-<?php echo $p['status']; ?>"><?php echo $p['status']; ?></span></td></tr>
                    <?php endforeach; ?>
                </tbody></table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){
    var timeEl=document.getElementById('liveTime');
    if(timeEl){setInterval(function(){var d=new Date();var h=d.getHours(),m=d.getMinutes(),ampm=h>=12?'PM':'AM';h=h%12||12;timeEl.textContent=h+':'+(m<10?'0':'')+m+' '+ampm;},30000);
    }
})();
</script>
