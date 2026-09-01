<?php
/**
 * Live Classes - Student view
 */

require_once 'src/classes/Enrollment.php';
require_once 'src/classes/Course.php';

requireRole('student');

$live_class_id = (int)($_GET['id'] ?? 0);

$classes = [];

$db = (new Database())->connect();

// If no id: list upcoming classes for enrolled students
if (!$live_class_id) {
    $user_id = getCurrentUserId();

    $stmt = $db->prepare("SELECT lc.*, c.title as course_title, t.first_name, t.last_name
        FROM live_classes lc
        JOIN courses c ON lc.course_id = c.id
        JOIN users t ON lc.teacher_id = t.id
        JOIN enrollments e ON e.course_id = c.id
        WHERE e.student_id = :student_id
          AND lc.scheduled_at >= NOW()
          AND lc.scheduled_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)
        ORDER BY lc.scheduled_at ASC");
    $stmt->execute([':student_id' => $user_id]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    // join page: get class to display meeting url
    $user_id = getCurrentUserId();

    $stmt = $db->prepare("SELECT lc.*, c.title as course_title
        FROM live_classes lc
        JOIN courses c ON lc.course_id = c.id
        WHERE lc.id = :id");
    $stmt->execute([':id' => $live_class_id]);
    $lc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lc) {
        http_response_code(404);
        exit('Live class not found');
    }

    $enrollment = new Enrollment();
    if (!$enrollment->isEnrolled((int)$user_id, (int)$lc['course_id'])) {
        http_response_code(403);
        exit('Not enrolled');
    }

    $classes = [$lc];
}
?>

<div class="live-classes-page">
    <div class="container">
        <h1><i class="fas fa-video"></i> Live Classes</h1>
        <p class="muted">Join your scheduled classes or watch available recordings.</p>

        <?php if (empty($classes)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <h2>No classes scheduled</h2>
                <p>Check back later.</p>
            </div>
        <?php else: ?>
            <div class="classes-list">
                <?php foreach ($classes as $lc): ?>
                    <div class="class-item">
                        <div class="class-left">
                            <div class="class-when">
                                <div class="time-label">Scheduled</div>
                                <div class="time-value"><?php echo htmlspecialchars(date('M d, Y', strtotime($lc['scheduled_at']))); ?></div>
                                <div class="time-sub"><?php echo htmlspecialchars(date('H:i A', strtotime($lc['scheduled_at']))); ?></div>
                            </div>
                        </div>

                        <div class="class-details">
                            <h3><?php echo htmlspecialchars($lc['title']); ?></h3>
                            <p><i class="fas fa-book"></i> <?php echo htmlspecialchars($lc['course_title']); ?></p>
                            <p class="muted">Status: <strong><?php echo htmlspecialchars($lc['status']); ?></strong></p>

                            <?php if (!empty($lc['meeting_url'])): ?>
                                <a class="btn btn-primary" href="<?php echo htmlspecialchars($lc['meeting_url']); ?>" target="_blank" rel="noopener">
                                    Join Class
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($lc['recording_url'])): ?>
                                <a class="btn btn-outline" href="<?php echo htmlspecialchars($lc['recording_url']); ?>" target="_blank" rel="noopener">
                                    Watch Recording
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.live-classes-page{padding:40px 0;background:#f8f9fa;min-height:calc(100vh - 100px);}
.live-classes-page h1{font-size:32px;margin-bottom:10px;color:#333;display:flex;gap:10px;align-items:center;}
.live-classes-page p.muted{color:#666;margin-bottom:25px;}
.container{max-width:1050px;margin:0 auto;padding:0 15px;}
.classes-list{display:flex;flex-direction:column;gap:15px;}
.class-item{display:flex;gap:18px;align-items:flex-start;background:#fff;border:1px solid #eee;border-radius:12px;padding:18px;}
.class-when{min-width:160px;border-right:1px solid #f0f0f0;padding-right:18px;}
.time-label{font-size:12px;color:#999;text-transform:uppercase;letter-spacing:.4px;}
.time-value{font-weight:700;color:#333;margin-top:6px;}
.time-sub{color:#667eea;margin-top:4px;font-weight:600;}
.class-details h3{margin:0 0 8px 0;color:#1a202c;font-size:18px;}
.btn{display:inline-block;border-radius:10px;padding:10px 14px;text-decoration:none;border:1px solid transparent;cursor:pointer;margin-top:10px;}
.btn-primary{background:#667eea;color:#fff;}
.btn-outline{background:transparent;border-color:#667eea;color:#667eea;margin-left:10px;}
.empty-state{text-align:center;padding:60px 20px;color:#999;background:#fff;border-radius:12px;}
.empty-state i{font-size:48px;color:#ddd;display:block;margin-bottom:10px;}
@media (max-width: 768px){.class-item{flex-direction:column;}.class-when{min-width:unset;border-right:none;padding-right:0;}}
</style>

