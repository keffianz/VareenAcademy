<?php
/** Part 7 E2E: functional test of comment + threaded reply flow against the live DB. */
error_reporting(E_ALL);
ini_set('display_errors', '1');
$base = 'C:/Users/user/Desktop/word/VareenAcademy/lms_vareen/';
$log = [];
$fail = 0;
$GLOBALS['base'] = $base;
function out($m) { $GLOBALS['log'][] = $m; }
function check($cond, $label) {
    global $fail;
    out(($cond ? '[PASS] ' : '[FAIL] ') . $label);
    if (!$cond) $fail++;
}
$log[] = 'E2E runner started; base=' . $base;

require $base . 'src/config/database.php';
require $base . 'src/classes/Community.php';
$log[] = 'requires OK';

try {
    $community = new Community();
    $db = Database::getInstance()->connect();
    $student = $db->query("SELECT id FROM users WHERE role='student' AND is_active=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $teacher = $db->query("SELECT id FROM users WHERE role='teacher' AND is_active=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    check($student && $teacher, 'Seeded student & teacher users found');

            // createPost($communityId, $channelId, $userId, $title, $content) — confirmed signature.
    $postId = $community->createPost(1, null, (int)$student['id'], 'E2E test post ' . uniqid(), 'Part 7 functional verification body.');
    check((int)$postId > 0, "createPost() returns new id ($postId)");

    $commentId = $community->addComment($postId, (int)$student['id'], 'Top-level comment from E2E test');
    check((int)$commentId > 0, 'addComment() top-level returns new id (' . $commentId . ')');

    $replyId = $community->addComment($postId, (int)$teacher['id'], 'Threaded reply from E2E test', $commentId);
    check((int)$replyId > 0 && (int)$replyId !== (int)$commentId, 'addComment() reply returns new id (' . $replyId . ')');

    $reply2Id = $community->addComment($postId, (int)$student['id'], 'Nested reply level 2', $replyId);
    check((int)$reply2Id > 0, 'addComment() nested reply returns id (' . $reply2Id . ')');

    $comments = $community->getComments($postId);
    $byId = [];
    foreach ($comments as $cm) $byId[(int)$cm['id']] = $cm;
    check(count($comments) === 3, 'getComments() returns all 3 comments (' . count($comments) . ')');
    check(isset($byId[$replyId]) && (int)$byId[$replyId]['parent_comment_id'] === (int)$commentId, 'Reply parent_comment_id links to top-level comment');
    check(isset($byId[$reply2Id]) && (int)$byId[$reply2Id]['parent_comment_id'] === (int)$replyId, 'Nested reply links to level-1 reply');

            $otherPostId = $community->createPost(1, null, (int)$student['id'], 'E2E other post ' . uniqid(), 'x');
    $badReply = $community->addComment($otherPostId, (int)$student['id'], 'cross-post attempt', $commentId);
    $otherComments = $community->getComments($otherPostId);
    $ok = isset($byId[(int)$badReply]) === false && count($otherComments) === 1;
    if ($ok && $reply = $otherComments[0]) $ok = ($reply['parent_comment_id'] === null);
    check($ok, 'Cross-post parent falls back to top-level comment');

    $post = $community->getPost($postId);
    check($post && (int)$post['comments_count'] === 3, "getPost() visible, comments_count={$post['comments_count']}");

    $db->prepare('DELETE FROM community_posts WHERE id = :p')->execute([':p' => $postId]);
    $db->prepare('DELETE FROM community_posts WHERE id = :p')->execute([':p' => $otherPostId]);
    out('Cleanup: E2E posts removed');

} catch (Throwable $e) {
    out('[FAIL] Exception: ' . $e->getMessage() . ' @ line ' . $e->getLine());
    $fail++;
}

$log[] = 'E2E runner finished; fail=' . $fail;
$log[] = '=== ' . ($fail === 0 ? 'PART 7 E2E: ALL TESTS PASSED' : "PART 7 E2E: $fail TEST(S) FAILED") . ' ===';
$out = implode("\n", $log) . "\n";
echo $out;
file_put_contents(__DIR__ . '/part7_e2e_result.txt', $out);
file_put_contents(__DIR__ . '/_e2e_run.log', 'E2E run completed fail=' . $fail . ' ts=' . time() . "\n");

