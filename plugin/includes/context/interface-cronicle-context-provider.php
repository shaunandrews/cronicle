<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Context Provider Interface
 * 
 * Defines the contract for all context providers
 */
interface Cronicle_Context_Provider_Interface {
    
    /**
     * Get context data
     * 
     * @param array $options Context gathering options
     * @return array Context data
     */
    public function get_context($options = array());
    
    /**
     * Format context data for display
     * 
     * @param array $context_data Raw context data
     * @param string $format Output format ('plain', 'markdown', 'structured')
     * @return string Formatted context
     */
    public function format_context($context_data, $format = 'structured');
    
    /**
     * Get provider name/title
     * 
     * @return string Provider name
     */
    public function get_name();
    
    /**
     * Get provider description
     * 
     * @return string Provider description
     */
    public function get_description();
    
    /**
     * Check if provider is available/enabled
     * 
     * @param array $options Context options
     * @return bool Whether provider is available
     */
    public function is_available($options = array());
}