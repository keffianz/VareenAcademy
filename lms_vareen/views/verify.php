<?php
// Public certificate verification page (no login required).
// Supports ?code=VER-XXXXXX-XXXXXX for QR-code-linked verification.
$preFill = isset($_GET['code']) ? strtoupper(trim($_GET['code'])) : '';
if (!preg_match('/^[A-Z0-9\-]{1,40}$/', $preFill)) {
    $preFill = '';
}
$apiBase = appBasePath() . '/src/api/public.php';
?>
<div class="verify-page">
    <header class="public-header">
        <a class="brand" href="index.php?page=login">VAREEN Academy</a>
        <nav class="public-nav">
            <a href="index.php?page=instructors">Instructors</a>
            <a href="index.php?page=become-instructor">Teach with us</a>
            <a href="index.php?page=login" class="btn btn-small">Sign in</a>
        </nav>
    </header>

    <main class="verify-main">
        <h1>Certificate Verification</h1>
        <p class="verify-intro">Enter the Certificate ID shown on the certificate, or scan its QR code.</p>

        <form id="verifyForm" class="verify-form">
            <label for="certCode">Certificate ID</label>
            <div class="verify-row">
                <input type="text" id="certCode" name="code" value="<?php echo htmlspecialchars($preFill, ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="e.g. VER-7K2M9F-QX4T8B" autocomplete="off" maxlength="40" required>
                <button type="submit" class="btn btn-primary">Verify</button>
            </div>
        </form>

        <div id="verifyResult" class="verify-result" aria-live="polite"></div>

        <section id="qrSection" class="qr-section" style="display:none;">
            <h2>Verification QR code</h2>
            <p>Share this QR code — scanning it opens this verification page with the ID pre-filled.</p>
            <div id="qrBox"></div>
        </section>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    'use strict';
    var form = document.getElementById('verifyForm');
    var result = document.getElementById('verifyResult');
    var qrSection = document.getElementById('qrSection');
    var qrBox = document.getElementById('qrBox');

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function showResult(data) {
        qrSection.style.display = 'none';
        qrBox.innerHTML = '';
        if (!data || !data.found) {
            result.className = 'verify-result invalid';
            result.innerHTML = '<div class="badge badge-invalid">✕ Invalid Certificate</div>'
                + '<p>No certificate matches this ID. Check the ID and try again.</p>';
            return;
        }
        var c = data.certificate || {};
        if (data.valid) {
            result.className = 'verify-result valid';
            result.innerHTML =
                '<div class="badge badge-valid">✓ Valid Certificate</div>'
                + '<dl class="cert-details">'
                + '<dt>Certificate ID</dt><dd>' + esc(c.certificate_code) + '</dd>'
                + '<dt>Awarded to</dt><dd>' + esc(c.student_name) + '</dd>'
                + '<dt>Course</dt><dd>' + esc(c.course_title)
                + (c.course_category ? ' <span class="tag">' + esc(c.course_category) + '</span>' : '') + '</dd>'
                + '<dt>Completion status</dt><dd>Completed</dd>'
                + '<dt>Issued on</dt><dd>' + esc(c.issued_at) + '</dd>'
                + '</dl>';
        } else {
            result.className = 'verify-result invalid';
            result.innerHTML =
                '<div class="badge badge-invalid">✕ Certificate Revoked</div>'
                + '<dl class="cert-details">'
                + '<dt>Certificate ID</dt><dd>' + esc(c.certificate_code) + '</dd>'
                + '<dt>Status</dt><dd>This certificate has been revoked by the academy.</dd>'
                + '</dl>';
        }
        var url = window.location.origin + window.location.pathname + '?page=verify&code='
            + encodeURIComponent(c.certificate_code || '');
        if (typeof QRCode !== 'undefined') {
            new QRCode(qrBox, { text: url, width: 160, height: 160, correctLevel: QRCode.CorrectLevel.M });
            qrSection.style.display = 'block';
        }
    }

    function verify(code) {
        result.className = 'verify-result pending';
        result.textContent = 'Verifying…';
        fetch('<?php echo $apiBase; ?>?action=verify&code=' + encodeURIComponent(code))
            .then(function (r) { return r.json(); })
            .then(showResult)
            .catch(function () {
                result.className = 'verify-result invalid';
                result.textContent = 'Verification failed. Please try again.';
            });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var code = document.getElementById('certCode').value.trim();
        if (code) verify(code);
    });

    <?php if ($preFill !== ''): ?>
    verify(<?php echo json_encode($preFill); ?>);
    <?php endif; ?>
})();
</script>
<style>
    .verify-page { min-height: 100vh; background: #f4f6fb; font-family: 'Segoe UI', system-ui, sans-serif; }
    .public-header { display: flex; justify-content: space-between; align-items: center; padding: 14px 24px; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.08); flex-wrap: wrap; gap: 10px; }
    .brand { font-weight: 700; color: #667eea; text-decoration: none; font-size: 20px; }
    .public-nav { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
    .public-nav a { color: #444; text-decoration: none; font-size: 14px; }
    .public-nav a:hover { color: #667eea; }
    .btn-small { padding: 7px 14px; border-radius: 6px; background: #667eea; color: #fff !important; }
    .verify-main { max-width: 640px; margin: 0 auto; padding: 40px 20px 60px; }
    .verify-main h1 { color: #333; margin: 0 0 8px; font-size: 26px; }
    .verify-intro { color: #666; margin: 0 0 24px; }
    .verify-form label { display: block; font-size: 14px; font-weight: 600; color: #333; margin-bottom: 6px; }
    .verify-row { display: flex; gap: 10px; flex-wrap: wrap; }
    .verify-row input { flex: 1; min-width: 220px; padding: 12px 14px; border: 1px solid #d5daea; border-radius: 8px; font-size: 15px; }
    .btn { cursor: pointer; border: none; border-radius: 8px; font-size: 15px; }
    .btn-primary { background: #667eea; color: #fff; padding: 12px 22px; }
    .btn-primary:hover { background: #5568d3; }
    .verify-result { margin-top: 28px; background: #fff; border-radius: 10px; padding: 24px; box-shadow: 0 2px 10px rgba(0,0,0,.06); }
    .verify-result.pending { color: #666; }
    .verify-result.invalid { border-left: 5px solid #d9534f; }
    .verify-result.valid { border-left: 5px solid #2e9e5b; }
    .badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 14px; margin-bottom: 14px; }
    .badge-valid { background: #e6f6ec; color: #2e9e5b; }
    .badge-invalid { background: #fdeaea; color: #c9302c; }
    .cert-details { display: grid; grid-template-columns: 170px 1fr; gap: 8px 14px; margin: 0; }
    .cert-details dt { color: #888; font-size: 13px; font-weight: 600; }
    .cert-details dd { margin: 0; color: #333; font-size: 14px; word-break: break-word; }
    .tag { display: inline-block; background: #eef1ff; color: #667eea; border-radius: 12px; padding: 2px 10px; font-size: 12px; }
    .qr-section { margin-top: 28px; background: #fff; border-radius: 10px; padding: 24px; box-shadow: 0 2px 10px rgba(0,0,0,.06); text-align: center; }
    .qr-section h2 { font-size: 18px; color: #333; margin: 0 0 6px; }
    .qr-section p { color: #666; font-size: 14px; margin: 0 0 16px; }
    #qrBox { display: inline-block; padding: 12px; border: 1px solid #e3e7f2; border-radius: 8px; background: #fff; }
    #qrBox img { display: block; }
    @media (max-width: 480px) {
        .cert-details { grid-template-columns: 1fr; gap: 2px 0; }
        .cert-details dt { margin-top: 10px; }
        .verify-row { flex-direction: column; }
        .verify-row input { width: 100%; }
    }
</style>

