<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cronicle UI Class
 * 
 * Handles rendering of admin UI elements
 */
class Cronicle_UI {
    
    /**
     * Render the main page
     */
    public function render_main_page() {
        $api_client = cronicle_api_client();
        $is_api_configured = $api_client->is_api_ready();
        
        // Include the main page template
        include CRONICLE_PLUGIN_DIR . 'includes/admin/views/main-page.php';
    }
    
    /**
     * Render the context page
     */
    public function render_context_page() {
        // Include the context page template
        include CRONICLE_PLUGIN_DIR . 'includes/admin/views/context-page.php';
    }
} 