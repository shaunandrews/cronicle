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
                'creating_outline' => __('Creating Outline...', 'cronicle'),
                'outline_created' => __('✓ Outline Created', 'cronicle'),
                'create_outline' => __('Create Outline Draft', 'cronicle'),
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
                flex-wrap: wrap;
                gap: 15px;
            }
            .cronicle-header h1 {
                margin: 0;
                font-size: 23px;
                color: #23282d;
            }
            .cronicle-header-right {
                display: flex;
                align-items: center;
                gap: 15px;
            }
            .cronicle-mode-selector {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .cronicle-mode-selector label {
                font-size: 14px;
                font-weight: 600;
                color: #23282d;
                margin: 0;
            }
            .cronicle-mode-select {
                padding: 6px 12px;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                background: #fff;
                font-size: 14px;
                color: #23282d;
                cursor: pointer;
                min-width: 120px;
            }
            .cronicle-mode-select:focus {
                border-color: #0073aa;
                outline: none;
                box-shadow: 0 0 0 1px #0073aa;
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
            .cronicle-main-content {
                display: flex;
                min-height: 600px;
            }
            .cronicle-chat-container {
                flex: 1;
                display: flex;
                flex-direction: column;
                min-height: 600px;
            }
            .cronicle-preview-container {
                width: 400px;
                border-left: 1px solid #c3c4c7;
                background: #fff;
                display: none;
                flex-direction: column;
            }
            .cronicle-preview-container.active {
                display: flex;
            }
            .cronicle-preview-header {
                background: #f6f7f7;
                border-bottom: 1px solid #c3c4c7;
                padding: 15px 20px;
                font-weight: 600;
                font-size: 14px;
                color: #23282d;
            }
            .cronicle-preview-content {
                flex: 1;
                padding: 20px;
                overflow-y: auto;
                background: #fff;
            }
            .cronicle-preview-post {
                max-width: none;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                line-height: 1.6;
                color: #23282d;
            }
            .cronicle-preview-post h1 {
                margin: 0 0 20px 0;
                font-size: 28px;
                font-weight: 600;
                line-height: 1.2;
                color: #1d2327;
                border-bottom: 1px solid #e0e0e0;
                padding-bottom: 10px;
            }
            .cronicle-preview-post h2 {
                margin: 25px 0 15px 0;
                font-size: 22px;
                font-weight: 600;
                color: #1d2327;
            }
            .cronicle-preview-post h3 {
                margin: 20px 0 12px 0;
                font-size: 18px;
                font-weight: 600;
                color: #1d2327;
            }
            .cronicle-preview-post p {
                margin: 0 0 16px 0;
                font-size: 16px;
                line-height: 1.6;
            }
            .cronicle-preview-post ul, .cronicle-preview-post ol {
                margin: 0 0 16px 20px;
                padding-left: 20px;
            }
            .cronicle-preview-post li {
                margin-bottom: 8px;
            }
            .cronicle-preview-actions {
                border-top: 1px solid #c3c4c7;
                padding: 15px 20px;
                background: #f6f7f7;
                display: flex;
                gap: 10px;
                align-items: center;
            }
            .cronicle-preview-create-btn {
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
            .cronicle-preview-create-btn:hover:not(:disabled) {
                background: #008a20;
            }
            .cronicle-preview-create-btn:disabled {
                background: #c3c4c7;
                cursor: not-allowed;
            }
            .cronicle-preview-close-btn {
                padding: 8px 16px;
                background: transparent;
                color: #646970;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                cursor: pointer;
                font-size: 13px;
                transition: all 0.2s;
            }
            .cronicle-preview-close-btn:hover {
                background: #f6f7f7;
                border-color: #949494;
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
                max-height: 300px;
                overflow-y: auto;
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
            @media (max-width: 1024px) {
                .cronicle-main-content {
                    flex-direction: column;
                }
                .cronicle-preview-container {
                    width: 100%;
                    border-left: none;
                    border-top: 1px solid #c3c4c7;
                    max-height: 400px;
                }
                .cronicle-chat-container {
                    min-height: 400px;
                }
            }
            @media (max-width: 782px) {
                .cronicle-container {
                    margin: 10px;
                }
                .cronicle-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 10px;
                }
                .cronicle-header-right {
                    width: 100%;
                    flex-direction: column;
                    align-items: stretch;
                    gap: 10px;
                }
                .cronicle-mode-selector {
                    justify-content: space-between;
                }
                .cronicle-mode-select {
                    min-width: auto;
                    flex: 1;
                    max-width: 200px;
                }
                .cronicle-status {
                    align-self: flex-end;
                }
                .cronicle-message {
                    max-width: 95%;
                }
                .cronicle-input-form {
                    flex-direction: column;
                }
                .cronicle-preview-container {
                    max-height: 300px;
                }
                .cronicle-preview-actions {
                    flex-direction: column;
                    align-items: stretch;
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
                var $modeSelect = $(".cronicle-mode-select");
                var $previewContainer = $(".cronicle-preview-container");
                var $previewContent = $(".cronicle-preview-content");
                var $previewActions = $(".cronicle-preview-actions");
                
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
                    
                    // Get selected mode
                    var selectedMode = $modeSelect.val() || "draft";
                    
                    // Send AJAX request
                    $.ajax({
                        url: cronicle_ajax.ajax_url,
                        type: "POST",
                        data: {
                            action: "cronicle_chat_message",
                            message: message,
                            mode: selectedMode,
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
                
                var maxHeight = 300;
                function resizeInput() {
                    $input.css("height", "auto");

                    var scrollHeight = $input[0].scrollHeight;

                    if (!$input.val()) {
                        var $tmp = $("<div>")
                            .css({
                                position: "absolute",
                                visibility: "hidden",
                                padding: $input.css("padding"),
                                width: $input.outerWidth(),
                                'font-family': $input.css('font-family'),
                                'font-size': $input.css('font-size'),
                                'line-height': $input.css('line-height'),
                                'white-space': 'pre-wrap',
                                'word-wrap': 'break-word'
                            })
                            .text($input.attr("placeholder"));

                        $("body").append($tmp);
                        scrollHeight = $tmp[0].scrollHeight;
                        $tmp.remove();
                    }

                    var newHeight = Math.min(scrollHeight, maxHeight);
                    $input.css({
                        height: newHeight + "px",
                        'overflow-y': scrollHeight > maxHeight ? 'auto' : 'hidden'
                    });
                }

                $input.on("input", resizeInput);
                
                // Add message to chat
                function addMessage(type, content, data) {
                    data = data || {};
                    
                    var $message = $("<div>").addClass("cronicle-message").addClass(type);
                    var $content = $("<div>").addClass("cronicle-message-content").text(content);
                    
                    // Add post creation button and preview if this is post content
                    if (data.is_post_content && data.post_data) {
                        var $actions = $("<div>").addClass("cronicle-post-actions");
                        var buttonText = data.post_data.is_outline ? "Create Outline Draft" : "Create Draft Post";
                        var $createButton = $("<button>")
                            .addClass("cronicle-create-post-btn")
                            .text(buttonText)
                            .data("post-data", data.post_data);
                        
                        var $previewButton = $("<button>")
                            .addClass("button button-secondary")
                            .text("Preview")
                            .css({"margin-left": "8px", "padding": "8px 16px", "font-size": "13px"})
                            .data("post-data", data.post_data)
                            .on("click", function() {
                                showPreview($(this).data("post-data"));
                            });
                        
                        $actions.append($createButton).append($previewButton);
                        $content.append($actions);
                        
                        // Auto-show preview for new post content
                        setTimeout(function() {
                            showPreview(data.post_data);
                        }, 500);
                    }
                    
                    $message.append($content);
                    $messages.append($message);
                    
                    // Clear float
                    $messages.append($("<div>").css("clear", "both"));
                    
                    // Scroll to bottom
                    $messages.scrollTop($messages[0].scrollHeight);
                }
                
                // Show preview of post content
                function showPreview(postData) {
                    if (!postData || !postData.title || !postData.content) {
                        return;
                    }
                    
                    // Convert WordPress blocks to HTML for preview
                    var previewHTML = convertBlocksToHTML(postData.content);
                    var previewTitle = postData.title;
                    var isOutline = postData.is_outline;
                    
                    // Build preview HTML
                    var $previewPost = $("<div>").addClass("cronicle-preview-post");
                    $previewPost.append($("<h1>").text(previewTitle));
                    $previewPost.append(previewHTML);
                    
                    // Update preview container
                    $previewContent.empty().append($previewPost);
                    
                    // Update preview header
                    var headerText = isOutline ? "Post Outline Preview" : "Post Preview";
                    $previewContainer.find(".cronicle-preview-header").text(headerText);
                    
                    // Update action buttons
                    var createButtonText = isOutline ? "Create Outline Draft" : "Create Draft Post";
                    var $createBtn = $previewActions.find(".cronicle-preview-create-btn");
                    if ($createBtn.length === 0) {
                        $createBtn = $("<button>").addClass("cronicle-preview-create-btn");
                        var $closeBtn = $("<button>").addClass("cronicle-preview-close-btn").text("Close Preview");
                        
                        $previewActions.empty().append($createBtn).append($closeBtn);
                        
                        // Handle close button
                        $closeBtn.on("click", function() {
                            $previewContainer.removeClass("active");
                        });
                    }
                    
                    $createBtn.text(createButtonText).data("post-data", postData);
                    
                    // Show preview container
                    $previewContainer.addClass("active");
                }
                
                // Convert WordPress blocks to HTML for preview
                function convertBlocksToHTML(content) {
                    var $temp = $("<div>").html(content);
                    var result = "";
                    
                    $temp.find("*").each(function() {
                        var $el = $(this);
                        var tagName = this.tagName.toLowerCase();
                        var text = $el.text().trim();
                        
                        if (!text) return;
                        
                        switch(tagName) {
                            case "h1":
                            case "h2":
                            case "h3":
                            case "h4":
                            case "h5":
                            case "h6":
                                result += "<" + tagName + ">" + text + "</" + tagName + ">";
                                break;
                            case "p":
                                result += "<p>" + text + "</p>";
                                break;
                            case "ul":
                                result += "<ul>";
                                $el.find("li").each(function() {
                                    result += "<li>" + $(this).text() + "</li>";
                                });
                                result += "</ul>";
                                break;
                            case "ol":
                                result += "<ol>";
                                $el.find("li").each(function() {
                                    result += "<li>" + $(this).text() + "</li>";
                                });
                                result += "</ol>";
                                break;
                        }
                    });
                    
                    // If no structured content found, treat as plain text with line breaks
                    if (!result) {
                        result = content.replace(/\n/g, "<br>");
                    }
                    
                    return result;
                }
                
                // Handle post creation button clicks (both in chat and preview)
                $(document).on("click", ".cronicle-create-post-btn, .cronicle-preview-create-btn", function() {
                    var $btn = $(this);
                    var postData = $btn.data("post-data");
                    
                    if (!postData || !postData.title || !postData.content) {
                        alert("Error: Invalid post data");
                        return;
                    }
                    
                    var creatingText = postData.is_outline ? cronicle_ajax.strings.creating_outline : cronicle_ajax.strings.creating_post;
                    $btn.prop("disabled", true).text(creatingText);
                    
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
                                var createdText = postData.is_outline ? cronicle_ajax.strings.outline_created : cronicle_ajax.strings.post_created;
                                $btn.text(createdText).removeClass("cronicle-create-post-btn").addClass("button-secondary");
                                
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
                                var originalText = postData.is_outline ? cronicle_ajax.strings.create_outline : cronicle_ajax.strings.create_post;
                                $btn.prop("disabled", false).text(originalText);
                            }
                        },
                        error: function() {
                            alert("Error creating post. Please try again.");
                            var originalText = postData.is_outline ? cronicle_ajax.strings.create_outline : cronicle_ajax.strings.create_post;
                            $btn.prop("disabled", false).text(originalText);
                        }
                    });
                });
                
                // Handle mode selector change
                $modeSelect.on("change", function() {
                    var mode = $(this).val();
                    if (mode === "outline") {
                        $input.attr("placeholder", "What topic would you like an outline for? (e.g., benefits of exercise, cooking tips, travel destinations)");
                    } else {
                        $input.attr("placeholder", "What would you like to write about? (e.g., benefits of exercise, cooking tips, travel destinations)");
                    }
                    resizeInput();
                });

                // Focus input on load
                resizeInput();
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
        $mode = sanitize_text_field($_POST['mode']) ?: 'draft';
        
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
        
        // Gather site and user context
        $site_context = $this->get_site_context();
        $user_context = $this->get_user_context();
        
        // Create structured prompt for blog post generation
        $structured_prompt = $this->build_post_generation_prompt($message, $mode, $site_context, $user_context);
        
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
     * Get site context information
     */
    private function get_site_context() {
        $site_context = array(
            'title' => get_bloginfo('name'),
            'tagline' => get_bloginfo('description'),
            'url' => get_site_url(),
            'admin_email' => get_option('admin_email'),
            'language' => get_locale(),
            'timezone' => get_option('timezone_string') ?: 'UTC'
        );
        
        // Get site categories for additional context
        $categories = get_categories(array(
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => 5,
            'hide_empty' => true
        ));
        
        if (!empty($categories)) {
            $site_context['popular_categories'] = array_map(function($cat) {
                return $cat->name;
            }, $categories);
        }
        
        // Get recent post titles for context about content style
        $recent_posts = get_posts(array(
            'numberposts' => 5,
            'post_status' => 'publish',
            'fields' => 'ids'
        ));
        
        if (!empty($recent_posts)) {
            $site_context['recent_post_titles'] = array_map('get_the_title', $recent_posts);
        }
        
        return $site_context;
    }
    
    /**
     * Get current user context information
     */
    private function get_user_context() {
        $current_user = wp_get_current_user();
        
        $user_context = array(
            'display_name' => $current_user->display_name,
            'user_login' => $current_user->user_login,
            'user_email' => $current_user->user_email,
            'roles' => $current_user->roles
        );
        
        // Get user meta information
        $user_meta_fields = array(
            'first_name' => get_user_meta($current_user->ID, 'first_name', true),
            'last_name' => get_user_meta($current_user->ID, 'last_name', true),
            'description' => get_user_meta($current_user->ID, 'description', true),
            'user_url' => $current_user->user_url
        );
        
        // Only include non-empty meta fields
        foreach ($user_meta_fields as $key => $value) {
            if (!empty($value)) {
                $user_context[$key] = $value;
            }
        }
        
        // Get user's post count and recent activity
        $user_context['post_count'] = count_user_posts($current_user->ID, 'post');
        
        return $user_context;
    }
    
    /**
     * Build context section for AI prompt
     */
    private function build_context_section($site_context, $user_context) {
        $context_parts = array();
        
        if (!empty($site_context)) {
            $context_parts[] = "SITE CONTEXT:";
            $context_parts[] = "- Site Name: " . $site_context['title'];
            
            if (!empty($site_context['tagline'])) {
                $context_parts[] = "- Site Tagline: " . $site_context['tagline'];
            }
            
            if (!empty($site_context['popular_categories'])) {
                $context_parts[] = "- Popular Categories: " . implode(', ', $site_context['popular_categories']);
            }
            
            if (!empty($site_context['recent_post_titles'])) {
                $context_parts[] = "- Recent Post Titles: " . implode(', ', array_slice($site_context['recent_post_titles'], 0, 3));
            }
        }
        
        if (!empty($user_context)) {
            if (!empty($context_parts)) {
                $context_parts[] = "";
            }
            
            $context_parts[] = "AUTHOR CONTEXT:";
            $context_parts[] = "- Author: " . $user_context['display_name'];
            
            if (!empty($user_context['description'])) {
                $context_parts[] = "- Bio: " . wp_trim_words($user_context['description'], 30);
            }
            
            if (!empty($user_context['post_count'])) {
                $context_parts[] = "- Published Posts: " . $user_context['post_count'];
            }
            
            if (!empty($user_context['user_url'])) {
                $context_parts[] = "- Website: " . $user_context['user_url'];
            }
        }
        
        return !empty($context_parts) ? implode("\n", $context_parts) : '';
    }
    
    /**
     * Build structured prompt for post generation
     */
    private function build_post_generation_prompt($topic, $mode = 'draft', $site_context = null, $user_context = null) {
        $context_info = $this->build_context_section($site_context, $user_context);
        
        if ($mode === 'outline') {
            return 'You are an expert blogger. Create a detailed outline for a blog post about: "' . $topic . '"

' . $context_info . '

Respond with valid JSON in this exact format:

{
    "chat_response": "A friendly message about the outline you created (e.g., \'I\'ve created a detailed outline for \"Benefits of Morning Exercise\" with 6 main sections and key talking points. This gives you a solid structure to build your post around.\')",
    "post_title": "An engaging, SEO-friendly title for the post",
    "post_content": "A structured outline in WordPress block syntax with headings and brief bullet points. Use h2 for main sections, h3 for subsections, and bullet lists for key points. Keep it concise but comprehensive.",
    "word_count": 150
}

Requirements:
- Create a detailed outline with 4-6 main sections
- Include brief bullet points under each section (2-4 points per section)
- Format using WordPress block HTML (e.g., <!-- wp:heading -->)
- Focus on structure and key talking points, not full content
- Make it actionable and logical flow
- The chat_response should mention it\'s an outline and how many sections
- Consider the site context and user information to make the content relevant and appropriate
- If the site has specific categories or themes, try to align the content accordingly

Respond ONLY with the JSON, no additional text before or after.';
        }
        
        // Default draft mode
        return 'You are an expert blogger. Draft a new blog post about: "' . $topic . '"

' . $context_info . '

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
- Consider the site context and user information to make the content relevant and appropriate
- If the site has specific categories or themes, try to align the content accordingly
- Use a tone and style that matches the site\'s existing content if possible

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
            // Determine if this is an outline based on word count (outlines are typically shorter)
            $is_outline = isset($parsed['word_count']) && $parsed['word_count'] < 300;
            
            return array(
                'content' => $parsed['chat_response'],
                'is_post_content' => true,
                'post_data' => array(
                    'title' => isset($parsed['post_title']) ? $parsed['post_title'] : 'Untitled Post',
                    'content' => $parsed['post_content'],
                    'word_count' => isset($parsed['word_count']) ? $parsed['word_count'] : null,
                    'is_outline' => $is_outline
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
                    <div class="cronicle-header-right">
                        <?php if ($is_api_configured): ?>
                        <div class="cronicle-mode-selector">
                            <label for="cronicle-mode-select"><?php _e('Mode:', 'cronicle'); ?></label>
                            <select id="cronicle-mode-select" class="cronicle-mode-select">
                                <option value="draft"><?php _e('Full Draft', 'cronicle'); ?></option>
                                <option value="outline"><?php _e('Outline', 'cronicle'); ?></option>
                            </select>
                        </div>
                        <?php endif; ?>
                        <span class="cronicle-status <?php echo $is_api_configured ? 'connected' : 'disconnected'; ?>">
                            <?php echo $is_api_configured ? __('Connected', 'cronicle') : __('Not Connected', 'cronicle'); ?>
                        </span>
                    </div>
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
                    <div class="cronicle-main-content">
                        <div class="cronicle-chat-container">
                            <div class="cronicle-messages">
                                <div class="cronicle-message assistant">
                                    <div class="cronicle-message-content">
                                        <?php _e('Hello! I\'m ready to help you with your blog posts. Choose "Full Draft" mode for a complete ready-to-publish post, or "Outline" mode for a structured outline that you can expand yourself. Just tell me what topic you\'d like to work on!', 'cronicle'); ?>
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
                        
                        <div class="cronicle-preview-container">
                            <div class="cronicle-preview-header">
                                <?php _e('Post Preview', 'cronicle'); ?>
                            </div>
                            <div class="cronicle-preview-content">
                                <!-- Preview content will be populated by JavaScript -->
                            </div>
                            <div class="cronicle-preview-actions">
                                <!-- Preview actions will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
} 