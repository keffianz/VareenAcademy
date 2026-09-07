<?php
/**
 * Teacher API — AI assistant, community moderation, announcements, grading.
 * Teacher & admin only. CSRF enforced on POST.
 */
require_once '../classes/Database.php';
require_once '../classes/Community.php';
require_once '../classes/AIAssistant.php';
require_once '../middleware/auth.php';
header('Content-Type: application/json');

try {
    $user = checkAuth();
    $role = $user['role'] ?? '';
    if (!in_array($role, ['teacher', 'admin'], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') requireCsrf();

    $action = $_GET['action'] ?? '';
    $db = (new Database())->connect();
    $userId = (int)$user['id'];
    $community = new Community();

    switch ($action) {

        case 'ai_chat': {
            $prompt = trim($_POST['prompt'] ?? '');
            $context = $_POST['context'] ?? 'general';
            if ($prompt === '') { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Prompt required']); break; }
            $response = '';
            $ai = new AIAssistant();
            if ($ai->isConfigured()) {
                $response = $ai->ask($prompt, $context);
            } else {
                $response = getTeacherAIResponse($prompt, $context);
            }
            $stmt = $db->prepare('INSERT INTO ai_conversations (user_id, role, prompt, response, context) VALUES (:u, :r, :p, :res, :c)');
            $stmt->execute([':u' => $userId, ':r' => $role, ':p' => $prompt, ':res' => $response, ':c' => $context]);
            echo json_encode(['success' => true, 'response' => $response]);
            break;
        }

        case 'ai_history': {
            $stmt = $db->prepare('SELECT * FROM ai_conversations WHERE user_id = :uid ORDER BY created_at DESC LIMIT 30');
            $stmt->execute([':uid' => $userId]);
            echo json_encode(['success' => true, 'history' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
        }

        case 'community_answer': {
            $postId = (int)($_POST['post_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            if (!$postId || $content === '') { http_response_code(400); echo json_encode(['success' => false, 'message' => 'All fields required']); break; }
            $id = $community->addComment($postId, $userId, $content);
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Answer posted']);
            break;
        }

        case 'community_pin': {
            if ($role !== 'admin') { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Admin only']); break; }
            $community->pinPost((int)($_POST['post_id'] ?? 0), (int)!empty($_POST['pinned']));
            echo json_encode(['success' => true, 'message' => 'Post updated']);
            break;
        }

        case 'community_delete_post': {
            if ($role !== 'admin') { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Admin only']); break; }
            $community->deletePost((int)($_POST['post_id'] ?? 0));
            echo json_encode(['success' => true, 'message' => 'Post deleted']);
            break;
        }

        case 'announcement_create': {
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $courseId = !empty($_POST['course_id']) ? (int)$_POST['course_id'] : null;
            if ($title === '' || $content === '') { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Title and content required']); break; }
            $stmt = $db->prepare('INSERT INTO teacher_announcements (teacher_id, course_id, title, content) VALUES (:t, :c, :ti, :co)');
            $stmt->execute([':t' => $userId, ':c' => $courseId, ':ti' => $title, ':co' => $content]);
            echo json_encode(['success' => true, 'id' => (int)$db->lastInsertId(), 'message' => 'Announcement sent']);
            break;
        }

        case 'announcements_list': {
            $stmt = $db->prepare('SELECT a.*, c.title AS course_title FROM teacher_announcements a LEFT JOIN courses c ON c.id = a.course_id WHERE a.teacher_id = :tid ORDER BY a.created_at DESC LIMIT 50');
            $stmt->execute([':tid' => $userId]);
            echo json_encode(['success' => true, 'announcements' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
        }

        case 'grading_summary': {
            $assignmentId = (int)($_GET['assignment_id'] ?? 0);
            if (!$assignmentId) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'assignment_id required']); break; }
            $stmt = $db->prepare('SELECT s.*, CONCAT(u.first_name, " ", u.last_name) AS student_name FROM assignment_submissions s JOIN users u ON u.id = s.student_id WHERE s.assignment_id = :aid ORDER BY s.submitted_at DESC');
            $stmt->execute([':aid' => $assignmentId]);
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $scores = array_filter(array_column($submissions, 'score'), function($v) { return $v !== null; });
            $avgScore = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0;
            $pending = count(array_filter($submissions, function($s) { return $s['score'] === null; }));
            echo json_encode(['success' => true, 'submissions' => $submissions, 'stats' => ['total' => count($submissions), 'graded' => count($scores), 'pending' => $pending, 'average' => $avgScore]]);
            break;
        }

        case 'submission_grade': {
            $submissionId = (int)($_POST['submission_id'] ?? 0);
            $score = (float)($_POST['score'] ?? 0);
            $feedback = trim($_POST['feedback'] ?? '');
            if (!$submissionId) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'submission_id required']); break; }
            $stmt = $db->prepare('UPDATE assignment_submissions SET score = :s, feedback = :f, graded_at = NOW() WHERE id = :id');
            $stmt->execute([':s' => $score, ':f' => $feedback, ':id' => $submissionId]);
            echo json_encode(['success' => true, 'message' => 'Grade saved']);
            break;
        }

        case 'course_students': {
            $courseId = (int)($_GET['course_id'] ?? 0);
            if (!$courseId) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'course_id required']); break; }
            $stmt = $db->prepare(
                'SELECT u.id, u.first_name, u.last_name, u.email, e.progress_percent, e.enrolled_at
                 FROM enrollments e JOIN users u ON u.id = e.user_id
                 WHERE e.course_id = :cid ORDER BY u.first_name'
            );
            $stmt->execute([':cid' => $courseId]);
            echo json_encode(['success' => true, 'students' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
        }

        case 'live_class_create': {
            $title = trim($_POST['title'] ?? '');
            $courseId = (int)($_POST['course_id'] ?? 0);
            $scheduledAt = $_POST['scheduled_at'] ?? '';
            $duration = (int)($_POST['duration_minutes'] ?? 60);
            if (!$title || !$courseId || !$scheduledAt) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'All fields required']); break; }
            $roomName = 'vareen-' . $courseId . '-' . time();
            $stmt = $db->prepare('INSERT INTO live_classes (course_id, teacher_id, title, room_name, scheduled_at, duration_minutes, status) VALUES (:c, :t, :ti, :r, :s, :d, "scheduled")');
            $stmt->execute([':c' => $courseId, ':t' => $userId, ':ti' => $title, ':r' => $roomName, ':s' => $scheduledAt, ':d' => $duration]);
            $id = (int)$db->lastInsertId();
            echo json_encode(['success' => true, 'id' => $id, 'room_name' => $roomName, 'message' => 'Live class scheduled']);
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

/**
 * Fallback rule-based AI response for teachers when no API key is configured.
 * Provides useful pre-built responses for common teaching tasks.
 */
function getTeacherAIResponse(string $prompt, string $context): string
{
    $promptLower = strtolower($prompt);

    if (strpos($promptLower, 'quiz') !== false) {
        return "Here's a suggested quiz structure:\n\n1. **Multiple Choice** (4 options) - Test recall of key concepts\n2. **True/False** - Check understanding of fundamentals\n3. **Short Answer** - Assess deeper comprehension\n\nTip: Set passing score to 70% and enable randomization for fairness.";
    }
    if (strpos($promptLower, 'rubric') !== false) {
        return "Here's a general grading rubric template:\n\n- **Excellent (90-100%)**: Complete, accurate, well-organized\n- **Good (70-89%)**: Mostly complete with minor errors\n- **Satisfactory (50-69%)**: Partially complete, needs improvement\n- **Needs Work (<50%)**: Incomplete or significant errors\n\nCustomize criteria based on assignment type.";
    }
    if (strpos($promptLower, 'announcement') !== false) {
        return "Here's an announcement template:\n\n📢 **Course Update**\n\nDear Students,\n\n[Your message here]\n\nKey points:\n• Point 1\n• Point 2\n\nBest regards,\n[Your name]";
    }
    if (strpos($promptLower, 'hausa') !== false || strpos($promptLower, 'translate') !== false) {
        return "To translate instructions to Hausa:\n\n1. Write your instruction clearly in English first\n2. Use simple sentences\n3. Consider cultural context\n\nExample:\nEnglish: 'Submit your assignment by Friday'\nHausa: 'Aika aikin zuwa Juma'a'";
    }
    if (strpos($promptLower, 'practice') !== false || strpos($promptLower, 'exercise') !== false) {
        return "Here are practice exercise ideas:\n\n1. **Recall**: Define the key term\n2. **Apply**: Solve this problem using [concept]\n3. **Analyze**: Compare X and Y\n4. **Create**: Design your own example\n\nSpace practice over time for better retention.";
    }
    if (strpos($promptLower, 'explain') !== false || strpos($promptLower, 'topic') !== false) {
        return "When explaining difficult topics:\n\n1. Start with what students already know\n2. Use analogies from everyday life\n3. Break into smaller chunks\n4. Provide visual examples\n5. Check understanding with questions\n\nRemember: if you can't explain it simply, you don't understand it well enough.";
    }
    if (strpos($promptLower, 'mistake') !== false || strpos($promptLower, 'feedback') !== false) {
        return "Effective feedback framework:\n\n1. **What went well**: Start positive\n2. **What needs improvement**: Be specific\n3. **How to improve**: Give actionable steps\n4. **Encouragement**: End on motivation\n\nExample: 'Great structure! Next time, add more examples to support your argument.'";
    }

    return "I'm your AI Teaching Assistant. I can help you with:\n\n• **Generate quizzes** from your lessons\n• **Create rubrics** for grading\n• **Draft announcements** for students\n• **Translate** instructions to Hausa\n• **Generate practice exercises**\n• **Explain difficult topics** simply\n• **Suggest feedback** patterns\n\nTry asking: 'Create a rubric for my assignment' or 'Generate a quiz on Lesson 3'";
}
