<?php
/**
 * REST API Handler Class
 * 
 * Provides custom REST API endpoints for n8n to interact with WordPress.
 * These endpoints allow n8n workflows to:
 * - Install and activate themes
 * - Create and manage posts
 * - Log webhook events
 * - Manage custom post types
 * 
 * @package N8N_Automation_Connector
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * N8N REST API Class
 * 
 * Registers and handles custom REST API endpoints.
 */
class N8N_REST_API {
    
    /**
     * API namespace
     * 
     * @var string
     */
    const NAMESPACE = 'custom/v1';
    
    /**
     * Register Routes
     * 
     * Registers all custom REST API endpoints.
     * Called by WordPress on 'rest_api_init' action.
     */
    public static function register_routes() {
        // Endpoint: Install theme from GitHub
        register_rest_route(self::NAMESPACE, '/install-theme', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'install_theme'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
            'args' => array(
                'theme_url' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'GitHub repository ZIP URL',
                    'validate_callback' => array(__CLASS__, 'validate_url'),
                ),
                'activate' => array(
                    'required' => false,
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'Whether to activate theme after install',
                ),
            ),
        ));
        
        // Endpoint: Log webhook event
        register_rest_route(self::NAMESPACE, '/log-webhook', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'log_webhook'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
            'args' => array(
                'event_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'event_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'processed_at' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'status' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('completed', 'failed', 'processing'),
                ),
                'actions_taken' => array(
                    'required' => false,
                    'type' => 'array',
                ),
            ),
        ));
        
        // Endpoint: Create custom post type
        register_rest_route(self::NAMESPACE, '/create-post-type', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'create_post_type'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
            'args' => array(
                'post_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_key',
                ),
                'args' => array(
                    'required' => true,
                    'type' => 'object',
                ),
            ),
        ));
        
        // Endpoint: Get webhook statistics
        register_rest_route(self::NAMESPACE, '/webhook-stats', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_webhook_stats'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
            'args' => array(
                'days' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 7,
                    'minimum' => 1,
                    'maximum' => 90,
                ),
            ),
        ));
        
        // Endpoint: Test connection
        register_rest_route(self::NAMESPACE, '/test-connection', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'test_connection'),
            'permission_callback' => '__return_true', // Public endpoint
        ));
    }
    
    /**
     * Install Theme Endpoint
     * 
     * Downloads and installs a WordPress theme from a URL (typically GitHub).
     * Used by n8n workflows to deploy generated themes.
     * 
     * @param WP_REST_Request $request Full request data
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public static function install_theme($request) {
        // Get parameters
        $theme_url = $request->get_param('theme_url');
        $activate = $request->get_param('activate');
        
        // Validate URL
        if (!filter_var($theme_url, FILTER_VALIDATE_URL)) {
            return new WP_Error(
                'invalid_url',
                'Invalid theme URL provided',
                array('status' => 400)
            );
        }
        
        // Load WordPress upgrader
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/theme.php';
        
        // Create upgrader instance
        $upgrader = new Theme_Upgrader(
            new WP_Ajax_Upgrader_Skin()
        );
        
        // Download and install theme
        $result = $upgrader->install($theme_url);
        
        // Check for errors
        if (is_wp_error($result)) {
            return new WP_Error(
                'installation_failed',
                $result->get_error_message(),
                array('status' => 500)
            );
        }
        
        // Get installed theme info
        $theme_info = $upgrader->theme_info();
        
        if (!$theme_info) {
            return new WP_Error(
                'theme_not_found',
                'Theme installed but could not be located',
                array('status' => 500)
            );
        }
        
        $theme_slug = $theme_info->get_stylesheet();
        
        // Activate theme if requested
        if ($activate) {
            switch_theme($theme_slug);
        }
        
        // Return success response
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Theme installed successfully',
            'theme' => array(
                'name' => $theme_info->get('Name'),
                'version' => $theme_info->get('Version'),
                'author' => $theme_info->get('Author'),
                'slug' => $theme_slug,
                'activated' => $activate,
            ),
        ), 200);
    }
    
    /**
     * Log Webhook Endpoint
     * 
     * Allows n8n to log back to WordPress that a webhook was processed.
     * This creates a complete audit trail.
     * 
     * @param WP_REST_Request $request Full request data
     * @return WP_REST_Response Response object
     */
    public static function log_webhook($request) {
        global $wpdb;
        
        // Get parameters
        $event_id = $request->get_param('event_id');
        $event_type = $request->get_param('event_type');
        $processed_at = $request->get_param('processed_at');
        $status = $request->get_param('status');
        $actions_taken = $request->get_param('actions_taken');
        
        // Update existing log or create new one
        $table_name = $wpdb->prefix . 'n8n_webhook_log';
        
        // Check if log exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE event_id = %s",
            $event_id
        ));
        
        if ($exists) {
            // Update existing log
            $wpdb->update(
                $table_name,
                array(
                    'status' => $status,
                    'response' => wp_json_encode(array(
                        'processed_at' => $processed_at,
                        'actions_taken' => $actions_taken,
                    )),
                ),
                array('event_id' => $event_id),
                array('%s', '%s'),
                array('%s')
            );
        } else {
            // Create new log
            $wpdb->insert(
                $table_name,
                array(
                    'event_id' => $event_id,
                    'event_type' => $event_type,
                    'status' => $status,
                    'response' => wp_json_encode(array(
                        'processed_at' => $processed_at,
                        'actions_taken' => $actions_taken,
                    )),
                    'created_at' => current_time('mysql'),
                ),
                array('%s', '%s', '%s', '%s', '%s')
            );
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Webhook logged successfully',
            'event_id' => $event_id,
        ), 200);
    }
    
    /**
     * Create Post Type Endpoint
     * 
     * Dynamically creates a custom post type.
     * Useful for n8n workflows that need to set up new content types.
     * 
     * @param WP_REST_Request $request Full request data
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public static function create_post_type($request) {
        $post_type = $request->get_param('post_type');
        $args = $request->get_param('args');
        
        // Validate post type name
        if (strlen($post_type) > 20) {
            return new WP_Error(
                'invalid_post_type',
                'Post type name must be 20 characters or less',
                array('status' => 400)
            );
        }
        
        // Register the post type
        $result = register_post_type($post_type, $args);
        
        if (is_wp_error($result)) {
            return new WP_Error(
                'registration_failed',
                $result->get_error_message(),
                array('status' => 500)
            );
        }
        
        // Store in option for persistence
        $registered_post_types = get_option('n8n_custom_post_types', array());
        $registered_post_types[$post_type] = $args;
        update_option('n8n_custom_post_types', $registered_post_types);
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Post type created successfully',
            'post_type' => $post_type,
        ), 200);
    }
    
    /**
     * Get Webhook Statistics Endpoint
     * 
     * Returns statistics about webhook deliveries.
     * 
     * @param WP_REST_Request $request Full request data
     * @return WP_REST_Response Response object
     */
    public static function get_webhook_stats($request) {
        $days = $request->get_param('days');
        
        $handler = N8N_Webhook_Handler::get_instance();
        $stats = $handler->get_statistics($days);
        
        return new WP_REST_Response($stats, 200);
    }
    
    /**
     * Test Connection Endpoint
     * 
     * Simple endpoint to verify API is accessible.
     * 
     * @param WP_REST_Request $request Full request data
     * @return WP_REST_Response Response object
     */
    public static function test_connection($request) {
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'n8n Automation Connector is active',
            'version' => N8N_CONNECTOR_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'site_url' => get_site_url(),
            'timestamp' => current_time('mysql'),
        ), 200);
    }
    
    /**
     * Check Admin Permission
     * 
     * Permission callback for admin-only endpoints.
     * Requires user to be logged in as administrator.
     * 
     * @param WP_REST_Request $request Full request data
     * @return bool True if user has permission
     */
    public static function check_admin_permission($request) {
        return current_user_can('manage_options');
    }
    
    /**
     * Check API Key
     * 
     * Permission callback for API key protected endpoints.
     * Checks for valid API key in Authorization header.
     * 
     * @param WP_REST_Request $request Full request data
     * @return bool True if API key is valid
     */
    public static function check_api_key($request) {
        // If no API key is configured, allow access
        if (!defined('N8N_API_KEY') || empty(N8N_API_KEY)) {
            return true;
        }
        
        // Get Authorization header
        $auth_header = $request->get_header('Authorization');
        
        if (!$auth_header) {
            return false;
        }
        
        // Extract API key from header
        // Format: "Bearer YOUR_API_KEY"
        $api_key = str_replace('Bearer ', '', $auth_header);
        
        // Compare with configured API key
        return hash_equals(N8N_API_KEY, trim($api_key));
    }
    
    /**
     * Validate URL
     * 
     * Validation callback for URL parameters.
     * 
     * @param string $value URL to validate
     * @return bool True if valid URL
     */
    public static function validate_url($value) {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
