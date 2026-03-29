# n8n Automation Connector — Claude Context

## Plugin Overview
WordPress plugin that bridges WordPress events to n8n automation workflows via webhooks and a custom REST API.

- **Version:** 1.0.0
- **Author:** Anup Kankale
- **License:** MIT
- **Text Domain:** `n8n-automation-connector`
- **Stack:** PHP 7.4+, WordPress 5.8+, WooCommerce (optional), MySQL

---

## File Map

```
n8n-automation-connector/
├── n8n-automation-connector.php       # Main plugin entry point + core class
├── config/
│   └── constants.php                  # All N8N_ constants (overridable in wp-config.php)
├── includes/
│   ├── class-webhook-handler.php      # Webhook delivery engine, HTTP requests, DB logging
│   └── class-rest-api.php             # 5 custom REST API endpoints (static class)
├── admin/
│   ├── class-admin.php                # Admin menu, page rendering, asset loading (static class)
│   ├── pages/
│   │   ├── dashboard.php              # Webhook statistics dashboard template
│   │   ├── logs.php                   # Webhook delivery logs viewer template
│   │   └── settings.php              # Configuration & documentation template
│   └── assets/
│       ├── css/admin.css              # Admin dashboard styles
│       └── js/admin.js                # Admin dashboard JS
├── public/assets/                     # Frontend assets (css/js — not yet used)
└── languages/                         # i18n translation files
```

---

## Class Reference

| Class | File | Pattern | Responsibility |
|-------|------|---------|----------------|
| `N8N_Automation_Connector` | `n8n-automation-connector.php` | Singleton | Plugin init, hook registration, all event handlers |
| `N8N_Webhook_Handler` | `includes/class-webhook-handler.php` | Singleton | HTTP POST to n8n, payload building, DB logging, stats |
| `N8N_REST_API` | `includes/class-rest-api.php` | Static | REST route registration and handlers |
| `N8N_Admin` | `admin/class-admin.php` | Static | Admin menu, page render, asset enqueue |

**Singleton pattern used in `N8N_Automation_Connector` and `N8N_Webhook_Handler`:**
```php
private static $instance = null;
public static function get_instance() {
    if (null === self::$instance) self::$instance = new self();
    return self::$instance;
}
private function __construct() { /* register hooks here */ }
```

---

## Constants (`config/constants.php`)

All constants use `if (!defined(...))` — override any in `wp-config.php`.

| Constant | Default | Required | Purpose |
|----------|---------|----------|---------|
| `N8N_CONNECTOR_VERSION` | `'1.0.0'` | — | Plugin version |
| `N8N_CONNECTOR_PATH` | `plugin_dir_path()` | — | Absolute path to plugin dir |
| `N8N_CONNECTOR_URL` | `plugin_dir_url()` | — | Plugin URL for assets |
| `N8N_WEBHOOK_URL` | `''` | **Yes** | n8n webhook endpoint URL |
| `N8N_API_KEY` | `''` | No | Bearer token for authentication |
| `N8N_DEBUG_MODE` | `false` | No | Enable `error_log` debug output |
| `N8N_WEBHOOK_TIMEOUT` | `15` | No | HTTP request timeout in seconds |
| `N8N_LOG_TABLE` | `'n8n_webhook_log'` | — | DB table name (without WP prefix) |

**Minimum wp-config.php setup:**
```php
define('N8N_WEBHOOK_URL', 'https://your-n8n.com/webhook/wordpress-events');
define('N8N_API_KEY', 'your-secret-key');       // recommended
define('N8N_DEBUG_MODE', true);                  // during development
```

---

## WordPress Hooks

### Lifecycle
| Hook | Handler | What it does |
|------|---------|--------------|
| `register_activation_hook` | `N8N_Automation_Connector::activate()` | Creates DB table, sets default options |
| `register_deactivation_hook` | `N8N_Automation_Connector::deactivate()` | Clears cron jobs |
| `plugins_loaded` | `N8N_Automation_Connector::init()` | Inits webhook handler, loads text domain, schedules cron |
| `rest_api_init` | `N8N_REST_API::register_routes()` | Registers all REST endpoints |
| `admin_menu` | `N8N_Admin::add_admin_menu()` | Creates admin menu with 3 subpages |
| `admin_enqueue_scripts` | `N8N_Admin::enqueue_admin_scripts()` | Loads CSS/JS on plugin admin pages |
| `n8n_cleanup_logs` (cron) | `N8N_Automation_Connector::cleanup_old_logs()` | Deletes logs older than 30 days (daily) |

### Event Handlers
| Hook | Handler | Event type sent |
|------|---------|----------------|
| `woocommerce_new_order` | `handle_new_order()` | `woocommerce_new_order` |
| `woocommerce_order_status_changed` | `handle_order_status_change()` | `woocommerce_order_status_changed` |
| `wpcf7_mail_sent` | `handle_cf7_submission()` | `cf7_form_submission` |
| `user_register` | `handle_user_registration()` | `user_registered` |
| `publish_post` | `handle_post_published()` | `post_published` |
| `wp_insert_comment` | `handle_comment_added()` | `comment_added` |

### Extensibility Filters/Actions
```php
apply_filters('n8n_webhook_payload', $payload)        // Modify payload before send
apply_filters('n8n_webhook_request_args', $args)      // Modify HTTP request args
do_action('n8n_webhook_logged', $log_id, $payload)    // Fires after DB logging

// Send custom event from anywhere:
do_action('n8n_send_webhook', ['event_type' => 'my_event', 'data' => []]);
```

---

## REST API Endpoints

**Namespace:** `custom/v1` — Base: `/wp-json/custom/v1/`

| Endpoint | Method | Permission | Purpose |
|----------|--------|-----------|---------|
| `/test-connection` | GET | Public | Verify API connectivity |
| `/webhook-stats` | GET | API Key | Delivery statistics (`?days=7`) |
| `/log-webhook` | POST | API Key | n8n logs processing result back |
| `/install-theme` | POST | Admin | Install WP theme from ZIP URL |
| `/create-post-type` | POST | Admin | Dynamically register custom post type |

**Auth header for API Key endpoints:**
```
Authorization: Bearer your-secret-key
```

**Permission logic:**
- Admin: `current_user_can('manage_options')`
- API Key: `hash_equals()` comparison against `N8N_API_KEY`; open if no key set

---

## Supported Events & Payload Structure

All events share this envelope:
```json
{
  "event": "event_type_name",
  "event_id": "evt_1234567890_abcdefgh",
  "timestamp": "2024-01-15 10:30:45",
  "data": { },
  "site": {
    "url": "https://example.com",
    "name": "Site Name",
    "admin_email": "admin@example.com",
    "wordpress_version": "6.4.1",
    "plugin_version": "1.0.0"
  },
  "metadata": {
    "user_agent": "...",
    "ip_address": "...",
    "request_time": 1705317045.12
  }
}
```

**Event types:** `woocommerce_new_order`, `woocommerce_order_status_changed`, `cf7_form_submission`, `user_registered`, `post_published`, `comment_added`, `test_connection`

---

## Database Table

**Table:** `{wpdb->prefix}n8n_webhook_log` (e.g., `wp_n8n_webhook_log`)

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint unsigned AUTO_INCREMENT | Primary key |
| `event_id` | varchar(100) | Format: `evt_{timestamp}_{random}` |
| `event_type` | varchar(50) | e.g., `woocommerce_new_order` |
| `payload` | longtext | JSON-encoded full payload |
| `response` | longtext | HTTP response body or error |
| `status` | varchar(20) DEFAULT `'pending'` | `pending`, `success`, `failed`, `processing` |
| `created_at` | datetime DEFAULT CURRENT_TIMESTAMP | Indexed |

Indexes: `event_type`, `created_at`

---

## Coding Conventions

- **Prefix everything:** Classes `N8N_`, constants `N8N_`, hooks `n8n_`, options `n8n_`
- **Direct-access guard:** `if (!defined('ABSPATH')) exit;` — top of every PHP file
- **Security:** `sanitize_text_field()` / `absint()` for input; `esc_html()` / `esc_attr()` / `esc_url()` for output; `$wpdb->prepare()` for all SQL; `wp_nonce_field()` + `check_admin_referer()` for forms
- **Debug logging:**
  ```php
  if (defined('N8N_DEBUG_MODE') && N8N_DEBUG_MODE) {
      error_log('[n8n-connector] ' . $message);
  }
  ```
- **New REST endpoints:** Add to `includes/class-rest-api.php` under `custom/v1`, always define `permission_callback`
- **New event handlers:** Register hook in `N8N_Automation_Connector::__construct()`, add handler method, call `$this->send_webhook($payload)`
- **PHP standards:** WordPress Coding Standards, snake_case methods/variables, PascalCase classes

---

## Webhook Sending Mechanism

- **Method:** `wp_remote_post()` — async, non-blocking (`'blocking' => false`)
- **Timeout:** `N8N_WEBHOOK_TIMEOUT` seconds (default 15)
- **SSL:** Verified (`sslverify => true`)
- **Headers:** `Content-Type: application/json` + `Authorization: Bearer {key}` (if key set)
- **Flow:** Validate URL → generate event ID → build payload → apply filter → POST → log to DB → fire action
