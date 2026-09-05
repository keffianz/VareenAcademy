<?php
/**
 * Checkout Page (student) — pick a payment method, apply a coupon, complete purchase.
 */
require_once 'src/classes/Course.php';
require_once 'src/classes/Enrollment.php';

$course_id = (int) ($_GET['id'] ?? 0);
$course = (new Course())->getCourseById($course_id);

if (!$course) {
    header('Location: ' . appBasePath() . '/index.php?page=courses');
    exit;
}
if ((new Enrollment())->isEnrolled(getCurrentUserId(), $course_id)) {
    header('Location: ' . appBasePath() . '/index.php?page=course-detail&id=' . $course_id);
    exit;
}

$apiBase = appBasePath() . '/src/api/payments.php';
$price   = (float) ($course['price'] ?? 0);
?>
<div class="checkout-page">
    <div class="container">
        <a href="<?= appBasePath() ?>/index.php?page=courses" class="back-link">&larr; Back to Courses</a>
        <h1>Checkout</h1>

        <div class="checkout-grid">
            <div class="checkout-summary card">
                <h2>Order Summary</h2>
                <h3><?= htmlspecialchars($course['title']) ?></h3>
                <p class="checkout-price" data-price="<?= $price ?>"><?= $price > 0 ? '₦' . number_format($price, 2) : 'FREE' ?></p>
                <div class="coupon-row">
                    <input type="text" id="couponCode" placeholder="Coupon / scholarship code">
                    <button class="btn btn-primary btn-small" id="applyCoupon" type="button">Apply</button>
                </div>
                <p id="couponMsg" class="coupon-msg" role="status" aria-live="polite"></p>
                <p id="finalPrice" class="checkout-final"></p>
            </div>

            <div class="checkout-methods">
                <h2>Payment Method</h2>
                <div id="methodList" aria-live="polite">
                    <p class="muted">Loading payment options…</p>
                </div>
                <p id="payMsg" class="coupon-msg" role="status" aria-live="polite"></p>
                <button class="btn btn-primary btn-block" id="payNow" type="button" disabled>Continue to Payment</button>
            </div>
        </div>
    </div>
</div>

<style>
    .checkout-page{padding:40px 0;min-height:calc(100vh - 100px);background:#f8f9fa}
    .checkout-grid{display:grid;grid-template-columns:1fr 1.2fr;gap:30px;margin-top:20px}
    .checkout-summary{background:#fff;border-radius:12px;padding:25px}
    .checkout-price{font-size:1.6rem;font-weight:700;color:#1e3a8a}
    .checkout-final{font-weight:600;color:#16a34a;font-size:1.1rem}
    .coupon-row{display:flex;gap:10px;margin:12px 0}
    .coupon-row input{flex:1;padding:10px 12px;border:1px solid #d5daea;border-radius:8px}
    .coupon-msg{font-size:0.9rem;color:#b45309;min-height:1.2em}
    .method-card{border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;margin-bottom:12px;cursor:pointer;transition:border .2s,background .2s}
    .method-card:hover{border-color:#1e3a8a}
    .method-card.selected{border-color:#1e3a8a;background:#eff6ff}
    .method-card h4{margin:0 0 4px}
    .method-card p{margin:0;font-size:0.85rem;color:#666}
    .bank-details{margin-top:12px;font-size:0.95rem}
    .bank-details input{margin-top:8px}
    .muted{color:#888}
    @media(max-width:768px){.checkout-grid{grid-template-columns:1fr}}
</style>
<script>
(function () {
    var apiBase = '<?= $apiBase ?>';
    var courseId = <?= $course_id ?>;
    var methods = [];
    var selectedMethod = null;
    var finalAmount = <?= $price ?>;
    var couponMsg = document.getElementById('couponMsg');

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }
    function post(action, body, onOk) {
        var fd = new FormData();
        fd.append('course_id', courseId);
        if (typeof body === 'object') {
            Object.keys(body).forEach(function (k) { fd.append(k, body[k]); });
        }
        fetch(apiBase + '?action=' + action, {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrf() },
            body: fd
        }).then(function (r) { return r.json(); }).then(function (r) {
            if (!r.success) { couponMsg.textContent = r.message || 'Request failed'; onOk && onOk(r); return; }
            couponMsg.textContent = '';
            onOk && onOk(r);
        }).catch(function () { couponMsg.textContent = 'Network error'; });
    }

    post('methods', {}, function (r) {
        methods = r.data.methods || [];
        document.getElementById('finalPrice').textContent = methods.length
            ? 'Total due: ' + (r.data.currency || '₦') + finalAmount.toFixed(2)
            : 'Total due: FREE';
        var html = '';
        if (methods.indexOf('paystack') !== -1) html += methodCard('paystack', 'Card / Paystack', 'Pay securely with card, bank transfer or USSD via Paystack.');
        if (methods.indexOf('flutterwave') !== -1) html += methodCard('flutterwave', 'Flutterwave', 'Pay with card, mobile money or bank via Flutterwave.');
        if (methods.indexOf('bank_transfer') !== -1) {
            html += methodCard('bank_transfer', 'Direct Bank Transfer', 'Transfer to the account below, then upload your receipt.');
            html += '<div class="bank-details" id="bankDetails" style="display:none"></div>';
        }
        document.getElementById('methodList').innerHTML = html ||
            '<p class="muted">No payment methods are currently enabled. Please use bank transfer or contact support.</p>';
        bindMethods(r.data);
        document.getElementById('payNow').disabled = methods.length === 0;
        if (methods.length === 0) couponMsg.textContent = 'Payments are being configured. Please try again later.';
    });

    function methodCard(id, title, desc) {
        return '<div class="method-card" data-method="' + id + '"><h4>' + title + '</h4><p>' + desc + '</p></div>';
    }
    function bindMethods(data) {
        document.querySelectorAll('.method-card').forEach(function (card) {
            card.addEventListener('click', function () {
                document.querySelectorAll('.method-card').forEach(function (c) { c.classList.remove('selected'); });
                card.classList.add('selected');
                selectedMethod = card.dataset.method;
                var bankDetails = document.getElementById('bankDetails');
                if (bankDetails) {
                    var bt = data.bank_transfer || {};
                    bankDetails.style.display = selectedMethod === 'bank_transfer' ? 'block' : 'none';
                    bankDetails.innerHTML = selectedMethod === 'bank_transfer'
                        ? '<p><strong>' + (bt.account_name || '') + '</strong><br>'
                        + (bt.bank_name || '') + '<br>Account: <strong>' + (bt.account_number || '') + '</strong></p>'
                        + '<p style="font-size:0.85rem;color:#666">' + (bt.instructions || '') + '</p>'
                        : '';
                }
            });
        });
    }
</script>
document.getElementById('applyCoupon').addEventListener('click', function () {
        var code = document.getElementById('couponCode').value.trim();
        if (!code) { couponMsg.textContent = 'Enter a coupon code'; return; }
        post('initialize', { coupon_code: code, method: 'bank_transfer' }, function (r) {
            if (r.success) {
                couponMsg.textContent = 'Scholarship applied — no payment needed.';
                finalAmount = 0;
                document.getElementById('finalPrice').textContent = 'Total due: FREE — proceed to enroll';
                document.getElementById('payNow').disabled = false;
            }
        });
    });

    document.getElementById('payNow').addEventListener('click', function () {
        if (!selectedMethod) { couponMsg.textContent = 'Please pick a payment method'; return; }
        var coupon = document.getElementById('couponCode').value.trim();
        if (finalAmount <= 0) { enrollFree(coupon); return; }
        post('initialize', { method: selectedMethod, coupon_code: coupon }, function (r) {
            if (r.success) {
                couponMsg.textContent = 'Initialized — redirecting…';
                if (r.redirect_url) { window.location.href = r.redirect_url; return; }
                if (selectedMethod === 'bank_transfer') {
                    couponMsg.textContent = 'Transfer the amount, then upload your proof below.';
                    showBankProof(r.reference, r.payment_id, coupon);
                }
            }
        });
    });

    function enrollFree(coupon) {
        post('initialize', { method: 'bank_transfer', coupon_code: coupon }, function (r) {
            if (r.success) {
                couponMsg.textContent = 'Enrolled! (full scholarship)';
                setTimeout(function () { window.location.href = '<?= appBasePath() ?>/index.php?page=course-detail&id=' + courseId; }, 1200);
            }
        });
    }

    function showBankProof(reference, paymentId) {
        var bt = document.getElementById('bankDetails');
        bt.innerHTML = '<p><strong>Reference:</strong> ' + reference + '</p>'
            + '<label for="proofFile" style="display:block;margin-top:8px">Upload receipt/screenshot</label>'
            + '<input type="file" id="proofFile" accept=".pdf,.jpg,.jpeg,.png,.webp">'
            + '<button class="btn btn-primary btn-small" id="uploadProof" type="button" style="margin-top:8px">Upload Proof</button>';
        document.getElementById('uploadProof').addEventListener('click', function () {
            var fileInput = document.getElementById('proofFile');
            if (!fileInput.files.length) { couponMsg.textContent = 'Choose a file first'; return; }
            var fd = new FormData();
            fd.append('payment_id', paymentId);
            fd.append('proof', fileInput.files[0]);
            fetch(apiBase + '?action=upload_proof', {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrf() },
                body: fd
            }).then(function (r) { return r.json(); }).then(function (r) {
                couponMsg.textContent = r.success ? r.message : (r.message || 'Upload failed');
            }).catch(function () { couponMsg.textContent = 'Upload failed'; });
        });
    }
})();
</script>