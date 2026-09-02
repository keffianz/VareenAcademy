<?php
/**
 * Public API - endpoints that work WITHOUT login.
 *  - instructors      : public instructor directory (name, specialization, bio, courses taught)
 *  - verify           : public certificate verification by unguessable code
 *  - apply_instructor : "Become an Instructor" application (POST, CSRF protected)
 *
 * Only non-sensitive data is returned: no emails, user IDs, or account status.
 */

require_once '../classes/Database.php';
require_once '../middleware/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// State-changing actions require a valid CSRF token even for guests.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
}

try {
    $db = (new Database())->connect();

    switch ($action) {

        case 'instructors': {
            $stmt = $db->prepare(
                "SELECT u.id, u.first_name, u.last_name, u.specialization, u.bio, u.profile_image,
                        (SELECT COUNT(*) FROM courses c WHERE c.teacher_id = u.id AND c.is_active = 1) AS course_count
                 FROM users u
                 WHERE u.role = 'teacher' AND u.is_active = 1
                 ORDER BY course_count DESC, u.first_name, u.last_name
                 LIMIT 100"
            );
            $stmt->execute();
            $instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Attach public course titles for each instructor
            $cStmt = $db->prepare(
                'SELECT title, category FROM courses WHERE teacher_id = :tid AND is_active = 1 ORDER BY created_at DESC LIMIT 20'
            );
            foreach ($instructors as &$ins) {
                $cStmt->execute([':tid' => (int)$ins['id']]);
                $ins['courses'] = $cStmt->fetchAll(PDO::FETCH_ASSOC);
                unset($ins['id']); // internal user id is not public data
            }
            unset($ins);

            echo json_encode(['success' => true, 'instructors' => $instructors]);
            break;
        }

        case 'verify': {
            $code = strtoupper(trim($_GET['code'] ?? ''));
            if ($code === '' || strlen($code) > 40 || !preg_match('/^[A-Z0-9\-]+$/', $code)) {
                echo json_encode(['success' => true, 'found' => false, 'message' => 'Invalid certificate ID format']);
                break;
            }

            $stmt = $db->prepare(
                'SELECT cert.certificate_code, cert.issued_at, cert.revoked,
                        CONCAT(u.first_name, " ", u.last_name) AS student_name,
                        c.title AS course_title, c.category AS course_category
                 FROM certificates cert
                 JOIN users u ON cert.student_id = u.id
                 JOIN courses c ON cert.course_id = c.id
                 WHERE cert.certificate_code = :code
                 LIMIT 1'
            );
            $stmt->execute([':code' => $code]);
            $cert = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cert) {
                echo json_encode(['success' => true, 'found' => false]);
            } else {
                echo json_encode([
                    'success'      => true,
                    'found'        => true,
                    'valid'        => !(int)$cert['revoked'],
                    'certificate'  => [
                        'certificate_code' => $cert['certificate_code'],
                        'student_name'     => $cert['student_name'],
                        'course_title'     => $cert['course_title'],
                        'course_category'  => $cert['course_category'],
                        'issued_at'        => $cert['issued_at'],
                        'revoked'          => (bool)$cert['revoked'],
                    ],
                ]);
            }
            break;
        }

        case 'apply_instructor': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST required']);
                break;
            }

            $first  = trim($_POST['first_name'] ?? '');
            $last   = trim($_POST['last_name'] ?? '');
            $email  = trim($_POST['email'] ?? '');
            $spec   = trim($_POST['specialization'] ?? '');
            $exp    = (int)($_POST['experience_years'] ?? 0);
            $bio    = trim($_POST['bio'] ?? '');
            $phone  = trim($_POST['phone'] ?? '');
            $cv     = trim($_POST['cv_url'] ?? '');
            $port   = trim($_POST['portfolio_url'] ?? '');
            $sample = trim($_POST['sample_lesson_url'] ?? '');
            $extra  = trim($_POST['additional_info'] ?? '');

            $errors = [];
            if ($first === '' || mb_strlen($first) > 100) $errors[] = 'First name is required (max 100 chars).';
            if ($last === '' || mb_strlen($last) > 100)  $errors[] = 'Last name is required (max 100 chars).';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) $errors[] = 'A valid email is required.';
            if ($spec === '' || mb_strlen($spec) > 255)  $errors[] = 'Specialization is required (max 255 chars).';
            if ($exp < 0 || $exp > 70)                   $errors[] = 'Experience must be between 0 and 70 years.';
            foreach (['CV' => $cv, 'Portfolio' => $port, 'Sample lesson' => $sample] as $label => $url) {
                if ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || strlen($url) > 500)) {
                    $errors[] = $label . ' URL is invalid or too long.';
                }
            }
            if (mb_strlen($bio) > 5000 || mb_strlen($extra) > 5000 || mb_strlen($phone) > 30) {
                $errors[] = 'One of the fields is too long.';
            }

            if ($errors) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
                break;
            }

            // Basic flood control: only one pending application per email
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM instructor_applications WHERE email = :email AND status = 'pending'"
            );
            $stmt->execute([':email' => $email]);
            if ((int)$stmt->fetchColumn() > 0) {
                http_response_code(429);
                echo json_encode(['success' => false, 'message' => 'An application with this email is already under review.']);
                break;
            }

            $stmt = $db->prepare(
                "INSERT INTO instructor_applications
                    (first_name, last_name, email, phone, specialization, experience_years,
                     bio, cv_url, portfolio_url, sample_lesson_url, additional_info, status)
                 VALUES (:first, :last, :email, :phone, :spec, :exp, :bio, :cv, :port, :sample, :extra, 'pending')"
            );
            $stmt->execute([
                ':first' => $first, ':last' => $last, ':email' => $email, ':phone' => $phone ?: null,
                ':spec' => $spec, ':exp' => $exp, ':bio' => $bio ?: null,
                ':cv' => $cv ?: null, ':port' => $port ?: null, ':sample' => $sample ?: null,
                ':extra' => $extra ?: null,
            ]);

            echo json_encode(['success' => true, 'message' => 'Application received. Our team will review it and contact you.']);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log('public.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again later.']);
}
