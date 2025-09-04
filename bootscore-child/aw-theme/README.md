# AW-Theme Components

This folder contains organized components for the AW-Theme custom functionality.

## Files

### `wordpress-update-notice.php`
- **Purpose**: Complete GitHub-to-WordPress theme updater system
- **Features**: 
  - Automatic update detection from GitHub repository
  - WordPress admin update notifications
  - One-click theme updates
  - Subfolder repository support
  - Comprehensive debug logging
- **Class**: `AW_GitHub_Theme_Updater`

### `test-updater.php`
- **Purpose**: Testing and debugging script for the updater system
- **Usage**: Access via `yoursite.com/wp-content/themes/bootscore-child/aw-theme/test-updater.php?test=1`
- **Important**: Delete this file after testing in production

## Integration

The main theme's `functions.php` includes these components via:
```php
require_once get_stylesheet_directory() . '/aw-theme/wordpress-update-notice.php';
```

## GitHub Configuration

The updater system reads configuration from `style.css` headers:
- `GitHub Theme URI`: Repository path (e.g., devdav-id/custom-bootscore-child)  
- `GitHub Theme Folder`: Subfolder containing theme files (e.g., bootscore-child)

## Version History

- **v1.0.1**: Organized code structure, moved from functions.php to dedicated files
- **v1.0.0**: Initial working GitHub updater implementation

## Dependencies

- WordPress 5.0+
- PHP 7.4+
- GitHub public repository
- WordPress `wp_remote_get` for API calls
