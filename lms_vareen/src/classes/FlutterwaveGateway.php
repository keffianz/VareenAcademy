<?php
/**
 * Flutterwave gateway implementation (secondary, structured for future enablement).
 * Mirrors PaystackGateway exactly — the payment controller treats both
 * identically through the PaymentGateway interface.
 *
 * Docs: https://developer.flutterwave.com/docs
 */

require_once __DIR__ . '/PaymentGateway.php';

class FlutterwaveGateway implements PaymentGateway
{
    private $config;

    public function __construct(array $flwConfig) {
        $this->config = $flwConfig;
    }

    public function initializeTransaction(array $payment, string $callbackUrl): array {
        if (empty($this->config['secret_key'])) {
            return ['success' => false, 'redirect_url' => null,
                    'message' => 'Flutterwave is not configured. Set FLW_SECRET_KEY.'];
        }

        $payload = [
            'tx_ref'          => $payment['reference'],
            'amount'          => (float) $payment['amount'],
            'currency'        => $payment['currency'] ?: 'NGN',
            'redirect_url'    => $callbackUrl,
            'customer'        => [
                'email'       => $payment['email'],
                'name'        => trim(($payment['first_name'] ?? '') . ' ' . ($payment['last_name'] ?? '')),
            ],
            'customizations'  => [
                'title'       => 'VAREEN Academy',
                'description' => 'Course enrollment payment',
            ],
            'meta'            => [
                'user_id'   => $payment['user_id'],
                'course_id' => $payment['course_id'],
            ],
        ];

        $res = $this->request('POST', '/payments', $payload);
        if (!$res['success']) {
            return ['success' => false, 'redirect_url' => null, 'message' => $res['message']];
        }

        $link = $res['body']['data']['link'] ?? null;
        if (!$link) {
            return ['success' => false, 'redirect_url' => null, 'message' => 'Flutterwave did not return a checkout URL.'];
        }

        return ['success' => true, 'redirect_url' => $link, 'message' => 'Initialized'];
    }

    public function verifyTransaction(string $reference): array {
        if (empty($this->config['secret_key'])) {
            return ['success' => false, 'verified' => false, 'transaction_id' => null,
                    'amount_paid' => null, 'gateway_response' => 'Flutterwave not configured',
                    'message' => 'Flutterwave is not configured.'];
        }

        // 1) Find the transaction id by reference, then verify it by id (recommended flow).
        $lookup = $this->request('GET', '/transactions/verify_by_reference?tx_ref=' . rawurlencode($reference));
        if (!$lookup['success']) {
            return ['success' => false, 'verified' => false, 'transaction_id' => null,
                    'amount_paid' => null, 'gateway_response' => $lookup['message'],
                    'message' => 'Verification request failed: ' . $lookup['message']];
        }

        $txn = $lookup['body']['data'] ?? [];
        $txnId = $txn['id'] ?? null;
        $status = strtolower($txn['status'] ?? '');
        $verified = ($status === 'successful');

        return [
            'success'          => true,
            'verified'         => $verified,
            'transaction_id'   => $txnId,
            'amount_paid'      => isset($txn['amount']) ? (float) $txn['amount'] : null,
            'currency'         => $txn['currency'] ?? null,
            'gateway_response' => $txn['processor_response'] ?? $status,
            'message'          => $verified ? 'Payment verified' : 'Payment not successful (' . $status . ')',
        ];
    }

    public function verifyWebhookSignature(string $rawBody, string $signature): bool {
        // Flutterwave sends the shared secret verbatim in the verif-hash header.
        if (empty($this->config['secret_hash']) || empty($signature)) {
            return false;
        }
        return hash_equals($this->config['secret_hash'], $signature);
    }

    /** Minimal cURL wrapper for the Flutterwave v3 API. */
    private function request(string $method, string $path, ?array $payload = null): array {
        $ch = curl_init($this->config['api_base'] . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->config['secret_key'],
                'Content-Type: application/json',
            ],
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        $body   = curl_exec($ch);
        $err    = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            return ['success' => false, 'body' => null, 'message' => 'Network error: ' . $err];
        }
        $decoded = json_decode($body, true);
        if ($status >= 400) {
            return ['success' => false, 'body' => $decoded,
                    'message' => $decoded['message'] ?? ('HTTP ' . $status)];
        }
        return ['success' => true, 'body' => $decoded, 'message' => 'OK'];
    }
}
