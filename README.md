# Security & Comments Lite Tweaks

A lightweight WordPress plugin for essential security hardening and comment management tweaks. Built following WordPress coding standards and best practices.

## Description

Security & Comments Lite Tweaks provides a collection of essential security and comment management features to help protect and optimize your WordPress site. All settings are stored in a custom database table to avoid bloating the wp_options table.

## Features

### Security Settings

- **Hide WordPress Version** - Removes WordPress version number from HTML source for security
- **Disable Generator Meta Tag** - Removes generator meta tag from page head
- **Remove Script/Style Versions** - Removes version parameters from CSS/JS files for better caching
- **Disable Application Passwords** - Disables WordPress application passwords feature for enhanced security
- **Disable Code Editors** - Removes file and plugin editor from admin for security
- **Disable Admin Email Confirmation** - Removes the admin email verification prompt

### Comment Settings

- **Optimize Comment Scripts** - Only load comment reply scripts when comments are enabled and open
- **Disable Comment Hyperlinks** - Prevents automatic URL linking in comments for security and spam prevention
- **Disable Trackbacks & Pingbacks** - Disables automatic notifications when linking to other sites
- **Disable Comments Site-wide** - Completely removes comment functionality from your entire site

### Uninstall Settings

- **Cleanup on Uninstall** - Option to remove all plugin data when uninstalling

## Installation

1. Upload the `security-comments-lite-tweaks` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings at Tools → Security & Comments

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.0+ or MariaDB (MySQL 8.0+ recommended)

## File Structure

```
security-comments-lite-tweaks/
├── security-comments-lite-tweaks.php  # Main plugin file
├── README.md                          # This file
├── uninstall.php                      # Cleanup on uninstall
├── index.php                          # Security stub
├── assets/
│   ├── admin.css                      # Admin styles
│   ├── admin.js                       # Admin JavaScript
│   └── index.php                      # Security stub
└── includes/
    ├── class-database.php             # Database operations
    ├── class-core.php                 # Core functionality
    ├── class-admin.php                # Admin interface
    └── index.php                      # Security stub
```

## Usage

### Accessing Settings

Navigate to **Tools → Security & Comments** in your WordPress admin panel.

### Configuring Settings

1. Check the boxes for features you want to enable
2. Click "Save Settings" to apply changes
3. Settings take effect immediately after saving

### Uninstalling

1. Deactivate the plugin from the Plugins page
2. Delete the plugin
3. If "Cleanup on Uninstall" was enabled, all plugin data will be automatically removed
4. If disabled, plugin data will remain in the database for future reinstallation

## Technical Details

### Database Structure

The plugin creates a custom table `{prefix}_sclt_settings` with the following structure:

- `id` (bigint) - Primary key
- `setting_key` (varchar) - Unique setting identifier
- `setting_value` (varchar) - Setting value (0 or 1)

### Performance Optimization

- Settings are cached using WordPress Transients API (12-hour expiration)
- Lazy loading pattern prevents unnecessary database queries
- Admin assets only load on plugin settings page
- Conditional script loading based on page context

### Security Features

- All database queries use prepared statements
- Nonce verification for form submissions
- Capability checks in both render and save methods
- All output is properly escaped
- No external service dependencies

## Development

### Coding Standards

This plugin follows WordPress Coding Standards and best practices:

- PSR-4-style autoloading architecture
- Separation of concerns (Database, Core, Admin)
- Proper sanitization and validation
- Comprehensive error handling
- No wp_options table bloat

### Hooks and Filters

The plugin provides several internal hooks for extensibility:

**Actions:**
- Plugin initialization happens on `plugins_loaded`
- Admin menu added on `admin_menu`
- Settings processed on `admin_init`

**Filters:**
- Various WordPress core filters modified based on settings
- Standard WordPress hook system for all functionality

## Changelog

### 1.0.0
- Initial release
- Security hardening features
- Comment management features
- Custom database table implementation
- Admin settings interface

## License

This plugin is licensed under the GPL v2 or later.
