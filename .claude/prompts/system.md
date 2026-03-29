# System Prompt — n8n Automation Connector

You are assisting with development of a WordPress plugin that connects WordPress to n8n automation workflows.

## Context
- **Stack:** PHP 7.4+, WordPress 5.8+, WooCommerce (optional integration), REST API, MySQL
- **Plugin slug:** `n8n-automation-connector`
- **Text domain:** `n8n-automation-connector`

## Code Style Rules

1. **Constants first** — use existing constants from `config/constants.php` rather than hardcoding values. Never duplicate a constant definition.

2. **Singleton pattern** — new classes in `includes/` must follow the existing singleton pattern:
   ```php
   private static $instance = null;
   public static function get_instance() {
       if (null === self::$instance) self::$instance = new self();
       return self::$instance;
   }
   private function __construct() { /* register hooks here */ }
   ```

3. **Security — mandatory checks:**
   - `if (!defined('ABSPATH')) exit;` at the top of every PHP file
   - Sanitize all input: `sanitize_text_field()`, `absint()`, `wp_kses_post()` as appropriate
   - Escape all output: `esc_html()`, `esc_attr()`, `esc_url()`
   - Verify nonces on every admin form submission (`wp_nonce_field` / `check_admin_referer`)
   - Use `$wpdb->prepare()` for every direct DB query

4. **Logging** — wrap all debug output in a `N8N_DEBUG_MODE` check:
   ```php
   if (defined('N8N_DEBUG_MODE') && N8N_DEBUG_MODE) {
       error_log('[n8n] ' . $message);
   }
   ```

5. **REST API** — new endpoints belong in `includes/class-rest-api.php` under the `n8n-connector/v1` namespace. Always define a `permission_callback`.

6. **Prefix everything** — functions, classes, hooks, option names, and DB tables must be prefixed `n8n_` or `N8N_` to avoid collisions.

7. **No unnecessary abstractions** — solve the task directly. Don't add helpers, traits, or base classes unless the task genuinely requires shared logic across 3+ callsites.
