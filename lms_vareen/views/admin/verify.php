<?php
requireRole('admin');
require_once 'src/classes/Database.php';
$db = (new Database())->connect();
$message = '';
$cert = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['code'])) {
    $code = trim($_POST['code']);
    $stmt = $db->prepare('SELECT cert.*, CONCAT(u.first_name, " ", u.last_name) AS student_name, u.email AS student_email, c.title AS course_title FROM certificates cert JOIN users u ON u.id = cert.student_id JOIN courses c ON c.id = cert.course_id WHERE cert.certificate_code = :code');
    $stmt->execute([':code' => $code]);
    $cert = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cert) $message = 'Certificate not found.';
}
?>
<div class="dashboard-wrapper">
    <?php $admin_active='verify'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Certificate Verification</h1><p>Verify certificate authenticity</p></div>
        </div>
        <div class="dashboard-section">
            <div class="section-header"><h2>Verify Certificate</h2></div>
            <form method="POST" class="form-grid">
                <div class="form-group"><label>Certificate Code</label><input type="text" name="code" class="form-input" placeholder="Enter code..." required></div>
                <div class="form-group"><button type="submit" class="btn btn-primary">Verify</button></div>
            </form>
            <?php if($message): ?><div class="alert alert-warning" style="margin-top:15px"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            <?php if($cert): ?>
            <div style="margin-top:20px;padding:20px;background:#f0fdf4;border-radius:10px">
                <h3 style="color:#166534"><i class="fas fa-check-circle"></i> Valid Certificate</h3>
                <p><strong>Code:</strong> <?php echo htmlspecialchars($cert['certificate_code']); ?></p>
                <p><strong>Student:</strong> <?php echo htmlspecialchars($cert['student_name']); ?> (<?php echo htmlspecialchars($cert['student_email']); ?>)</p>
                <p><strong>Course:</strong> <?php echo htmlspecialchars($cert['course_title']); ?></p>
                <p><strong>Issued:</strong> <?php echo date('F j, Y', strtotime($cert['issued_at'])); ?></p>
                <p><strong>Status:</strong> <?php echo $cert['revoked']?'<span style="color:#dc2626">Revoked</span>':'<span style="color:#16a34a">Active</span>'; ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>(function(){var s=document.getElementById('adminSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();</script>