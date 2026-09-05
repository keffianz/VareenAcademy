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
    <div class="dashboard-content" style="margin-left:0;max-width:900px;">
        <div class="dashboard-topbar">
            <div class="topbar-title">
                <h1>My Certificates</h1>
                <p>Certificates you have earned by completing courses. Download a PDF copy or verify a code publicly.</p>
            </div>
        </div>

        <section class="dashboard-section">
            <?php if (empty($certificates)): ?>
                <div class="empty-state">
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
                                        <span class="tag" style="background:#eef1ff;color:#667eea;border-radius:12px;padding:2px 10px;font-size:12px;">
                                            <?php echo htmlspecialchars($cert['course_category'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars(date('M j, Y', strtotime($cert['issued_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if (!empty($cert['revoked'])): ?>
                                        <span class="role-badge" style="background:#fdecea;color:#d9534f;">Revoked</span>
                                    <?php else: ?>
                                        <span class="role-badge" style="background:#e6f6ec;color:#2e9e5b;">Valid</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (empty($cert['revoked'])): ?>
                                        <a class="btn btn-small" style="padding:4px 12px;font-size:12px;margin-right:4px;"
                                           href="<?php echo $printBase; ?>&code=<?php echo urlencode($cert['certificate_code']); ?>"
                                           target="_blank">
                                            <i class="fas fa-download"></i> PDF
                                        </a>
                                    <?php endif; ?>
                                    <a class="btn btn-small" style="padding:4px 12px;font-size:12px;"
                                       href="<?php echo $verifyBase; ?>&code=<?php echo urlencode($cert['certificate_code']); ?>">Verify</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>
</div>
<style>
    .dashboard-content{margin-left:0!important;max-width:900px;padding:24px}
    .dashboard-topbar{display:flex;align-items:center;margin-bottom:24px}
    .topbar-title h1{margin:0;font-size:22px;color:#222}
    .topbar-title p{margin:2px 0 0;color:#777;font-size:13px}
    .dashboard-section{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
    .admin-table{width:100%;border-collapse:collapse;font-size:13px}
    .admin-table th{text-align:left;padding:10px;color:#888;font-weight:600;border-bottom:2px solid #eee}
    .admin-table td{padding:10px;border-bottom:1px solid #f0f0f0;color:#444}
    .empty-state{text-align:center;padding:30px;color:#999}
</style>