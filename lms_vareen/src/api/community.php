<?php
/**
 * Community API — communities, channels, posts, comments, likes, reports.
 * Student, teacher & admin endpoints. CSRF enforced on POST.
 */
require_once '../classes/Database.php';
require_once '../classes/Community.php';
require_once '../classes/ActivityLog.php';
require_once '../middleware/auth.php';
header('Content-Type: application/json');

try {
    $user = checkAuth();
    $role = $user['role'] ?? '';
    $userId = (int)$user['id'];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') requireCsrf();

    $action = $_GET['action'] ?? '';
    $community = new Community();
    $activity = new ActivityLog();

    switch ($action) {

        case 'communities_list': {
            echo json_encode(['success' => true, 'communities' => $community->getAll()]);
            break;
        }

        case 'community_get': {
            $id = (int)($_GET['id'] ?? 0);
            $c = $community->getById($id);
            if (!$c) { http_response_code(404); echo json_encode(['success' => false, 'message' => 'Not found']); break; }
            $c['channels'] = $community->getChannels($id);
            echo json_encode(['success' => true, 'community' => $c]);
            break;
        }

        case 'community_create': {
            if ($role !== 'admin') { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Access denied']); break; }
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            if ($name === '' || $slug === '') { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Name and slug required']); break; }
            $id = $community->create($name, $slug, trim($_POST['description'] ?? ''), trim($_POST['icon'] ?? 'fa-users'), trim($_POST['category'] ?? 'general'));
            $activity->log($userId, 'community_created', "Created community: {$name}", 'community', $id);
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Community created']);
            break;
        }

        case 'community_update': {
            if ($role !== 'admin') { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Access denied']); break; }
            $id = (int)($_POST['id'] ?? 0);
            $community->update($id, trim($_POST['name'] ?? ''), trim($_POST['description'] ?? ''), trim($_POST['icon'] ?? 'fa-users'), trim($_POST['category'] ?? 'general'), (int)!empty($_POST['is_active']));
            $activity->log($userId, 'community_updated', "Updated community #{$id}", 'community', $id);
            echo json_encode(['success' => true, 'message' => 'Community updated']);
            break;
        }

        case 'community_delete': {
            if ($role !== 'admin') { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Access denied']); break; }
            $id = (int)($_POST['id'] ?? 0);
            $community->delete($id);
            $activity->log($userId, 'community_deleted', "Deleted community #{$id}", 'community', $id);
            echo json_encode(['success' => true, 'message' => 'Community deleted']);
            break;
        }

        case 'channel_create': {
            if ($role !== 'admin') { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Access denied']); break; }
            $cid = (int)($_POST['community_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            if (!$cid || $name === '' || $slug === '') { http_response_code(400); echo json_encode(['success' => false, 'message' => 'All fields required']); break; }
            $id = $community->createChannel($cid, $name, $slug, trim($_POST['description'] ?? ''));
            $activity->log($userId, 'channel_created', "Created channel: {$name}", 'channel', $id);
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Channel created']);
            break;
        }

        case 'channel_delete': {
            if ($role !== 'admin') { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Access denied']); break; }
            $community->deleteChannel((int)($_POST['id'] ?? 0));
            echo json_encode(['success' => true, 'message' => 'Channel deleted']);
            break;
        }

        case 'posts_list': {
            $cid = !empty($_GET['community_id']) ? (int)$_GET['community_id'] : null;
            $chid = !empty($_GET['channel_id']) ? (int)$_GET['channel_id'] : null;
            echo json_encode(['success' => true, 'posts' => $community->getPosts($cid, $chid, (int)($_GET['limit'] ?? 30), (int)($_GET['offset'] ?? 0))]);
            break;
        }

        case 'post_get': {
            $id = (int)($_GET['id'] ?? 0);
            $post = $community->getPost($id);
            if (!$post) { http_response_code(404); echo json_encode(['success' => false, 'message' => 'Not found']); break; }
            $post['comments'] = $community->getComments($id);
            echo json_encode(['success' => true, 'post' => $post]);
            break;
        }

        case 'post_create': {
            if (!in_array($role, ['student','teacher','admin'])) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Access denied']); break; }
            $cid = (int)($_POST['community_id'] ?? 0);
            $chid = !empty($_POST['channel_id']) ? (int)$_POST['channel_id'] : null;
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            if (!$cid || $title === '' || $content === '') { http_response_code(400); echo json_encode(['success' => false, 'message' => 'All fields required']); break; }
            $id = $community->createPost($cid, $chid, $userId, $title, $content);
            $activity->log($userId, 'post_created', "Post: {$title}", 'post', $id);
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Post created']);
            break;
        }

        case 'post_delete': {
            if ($role !== 'admin') { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Access denied']); break; }
            $community->deletePost((int)($_POST['id'] ?? 0));
            $activity->log($userId, 'post_deleted', 'Deleted post #' . (int)$_POST['id'], 'post', (int)$_POST['id']);
            echo json_encode(['success' => true, 'message' => 'Post deleted']);
            break;
        }

        case 'post_pin': {
            if ($role !== 'admin') { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Access denied']); break; }
            $community->pinPost((int)($_POST['id'] ?? 0), (int)!empty($_POST['pinned']));
            echo json_encode(['success' => true, 'message' => 'Post updated']);
            break;
        }

        case 'comment_add': {
            if (!in_array($role, ['student','teacher','admin'])) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Access denied']); break; }
            $pid = (int)($_POST['post_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            $parent = isset($_POST['parent_comment_id']) && $_POST['parent_comment_id'] !== '' ? (int)$_POST['parent_comment_id'] : null;
            if (!$pid || $content === '') { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Content required']); break; }
            $id = $community->addComment($pid, $userId, $content, $parent);
            echo json_encode(['success' => true, 'id' => $id, 'parent_comment_id' => $parent, 'message' => 'Comment added']);
            break;
        }

        case 'comment_delete': {
            if ($role !== 'admin') { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Access denied']); break; }
            $community->deleteComment((int)($_POST['id'] ?? 0));
            echo json_encode(['success' => true, 'message' => 'Comment deleted']);
            break;
        }

        case 'like_toggle': {
            if (!in_array($role, ['student','teacher','admin'])) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Access denied']); break; }
            $pid = (int)($_POST['post_id'] ?? 0);
            if (!$pid) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'post_id required']); break; }
            echo json_encode(['success' => true] + $community->toggleLike($pid, $userId));
            break;
        }

        case 'report_post': {
            if (!in_array($role, ['student','teacher','admin'])) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Access denied']); break; }
            $pid = (int)($_POST['post_id'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            if (!$pid || $reason === '') { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Reason required']); break; }
            $community->reportPost($pid, $userId, $reason);
            echo json_encode(['success' => true, 'message' => 'Report submitted']);
            break;
        }

        case 'reports_list': {
            if ($role !== 'admin') { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Access denied']); break; }
            echo json_encode(['success' => true, 'reports' => $community->getReports($_GET['status'] ?? 'pending')]);
            break;
        }

        case 'report_update': {
            if ($role !== 'admin') { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Access denied']); break; }
            $community->updateReportStatus((int)($_POST['id'] ?? 0), $_POST['status'] ?? '', $userId);
            echo json_encode(['success' => true, 'message' => 'Report updated']);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
