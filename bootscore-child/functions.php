<?php
/**
 * @package Bootscore Child
 * @version 6.0.0
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Include AW-Theme components
 */
require_once get_stylesheet_directory() . '/aw-theme/wordpress-update-notice.php';

/**
 * Enqueue scripts and styles
 */
add_action('wp_enqueue_scripts', 'bootscore_child_enqueue_styles');
function bootscore_child_enqueue_styles() {

  // Parent theme style
  wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
  
  // Check if compiled main.css exists
  $main_css_path = get_stylesheet_directory() . '/assets/css/main.css';
  if (file_exists($main_css_path)) {
    // Compiled main.css (includes AW SCSS)
    $modified_bootscoreChildCss = date('YmdHi', filemtime($main_css_path));
    wp_enqueue_style('main', get_stylesheet_directory_uri() . '/assets/css/main.css', array('parent-style'), $modified_bootscoreChildCss);
    $main_handle = 'main';
  } else {
    // Fallback to child theme style.css
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style'), wp_get_theme()->get('Version'));
    $main_handle = 'child-style';
  }

  // AW Custom CSS
  if (file_exists(get_stylesheet_directory() . '/assets/aw-css/aw-custom.css')) {
    $modified_awCustomCss = date('YmdHi', filemtime(get_stylesheet_directory() . '/assets/aw-css/aw-custom.css'));
    wp_enqueue_style('aw-custom', get_stylesheet_directory_uri() . '/assets/aw-css/aw-custom.css', array($main_handle), $modified_awCustomCss);
  }
  
  // Original custom.js
  if (file_exists(get_stylesheet_directory() . '/assets/js/custom.js')) {
    $modificated_CustomJS = date('YmdHi', filemtime(get_stylesheet_directory() . '/assets/js/custom.js'));
    wp_enqueue_script('custom-js', get_stylesheet_directory_uri() . '/assets/js/custom.js', array('jquery'), $modificated_CustomJS, true);
  }
  
  // AW Custom JavaScript
  if (file_exists(get_stylesheet_directory() . '/assets/aw-js/aw-custom.js')) {
    $modified_awCustomJs = date('YmdHi', filemtime(get_stylesheet_directory() . '/assets/aw-js/aw-custom.js'));
    wp_enqueue_script('aw-custom-js', get_stylesheet_directory_uri() . '/assets/aw-js/aw-custom.js', array('jquery'), $modified_awCustomJs, true);
  }
}
