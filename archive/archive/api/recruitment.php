<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
require_once 'config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['first_name', 'last_name', 'email', 'phone', 'agency', 'position_applied'];
$errors = [];

foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
    }
}

// Additional validation
if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (isset($data['phone']) && !preg_match('/^[0-9+\-\s()]+$/', $data['phone'])) {
    $errors[] = 'Invalid phone number format';
}

$valid_agencies = ['army', 'police', 'navy', 'civil_defense', 'immigration', 'frsc', 'efcc', 'customs', 'prisons', 'fire_service', 'other'];
if (isset($data['agency']) && !in_array($data['agency'], $valid_agencies)) {
    $errors[] = 'Invalid agency selected';
}

if (isset($data['years_of_experience']) && (!is_numeric($data['years_of_experience']) || $data['years_of_experience'] < 0)) {
    $errors[] = 'Invalid years of experience';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
    exit();
}

try {
    // Database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Check if email already exists for this agency and position
    $stmt = $pdo->prepare("SELECT id FROM recruitment_applications WHERE email = ? AND agency = ? AND position_applied = ?");
    $stmt->execute([$data['email'], $data['agency'], $data['position_applied']]);

    if ($stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'You have already applied for this position']);
        exit();
    }

    // Insert recruitment application
    $stmt = $pdo->prepare("\n        INSERT INTO recruitment_applications (\n            first_name, last_name, email, phone, date_of_birth, address, city, state,\n            agency, position_applied, qualification_level, years_of_experience, specialization,\n            previous_employment, medical_fitness, criminal_record, criminal_details,\n            guarantor_name, guarantor_phone, guarantor_address, application_fee\n        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)\n    ");

    $stmt->execute([
        trim($data['first_name']),
        trim($data['last_name']),
        trim($data['email']),
        trim($data['phone']),
        isset($data['date_of_birth']) ? $data['date_of_birth'] : null,
        isset($data['address']) ? trim($data['address']) : null,
        isset($data['city']) ? trim($data['city']) : null,
        isset($data['state']) ? trim($data['state']) : null,
        $data['agency'],
        trim($data['position_applied']),
        isset($data['qualification_level']) ? trim($data['qualification_level']) : null,
        isset($data['years_of_experience']) ? (int)$data['years_of_experience'] : 0,
        isset($data['specialization']) ? trim($data['specialization']) : null,
        isset($data['previous_employment']) ? trim($data['previous_employment']) : null,
        isset($data['medical_fitness']) ? $data['medical_fitness'] : 'pending',
        isset($data['criminal_record']) ? $data['criminal_record'] : 'no',
        isset($data['criminal_details']) ? trim($data['criminal_details']) : null,
        isset($data['guarantor_name']) ? trim($data['guarantor_name']) : null,
        isset($data['guarantor_phone']) ? trim($data['guarantor_phone']) : null,
        isset($data['guarantor_address']) ? trim($data['guarantor_address']) : null,
        isset($data['application_fee']) ? $data['application_fee'] : null
    ]);

    $application_id = $pdo->lastInsertId();

    // Generate payment reference if payment is required
    $payment_reference = null;
    if (isset($data['application_fee']) && $data['application_fee'] > 0) {
        $payment_reference = 'RECRUIT-' . date('Y') . '-' . str_pad($application_id, 6, '0', STR_PAD_LEFT);
    }

    // Update payment reference if generated
    if ($payment_reference) {
        $stmt = $pdo->prepare("UPDATE recruitment_applications SET payment_reference = ? WHERE id = ?");
        $stmt->execute([$payment_reference, $application_id]);
    }

    // Send confirmation email (you can implement this)
    // sendRecruitmentApplicationEmail($data['email'], $application_id, $data);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Recruitment application submitted successfully',
        'application_id' => $application_id,
        'payment_reference' => $payment_reference,
        'data' => [
            'agency' => $data['agency'],
            'position' => $data['position_applied'],
            'payment_required' => isset($data['application_fee']) && $data['application_fee'] > 0
        ]
    ]);

} catch (PDOException $e) {
    error_log('Recruitment application error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('Recruitment application error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your application']);
}
?>

