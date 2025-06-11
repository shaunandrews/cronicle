<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cronicle Admin Main Page Class
 * 
 * Handles the main Cronicle admin page with chat interface
 */
class Cronicle_Admin_Main {
    
    /**
     * Main page slug
     */
    const PAGE_SLUG = 'cronicle';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_cronicle_chat_message', array($this, 'handle_chat_message'));
        add_action('wp_ajax_cronicle_create_post', array($this, 'create_draft_post'));
    }
    
    /**
     * Add main admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Cronicle', 'cronicle'),                    // Page title
            __('Cronicle', 'cronicle'),                    // Menu title
            'edit_posts',                                   // Capability required
            self::PAGE_SLUG,                               // Menu slug
            array($this, 'render_main_page'),              // Callback function
            'dashicons-format-chat',                       // Icon
            30                                             // Position
        );
        
        // Add submenu for settings
        add_submenu_page(
            self::PAGE_SLUG,                               // Parent slug
            __('Cronicle Settings', 'cronicle'),           // Page title
            __('Settings', 'cronicle'),                    // Menu title
            'manage_options',                              // Capability required
            'cronicle-settings',                           // Menu slug (matches existing settings page)
            array($this, 'redirect_to_settings')          // Callback function
        );
    }
    
    /**
     * Redirect to existing settings page
     */
    public function redirect_to_settings() {
        wp_redirect(admin_url('options-general.php?page=cronicle-settings'));
        exit;
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our main page
        if ('toplevel_page_' . self::PAGE_SLUG !== $hook) {
            return;
        }
        
        // Enqueue scripts
        wp_enqueue_script('jquery');
        
        // Add chat interface styles and scripts
        wp_add_inline_style('wp-admin', $this->get_chat_styles());
        wp_add_inline_script('jquery', $this->get_chat_script());
        
        // Localize script for AJAX
        wp_localize_script('jquery', 'cronicle_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cronicle_chat_nonce'),
            'strings' => array(
                'sending' => __('Sending...', 'cronicle'),
                'error' => __('Error sending message. Please try again.', 'cronicle'),
                'api_not_configured' => __('API not configured. Please add your Anthropic API key in Settings.', 'cronicle'),
                'creating_post' => __('Creating Draft...', 'cronicle'),
                'post_created' => __('✓ Draft Created', 'cronicle'),
                'create_post' => __('Create Draft Post', 'cronicle'),
            )
        ));
    }
    
    /**
     * Get chat interface styles
     */
    private function get_chat_styles() {
        return '
            .cronicle-container {
                max-width: 1200px;
                margin: 20px auto;
                background: #fff;
                border: 1px solid #c3c4c7;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .cronicle-header {
                background: #f6f7f7;
                border-bottom: 1px solid #c3c4c7;
                padding: 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .cronicle-header h1 {
                margin: 0;
                font-size: 23px;
                color: #23282d;
            }
            .cronicle-status {
                padding: 5px 12px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .cronicle-status.connected {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .cronicle-status.disconnected {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            .cronicle-chat-container {
                height: 600px;
                display: flex;
                flex-direction: column;
            }
            .cronicle-messages {
                flex: 1;
                overflow-y: auto;
                padding: 20px;
                background: #fafafa;
            }
            .cronicle-message {
                margin-bottom: 20px;
                max-width: 80%;
                clear: both;
            }
            .cronicle-message.user {
                float: right;
                text-align: right;
            }
            .cronicle-message.assistant {
                float: left;
                text-align: left;
            }
            .cronicle-message-content {
                display: inline-block;
                padding: 12px 16px;
                border-radius: 18px;
                word-wrap: break-word;
                white-space: pre-wrap;
            }
            .cronicle-message.user .cronicle-message-content {
                background: #0073aa;
                color: white;
            }
            .cronicle-message.assistant .cronicle-message-content {
                background: #fff;
                color: #23282d;
                border: 1px solid #c3c4c7;
            }
            .cronicle-input-area {
                border-top: 1px solid #c3c4c7;
                padding: 20px;
                background: #fff;
            }
            .cronicle-input-form {
                display: flex;
                gap: 10px;
            }
            .cronicle-input {
                flex: 1;
                padding: 12px;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                font-size: 14px;
                resize: vertical;
                min-height: 44px;
                max-height: 200px;
            }
            .cronicle-send-button {
                padding: 12px 24px;
                background: #0073aa;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                transition: background-color 0.2s;
            }
            .cronicle-send-button:hover:not(:disabled) {
                background: #005a87;
            }
            .cronicle-send-button:disabled {
                background: #c3c4c7;
                cursor: not-allowed;
            }
            .cronicle-setup-notice {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
                padding: 20px;
                margin: 20px;
                border-radius: 4px;
                text-align: center;
            }
            .cronicle-setup-notice a {
                color: #856404;
                text-decoration: underline;
                font-weight: 600;
            }
            .cronicle-typing-indicator {
                display: none;
                color: #666;
                font-style: italic;
                padding: 10px 0;
            }
            .cronicle-create-post-btn {
                margin-top: 12px;
                padding: 8px 16px;
                background: #00a32a;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 13px;
                font-weight: 600;
                transition: background-color 0.2s;
            }
            .cronicle-create-post-btn:hover:not(:disabled) {
                background: #008a20;
            }
            .cronicle-create-post-btn:disabled {
                background: #c3c4c7;
                cursor: not-allowed;
            }
            .cronicle-post-actions {
                margin-top: 12px;
                padding-top: 8px;
                border-top: 1px solid #e0e0e0;
            }
            @media (max-width: 782px) {
                .cronicle-container {
                    margin: 10px;
                }
                .cronicle-message {
                    max-width: 95%;
                }
                .cronicle-input-form {
                    flex-direction: column;
                }
            }
        ';
    }
    
    /**
     * Get chat interface JavaScript
     */
    private function get_chat_script() {
        return '
            jQuery(document).ready(function($) {
                var $form = $(".cronicle-input-form");
                var $input = $(".cronicle-input");
                var $button = $(".cronicle-send-button");
                var $messages = $(".cronicle-messages");
                var $typing = $(".cronicle-typing-indicator");
                
                // Handle form submission
                $form.on("submit", function(e) {
                    e.preventDefault();
                    
                    var message = $input.val().trim();
                    if (!message) return;
                    
                    // Add user message to chat
                    addMessage("user", message);
                    
                    // Clear input and disable button
                    $input.val("");
                    $button.prop("disabled", true).text(cronicle_ajax.strings.sending);
                    $typing.show();
                    
                    // Send AJAX request
                    $.ajax({
                        url: cronicle_ajax.ajax_url,
                        type: "POST",
                        data: {
                            action: "cronicle_chat_message",
                            message: message,
                            nonce: cronicle_ajax.nonce
                        },
                        success: function(response) {
                            if (response.success && response.data.content) {
                                addMessage("assistant", response.data.content, response.data);
                            } else {
                                var errorMsg = response.data && response.data.message 
                                    ? response.data.message 
                                    : cronicle_ajax.strings.error;
                                addMessage("assistant", "Error: " + errorMsg);
                            }
                        },
                        error: function() {
                            addMessage("assistant", "Error: " + cronicle_ajax.strings.error);
                        },
                        complete: function() {
                            $button.prop("disabled", false).text("Send");
                            $typing.hide();
                            $input.focus();
                        }
                    });
                });
                
                // Handle Enter key (Shift+Enter for new line)
                $input.on("keydown", function(e) {
                    if (e.key === "Enter" && !e.shiftKey) {
                        e.preventDefault();
                        $form.submit();
                    }
                });
                
                // Auto-resize textarea
                $input.on("input", function() {
                    this.style.height = "auto";
                    this.style.height = Math.min(this.scrollHeight, 200) + "px";
                });
                
                // Add message to chat
                function addMessage(type, content, data) {
                    data = data || {};
                    
                    var $message = $("<div>").addClass("cronicle-message").addClass(type);
                    var $content = $("<div>").addClass("cronicle-message-content").text(content);
                    
                    // Add post creation button if this is post content
                    if (data.is_post_content && data.post_data) {
                        var $actions = $("<div>").addClass("cronicle-post-actions");
                        var $button = $("<button>")
                            .addClass("cronicle-create-post-btn")
                            .text("Create Draft Post")
                            .data("post-data", data.post_data);
                        
                        $actions.append($button);
                        $content.append($actions);
                    }
                    
                    $message.append($content);
                    $messages.append($message);
                    
                    // Clear float
                    $messages.append($("<div>").css("clear", "both"));
                    
                    // Scroll to bottom
                    $messages.scrollTop($messages[0].scrollHeight);
                }
                
                // Handle post creation button clicks
                $(document).on("click", ".cronicle-create-post-btn", function() {
                    var $btn = $(this);
                    var postData = $btn.data("post-data");
                    
                    if (!postData || !postData.title || !postData.content) {
                        alert("Error: Invalid post data");
                        return;
                    }
                    
                    $btn.prop("disabled", true).text("Creating Draft...");
                    
                    $.ajax({
                        url: cronicle_ajax.ajax_url,
                        type: "POST",
                        data: {
                            action: "cronicle_create_post",
                            title: postData.title,
                            content: postData.content,
                            nonce: cronicle_ajax.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                $btn.text("✓ Draft Created").removeClass("cronicle-create-post-btn").addClass("button-secondary");
                                
                                // Add success message
                                setTimeout(function() {
                                    addMessage("assistant", response.data.message + " Click here to edit it.");
                                    
                                    // Add edit button
                                    var $editBtn = $("<button>")
                                        .addClass("button button-primary")
                                        .text("Edit Post")
                                        .css("margin-top", "8px")
                                        .on("click", function() {
                                            window.open(response.data.edit_url, "_blank");
                                        });
                                    
                                    $messages.find(".cronicle-message:last .cronicle-message-content").append($("<br>")).append($editBtn);
                                }, 1000);
                            } else {
                                alert("Error creating post: " + (response.data?.message || "Unknown error"));
                                $btn.prop("disabled", false).text("Create Draft Post");
                            }
                        },
                        error: function() {
                            alert("Error creating post. Please try again.");
                            $btn.prop("disabled", false).text("Create Draft Post");
                        }
                    });
                });
                
                // Focus input on load
                $input.focus();
            });
        ';
    }
    
    /**
     * Handle AJAX chat message
     */
    public function handle_chat_message() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cronicle_chat_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to use this feature.', 'cronicle')));
        }
        
        $message = sanitize_textarea_field($_POST['message']);
        if (empty($message)) {
            wp_send_json_error(array('message' => __('Message cannot be empty.', 'cronicle')));
        }
        
        // Get API client
        $api_client = cronicle_api_client();
        
        if (!$api_client->is_api_ready()) {
            wp_send_json_error(array(
                'message' => __('API not configured. Please add your Anthropic API key in Settings.', 'cronicle'),
                'redirect_to_settings' => true
            ));
        }
        
        // Create structured prompt for blog post generation
        $structured_prompt = $this->build_post_generation_prompt($message);
        
        // Generate response using Claude
        $response = $api_client->generate_content($structured_prompt, array(
            'model' => 'claude-3-7-sonnet-20250219',
            'max_tokens' => 4000,
            'temperature' => 0.7,
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }
        
        if (isset($response['content'][0]['text'])) {
            $ai_response = $response['content'][0]['text'];
            $parsed_response = $this->parse_ai_response($ai_response);
            
            if ($parsed_response) {
                wp_send_json_success($parsed_response);
            } else {
                // Fallback if JSON parsing fails
                wp_send_json_success(array(
                    'content' => $ai_response,
                    'is_post_content' => false
                ));
            }
        } else {
            wp_send_json_error(array('message' => __('Unexpected response format from API.', 'cronicle')));
        }
    }
    
    /**
     * Build structured prompt for post generation
     */
    private function build_post_generation_prompt($topic) {
        return 'You are an expert blogger. Draft a new blog post about: "' . $topic . '"

Respond with valid JSON in this exact format:

{
    "chat_response": "A friendly message about the post you created (e.g., \'I\'ve created a new post titled \"Benefits of Morning Exercise\" with 487 words. The post covers key health benefits and practical tips to get started.\')",
    "post_title": "An engaging, SEO-friendly title for the post",
    "post_content": "The complete blog post content in WordPress block syntax. Structure it with proper headings (h2, h3), paragraphs, and lists as appropriate. Make it informative, engaging, and well-organized.",
    "word_count": 500
}

Requirements:
- The post should be 400-600 words
- Format all post content using WordPress block HTML (e.g., <!-- wp:paragraph -->)
- Make it engaging and informative
- Include practical tips or actionable advice where relevant
- Ensure the content is original and valuable to readers
- The chat_response should be conversational and mention the post title and word count

Respond ONLY with the JSON, no additional text before or after.';
    }
    
    /**
     * Parse AI response and extract structured data
     */
    private function parse_ai_response($response) {
        // Clean up the response - remove any markdown code blocks
        $response = preg_replace('/^```json\s*/', '', $response);
        $response = preg_replace('/\s*```$/', '', $response);
        $response = trim($response);
        
        $parsed = json_decode($response, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($parsed['chat_response']) && isset($parsed['post_content'])) {
            return array(
                'content' => $parsed['chat_response'],
                'is_post_content' => true,
                'post_data' => array(
                    'title' => isset($parsed['post_title']) ? $parsed['post_title'] : 'Untitled Post',
                    'content' => $parsed['post_content'],
                    'word_count' => isset($parsed['word_count']) ? $parsed['word_count'] : null
                )
            );
        }
        
        return false;
    }
    
    /**
     * Create draft post from AI-generated content
     */
    public function create_draft_post() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cronicle_chat_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to create posts.', 'cronicle')));
        }
        
        $post_title = sanitize_text_field($_POST['title']);
        $post_content = wp_kses_post($_POST['content']);
        
        if (empty($post_title) || empty($post_content)) {
            wp_send_json_error(array('message' => __('Post title and content are required.', 'cronicle')));
        }
        
        $post_data = array(
            'post_title' => $post_title,
            'post_content' => $post_content,
            'post_status' => 'draft',
            'post_author' => get_current_user_id(),
            'post_type' => 'post',
            'meta_input' => array(
                '_cronicle_generated' => true,
                '_cronicle_generated_date' => current_time('mysql')
            )
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => __('Failed to create draft post.', 'cronicle')));
        }
        
        wp_send_json_success(array(
            'post_id' => $post_id,
            'edit_url' => admin_url("post.php?post={$post_id}&action=edit"),
            'message' => __('Draft post created successfully!', 'cronicle')
        ));
    }
    
    /**
     * Render the main page
     */
    public function render_main_page() {
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'cronicle'));
        }
        
        $api_client = cronicle_api_client();
        $is_api_configured = $api_client->is_api_ready();
        ?>
        <div class="wrap">
            <div class="cronicle-container">
                <div class="cronicle-header">
                    <h1><?php _e('Cronicle AI Assistant', 'cronicle'); ?></h1>
                    <span class="cronicle-status <?php echo $is_api_configured ? 'connected' : 'disconnected'; ?>">
                        <?php echo $is_api_configured ? __('Connected', 'cronicle') : __('Not Connected', 'cronicle'); ?>
                    </span>
                </div>
                
                <?php if (!$is_api_configured): ?>
                    <div class="cronicle-setup-notice">
                        <p>
                            <strong><?php _e('Welcome to Cronicle!', 'cronicle'); ?></strong><br>
                            <?php _e('To get started, you need to configure your Anthropic API key.', 'cronicle'); ?>
                        </p>
                        <p>
                            <a href="<?php echo admin_url('options-general.php?page=cronicle-settings'); ?>" class="button button-primary">
                                <?php _e('Configure API Key', 'cronicle'); ?>
                            </a>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="cronicle-chat-container">
                        <div class="cronicle-messages">
                            <div class="cronicle-message assistant">
                                <div class="cronicle-message-content">
                                    <?php _e('Hello! I\'m ready to help you write blog posts. Just tell me what topic you\'d like to write about, and I\'ll create a draft post for your WordPress site. What would you like to write about today?', 'cronicle'); ?>
                                </div>
                            </div>
                            <div style="clear: both;"></div>
                        </div>
                        
                        <div class="cronicle-typing-indicator">
                            <?php _e('Assistant is typing...', 'cronicle'); ?>
                        </div>
                        
                        <div class="cronicle-input-area">
                            <form class="cronicle-input-form">
                                <textarea 
                                    class="cronicle-input" 
                                    placeholder="<?php esc_attr_e('What would you like to write about? (e.g., benefits of exercise, cooking tips, travel destinations)', 'cronicle'); ?>"
                                    rows="1"
                                ></textarea>
                                <button type="submit" class="cronicle-send-button">
                                    <?php _e('Send', 'cronicle'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
} 