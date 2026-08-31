<?php
// VAREEN Academy - Course Application Handler
// Version: 1.0
// Last Updated: 2024

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once 'config.php';

session_start();

$rate_limit_key = 'application_form_' . $_SERVER['REMOTE_ADDR'];
$current_time = time();

if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = ['count' => 0, 'first_request' => $current_time];
}

$rate_data = $_SESSION[$rate_limit_key];

if ($current_time - $rate_data['first_request'] > 3600) {
    $rate_data = ['count' => 0, 'first_request' => $current_time];
}

if ($rate_data['count'] >= 3) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many applications. Please try again later.']);
    exit();
}

$rate_data['count']++;
$_SESSION[$rate_limit_key] = $rate_data;

// Sanitize and validate input
$first_name = sanitize_input($_POST['firstName'] ?? '');
$last_name = sanitize_input($_POST['lastName'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$phone = sanitize_input($_POST['phone'] ?? '');
$date_of_birth = $_POST['dateOfBirth'] ?? null;
$address = sanitize_input($_POST['address'] ?? '');
$city = sanitize_input($_POST['city'] ?? '');
$state = sanitize_input($_POST['state'] ?? '');
$education_level = sanitize_input($_POST['education'] ?? '');
$computer_experience = sanitize_input($_POST['computerExperience'] ?? '');
$course_interest = sanitize_input($_POST['program'] ?? '');
$preferred_schedule = sanitize_input($_POST['schedule'] ?? '');
$occupation = sanitize_input($_POST['occupation'] ?? '');
$goals = sanitize_input($_POST['goals'] ?? '');
$referral = sanitize_input($_POST['referral'] ?? '');
$special_needs = sanitize_input($_POST['specialNeeds'] ?? '');
$terms = isset($_POST['terms']) ? 1 : 0;
$newsletter = isset($_POST['newsletter']) ? 1 : 0;

// Validation
$errors = [];

if (empty($first_name) || strlen($first_name) < 2) {
    $errors[] = 'First name must be at least 2 characters';
}

if (empty($last_name) || strlen($last_name) < 2) {
    $errors[] = 'Last name must be at least 2 characters';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required';
}

if (empty($phone) || !preg_match('/^[\+]?[0-9\-\s\(\)]{10,15}$/', $phone)) {
    $errors[] = 'Valid phone number is required';
}

if (empty($address)) {
    $errors[] = 'Address is required';
}

if (empty($education_level)) {
    $errors[] = 'Education level is required';
}

if (empty($computer_experience)) {
    $errors[] = 'Computer experience level is required';
}

if (empty($course_interest)) {
    $errors[] = 'Course selection is required';
}

if (empty($preferred_schedule)) {
    $errors[] = 'Preferred schedule is required';
}

if (!$terms) {
    $errors[] = 'You must agree to the terms and conditions';
}

if (count($errors) > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM course_applications WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'An application with this email already exists']);
        exit();
    }

    // Insert application
    $stmt = $pdo->prepare("\n        INSERT INTO course_applications (\n            first_name, last_name, email, phone, date_of_birth, address, city, state,\n            education_level, computer_experience, course_interest, preferred_schedule,\n            how_did_you_hear, additional_notes, created_at\n        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())\n    ");

    $additional_notes = "Occupation: $occupation\nGoals: $goals\nSpecial Needs: $special_needs\nNewsletter: " . ($newsletter ? 'Yes' : 'No');

    $stmt->execute([
        $first_name, $last_name, $email, $phone, $date_of_birth, $address, $city, $state,
        $education_level, $computer_experience, $course_interest, $preferred_schedule,
        $referral, $additional_notes
    ]);

    $application_id = $pdo->lastInsertId();

    // Send confirmation email
    if (defined('ADMIN_EMAIL') && ADMIN_EMAIL) {
        $subject = 'VAREEN Academy - Application Received';
        $message = "\n        <html>\n        <head>\n            <title>Application Confirmation</title>\n            <style>\n                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }\n                .container { max-width: 600px; margin: 0 auto; padding: 20px; }\n                .header { background: #1e3a8a; color: white; padding: 20px; text-align: center; }\n                .content { padding: 20px; background: #f8fafc; }\n                .application-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #1e3a8a; }\n            </style>\n        </head>\n        <body>\n            <div class='container'>\n                <div class='header'>\n                    <h2>Application Received!</h2>\n                    <p>VAREEN Computer Academy & Cyber Café</p>\n                </div>\n                <div class='content'>\n                    <p>Dear $first_name $last_name,</p>\n                    <p>Thank you for your interest in our training programs! We have received your application and will review it shortly.</p>\n\n                    <div class='application-details'>\n                        <h3>Application Details:</h3>\n                        <p><strong>Application ID:</strong> $application_id</p>\n                        <p><strong>Program:</strong> $course_interest</p>\n                        <p><strong>Schedule:</strong> $preferred_schedule</p>\n                        <p><strong>Submitted:</strong> " . date('Y-m-d H:i:s') . "</p>\n                    </div>\n\n                    <p>Our admissions team will contact you within 24-48 hours to discuss next steps and payment options.</p>\n\n                    <p>If you have any questions, please contact us at:</p>\n                    <ul>\n                        <li>Phone: 08130397723</li>\n                        <li>Email: VEREENacademy@gmail.com</li>\n                    </ul>\n\n                    <p>Best regards,<br>VAREEN Academy Team</p>\n                </div>\n            </div>\n        </body>\n        </html>\n        ";

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: VAREEN Academy <noreply@VEREENacademy.com>',
            'Reply-To: ' . ADMIN_EMAIL
        ];

        mail($email, $subject, $message, implode("\r\n", $headers));

        // Notify admin
        $admin_subject = 'New Course Application - VAREEN Academy';
        $admin_message = "\n        <html>\n        <head>\n            <title>New Application</title>\n        </head>\n        <body>\n            <h2>New Course Application Received</h2>\n            <p><strong>Application ID:</strong> $application_id</p>\n            <p><strong>Name:</strong> $first_name $last_name</p>\n            <p><strong>Email:</strong> $email</p>\n            <p><strong>Phone:</strong> $phone</p>\n            <p><strong>Program:</strong> $course_interest</p>\n            <p><strong>Schedule:</strong> $preferred_schedule</p>\n            <p><strong>Education:</strong> $education_level</p>\n            <p><strong>Experience:</strong> $computer_experience</p>\n            <p><strong>Submitted:</strong> " . date('Y-m-d H:i:s') . "</p>\n        </body>\n        </html>\n        ";

        mail(ADMIN_EMAIL, $admin_subject, $admin_message, implode("\r\n", $headers));
    }

    error_log("Course application successful: ID {$application_id} from {$email}");

    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully! Check your email for confirmation.',
        'application_id' => $application_id
    ]);

} catch (PDOException $e) {
    error_log("Database error in application: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again later.']);
} catch (Exception $e) {
    error_log("General error in application: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again later.']);
}
?>

