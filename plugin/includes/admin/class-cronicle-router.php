<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cronicle Router Class
 * 
 * Handles admin menu registration and routing
 */
class Cronicle_Router {
    
    /**
     * Main page slug
     */
    const PAGE_SLUG = 'cronicle';
    
    /**
     * UI handler reference
     */
    private $ui_handler;
    
    /**
     * Constructor
     */
    public function __construct($ui_handler = null) {
        $this->ui_handler = $ui_handler;
    }
    
    /**
     * Register hooks
     */
    public function register_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Add main admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Cronicle', 'cronicle'),                    // Page title
            __('Cronicle', 'cronicle'),                    // Menu title
            'edit_posts',                                   // Capability required
            self::PAGE_SLUG,                               // Menu slug
            array($this, 'render_main_page'),              // Callback function
            'dashicons-format-chat',                       // Icon
            30                                             // Position
        );
        
        // Add submenu for settings
        add_submenu_page(
            self::PAGE_SLUG,                               // Parent slug
            __('Cronicle Settings', 'cronicle'),           // Page title
            __('Settings', 'cronicle'),                    // Menu title
            'manage_options',                              // Capability required
            'cronicle-settings',                           // Menu slug (matches existing settings page)
            array($this, 'redirect_to_settings')          // Callback function
        );
    }
    
    /**
     * Redirect to existing settings page
     */
    public function redirect_to_settings() {
        wp_redirect(admin_url('options-general.php?page=cronicle-settings'));
        exit;
    }
    
    /**
     * Render the main page
     */
    public function render_main_page() {
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'cronicle'));
        }
        
        if ($this->ui_handler && method_exists($this->ui_handler, 'render_main_page')) {
            $this->ui_handler->render_main_page();
        } else {
            // Fallback if UI handler is not available
            echo '<div class="wrap"><h1>' . __('Cronicle', 'cronicle') . '</h1><p>' . __('UI handler not available.', 'cronicle') . '</p></div>';
        }
    }
} 