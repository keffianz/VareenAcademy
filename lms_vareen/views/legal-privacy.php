<?php
/**
 * Privacy Policy (public, self-contained)
 */
$page_title = 'Privacy Policy — VAREEN Academy';
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
    .footer{ margin-top:40px;padding-top:16px;border-top:1px solid #eee;font-size:13px;color:#888;text-align:center }
</style>
</head>
<body>
<div class="wrap">
    <a class="back" href="index.php">&larr; Back to VAREEN Academy</a>
    <h1>Privacy Policy</h1>
    <p><em>Last updated: <?= date('j F Y') ?></em></p>

    <h2>1. Information We Collect</h2>
    <ul>
        <li><strong>Account data:</strong> name, email address, phone number, and password (stored only as a bcrypt hash).</li>
        <li><strong>Learning data:</strong> course enrollment, lesson progress, quiz attempts and scores, assignment submissions, certificates earned, and live-class attendance.</li>
        <li><strong>Payment data:</strong> payment reference codes, amounts, payment method, receipts, and (for bank transfers) the payment proof you upload. We do <em>not</em> store your card number, which is handled exclusively by Paystack / Flutterwave.</li>
        <li><strong>AI assistant conversations:</strong> your questions and our responses are stored to provide the lesson assistant, enforce usage limits, and improve quality. Never share personal secrets in the assistant.</li>
    </ul>

    <h2>2. How We Use Your Information</h2>
    <ul>
        <li>To provide and operate the learning platform (enrollments, grading, certificates).</li>
        <li>To process payments and verify enrollment after successful payment.</li>
        <li>To send essential notifications about your courses and account.</li>
        <li>To maintain security: prevent fraud, abuse, and unauthorized access.</li>
    </ul>

    <h2>3. Data We Do NOT Sell</h2>
    <p>We never sell or rent your personal data to third parties. Your data is shared only with the processors required to operate the service (hosting provider, payment gateways, and email delivery) and only to the extent necessary.</p>

    <h2>4. Data Retention &amp; Deletion</h2>
    <p>We retain your data while your account is active. You may request account deletion at any time by contacting support; we will remove or anonymize your personal data within 30 days, except where we are legally required to keep records (e.g., payment receipts).</p>

    <h2>5. Security</h2>
    <p>We use HTTPS, bcrypt password hashing, per-session CSRF tokens, prepared SQL statements, server-side role checks, and restricted file uploads to protect your data. No system is 100% secure, but we follow industry-standard practices for a platform of this scale.</p>

    <h2>6. Cookies &amp; Local Storage</h2>
    <p>We use session cookies for authentication and local storage to remember UI preferences (e.g., which module sections you opened). We do not use third-party advertising cookies.</p>

    <h2>7. Children's Privacy</h2>
    <p>VAREEN Academy is intended for learners aged 13 and above. If you are under 13, please do not create an account.</p>

    <h2>8. Contact</h2>
    <p>For privacy questions or requests, contact us at <strong>VEREENacademy@gmail.com</strong>.</p>

    <div class="footer">&copy; <?= date('Y') ?> VAREEN Academy. All rights reserved.</div>
</div>
</body>
</html>