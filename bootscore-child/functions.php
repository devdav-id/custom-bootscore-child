<?php

/**
 * @package Bootscore Child
 *
 * @version 1.0.0
 */


// Exit if accessed directly
defined('ABSPATH') || exit;


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
      $this->github_repo = $this->theme_data->get('GitHub Theme URI');
      $this->github_folder = $this->theme_data->get('GitHub Theme Folder');
      $this->version = $this->theme_data->get('Version');
      
      if ($this->github_repo) {
        add_filter('pre_set_site_transient_update_themes', array($this, 'check_for_update'));
        add_filter('upgrader_source_selection', array($this, 'fix_source_folder'), 10, 4);
      }
    }
    
    /**
     * Check for theme updates
     */
    public function check_for_update($transient) {
      if (empty($transient->checked)) {
        return $transient;
      }
      
      $remote_version = $this->get_remote_version();
      
      if (version_compare($this->version, $remote_version, '<')) {
        $transient->response[$this->theme_slug] = array(
          'theme' => $this->theme_slug,
          'new_version' => $remote_version,
          'url' => 'https://github.com/' . $this->github_repo,
          'package' => $this->get_download_url()
        );
      }
      
      return $transient;
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
      if (isset($hook_extra['theme']) && $hook_extra['theme'] === $this->theme_slug) {
        $corrected_source = $remote_source . '/' . $this->github_folder . '/';
        
        if (is_dir($corrected_source)) {
          return $corrected_source;
        }
      }
      
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
    delete_site_transient('update_themes');
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
      echo 'Theme Slug: ' . get_option('stylesheet') . '</p>';
      echo '</div>';
    }
  }
});
