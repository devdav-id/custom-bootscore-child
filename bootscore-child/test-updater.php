<?php
/**
 * Updater Test Script
 * Upload this to your WordPress theme folder and access: yoursite.com/wp-content/themes/bootscore-child/test-updater.php?test=1
 * 
 * IMPORTANT: Delete this file after testing!
 */

if (!isset($_GET['test']) || $_GET['test'] !== '1') {
    die('Access denied. Add ?test=1 to the URL.');
}

// Include WordPress
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');

// Manually register custom headers for testing
add_filter('extra_theme_headers', function($headers) {
  $headers['GitHub Theme URI'] = 'GitHub Theme URI';
  $headers['GitHub Theme Folder'] = 'GitHub Theme Folder';
  return $headers;
});

echo "<h2>WordPress GitHub Updater Test</h2>";

// Test 1: WordPress functions
echo "<h3>1. WordPress Environment Test</h3>";
if (function_exists('wp_get_theme')) {
    // Clear theme cache to force fresh read
    wp_cache_delete('themes', 'themes');
    
    $theme = wp_get_theme();
    echo "✅ WordPress loaded successfully<br>";
    echo "Current theme: " . $theme->get('Name') . "<br>";
    echo "Current version: " . $theme->get('Version') . "<br>";
    echo "GitHub URI: " . $theme->get('GitHub Theme URI') . "<br>";
    echo "GitHub Folder: " . $theme->get('GitHub Theme Folder') . "<br>";
    
    // Debug: Check if custom headers are registered
    echo "<h4>Custom Headers Debug:</h4>";
    $extra_headers = apply_filters('extra_theme_headers', array());
    echo "Extra headers registered: " . (empty($extra_headers) ? "None" : implode(', ', array_keys($extra_headers))) . "<br>";
    
    // Debug: Manual header check
    $style_css_path = get_stylesheet_directory() . '/style.css';
    if (file_exists($style_css_path)) {
        $style_content = file_get_contents($style_css_path);
        if (preg_match('/GitHub Theme URI:\s*(.+)/i', $style_content, $matches)) {
            echo "GitHub URI found in file: " . trim($matches[1]) . "<br>";
        } else {
            echo "❌ GitHub URI not found in style.css<br>";
        }
        if (preg_match('/GitHub Theme Folder:\s*(.+)/i', $style_content, $matches)) {
            echo "GitHub Folder found in file: " . trim($matches[1]) . "<br>";
        } else {
            echo "❌ GitHub Folder not found in style.css<br>";
        }
    }
    
    // Force WordPress to re-read theme headers
    echo "<h4>Forcing Theme Refresh:</h4>";
    delete_site_transient('theme_roots');
    wp_cache_flush();
    
    // Get theme again after cache clear
    $theme_fresh = wp_get_theme();
    echo "After cache clear - GitHub URI: " . $theme_fresh->get('GitHub Theme URI') . "<br>";
    echo "After cache clear - GitHub Folder: " . $theme_fresh->get('GitHub Theme Folder') . "<br>";
    
} else {
    echo "❌ WordPress not loaded properly<br>";
}

// Test 2: GitHub API test
echo "<h3>2. GitHub API Test</h3>";
$github_repo = 'devdav-id/custom-bootscore-child';
$github_folder = 'bootscore-child';

if (function_exists('wp_remote_get')) {
    $request = wp_remote_get("https://api.github.com/repos/{$github_repo}/contents/{$github_folder}/style.css");
    
    if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body, true);
        
        if (isset($data['content'])) {
            $content = base64_decode($data['content']);
            
            if (preg_match('/Version:\s*(.+)/i', $content, $matches)) {
                $remote_version = trim($matches[1]);
                echo "✅ GitHub API working<br>";
                echo "Remote version: <strong>{$remote_version}</strong><br>";
                
                // Version comparison test
                $local_version = $theme->get('Version');
                echo "<h4>Version Comparison:</h4>";
                echo "Local: {$local_version}<br>";
                echo "Remote: {$remote_version}<br>";
                
                if (version_compare($local_version, $remote_version, '<')) {
                    echo "🟢 <strong>UPDATE AVAILABLE!</strong> (Local < Remote)<br>";
                } else {
                    echo "🔴 No update needed (Local >= Remote)<br>";
                }
            } else {
                echo "❌ Could not extract version from GitHub<br>";
            }
        } else {
            echo "❌ No content in GitHub response<br>";
        }
    } else {
        echo "❌ GitHub API request failed<br>";
        if (is_wp_error($request)) {
            echo "Error: " . $request->get_error_message() . "<br>";
        } else {
            echo "HTTP Code: " . wp_remote_retrieve_response_code($request) . "<br>";
        }
    }
} else {
    echo "❌ wp_remote_get function not available<br>";
}

// Test 3: Check update transients
echo "<h3>3. WordPress Update System Test</h3>";
if (function_exists('get_site_transient')) {
    $update_themes = get_site_transient('update_themes');
    echo "Update themes transient exists: " . (empty($update_themes) ? "No" : "Yes") . "<br>";
    
    if (!empty($update_themes)) {
        echo "<h4>Transient Debug:</h4>";
        echo "Checked themes: " . (isset($update_themes->checked) ? count($update_themes->checked) : 0) . "<br>";
        echo "Response themes: " . (isset($update_themes->response) ? count($update_themes->response) : 0) . "<br>";
        
        if (isset($update_themes->checked)) {
            echo "Checked array: <pre>" . print_r($update_themes->checked, true) . "</pre>";
        }
        
        if (isset($update_themes->response)) {
            echo "Response array: <pre>" . print_r($update_themes->response, true) . "</pre>";
            
            $theme_slug = get_option('stylesheet');
            if (isset($update_themes->response[$theme_slug])) {
                echo "🟢 <strong>This theme has an update available!</strong><br>";
                $update_info = $update_themes->response[$theme_slug];
                echo "New version: " . $update_info['new_version'] . "<br>";
                echo "Package URL: " . $update_info['package'] . "<br>";
            } else {
                echo "🔴 No update found for this theme in transient<br>";
                echo "Theme slug looking for: " . $theme_slug . "<br>";
            }
        }
    }
} else {
    echo "❌ get_site_transient function not available<br>";
}

echo "<br><strong>⚠️ Delete this file after testing!</strong>";
?>
