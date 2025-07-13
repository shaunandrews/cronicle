<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cronicle Context Manager Class
 * 
 * Central manager for all context providers and prompt generation
 */
class Cronicle_Context_Manager {
    
    /**
     * Registered context providers
     * 
     * @var array
     */
    private $providers = array();
    
    /**
     * Context cache to avoid regenerating same context
     * 
     * @var array
     */
    private $context_cache = array();
    
    /**
     * Singleton instance
     * 
     * @var Cronicle_Context_Manager
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     * 
     * @return Cronicle_Context_Manager
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Register default providers
     */
    private function __construct() {
        $this->register_default_providers();
    }
    
    /**
     * Register a context provider
     * 
     * @param string $key Provider identifier
     * @param Cronicle_Context_Provider_Interface $provider Provider instance
     * @param int $priority Priority for ordering (lower = higher priority)
     */
    public function register_provider($key, $provider, $priority = 10) {
        if (!($provider instanceof Cronicle_Context_Provider_Interface)) {
            return new WP_Error('invalid_provider', 'Provider must implement Cronicle_Context_Provider_Interface');
        }
        
        $this->providers[$key] = array(
            'provider' => $provider,
            'priority' => $priority,
            'enabled' => true
        );
        
        // Sort providers by priority
        uasort($this->providers, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        return true;
    }
    
    /**
     * Unregister a context provider
     * 
     * @param string $key Provider identifier
     */
    public function unregister_provider($key) {
        unset($this->providers[$key]);
        $this->clear_cache();
    }
    
    /**
     * Enable or disable a provider
     * 
     * @param string $key Provider identifier
     * @param bool $enabled Whether provider should be enabled
     */
    public function set_provider_enabled($key, $enabled = true) {
        if (isset($this->providers[$key])) {
            $this->providers[$key]['enabled'] = $enabled;
            $this->clear_cache();
        }
    }
    
    /**
     * Get list of registered providers
     * 
     * @param bool $enabled_only Whether to return only enabled providers
     * @return array
     */
    public function get_providers($enabled_only = true) {
        if (!$enabled_only) {
            return $this->providers;
        }
        
        return array_filter($this->providers, function($provider_data) {
            return $provider_data['enabled'];
        });
    }
    
    /**
     * Gather context from all enabled providers
     * 
     * @param array $options Context gathering options
     * @param bool $use_cache Whether to use cached context
     * @return array Compiled context data
     */
    public function gather_context($options = array(), $use_cache = true) {
        $cache_key = md5(serialize($options));
        
        if ($use_cache && isset($this->context_cache[$cache_key])) {
            return $this->context_cache[$cache_key];
        }
        
        $context = array();
        $enabled_providers = $this->get_providers(true);
        
        foreach ($enabled_providers as $key => $provider_data) {
            $provider = $provider_data['provider'];
            
            // Check if this provider should be included based on options
            if (!$this->should_include_provider($key, $options)) {
                continue;
            }
            
            try {
                $provider_context = $provider->get_context($options);
                if (!empty($provider_context)) {
                    $context[$key] = $provider_context;
                }
            } catch (Exception $e) {
                // Log error but continue with other providers
                error_log("Cronicle Context Manager: Error getting context from provider '{$key}': " . $e->getMessage());
            }
        }
        
        // Apply context filters
        $context = apply_filters('cronicle_context_data', $context, $options);
        
        // Cache the context
        if ($use_cache) {
            $this->context_cache[$cache_key] = $context;
        }
        
        return $context;
    }
    
    /**
     * Build formatted context string from context data
     * 
     * @param array $context_data Context data from gather_context()
     * @param string $format Output format ('plain', 'markdown', 'structured')
     * @return string Formatted context string
     */
    public function build_context_string($context_data, $format = 'structured') {
        if (empty($context_data)) {
            return '';
        }
        
        $context_parts = array();
        
        foreach ($context_data as $provider_key => $provider_context) {
            $provider_data = $this->providers[$provider_key] ?? null;
            if (!$provider_data) {
                continue;
            }
            
            $provider = $provider_data['provider'];
            $formatted_section = $provider->format_context($provider_context, $format);
            
            if (!empty($formatted_section)) {
                $context_parts[] = $formatted_section;
            }
        }
        
        $separator = ($format === 'markdown') ? "\n\n---\n\n" : "\n\n";
        return implode($separator, $context_parts);
    }
    
    /**
     * Generate a complete prompt with context
     * 
     * @param string $template_key Template identifier
     * @param array $variables Template variables
     * @param array $context_options Context gathering options
     * @return string|WP_Error Complete prompt or error
     */
    public function generate_prompt($template_key, $variables = array(), $context_options = array()) {
        // Get template library
        $template_library = Cronicle_Prompt_Template_Library::get_instance();
        
        // Get the template
        $template = $template_library->get_template($template_key);
        if (is_wp_error($template)) {
            return $template;
        }
        
        // Gather context
        $context_data = $this->gather_context($context_options);
        $context_string = $this->build_context_string($context_data, 'structured');
        
        // Merge context into variables
        $variables['context'] = $context_string;
        $variables['has_context'] = !empty($context_string);
        
        // Compile the template
        return $template_library->compile_template($template, $variables);
    }
    
    /**
     * Clear the context cache
     */
    public function clear_cache() {
        $this->context_cache = array();
    }
    
    /**
     * Check if a provider should be included based on options
     * 
     * @param string $provider_key Provider identifier
     * @param array $options Context options
     * @return bool
     */
    private function should_include_provider($provider_key, $options) {
        // Check if provider is explicitly excluded
        if (isset($options['exclude_providers']) && in_array($provider_key, $options['exclude_providers'])) {
            return false;
        }
        
        // Check if only specific providers should be included
        if (isset($options['include_providers']) && !in_array($provider_key, $options['include_providers'])) {
            return false;
        }
        
        // Check user preferences
        $user_preferences = cronicle_preferences_engine()->get_user_preferences();
        $provider_enabled = $user_preferences['context_providers'][$provider_key] ?? true;
        
        return $provider_enabled;
    }
    
    /**
     * Register default context providers
     */
    private function register_default_providers() {
        // Load provider classes
        require_once CRONICLE_PLUGIN_DIR . 'includes/context/providers/class-site-context-provider.php';
        require_once CRONICLE_PLUGIN_DIR . 'includes/context/providers/class-user-context-provider.php';
        require_once CRONICLE_PLUGIN_DIR . 'includes/context/providers/class-content-context-provider.php';
        require_once CRONICLE_PLUGIN_DIR . 'includes/context/providers/class-conversation-context-provider.php';
        require_once CRONICLE_PLUGIN_DIR . 'includes/context/providers/class-writing-style-provider.php';
        
        // Register providers with priorities
        $this->register_provider('site', new Cronicle_Site_Context_Provider(), 5);
        $this->register_provider('user', new Cronicle_User_Context_Provider(), 10);
        $this->register_provider('writing_style', new Cronicle_Writing_Style_Provider(), 15);
        $this->register_provider('content', new Cronicle_Content_Context_Provider(), 20);
        $this->register_provider('conversation', new Cronicle_Conversation_Context_Provider(), 25);
    }
}

/**
 * Get the global context manager instance
 * 
 * @return Cronicle_Context_Manager
 */
function cronicle_context_manager() {
    return Cronicle_Context_Manager::get_instance();
}