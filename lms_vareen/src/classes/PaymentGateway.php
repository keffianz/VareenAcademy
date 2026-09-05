<?php
/**
 * Payment Gateway interface.
 * Every gateway (Paystack, Flutterwave, future ones) implements this contract
 * so the payment controller never needs gateway-specific branching beyond
 * selecting the class.
 */

interface PaymentGateway
{
    /**
     * Initialize a transaction with the gateway.
     *
     * @param array $payment  Row from the payments table (must include reference, amount, email)
     * @param string $callbackUrl  Absolute URL the gateway redirects back to
     * @return array ['success' => bool, 'redirect_url' => ?string, 'message' => string]
     */
    public function initializeTransaction(array $payment, string $callbackUrl): array;

    /**
     * Verify a transaction with the gateway API (server-to-server).
     * Never trust frontend callbacks — this is the only source of truth.
     *
     * @param string $reference  Our unique payment reference
     * @return array ['success' => bool, 'verified' => bool, 'transaction_id' => ?string,
     *                'amount_paid' => ?float, 'gateway_response' => string, 'message' => string]
     */
    public function verifyTransaction(string $reference): array;

    /**
     * Validate an inbound webhook signature against the raw request body.
     *
     * @param string $rawBody   The exact raw POST body
     * @param string $signature The signature/header sent by the gateway
     * @return bool
     */
    public function verifyWebhookSignature(string $rawBody, string $signature): bool;
}
