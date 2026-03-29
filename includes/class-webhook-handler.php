<?php
/**
 * Webhook Handler Class
 * 
 * Handles sending WordPress events to n8n via webhooks.
 * This is the core component that monitors WordPress for events
 * and sends them to n8n for processing.
 * 
 * @package N8N_Automation_Connector
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * N8N Webhook Handler Class
 * 
 * Responsible for:
 * - Formatting event payloads
 * - Sending HTTP requests to n8n
 * - Handling response errors
 * - Logging webhook attempts
 */
class N8N_Webhook_Handler {
    
    /**
     * Single instance of the class
     * 
     * @var N8N_Webhook_Handler
     */
    private static $instance = null;
    
    /**
     * Webhook URL endpoint
     * 
     * @var string
     */
    private $webhook_url;
    
    /**
     * API key for authentication
     * 
     * @var string
     */
    private $api_key;
    
    /**
     * Debug mode flag
     * 
     * @var bool
     */
    private $debug_mode;
    
    /**
     * Get singleton instance
     * 
     * @return N8N_Webhook_Handler
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
     * Initializes webhook handler with configuration from wp-config.php
     */
    private function __construct() {
        // Load configuration from constants
        $this->webhook_url = defined('N8N_WEBHOOK_URL') ? N8N_WEBHOOK_URL : '';
        $this->api_key = defined('N8N_API_KEY') ? N8N_API_KEY : '';
        $this->debug_mode = defined('N8N_DEBUG_MODE') ? N8N_DEBUG_MODE : false;
        
        // Log initialization
        if ($this->debug_mode) {
            $this->log('Webhook handler initialized');
        }
    }
    
    /**
     * Send Event to n8n
     * 
     * Main method to send any WordPress event to n8n webhook.
     * Handles the complete lifecycle: formatting, sending, logging.
     * 
     * @param string $event_type Type of event (e.g., 'woocommerce_new_order')
     * @param array  $event_data Data associated with the event
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function send_event($event_type, $event_data) {
        // Validate webhook URL is configured
        if (empty($this->webhook_url)) {
            $error = new WP_Error('no_webhook_url', 'n8n webhook URL not configured');
            $this->log('Failed to send event: No webhook URL configured', 'error');
            return $error;
        }
        
        // Generate unique event ID for tracking
        $event_id = $this->generate_event_id();
        
        // Build webhook payload
        $payload = $this->build_payload($event_type, $event_data, $event_id);
        
        // Allow other plugins to modify payload
        $payload = apply_filters('n8n_webhook_payload', $payload, $event_type);
        
        // Send HTTP request to n8n
        $response = $this->send_http_request($payload);
        
        // Log the attempt
        $this->log_webhook_attempt($event_id, $event_type, $payload, $response);
        
        // Check for errors
        if (is_wp_error($response)) {
            $this->log(
                sprintf('Webhook error for event %s: %s', $event_type, $response->get_error_message()),
                'error'
            );
            return $response;
        }
        
        // Log success
        $this->log(
            sprintf('Successfully sent event %s (ID: %s)', $event_type, $event_id),
            'info'
        );
        
        return true;
    }
    
    /**
     * Build Webhook Payload
     * 
     * Creates the JSON payload to send to n8n.
     * Includes event metadata and site information.
     * 
     * @param string $event_type Type of event
     * @param array  $event_data Event data
     * @param string $event_id   Unique event identifier
     * @return array Complete payload ready for JSON encoding
     */
    private function build_payload($event_type, $event_data, $event_id) {
        return array(
            // Event identification
            'event' => $event_type,
            'event_id' => $event_id,
            'timestamp' => current_time('mysql'),
            
            // Event data (the actual information)
            'data' => $event_data,
            
            // Site context
            'site' => array(
                'url' => get_site_url(),
                'name' => get_bloginfo('name'),
                'admin_email' => get_option('admin_email'),
                'wordpress_version' => get_bloginfo('version'),
                'plugin_version' => N8N_CONNECTOR_VERSION,
            ),
            
            // Request metadata
            'metadata' => array(
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? 
                    sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
                'ip_address' => $this->get_client_ip(),
                'request_time' => microtime(true),
            ),
        );
    }
    
    /**
     * Send HTTP Request to n8n
     * 
     * Performs the actual HTTP POST request to n8n webhook endpoint.
     * Uses WordPress HTTP API for compatibility and security.
     * 
     * @param array $payload Webhook payload
     * @return array|WP_Error Response array or WP_Error on failure
     */
    private function send_http_request($payload) {
        // Prepare HTTP headers
        $headers = array(
            'Content-Type' => 'application/json',
        );
        
        // Add authentication if API key is configured
        if (!empty($this->api_key)) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
        }
        
        // Get timeout setting (default: 15 seconds)
        $timeout = defined('N8N_WEBHOOK_TIMEOUT') ? N8N_WEBHOOK_TIMEOUT : 15;
        
        // Prepare request arguments
        $args = array(
            'headers' => $headers,
            'body' => wp_json_encode($payload),
            'timeout' => $timeout,
            'blocking' => false, // Non-blocking for better performance
            'httpversion' => '1.1',
            'sslverify' => true, // Verify SSL certificates
        );
        
        // Allow filtering of request arguments
        $args = apply_filters('n8n_webhook_request_args', $args, $payload);
        
        // Send POST request
        $response = wp_remote_post($this->webhook_url, $args);
        
        return $response;
    }
    
    /**
     * Log Webhook Attempt
     * 
     * Stores webhook delivery attempt in database for monitoring.
     * This helps with debugging and provides an audit trail.
     * 
     * @param string       $event_id   Unique event ID
     * @param string       $event_type Type of event
     * @param array        $payload    Webhook payload
     * @param array|object $response   HTTP response
     */
    private function log_webhook_attempt($event_id, $event_type, $payload, $response) {
        global $wpdb;
        
        // Get table name
        $table_name = $wpdb->prefix . 'n8n_webhook_log';
        
        // Determine status from response
        $status = 'pending';
        $response_body = '';
        
        if (is_wp_error($response)) {
            $status = 'failed';
            $response_body = $response->get_error_message();
        } elseif (isset($response['response']['code'])) {
            $status = ($response['response']['code'] === 200) ? 'success' : 'failed';
            $response_body = wp_remote_retrieve_body($response);
        }
        
        // Insert log entry
        $wpdb->insert(
            $table_name,
            array(
                'event_id' => $event_id,
                'event_type' => $event_type,
                'payload' => wp_json_encode($payload),
                'response' => $response_body,
                'status' => $status,
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        // Fire action for other plugins to hook into
        do_action('n8n_webhook_logged', $event_id, $event_type, $status);
    }
    
    /**
     * Generate Unique Event ID
     * 
     * Creates a unique identifier for each webhook event.
     * Format: evt_{timestamp}_{random}
     * 
     * @return string Unique event ID
     */
    private function generate_event_id() {
        return sprintf(
            'evt_%d_%s',
            time(),
            wp_generate_password(8, false)
        );
    }
    
    /**
     * Get Client IP Address
     * 
     * Attempts to get the real client IP address, handling proxies.
     * 
     * @return string Client IP address
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return sanitize_text_field($_SERVER[$key]);
            }
        }
        
        return 'Unknown';
    }
    
    /**
     * Log Message
     * 
     * Writes debug messages to WordPress error log.
     * Only logs when debug mode is enabled.
     * 
     * @param string $message Message to log
     * @param string $level   Log level (info, error, warning)
     */
    private function log($message, $level = 'info') {
        if ($this->debug_mode && function_exists('error_log')) {
            error_log(
                sprintf(
                    '[n8n-connector] [%s] %s',
                    strtoupper($level),
                    $message
                )
            );
        }
    }
    
    /**
     * Get Webhook Statistics
     * 
     * Retrieves statistics about webhook deliveries.
     * Used by admin dashboard.
     * 
     * @param int $days Number of days to look back (default: 7)
     * @return array Statistics array
     */
    public function get_statistics($days = 7) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'n8n_webhook_log';
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Get total count
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE created_at >= %s",
            $date_from
        ));
        
        // Get success count
        $success = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE status = 'success' AND created_at >= %s",
            $date_from
        ));
        
        // Get failed count
        $failed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE status = 'failed' AND created_at >= %s",
            $date_from
        ));
        
        // Get pending count
        $pending = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE status = 'pending' AND created_at >= %s",
            $date_from
        ));
        
        // Get events by type
        $by_type = $wpdb->get_results($wpdb->prepare(
            "SELECT event_type, COUNT(*) as count 
             FROM {$table_name} 
             WHERE created_at >= %s 
             GROUP BY event_type 
             ORDER BY count DESC",
            $date_from
        ), ARRAY_A);
        
        return array(
            'total' => (int) $total,
            'success' => (int) $success,
            'failed' => (int) $failed,
            'pending' => (int) $pending,
            'success_rate' => $total > 0 ? round(($success / $total) * 100, 2) : 0,
            'by_type' => $by_type,
            'period_days' => $days,
        );
    }
    
    /**
     * Get Recent Webhook Logs
     * 
     * Retrieves recent webhook delivery attempts.
     * 
     * @param int $limit Number of logs to retrieve (default: 50)
     * @return array Array of log entries
     */
    public function get_recent_logs($limit = 50) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'n8n_webhook_log';
        
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        ), ARRAY_A);
        
        return $logs;
    }
    
    /**
     * Clear Old Logs
     * 
     * Removes webhook logs older than specified days.
     * Called by cron job to prevent database bloat.
     * 
     * @param int $days Number of days to keep (default: 30)
     * @return int Number of rows deleted
     */
    public function clear_old_logs($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'n8n_webhook_log';
        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE created_at < %s",
            $date_threshold
        ));
        
        $this->log(
            sprintf('Cleared %d old webhook logs (older than %d days)', $deleted, $days),
            'info'
        );
        
        return $deleted;
    }
    
    /**
     * Test Webhook Connection
     * 
     * Sends a test webhook to verify configuration.
     * Used by admin settings page.
     * 
     * @return array Result with success status and message
     */
    public function test_connection() {
        $test_payload = array(
            'event' => 'test_connection',
            'data' => array(
                'message' => 'Test webhook from WordPress',
                'site_url' => get_site_url(),
                'test_time' => current_time('mysql'),
            ),
        );
        
        $result = $this->send_event('test_connection', $test_payload['data']);
        
        if (is_wp_error($result)) {
            return array(
                'success' => false,
                'message' => $result->get_error_message(),
            );
        }
        
        return array(
            'success' => true,
            'message' => 'Test webhook sent successfully!',
        );
    }
}
