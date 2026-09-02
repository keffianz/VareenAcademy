<?php
// api/exam.php - Exam Registration endpoint for VAREEN Academy
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

$examType = trim($_POST['examType'] ?? '');
$fullName = trim($_POST['fullName'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$state = trim($_POST['state'] ?? '');
$lga = trim($_POST['lga'] ?? '');
$additionalInfo = trim($_POST['additionalInfo'] ?? '');

$errors = [];
if ($examType === '') $errors[] = 'Exam type is required.';
if ($fullName === '') $errors[] = 'Full name is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
if ($phone === '') $errors[] = 'Phone number is required.';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
    exit();
}

try {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('INSERT INTO exam_registrations (exam_type, full_name, email, phone, address, state, lga, additional_info, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([$examType, $fullName, $email, $phone, $address, $state, $lga, $additionalInfo]);

    $id = $pdo->lastInsertId();

    if (!empty(ADMIN_EMAIL)) {
        $to = ADMIN_EMAIL;
        $subject = 'New Exam Registration - ' . $examType;
        $body = "Name: $fullName\nEmail: $email\nPhone: $phone\nExam: $examType\nAddress: $address\nState: $state\nLGA: $lga\nAdditional Info: $additionalInfo";
        @mail($to, $subject, $body, 'From: noreply@' . ($_SERVER['SERVER_NAME'] ?? 'example.com'));
    }

    echo json_encode(['success' => true, 'message' => 'Exam registration submitted successfully.', 'id' => $id]);
} catch (Exception $e) {
    error_log('Exam registration error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
}
?>