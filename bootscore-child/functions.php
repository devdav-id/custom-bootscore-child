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
 * Generate hooks for all Bootscore actions
 */
$bootscore_hooks = [
  'bootscore_before_masthead',
  'bootscore_after_masthead_open',
  'bootscore_before_navbar_brand',
  'bootscore_after_navbar_brand',
  'bootscore_after_nav_toggler',
  'bootscore_before_masthead_close',
  'bootscore_after_masthead',
  'bootscore_after_primary_open',
  'bootscore_before_title',
  'bootscore_after_title',
  'bootscore_before_loop',
  'bootscore_before_loop_item',
  'bootscore_before_loop_title',
  'bootscore_after_loop_title',
  'bootscore_loop_item_after_card_body',
  'bootscore_after_loop_item',
  'bootscore_after_loop',
  'bootscore_after_featured_image',
  'bootscore_before_entry_footer',
  'bootscore_before_sidebar_widgets',
  'bootscore_after_sidebar_widgets',
  'bootscore_before_my_offcanvas_account',
  'bootscore_before_footer',
  'bootscore_footer_columns_before_container',
  'bootscore_footer_columns_after_container_open',
  'bootscore_footer_columns_before_container_close',
  'bootscore_footer_columns_after_container',
  'bootscore_footer_info_after_container_open',
  'bootscore_before_mini_cart_footer',
];

// Add hooks dynamically
foreach ($bootscore_hooks as $hook) {
  add_action($hook, function ($location = null) use ($hook) {
    $output = '<div class="alert alert-warning"><code>' . esc_html($hook) . '</code>';
    if ($location) {
      $output .= ' (Location: <strong>' . esc_html($location) . '</strong>)';
    }
    $output .= '</div>';
    echo $output;
  });
}