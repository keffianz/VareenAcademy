<?php
/**
 * Paystack gateway implementation (primary gateway).
 *
 * Flow:
 *  1. initializeTransaction() -> POST /transaction/initialize -> authorization_url
 *  2. Student pays on Paystack-hosted checkout -> redirected to our callback
 *  3. verifyTransaction()     -> GET /transaction/verify/:reference  (SERVER-SIDE ONLY)
 *
 * Docs: https://paystack.com/docs/api
 */

require_once __DIR__ . '/PaymentGateway.php';

class PaystackGateway implements PaymentGateway
{
    private $config;

    public function __construct(array $paystackConfig) {
        $this->config = $paystackConfig;
    }

    public function initializeTransaction(array $payment, string $callbackUrl): array {
        if (empty($this->config['secret_key'])) {
            return ['success' => false, 'redirect_url' => null,
                    'message' => 'Paystack is not configured. Set PAYSTACK_SECRET_KEY.'];
        }

        $payload = [
            'email'        => $payment['email'],
            'amount'       => (int) round(((float) $payment['amount']) * 100), // kobo
            'currency'     => $payment['currency'] ?: 'NGN',
            'reference'    => $payment['reference'],
            'callback_url' => $callbackUrl,
            'metadata'     => [
                'user_id'  => $payment['user_id'],
                'course_id' => $payment['course_id'],
            ],
        ];

        $res = $this->request('POST', '/transaction/initialize', $payload);
        if (!$res['success']) {
            return ['success' => false, 'redirect_url' => null, 'message' => $res['message']];
        }

        $authUrl = $res['body']['data']['authorization_url'] ?? null;
        if (!$authUrl) {
            return ['success' => false, 'redirect_url' => null, 'message' => 'Paystack did not return a checkout URL.'];
        }

        return ['success' => true, 'redirect_url' => $authUrl, 'message' => 'Initialized'];
    }

    public function verifyTransaction(string $reference): array {
        if (empty($this->config['secret_key'])) {
            return ['success' => false, 'verified' => false, 'transaction_id' => null,
                    'amount_paid' => null, 'gateway_response' => 'Paystack not configured',
                    'message' => 'Paystack is not configured.'];
        }

        $res = $this->request('GET', '/transaction/verify/' . rawurlencode($reference));
        if (!$res['success']) {
            return ['success' => false, 'verified' => false, 'transaction_id' => null,
                    'amount_paid' => null, 'gateway_response' => $res['message'],
                    'message' => 'Verification request failed: ' . $res['message']];
        }

        $data  = $res['body']['data'] ?? [];
        $status = strtolower($data['status'] ?? '');
        $verified = ($status === 'success');

        return [
            'success'          => true,
            'verified'         => $verified,
            'transaction_id'   => $data['id'] ?? null,
            'amount_paid'      => isset($data['amount']) ? ((int) $data['amount']) / 100 : null,
            'gateway_response' => $data['gateway_response'] ?? ($data['failure_message'] ?? $status),
            'message'          => $verified ? 'Payment verified' : 'Payment not successful (' . $status . ')',
        ];
    }

    public function verifyWebhookSignature(string $rawBody, string $signature): bool {
        if (empty($this->config['secret_key']) || empty($signature)) {
            return false;
        }
        $expected = hash_hmac('sha512', $rawBody, $this->config['secret_key']);
        return hash_equals($expected, $signature);
    }

    /** Minimal cURL wrapper for the Paystack API. */
    private function request(string $method, string $path, ?array $payload = null): array {
        $ch = curl_init($this->config['api_base'] . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->config['secret_key'],
                'Content-Type: application/json',
                'Cache-Control: no-cache',
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
