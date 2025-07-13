<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cronicle Enqueue Class
 * 
 * Handles enqueueing of admin scripts and styles
 */
class Cronicle_Enqueue {
    
    /**
     * Main page slug
     */
    const PAGE_SLUG = 'cronicle';
    
    /**
     * Register hooks
     */
    public function register_hooks() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our main page
        if ('toplevel_page_' . self::PAGE_SLUG !== $hook) {
            return;
        }
        
        // Enqueue CSS file
        wp_enqueue_style(
            'cronicle-admin',
            CRONICLE_PLUGIN_URL . 'assets/css/cronicle-admin.css',
            array(),
            CRONICLE_VERSION
        );
        
        // Enqueue WordPress React dependencies
        wp_enqueue_script('wp-element');
        wp_enqueue_script('wp-i18n');
        wp_enqueue_script('wp-api-fetch');
        wp_enqueue_script('wp-components');
        
        // Enqueue React app bundle
        wp_enqueue_script(
            'cronicle-admin',
            CRONICLE_PLUGIN_URL . 'assets/js/cronicle-admin.js',
            array('wp-element', 'wp-i18n', 'wp-api-fetch', 'wp-components'),
            CRONICLE_VERSION,
            true
        );
        
        // Check if API is configured
        $is_api_configured = false;
        if (function_exists('cronicle_api_client')) {
            $api_client = cronicle_api_client();
            $is_api_configured = $api_client && $api_client->is_api_ready();
        }
        
        // Localize script for AJAX and React
        wp_localize_script('cronicle-admin', 'cronicle_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cronicle_chat_nonce'),
            'api_configured' => $is_api_configured,
            'strings' => array(
                'sending' => __('Sending...', 'cronicle'),
                'error' => __('Error sending message. Please try again.', 'cronicle'),
                'api_not_configured' => __('API not configured. Please add your Anthropic API key in Settings.', 'cronicle'),
                'creating_post' => __('Creating Draft...', 'cronicle'),
                'post_created' => __('✓ Draft Created', 'cronicle'),
                'create_post' => __('Create Draft Post', 'cronicle'),
                'creating_outline' => __('Creating Outline...', 'cronicle'),
                'outline_created' => __('✓ Outline Created', 'cronicle'),
                'create_outline' => __('Create Outline Draft', 'cronicle'),
                'publish_post' => __('Publish Now', 'cronicle'),
                'publishing_post' => __('Publishing...', 'cronicle'),
                'post_published' => __('✓ Post Published', 'cronicle'),
                'schedule_post' => __('Schedule', 'cronicle'),
                'scheduling_post' => __('Scheduling...', 'cronicle'),
                'post_scheduled' => __('✓ Post Scheduled', 'cronicle'),
                'enter_datetime' => __('Select publish date/time', 'cronicle'),
            )
        ));
    }
    


} 