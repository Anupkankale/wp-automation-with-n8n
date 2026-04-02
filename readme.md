 🔄 n8n Automation Connector for WordPress

Connect your WordPress site to n8n workflow automation — publish AI-generated, SEO-optimized blog posts in under 15 seconds.

✨ What is This?
A WordPress plugin that bridges your site with n8n, the powerful open-source workflow automation platform. Automatically send WordPress events to n8n and let n8n control your WordPress site via REST API.
🚀 Key Features

Webhook Sender — Automatically push WordPress events to n8n
REST API Extensions — Custom endpoints for n8n ↔ WordPress communication
Multi-Event Support — WooCommerce orders, Contact Form 7, user registrations, posts & comments
Event Logging — Debug-friendly webhook delivery tracking
Admin Dashboard — Monitor all automation activity in one place

📋 Requirements
RequirementVersionWordPress6.0+PHP8.0+n8nSelf-hosted or Cloud
⚡ Quick Start

Upload the plugin to /wp-content/plugins/
Activate via WordPress Admin → Plugins
Add to your wp-config.php:

phpdefine('N8N_WEBHOOK_URL', 'https://your-n8n.com/webhook/wordpress-events');
define('N8N_API_KEY', 'your-secret-api-key');

Configure in WordPress Admin → n8n Connector

📁 Project Structure
├── admin/                  # Admin dashboard & settings
├── config/                 # Configuration files
├── includes/               # Core plugin functionality
├── n8n-workflow-template/  # Ready-to-use n8n workflow templates
├── public/assets/          # Frontend assets
└── n8n-automation-connector.php  # Main plugin file
📄 License
MIT License — feel free to use, modify, and distribute.

Built with ❤️ for WordPress by Anup