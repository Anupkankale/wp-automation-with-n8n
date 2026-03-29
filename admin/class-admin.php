<?php
/**
 * Admin Dashboard Class
 *
 * Handles the WordPress admin interface for the n8n connector.
 * Provides:
 * - Settings page
 * - Webhook logs viewer
 * - Statistics dashboard
 * - Connection testing
 *
 * @package N8N_Automation_Connector
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * N8N Admin Class
 *
 * Manages all admin interface functionality.
 */
class N8N_Admin {

    /**
     * Add Admin Menu
     *
     * Creates menu items in WordPress admin sidebar.
     */
    public static function add_admin_menu() {
        // Main menu page
        add_menu_page(
            'n8n Connector',           // Page title
            'n8n Connector',           // Menu title
            'manage_options',          // Capability required
            'n8n-connector',           // Menu slug
            array(__CLASS__, 'render_dashboard_page'), // Callback function
            'dashicons-networking',    // Icon
            80                         // Position
        );

        // Dashboard submenu
        add_submenu_page(
            'n8n-connector',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'n8n-connector',
            array(__CLASS__, 'render_dashboard_page')
        );

        // Webhook logs submenu
        add_submenu_page(
            'n8n-connector',
            'Webhook Logs',
            'Webhook Logs',
            'manage_options',
            'n8n-webhook-logs',
            array(__CLASS__, 'render_logs_page')
        );

        // Settings submenu
        add_submenu_page(
            'n8n-connector',
            'Settings',
            'Settings',
            'manage_options',
            'n8n-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }

    /**
     * Enqueue Admin Scripts
     *
     * Loads CSS and JavaScript for admin pages.
     *
     * @param string $hook Current admin page hook
     */
    public static function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'n8n-') === false) {
            return;
        }

        // Enqueue WordPress core styles
        wp_enqueue_style('wp-admin');
        wp_enqueue_style('common');

        // Enqueue our custom styles
        wp_enqueue_style(
            'n8n-admin-css',
            N8N_CONNECTOR_URL . 'admin/assets/css/admin.css',
            array(),
            N8N_CONNECTOR_VERSION
        );

        // Enqueue our custom scripts
        wp_enqueue_script(
            'n8n-admin-js',
            N8N_CONNECTOR_URL . 'admin/assets/js/admin.js',
            array('jquery'),
            N8N_CONNECTOR_VERSION,
            true
        );
    }

    /**
     * Render Dashboard Page
     *
     * Displays the main dashboard with statistics and overview.
     */
    public static function render_dashboard_page() {
        // Get webhook handler instance
        $handler = N8N_Webhook_Handler::get_instance();

        // Get statistics
        $stats = $handler->get_statistics(7);
        $recent_logs = $handler->get_recent_logs(10);

        include N8N_CONNECTOR_PATH . 'admin/pages/dashboard.php';
    }

    /**
     * Render Webhook Logs Page
     *
     * Displays detailed webhook delivery logs.
     */
    public static function render_logs_page() {
        // Handle log clearing if requested
        if (isset($_POST['clear_logs']) && check_admin_referer('n8n_clear_logs')) {
            $handler = N8N_Webhook_Handler::get_instance();
            $deleted = $handler->clear_old_logs(0); // Delete all logs
            echo '<div class="notice notice-success"><p>Cleared ' . esc_html($deleted) . ' log entries.</p></div>';
        }

        // Get logs
        $handler = N8N_Webhook_Handler::get_instance();
        $logs = $handler->get_recent_logs(100);

        include N8N_CONNECTOR_PATH . 'admin/pages/logs.php';
    }

    /**
     * Render Settings Page
     *
     * Displays settings and configuration instructions.
     */
    public static function render_settings_page() {
        // Handle test connection
        $test_result = null;
        if (isset($_POST['test_connection']) && check_admin_referer('n8n_test_connection')) {
            $handler = N8N_Webhook_Handler::get_instance();
            $test_result = $handler->test_connection();
        }

        include N8N_CONNECTOR_PATH . 'admin/pages/settings.php';
    }

    /**
     * Render Configuration Notice
     *
     * Shows warning if plugin is not configured.
     */
    public static function render_configuration_notice() {
        if (!defined('N8N_WEBHOOK_URL') || empty(N8N_WEBHOOK_URL)) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong>n8n Webhook URL not configured!</strong>
                    Please add <code>define('N8N_WEBHOOK_URL', 'your-webhook-url');</code> to your wp-config.php file.
                    <a href="<?php echo admin_url('admin.php?page=n8n-settings'); ?>">View Settings</a>
                </p>
            </div>
            <?php
        }
    }
}
