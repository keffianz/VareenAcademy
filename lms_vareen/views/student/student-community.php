<?php
requireRole('student');
require_once 'src/classes/Database.php';
require_once 'src/classes/Community.php';

$db = (new Database())->connect();
$community = new Community();
$user_id = getCurrentUserId();

$communities = $community->getAll();
$selectedCommunity = !empty($_GET['community_id']) ? (int)$_GET['community_id'] : ($communities[0]['id'] ?? null);
$posts = $selectedCommunity ? $community->getPosts($selectedCommunity, null, 20) : [];
?>
<div class="dashboard-wrapper">
    <?php $student_active = 'student-community'; include __DIR__ . '/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Community Hub</h1><p>Connect with fellow students and teachers</p></div>
        </div>

        <div class="community-container">
            <div class="community-sidebar">
                <h3>Communities</h3>
                <ul class="community-nav">
                    <?php foreach ($communities as $c): ?>
                        <li><a href="/index.php?page=student-community&community_id=<?php echo $c['id']; ?>" class="<?php echo $selectedCommunity == $c['id'] ? 'active' : ''; ?>"><i class="fas <?php echo htmlspecialchars($c['icon']); ?>"></i> <?php echo htmlspecialchars($c['name']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="community-main">
                <div class="community-header">
                    <h2><?php echo htmlspecialchars($communities[0]['name'] ?? 'Community'); ?></h2>
                    <button class="btn btn-primary btn-sm" onclick="document.getElementById('newPostModal').style.display='block'">New Post</button>
                </div>
                <?php if (empty($posts)): ?>
                    <div class="empty-state"><p>No posts yet. Be the first to start a discussion!</p></div>
                <?php else: ?>
                    <div class="posts-list">
                        <?php foreach ($posts as $p): ?>
                            <div class="post-card">
                                <div class="post-header">
                                    <div class="post-author">
                                        <div class="author-avatar"><?php echo strtoupper(substr($p['author_name'], 0, 1)); ?></div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($p['author_name']); ?></strong>
                                            <span class="role-badge <?php echo $p['author_role'] === 'teacher' ? 'role-teacher' : 'role-student'; ?>"><?php echo $p['author_role']; ?></span>
                                            <small class="muted"><?php echo date('M j, g:i A', strtotime($p['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <?php if ($p['is_pinned']): ?><span class="badge"><i class="fas fa-thumbtack"></i> Pinned</span><?php endif; ?>
                                </div>
                                <h3 class="post-title"><?php echo htmlspecialchars($p['title']); ?></h3>
                                <p class="post-content"><?php echo nl2br(htmlspecialchars(substr($p['content'], 0, 200))); ?></p>
                                <div class="post-actions">
                                    <button class="btn btn-sm btn-outline" onclick="toggleLike(<?php echo $p['id']; ?>)"><i class="fas fa-heart"></i> <?php echo $p['likes_count']; ?></button>
                                    <a href="/index.php?page=post-detail&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-comment"></i> <?php echo $p['comments_count']; ?></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="newPostModal" class="modal" style="display:none">
    <div class="modal-content">
        <div class="modal-header"><h3>Create New Post</h3><button onclick="document.getElementById('newPostModal').style.display='none'" class="modal-close">&times;</button></div>
        <form id="newPostForm">
            <input type="hidden" name="community_id" value="<?php echo $selectedCommunity; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            <div class="form-group"><label>Title</label><input type="text" name="title" required class="form-input"></div>
            <div class="form-group"><label>Content</label><textarea name="content" rows="4" required class="form-input"></textarea></div>
            <button type="submit" class="btn btn-primary">Post</button>
        </form>
    </div>
</div>

<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('studentSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
document.getElementById('newPostForm').addEventListener('submit',async function(e){e.preventDefault();const fd=new FormData(this);const res=await fetch('/lms_vareen/src/api/community.php?action=post_create',{method:'POST',body:fd});const data=await res.json();if(data.success){location.reload();}else{alert(data.message||'Error');}});
async function toggleLike(postId){const fd=new FormData();fd.append('post_id',postId);const res=await fetch('/lms_vareen/src/api/community.php?action=like_toggle',{method:'POST',body:fd});const data=await res.json();if(data.success){location.reload();}}
</script>