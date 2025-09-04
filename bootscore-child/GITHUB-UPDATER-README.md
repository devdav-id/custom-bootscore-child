# GitHub Auto-Updater Setup

This theme now includes a lightweight GitHub auto-updater that will check for updates from your repository and allow one-click updates from the WordPress admin.

## How It Works

1. **Version Detection**: The updater checks the `Version` field in your `style.css` against the version in your GitHub repository
2. **Update Notifications**: When a new version is available, you'll see an update notification in WordPress admin under Appearance → Themes
3. **One-Click Updates**: Click the "Update" button to automatically download and install the latest version from GitHub

## Setup Instructions

### 1. Repository Structure
Your repository structure is already configured correctly:
- Theme files are in the `bootscore-child/` folder
- The updater knows to extract files from this subfolder during updates

### 2. Version Management
To release a new version:

1. Update the version number in `bootscore-child/style.css`:
   ```css
   Version: 6.0.1
   ```

2. Commit and push your changes to the `main` branch

3. WordPress will detect the new version within 12 hours (WordPress checks for updates twice daily)

### 3. Force Update Check (Optional)
To check for updates immediately:
1. Go to WordPress Admin → Appearance → Themes
2. The page load will trigger an update check

## Configuration

The updater is configured in `functions.php` with these settings:
- **GitHub Repository**: `devdav-id/custom-bootscore-child`
- **Theme Folder**: `bootscore-child`
- **Branch**: `main`

## Troubleshooting

### Updates Not Showing
1. Check that the version number in GitHub is higher than the installed version
2. Ensure your repository is public (or configure GitHub API access for private repos)
3. Check WordPress error logs for any API issues

### Update Fails
1. Ensure WordPress has write permissions to the themes directory
2. Check that the `bootscore-child` folder exists in your repository
3. Verify the repository URL is correct in `style.css`

## Security Notes

- This updater only works with public repositories by default
- Updates are downloaded directly from GitHub's main branch
- Always test updates on a staging site first
- Consider backing up your site before applying updates

## Customization

To modify the updater behavior:
1. Edit the `GitHub_Theme_Updater` class in `functions.php`
2. Change the branch by modifying the `get_download_url()` method
3. Add authentication for private repositories by modifying the API calls
