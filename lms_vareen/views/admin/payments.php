<?php
/**
 * Admin - Payments: revenue stats, bank-transfer approvals, refund processing.
 */
requireRole('admin');
$apiBase = appBasePath() . '/src/api/payments.php';
?>
<div class="dashboard-wrapper">
    <?php $admin_active = 'payments'; include __DIR__ . '/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Payments</h1><p>Revenue, transfers and refunds</p></div>
            <button class="btn btn-logout" id="adminLogoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>

        <div id="statsRow" class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:20px"></div>

        <div class="tabs" style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
            <button class="tab-btn active" data-tab="all">All Payments</button>
            <button class="tab-btn" data-tab="transfers">Pending Bank Transfers</button>
            <button class="tab-btn" data-tab="refunds">Pending Refunds</button>
        </div>

        <section class="dashboard-section">
            <div id="payTableWrap"><p class="muted" style="color:#888">Loading…</p></div>
        </section>
    </div>
</div>

<style>
    .stats-card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
    .stats-card .label{font-size:12px;color:#888;text-transform:uppercase}
    .stats-card .value{font-size:1.5rem;font-weight:700;color:#1e3a8a}
    .tab-btn{padding:8px 14px;border-radius:8px;border:1px solid #d5daea;background:#fff;cursor:pointer;font-size:13px}
    .tab-btn.active{background:#1e3a8a;color:#fff;border-color:#1e3a8a}
    .pay-admin-table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden}
    .pay-admin-table th{text-align:left;padding:10px;color:#888;font-weight:600;border-bottom:2px solid #eee;font-size:0.8rem}
    .pay-admin-table td{padding:10px;border-bottom:1px solid #f0f0f0;font-size:0.88rem}
    .pay-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
    .pay-badge.paid{background:#e6f6ec;color:#2e9e5b}.pay-badge.pending{background:#fef3c7;color:#b45309}
    .pay-badge.failed,.pay-badge.refunded{background:#fdeaea;color:#d9534f}
    .btn-xs{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600;border:1px solid #ddd;background:#fff;cursor:pointer;margin:2px}
    .btn-xs.approve{background:#1e3a8a;color:#fff;border-color:#1e3a8a}
    .empty-state{text-align:center;padding:30px;color:#999}
    @media(max-width:768px){.pay-admin-table{display:block;overflow-x:auto}}
</style>
<script>
(function () {
    var apiBase = '<?= $apiBase ?>';
    var currentTab = 'all';

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }
    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }
    function money(n, c) {
        return (c || '₦') + Number(n || 0).toFixed(2);
    }
    function loadStats() {
        fetch(apiBase + '?action=admin_stats', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrf() },
            body: new FormData()
        }).then(function (r) { return r.json(); }).then(function (r) {
            var s = r.data || {};
            var el = document.getElementById('statsRow');
            el.innerHTML = [
                ['Today', money(s.today)], ['Week', money(s.weekly)], ['Month', money(s.monthly)],
                ['Total Revenue', money(s.total)], ['Paid', s.paid_count || 0],
                ['Pending', s.pending_count || 0], ['Failed', s.failed_count || 0], ['Refunded', s.refund_count || 0]
            ].map(function (item) {
                return '<div class="stats-card"><div class="label">' + item[0] + '</div><div class="value">' + item[1] + '</div></div>';
            }).join('');
        }).catch(function () {});
    }
    function loadAll() {
        fetch(apiBase + '?action=admin_list', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrf() },
            body: new FormData()
        }).then(function (r) { return r.json(); }).then(function (r) {
            var data = r.data || [];
            var wrap = document.getElementById('payTableWrap');
            if (!data.length) { wrap.innerHTML = '<div class="empty-state">No payments recorded.</div>'; return; }
            wrap.innerHTML = '<div style="overflow-x:auto"><table class="pay-admin-table"><thead><tr>'
                + '<th>User</th><th>Course</th><th>Amount</th><th>Method</th><th>Status</th><th>Ref</th><th>Receipt</th></tr></thead><tbody>'
                + data.map(function (p) {
                    return '<tr><td>' + esc(p.student_name) + '</td><td>' + esc(p.course_title) + '</td>'
                        + '<td>' + money(p.amount, p.currency) + '</td><td>' + esc(p.payment_method) + '</td>'
                        + '<td><span class="pay-badge ' + (p.status || '') + '">' + (p.status || '') + '</span></td>'
                        + '<td>' + esc(p.reference) + '</td><td>' + esc(p.receipt_number || '—') + '</td></tr>';
                }).join('') + '</tbody></table></div>';
        }).catch(function () {});
    }
</script>
function loadTransfers() {
        fetch(apiBase + '?action=admin_pending_transfers', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrf() },
            body: new FormData()
        }).then(function (r) { return r.json(); }).then(function (r) {
            var data = r.data || [];
            var wrap = document.getElementById('payTableWrap');
            if (!data.length) { wrap.innerHTML = '<div class="empty-state">No pending bank transfers.</div>'; return; }
            wrap.innerHTML = '<div style="overflow-x:auto"><table class="pay-admin-table"><thead><tr>'
                + '<th>Student</th><th>Course</th><th>Amount</th><th>Reference</th><th>Proof</th><th>Actions</th></tr></thead><tbody>'
                + data.map(function (p) {
                    var proof = p.payment_proof_path
                        ? '<a href="<?= appBasePath() ?>/' + p.payment_proof_path + '" target="_blank">View</a>'
                        : '—';
                    return '<tr><td>' + esc(p.student_name) + '</td><td>' + esc(p.course_title) + '</td>'
                        + '<td>' + money(p.amount, p.currency) + '</td><td>' + esc(p.reference) + '</td>'
                        + '<td>' + proof + '</td>'
                        + '<td><button class="btn-xs approve" data-approve="' + p.id + '">Approve</button>'
                        + '<button class="btn-xs" data-reject="' + p.id + '">Reject</button></td></tr>';
                }).join('') + '</tbody></table></div>';
            bindTransferActions();
        }).catch(function () {});
    }
    function loadRefunds() {
        fetch(apiBase + '?action=admin_pending_refunds', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrf() },
            body: new FormData()
        }).then(function (r) { return r.json(); }).then(function (r) {
            var data = r.data || [];
            var wrap = document.getElementById('payTableWrap');
            if (!data.length) { wrap.innerHTML = '<div class="empty-state">No pending refund requests.</div>'; return; }
            wrap.innerHTML = '<div style="overflow-x:auto"><table class="pay-admin-table"><thead><tr>'
                + '<th>Student</th><th>Course</th><th>Amount</th><th>Reason</th><th>Actions</th></tr></thead><tbody>'
                + data.map(function (r) {
                    return '<tr><td>' + esc(r.student_name) + '</td><td>' + esc(r.course_title) + '</td>'
                        + '<td>' + money(r.amount) + '</td><td>' + esc(r.reason) + '</td>'
                        + '<td><button class="btn-xs approve" data-refund="' + r.id + '" data-decision="approved">Approve</button>'
                        + '<button class="btn-xs" data-refund="' + r.id + '" data-decision="rejected">Reject</button></td></tr>';
                }).join('') + '</tbody></table></div>';
            bindRefundActions();
        }).catch(function () {});
    }
    function act(endpoint, body) {
        var fd = new FormData();
        Object.keys(body).forEach(function (k) { fd.append(k, body[k]); });
        fetch(apiBase + '?action=' + endpoint, {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrf() },
            body: fd
        }).then(function (r) { return r.json(); }).then(function (r) {
            alert(r.message || (r.success ? 'Done' : 'Failed'));
            loadStats();
            loadTransfers();
            loadRefunds();
            loadAll();
        }).catch(function () { alert('Network error'); });
    }
    function bindTransferActions() {
        document.querySelectorAll('[data-approve]').forEach(function (b) {
            b.addEventListener('click', function () {
                if (confirm('Approve this bank transfer and enroll the student?')) act('admin_approve', { payment_id: b.dataset.approve });
            });
        });
        document.querySelectorAll('[data-reject]').forEach(function (b) {
            b.addEventListener('click', function () {
                var reason = prompt('Reason for rejection:');
                if (reason) act('admin_reject', { payment_id: b.dataset.reject, reason: reason });
            });
        });
    }
    function bindRefundActions() {
        document.querySelectorAll('[data-refund]').forEach(function (b) {
            b.addEventListener('click', function () {
                var notes = prompt('Notes (optional):') || '';
                act('admin_refund_process', { refund_id: b.dataset.refund, decision: b.dataset.decision, notes: notes });
            });
        });
    }
    function loadTab() {
        if (currentTab === 'transfers') loadTransfers();
        else if (currentTab === 'refunds') loadRefunds();
        else loadAll();
    }
    document.querySelectorAll('.tab-btn').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('.tab-btn').forEach(function (x) { x.classList.remove('active'); });
            b.classList.add('active');
            currentTab = b.dataset.tab;
            loadTab();
        });
    });
    loadStats();
    loadTab();
})();
</script>