<?php

/**
 * WordPress GitHub Theme Updater
 * 
 * 1. Register custom headers (GitHub Theme URI, GitHub Theme Folder)
 * 2. Initialize updater class and read GitHub config
 * 3. Check GitHub API for newer versions
 * 4. Add WordPress update notifications
 * 5. Handle subfolder extraction during updates
 * 6. Force update checks and debug info
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * STEP 1: Register Custom Headers from style.css
 * ===============================
 */
add_filter('extra_theme_headers', function ($headers) {
	$headers['GitHub Theme URI'] = 'GitHub Theme URI';
	$headers['GitHub Theme Folder'] = 'GitHub Theme Folder';
	return $headers;
});

/**
 * STEP 2: Initialize Updater Class
 * ================================
 * Main class that reads GitHub config in style.css and sets up WordPress hooks
 */
if (!class_exists('AW_GitHub_Theme_Updater')) {

	class AW_GitHub_Theme_Updater
	{

		private $theme_slug;
		private $theme_data;
		private $github_repo;
		private $github_folder;
		private $version;

		public function __construct()
		{
			// wordpress theme info
			$this->theme_slug = get_option('stylesheet');
			$this->theme_data = wp_get_theme();

			// Read GitHub config from style.css
			$style_css_path = get_stylesheet_directory() . '/style.css';
			if (file_exists($style_css_path)) {
				$style_content = file_get_contents($style_css_path);

				if (preg_match('/GitHub Theme URI:\s*(.+)/i', $style_content, $matches)) {
					$this->github_repo = trim($matches[1]);
				}

				if (preg_match('/GitHub Theme Folder:\s*(.+)/i', $style_content, $matches)) {
					$this->github_folder = trim($matches[1]);
				}
			}

			$this->version = $this->theme_data->get('Version');

			// set up wordpress hooks
			if ($this->github_repo) {
				add_filter('pre_set_site_transient_update_themes', array($this, 'check_for_update'));
				add_filter('upgrader_source_selection', array($this, 'fix_source_folder'), 10, 4);
				add_action('admin_init', array($this, 'show_update_notification'));
			}
		}

		/**
		 * STEP 3: Check GitHub for Updates
		 * ===============================
		 * Compare local vs GitHub version and add to WordPress update system
		 */
		public function check_for_update($transient)
		{
			error_log("AW GitHub Updater: check_for_update called");
			// error_log("AW GitHub Updater: Repo = " . $this->github_repo);
			// error_log("AW GitHub Updater: Folder = " . $this->github_folder);
			error_log("AW GitHub Updater: Version = " . $this->version);

			if (empty($transient->checked)) {
				return $transient;
			}

			$remote_version = $this->get_remote_version();
			error_log("AW GitHub Updater: Remote version = " . $remote_version);

			if (version_compare($this->version, $remote_version, '<')) {
				
				$update_data = array(
					'theme' => $this->theme_slug,
					'new_version' => $remote_version,
					'url' => 'https://github.com/' . $this->github_repo,
					'package' => $this->get_download_url()
				);

				$transient->response[$this->theme_slug] = $update_data;

				// Also add to checked array to ensure WordPress sees it
				if (!isset($transient->checked)) {
					$transient->checked = array();
				}
				$transient->checked[$this->theme_slug] = $this->version;

				// error_log("AW GitHub Updater: Update data = " . print_r($update_data, true));
			} else {
				// error_log("AW GitHub Updater: No update needed");
			}

			return $transient;
		}

		/**
		 * STEP 4: Show Update Notifications
		 * ================================
		 */
		public function show_update_notification()
		{
			$update_themes = get_site_transient('update_themes');

			if (!empty($update_themes) && isset($update_themes->response[$this->theme_slug])) {
				// error_log("AW GitHub Updater: Update available in transient for theme display");
				add_action('admin_notices', array($this, 'admin_notice_update'));
			}
		}

		// displays on wordpress backend
		public function admin_notice_update()
		{
			if (get_current_screen()->id === 'themes') {
				$update_themes = get_site_transient('update_themes');
				if (isset($update_themes->response[$this->theme_slug])) {
					$update_info = $update_themes->response[$this->theme_slug];
					echo '<div class="notice notice-warning">';
					echo '<p><strong>Theme Update Available:</strong> Version ' . $update_info['new_version'] . ' is available for ' . $this->theme_data->get('Name') . '</p>';
					echo '</div>';
				}
			}
		}

		private function get_remote_version()
		{
			$request = wp_remote_get('https://api.github.com/repos/' . $this->github_repo . '/contents/' . $this->github_folder . '/style.css');

			if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
				$body = wp_remote_retrieve_body($request);
				$data = json_decode($body, true);

				if (isset($data['content'])) {
					$content = base64_decode($data['content']);

					if (preg_match('/Version:\s*(.+)/i', $content, $matches)) {
						return trim($matches[1]);
					}
				}
			}

			return $this->version;
		}

		private function get_download_url()
		{
			return 'https://github.com/' . $this->github_repo . '/archive/refs/heads/main.zip';
		}

		/**
		 * STEP 5: Fix Subfolder Extraction
		 * ================================
		 */
		public function fix_source_folder($source, $remote_source, $upgrader, $hook_extra)
		{

			if (isset($hook_extra['theme']) && $hook_extra['theme'] === $this->theme_slug) {

				// GitHub creates folder with repo-name-branch format
				$expected_folder = 'custom-bootscore-child-main';
				$corrected_source = $remote_source . '/' . $expected_folder . '/' . $this->github_folder . '/';
				// error_log("AW GitHub Updater: corrected_source = " . $corrected_source);

				if (is_dir($corrected_source)) {
					return $corrected_source;
				} else {
					// error_log("AW GitHub Updater: Corrected source directory does not exist");

					// Try to find the actual folder structure
					if (is_dir($remote_source)) {
						$dirs = scandir($remote_source);
						foreach ($dirs as $dir) {
							if ($dir !== '.' && $dir !== '..' && is_dir($remote_source . '/' . $dir)) {
								// error_log("AW GitHub Updater: Found directory: " . $dir);

								// Check if this directory contains our theme folder
								$potential_theme_path = $remote_source . '/' . $dir . '/' . $this->github_folder . '/';
								if (is_dir($potential_theme_path)) {
									// error_log("AW GitHub Updater: Found theme in: " . $potential_theme_path);
									return $potential_theme_path;
								}
							}
						}
					}
				}
			}

			return $source;
		}
	}

	// Initialize the updater
	new AW_GitHub_Theme_Updater();
}
//end of class

/**
 * STEP 6: Force Update Check Handler
 * =================================
 * Manual update trigger via URL parameter: ?force_github_check=1
 */
add_action('load-themes.php', function () {
	if (current_user_can('update_themes')) {
		if (isset($_GET['force_github_check'])) {
			delete_site_transient('update_themes');
		}
	}
});

/**
 * STEP 7: Debug Admin Notice
 * =========================
 * Show updater status and GitHub version comparison
 */
add_action('admin_notices', function () {
	if (current_user_can('manage_options')) {
		$current_page = basename($_SERVER['PHP_SELF']);

		// Only show on themes page
		if ($current_page == 'themes.php') {
			$theme = wp_get_theme();
			$current_version = $theme->get('Version');

			// READ GITHUB INFO MANUALLY (same method as the updater class)
			// This ensures we show exactly what the updater class is reading
			$github_repo = '';
			$github_folder = '';
			$style_css_path = get_stylesheet_directory() . '/style.css';

			if (file_exists($style_css_path)) {
				$style_content = file_get_contents($style_css_path);

				// Extract GitHub Theme URI
				if (preg_match('/GitHub Theme URI:\s*(.+)/i', $style_content, $matches)) {
					$github_repo = trim($matches[1]);
				}

				// Extract GitHub Theme Folder
				if (preg_match('/GitHub Theme Folder:\s*(.+)/i', $style_content, $matches)) {
					$github_folder = trim($matches[1]);
				}
			}

			// Get GitHub version for comparison
			$github_version = 'Unable to fetch';
			if ($github_repo && $github_folder) {
				$request = wp_remote_get('https://api.github.com/repos/' . $github_repo . '/contents/' . $github_folder . '/style.css');
				if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
					$body = wp_remote_retrieve_body($request);
					$data = json_decode($body, true);
					if (isset($data['content'])) {
						$content = base64_decode($data['content']);
						if (preg_match('/Version:\s*(.+)/i', $content, $matches)) {
							$github_version = trim($matches[1]);
						}
					}
				}
			}

			// display on wordpress backend
			echo '<div class="notice notice-info is-dismissible">';
			echo '<p><strong>AW GitHub Theme Updater Debug:</strong><br>';
			echo 'GitHub Repo: ' . ($github_repo ? $github_repo : 'NOT FOUND') . '<br>';
			echo 'GitHub Theme Folder: ' . ($github_folder ? $github_folder : 'NOT FOUND') . '<br>';
			echo 'Theme Slug: ' . get_option('stylesheet') . '<br>';
			echo '---------------<br>';
			echo 'GitHub Theme Version: ' . $github_version . '<br>';
			echo 'WordPress Theme Version: ' . $current_version . '<br>';
			echo '<a href="themes.php?force_github_check=1" class="button">Force Update Check</a></p>';
			echo '</div>';

			// Force update check for debugging
			if (isset($_GET['force_github_check'])) {
				echo '<div class="notice notice-warning">';
				echo '<p><strong>Forcing GitHub Update Check...</strong></p>';
				echo '</div>';

				delete_site_transient('update_themes');
				wp_redirect(admin_url('themes.php'));
				exit;
			}
		}
	}
});