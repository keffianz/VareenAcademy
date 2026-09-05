<?php
/**
 * Terms of Service (public, self-contained)
 */
$page_title = 'Terms of Service — VAREEN Academy';
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
    <h1>Terms of Service</h1>
    <p><em>Last updated: <?= date('j F Y') ?></em></p>

    <h2>1. Acceptance of Terms</h2>
    <p>By creating an account or enrolling in a course, you agree to these Terms of Service. If you do not agree, please do not use the platform.</p>

    <h2>2. Accounts</h2>
    <ul>
        <li>You must provide accurate account information and keep your password confidential.</li>
        <li>One person may maintain one student account. Accounts are personal and non-transferable.</li>
        <li>We may suspend or terminate accounts that violate these terms.</li>
    </ul>

    <h2>3. Enrollment &amp; Payments</h2>
    <ul>
        <li>Paid courses require full payment (or an approved scholarship coupon) before enrollment is activated.</li>
        <li>Online payments are processed by Paystack / Flutterwave under their own terms; bank transfers are confirmed manually by our team.</li>
        <li>Refund eligibility is described in the <a href="index.php?page=legal-refund">Refund Policy</a>.</li>
    </ul>

    <h2>4. Acceptable Use</h2>
    <ul>
        <li>Do not share your account or attempt to access another user's data.</li>
        <li>Do not attempt to interfere with the platform's security features.</li>
        <li>The AI assistant may not be used to obtain answers to graded assessments; the system enforces this automatically.</li>
        <li>Do not upload inappropriate or infringing content (course work, assignments, payment proofs).</li>
    </ul>

    <h2>5. Intellectual Property</h2>
    <p>Course content, software, and branding are the property of VAREEN Academy. You receive a personal, non-exclusive, non-transferable license to access the courses you have enrolled in for personal learning.</p>

    <h2>6. AI Assistant</h2>
    <p>The AI assistant is provided on an "as-is" basis for learning support. It may occasionally produce inaccurate content and must not be relied upon as professional advice.</p>

    <h2>7. Limitation of Liability</h2>
    <p>To the maximum extent permitted by law, VAREEN Academy is not liable for indirect, incidental, or consequential damages arising from your use of the platform, including learning outcomes.</p>

    <h2>8. Changes to These Terms</h2>
    <p>We may update these terms from time to time. Continued use after changes constitutes acceptance.</p>

    <h2>9. Contact</h2>
    <p>Questions: <strong>VEREENacademy@gmail.com</strong></p>

    <div class="footer">&copy; <?= date('Y') ?> VAREEN Academy. All rights reserved.</div>
</div>
</body>
</html>