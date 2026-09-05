<?php
/**
 * Refund Policy (public, self-contained)
 */
$page_title = 'Refund Policy — VAREEN Academy';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title ?></title>
<style>
    body{font-family:'Segoe UI',Arial,sans-serif;line-height:1.7;color:#333;margin:0;background:#f8f9fa}
    .wrap{max-width:820px;margin:0 auto;padding:40px 20px;background:#fff;min-height:100vh;box-shadow:0 0 20px rgba(0,0,0,.05)}
    h1{color:#1e3a8a;border-bottom:3px solid #1e3a8a;padding-bottom:12px}
    h2{color:#1e3a8a;margin-top:28px;font-size:1.2rem}
    .back{display:inline-block;margin-bottom:20px;color:#1e3a8a;text-decoration:none;font-size:14px}
    p,li{font-size:15px}
    .footer{margin-top:40px;padding-top:16px;border-top:1px solid #eee;font-size:13px;color:#888;text-align:center}
</style>
</head>
<body>
<div class="wrap">
    <a class="back" href="index.php">&larr; Back to VAREEN Academy</a>
    <h1>Refund Policy</h1>
    <p><em>Last updated: <?= date('j F Y') ?></em></p>

    <h2>1. Eligibility</h2>
    <ul>
        <li>You may request a refund within <strong>7 days</strong> of purchase if you have completed <strong>less than 25%</strong> of the course.</li>
        <li>Refund requests are reviewed and processed manually by our team within 5–7 working days.</li>
        <li>Approved refunds are returned to the original payment method where possible; bank-transfer payments require your account details.</li>
    </ul>

    <h2>2. Non-Refundable Cases</h2>
    <ul>
        <li>Courses completed beyond 25%.</li>
        <li>Full-scholarship (free) enrollments — there is nothing to refund.</li>
        <li>Requests made more than 7 days after purchase.</li>
        <li>Suspension or termination of your account due to a terms violation.</li>
    </ul>

    <h2>3. How to Request a Refund</h2>
    <p>Go to <strong>My Payments</strong> in your account, find the payment, and click <strong>"Request Refund"</strong>. Our team will be notified and will review your request.</p>

    <h2>4. Coupons &amp; Scholarships</h2>
    <p>Refunds are calculated on the amount you actually paid; coupon discounts are not paid out in cash.</p>

    <h2>5. Contact</h2>
    <p>For refund questions: <strong>VEREENacademy@gmail.com</strong></p>

    <div class="footer">&copy; <?= date('Y') ?> VAREEN Academy. All rights reserved.</div>
</div>
</body>
</html>