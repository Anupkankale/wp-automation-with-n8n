<?php
/**
 * Plugin Configuration
 *
 * Define all constant values used throughout the plugin.
 * These can be overridden in wp-config.php if needed.
 *
 * @package N8N_Automation_Connector
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin version - update this when releasing new versions
 */
if (!defined('N8N_CONNECTOR_VERSION')) {
    define('N8N_CONNECTOR_VERSION', '1.0.0');
}

/**
 * Plugin directory path - useful for including files
 */
if (!defined('N8N_CONNECTOR_PATH')) {
    define('N8N_CONNECTOR_PATH', plugin_dir_path(dirname(__FILE__)));
}

/**
 * Plugin directory URL - useful for enqueuing assets
 */
if (!defined('N8N_CONNECTOR_URL')) {
    define('N8N_CONNECTOR_URL', plugin_dir_url(dirname(__FILE__)));
}

/**
 * n8n webhook URL - REQUIRED: Set this in wp-config.php
 */
if (!defined('N8N_WEBHOOK_URL')) {
    define('N8N_WEBHOOK_URL', '');
}

/**
 * n8n API key for authentication (optional but recommended)
 */
if (!defined('N8N_API_KEY')) {
    define('N8N_API_KEY', '');
}

/**
 * Enable debug logging
 */
if (!defined('N8N_DEBUG_MODE')) {
    define('N8N_DEBUG_MODE', false);
}

/**
 * Webhook timeout in seconds (optional)
 */
if (!defined('N8N_WEBHOOK_TIMEOUT')) {
    define('N8N_WEBHOOK_TIMEOUT', 15);
}

/**
 * Database table prefix for webhook logs
 */
if (!defined('N8N_LOG_TABLE')) {
    define('N8N_LOG_TABLE', 'n8n_webhook_log');
}
