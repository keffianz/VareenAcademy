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

$firstName = trim(filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_STRING));
$lastName = trim(filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_STRING));
$email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
$phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING));
$program = trim(filter_input(INPUT_POST, 'program', FILTER_SANITIZE_STRING));
$startDate = trim(filter_input(INPUT_POST, 'startDate', FILTER_SANITIZE_STRING));

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

    // Optional email confirmation
    if (!empty(ADMIN_EMAIL)) {
        $to = ADMIN_EMAIL;
        $subject = 'New Course Application';
        $body = "Application ID: $application_id\nName: $firstName $lastName\nEmail: $email\nProgram: $program";
        @mail($to, $subject, $body, "From: noreply@" . ($_SERVER['SERVER_NAME'] ?? 'example.com'));
    }

    echo json_encode(['success' => true, 'message' => 'Application submitted', 'application_id' => $application_id]);
} catch (Exception $e) {
    error_log('Application error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

?>
<?php
// API removed for static build. See /archive/api/apply.php for the original implementation.
http_response_code(410);
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => 'API removed for static build. See /archive/api/ for original server code.'
]);
exit();
?>


