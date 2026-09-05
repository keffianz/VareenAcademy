<?php
/**
 * Payment Callback (student) — gateway redirect target, verifies server-side.
 */
$apiBase = appBasePath() . '/src/api/payments.php';
$reference = $_GET['reference'] ?? '';
?>
<div class="callback-page">
    <div class="container text-center">
        <h1>Processing your payment…</h1>
        <p class="muted">Please wait, we are confirming your payment with the gateway.</p>
        <div id="callbackResult" role="status" aria-live="polite"></div>
    </div>
</div>

<style>
    .callback-page{padding:80px 20px;min-height:60vh;background:#f8f9fa;text-align:center}
    .callback-page h1{color:#1e3a8a;margin-bottom:10px}
    .muted{color:#888}
    .result-box{margin-top:20px;padding:20px;border-radius:12px;display:inline-block;min-width:280px}
    .result-box.success{background:#e6f6ec;color:#2e9e5b}
    .result-box.error{background:#fdeaea;color:#d9534f}
    .btn{margin-top:14px}
</style>

<script>
(function () {
    var apiBase = '<?= $apiBase ?>';
    var reference = '<?= htmlspecialchars($reference, ENT_QUOTES) ?>';
    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }
    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }
    if (!reference) {
        document.getElementById('callbackResult').innerHTML = '<div class="result-box error">No payment reference provided.</div>';
    } else {
        var fd = new FormData();
        fd.append('reference', reference);
        fetch(apiBase + '?action=verify', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrf() },
            body: fd
        }).then(function (r) { return r.json(); }).then(function (r) {
            var el = document.getElementById('callbackResult');
            if (r.success) {
                el.innerHTML = '<div class="result-box success"><h3>Payment successful!</h3>'
                    + '<p>' + esc(r.message || 'You are now enrolled.') + '</p>'
                    + '<a class="btn btn-primary" href="<?= appBasePath() ?>/index.php?page=courses">Go to My Courses</a></div>';
            } else {
                el.innerHTML = '<div class="result-box error"><h3>Payment not confirmed</h3>'
                    + '<p>' + esc(r.message || 'Please check your payment or contact support.') + '</p>'
                    + '<a class="btn" href="<?= appBasePath() ?>/index.php?page=payments">View My Payments</a></div>';
            }
        }).catch(function () {
            document.getElementById('callbackResult').innerHTML = '<div class="result-box error">Network error while confirming payment.</div>';
        });
    }
})();
</script>