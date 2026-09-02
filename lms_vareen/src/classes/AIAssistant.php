<?php
/**
 * AI Assistant Class
 * Handles lesson-based AI chat using Anthropic API
 */

class AIAssistant {
    private $db;
    
    public function __construct() {
        $this->db = (new Database())->connect();
    }
    
    /**
     * Get list of lessons student can ask about (only enrolled courses)
     */
    public function getAskableLessons($student_id) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT l.id, l.title, c.id as course_id, c.title as course_title
            FROM lessons l
            JOIN courses c ON l.course_id = c.id
            JOIN enrollments e ON e.course_id = c.id
            WHERE e.student_id = :student_id AND c.is_active = 1 AND l.is_active = 1
            ORDER BY c.title, l.title
        ");
        $stmt->execute([':student_id' => (int)$student_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check daily limit for a student
     */
    public function getDailyUsageCount($student_id) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM ai_conversations
            WHERE student_id = :student_id 
            AND DATE(created_at) = CURDATE()
            AND success = 1
        ");
        $stmt->execute([':student_id' => (int)$student_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * Check if student is enrolled in the lesson
     */
    private function isStudentEnrolledInLesson($student_id, $lesson_id) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            JOIN lessons l ON l.course_id = c.id
            WHERE e.student_id = :student_id AND l.id = :lesson_id
        ");
        $stmt->execute([':student_id' => (int)$student_id, ':lesson_id' => (int)$lesson_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0) > 0;
    }
    
    /**
     * Get lesson content for AI context
     */
    private function getLessonContext($lesson_id) {
        $stmt = $this->db->prepare("
            SELECT l.id, l.title, l.description, l.content, c.title as course_title
            FROM lessons l
            JOIN courses c ON l.course_id = c.id
            WHERE l.id = :lesson_id
        ");
        $stmt->execute([':lesson_id' => (int)$lesson_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Ask a question about a lesson
     */
    public function askLesson($student_id, $lesson_id, $question) {
        // 1. Verify enrollment
        if (!$this->isStudentEnrolledInLesson($student_id, $lesson_id)) {
            return [
                'success' => false,
                'message' => 'You are not enrolled in this lesson.',
                'code' => 'not_enrolled'
            ];
        }
        
        // 2. Check daily limit
        $usage = $this->getDailyUsageCount($student_id);
        if ($usage >= AI_DAILY_LIMIT) {
            return [
                'success' => false,
                'message' => 'You have reached your daily question limit (' . AI_DAILY_LIMIT . '). Please try again tomorrow.',
                'code' => 'limit_exceeded'
            ];
        }
        
        // 3. Check if API is configured
        if (ANTHROPIC_API_KEY === 'REPLACE_WITH_YOUR_KEY' || empty(ANTHROPIC_API_KEY)) {
            return [
                'success' => false,
                'message' => 'AI Assistant is not configured yet. Please contact support.',
                'code' => 'not_configured'
            ];
        }
        
        // 4. Get lesson context
        $lesson = $this->getLessonContext($lesson_id);
        if (!$lesson) {
            return [
                'success' => false,
                'message' => 'Lesson not found.',
                'code' => 'not_found'
            ];
        }
        
        // 5. Call Anthropic API
        $answer = $this->callAnthropicAPI($question, $lesson);
        
        if (!$answer) {
            // Log failed attempt
            $this->logConversation($student_id, $lesson_id, $question, null, 0);
            return [
                'success' => false,
                'message' => 'Failed to get response from AI. Please try again.',
                'code' => 'api_error'
            ];
        }
        
        // 6. Log successful conversation
        $this->logConversation($student_id, $lesson_id, $question, $answer, 1);
        
        return [
            'success' => true,
            'message' => 'Question answered successfully',
            'answer' => $answer,
            'code' => 'success'
        ];
    }
    
    /**
     * Call Anthropic API via cURL
     */
    private function callAnthropicAPI($question, $lesson) {
        $system_prompt = "You are a helpful tutor for '{$lesson['course_title']}' course, specifically for the lesson '{$lesson['title']}'.\n\n" .
                        "Lesson Context:\n" .
                        "Title: {$lesson['title']}\n" .
                        "Description: {$lesson['description']}\n\n" .
                        "Content:\n{$lesson['content']}\n\n" .
                        "Please answer questions primarily based on the lesson content above. " .
                        "Explain concepts simply and clearly. " .
                        "If asked for practice questions, provide 2-3 relevant practice questions. " .
                        "If asked for translation, you can provide Hausa translation and simple explanation. " .
                        "NEVER provide ready-made answers to graded assignments or quizzes - guide students to learn instead.";
        
        $request_body = [
            'model' => AI_MODEL,
            'max_tokens' => 1024,
            'system' => $system_prompt,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $question
                ]
            ]
        ];
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, AI_API_ENDPOINT);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, AI_TIMEOUT);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_body));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'x-api-key: ' . ANTHROPIC_API_KEY,
                'anthropic-version: 2023-06-01'
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code !== 200) {
                if (AI_DEBUG) {
                    error_log("Anthropic API Error: HTTP {$http_code} - {$response}");
                }
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (isset($data['content'][0]['text'])) {
                return $data['content'][0]['text'];
            }
            
            return null;
        } catch (Exception $e) {
            if (AI_DEBUG) {
                error_log("AI Assistant Error: " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Server-side AI assessment lock: true while the student has a fresh
     * in-progress quiz attempt. A stale attempt (older than its time limit
     * plus a 5-minute grace period) is treated as abandoned and does not
     * lock the assistant.
     */
    public function isAssessmentLocked($student_id) {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS count
                 FROM quiz_attempts qa
                 JOIN quizzes q ON qa.quiz_id = q.id
                 WHERE qa.student_id = :sid
                   AND qa.status = 'in_progress'
                   AND qa.started_at >= NOW() - INTERVAL (COALESCE(q.time_limit_minutes, 60) + 5) MINUTE"
            );
            $stmt->execute([':sid' => (int)$student_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['count'] ?? 0) > 0;
        } catch (PDOException $e) {
            // Fail closed: if we cannot verify, do not unlock during assessments
            return true;
        }
    }

    /**
     * Log conversation to database
     */
    private function logConversation($student_id, $lesson_id, $question, $answer, $success) {
        $stmt = $this->db->prepare("
            INSERT INTO ai_conversations (student_id, lesson_id, question, answer, success)
            VALUES (:student_id, :lesson_id, :question, :answer, :success)
        ");
        $stmt->execute([
            ':student_id' => (int)$student_id,
            ':lesson_id' => (int)$lesson_id,
            ':question' => $question,
            ':answer' => $answer,
            ':success' => (int)$success
        ]);
    }
}
