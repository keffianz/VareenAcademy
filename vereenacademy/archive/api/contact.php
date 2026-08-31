<?php
// VAREEN Academy - Secure Contact Form Handler
// Version: 1.0
// Last Updated: 2024

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Include configuration
require_once 'config.php';

// Rate limiting
session_start();
$rate_limit_key = 'contact_form_' . $_SERVER['REMOTE_ADDR'];
$current_time = time();

if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = ['count' => 0, 'first_request' => $current_time];
}

$rate_data = $_SESSION[$rate_limit_key];

// Reset counter if more than 1 hour has passed
if ($current_time - $rate_data['first_request'] > 3600) {
    $rate_data = ['count' => 0, 'first_request' => $current_time];
}

// Check rate limit (max 5 submissions per hour)
if ($rate_data['count'] >= 5) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']);
    exit();
}

$rate_data['count']++;
$_SESSION[$rate_limit_key] = $rate_data;

// Get and sanitize input data
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
$subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
$message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

// Validate required fields
$errors = [];

if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Name must be at least 2 characters long';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please provide a valid email address';
}

if (empty($message) || strlen($message) < 10) {
    $errors[] = 'Message must be at least 10 characters long';
}

// Additional validation
if (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
    $errors[] = 'Name can only contain letters and spaces';
}

if (!empty($phone) && !preg_match('/^[\+]?[0-9\-\s\(\)]{10,15}$/', $phone)) {
    $errors[] = 'Please provide a valid phone number';
}

if (count($errors) > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
    exit();
}

try {
    // Database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Insert contact message
    $stmt = $pdo->prepare("\n        INSERT INTO contact_messages (name, email, phone, subject, message, ip_address, user_agent, created_at)\n        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())\n    ");

    $stmt->execute([
        $name,
        $email,
        $phone,
        $subject,
        $message,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);

    $message_id = $pdo->lastInsertId();

    // Send email notification (if email is configured)
    if (defined('ADMIN_EMAIL') && ADMIN_EMAIL) {
        $to = ADMIN_EMAIL;
        $email_subject = 'New Contact Form Submission - VAREEN Academy';
        $email_body = "\n        <html>\n        <head>\n            <title>New Contact Form Submission</title>\n            <style>\n                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }\n                .container { max-width: 600px; margin: 0 auto; padding: 20px; }\n                .header { background: #1e3a8a; color: white; padding: 20px; text-align: center; }\n                .content { padding: 20px; background: #f8fafc; }\n                .field { margin-bottom: 15px; }\n                .label { font-weight: bold; color: #1e3a8a; }\n            </style>\n        </head>\n        <body>\n            <div class='container'>\n                <div class='header'>\n                    <h2>New Contact Form Submission</h2>\n                    <p>VAREEN Academy</p>\n                </div>\n                <div class='content'>\n                    <div class='field'>\n                        <span class='label'>Name:</span> " . htmlspecialchars($name) . "\n                    </div>\n                    <div class='field'>\n                        <span class='label'>Email:</span> " . htmlspecialchars($email) . "\n                    </div>\n                    " . (!empty($phone) ? "<div class='field'><span class='label'>Phone:</span> " . htmlspecialchars($phone) . "</div>" : "") . "\n                    " . (!empty($subject) ? "<div class='field'><span class='label'>Subject:</span> " . htmlspecialchars($subject) . "</div>" : "") . "\n                    <div class='field'>\n                        <span class='label'>Message:</span><br>\n                        " . nl2br(htmlspecialchars($message)) . "\n                    </div>\n                    <div class='field'>\n                        <span class='label'>Submitted:</span> " . date('Y-m-d H:i:s') . "\n                    </div>\n                </div>\n            </div>\n        </body>\n        </html>\n        ";

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: VAREEN Academy <noreply@VEREENacademy.com>',
            'Reply-To: ' . $email,
            'X-Mailer: PHP/' . phpversion()
        ];

        mail($to, $email_subject, $email_body, implode("\r\n", $headers));
    }

    // Log successful submission
    error_log("Contact form submission successful: ID {$message_id} from {$email}");

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message! We will contact you soon.',
        'message_id' => $message_id
    ]);

} catch (PDOException $e) {
    // Log database error
    error_log("Database error in contact form: " . $e->getMessage());

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again later.']);
} catch (Exception $e) {
    // Log general error
    error_log("General error in contact form: " . $e->getMessage());

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again later.']);
}
?>

