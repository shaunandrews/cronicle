<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cronicle Admin Main Class (Refactored)
 * 
 * Orchestrates all admin functionality by managing separate components
 */
class Cronicle_Admin_Main_Refactored {
    
    /**
     * Component instances
     */
    private $router;
    private $ui;
    private $enqueue;
    private $chat_handler;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_components();
        $this->register_hooks();
    }
    
    /**
     * Initialize all components
     */
    private function init_components() {
        // Initialize UI handler first
        $this->ui = new Cronicle_UI();
        
        // Initialize router with UI handler reference
        $this->router = new Cronicle_Router($this->ui);
        
        // Initialize other components
        $this->enqueue = new Cronicle_Enqueue();
        $this->chat_handler = new Cronicle_Chat_Handler();
    }
    
    /**
     * Register all hooks through components
     */
    private function register_hooks() {
        $this->router->register_hooks();
        $this->enqueue->register_hooks();
        $this->chat_handler->register_hooks();
    }
    
    /**
     * Get component instances (for external access if needed)
     */
    public function get_router() {
        return $this->router;
    }
    
    public function get_ui() {
        return $this->ui;
    }
    
    public function get_enqueue() {
        return $this->enqueue;
    }
    
    public function get_chat_handler() {
        return $this->chat_handler;
    }
} 