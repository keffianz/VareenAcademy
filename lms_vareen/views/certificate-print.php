<?php
/**
 * Printable Certificate (student) — A4 landscape, printer-friendly, "Download PDF".
 */
require_once 'src/classes/Certificate.php';
require_once 'src/classes/Course.php';

$code = $_GET['code'] ?? '';
$cert = (new Certificate())->getByCode($code);

// Ownership check: only the owner may open the page (public QR verify is separate).
if (!$cert || (int) $cert['student_id'] !== (int) getCurrentUserId() || !empty($cert['revoked'])) {
    exit('<div style="padding:60px;text-align:center;font-family:sans-serif"><h2>Certificate not available</h2>'
        . '<p>This certificate does not exist, was revoked, or is not yours.</p>'
        . '<a href="index.php?page=certificates">Back to My Certificates</a></div>');
}

$course = (new Course())->getCourseById((int) $cert['course_id']);
$studentName = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$issued = date('j F Y', strtotime($cert['issued_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Certificate — <?= htmlspecialchars($studentName) ?></title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Georgia', serif; background: #e9ecef; padding: 30px; }
    .cert {
        width: 1120px; height: 794px; margin: auto; background: #fff;
        border-top: 12px solid #1e3a8a; border-bottom: 12px solid #1e3a8a;
        padding: 60px 80px; text-align: center; position: relative;
        box-shadow: 0 8px 30px rgba(0,0,0,.15);
    }
    .cert::before {
        content: ''; position: absolute; inset: 18px;
        border: 2px solid #d4af37;
    }
    .school { color: #1e3a8a; font-size: 15px; letter-spacing: 4px; text-transform: uppercase; }
    .name { color: #1e3a8a; font-size: 28px; margin: 28px 0 6px; }
    .this-is { font-size: 14px; color: #555; }
    .course-title { font-size: 26px; margin: 14px 0; font-weight: 700; }
    .detail { font-size: 14px; color: #555; max-width: 720px; margin: 0 auto; line-height: 1.6; }
    .meta { margin-top: 30px; font-size: 14px; color: #333; }
    .code { margin-top: 10px; font-family: 'Courier New', monospace; font-size: 14px; color: #666; }
    .sign-row { position: absolute; bottom: 60px; left: 80px; right: 80px; display: flex; justify-content: space-between; text-align: center; font-size: 13px; color: #333; }
    .sign-row .line { width: 220px; border-top: 1px solid #333; margin: 8px auto 6px; }
    @media print {
        body { background: #fff; padding: 0; }
        .cert { box-shadow: none; width: 100%; height: auto; }
        .no-print { display: none; }
    }
</style>
</head>
<body>
    <div class="no-print" style="text-align:center;margin-bottom:16px">
        <button onclick="window.print()" style="padding:10px 28px;font-size:15px;background:#1e3a8a;color:#fff;border:none;border-radius:8px;cursor:pointer;font-family:sans-serif">
            Download PDF / Print
        </button>
        &nbsp;
        <a href="index.php?page=certificates" style="font-family:sans-serif;font-size:15px;color:#1e3a8a">Back to Certificates</a>
    </div>

    <div class="cert">
        <div class="school">VAREEN Academy</div>
        <div class="this-is">THIS IS TO CERTIFY THAT</div>
        <div class="name"><?= htmlspecialchars($studentName) ?></div>
        <div class="this-is">has successfully completed the course</div>
        <div class="course-title"><?= htmlspecialchars($course['title'] ?? '') ?></div>
        <div class="detail">
            This certificate is awarded for the completion of all lessons and assessments
            in the above course, in accordance with the VAREEN Academy curriculum.
        </div>
        <div class="meta">Issued on <?= htmlspecialchars($issued) ?></div>
        <div class="code">Certificate No: <?= htmlspecialchars($code) ?></div>

        <div class="sign-row">
            <div>
                <div class="line"></div>
                <strong>Director of Studies</strong><br>VAREEN Academy
            </div>
            <div>
                <div class="line"></div>
                <strong>Academy Seal</strong><br><span style="font-size:26px">&#9678;</span>
            </div>
        </div>
    </div>
</body>
</html>