<?php
/**
 * Student Certificates Page
 * Lists certificates earned by the logged-in student (with PDF download link).
 */
requireRole('student');

require_once 'src/classes/Database.php';
require_once 'src/classes/Certificate.php';

$student_id = (int) getCurrentUserId();
$certificates = [];

try {
    $pdo = (new Database())->connect();
    $stmt = $pdo->prepare(
        'SELECT cert.id, cert.certificate_code, cert.issued_at, cert.revoked,
                c.id as course_id, c.title AS course_title, c.category AS course_category
         FROM certificates cert
         JOIN courses c ON c.id = cert.course_id
         WHERE cert.student_id = :sid
         ORDER BY cert.issued_at DESC'
    );
    $stmt->execute([':sid' => $student_id]);
    $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $certificates = [];
}

$verifyBase = 'index.php?page=verify';
$printBase  = appBasePath() . '/index.php?page=certificate-print';
?>
<div class="dashboard-wrapper">
    <?php $student_active = 'certificates'; include __DIR__ . '/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title">
                <h1>My Certificates</h1>
                <p>Certificates you have earned by completing courses. Download a PDF copy or verify a code publicly.</p>
            </div>
        </div>

        <section class="dashboard-section">
            <div class="section-header"><h2>Earned Certificates</h2></div>
            <?php if (empty($certificates)): ?>
                <div class="empty-state">
                    <i class="fas fa-certificate"></i>
                    <p>You have not earned any certificate yet.</p>
                    <p style="font-size:13px;color:#999;">Complete all the lessons in a course to earn a certificate.</p>
                </div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Certificate ID</th>
                            <th>Course</th>
                            <th>Issued</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificates as $cert): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($cert['certificate_code'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td>
                                    <?php echo htmlspecialchars($cert['course_title'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (!empty($cert['course_category'])): ?>
                                        <span class="role-badge role-teacher"><?php echo htmlspecialchars($cert['course_category'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars(date('M j, Y', strtotime($cert['issued_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if (!empty($cert['revoked'])): ?>
                                        <span class="status-pill pill-revoked">Revoked</span>
                                    <?php else: ?>
                                        <span class="status-pill pill-valid">Valid</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (empty($cert['revoked'])): ?>
                                        <a class="btn btn-sm btn-primary" href="<?php echo $printBase; ?>&code=<?php echo urlencode($cert['certificate_code']); ?>" target="_blank">
                                            <i class="fas fa-download"></i> PDF
                                        </a>
                                    <?php endif; ?>
                                    <a class="btn btn-sm btn-ghost" href="<?php echo $verifyBase; ?>&code=<?php echo urlencode($cert['certificate_code']); ?>">Verify</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>