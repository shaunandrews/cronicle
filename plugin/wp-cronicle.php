<?php
/*
Plugin Name: Cronicle
Plugin URI: https://example.com/wp-cronicle
Description: AI-powered WordPress content assistant with natural language drafting and scheduling capabilities.
Version: 0.0.1
Author: Shaun Andrews
Author URI: https://shaunandrews.com
License: GPL2
Text Domain: cronicle
Domain Path: /languages
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CRONICLE_VERSION', '0.0.1');
define('CRONICLE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CRONICLE_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Cronicle Plugin Class
 */
class Cronicle_Plugin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('cronicle', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize admin functionality
        if (is_admin()) {
            $this->init_admin();
        }
    }
    
    /**
     * Initialize admin functionality
     */
    private function init_admin() {
        // Include admin settings class
        require_once CRONICLE_PLUGIN_DIR . 'includes/class-cronicle-admin-settings.php';
        
        // Include API client class
        require_once CRONICLE_PLUGIN_DIR . 'includes/class-cronicle-api-client.php';
        
        // Include refactored admin classes
        require_once CRONICLE_PLUGIN_DIR . 'includes/admin/class-cronicle-router.php';
        require_once CRONICLE_PLUGIN_DIR . 'includes/admin/class-cronicle-ui.php';
        require_once CRONICLE_PLUGIN_DIR . 'includes/admin/class-cronicle-enqueue.php';
        require_once CRONICLE_PLUGIN_DIR . 'includes/admin/class-cronicle-chat-handler.php';
        require_once CRONICLE_PLUGIN_DIR . 'includes/admin/class-cronicle-admin-main-refactored.php';
        
        // Initialize admin settings
        new Cronicle_Admin_Settings();
        
        // Initialize refactored main admin page
        new Cronicle_Admin_Main_Refactored();
    }
}

// Initialize the plugin
new Cronicle_Plugin();
