<?php
/**
 * Plugin Name: n8n Automation Connector DevXpert
 * Plugin URI: https://github.com/yourusername/wordpress-n8n-automation
 * Description: Connects WordPress to n8n automation workflows via webhooks and REST API
 * Version: 1.0.0
 * Author: Anup Kankale
 * Author URI: https://www.linkedin.com/in/anupkankale/
 * License: MIT
 * Text Domain: n8n-connector
 * Domain Path: /languages
 *
 * @package N8N_Automation_Connector
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load plugin configuration and constants
require_once plugin_dir_path(__FILE__) . 'config/constants.php';

/**
 * Main Plugin Class
 * 
 * Handles plugin initialization, hooks registration, and core functionality.
 */
class N8N_Automation_Connector {
    
    /**
     * Single instance of the class
     * 
     * @var N8N_Automation_Connector
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     * 
     * Ensures only one instance of the plugin is loaded or can be loaded.
     * 
     * @return N8N_Automation_Connector Single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     * 
     * Initialize the plugin by setting up hooks and loading dependencies.
     */
    private function __construct() {
        // Load plugin components
        $this->load_dependencies();
        
        // Initialize hooks
        $this->init_hooks();
        
        // Log plugin initialization if debug mode is enabled
        if (N8N_DEBUG_MODE) {
            $this->log('Plugin initialized');
        }
    }
    
    /**
     * Load Dependencies
     *
     * Include all required PHP files for the plugin to function.
     */
    private function load_dependencies() {
        // Core classes - webhook handler and REST API
        require_once N8N_CONNECTOR_PATH . 'includes/class-webhook-handler.php';
        require_once N8N_CONNECTOR_PATH . 'includes/class-rest-api.php';

        // Admin interface - dashboard and settings
        require_once N8N_CONNECTOR_PATH . 'admin/class-admin.php';
    }
    
    /**
     * Initialize Hooks
     * 
     * Register all WordPress hooks (actions and filters).
     * This is where we tell WordPress what functions to call when certain events occur.
     */
    private function init_hooks() {
        // Activation hook - runs when plugin is activated
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Deactivation hook - runs when plugin is deactivated
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize plugin components after WordPress loads
        add_action('plugins_loaded', array($this, 'init'));
        
        // Register REST API endpoints
        add_action('rest_api_init', array('N8N_REST_API', 'register_routes'));
        
        // WooCommerce hooks - send webhooks when orders are created/updated
        if (class_exists('WooCommerce')) {
            add_action('woocommerce_new_order', array($this, 'handle_new_order'), 10, 1);
            add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 4);
        }
        
        // Contact Form 7 hooks - send webhooks when forms are submitted
        if (function_exists('wpcf7')) {
            add_action('wpcf7_mail_sent', array($this, 'handle_cf7_submission'), 10, 1);
        }
        
        // Core WordPress hooks
        add_action('user_register', array($this, 'handle_user_registration'), 10, 1);
        add_action('publish_post', array($this, 'handle_post_published'), 10, 2);
        add_action('wp_insert_comment', array($this, 'handle_comment_added'), 10, 2);
        
        // Admin menu
        add_action('admin_menu', array('N8N_Admin', 'add_admin_menu'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array('N8N_Admin', 'enqueue_admin_scripts'));
    }
    
    /**
     * Plugin Activation
     * 
     * Runs when the plugin is activated.
     * Creates database tables, sets default options, etc.
     */
    public function activate() {
        global $wpdb;
        
        // Create webhook log table for tracking webhook deliveries
        $table_name = $wpdb->prefix . 'n8n_webhook_log';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id varchar(100) NOT NULL,
            event_type varchar(50) NOT NULL,
            payload longtext NOT NULL,
            response longtext,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        
        // Set default options
        add_option('n8n_connector_version', N8N_CONNECTOR_VERSION);
        add_option('n8n_connector_installed', current_time('mysql'));
        
        $this->log('Plugin activated successfully');
    }
    
    /**
     * Plugin Deactivation
     * 
     * Runs when the plugin is deactivated.
     * Clean up scheduled tasks, temporary data, etc.
     */
    public function deactivate() {
        // Clear any scheduled cron jobs
        wp_clear_scheduled_hook('n8n_cleanup_logs');
        
        $this->log('Plugin deactivated');
    }
    
    /**
     * Initialize Plugin
     * 
     * Called after WordPress is fully loaded.
     * Initialize plugin components here.
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('n8n-connector', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize webhook handler
        N8N_Webhook_Handler::get_instance();
        
        // Schedule cleanup cron job (runs daily to remove old logs)
        if (!wp_next_scheduled('n8n_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'n8n_cleanup_logs');
        }
        
        add_action('n8n_cleanup_logs', array($this, 'cleanup_old_logs'));
    }
    
    /**
     * Handle New WooCommerce Order
     * 
     * Triggered when a new WooCommerce order is created.
     * Sends order data to n8n webhook.
     * 
     * @param int $order_id The ID of the newly created order
     */
    public function handle_new_order($order_id) {
        // Get order object
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Prepare webhook payload
        $payload = array(
            'event' => 'woocommerce_new_order',
            'data' => array(
                'order_id' => $order_id,
                'order_number' => $order->get_order_number(),
                'status' => $order->get_status(),
                'total' => $order->get_total(),
                'currency' => $order->get_currency(),
                'created_at' => $order->get_date_created()->format('Y-m-d H:i:s'),
                
                // Customer information
                'customer' => array(
                    'id' => $order->get_customer_id(),
                    'email' => $order->get_billing_email(),
                    'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    'phone' => $order->get_billing_phone(),
                ),
                
                // Billing address
                'billing' => array(
                    'address_1' => $order->get_billing_address_1(),
                    'address_2' => $order->get_billing_address_2(),
                    'city' => $order->get_billing_city(),
                    'state' => $order->get_billing_state(),
                    'postcode' => $order->get_billing_postcode(),
                    'country' => $order->get_billing_country(),
                ),
                
                // Order items
                'items' => array(),
                
                // Payment information
                'payment_method' => $order->get_payment_method(),
                'payment_method_title' => $order->get_payment_method_title(),
            )
        );
        
        // Add order items with details
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            
            $payload['data']['items'][] = array(
                'name' => $item->get_name(),
                'product_id' => $item->get_product_id(),
                'variation_id' => $item->get_variation_id(),
                'quantity' => $item->get_quantity(),
                'subtotal' => $item->get_subtotal(),
                'total' => $item->get_total(),
                'sku' => $product ? $product->get_sku() : '',
            );
        }
        
        // Send to n8n
        $this->send_webhook($payload);
    }
    
    /**
     * Handle WooCommerce Order Status Change
     * 
     * Triggered when an order's status changes (e.g., from pending to processing).
     * 
     * @param int    $order_id   Order ID
     * @param string $old_status Previous status
     * @param string $new_status New status
     * @param object $order      Order object
     */
    public function handle_order_status_change($order_id, $old_status, $new_status, $order) {
        $payload = array(
            'event' => 'woocommerce_order_status_changed',
            'data' => array(
                'order_id' => $order_id,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'customer_email' => $order->get_billing_email(),
                'total' => $order->get_total(),
            )
        );
        
        $this->send_webhook($payload);
    }
    
    /**
     * Handle Contact Form 7 Submission
     * 
     * Triggered when a Contact Form 7 form is successfully submitted.
     * 
     * @param object $contact_form WPCF7_ContactForm instance
     */
    public function handle_cf7_submission($contact_form) {
        // Get submission data
        $submission = WPCF7_Submission::get_instance();
        
        if (!$submission) {
            return;
        }
        
        $posted_data = $submission->get_posted_data();
        
        $payload = array(
            'event' => 'cf7_form_submission',
            'data' => array(
                'form_id' => $contact_form->id(),
                'form_name' => $contact_form->title(),
                'form_data' => $posted_data,
                'submitted_at' => current_time('mysql'),
                
                // Common fields (if present)
                'name' => isset($posted_data['your-name']) ? $posted_data['your-name'] : '',
                'email' => isset($posted_data['your-email']) ? $posted_data['your-email'] : '',
                'message' => isset($posted_data['your-message']) ? $posted_data['your-message'] : '',
            )
        );
        
        $this->send_webhook($payload);
    }
    
    /**
     * Handle User Registration
     * 
     * Triggered when a new user registers on the site.
     * 
     * @param int $user_id ID of the newly registered user
     */
    public function handle_user_registration($user_id) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        $payload = array(
            'event' => 'user_registered',
            'data' => array(
                'user_id' => $user_id,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'roles' => $user->roles,
                'registered_at' => $user->user_registered,
            )
        );
        
        $this->send_webhook($payload);
    }
    
    /**
     * Handle Post Published
     * 
     * Triggered when a post is published.
     * 
     * @param int    $post_id Post ID
     * @param object $post    Post object
     */
    public function handle_post_published($post_id, $post) {
        // Skip auto-drafts and revisions
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        $payload = array(
            'event' => 'post_published',
            'data' => array(
                'post_id' => $post_id,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'excerpt' => $post->post_excerpt,
                'author_id' => $post->post_author,
                'author_name' => get_the_author_meta('display_name', $post->post_author),
                'permalink' => get_permalink($post_id),
                'published_at' => $post->post_date,
                'categories' => wp_get_post_categories($post_id, array('fields' => 'names')),
                'tags' => wp_get_post_tags($post_id, array('fields' => 'names')),
            )
        );
        
        $this->send_webhook($payload);
    }
    
    /**
     * Handle Comment Added
     * 
     * Triggered when a new comment is added.
     * 
     * @param int   $comment_id Comment ID
     * @param mixed $comment    Comment object or array
     */
    public function handle_comment_added($comment_id, $comment) {
        // Convert to object if array
        if (is_array($comment)) {
            $comment = (object) $comment;
        }
        
        // Skip spam and trash comments
        if (in_array($comment->comment_approved, array('spam', 'trash'))) {
            return;
        }
        
        $payload = array(
            'event' => 'comment_added',
            'data' => array(
                'comment_id' => $comment_id,
                'post_id' => $comment->comment_post_ID,
                'post_title' => get_the_title($comment->comment_post_ID),
                'author_name' => $comment->comment_author,
                'author_email' => $comment->comment_author_email,
                'content' => $comment->comment_content,
                'approved' => $comment->comment_approved,
                'created_at' => $comment->comment_date,
            )
        );
        
        $this->send_webhook($payload);
    }
    
    /**
     * Send Webhook to n8n
     * 
     * Sends a webhook request to the configured n8n endpoint.
     * Logs the attempt for debugging and monitoring.
     * 
     * @param array $payload Data to send to n8n
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private function send_webhook($payload) {
        // Check if webhook URL is configured
        if (empty(N8N_WEBHOOK_URL)) {
            $this->log('Webhook URL not configured', 'error');
            return new WP_Error('no_webhook_url', 'n8n webhook URL not configured');
        }
        
        // Generate unique event ID
        $event_id = 'evt_' . time() . '_' . wp_generate_password(8, false);
        $payload['event_id'] = $event_id;
        $payload['timestamp'] = current_time('mysql');
        $payload['site_url'] = get_site_url();
        
        // Prepare request headers
        $headers = array(
            'Content-Type' => 'application/json',
        );
        
        // Add API key if configured
        if (!empty(N8N_API_KEY)) {
            $headers['Authorization'] = 'Bearer ' . N8N_API_KEY;
        }
        
        // Send HTTP request to n8n
        $response = wp_remote_post(N8N_WEBHOOK_URL, array(
            'headers' => $headers,
            'body' => wp_json_encode($payload),
            'timeout' => 15,
            'blocking' => false, // Non-blocking for better performance
        ));
        
        // Log the webhook attempt
        $this->log_webhook($event_id, $payload, $response);
        
        // Check for errors
        if (is_wp_error($response)) {
            $this->log('Webhook error: ' . $response->get_error_message(), 'error');
            return $response;
        }
        
        return true;
    }
    
    /**
     * Log Webhook Attempt
     * 
     * Stores webhook delivery attempts in the database for monitoring and debugging.
     * 
     * @param string       $event_id Event unique ID
     * @param array        $payload  Webhook payload
     * @param array|object $response HTTP response
     */
    private function log_webhook($event_id, $payload, $response) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'n8n_webhook_log';
        
        // Determine status from response
        $status = 'pending';
        if (is_wp_error($response)) {
            $status = 'failed';
        } elseif (isset($response['response']['code']) && $response['response']['code'] === 200) {
            $status = 'success';
        }
        
        // Insert log entry
        $wpdb->insert(
            $table_name,
            array(
                'event_id' => $event_id,
                'event_type' => $payload['event'],
                'payload' => wp_json_encode($payload),
                'response' => is_wp_error($response) ? $response->get_error_message() : wp_json_encode($response),
                'status' => $status,
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Cleanup Old Logs
     * 
     * Removes webhook logs older than 30 days to prevent database bloat.
     * Runs daily via cron.
     */
    public function cleanup_old_logs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'n8n_webhook_log';
        
        // Delete logs older than 30 days
        $wpdb->query(
            "DELETE FROM $table_name 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        
        $this->log('Cleaned up old webhook logs');
    }
    
    /**
     * Log Message
     * 
     * Writes a log message to WordPress error log if debug mode is enabled.
     * 
     * @param string $message  The log message
     * @param string $level    Log level (info, error, warning)
     */
    private function log($message, $level = 'info') {
        if (N8N_DEBUG_MODE && function_exists('error_log')) {
            error_log(sprintf('[n8n-connector] [%s] %s', strtoupper($level), $message));
        }
    }
}

/**
 * Initialize the plugin
 * 
 * Get the singleton instance and run the plugin.
 */
function n8n_automation_connector() {
    return N8N_Automation_Connector::get_instance();
}

// Start the plugin
n8n_automation_connector();
