<?php
/**
 * Payments API — student checkout + admin payment management.
 *
 * Student actions:  methods, initialize, verify, my, request_refund, upload_proof
 * Admin actions:    admin_stats, admin_list, admin_pending_transfers, admin_approve,
 *                   admin_reject, admin_pending_refunds, admin_refund_process
 *
 * Every POST requires CSRF. Every action re-checks the user's role server-side.
 */

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Payment.php';
require_once __DIR__ . '/../middleware/auth.php';

header('Content-Type: application/json');

$user   = checkAuth();
$role   = $user['role'] ?? '';
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
}

$config = require __DIR__ . '/../config/payments.php';
$payment = new Payment($config);

try {
    switch ($action) {

        // ————————————————————— STUDENT —————————————————————
        case 'methods':
            $methods = $payment->availableMethods();
            echo json_encode([
                'success' => true,
                'data'    => [
                    'methods'         => $methods,
                    'paystack_public' => $config['paystack']['public_key'] ?? '',
                    'flutterwave_public' => $config['flutterwave']['public_key'] ?? '',
                    'bank_transfer'   => $config['bank_transfer'] ?? [],
                    'currency'        => $config['currency_symbol'] ?? '₦',
                ],
            ]);
            break;

        case 'initialize':
            if (!in_array($role, ['student', 'admin'], true)) throw new Exception('Access denied');

            $courseId    = (int) ($_POST['course_id'] ?? 0);
            $method      = trim($_POST['method'] ?? '');
            $couponCode  = trim($_POST['coupon_code'] ?? '');
            $callbackUrl = appBasePath() . '/index.php?page=payment-callback';

            if (!$courseId || !$method) throw new Exception('course_id and method are required');

            $result = $payment->initialize($user['id'], $courseId, $method, $couponCode, $callbackUrl);
            echo json_encode($result);
            break;

        case 'verify':
            if (!in_array($role, ['student', 'admin'], true)) throw new Exception('Access denied');

            $reference = trim($_POST['reference'] ?? '');
            if ($reference === '') throw new Exception('reference is required');
            echo json_encode($payment->verify($reference));
            break;

        case 'my':
            if ($role !== 'student') throw new Exception('Access denied');
            echo json_encode([
                'success' => true,
                'data'    => $payment->getUserPayments((int) $user['id'], 50, 0),
            ]);
            break;

        case 'request_refund':
            if ($role !== 'student') throw new Exception('Access denied');

            $paymentId = (int) ($_POST['payment_id'] ?? 0);
            $reason    = trim($_POST['reason'] ?? '');
            if (!$paymentId || $reason === '') throw new Exception('payment_id and reason are required');
            if (mb_strlen($reason) < 10) throw new Exception('Please provide a reason of at least 10 characters');

            echo json_encode($payment->requestRefund($paymentId, (int) $user['id'], $reason));
            break;
case 'upload_proof':
            if ($role !== 'student') throw new Exception('Access denied');

            $paymentId = (int) ($_POST['payment_id'] ?? 0);
            if (!$paymentId) throw new Exception('payment_id is required');
            if (empty($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Please choose a valid proof file.');
            }

            $file    = $_FILES['proof'];
            $allowed = $config['uploads']['mime_types'] ?? [];
            $max     = $config['uploads']['max_bytes'] ?? 5242880;

            if ($file['size'] > $max) {
                throw new Exception('File is too large. Maximum allowed is 5MB.');
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);
            if (!in_array($mime, $allowed, true)) {
                throw new Exception('File type not allowed. Upload a PDF, JPEG, PNG, or WebP image.');
            }
            if (!is_uploaded_file($file['tmp_name'])) {
                throw new Exception('Invalid upload.');
            }

            $dir = __DIR__ . '/../../' . $config['uploads']['proof_dir'];
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $ext = match ($mime) {
                'application/pdf' => 'pdf',
                'image/jpeg'      => 'jpg',
                'image/png'       => 'png',
                'image/webp'      => 'webp',
                default           => 'bin',
            };
            $filename = 'proof-' . $paymentId . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
            $relPath  = $config['uploads']['proof_dir'] . '/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
                throw new Exception('Could not save the uploaded file.');
            }

            $db = (new Database())->connect();
            $stmt = $db->prepare(
                "UPDATE payments SET payment_proof_path = :p, proof_uploaded_at = NOW()
                 WHERE id = :id AND user_id = :uid AND status = 'pending' AND payment_method = 'bank_transfer'"
            );
            $stmt->execute([':p' => $relPath, ':id' => $paymentId, ':uid' => (int) $user['id']]);
            if ($stmt->rowCount() === 0) {
                @unlink($dir . '/' . $filename);
                throw new Exception('Payment not found or not pending for bank-transfer approval.');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Payment proof uploaded. Please wait for admin approval.',
                'path'    => $relPath,
            ]);
            break;

        // ————————————————————— ADMIN —————————————————————
        case 'admin_stats':
            if ($role !== 'admin') throw new Exception('Access denied');
            echo json_encode(['success' => true, 'data' => $payment->adminRevenueStats()]);
            break;

        case 'admin_list':
            if ($role !== 'admin') throw new Exception('Access denied');
            $status = trim($_GET['status'] ?? '');
            $search = trim($_GET['search'] ?? '');
            echo json_encode([
                'success' => true,
                'data'    => $payment->adminPaymentsList($status, $search, 50, 0),
            ]);
            break;

        case 'admin_pending_transfers':
            if ($role !== 'admin') throw new Exception('Access denied');
            echo json_encode(['success' => true, 'data' => $payment->adminPendingBankTransfers()]);
            break;

        case 'admin_approve':
            if ($role !== 'admin') throw new Exception('Access denied');
            $paymentId = (int) ($_POST['payment_id'] ?? 0);
            if (!$paymentId) throw new Exception('payment_id is required');
            echo json_encode($payment->approveBankTransfer($paymentId, (int) $user['id']));
            break;

        case 'admin_reject':
            if ($role !== 'admin') throw new Exception('Access denied');
            $paymentId = (int) ($_POST['payment_id'] ?? 0);
            $reason    = trim($_POST['reason'] ?? '');
            if (!$paymentId || $reason === '') throw new Exception('payment_id and reason are required');
            echo json_encode($payment->rejectBankTransfer($paymentId, (int) $user['id'], $reason));
            break;

        case 'admin_pending_refunds':
            if ($role !== 'admin') throw new Exception('Access denied');
            echo json_encode(['success' => true, 'data' => $payment->adminPendingRefunds()]);
            break;

        case 'admin_refund_process':
            if ($role !== 'admin') throw new Exception('Access denied');
            $refundId = (int) ($_POST['refund_id'] ?? 0);
            $decision = trim($_POST['decision'] ?? '');
            $notes    = trim($_POST['notes'] ?? '');
            if (!$refundId || !in_array($decision, ['approved', 'rejected'], true)) {
                throw new Exception('refund_id and a valid decision are required');
            }
            echo json_encode($payment->processRefund($refundId, (int) $user['id'], $decision, $notes));
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}