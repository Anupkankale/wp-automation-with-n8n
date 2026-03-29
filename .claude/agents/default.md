# Default Agent — n8n Automation Connector

## Plugin Purpose
WordPress plugin that bridges WordPress events to n8n automation workflows via webhooks and a custom REST API. Supports WooCommerce orders, Contact Form 7 submissions, user registrations, posts, and comments.

## Architecture

### Class Structure (Singleton Pattern)
All core classes use the singleton pattern (`get_instance()` + private `__construct()`).

| Class | File | Role |
|-------|------|------|
| `N8N_Automation_Connector` | `n8n-automation-connector.php` | Main entry point, hook registration |
| `N8N_Webhook_Handler` | `includes/class-webhook-handler.php` | Sends events to n8n webhook URL |
| `N8N_REST_API` | `includes/class-rest-api.php` | Custom REST endpoints for WordPress manipulation |
| `N8N_Admin` | `admin/class-admin.php` | Admin menu/page registration and asset enqueueing |

### Configuration
All configuration lives in `config/constants.php` using `if (!defined(...))` for override-safe definitions. All constants are prefixed `N8N_`.

Key constants:
- `N8N_WEBHOOK_URL` — target n8n webhook endpoint
- `N8N_API_KEY` — optional auth key
- `N8N_DEBUG_MODE` — enables verbose error_log output
- `N8N_WEBHOOK_TIMEOUT` — HTTP timeout (default 15s)
- `N8N_LOG_TABLE` — DB table name for webhook logs

### WordPress Integration Patterns
- Hooks registered via `add_action` / `add_filter` in `__construct`
- REST endpoints via `register_rest_route()` under `n8n-connector/v1` namespace
- Admin pages via `add_menu_page()` / `add_submenu_page()`
- Direct-access guard: `if (!defined('ABSPATH')) exit;` at top of every file

## Coding Conventions
- All symbols prefixed `N8N_` (classes, constants, functions)
- Follow WordPress Coding Standards (snake_case functions/variables, PascalCase classes)
- Use `$wpdb` for all direct database queries; prepared statements required
- Log debug output only when `N8N_DEBUG_MODE` is `true`
- PHP 7.4+ compatible; avoid named arguments or match expressions
