<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cronicle Preferences Engine
 * 
 * Manages user and site-wide preferences for context and AI behavior
 */
class Cronicle_Preferences_Engine {
    
    /**
     * User preferences option prefix
     */
    const USER_PREF_PREFIX = 'cronicle_user_prefs_';
    
    /**
     * Site preferences option key
     */
    const SITE_PREF_KEY = 'cronicle_site_preferences';
    
    /**
     * Default user preferences
     * 
     * @var array
     */
    private $default_user_preferences = array();
    
    /**
     * Default site preferences
     * 
     * @var array
     */
    private $default_site_preferences = array();
    
    /**
     * Preference cache
     * 
     * @var array
     */
    private $preference_cache = array();
    
    /**
     * Singleton instance
     * 
     * @var Cronicle_Preferences_Engine
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     * 
     * @return Cronicle_Preferences_Engine
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->initialize_default_preferences();
        $this->register_hooks();
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('wp_ajax_cronicle_save_user_preferences', array($this, 'ajax_save_user_preferences'));
        add_action('wp_ajax_cronicle_reset_user_preferences', array($this, 'ajax_reset_user_preferences'));
        add_action('wp_ajax_cronicle_save_site_preferences', array($this, 'ajax_save_site_preferences'));
        
        // Clear cache when user preferences are updated
        add_action('updated_user_meta', array($this, 'clear_user_preference_cache'), 10, 4);
        add_action('updated_option', array($this, 'clear_site_preference_cache'), 10, 3);
    }
    
    /**
     * Get user preferences
     * 
     * @param int $user_id User ID (defaults to current user)
     * @return array User preferences
     */
    public function get_user_preferences($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return $this->default_user_preferences;
        }
        
        // Check cache first
        $cache_key = 'user_' . $user_id;
        if (isset($this->preference_cache[$cache_key])) {
            return $this->preference_cache[$cache_key];
        }
        
        // Get user preferences from database
        $stored_preferences = get_user_meta($user_id, self::USER_PREF_PREFIX . 'settings', true);
        
        if (!is_array($stored_preferences)) {
            $stored_preferences = array();
        }
        
        // Merge with defaults
        $preferences = $this->deep_merge_preferences($this->default_user_preferences, $stored_preferences);
        
        // Cache the result
        $this->preference_cache[$cache_key] = $preferences;
        
        return $preferences;
    }
    
    /**
     * Save user preferences
     * 
     * @param array $preferences Preferences to save
     * @param int $user_id User ID (defaults to current user)
     * @return bool Success
     */
    public function save_user_preferences($preferences, $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        // Validate preferences
        $validated_preferences = $this->validate_user_preferences($preferences);
        
        // Save to database
        $result = update_user_meta($user_id, self::USER_PREF_PREFIX . 'settings', $validated_preferences);
        
        // Clear cache
        $this->clear_user_preference_cache($user_id);
        
        return $result !== false;
    }
    
    /**
     * Get site-wide preferences
     * 
     * @return array Site preferences
     */
    public function get_site_preferences() {
        // Check cache first
        if (isset($this->preference_cache['site'])) {
            return $this->preference_cache['site'];
        }
        
        // Get site preferences from database
        $stored_preferences = get_option(self::SITE_PREF_KEY, array());
        
        if (!is_array($stored_preferences)) {
            $stored_preferences = array();
        }
        
        // Merge with defaults
        $preferences = $this->deep_merge_preferences($this->default_site_preferences, $stored_preferences);
        
        // Cache the result
        $this->preference_cache['site'] = $preferences;
        
        return $preferences;
    }
    
    /**
     * Save site-wide preferences
     * 
     * @param array $preferences Preferences to save
     * @return bool Success
     */
    public function save_site_preferences($preferences) {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        // Validate preferences
        $validated_preferences = $this->validate_site_preferences($preferences);
        
        // Save to database
        $result = update_option(self::SITE_PREF_KEY, $validated_preferences);
        
        // Clear cache
        $this->clear_site_preference_cache();
        
        return $result;
    }
    
    /**
     * Get specific preference value
     * 
     * @param string $key Preference key (dot notation supported)
     * @param mixed $default Default value
     * @param string $scope Preference scope ('user' or 'site')
     * @param int $user_id User ID for user scope
     * @return mixed Preference value
     */
    public function get_preference($key, $default = null, $scope = 'user', $user_id = null) {
        if ($scope === 'site') {
            $preferences = $this->get_site_preferences();
        } else {
            $preferences = $this->get_user_preferences($user_id);
        }
        
        return $this->get_nested_preference($preferences, $key, $default);
    }
    
    /**
     * Set specific preference value
     * 
     * @param string $key Preference key (dot notation supported)
     * @param mixed $value Preference value
     * @param string $scope Preference scope ('user' or 'site')
     * @param int $user_id User ID for user scope
     * @return bool Success
     */
    public function set_preference($key, $value, $scope = 'user', $user_id = null) {
        if ($scope === 'site') {
            $preferences = $this->get_site_preferences();
            $this->set_nested_preference($preferences, $key, $value);
            return $this->save_site_preferences($preferences);
        } else {
            $preferences = $this->get_user_preferences($user_id);
            $this->set_nested_preference($preferences, $key, $value);
            return $this->save_user_preferences($preferences, $user_id);
        }
    }
    
    /**
     * Reset user preferences to defaults
     * 
     * @param int $user_id User ID (defaults to current user)
     * @return bool Success
     */
    public function reset_user_preferences($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        // Delete user preferences
        $result = delete_user_meta($user_id, self::USER_PREF_PREFIX . 'settings');
        
        // Clear cache
        $this->clear_user_preference_cache($user_id);
        
        return $result;
    }
    
    /**
     * Get preference schema for UI generation
     * 
     * @param string $scope Preference scope ('user' or 'site')
     * @return array Preference schema
     */
    public function get_preference_schema($scope = 'user') {
        if ($scope === 'site') {
            return $this->get_site_preference_schema();
        } else {
            return $this->get_user_preference_schema();
        }
    }
    
    /**
     * Initialize default preferences
     */
    private function initialize_default_preferences() {
        $this->default_user_preferences = array(
            'writing_style' => array(
                'tone' => 'professional',
                'voice' => 'informative',
                'target_audience' => 'general',
                'complexity_level' => 'moderate',
                'perspective' => 'third_person',
                'preferred_length' => 'medium',
                'use_humor' => false,
                'include_examples' => true,
                'cta_style' => 'subtle'
            ),
            'content_preferences' => array(
                'default_content_type' => 'blog_post',
                'preferred_template' => 'auto',
                'seo_optimization' => true,
                'include_images' => true,
                'auto_save_drafts' => true
            ),
            'context_providers' => array(
                'site' => true,
                'user' => true,
                'writing_style' => true,
                'content' => true,
                'conversation' => true
            ),
            'ui_preferences' => array(
                'default_mode' => 'draft',
                'show_context_preview' => false,
                'enable_shortcuts' => true,
                'auto_expand_preview' => false
            ),
            'ai_behavior' => array(
                'creativity_level' => 0.7,
                'response_length' => 'balanced',
                'follow_up_suggestions' => true,
                'auto_title_generation' => true
            )
        );
        
        $this->default_site_preferences = array(
            'default_context_providers' => array(
                'site' => true,
                'user' => true,
                'writing_style' => true,
                'content' => false,
                'conversation' => false
            ),
            'content_guidelines' => array(
                'enforce_style_guide' => false,
                'required_categories' => array(),
                'blocked_topics' => array(),
                'content_approval_required' => false
            ),
            'ai_settings' => array(
                'max_tokens' => 4000,
                'default_model' => 'claude-3-5-sonnet-20241022',
                'temperature' => 0.7,
                'enable_context_caching' => true
            ),
            'user_permissions' => array(
                'allow_preference_customization' => true,
                'allow_template_selection' => true,
                'allow_context_modification' => false,
                'require_admin_approval' => false
            )
        );
    }
    
    /**
     * Validate user preferences
     * 
     * @param array $preferences Preferences to validate
     * @return array Validated preferences
     */
    private function validate_user_preferences($preferences) {
        $validated = array();
        $schema = $this->get_user_preference_schema();
        
        foreach ($schema as $section_key => $section) {
            if (!isset($preferences[$section_key])) {
                continue;
            }
            
            $validated[$section_key] = array();
            
            foreach ($section['fields'] as $field_key => $field) {
                if (!isset($preferences[$section_key][$field_key])) {
                    continue;
                }
                
                $value = $preferences[$section_key][$field_key];
                $validated_value = $this->validate_preference_value($value, $field);
                
                if ($validated_value !== null) {
                    $validated[$section_key][$field_key] = $validated_value;
                }
            }
        }
        
        return $validated;
    }
    
    /**
     * Validate site preferences
     * 
     * @param array $preferences Preferences to validate
     * @return array Validated preferences
     */
    private function validate_site_preferences($preferences) {
        $validated = array();
        $schema = $this->get_site_preference_schema();
        
        foreach ($schema as $section_key => $section) {
            if (!isset($preferences[$section_key])) {
                continue;
            }
            
            $validated[$section_key] = array();
            
            foreach ($section['fields'] as $field_key => $field) {
                if (!isset($preferences[$section_key][$field_key])) {
                    continue;
                }
                
                $value = $preferences[$section_key][$field_key];
                $validated_value = $this->validate_preference_value($value, $field);
                
                if ($validated_value !== null) {
                    $validated[$section_key][$field_key] = $validated_value;
                }
            }
        }
        
        return $validated;
    }
    
    /**
     * Validate individual preference value
     * 
     * @param mixed $value Value to validate
     * @param array $field Field configuration
     * @return mixed Validated value or null if invalid
     */
    private function validate_preference_value($value, $field) {
        $type = $field['type'];
        
        switch ($type) {
            case 'boolean':
                return (bool) $value;
                
            case 'string':
                $sanitized = sanitize_text_field($value);
                if (isset($field['options']) && !in_array($sanitized, array_keys($field['options']))) {
                    return null;
                }
                return $sanitized;
                
            case 'number':
                $number = floatval($value);
                if (isset($field['min']) && $number < $field['min']) {
                    $number = $field['min'];
                }
                if (isset($field['max']) && $number > $field['max']) {
                    $number = $field['max'];
                }
                return $number;
                
            case 'array':
                if (!is_array($value)) {
                    return array();
                }
                return array_map('sanitize_text_field', $value);
                
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Get user preference schema
     * 
     * @return array User preference schema
     */
    private function get_user_preference_schema() {
        return array(
            'writing_style' => array(
                'title' => __('Writing Style', 'cronicle'),
                'description' => __('Configure your preferred writing style and tone', 'cronicle'),
                'fields' => array(
                    'tone' => array(
                        'title' => __('Tone', 'cronicle'),
                        'type' => 'string',
                        'options' => array(
                            'casual' => __('Casual', 'cronicle'),
                            'professional' => __('Professional', 'cronicle'),
                            'friendly' => __('Friendly', 'cronicle'),
                            'authoritative' => __('Authoritative', 'cronicle'),
                            'technical' => __('Technical', 'cronicle')
                        ),
                        'default' => 'professional'
                    ),
                    'target_audience' => array(
                        'title' => __('Target Audience', 'cronicle'),
                        'type' => 'string',
                        'options' => array(
                            'general' => __('General Public', 'cronicle'),
                            'professional' => __('Professionals', 'cronicle'),
                            'technical' => __('Technical Audience', 'cronicle'),
                            'beginner' => __('Beginners', 'cronicle'),
                            'expert' => __('Experts', 'cronicle')
                        ),
                        'default' => 'general'
                    ),
                    'preferred_length' => array(
                        'title' => __('Preferred Content Length', 'cronicle'),
                        'type' => 'string',
                        'options' => array(
                            'short' => __('Short (300-500 words)', 'cronicle'),
                            'medium' => __('Medium (500-800 words)', 'cronicle'),
                            'long' => __('Long (800-1200 words)', 'cronicle'),
                            'comprehensive' => __('Comprehensive (1200+ words)', 'cronicle')
                        ),
                        'default' => 'medium'
                    )
                )
            ),
            'context_providers' => array(
                'title' => __('Context Information', 'cronicle'),
                'description' => __('Choose what context information to include in AI requests', 'cronicle'),
                'fields' => array(
                    'site' => array(
                        'title' => __('Site Information', 'cronicle'),
                        'type' => 'boolean',
                        'default' => true
                    ),
                    'user' => array(
                        'title' => __('User Profile', 'cronicle'),
                        'type' => 'boolean',
                        'default' => true
                    ),
                    'writing_style' => array(
                        'title' => __('Writing Style Preferences', 'cronicle'),
                        'type' => 'boolean',
                        'default' => true
                    ),
                    'content' => array(
                        'title' => __('Related Content', 'cronicle'),
                        'type' => 'boolean',
                        'default' => true
                    ),
                    'conversation' => array(
                        'title' => __('Conversation History', 'cronicle'),
                        'type' => 'boolean',
                        'default' => true
                    )
                )
            )
        );
    }
    
    /**
     * Get site preference schema
     * 
     * @return array Site preference schema
     */
    private function get_site_preference_schema() {
        return array(
            'ai_settings' => array(
                'title' => __('AI Configuration', 'cronicle'),
                'description' => __('Configure AI behavior and performance settings', 'cronicle'),
                'fields' => array(
                    'max_tokens' => array(
                        'title' => __('Maximum Response Length', 'cronicle'),
                        'type' => 'number',
                        'min' => 1000,
                        'max' => 8000,
                        'default' => 4000
                    ),
                    'temperature' => array(
                        'title' => __('Creativity Level', 'cronicle'),
                        'type' => 'number',
                        'min' => 0.1,
                        'max' => 1.0,
                        'step' => 0.1,
                        'default' => 0.7
                    )
                )
            )
        );
    }
    
    /**
     * Deep merge preferences arrays
     * 
     * @param array $defaults Default preferences
     * @param array $custom Custom preferences
     * @return array Merged preferences
     */
    private function deep_merge_preferences($defaults, $custom) {
        $merged = $defaults;
        
        foreach ($custom as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->deep_merge_preferences($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        
        return $merged;
    }
    
    /**
     * Get nested preference value using dot notation
     * 
     * @param array $preferences Preferences array
     * @param string $key Dot notation key
     * @param mixed $default Default value
     * @return mixed Preference value
     */
    private function get_nested_preference($preferences, $key, $default = null) {
        $keys = explode('.', $key);
        $value = $preferences;
        
        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        
        return $value;
    }
    
    /**
     * Set nested preference value using dot notation
     * 
     * @param array &$preferences Preferences array (by reference)
     * @param string $key Dot notation key
     * @param mixed $value Value to set
     */
    private function set_nested_preference(&$preferences, $key, $value) {
        $keys = explode('.', $key);
        $current = &$preferences;
        
        foreach ($keys as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = array();
            }
            $current = &$current[$segment];
        }
        
        $current = $value;
    }
    
    /**
     * Clear user preference cache
     * 
     * @param int $user_id User ID
     */
    public function clear_user_preference_cache($user_id) {
        $cache_key = 'user_' . $user_id;
        unset($this->preference_cache[$cache_key]);
    }
    
    /**
     * Clear site preference cache
     */
    public function clear_site_preference_cache() {
        unset($this->preference_cache['site']);
    }
    
    /**
     * AJAX handler for saving user preferences
     */
    public function ajax_save_user_preferences() {
        if (!wp_verify_nonce($_POST['nonce'], 'cronicle_preferences_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to save preferences.', 'cronicle')));
        }
        
        $preferences = $_POST['preferences'] ?? array();
        $success = $this->save_user_preferences($preferences);
        
        if ($success) {
            wp_send_json_success(array('message' => __('Preferences saved successfully!', 'cronicle')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save preferences.', 'cronicle')));
        }
    }
    
    /**
     * AJAX handler for resetting user preferences
     */
    public function ajax_reset_user_preferences() {
        if (!wp_verify_nonce($_POST['nonce'], 'cronicle_preferences_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to reset preferences.', 'cronicle')));
        }
        
        $success = $this->reset_user_preferences();
        
        if ($success) {
            wp_send_json_success(array('message' => __('Preferences reset to defaults!', 'cronicle')));
        } else {
            wp_send_json_error(array('message' => __('Failed to reset preferences.', 'cronicle')));
        }
    }
    
    /**
     * AJAX handler for saving site preferences
     */
    public function ajax_save_site_preferences() {
        if (!wp_verify_nonce($_POST['nonce'], 'cronicle_preferences_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to save site preferences.', 'cronicle')));
        }
        
        $preferences = $_POST['preferences'] ?? array();
        $success = $this->save_site_preferences($preferences);
        
        if ($success) {
            wp_send_json_success(array('message' => __('Site preferences saved successfully!', 'cronicle')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save site preferences.', 'cronicle')));
        }
    }
}

/**
 * Get the global preferences engine instance
 * 
 * @return Cronicle_Preferences_Engine
 */
function cronicle_preferences_engine() {
    return Cronicle_Preferences_Engine::get_instance();
}