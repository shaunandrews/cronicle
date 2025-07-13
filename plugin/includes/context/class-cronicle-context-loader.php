<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cronicle Context System Loader
 * 
 * Loads and initializes the entire context management system
 */
class Cronicle_Context_Loader {
    
    /**
     * Whether the context system has been loaded
     * 
     * @var bool
     */
    private static $loaded = false;
    
    /**
     * Load the context system
     */
    public static function load() {
        if (self::$loaded) {
            return;
        }
        
        // Load core interfaces and base classes
        require_once CRONICLE_PLUGIN_DIR . 'includes/context/interface-cronicle-context-provider.php';
        require_once CRONICLE_PLUGIN_DIR . 'includes/context/abstract-cronicle-context-provider.php';
        
        // Load context providers
        require_once CRONICLE_PLUGIN_DIR . 'includes/context/providers/class-site-context-provider.php';
        require_once CRONICLE_PLUGIN_DIR . 'includes/context/providers/class-user-context-provider.php';
        require_once CRONICLE_PLUGIN_DIR . 'includes/context/providers/class-content-context-provider.php';
        require_once CRONICLE_PLUGIN_DIR . 'includes/context/providers/class-conversation-context-provider.php';
        require_once CRONICLE_PLUGIN_DIR . 'includes/context/providers/class-writing-style-provider.php';
        
        // Load template system
        require_once CRONICLE_PLUGIN_DIR . 'includes/context/templates/class-cronicle-prompt-template-library.php';
        
        // Load preferences engine
        require_once CRONICLE_PLUGIN_DIR . 'includes/context/class-cronicle-preferences-engine.php';
        
        // Load context manager (this will initialize the default providers)
        require_once CRONICLE_PLUGIN_DIR . 'includes/context/class-cronicle-context-manager.php';
        
        // Initialize the system
        self::initialize();
        
        self::$loaded = true;
    }
    
    /**
     * Initialize the context system
     */
    private static function initialize() {
        // Initialize the preferences engine
        cronicle_preferences_engine();
        
        // Initialize the template library
        cronicle_prompt_template_library();
        
        // Initialize the context manager (which registers default providers)
        cronicle_context_manager();
        
        // Allow plugins to extend the context system
        do_action('cronicle_context_system_loaded');
    }
    
    /**
     * Check if context system is loaded
     * 
     * @return bool Whether the system is loaded
     */
    public static function is_loaded() {
        return self::$loaded;
    }
}