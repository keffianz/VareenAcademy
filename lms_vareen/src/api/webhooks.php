<?php
/**
 * Payment Webhook Receiver
 *
 * Verifies gateway signatures BEFORE trusting any payload, logs every event to
 * payment_webhooks, and marks payments paid once the gateway confirms success.
 *
 * Paystack   — header "x-paystack-signature" = HMAC-SHA512(rawBody, secret)
 * Flutterwave— header "verif-hash"           = the shared secret_hash verbatim
 *
 * A valid signature always returns HTTP 200 (even for unknown events) to stop
 * gateway retries; an invalid signature returns 401 and is never processed.
 */

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Payment.php';

header('Content-Type: application/json');

$config   = require __DIR__ . '/../config/payments.php';
$rawBody  = file_get_contents('php://input');
$event    = json_decode($rawBody, true);
$db       = (new Database())->connect();

$gateway     = strtolower(trim($_SERVER['HTTP_X_GATEWAY'] ?? ($_GET['gateway'] ?? '')));
$paystackSig = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
$flwHash     = $_SERVER['HTTP_VERIF_HASH'] ?? '';

// Resolve which gateway this event belongs to from headers.
if ($paystackSig !== '') {
    $gateway = 'paystack';
} elseif ($flwHash !== '') {
    $gateway = 'flutterwave';
}
if (!in_array($gateway, ['paystack', 'flutterwave'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown gateway']);
    exit;
}

// ————————————————————— Signature verification —————————————————————
$cls = $gateway === 'paystack' ? 'PaystackGateway' : 'FlutterwaveGateway';
require_once __DIR__ . '/../classes/' . $cls . '.php';
$gwConfig = $gateway === 'paystack' ? $config['paystack'] : $config['flutterwave'];
$gw = new $cls($gwConfig);

$signature = $gateway === 'paystack' ? $paystackSig : $flwHash;
$valid     = $gw->verifyWebhookSignature($rawBody, $signature);

// Log every event — valid or not — for audit.
$stmt = $db->prepare(
    'INSERT INTO payment_webhooks (gateway, event_type, reference, payload, signature, signature_valid)
     VALUES (:g, :e, :r, :p, :s, :v)'
);
$stmt->execute([
    ':g' => $gateway,
    ':e' => $event['event'] ?? null,
    ':r' => $event['data']['reference'] ?? ($event['data']['tx_ref'] ?? null),
    ':p' => $rawBody,
    ':s' => substr($signature ?? '', 0, 255),
    ':v' => (int) $valid,
]);

if (!$valid) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
    exit;
}

$eventName = strtolower($event['event'] ?? '');
$reference = $event['data']['reference'] ?? ($event['data']['tx_ref'] ?? '');
$isSuccess = in_array($eventName, ['charge.success', 'charge.completed'], true)
    && $reference !== '';

if (!$isSuccess) {
    // Acknowledge non-payment events (e.g. transfer.*, charge.retry) without processing.
    echo json_encode(['success' => true, 'message' => 'Event acknowledged']);
    exit;
}

// ————————————————————— Process the payment —————————————————————
$payment = new Payment($config);
$result  = $payment->verify($reference);
$processed = $result['success'] ? 1 : 0;

$stmt = $db->prepare(
    'UPDATE payment_webhooks SET processed = :p, processed_at = NOW()
     WHERE gateway = :g AND reference = :r AND processed = 0 ORDER BY id DESC LIMIT 1'
);
$stmt->execute([':p' => $processed, ':g' => $gateway, ':r' => $reference]);

if (!$result['success']) {
    http_response_code(200); // ack to avoid retry storms; payment stays failed in DB
    echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Verification failed']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Payment processed and enrollment completed']);