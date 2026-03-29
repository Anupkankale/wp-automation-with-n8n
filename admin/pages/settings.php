<?php
/**
 * Settings Page Template
 *
 * @package N8N_Automation_Connector
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>n8n Connector Settings</h1>

    <?php if ($test_result) : ?>
        <div class="notice notice-<?php echo $test_result['success'] ? 'success' : 'error'; ?>">
            <p><?php echo esc_html($test_result['message']); ?></p>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Configuration</h2>
        <p>This plugin is configured via <code>wp-config.php</code> constants. Add the following to your <code>wp-config.php</code> file:</p>

        <h3>Required Configuration:</h3>
        <pre style="background: #f5f5f5; padding: 15px; border-left: 3px solid #0073aa;">
// n8n Webhook URL (Required)
define('N8N_WEBHOOK_URL', 'http://your-n8n-instance.com/webhook/wordpress-events');
</pre>

        <h3>Optional Configuration:</h3>
        <pre style="background: #f5f5f5; padding: 15px; border-left: 3px solid #0073aa;">
// API Key for authentication (Optional but recommended)
define('N8N_API_KEY', 'your-secret-api-key');

// Enable debug logging (Optional, useful for troubleshooting)
define('N8N_DEBUG_MODE', true);

// Webhook timeout in seconds (Optional, default: 15)
define('N8N_WEBHOOK_TIMEOUT', 15);
</pre>

        <h3>Test Connection:</h3>
        <form method="post">
            <?php wp_nonce_field('n8n_test_connection'); ?>
            <button type="submit" name="test_connection" class="button button-primary">
                Test Webhook Connection
            </button>
        </form>
    </div>

    <div class="card">
        <h2>Available REST API Endpoints</h2>
        <table class="wp-list-table widefat">
            <thead>
                <tr>
                    <th>Endpoint</th>
                    <th>Method</th>
                    <th>Purpose</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>/wp-json/custom/v1/install-theme</code></td>
                    <td>POST</td>
                    <td>Install WordPress theme from URL</td>
                </tr>
                <tr>
                    <td><code>/wp-json/custom/v1/log-webhook</code></td>
                    <td>POST</td>
                    <td>Log webhook processing result</td>
                </tr>
                <tr>
                    <td><code>/wp-json/custom/v1/create-post-type</code></td>
                    <td>POST</td>
                    <td>Create custom post type</td>
                </tr>
                <tr>
                    <td><code>/wp-json/custom/v1/webhook-stats</code></td>
                    <td>GET</td>
                    <td>Get webhook statistics</td>
                </tr>
                <tr>
                    <td><code>/wp-json/custom/v1/test-connection</code></td>
                    <td>GET</td>
                    <td>Test API connectivity</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Supported WordPress Events</h2>
        <ul class="ul-disc">
            <li><strong>woocommerce_new_order</strong> - Triggered when a new WooCommerce order is created</li>
            <li><strong>woocommerce_order_status_changed</strong> - Triggered when order status changes</li>
            <li><strong>cf7_form_submission</strong> - Triggered when Contact Form 7 form is submitted</li>
            <li><strong>user_registered</strong> - Triggered when new user registers</li>
            <li><strong>post_published</strong> - Triggered when post is published</li>
            <li><strong>comment_added</strong> - Triggered when comment is added</li>
        </ul>
    </div>
</div>
