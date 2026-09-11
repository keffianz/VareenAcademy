<?php
/**
 * AI Assistant Configuration
 *
 * IMPORTANT: This file is protected by .htaccess rules in src/ and should never be accessible
 * via web. The API key is read from the ANTHROPIC_API_KEY environment variable in production;
 * the placeholder below is used for local development only.
 */

// Anthropic API Configuration — the key is resolved at the bottom of this
// file (env var first, then the admin-managed ai_local_key.php secret file).
// https://console.anthropic.com
define('AI_MODEL', 'claude-haiku-4-5-20251001'); // Haiku model - cheapest and fastest for chat
define('AI_API_ENDPOINT', 'https://api.anthropic.com/v1/messages');

// AI Assistant Limits
define('AI_DAILY_LIMIT', 40); // Per-student daily limit to control costs
define('AI_TIMEOUT', 30); // API request timeout in seconds

// Feature Flags
define('AI_ENABLED', true);
define('AI_DEBUG', false); // Set to true for development to see API responses

/**
 * Resolve the Anthropic API key.
 *
 * Precedence:
 *   1. ANTHROPIC_API_KEY environment variable (hosting-level secret)
 *   2. src/config/ai_local_key.php — written by the Admin AI Control Center.
 *      Git-ignored and web-blocked (root .htaccess denies /src/), same
 *      protected-secrets pattern as local_db.php.
 */
if (!defined('ANTHROPIC_API_KEY')) {
    $aiLocalKeyFile = __DIR__ . '/ai_local_key.php';
    $aiLocalKey = '';
    if (is_file($aiLocalKeyFile)) {
        $aiLocalKey = trim((string) @include $aiLocalKeyFile);
    }
    define('ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY') ?: ($aiLocalKey !== '' ? $aiLocalKey : 'REPLACE_WITH_YOUR_KEY'));
}

/**
 * Returns a masked version of the configured key for admin display,
 * e.g. "sk-ant-…abcd". Returns null when the key is not set.
 */
function ai_masked_key(): ?string
{
    if (!defined('ANTHROPIC_API_KEY')) {
        return null;
    }
    $key = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '';
    if ($key === '' || $key === 'REPLACE_WITH_YOUR_KEY') {
        return null;
    }
    $prefix = substr($key, 0, 7);
    $suffix = substr($key, -4);
    return $prefix . '…' . $suffix;
}

/** True when a real API key is configured (env var or saved via admin UI). */
function ai_key_configured(): bool
{
    return ai_masked_key() !== null;
}
