<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cronicle Admin Settings Class
 * 
 * Handles the administration settings page for the Cronicle plugin
 */
class Cronicle_Admin_Settings {
    
    /**
     * Settings page slug
     */
    const PAGE_SLUG = 'cronicle-settings';
    
    /**
     * Option group name
     */
    const OPTION_GROUP = 'cronicle_settings';
    
    /**
     * Settings section name
     */
    const SETTINGS_SECTION = 'cronicle_api_settings';
    
    /**
     * API key option name
     */
    const API_KEY_OPTION = 'cronicle_anthropic_api_key';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_options_page(
            __('Cronicle Settings', 'cronicle'),           // Page title
            __('Cronicle', 'cronicle'),                    // Menu title
            'manage_options',                               // Capability required
            self::PAGE_SLUG,                               // Menu slug
            array($this, 'render_settings_page')          // Callback function
        );
    }
    
    /**
     * Initialize settings using WordPress Settings API
     */
    public function init_settings() {
        // Register setting
        register_setting(
            self::OPTION_GROUP,     // Option group
            self::API_KEY_OPTION,   // Option name
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_api_key'),
                'default' => '',
            )
        );
        
        // Add settings section
        add_settings_section(
            self::SETTINGS_SECTION,                        // Section ID
            __('API Configuration', 'cronicle'),           // Section title
            array($this, 'render_section_description'),    // Callback
            self::PAGE_SLUG                                // Page slug
        );
        
        // Add API key field
        add_settings_field(
            'anthropic_api_key',                           // Field ID
            __('Anthropic API Key', 'cronicle'),           // Field title
            array($this, 'render_api_key_field'),         // Callback
            self::PAGE_SLUG,                               // Page slug
            self::SETTINGS_SECTION,                        // Section ID
            array(
                'label_for' => 'anthropic_api_key',
                'class' => 'cronicle-api-key-row',
            )
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our settings page
        if ('settings_page_' . self::PAGE_SLUG !== $hook) {
            return;
        }
        
        // Add inline styles for better UI
        wp_add_inline_style('wp-admin', '
            .cronicle-api-key-input {
                width: 400px;
                font-family: monospace;
            }
            .cronicle-api-key-description {
                color: #666;
                font-style: italic;
                margin-top: 5px;
            }
            .cronicle-settings-header {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
            }
            .cronicle-settings-header h1 {
                margin: 0;
            }
            .cronicle-status-indicator {
                margin-left: 15px;
                padding: 5px 10px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: bold;
            }
            .cronicle-status-connected {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .cronicle-status-disconnected {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
        ');
    }
    
    /**
     * Render the settings page
     */
    public function render_settings_page() {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'cronicle'));
        }
        
        $api_key = get_option(self::API_KEY_OPTION, '');
        $is_connected = !empty($api_key);
        ?>
        <div class="wrap">
            <div class="cronicle-settings-header">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <span class="cronicle-status-indicator <?php echo $is_connected ? 'cronicle-status-connected' : 'cronicle-status-disconnected'; ?>">
                    <?php echo $is_connected ? __('API Connected', 'cronicle') : __('API Not Connected', 'cronicle'); ?>
                </span>
            </div>
            
            <?php
            // Show custom messages from transients
            $error_message = get_transient('cronicle_settings_error');
            if ($error_message) {
                delete_transient('cronicle_settings_error');
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
            }
            
            $success_message = get_transient('cronicle_settings_success');
            if ($success_message) {
                delete_transient('cronicle_settings_success');
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($success_message) . '</p></div>';
            }
            ?>
            
            <form method="post" action="options.php">
                <?php
                // Output security fields for the registered setting
                settings_fields(self::OPTION_GROUP);
                
                // Output setting sections and their fields
                do_settings_sections(self::PAGE_SLUG);
                
                // Output save settings button
                submit_button(__('Save Settings', 'cronicle'));
                ?>
            </form>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ccd0d4;">
                <h3><?php _e('About Cronicle', 'cronicle'); ?></h3>
                <p><?php _e('Cronicle is an AI-powered WordPress content assistant that helps you draft, refine, schedule, and publish WordPress posts through natural-language conversations.', 'cronicle'); ?></p>
                <p>
                    <strong><?php _e('Need an API Key?', 'cronicle'); ?></strong> 
                    <a href="https://console.anthropic.com/" target="_blank" rel="noopener noreferrer">
                        <?php _e('Get your Anthropic API key here', 'cronicle'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render section description
     */
    public function render_section_description() {
        echo '<p>' . __('Configure your Anthropic API key to enable AI-powered content assistance.', 'cronicle') . '</p>';
    }
    
    /**
     * Render API key field
     */
    public function render_api_key_field($args) {
        $option_value = get_option(self::API_KEY_OPTION, '');
        $masked_value = !empty($option_value) ? 'sk-ant-' . str_repeat('•', 32) . substr($option_value, -4) : '';
        ?>
        <input 
            type="password" 
            id="<?php echo esc_attr($args['label_for']); ?>"
            name="<?php echo esc_attr(self::API_KEY_OPTION); ?>"
            value="<?php echo esc_attr($option_value); ?>"
            class="cronicle-api-key-input"
            placeholder="<?php esc_attr_e('sk-ant-api03-...', 'cronicle'); ?>"
            autocomplete="off"
        />
        <?php if (!empty($option_value)): ?>
            <div class="cronicle-api-key-description">
                <?php 
                echo sprintf(
                    __('Current key: %s (Leave empty to use existing key)', 'cronicle'),
                    esc_html($masked_value)
                ); 
                ?>
            </div>
        <?php else: ?>
            <div class="cronicle-api-key-description">
                <?php _e('Enter your Anthropic API key to enable AI features.', 'cronicle'); ?>
            </div>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Sanitize API key input
     */
    public function sanitize_api_key($input) {
        // If input is empty and we already have a key, keep the existing key
        if (empty($input)) {
            $existing_key = get_option(self::API_KEY_OPTION, '');
            if (!empty($existing_key)) {
                return $existing_key;
            }
        }
        
        // Sanitize the input
        $sanitized = sanitize_text_field($input);
        
        // Basic validation for Anthropic API key format
        if (!empty($sanitized) && !preg_match('/^sk-ant-api03-[a-zA-Z0-9_-]+$/', $sanitized)) {
            // Store the error in a transient to display after redirect
            set_transient('cronicle_settings_error', __('Invalid API key format. Anthropic API keys should start with "sk-ant-api03-".', 'cronicle'), 30);
            
            // Return existing key if validation fails
            return get_option(self::API_KEY_OPTION, '');
        }
        
        // If we have a valid new key, show success message
        if (!empty($sanitized) && $sanitized !== get_option(self::API_KEY_OPTION, '')) {
            // Store success message in transient
            set_transient('cronicle_settings_success', __('API key updated successfully.', 'cronicle'), 30);
        }
        
        return $sanitized;
    }
    
    /**
     * Get the stored API key
     * 
     * @return string The API key or empty string if not set
     */
    public static function get_api_key() {
        return get_option(self::API_KEY_OPTION, '');
    }
    
    /**
     * Check if API key is configured
     * 
     * @return bool True if API key is set, false otherwise
     */
    public static function is_api_key_configured() {
        $api_key = self::get_api_key();
        return !empty($api_key);
    }
} 