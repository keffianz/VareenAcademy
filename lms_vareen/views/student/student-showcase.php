<?php
requireRole('student');
require_once 'src/classes/Database.php';

$db = (new Database())->connect();
$user_id = getCurrentUserId();

// Get student's showcase posts
$stmt = $db->prepare(
    'SELECT p.*, c.name as community_name, CONCAT(u.first_name, " ", u.last_name) as author_name
     FROM community_posts p
     JOIN communities c ON c.id = p.community_id
     JOIN users u ON u.id = p.user_id
     WHERE p.user_id = :uid AND p.is_deleted = 0
     ORDER BY p.created_at DESC LIMIT 20'
);
$stmt->execute([':uid' => $user_id]);
$myPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get showcase community
$stmt2 = $db->prepare("SELECT * FROM communities WHERE slug = 'student-showcase'");
$stmt2->execute();
$showcaseCommunity = $stmt2->fetch(PDO::FETCH_ASSOC);
?>
<div class="dashboard-wrapper">
    <?php $student_active = 'student-showcase'; include __DIR__ . '/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Student Showcase</h1><p>Share your projects and get feedback</p></div>
        </div>

        <div class="dashboard-section">
            <div class="section-header"><h2>My Showcase Posts</h2></div>
            <?php if (empty($myPosts)): ?>
                <div class="empty-state"><p>You haven't shared any projects yet. Show your work to the community!</p></div>
            <?php else: ?>
                <div class="showcase-grid">
                    <?php foreach ($myPosts as $p): ?>
                        <div class="showcase-card">
                            <div class="showcase-header">
                                <h3><?php echo htmlspecialchars($p['title']); ?></h3>
                                <span class="badge"><?php echo htmlspecialchars($p['community_name']); ?></span>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars(substr($p['content'], 0, 150))); ?>...</p>
                            <div class="showcase-stats">
                                <span><i class="fas fa-heart"></i> <?php echo $p['likes_count']; ?></span>
                                <span><i class="fas fa-comment"></i> <?php echo $p['comments_count']; ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo date('M j', strtotime($p['created_at'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('studentSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>