<?php
requireRole('admin');
require_once 'src/classes/Database.php';
$db = (new Database())->connect();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    requireCsrf();
    if ($_POST['action'] === 'create_coupon') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discount = (float)($_POST['discount'] ?? 0);
        $type = $_POST['discount_type'] ?? 'percent';
        if ($code && $discount > 0) {
            $stmt = $db->prepare('INSERT INTO coupons (code, discount_amount, discount_type, max_uses, expires_at) VALUES (:c, :d, :t, :m, :e)');
            $stmt->execute([':c' => $code, ':d' => $discount, ':t' => $type, ':m' => (int)($_POST['max_uses'] ?? 0) ?: null, ':e' => $_POST['expires_at'] ?: null]);
            $message = 'Coupon created.';
        }
    } elseif ($_POST['action'] === 'delete_coupon') {
        $db->prepare('DELETE FROM coupons WHERE id = :id')->execute([':id' => (int)$_POST['id']]);
        $message = 'Coupon deleted.';
    }
}
$coupons = $db->query('SELECT * FROM coupons ORDER BY created_at DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="dashboard-wrapper">
    <?php $admin_active='coupons'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Coupons</h1><p>Manage discount coupons</p></div>
            <button class="btn btn-logout" id="adminLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <?php if($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <div class="dashboard-section">
            <div class="section-header"><h2>All Coupons</h2></div>
            <table class="admin-table"><thead><tr><th>Code</th><th>Discount</th><th>Uses</th><th>Expires</th><th>Actions</th></tr></thead><tbody>
                <?php foreach($coupons as $c): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($c['code']); ?></strong></td>
                    <td><?php echo $c['discount_type']==='percent'?$c['discount_amount'].'%':'₦'.$c['discount_amount']; ?></td>
                    <td><?php echo $c['used_count']; ?><?php echo $c['max_uses']?'/'.$c['max_uses']:''; ?></td>
                    <td><?php echo $c['expires_at']?date('M j, Y', strtotime($c['expires_at'])):'Never'; ?></td>
                    <td>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
                            <input type="hidden" name="action" value="delete_coupon">
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
            <div class="section-header"><h2>Create Coupon</h2></div>
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="create_coupon">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <div class="form-group"><label>Code</label><input type="text" name="code" required class="form-input" placeholder="SUMMER2024"></div>
                <div class="form-group"><label>Discount</label><input type="number" name="discount" step="0.01" required class="form-input"></div>
                <div class="form-group"><label>Type</label><select name="discount_type" class="form-input"><option value="percent">Percent</option><option value="fixed">Fixed</option></select></div>
                <div class="form-group"><label>Max Uses</label><input type="number" name="max_uses" class="form-input" placeholder="Optional"></div>
                <div class="form-group"><label>Expires</label><input type="date" name="expires_at" class="form-input"></div>
                <div class="form-group"><button type="submit" class="btn btn-primary">Create</button></div>
            </form>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('adminSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>