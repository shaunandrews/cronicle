<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cronicle Prompt Template Library
 * 
 * Manages prompt templates for different content types and styles
 */
class Cronicle_Prompt_Template_Library {
    
    /**
     * Registered templates
     * 
     * @var array
     */
    private $templates = array();
    
    /**
     * Template cache
     * 
     * @var array
     */
    private $template_cache = array();
    
    /**
     * Singleton instance
     * 
     * @var Cronicle_Prompt_Template_Library
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     * 
     * @return Cronicle_Prompt_Template_Library
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Load default templates
     */
    private function __construct() {
        $this->load_default_templates();
    }
    
    /**
     * Register a template
     * 
     * @param string $key Template identifier
     * @param array $template Template configuration
     * @return bool|WP_Error Success or error
     */
    public function register_template($key, $template) {
        // Validate template structure
        $validation = $this->validate_template($template);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        $this->templates[$key] = array_merge(array(
            'name' => '',
            'description' => '',
            'category' => 'general',
            'variables' => array(),
            'content' => '',
            'priority' => 10,
            'conditions' => array()
        ), $template);
        
        return true;
    }
    
    /**
     * Get a specific template
     * 
     * @param string $key Template identifier
     * @return array|WP_Error Template data or error
     */
    public function get_template($key) {
        if (!isset($this->templates[$key])) {
            return new WP_Error('template_not_found', "Template '{$key}' not found.");
        }
        
        return $this->templates[$key];
    }
    
    /**
     * Get all registered templates
     * 
     * @param string $category Optional category filter
     * @return array Templates
     */
    public function get_templates($category = null) {
        if ($category === null) {
            return $this->templates;
        }
        
        return array_filter($this->templates, function($template) use ($category) {
            return $template['category'] === $category;
        });
    }
    
    /**
     * Get template categories
     * 
     * @return array Available categories
     */
    public function get_categories() {
        $categories = array();
        
        foreach ($this->templates as $template) {
            $categories[] = $template['category'];
        }
        
        return array_unique($categories);
    }
    
    /**
     * Compile a template with variables
     * 
     * @param array $template Template data
     * @param array $variables Template variables
     * @return string Compiled template
     */
    public function compile_template($template, $variables = array()) {
        $content = $template['content'];
        
        // Add default variables
        $default_variables = array(
            'site_name' => get_bloginfo('name'),
            'site_url' => get_site_url(),
            'current_date' => date('Y-m-d'),
            'current_time' => date('H:i:s'),
            'user_name' => wp_get_current_user()->display_name
        );
        
        $variables = array_merge($default_variables, $variables);
        
        // Replace template variables
        foreach ($variables as $var_key => $var_value) {
            $placeholder = '{{' . $var_key . '}}';
            $content = str_replace($placeholder, $var_value, $content);
        }
        
        // Handle conditional sections
        $content = $this->process_conditionals($content, $variables);
        
        // Clean up any remaining placeholders
        $content = preg_replace('/\{\{[^}]+\}\}/', '', $content);
        
        return trim($content);
    }
    
    /**
     * Find best template for given criteria
     * 
     * @param array $criteria Selection criteria
     * @return string|WP_Error Template key or error
     */
    public function find_best_template($criteria) {
        $scored_templates = array();
        
        foreach ($this->templates as $key => $template) {
            $score = $this->score_template_match($template, $criteria);
            if ($score > 0) {
                $scored_templates[$key] = $score;
            }
        }
        
        if (empty($scored_templates)) {
            return new WP_Error('no_matching_template', 'No matching template found for the given criteria.');
        }
        
        // Sort by score (highest first)
        arsort($scored_templates);
        
        return key($scored_templates);
    }
    
    /**
     * Validate template structure
     * 
     * @param array $template Template data
     * @return bool|WP_Error True if valid, error otherwise
     */
    private function validate_template($template) {
        if (!is_array($template)) {
            return new WP_Error('invalid_template', 'Template must be an array.');
        }
        
        if (empty($template['content'])) {
            return new WP_Error('missing_content', 'Template must have content.');
        }
        
        // Validate variables structure
        if (isset($template['variables']) && !is_array($template['variables'])) {
            return new WP_Error('invalid_variables', 'Template variables must be an array.');
        }
        
        return true;
    }
    
    /**
     * Score template match against criteria
     * 
     * @param array $template Template data
     * @param array $criteria Selection criteria
     * @return int Match score
     */
    private function score_template_match($template, $criteria) {
        $score = 0;
        
        // Category match
        if (isset($criteria['category']) && $template['category'] === $criteria['category']) {
            $score += 10;
        }
        
        // Content type match
        if (isset($criteria['content_type'])) {
            $content_types = $template['content_types'] ?? array($template['category']);
            if (in_array($criteria['content_type'], $content_types)) {
                $score += 8;
            }
        }
        
        // Style preference match
        if (isset($criteria['style'])) {
            $styles = $template['styles'] ?? array();
            if (in_array($criteria['style'], $styles)) {
                $score += 6;
            }
        }
        
        // Check conditions
        if (!empty($template['conditions'])) {
            foreach ($template['conditions'] as $condition) {
                if ($this->evaluate_condition($condition, $criteria)) {
                    $score += 3;
                } else {
                    $score -= 2; // Penalty for not meeting conditions
                }
            }
        }
        
        // Priority bonus
        $score += (20 - $template['priority']) / 2;
        
        return max(0, $score);
    }
    
    /**
     * Evaluate template condition
     * 
     * @param array $condition Condition data
     * @param array $criteria Selection criteria
     * @return bool Whether condition is met
     */
    private function evaluate_condition($condition, $criteria) {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? '';
        
        if (!isset($criteria[$field])) {
            return false;
        }
        
        $criteria_value = $criteria[$field];
        
        switch ($operator) {
            case 'equals':
                return $criteria_value === $value;
            case 'not_equals':
                return $criteria_value !== $value;
            case 'in':
                return is_array($value) && in_array($criteria_value, $value);
            case 'not_in':
                return is_array($value) && !in_array($criteria_value, $value);
            case 'contains':
                return strpos($criteria_value, $value) !== false;
            case 'not_contains':
                return strpos($criteria_value, $value) === false;
            default:
                return false;
        }
    }
    
    /**
     * Process conditional sections in template content
     * 
     * @param string $content Template content
     * @param array $variables Template variables
     * @return string Processed content
     */
    private function process_conditionals($content, $variables) {
        // Process {{#if variable}} ... {{/if}} blocks
        $content = preg_replace_callback(
            '/\{\{#if\s+([^}]+)\}\}(.*?)\{\{\/if\}\}/s',
            function($matches) use ($variables) {
                $condition = trim($matches[1]);
                $conditional_content = $matches[2];
                
                // Simple variable existence check
                if (isset($variables[$condition]) && !empty($variables[$condition])) {
                    return $conditional_content;
                }
                
                return '';
            },
            $content
        );
        
        // Process {{#unless variable}} ... {{/unless}} blocks
        $content = preg_replace_callback(
            '/\{\{#unless\s+([^}]+)\}\}(.*?)\{\{\/unless\}\}/s',
            function($matches) use ($variables) {
                $condition = trim($matches[1]);
                $conditional_content = $matches[2];
                
                // Simple variable existence check (inverse)
                if (!isset($variables[$condition]) || empty($variables[$condition])) {
                    return $conditional_content;
                }
                
                return '';
            },
            $content
        );
        
        return $content;
    }
    
    /**
     * Load default templates
     */
    private function load_default_templates() {
        // Load template files
        $template_files = array(
            'blog-post-casual' => CRONICLE_PLUGIN_DIR . 'includes/context/templates/prompt-templates/blog-post-casual.php',
            'blog-post-professional' => CRONICLE_PLUGIN_DIR . 'includes/context/templates/prompt-templates/blog-post-professional.php',
            'blog-post-technical' => CRONICLE_PLUGIN_DIR . 'includes/context/templates/prompt-templates/blog-post-technical.php',
            'content-outline' => CRONICLE_PLUGIN_DIR . 'includes/context/templates/prompt-templates/content-outline.php',
            'content-revision' => CRONICLE_PLUGIN_DIR . 'includes/context/templates/prompt-templates/content-revision.php',
            'seo-optimized' => CRONICLE_PLUGIN_DIR . 'includes/context/templates/prompt-templates/seo-optimized.php'
        );
        
        foreach ($template_files as $key => $file) {
            if (file_exists($file)) {
                $template_data = include $file;
                if (is_array($template_data)) {
                    $this->register_template($key, $template_data);
                }
            }
        }
        
        // Allow plugins to register additional templates
        do_action('cronicle_register_templates', $this);
    }
}

/**
 * Get the global prompt template library instance
 * 
 * @return Cronicle_Prompt_Template_Library
 */
function cronicle_prompt_template_library() {
    return Cronicle_Prompt_Template_Library::get_instance();
}