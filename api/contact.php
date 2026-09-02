<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Sanitize input
$name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
$email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
$phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING));
$subject = trim(filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING));
$message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING));

$errors = [];
if (empty($name) || mb_strlen($name) < 2) $errors[] = 'Name must be at least 2 characters long';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please provide a valid email address';
if (empty($message) || mb_strlen($message) < 10) $errors[] = 'Message must be at least 10 characters long';

if ($errors) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
    exit();
}

try {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $name,
        $email,
        $phone,
        $subject,
        $message,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    $message_id = $pdo->lastInsertId();

    // Optional email notification
    if (!empty(ADMIN_EMAIL)) {
        $to = ADMIN_EMAIL;
        $email_subject = 'New Contact Form Submission';
        $email_body = "Name: " . htmlspecialchars($name) . "\nEmail: " . htmlspecialchars($email) . "\nMessage:\n" . htmlspecialchars($message);
        $headers = "From: noreply@" . ($_SERVER['SERVER_NAME'] ?? 'example.com') . "\r\n";
        @mail($to, $email_subject, $email_body, $headers);
    }

    echo json_encode(['success' => true, 'message' => 'Thank you for your message.', 'id' => $message_id]);
} catch (Exception $e) {
    error_log('Contact save error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}

?>
