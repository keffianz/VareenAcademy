<?php
requireRole('student');
require_once 'src/classes/Database.php';
$db = (new Database())->connect();
$user_id = getCurrentUserId();

// Get student's payments
$stmt = $db->prepare(
    'SELECT p.*, c.title as course_title
     FROM payments p
     JOIN courses c ON c.id = p.course_id
     WHERE p.user_id = :uid
     ORDER BY p.created_at DESC LIMIT 50'
);
$stmt->execute([':uid' => $user_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$totalSpent = 0;
$successful = 0;
foreach ($payments as $p) {
    if ($p['status'] === 'paid') {
        $totalSpent += $p['amount'];
        $successful++;
    }
}
?>
<div class="dashboard-wrapper">
    <?php $student_active = 'my-payments'; include __DIR__ . '/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>My Payments</h1><p>View your payment history</p></div>
        </div>

        <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr)">
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-credit-card"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo count($payments); ?></span><span class="kpi-label">Total Payments</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-check-circle"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $successful; ?></span><span class="kpi-label">Successful</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-naira-sign"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo number_format($totalSpent, 2); ?></span><span class="kpi-label">Total Spent</span></div></div>
        </div>

        <div class="dashboard-section">
            <div class="section-header"><h2>Payment History</h2></div>
            <?php if (empty($payments)): ?>
                <div class="empty-state"><p>No payments yet. Enroll in a course to make a payment.</p></div>
            <?php else: ?>
                <table class="admin-table">
                    <thead><tr><th>Course</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['course_title']); ?></td>
                            <td>₦<?php echo number_format($p['amount'], 2); ?></td>
                            <td><span class="role-badge role-teacher"><?php echo $p['payment_method']; ?></span></td>
                            <td><span class="role-badge <?php echo $p['status']==='paid'?'role-student':($p['status']==='pending'?'role-admin':'role-teacher'); ?>"><?php echo ucfirst($p['status']); ?></span></td>
                            <td><?php echo date('M j, Y', strtotime($p['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('studentSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>