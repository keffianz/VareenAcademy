<?php
/**
 * AI Assistant API Endpoints
 * Handles lesson-based AI chat requests
 */

require_once '../classes/AIAssistant.php';
require_once '../config/ai_config.php';
require_once '../middleware/auth.php';

header('Content-Type: application/json');

try {
    // Require authentication
    $auth = checkAuth();
    
    // Only students can use AI assistant
    if ($auth['role'] !== 'student') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Only students can use the AI assistant'
        ]);
        exit;
    }
    
    $action = $_GET['action'] ?? '';
    $student_id = $auth['id'];
    $ai = new AIAssistant();
    
    switch ($action) {
        case 'my_lessons':
            // Get list of lessons student can ask about
            $lessons = $ai->getAskableLessons($student_id);
            $daily_usage = $ai->getDailyUsageCount($student_id);
            
            echo json_encode([
                'success' => true,
                'lessons' => $lessons,
                'daily_usage' => $daily_usage,
                'daily_limit' => AI_DAILY_LIMIT,
                'remaining' => max(0, AI_DAILY_LIMIT - $daily_usage)
            ]);
            break;
            
        case 'ask':
            // Ask a question about a lesson
            $lesson_id = $_POST['lesson_id'] ?? 0;
            $question = $_POST['question'] ?? '';
            
            if (!$lesson_id || !$question) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Lesson ID and question are required'
                ]);
                exit;
            }
            
            // Validate question length
            if (strlen($question) < 5) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Question must be at least 5 characters'
                ]);
                exit;
            }
            
            if (strlen($question) > 2000) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Question is too long (max 2000 characters)'
                ]);
                exit;
            }
            
            $result = $ai->askLesson($student_id, $lesson_id, $question);
            
            // Set HTTP status based on result
            if (!$result['success']) {
                if ($result['code'] === 'not_enrolled' || $result['code'] === 'not_found') {
                    http_response_code(404);
                } elseif ($result['code'] === 'limit_exceeded') {
                    http_response_code(429); // Too Many Requests
                } else {
                    http_response_code(500);
                }
            }
            
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action. Use "my_lessons" or "ask"'
            ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
