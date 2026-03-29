# Changelog

All notable changes to the n8n Automation Connector plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - 2026-03-29

### Added

#### Project Structure
- Professional folder organization following WordPress plugin best practices
- Separate `admin/`, `public/`, `config/`, `includes/` directories
- Template-based admin pages (`dashboard.php`, `logs.php`, `settings.php`)
- Asset organization with dedicated CSS/JS folders for admin and public

#### Core Features
- **Webhook Integration**: Send WordPress events to n8n workflows
- **REST API Endpoints**: Custom endpoints for n8n ↔ WordPress communication
- **Admin Dashboard**: Statistics and monitoring dashboard
- **Event Logging**: Database storage of all webhook deliveries
- **Debug Mode**: Detailed logging for troubleshooting

#### Supported Events
- WooCommerce order creation and status changes
- Contact Form 7 form submissions
- User registration
- Post publication
- Comment addition
- Custom events via action hooks

#### Admin Features
- Dashboard with statistics cards
- Webhook logs viewer with detailed payload inspection
- Settings page with configuration instructions
- Connection testing tool
- Event type breakdown statistics
- Auto-refreshing dashboard

#### Configuration
- `wp-config.php` constant-based configuration
- Optional API key authentication
- Customizable webhook timeout
- Debug mode for troubleshooting
- Database table auto-creation on activation

#### API Endpoints
- `/wp-json/custom/v1/install-theme` - Install themes from URL
- `/wp-json/custom/v1/log-webhook` - Log webhook processing results
- `/wp-json/custom/v1/create-post-type` - Create custom post types
- `/wp-json/custom/v1/webhook-stats` - Get delivery statistics
- `/wp-json/custom/v1/test-connection` - Test API connectivity

### Documentation
- Comprehensive README.md with installation and configuration guide
- API endpoint documentation
- Project structure explanation
- Development guide for extending functionality
- Troubleshooting section

### Technical Details
- Singleton pattern for class instances
- WordPress security best practices (nonce validation, capability checks)
- Prepared database queries for security
- Non-blocking webhook sends for better performance
- SSL certificate verification enabled

---

## Future Improvements

### Planned for v1.1.0
- [ ] Webhook event filtering and transformation rules
- [ ] UI for managing webhook configurations
- [ ] Webhook retry mechanism for failed deliveries
- [ ] Integration with WordPress cron for scheduled webhooks
- [ ] Support for custom event mapping

### Planned for v2.0.0
- [ ] Multiple n8n instance support
- [ ] Advanced analytics and reporting
- [ ] Webhook signing for security
- [ ] Event queue system for high-traffic sites
- [ ] Webhook deduplication

---

## Version History

### Initial Release
- Version 1.0.0 released as part of DevXpert initiative
- Complete webhook integration with n8n
- Professional folder structure and best practices
- Comprehensive documentation

---

## Security

### Version 1.0.0 Security Features
- CSRF protection with WordPress nonces
- Admin capability checks
- API key authentication (optional)
- Prepared database statements
- SSL certificate verification
- Input sanitization and validation
- Output escaping in templates

---

## Support

For bug reports, feature requests, or questions:
1. Check the README.md troubleshooting section
2. Review the admin dashboard logs
3. Enable debug mode for detailed error information
4. Contact the plugin author

---

## Contributors

- **Anup Kankale** - Original Author and Maintainer

---

## License

MIT License - See LICENSE file in plugin root directory
