<?php

/**
 * @package Bootscore Child
 *
 * @version 6.0.0
 */


// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Register custom theme headers for GitHub updater
 */
add_filter('extra_theme_headers', function($headers) {
  $headers['GitHub Theme URI'] = 'GitHub Theme URI';
  $headers['GitHub Theme Folder'] = 'GitHub Theme Folder';
  return $headers;
});


/**
 * Enqueue scripts and styles
 */
add_action('wp_enqueue_scripts', 'bootscore_child_enqueue_styles');
function bootscore_child_enqueue_styles() {

  // Compiled main.css
  $modified_bootscoreChildCss = date('YmdHi', filemtime(get_stylesheet_directory() . '/assets/css/main.css'));
  wp_enqueue_style('main', get_stylesheet_directory_uri() . '/assets/css/main.css', array('parent-style'), $modified_bootscoreChildCss);

  // style.css
  wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
  
  // custom.js
  // Get modification time. Enqueue file with modification date to prevent browser from loading cached scripts when file content changes. 
  $modificated_CustomJS = date('YmdHi', filemtime(get_stylesheet_directory() . '/assets/js/custom.js'));
  wp_enqueue_script('custom-js', get_stylesheet_directory_uri() . '/assets/js/custom.js', array('jquery'), $modificated_CustomJS, false, true);
}


/**
 * GitHub Theme Updater
 * Lightweight updater for themes hosted on GitHub with subfolder structure
 */
if (!class_exists('GitHub_Theme_Updater')) {
  
  class GitHub_Theme_Updater {
    
    private $theme_slug;
    private $theme_data;
    private $github_repo;
    private $github_folder;
    private $version;
    
    public function __construct() {
      $this->theme_slug = get_option('stylesheet');
      $this->theme_data = wp_get_theme();
      
      // Get GitHub info manually from style.css since wp_get_theme() isn't reading custom headers
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
      
      if ($this->github_repo) {
        add_filter('pre_set_site_transient_update_themes', array($this, 'check_for_update'));
        add_filter('upgrader_source_selection', array($this, 'fix_source_folder'), 10, 4);
        
        // Additional hooks to ensure theme updates are displayed
        add_action('admin_init', array($this, 'show_update_notification'));
      }
    }
    
    /**
     * Check for theme updates
     */
    public function check_for_update($transient) {
      // Debug logging
      error_log("GitHub Updater: check_for_update called");
      error_log("GitHub Updater: Repo = " . $this->github_repo);
      error_log("GitHub Updater: Folder = " . $this->github_folder);
      error_log("GitHub Updater: Version = " . $this->version);
      
      if (empty($transient->checked)) {
        error_log("GitHub Updater: No checked themes, returning");
        return $transient;
      }
      
      $remote_version = $this->get_remote_version();
      error_log("GitHub Updater: Remote version = " . $remote_version);
      
      if (version_compare($this->version, $remote_version, '<')) {
        error_log("GitHub Updater: Adding update to transient");
        
        // For themes, WordPress expects a different format
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
        
        error_log("GitHub Updater: Update data = " . print_r($update_data, true));
      } else {
        error_log("GitHub Updater: No update needed");
      }
      
      return $transient;
    }
    
    /**
     * Show update notification
     */
    public function show_update_notification() {
      $update_themes = get_site_transient('update_themes');
      
      if (!empty($update_themes) && isset($update_themes->response[$this->theme_slug])) {
        error_log("GitHub Updater: Update available in transient for theme display");
        
        // Force WordPress to recognize the theme update
        add_action('admin_notices', array($this, 'admin_notice_update'));
      }
    }
    
    /**
     * Display admin notice for available update
     */
    public function admin_notice_update() {
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
    
    /**
     * Get the latest version from GitHub
     */
    private function get_remote_version() {
      $request = wp_remote_get('https://api.github.com/repos/' . $this->github_repo . '/contents/' . $this->github_folder . '/style.css');
      
      if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body, true);
        
        if (isset($data['content'])) {
          $content = base64_decode($data['content']);
          
          // Extract version from style.css content
          if (preg_match('/Version:\s*(.+)/i', $content, $matches)) {
            return trim($matches[1]);
          }
        }
      }
      
      return $this->version;
    }
    
    /**
     * Get the download URL for the latest version
     */
    private function get_download_url() {
      return 'https://github.com/' . $this->github_repo . '/archive/refs/heads/main.zip';
    }
    
    /**
     * Fix the source folder path during installation
     */
    public function fix_source_folder($source, $remote_source, $upgrader, $hook_extra) {
      error_log("GitHub Updater: fix_source_folder called");
      error_log("GitHub Updater: source = " . $source);
      error_log("GitHub Updater: remote_source = " . $remote_source);
      
      if (isset($hook_extra['theme']) && $hook_extra['theme'] === $this->theme_slug) {
        error_log("GitHub Updater: This is our theme update");
        
        // GitHub creates folder with repo-name-branch format
        $expected_folder = 'custom-bootscore-child-main';
        $corrected_source = $remote_source . '/' . $expected_folder . '/' . $this->github_folder . '/';
        error_log("GitHub Updater: corrected_source = " . $corrected_source);
        
        if (is_dir($corrected_source)) {
          error_log("GitHub Updater: Corrected source directory exists, using it");
          return $corrected_source;
        } else {
          error_log("GitHub Updater: Corrected source directory does not exist");
          
          // Try to find the actual folder structure
          if (is_dir($remote_source)) {
            $dirs = scandir($remote_source);
            foreach ($dirs as $dir) {
              if ($dir !== '.' && $dir !== '..' && is_dir($remote_source . '/' . $dir)) {
                error_log("GitHub Updater: Found directory: " . $dir);
                
                // Check if this directory contains our theme folder
                $potential_theme_path = $remote_source . '/' . $dir . '/' . $this->github_folder . '/';
                if (is_dir($potential_theme_path)) {
                  error_log("GitHub Updater: Found theme in: " . $potential_theme_path);
                  return $potential_theme_path;
                }
              }
            }
          }
        }
      }
      
      error_log("GitHub Updater: Returning original source");
      return $source;
    }
  }
  
  // Initialize the updater
  new GitHub_Theme_Updater();
}

/**
 * Force theme update check when visiting themes page
 * This clears WordPress update caches to force fresh checks
 */
add_action('load-themes.php', function() {
  if (current_user_can('update_themes')) {
    // Only clear transient if specifically requested
    if (isset($_GET['force_github_check'])) {
      delete_site_transient('update_themes');
    }
  }
});

/**
 * Debug: Add admin notice to show updater status
 */
add_action('admin_notices', function() {
  if (current_user_can('manage_options')) {
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // Only show on themes page
    if ($current_page == 'themes.php') {
      $theme = wp_get_theme();
      $current_version = $theme->get('Version');
      $github_repo = $theme->get('GitHub Theme URI');
      
      echo '<div class="notice notice-info is-dismissible">';
      echo '<p><strong>GitHub Updater Debug:</strong><br>';
      echo 'Current Version: ' . $current_version . '<br>';
      echo 'GitHub Repo: ' . $github_repo . '<br>';
      echo 'Theme Slug: ' . get_option('stylesheet') . '<br>';
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
