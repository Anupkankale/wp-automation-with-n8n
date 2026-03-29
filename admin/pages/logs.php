<?php
/**
 * Webhook Logs Page Template
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
    <h1>Webhook Logs</h1>

    <div class="n8n-logs-header">
        <form method="post" style="display: inline;">
            <?php wp_nonce_field('n8n_clear_logs'); ?>
            <button type="submit" name="clear_logs" class="button button-secondary"
                    onclick="return confirm('Are you sure you want to clear all logs?');">
                Clear All Logs
            </button>
        </form>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 15%;">Event ID</th>
                <th style="width: 20%;">Event Type</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 20%;">Time</th>
                <th style="width: 35%;">Details</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($logs)) : ?>
                <?php foreach ($logs as $log) : ?>
                    <tr>
                        <td><code><?php echo esc_html($log['event_id']); ?></code></td>
                        <td><?php echo esc_html($log['event_type']); ?></td>
                        <td>
                            <span class="n8n-status n8n-status-<?php echo esc_attr($log['status']); ?>">
                                <?php echo esc_html($log['status']); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($log['created_at']); ?></td>
                        <td>
                            <details>
                                <summary>View payload</summary>
                                <pre style="max-height: 200px; overflow: auto; font-size: 11px;"><?php
                                    echo esc_html(
                                        json_encode(
                                            json_decode($log['payload']),
                                            JSON_PRETTY_PRINT
                                        )
                                    );
                                ?></pre>
                            </details>
                            <?php if (!empty($log['response']) && $log['status'] === 'failed') : ?>
                                <div style="color: red; margin-top: 5px;">
                                    <strong>Error:</strong> <?php echo esc_html($log['response']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5">No webhook logs found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
