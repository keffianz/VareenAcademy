<?php
/**
 * AI Assistant Configuration
 *
 * IMPORTANT: This file is protected by .htaccess rules in src/ and should never be accessible
 * via web. The API key is read from the ANTHROPIC_API_KEY environment variable in production;
 * the placeholder below is used for local development only.
 */

// Anthropic API Configuration
define('ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY') ?: 'REPLACE_WITH_YOUR_KEY'); // https://console.anthropic.com
define('AI_MODEL', 'claude-haiku-4-5-20251001'); // Haiku model - cheapest and fastest for chat
define('AI_API_ENDPOINT', 'https://api.anthropic.com/v1/messages');

// AI Assistant Limits
define('AI_DAILY_LIMIT', 40); // Per-student daily limit to control costs
define('AI_TIMEOUT', 30); // API request timeout in seconds

// Feature Flags
define('AI_ENABLED', true);
define('AI_DEBUG', false); // Set to true for development to see API responses
