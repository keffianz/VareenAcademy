<?php
/**
 * Payment gateway configuration.
 *
 * SECURITY: secrets are read from environment variables when available so no
 * credential is ever committed to the repository. For local development you
 * may copy this file to payments.local.php (git-ignored) and hardcode keys,
 * or simply export the environment variables below.
 *
 * Required env vars for live mode:
 *   PAYSTACK_SECRET_KEY   sk_test_... / sk_live_...
 *   PAYSTACK_PUBLIC_KEY   pk_test_... / pk_live_...
 *   FLW_SECRET_KEY        FLWSECK-...
 *   FLW_PUBLIC_KEY        FLWPUBK-...
 *   FLW_SECRET_HASH       arbitrary secret string used to verify webhooks
 */

if (file_exists(__DIR__ . '/payments.local.php')) {
    return require __DIR__ . '/payments.local.php';
}

$env = static function (string $key, string $fallback = '') {
    $v = getenv($key);
    return ($v === false || $v === '') ? $fallback : $v;
};

return [
    'currency'            => 'NGN',
    'currency_symbol'     => '₦',

    'paystack' => [
        'enabled'     => $env('PAYSTACK_SECRET_KEY') !== '',
        'secret_key'  => $env('PAYSTACK_SECRET_KEY'),
        'public_key'  => $env('PAYSTACK_PUBLIC_KEY'),
        'api_base'    => 'https://api.paystack.co',
    ],

    'flutterwave' => [
        'enabled'      => $env('FLW_SECRET_KEY') !== '',
        'secret_key'   => $env('FLW_SECRET_KEY'),
        'public_key'   => $env('FLW_PUBLIC_KEY'),
        'secret_hash'  => $env('FLW_SECRET_HASH'),
        'api_base'     => 'https://api.flutterwave.com/v3',
    ],

    'bank_transfer' => [
        'enabled'       => true,
        'bank_name'     => 'Guaranty Trust Bank (GTBank)',
        'account_name'  => 'VAREEN Academy Ltd',
        'account_number' => $env('VAREEN_BANK_ACCOUNT', '0123456789'),
        'instructions'  => 'Transfer the exact amount to the account above and use the reference code shown on this page as your transfer narration. Then upload your payment proof (receipt/screenshot) below.',
    ],

    'uploads' => [
        // Folder (relative to the lms_vareen root) for payment-proof uploads.
        'proof_dir'  => 'uploads/payment_proofs',
        'max_bytes'  => 5 * 1024 * 1024,
        'mime_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
    ],
];
