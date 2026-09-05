<?php
/**
 * My Payments (student) — payment history, receipts, refund requests.
 */
$apiBase = appBasePath() . '/src/api/payments.php';
?>
<div class="payments-page">
    <div class="container">
        <h1>My Payments</h1>
        <p class="muted">Your enrollment payments and their status.</p>

        <div id="paymentsList" aria-live="polite">
            <p class="muted">Loading your payments…</p>
        </div>
    </div>
</div>

<style>
    .payments-page{padding:40px 0;min-height:calc(100vh - 100px);background:#f8f9fa}
    .pay-table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.05)}
    .pay-table th{text-align:left;padding:12px;color:#888;font-weight:600;border-bottom:2px solid #eee;font-size:0.85rem}
    .pay-table td{padding:12px;border-bottom:1px solid #f0f0f0}
    .pay-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
    .pay-badge.paid{background:#e6f6ec;color:#2e9e5b}
    .pay-badge.pending{background:#fef3c7;color:#b45309}
    .pay-badge.failed,.pay-badge.refunded{background:#fdeaea;color:#d9534f}
    .refund-form{margin-top:8px;display:flex;gap:8px;flex-wrap:wrap}
    .refund-form input{flex:1;min-width:160px;padding:8px 10px;border:1px solid #d5daea;border-radius:8px}
    .empty-state{text-align:center;padding:40px;color:#999}
    .muted{color:#888}
    @media(max-width:640px){.pay-table{display:block;overflow-x:auto}}
</style>
<script>
(function () {
    var apiBase = '<?= $apiBase ?>';
    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }
    function load() {
        fetch(apiBase + '?action=my', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrf() },
            body: new FormData()
        }).then(function (r) { return r.json(); }).then(function (r) {
            var el = document.getElementById('paymentsList');
            if (!r.success || !r.data || !r.data.length) {
                el.innerHTML = '<div class="empty-state">You have no payments yet.</div>';
                return;
            }
            var rows = r.data.map(function (p) {
                var badge = '<span class="pay-badge ' + (p.status || 'pending') + '">' + (p.status || 'pending') + '</span>';
                var refund = (p.status === 'paid')
                    ? '<div class="refund-form"><input type="text" placeholder="Reason for refund" data-reason>'
                      + '<button class="btn btn-primary btn-small" data-refund="' + p.id + '">Request Refund</button></div>'
                    : '';
                return '<tr><td>' + esc(p.course_title || 'Course') + '</td>'
                    + '<td>' + (p.currency || '₦') + Number(p.amount || 0).toFixed(2) + '</td>'
                    + '<td>' + esc(p.payment_method || '') + '</td>'
                    + '<td>' + badge + '</td>'
                    + '<td>' + esc(p.reference || '') + '</td>'
                    + '<td>' + esc(p.receipt_number || '—') + '</td>'
                    + '<td>' + refund + '</td></tr>';
            }).join('');
            el.innerHTML = '<div style="overflow-x:auto"><table class="pay-table">'
                + '<thead><tr><th>Course</th><th>Amount</th><th>Method</th><th>Status</th><th>Reference</th><th>Receipt</th><th>Action</th></tr></thead>'
                + '<tbody>' + rows + '</tbody></table></div>';
            bindRefunds();
        }).catch(function () {
            document.getElementById('paymentsList').innerHTML = '<div class="empty-state">Could not load payments.</div>';
        });
    }
    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }
    function bindRefunds() {
        document.querySelectorAll('[data-refund]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = btn.parentNode.querySelector('[data-reason]');
                var reason = input ? input.value.trim() : '';
                var fd = new FormData();
                fd.append('payment_id', btn.dataset.refund);
                fd.append('reason', reason);
                fetch(apiBase + '?action=request_refund', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrf() },
                    body: fd
                }).then(function (r) { return r.json(); }).then(function (r) {
                    alert(r.message || (r.success ? 'Refund requested' : 'Request failed'));
                    if (r.success) load();
                }).catch(function () { alert('Network error'); });
            });
        });
    }
    load();
})();
</script>