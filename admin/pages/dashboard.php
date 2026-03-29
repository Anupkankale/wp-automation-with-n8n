<?php
/**
 * Dashboard Page Template
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
    <h1>
        <span class="dashicons dashicons-networking"></span>
        n8n Automation Connector
    </h1>

    <?php N8N_Admin::render_configuration_notice(); ?>

    <div class="n8n-dashboard">
        <!-- Statistics Cards -->
        <div class="n8n-stats-cards">
            <div class="n8n-stat-card">
                <div class="n8n-stat-value"><?php echo esc_html($stats['total']); ?></div>
                <div class="n8n-stat-label">Total Webhooks (7 days)</div>
            </div>

            <div class="n8n-stat-card n8n-stat-success">
                <div class="n8n-stat-value"><?php echo esc_html($stats['success']); ?></div>
                <div class="n8n-stat-label">Successful</div>
            </div>

            <div class="n8n-stat-card n8n-stat-error">
                <div class="n8n-stat-value"><?php echo esc_html($stats['failed']); ?></div>
                <div class="n8n-stat-label">Failed</div>
            </div>

            <div class="n8n-stat-card">
                <div class="n8n-stat-value"><?php echo esc_html($stats['success_rate']); ?>%</div>
                <div class="n8n-stat-label">Success Rate</div>
            </div>
        </div>

        <!-- Events by Type -->
        <div class="n8n-section">
            <h2>Events by Type</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Event Type</th>
                        <th>Count</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($stats['by_type'])) : ?>
                        <?php foreach ($stats['by_type'] as $type_stat) : ?>
                            <tr>
                                <td><?php echo esc_html($type_stat['event_type']); ?></td>
                                <td><?php echo esc_html($type_stat['count']); ?></td>
                                <td>
                                    <?php
                                    $percentage = ($stats['total'] > 0) ?
                                        round(($type_stat['count'] / $stats['total']) * 100, 1) : 0;
                                    echo esc_html($percentage);
                                    ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="3">No events found in the last 7 days</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Activity -->
        <div class="n8n-section">
            <h2>Recent Activity</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Event ID</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recent_logs)) : ?>
                        <?php foreach ($recent_logs as $log) : ?>
                            <tr>
                                <td><code><?php echo esc_html($log['event_id']); ?></code></td>
                                <td><?php echo esc_html($log['event_type']); ?></td>
                                <td>
                                    <span class="n8n-status n8n-status-<?php echo esc_attr($log['status']); ?>">
                                        <?php echo esc_html($log['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(human_time_diff(strtotime($log['created_at']))); ?> ago</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4">No recent activity</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <p>
                <a href="<?php echo admin_url('admin.php?page=n8n-webhook-logs'); ?>" class="button">
                    View All Logs
                </a>
            </p>
        </div>

        <!-- Configuration Info -->
        <div class="n8n-section">
            <h2>Configuration</h2>
            <table class="form-table">
                <tr>
                    <th>Webhook URL:</th>
                    <td>
                        <?php if (defined('N8N_WEBHOOK_URL') && !empty(N8N_WEBHOOK_URL)) : ?>
                            <code><?php echo esc_html(N8N_WEBHOOK_URL); ?></code>
                            <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                        <?php else : ?>
                            <span style="color: red;">Not configured</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>API Key:</th>
                    <td>
                        <?php if (defined('N8N_API_KEY') && !empty(N8N_API_KEY)) : ?>
                            <span class="dashicons dashicons-yes-alt" style="color: green;"></span> Configured
                        <?php else : ?>
                            <span class="dashicons dashicons-minus" style="color: orange;"></span> Not configured (optional)
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Debug Mode:</th>
                    <td>
                        <?php if (defined('N8N_DEBUG_MODE') && N8N_DEBUG_MODE) : ?>
                            <span class="dashicons dashicons-yes-alt" style="color: orange;"></span> Enabled
                        <?php else : ?>
                            <span class="dashicons dashicons-no-alt" style="color: gray;"></span> Disabled
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Plugin Version:</th>
                    <td><?php echo esc_html(N8N_CONNECTOR_VERSION); ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
