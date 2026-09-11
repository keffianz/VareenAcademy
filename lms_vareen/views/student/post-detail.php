<?php
requireRole('student');
require_once 'src/classes/Community.php';

$community = new Community();
$postId = (int)($_GET['id'] ?? 0);
$post = $community->getPost($postId);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['content'])) {
    requireCsrf();
    $parent = isset($_POST['parent_comment_id']) && $_POST['parent_comment_id'] !== '' ? (int)$_POST['parent_comment_id'] : null;
    $result = $community->addComment($postId, (int)$_SESSION['user_id'], trim($_POST['content']), $parent);
    if ($result) {
        header('Location: /index.php?page=post-detail&id=' . $postId . ($parent ? '#comment-' . $result : ''));
        exit;
    }
    $message = 'Could not add comment.';
}

// Build a threaded tree from the flat comment list (supports replies)
$flatComments = $community->getComments($postId);
$commentsById = [];
foreach ($flatComments as $cm) {
    $cm['replies'] = [];
    $commentsById[(int)$cm['id']] = $cm;
}
$threadedComments = [];
foreach ($commentsById as $cm) {
    $pid = isset($cm['parent_comment_id']) ? (int)$cm['parent_comment_id'] : 0;
    if ($pid && isset($commentsById[$pid])) {
        $commentsById[$pid]['replies'][] = &$commentsById[(int)$cm['id']];
    } else {
        $threadedComments[] = &$commentsById[(int)$cm['id']];
    }
}
unset($cm);

/** Recursively render a comment and its replies. */
function renderCommentThread(array $cm, int $depth = 0): void {
    ?>
    <div class="comment-item" id="comment-<?php echo (int)$cm['id']; ?>" style="background:#fff;border:1px solid #eee;border-radius:10px;padding:12px 14px<?php echo $depth ? ';margin-left:26px;border-left:3px solid #d9e2ff' : '' ?>">
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px;flex-wrap:wrap">
            <div class="author-avatar" style="width:28px;height:28px;font-size:12px"><?php echo strtoupper(substr($cm['author_name'], 0, 1)); ?></div>
            <strong><?php echo htmlspecialchars($cm['author_name']); ?></strong>
            <span class="role-badge <?php echo $cm['author_role'] === 'teacher' ? 'role-teacher' : 'role-student'; ?>"><?php echo $cm['author_role']; ?></span>
            <small class="muted"><?php echo date('M j, g:i A', strtotime($cm['created_at'])); ?></small>
        </div>
        <p style="white-space:pre-wrap"><?php echo nl2br(htmlspecialchars($cm['content'])); ?></p>
        <div class="comment-actions" style="margin-top:6px">
            <button type="button" class="btn btn-ghost btn-sm reply-toggle" data-comment-id="<?php echo (int)$cm['id']; ?>" data-author="<?php echo htmlspecialchars($cm['author_name']); ?>"><i class="fas fa-reply"></i> Reply</button>
        </div>
        <form method="POST" class="reply-form" data-parent="<?php echo (int)$cm['id']; ?>" style="display:none;margin-top:10px">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            <input type="hidden" name="parent_comment_id" value="<?php echo (int)$cm['id']; ?>">
            <textarea name="content" rows="2" required class="form-input" placeholder="Reply to <?php echo htmlspecialchars($cm['author_name']); ?>…"></textarea>
            <button type="submit" class="btn btn-primary btn-sm" style="margin-top:6px"><i class="fas fa-paper-plane"></i> Post Reply</button>
        </form>
        <?php if (!empty($cm['replies'])): ?>
            <div class="comment-replies" style="display:flex;flex-direction:column;gap:10px;margin-top:10px">
                <?php foreach ($cm['replies'] as $reply): ?>
                    <?php renderCommentThread($reply, $depth + 1); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>
<div class="dashboard-wrapper">
    <?php $student_active = 'student-community'; include __DIR__ . '/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Discussion</h1><p><a href="/index.php?page=student-community">&larr; Back to Community Hub</a></p></div>
            <button class="btn btn-logout" id="studentLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>

        <?php if (!$post): ?>
            <div class="empty-state"><p>Post not found.</p></div>
        <?php else: ?>
            <?php if ($message): ?><div class="alert alert-warning"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

            <div class="dashboard-section">
                <div class="post-card" style="border:1px solid #e6e6ef;border-radius:12px;padding:16px;margin-bottom:16px">
                    <div class="post-header" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                        <div class="author-avatar"><?php echo strtoupper(substr($post['author_name'], 0, 1)); ?></div>
                        <div>
                            <strong><?php echo htmlspecialchars($post['author_name']); ?></strong>
                            <span class="role-badge <?php echo $post['author_role'] === 'teacher' ? 'role-teacher' : 'role-student'; ?>"><?php echo $post['author_role']; ?></span>
                            <small class="muted"><?php echo date('M j, g:i A', strtotime($post['created_at'])); ?></small>
                        </div>
                        <span class="role-badge role-teacher"><?php echo htmlspecialchars($post['community_name']); ?></span>
                    </div>
                    <h2 style="margin:12px 0 6px"><?php echo htmlspecialchars($post['title']); ?></h2>
                    <p style="white-space:pre-wrap"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                </div>
            </div>

            <div class="dashboard-section" style="margin-top:20px">
                <div class="section-header"><h2>Comments (<?php echo count($flatComments); ?>)</h2></div>

                <?php if (empty($threadedComments)): ?>
                    <div class="empty-state"><p>No comments yet. Be the first to answer!</p></div>
                <?php else: ?>
                    <div class="comments-list" style="display:flex;flex-direction:column;gap:12px">
                        <?php foreach ($threadedComments as $cm): ?>
                            <?php renderCommentThread($cm); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="form-grid" style="margin-top:16px">
                    <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                    <div class="form-group" style="grid-column:1/-1">
                        <label for="content">Write a comment</label>
                        <textarea id="content" name="content" rows="3" required class="form-input" placeholder="Share your answer or thought…"></textarea>
                    </div>
                    <div class="form-group"><button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Post Comment</button></div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('studentSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();

// Reply forms: one visible at a time, toggled from the Reply button
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.reply-toggle');
    if (!btn) return;
    var item = btn.closest('.comment-item');
    var form = item ? item.querySelector(':scope > .reply-form') : null;
    if (!form) return;
    var wasHidden = form.style.display === 'none';
    // hide all other open reply forms
    document.querySelectorAll('.reply-form').forEach(function (f) { f.style.display = 'none'; });
    if (wasHidden) {
        form.style.display = 'block';
        var ta = form.querySelector('textarea');
        if (ta) { ta.focus(); }
    }
});
</script>