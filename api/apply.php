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

// JSON-safe input (works for multipart form posts AND fetch/JSON bodies)
$__in = $_POST + (function () { $j = json_decode(file_get_contents('php://input'), true); return is_array($j) ? $j : []; })();
$firstName = trim((string)($__in['firstName'] ?? ''));
$lastName = trim((string)($__in['lastName'] ?? ''));
$email = trim((string)($__in['email'] ?? ''));
$email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
$phone = trim((string)($__in['phone'] ?? ''));
$program = trim((string)($__in['program'] ?? ''));
$startDate = trim((string)($__in['startDate'] ?? ''));

$errors = [];
if (empty($firstName)) $errors[] = 'First name required';
if (empty($lastName)) $errors[] = 'Last name required';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required';
if (empty($phone)) $errors[] = 'Phone required';
if (empty($program)) $errors[] = 'Program required';

if ($errors) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
    exit();
}

try {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("INSERT INTO applications (first_name, last_name, email, phone, program, start_date, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$firstName, $lastName, $email, $phone, $program, $startDate]);

    $application_id = $pdo->lastInsertId();

    // Email notification to admin (best-effort; failures are logged, never block UX)
    if (!empty(ADMIN_EMAIL)) {
        $domain = $_SERVER['SERVER_NAME'] ?? 'vereenacademy.com';
        $to = ADMIN_EMAIL;
        $email_subject = "New " . $program . " Application from " . $firstName . ' ' . $lastName;
        $esc = static function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
        $email_body = "<h3>New Training Application</h3>
            <table cellpadding='6' style='font-family:Arial,sans-serif;font-size:14px;border-collapse:collapse'>
              <tr><td><strong>Application ID:</strong></td><td>#{$application_id}</td></tr>
              <tr><td><strong>Name:</strong></td><td>{$esc($firstName . ' ' . $lastName)}</td></tr>
              <tr><td><strong>Email:</strong></td><td>{$esc($email)}</td></tr>
              <tr><td><strong>Phone:</strong></td><td>{$esc($phone)}</td></tr>
              <tr><td><strong>Program:</strong></td><td>{$esc($program)}</td></tr>
              <tr><td><strong>Preferred Start:</strong></td><td>" . ($startDate ? $esc($startDate) : '—') . "</td></tr>
              <tr><td><strong>Received:</strong></td><td>" . date('M j, Y g:i a') . "</td></tr>
            </table>
            <p style='font-size:12px;color:#888'>Review in the admin dashboard: " . $esc($domain . '/lms_vareen/index.php?page=admin-messages') . "</p>";
        $headers = "From: VAREEN Academy <noreply@{$domain}>\r\n"
                 . "Reply-To: {$email}\r\n"
                 . "MIME-Version: 1.0\r\n"
                 . "Content-Type: text/html; charset=UTF-8\r\n"
                 . "X-Mailer: PHP/" . phpversion();
        $ok = @mail($to, $email_subject, $email_body, $headers);
        if (!$ok) {
            error_log('[apply.php] Admin email notification failed to ' . $to);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Application submitted', 'application_id' => $application_id]);
} catch (Exception $e) {
    error_log('Application error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

?>
