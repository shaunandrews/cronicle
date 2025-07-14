<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cronicle Chat History Class
 * 
 * Manages chat session storage using WordPress custom post type
 */
class Cronicle_Chat_History {
    
    /**
     * Custom post type name
     */
    const POST_TYPE = 'cronicle_chats';
    
    /**
     * Meta key for session ID
     */
    const META_SESSION_ID = '_cronicle_session_id';
    
    /**
     * Meta key for message count
     */
    const META_MESSAGE_COUNT = '_cronicle_message_count';
    
    /**
     * Meta key for last activity
     */
    const META_LAST_ACTIVITY = '_cronicle_last_activity';
    
    /**
     * Meta key for session active status
     */
    const META_SESSION_ACTIVE = '_cronicle_session_active';
    
    /**
     * Register hooks
     */
    public function register_hooks() {
        add_action('init', array($this, 'register_post_type'), 5);
        add_action('wp_ajax_cronicle_load_chat_history', array($this, 'handle_load_chat_history'));
        add_action('wp_ajax_cronicle_start_new_session', array($this, 'handle_start_new_session'));
        add_action('wp_ajax_cronicle_load_session_list', array($this, 'handle_load_session_list'));
        add_action('wp_ajax_cronicle_switch_session', array($this, 'handle_switch_session'));
        
        // Add a test endpoint to verify AJAX is working
        add_action('wp_ajax_cronicle_test', array($this, 'handle_test'));
    }
    
    /**
     * Register the custom post type
     */
    public function register_post_type() {
        $args = array(
            'labels' => array(
                'name' => __('Chat Sessions', 'cronicle'),
                'singular_name' => __('Chat Session', 'cronicle'),
                'add_new' => __('Add New Session', 'cronicle'),
                'add_new_item' => __('Add New Chat Session', 'cronicle'),
                'edit_item' => __('Edit Chat Session', 'cronicle'),
                'new_item' => __('New Chat Session', 'cronicle'),
                'view_item' => __('View Chat Session', 'cronicle'),
                'search_items' => __('Search Chat Sessions', 'cronicle'),
                'not_found' => __('No chat sessions found', 'cronicle'),
                'not_found_in_trash' => __('No chat sessions found in trash', 'cronicle'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_admin_bar' => false,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'author'),
            'has_archive' => false,
            'query_var' => false,
            'can_export' => true,
            'rewrite' => false,
            'menu_icon' => 'dashicons-format-chat',
        );
        
        register_post_type(self::POST_TYPE, $args);
    }
    
    /**
     * Get or create current active session for user
     */
    public function get_current_session($user_id) {
        if (!$user_id) {
            return false;
        }
        
        // Look for existing active session
        $query_args = array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'private',
            'author' => $user_id,
            'meta_query' => array(
                array(
                    'key' => self::META_SESSION_ACTIVE,
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $sessions = get_posts($query_args);
        
        if (!empty($sessions)) {
            return $sessions[0];
        }
        
        // Create new session if none found
        return $this->create_new_session($user_id);
    }
    
    /**
     * Create a new chat session
     */
    public function create_new_session($user_id) {
        if (!$user_id) {
            return false;
        }
        
        // Deactivate any existing active sessions
        $this->deactivate_user_sessions($user_id);
        
        $session_id = wp_generate_uuid4();
        $current_time = current_time('mysql');
        
        $post_data = array(
            'post_type' => self::POST_TYPE,
            'post_title' => sprintf(__('Chat Session - %s', 'cronicle'), current_time('M j, Y g:i A')),
            'post_content' => wp_json_encode(array()), // Empty message array
            'post_status' => 'private',
            'post_author' => $user_id,
            'meta_input' => array(
                self::META_SESSION_ID => $session_id,
                self::META_MESSAGE_COUNT => 0,
                self::META_LAST_ACTIVITY => $current_time,
                self::META_SESSION_ACTIVE => '1'
            )
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return false;
        }
        
        return get_post($post_id);
    }
    
    /**
     * Save a message to the session
     */
    public function save_message($session_post, $message_data) {
        if (!$session_post || !$message_data) {
            return false;
        }
        
        // Get existing messages
        $existing_content = $session_post->post_content;
        $messages = json_decode($existing_content, true);
        
        if (!is_array($messages)) {
            $messages = array();
        }
        
        // Add new message
        $message_data['id'] = 'msg_' . (count($messages) + 1);
        $message_data['timestamp'] = current_time('c'); // ISO 8601 format
        $messages[] = $message_data;
        
        // Debug: Track message saving
        error_log('Cronicle: Saving ' . $message_data['type'] . ' message to session ' . $session_post->ID . '. Total: ' . count($messages));
        
        // Update post
        $updated_post = array(
            'ID' => $session_post->ID,
            'post_content' => wp_json_encode($messages)
        );
        
        $result = wp_update_post($updated_post);
        
        if ($result && !is_wp_error($result)) {
            // Update meta data
            update_post_meta($session_post->ID, self::META_MESSAGE_COUNT, count($messages));
            update_post_meta($session_post->ID, self::META_LAST_ACTIVITY, current_time('mysql'));
        } else {
            error_log('Cronicle: Failed to save message to session ' . $session_post->ID);
        }
        
        return $result;
    }
    
    /**
     * Load session history messages
     */
    public function load_session_history($session_post) {
        if (!$session_post) {
            return array();
        }
        
        $content = $session_post->post_content;
        $messages = json_decode($content, true);
        
        return is_array($messages) ? $messages : array();
    }
    
    /**
     * Get user's chat sessions
     */
    public function get_user_sessions($user_id, $limit = 10) {
        if (!$user_id) {
            return array();
        }
        
        $query_args = array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'private',
            'author' => $user_id,
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_key' => self::META_LAST_ACTIVITY,
            'orderby' => 'meta_value',
        );
        
        return get_posts($query_args);
    }
    
    /**
     * Switch to a different session
     */
    public function switch_session($user_id, $session_id) {
        if (!$user_id || !$session_id) {
            return false;
        }
        
        // Deactivate current active sessions
        $this->deactivate_user_sessions($user_id);
        
        // Find and activate the target session
        $query_args = array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'private',
            'author' => $user_id,
            'meta_query' => array(
                array(
                    'key' => self::META_SESSION_ID,
                    'value' => $session_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        );
        
        $sessions = get_posts($query_args);
        
        if (!empty($sessions)) {
            $session_post = $sessions[0];
            update_post_meta($session_post->ID, self::META_SESSION_ACTIVE, '1');
            return $session_post;
        }
        
        return false;
    }
    
    /**
     * Deactivate all active sessions for a user
     */
    private function deactivate_user_sessions($user_id) {
        $query_args = array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'private',
            'author' => $user_id,
            'meta_query' => array(
                array(
                    'key' => self::META_SESSION_ACTIVE,
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1
        );
        
        $active_sessions = get_posts($query_args);
        
        foreach ($active_sessions as $session) {
            update_post_meta($session->ID, self::META_SESSION_ACTIVE, '0');
        }
    }
    
    /**
     * Cleanup old sessions (older than 30 days with no activity)
     */
    public function cleanup_old_sessions() {
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        $query_args = array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'private',
            'meta_query' => array(
                array(
                    'key' => self::META_LAST_ACTIVITY,
                    'value' => $cutoff_date,
                    'compare' => '<',
                    'type' => 'DATETIME'
                )
            ),
            'posts_per_page' => -1
        );
        
        $old_sessions = get_posts($query_args);
        
        foreach ($old_sessions as $session) {
            wp_delete_post($session->ID, true);
        }
        
        return count($old_sessions);
    }
    
    /**
     * AJAX handler: Load chat history
     */
    public function handle_load_chat_history() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cronicle_chat_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'cronicle')));
        }
        
        $user_id = get_current_user_id();
        $session = $this->get_current_session($user_id);
        
        if (!$session) {
            wp_send_json_error(array('message' => __('Could not load session.', 'cronicle')));
        }
        
        $messages = $this->load_session_history($session);
        $session_id = get_post_meta($session->ID, self::META_SESSION_ID, true);
        
        wp_send_json_success(array(
            'session_id' => $session_id,
            'messages' => $messages,
            'session_title' => $session->post_title
        ));
    }
    
    /**
     * AJAX handler: Test endpoint
     */
    public function handle_test() {
        wp_send_json_success(array('message' => 'Test endpoint working'));
    }
    
    /**
     * AJAX handler: Start new session
     */
    public function handle_start_new_session() {
        if (!wp_verify_nonce($_POST['nonce'], 'cronicle_chat_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'cronicle')));
        }
        
        $user_id = get_current_user_id();
        $session = $this->create_new_session($user_id);
        
        if (!$session) {
            wp_send_json_error(array('message' => __('Could not create new session.', 'cronicle')));
        }
        
        $session_id = get_post_meta($session->ID, self::META_SESSION_ID, true);
        
        wp_send_json_success(array(
            'session_id' => $session_id,
            'session_title' => $session->post_title,
            'message' => __('Started new chat session.', 'cronicle')
        ));
    }
    
    /**
     * AJAX handler: Load session list
     */
    public function handle_load_session_list() {
        if (!wp_verify_nonce($_POST['nonce'], 'cronicle_chat_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'cronicle')));
        }
        
        $user_id = get_current_user_id();
        $sessions = $this->get_user_sessions($user_id);
        
        $session_list = array();
        foreach ($sessions as $session) {
            $session_id = get_post_meta($session->ID, self::META_SESSION_ID, true);
            $message_count = get_post_meta($session->ID, self::META_MESSAGE_COUNT, true);
            $is_active = get_post_meta($session->ID, self::META_SESSION_ACTIVE, true) === '1';
            
            $session_list[] = array(
                'session_id' => $session_id,
                'title' => $session->post_title,
                'message_count' => intval($message_count),
                'last_activity' => $session->post_modified,
                'is_active' => $is_active
            );
        }
        
        wp_send_json_success(array('sessions' => $session_list));
    }
    
    /**
     * AJAX handler: Switch session
     */
    public function handle_switch_session() {
        if (!wp_verify_nonce($_POST['nonce'], 'cronicle_chat_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'cronicle')));
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $user_id = get_current_user_id();
        
        $session = $this->switch_session($user_id, $session_id);
        
        if (!$session) {
            wp_send_json_error(array('message' => __('Could not switch to session.', 'cronicle')));
        }
        
        $messages = $this->load_session_history($session);
        
        wp_send_json_success(array(
            'session_id' => $session_id,
            'messages' => $messages,
            'session_title' => $session->post_title
        ));
    }
}

/**
 * Get the global chat history instance
 * 
 * @return Cronicle_Chat_History
 */
function cronicle_chat_history() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new Cronicle_Chat_History();
    }
    
    return $instance;
}