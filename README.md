# n8n Automation Connector - WordPress Plugin

A professional WordPress plugin that bridges WordPress events to n8n automation workflows via webhooks and REST API.

**Version:** 1.0.0
**Author:** Anup Kankale
**License:** MIT

---

## 📋 Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Project Structure](#project-structure)
- [Configuration](#configuration)
- [API Endpoints](#api-endpoints)
- [Supported Events](#supported-events)
- [Development](#development)
- [Troubleshooting](#troubleshooting)

---

## ✨ Features

- **Webhook Integration**: Send WordPress events to n8n workflows automatically
- **REST API Endpoints**: Expose custom endpoints for n8n to interact with WordPress
- **Admin Dashboard**: Beautiful dashboard showing webhook statistics and logs
- **Event Tracking**: Monitor all webhook deliveries with detailed logs
- **Easy Configuration**: Configure via `wp-config.php` constants
- **Multiple Event Support**: Monitor WooCommerce orders, Contact Form 7, user registration, posts, and comments
- **Debug Mode**: Enable detailed logging for troubleshooting

---

## 🔧 Installation

1. **Extract the plugin** to `/wp-content/plugins/n8n-automation-connector/`
2. **Activate the plugin** in WordPress admin panel
3. **Configure in `wp-config.php`** (see Configuration section below)

---

## 📁 Project Structure

```
n8n-automation-connector/
├── n8n-automation-connector.php      # Main plugin file
├── config/
│   └── constants.php                 # Plugin constants and configuration
├── includes/
│   ├── class-webhook-handler.php     # Webhook sending logic
│   └── class-rest-api.php            # REST API endpoints
├── admin/
│   ├── class-admin.php               # Admin dashboard controller
│   ├── pages/
│   │   ├── dashboard.php             # Dashboard template
│   │   ├── logs.php                  # Webhook logs template
│   │   └── settings.php              # Settings template
│   └── assets/
│       ├── css/
│       │   └── admin.css             # Admin styling
│       └── js/
│           └── admin.js              # Admin interactions
├── public/
│   └── assets/
│       ├── css/
│       │   └── public.css            # Public-facing styles
│       └── js/
│           └── public.js             # Public-facing scripts
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── languages/                         # Translation files
├── readme.txt                         # WordPress plugin readme
├── README.md                          # This file
└── CHANGELOG.md                       # Version history
```

### Folder Organization

- **`config/`** - Plugin configuration and constants
- **`includes/`** - Core plugin classes (webhook handler, REST API)
- **`admin/`** - Admin dashboard, pages, and admin-specific assets
- **`public/`** - Public-facing components and assets
- **`assets/`** - Shared assets across the plugin
- **`languages/`** - Translation files for internationalization

---

## ⚙️ Configuration

### Required Configuration

Add this to your `wp-config.php`:

```php
// n8n Webhook URL (Required)
define('N8N_WEBHOOK_URL', 'https://your-n8n-instance.com/webhook/wordpress-events');
```

### Optional Configuration

```php
// API Key for authentication (Optional but recommended)
define('N8N_API_KEY', 'your-secret-api-key');

// Enable debug logging (Optional)
define('N8N_DEBUG_MODE', true);

// Webhook timeout in seconds (Optional, default: 15)
define('N8N_WEBHOOK_TIMEOUT', 15);
```

### Test Connection

After configuring, visit **n8n Connector > Settings** in your WordPress admin and click "Test Webhook Connection" to verify your setup.

---

## 🔗 API Endpoints

All endpoints use the REST API namespace `/wp-json/custom/v1/`

### 1. Install Theme
**Endpoint:** `/install-theme`
**Method:** `POST`
**Permission:** Admin

Installs a WordPress theme from a URL (typically GitHub).

**Parameters:**
- `theme_url` (required): URL to theme ZIP file
- `activate` (optional): Activate theme after install (boolean)

**Response:**
```json
{
  "success": true,
  "message": "Theme installed successfully",
  "theme": {
    "name": "Theme Name",
    "version": "1.0.0",
    "slug": "theme-slug",
    "activated": false
  }
}
```

### 2. Log Webhook
**Endpoint:** `/log-webhook`
**Method:** `POST`
**Permission:** API Key (if configured)

Allows n8n to log webhook processing results back to WordPress.

**Parameters:**
- `event_id` (required): Unique event identifier
- `event_type` (required): Type of event
- `processed_at` (required): Processing timestamp
- `status` (required): Status (completed, failed, processing)
- `actions_taken` (optional): Array of actions taken

### 3. Create Post Type
**Endpoint:** `/create-post-type`
**Method:** `POST`
**Permission:** Admin

Dynamically creates a custom post type.

**Parameters:**
- `post_type` (required): Post type slug (max 20 chars)
- `args` (required): Post type registration arguments

### 4. Get Webhook Statistics
**Endpoint:** `/webhook-stats`
**Method:** `GET`
**Permission:** API Key (if configured)

Returns webhook delivery statistics.

**Parameters:**
- `days` (optional): Number of days to analyze (1-90, default: 7)

**Response:**
```json
{
  "total": 150,
  "success": 145,
  "failed": 5,
  "pending": 0,
  "success_rate": 96.67,
  "by_type": [
    {
      "event_type": "woocommerce_new_order",
      "count": 50
    }
  ]
}
```

### 5. Test Connection
**Endpoint:** `/test-connection`
**Method:** `GET`
**Permission:** Public

Verifies API is accessible and returns basic info.

---

## 📡 Supported Events

### WooCommerce
- **woocommerce_new_order** - When a new order is created
- **woocommerce_order_status_changed** - When order status changes

### Contact Form 7
- **cf7_form_submission** - When a form is submitted

### Core WordPress
- **user_registered** - When a new user registers
- **publish_post** - When a post is published
- **comment_added** - When a comment is added

### Custom Events
Trigger custom webhooks anywhere in your code:

```php
do_action('n8n_send_webhook', [
    'event_type' => 'custom_event',
    'data' => ['key' => 'value']
]);
```

---

## 🛠️ Development

### Adding New Event Handlers

1. Add event registration in `n8n-automation-connector.php`:
```php
add_action('your_event', array($this, 'handle_your_event'), 10, 1);
```

2. Create handler method:
```php
public function handle_your_event($data) {
    $payload = [
        'event_type' => 'your_event',
        'data' => $data
    ];
    $this->send_webhook($payload);
}
```

### Extending Admin Pages

1. Create new template in `admin/pages/`
2. Add menu item in `N8N_Admin::add_admin_menu()`
3. Create render method in `N8N_Admin` class

### Adding Assets

- **Admin CSS:** `admin/assets/css/admin.css`
- **Admin JS:** `admin/assets/js/admin.js`
- **Public CSS:** `public/assets/css/public.css`
- **Public JS:** `public/assets/js/public.js`

---

## 🐛 Troubleshooting

### Webhooks Not Being Sent

1. Check that `N8N_WEBHOOK_URL` is configured in `wp-config.php`
2. Enable debug mode: `define('N8N_DEBUG_MODE', true);`
3. Check WordPress error log for debug messages
4. Use the "Test Webhook Connection" button in settings

### Dashboard Not Showing Data

1. Verify webhooks have been triggered (should appear in Webhook Logs)
2. Check that webhook handler is properly initialized
3. Review WordPress error log for any PHP errors

### Permission Issues on API Endpoints

1. For admin endpoints: Ensure user is logged in with `manage_options` capability
2. For API key protected endpoints: Include `Authorization: Bearer YOUR_API_KEY` header

### Database Issues

If webhook logs table doesn't exist:
1. Re-activate the plugin to run activation hooks
2. Check that WordPress database user has CREATE TABLE permissions

---

## 📝 License

MIT License - See LICENSE file for details

---

## 👤 Author

**Anup Kankale**
📧 Email: [your-email@example.com](mailto:your-email@example.com)
🔗 LinkedIn: [linkedin.com/in/anupkankale](https://www.linkedin.com/in/anupkankale/)

---

## 🙏 Support

For issues, feature requests, or contributions, please visit the plugin repository or contact the author.
