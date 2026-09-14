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

// Sanitize input (JSON-safe: works for form posts AND fetch/JSON bodies)
$__in = $_POST + (function () { $j = json_decode(file_get_contents('php://input'), true); return is_array($j) ? $j : []; })();
$name = trim((string)($__in['name'] ?? ''));
$email = trim((string)($__in['email'] ?? ''));
$email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
$phone = trim((string)($__in['phone'] ?? ''));
$subject = trim((string)($__in['subject'] ?? ''));
$message = trim((string)($__in['message'] ?? ''));

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

    // Email notification to admin (best-effort; failures are logged, never block UX)
    if (!empty(ADMIN_EMAIL)) {
        $domain = $_SERVER['SERVER_NAME'] ?? 'vereenacademy.com';
        $siteName = 'VAREEN Academy';
        $to = ADMIN_EMAIL;
        $email_subject = 'New Contact Message from ' . $name;
        $esc = static function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
        $email_body = "<h3>New Contact Form Submission</h3>
            <table cellpadding='6' style='font-family:Arial,sans-serif;font-size:14px;border-collapse:collapse'>
              <tr><td><strong>Name:</strong></td><td>{$esc($name)}</td></tr>
              <tr><td><strong>Email:</strong></td><td>{$esc($email)}</td></tr>
              <tr><td><strong>Phone:</strong></td><td>" . ($phone !== '' ? $esc($phone) : '—') . "</td></tr>
              <tr><td><strong>Subject:</strong></td><td>" . ($subject !== '' ? $esc($subject) : '—') . "</td></tr>
              <tr><td><strong>Message:</strong></td><td>" . nl2br($esc($message)) . "</td></tr>
              <tr><td><strong>Received:</strong></td><td>" . date('M j, Y g:i a') . "</td></tr>
            </table>
            <p style='font-size:12px;color:#888'>View in the admin dashboard: " . $esc($domain . '/lms_vareen/index.php?page=admin-messages') . "</p>";
        $headers = "From: VAREEN Academy <noreply@{$domain}>\r\n"
                 . "Reply-To: {$email}\r\n"
                 . "MIME-Version: 1.0\r\n"
                 . "Content-Type: text/html; charset=UTF-8\r\n"
                 . "X-Mailer: PHP/" . phpversion();
        $ok = @mail($to, $email_subject, $email_body, $headers);
        if (!$ok) {
            error_log('[contact.php] Admin email notification failed to ' . $to);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Thank you for your message.', 'id' => $message_id]);
} catch (Exception $e) {
    error_log('Contact save error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}

?>
