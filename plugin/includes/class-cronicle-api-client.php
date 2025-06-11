<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cronicle API Client Class
 * 
 * Handles communication with the Anthropic API
 */
class Cronicle_API_Client {
    
    /**
     * API base URL
     */
    const API_BASE_URL = 'https://api.anthropic.com/v1';
    
    /**
     * Get the configured API key
     * 
     * @return string|false The API key or false if not configured
     */
    private function get_api_key() {
        if (!class_exists('Cronicle_Admin_Settings')) {
            return false;
        }
        
        $api_key = Cronicle_Admin_Settings::get_api_key();
        return !empty($api_key) ? $api_key : false;
    }
    
    /**
     * Check if the API is configured and ready to use
     * 
     * @return bool True if API is ready, false otherwise
     */
    public function is_api_ready() {
        return $this->get_api_key() !== false;
    }
    
    /**
     * Make a request to the Anthropic API
     * 
     * @param string $endpoint The API endpoint to call
     * @param array $data The data to send
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return array|WP_Error The API response or WP_Error on failure
     */
    public function make_request($endpoint, $data = array(), $method = 'POST') {
        $api_key = $this->get_api_key();
        
        if (!$api_key) {
            return new WP_Error(
                'no_api_key',
                __('Anthropic API key is not configured. Please configure it in Settings > Cronicle.', 'cronicle')
            );
        }
        
        $url = self::API_BASE_URL . '/' . ltrim($endpoint, '/');
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
                'User-Agent' => 'Cronicle WordPress Plugin/' . CRONICLE_VERSION,
            ),
            'timeout' => 30,
        );
        
        if (!empty($data) && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = wp_json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Decode JSON response
        $decoded_body = json_decode($body, true);
        
        if ($status_code >= 400) {
            $error_message = isset($decoded_body['error']['message']) 
                ? $decoded_body['error']['message'] 
                : sprintf(__('API request failed with status code %d', 'cronicle'), $status_code);
            
            return new WP_Error(
                'api_request_failed',
                $error_message,
                array('status_code' => $status_code, 'response' => $decoded_body)
            );
        }
        
        return $decoded_body;
    }
    
    /**
     * Test the API connection
     * 
     * @return array|WP_Error Test result or error
     */
    public function test_connection() {
        // Simple test request to validate the API key
        $test_data = array(
            'model' => 'claude-3-haiku-20240307',
            'max_tokens' => 10,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => 'Hello'
                )
            )
        );
        
        $response = $this->make_request('messages', $test_data);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return array(
            'success' => true,
            'message' => __('API connection successful!', 'cronicle')
        );
    }
    
    /**
     * Generate content using the Anthropic API
     * 
     * @param string $prompt The prompt to send to the API
     * @param array $options Additional options for the API request
     * @return array|WP_Error The generated content or error
     */
    public function generate_content($prompt, $options = array()) {
        if (!$this->is_api_ready()) {
            return new WP_Error(
                'api_not_configured',
                __('Anthropic API is not configured. Please add your API key in Settings > Cronicle.', 'cronicle')
            );
        }
        
        $defaults = array(
            'model' => 'claude-3-sonnet-20240229',
            'max_tokens' => 1000,
            'temperature' => 0.7,
        );
        
        $options = wp_parse_args($options, $defaults);
        
        $data = array(
            'model' => $options['model'],
            'max_tokens' => $options['max_tokens'],
            'temperature' => $options['temperature'],
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            )
        );
        
        return $this->make_request('messages', $data);
    }
}

/**
 * Get the global API client instance
 * 
 * @return Cronicle_API_Client
 */
function cronicle_api_client() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new Cronicle_API_Client();
    }
    
    return $instance;
} 