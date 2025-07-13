<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract Context Provider Base Class
 * 
 * Provides common functionality for all context providers
 */
abstract class Cronicle_Context_Provider_Base implements Cronicle_Context_Provider_Interface {
    
    /**
     * Provider configuration
     * 
     * @var array
     */
    protected $config = array();
    
    /**
     * Context cache for this provider
     * 
     * @var array
     */
    protected $cache = array();
    
    /**
     * Constructor
     * 
     * @param array $config Provider configuration
     */
    public function __construct($config = array()) {
        $this->config = wp_parse_args($config, $this->get_default_config());
    }
    
    /**
     * Get default configuration for this provider
     * 
     * @return array Default configuration
     */
    protected function get_default_config() {
        return array(
            'cache_enabled' => true,
            'cache_duration' => 300, // 5 minutes
            'max_items' => 10,
            'enabled' => true
        );
    }
    
    /**
     * Get context data (implemented by child classes)
     * 
     * @param array $options Context gathering options
     * @return array Context data
     */
    abstract public function get_context($options = array());
    
    /**
     * Format context data for display
     * 
     * @param array $context_data Raw context data
     * @param string $format Output format ('plain', 'markdown', 'structured')
     * @return string Formatted context
     */
    public function format_context($context_data, $format = 'structured') {
        if (empty($context_data)) {
            return '';
        }
        
        switch ($format) {
            case 'markdown':
                return $this->format_as_markdown($context_data);
            case 'plain':
                return $this->format_as_plain($context_data);
            case 'structured':
            default:
                return $this->format_as_structured($context_data);
        }
    }
    
    /**
     * Safely convert value to string for context display
     * 
     * @param mixed $value Value to convert
     * @return string String representation
     */
    protected function safe_string_conversion($value) {
        if (is_string($value)) {
            return $value;
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_numeric($value)) {
            return (string) $value;
        } elseif (is_array($value)) {
            if (empty($value)) {
                return 'none';
            }
            // Check if it's a simple indexed array of strings
            if (array_keys($value) === range(0, count($value) - 1)) {
                return implode(', ', array_slice(array_map('strval', $value), 0, 5));
            } else {
                // Associative array - show key-value pairs
                $pairs = array();
                $count = 0;
                foreach ($value as $k => $v) {
                    if ($count >= 3) break; // Limit to 3 pairs
                    $pairs[] = $k . ': ' . $this->safe_string_conversion($v);
                    $count++;
                }
                return implode(', ', $pairs);
            }
        } elseif (is_null($value)) {
            return 'null';
        } else {
            return (string) $value;
        }
    }

    /**
     * Format context as structured text
     * 
     * @param array $context_data Context data
     * @return string Formatted context
     */
    protected function format_as_structured($context_data) {
        $lines = array();
        $section_title = strtoupper($this->get_name()) . ':';
        $lines[] = $section_title;
        
        foreach ($context_data as $key => $value) {
            $formatted_value = $this->safe_string_conversion($value);
            $lines[] = '- ' . ucfirst(str_replace('_', ' ', $key)) . ': ' . $formatted_value;
        }
        
        return implode("\n", $lines);
    }
    
    /**
     * Format context as markdown
     * 
     * @param array $context_data Context data
     * @return string Formatted context
     */
    protected function format_as_markdown($context_data) {
        $lines = array();
        $lines[] = '## ' . $this->get_name();
        
        foreach ($context_data as $key => $value) {
            $formatted_value = $this->safe_string_conversion($value);
            $lines[] = '**' . ucfirst(str_replace('_', ' ', $key)) . ':** ' . $formatted_value;
        }
        
        return implode("\n", $lines);
    }
    
    /**
     * Format context as plain text
     * 
     * @param array $context_data Context data
     * @return string Formatted context
     */
    protected function format_as_plain($context_data) {
        $parts = array();
        
        foreach ($context_data as $key => $value) {
            $formatted_value = $this->safe_string_conversion($value);
            $parts[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $formatted_value;
        }
        
        return implode('. ', $parts) . '.';
    }
    
    /**
     * Check if provider is available/enabled
     * 
     * @param array $options Context options
     * @return bool Whether provider is available
     */
    public function is_available($options = array()) {
        return $this->config['enabled'];
    }
    
    /**
     * Get cached context if available
     * 
     * @param string $cache_key Cache identifier
     * @return array|false Cached context or false
     */
    protected function get_cached_context($cache_key) {
        if (!$this->config['cache_enabled']) {
            return false;
        }
        
        $cache_data = get_transient('cronicle_context_' . $cache_key);
        return $cache_data !== false ? $cache_data : false;
    }
    
    /**
     * Set cached context
     * 
     * @param string $cache_key Cache identifier
     * @param array $context_data Context to cache
     */
    protected function set_cached_context($cache_key, $context_data) {
        if ($this->config['cache_enabled']) {
            set_transient('cronicle_context_' . $cache_key, $context_data, $this->config['cache_duration']);
        }
    }
    
    /**
     * Generate cache key for given options
     * 
     * @param array $options Context options
     * @return string Cache key
     */
    protected function generate_cache_key($options = array()) {
        $provider_class = get_class($this);
        $user_id = get_current_user_id();
        $options_hash = md5(serialize($options));
        
        return "{$provider_class}_{$user_id}_{$options_hash}";
    }
    
    /**
     * Sanitize and limit array to max items
     * 
     * @param array $items Array to limit
     * @param int $max_items Maximum number of items (uses config if not provided)
     * @return array Limited array
     */
    protected function limit_items($items, $max_items = null) {
        if ($max_items === null) {
            $max_items = $this->config['max_items'];
        }
        
        return array_slice($items, 0, $max_items);
    }
}

/**
 * Helper function to check if array is indexed (not associative)
 * 
 * @param array $array Array to check
 * @return bool True if indexed array
 */
function is_indexed_array($array) {
    if (!is_array($array)) {
        return false;
    }
    
    return array_keys($array) === range(0, count($array) - 1);
}