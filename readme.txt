=== n8n Automation Connector ===
Contributors: yourname
Tags: automation, n8n, webhook, api, workflow
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Connect your WordPress site to n8n automation workflows via webhooks and REST API.

== Description ==

The n8n Automation Connector bridges your WordPress site with n8n workflow automation platform. Automatically send WordPress events (orders, forms, user registrations) to n8n and enable n8n to control WordPress via REST API.

**Features:**

* **Webhook Sender**: Automatically sends WordPress events to n8n
* **REST API Extensions**: Custom endpoints for n8n to interact with WordPress
* **Event Logging**: Track all webhook deliveries for debugging
* **Admin Dashboard**: Monitor automation activity
* **Supports Multiple Events**:
  - WooCommerce orders and status changes
  - Contact Form 7 submissions
  - User registrations
  - Post publications
  - Comment additions
  - Custom events

**Supported WordPress Events:**

* WooCommerce new orders
* WooCommerce order status changes
* Contact Form 7 form submissions
* New user registrations
* Post published events
* Comments added
* Custom post type events

**Requirements:**

* WordPress 6.0 or higher
* PHP 8.0 or higher
* n8n instance (self-hosted or cloud)
* OpenAI API key (for AI features)

== Installation ==

**Automatic Installation:**

1. Go to WordPress Admin → Plugins → Add New
2. Click "Upload Plugin"
3. Choose `n8n-automation-connector.zip`
4. Click "Install Now"
5. Click "Activate"

**Manual Installation:**

1. Upload `n8n-automation-connector` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings in WordPress Admin → n8n Connector

**Configuration:**

Add these constants to your `wp-config.php` file:

```php
// Required: n8n webhook URL
define('N8N_WEBHOOK_URL', 'http://your-n8n-instance.com/webhook/wordpress-events');

// Optional: API key for authentication
define('N8N_API_KEY', 'your-secret-api-key');

// Optional: Enable debug logging
define('N8N_DEBUG_MODE', true);
```

== Frequently Asked Questions ==

= What is n8n? =

n8n is a free, open-source workflow automation tool that lets you connect apps and automate tasks. Think of it as "IFTTT for developers" but self-hosted and more powerful.

= Do I need a paid n8n subscription? =

No! n8n is free and open-source. You can self-host it on your own server or use their cloud offering.

= What WordPress events are supported? =

Currently supported:
- WooCommerce orders (new, status changes)
- Contact Form 7 submissions
- User registrations
- Post publications
- Comments
- Custom events (you can add your own)

= How do I debug webhook issues? =

1. Enable debug mode: `define('N8N_DEBUG_MODE', true);`
2. Check WordPress Admin → n8n Connector → Webhook Logs
3. Review WordPress debug.log file
4. Verify n8n webhook URL is accessible

= Is this plugin secure? =

Yes! The plugin:
- Sanitizes all inputs
- Uses WordPress nonces for security
- Supports API key authentication
- Follows WordPress coding standards
- Validates all webhook payloads

= Can I customize which events are sent? =

Yes! Use WordPress filters to customize event data:

```php
add_filter('n8n_webhook_payload', function($payload, $event_type) {
    // Customize payload here
    return $payload;
}, 10, 2);
```

== Screenshots ==

1. Admin Dashboard - Monitor webhook activity
2. Webhook Logs - Debug delivery issues
3. Settings Page - Configure n8n connection

== Changelog ==

= 1.0.0 =
* Initial release
* WooCommerce integration
* Contact Form 7 integration
* User registration hooks
* Post publication hooks
* Comment hooks
* REST API endpoints
* Admin dashboard
* Webhook logging
* Debug mode

== Upgrade Notice ==

= 1.0.0 =
Initial release of n8n Automation Connector.

== Configuration Examples ==

**Basic Setup:**

```php
define('N8N_WEBHOOK_URL', 'https://n8n.yourdomain.com/webhook/wordpress-events');
```

**With Authentication:**

```php
define('N8N_WEBHOOK_URL', 'https://n8n.yourdomain.com/webhook/wordpress-events');
define('N8N_API_KEY', 'your-secret-key-here');
```

**Production Setup:**

```php
define('N8N_WEBHOOK_URL', 'https://n8n.yourdomain.com/webhook/wordpress-events');
define('N8N_API_KEY', 'your-secret-key-here');
define('N8N_DEBUG_MODE', false);
define('N8N_WEBHOOK_TIMEOUT', 15);
```

== Custom Events ==

Add your own webhook events:

```php
// Send custom event
do_action('n8n_send_webhook', array(
    'event' => 'custom_event',
    'data' => array(
        'user_id' => get_current_user_id(),
        'custom_field' => 'value'
    )
));
```

 
