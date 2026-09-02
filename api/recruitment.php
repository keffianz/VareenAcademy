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

// Accept form-data or JSON
$data = $_POST;
if (empty($data) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
}

$required = ['first_name','last_name','email','phone','agency','position_applied'];
$errors = [];
foreach ($required as $f) {
    if (!isset($data[$f]) || trim($data[$f]) === '') $errors[] = ucfirst(str_replace('_',' ',$f)) . ' is required';
}

if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email';

if ($errors) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
    exit();
}

try {
    $pdo = get_pdo();

    // Prevent duplicate applications
    $check = $pdo->prepare('SELECT id FROM recruitment_applications WHERE email = ? AND agency = ? AND position_applied = ?');
    $check->execute([trim($data['email']), trim($data['agency']), trim($data['position_applied'])]);
    if ($check->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'You have already applied for this position']);
        exit();
    }

    $stmt = $pdo->prepare('INSERT INTO recruitment_applications (first_name, last_name, email, phone, date_of_birth, address, city, state, agency, position_applied, qualification_level, years_of_experience, specialization, previous_employment, medical_fitness, criminal_record, criminal_details, guarantor_name, guarantor_phone, guarantor_address, application_fee, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');

    $values = [
        trim($data['first_name'] ?? ''),
        trim($data['last_name'] ?? ''),
        trim($data['email'] ?? ''),
        trim($data['phone'] ?? ''),
        trim($data['date_of_birth'] ?? null),
        trim($data['address'] ?? null),
        trim($data['city'] ?? null),
        trim($data['state'] ?? null),
        trim($data['agency'] ?? ''),
        trim($data['position_applied'] ?? ''),
        trim($data['qualification_level'] ?? null),
        is_numeric($data['years_of_experience'] ?? null) ? (int)$data['years_of_experience'] : null,
        trim($data['specialization'] ?? null),
        trim($data['previous_employment'] ?? null),
        trim($data['medical_fitness'] ?? null),
        trim($data['criminal_record'] ?? null),
        trim($data['criminal_details'] ?? null),
        trim($data['guarantor_name'] ?? null),
        trim($data['guarantor_phone'] ?? null),
        trim($data['guarantor_address'] ?? null),
        trim($data['application_fee'] ?? null)
    ];

    $stmt->execute($values);
    $id = $pdo->lastInsertId();

    if (!empty(ADMIN_EMAIL)) {
        @mail(ADMIN_EMAIL, 'New Recruitment Application', 'Application ID: ' . $id, 'From: noreply@' . ($_SERVER['SERVER_NAME'] ?? 'example.com'));
    }

    echo json_encode(['success' => true, 'message' => 'Application received', 'id' => $id]);
} catch (Exception $e) {
    error_log('Recruitment error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

?>
