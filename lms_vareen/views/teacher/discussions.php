<?php
requireRoles(['teacher', 'admin']);
require_once 'src/classes/Database.php';
require_once 'src/classes/Community.php';

$db = (new Database())->connect();
$community = new Community();

$posts = $community->getPosts(null, null, 30, 0);
?>
<div class="dashboard-wrapper">
    <?php $teacher_active='discussions'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Discussions</h1><p>Community discussions across all channels</p></div>
        </div>
        <?php if(empty($posts)): ?><div class="empty-state"><i class="fas fa-comment-dots"></i><p>No discussions yet</p></div>
        <?php else: ?>
        <table class="admin-table"><thead><tr><th>Title</th><th>Author</th><th>Community</th><th>Likes</th><th>Comments</th><th>Date</th></tr></thead><tbody>
            <?php foreach($posts as $p): ?>
            <tr>
                <td><?php echo htmlspecialchars($p['title']); ?></td>
                <td><?php echo htmlspecialchars($p['author_name']); ?></td>
                <td><?php echo htmlspecialchars($p['community_name']); ?></td>
                <td><?php echo $p['likes_count']; ?></td>
                <td><?php echo $p['comments_count']; ?></td>
                <td><?php echo date('M j', strtotime($p['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php endif; ?>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('teacherSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>