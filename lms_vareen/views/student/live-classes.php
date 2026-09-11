<?php
requireRole('student');
require_once 'src/classes/Database.php';

$db = (new Database())->connect();
$user_id = getCurrentUserId();

// Get upcoming live classes for enrolled courses
$stmt = $db->prepare(
    'SELECT l.*, c.title as course_title, CONCAT(u.first_name, " ", u.last_name) as teacher_name
     FROM live_classes l
     JOIN courses c ON c.id = l.course_id
     JOIN enrollments e ON e.course_id = c.id
     JOIN users u ON u.id = l.teacher_id
     WHERE e.student_id = :sid AND l.scheduled_at > NOW() AND l.status != "ended"
     ORDER BY l.scheduled_at ASC LIMIT 20'
);
$stmt->execute([':sid' => $user_id]);
$upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get past recordings
$stmt2 = $db->prepare(
    'SELECT l.*, c.title as course_title
     FROM live_classes l
     JOIN courses c ON c.id = l.course_id
     JOIN enrollments e ON e.course_id = c.id
     WHERE e.student_id = :sid AND l.recording_url IS NOT NULL
     ORDER BY l.scheduled_at DESC LIMIT 10'
);
$stmt2->execute([':sid' => $user_id]);
$recordings = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="dashboard-wrapper">
    <?php $student_active = 'live-classes'; include __DIR__ . '/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Live Classes</h1><p>Join virtual classroom sessions</p></div>
            <button class="btn btn-logout" id="studentLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>

        <div class="dashboard-section">
            <div class="section-header"><h2>Upcoming Classes</h2></div>
            <?php if (empty($upcoming)): ?>
                <div class="empty-state"><p>No upcoming live classes scheduled.</p></div>
            <?php else: ?>
                <div class="live-classes-list">
                    <?php foreach ($upcoming as $l): ?>
                        <div class="live-class-card">
                            <div class="class-date">
                                <span class="day"><?php echo date('d', strtotime($l['scheduled_at'])); ?></span>
                                <span class="month"><?php echo date('M', strtotime($l['scheduled_at'])); ?></span>
                            </div>
                            <div class="class-info">
                                <h3><?php echo htmlspecialchars($l['title']); ?></h3>
                                <p><i class="fas fa-book"></i> <?php echo htmlspecialchars($l['course_title']); ?></p>
                                <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($l['teacher_name']); ?></p>
                                <p><i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($l['scheduled_at'])); ?></p>
                            </div>
                            <div class="class-action">
                                <?php if (!empty($l['meeting_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($l['meeting_url']); ?>" target="_blank" class="btn btn-primary btn-sm">Join Now</a>
                                <?php else: ?>
                                    <span class="badge">Scheduled</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($recordings)): ?>
        <div class="dashboard-section" style="margin-top:20px">
            <div class="section-header"><h2>Recordings</h2></div>
            <table class="admin-table">
                <thead><tr><th>Class</th><th>Course</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($recordings as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['title']); ?></td>
                            <td><?php echo htmlspecialchars($r['course_title']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($r['scheduled_at'])); ?></td>
                            <td><a href="<?php echo htmlspecialchars($r['recording_url']); ?>" target="_blank" class="btn btn-sm btn-outline">Watch</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('studentSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>