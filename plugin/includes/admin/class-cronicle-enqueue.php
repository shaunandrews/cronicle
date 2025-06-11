<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cronicle Enqueue Class
 * 
 * Handles enqueueing of admin scripts and styles
 */
class Cronicle_Enqueue {
    
    /**
     * Main page slug
     */
    const PAGE_SLUG = 'cronicle';
    
    /**
     * Register hooks
     */
    public function register_hooks() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
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
                });
                
                // Focus input on load
                $input.focus();
            });
        ';
    }
} 