/**
 * Cronicle Admin JavaScript
 * 
 * Main JavaScript functionality for the Cronicle admin interface
 */

jQuery(document).ready(function($) {
    var $form = $(".cronicle-input-form");
    var $input = $(".cronicle-input");
    var $button = $(".cronicle-send-button");
    var $messages = $(".cronicle-messages");
    var $welcomeMessage = $(".cronicle-welcome-message");
    var $typing = $(".cronicle-typing-indicator");
    var $modeSelect = $(".cronicle-mode-select");
    var $previewContainer = $(".cronicle-preview-container");
    var $previewContent = $(".cronicle-preview-content");
    var $previewActions = $(".cronicle-preview-actions");
    var currentDraft = null;
    
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

        var ajaxData;
        if (currentDraft) {
            ajaxData = {
                action: "cronicle_revise_draft",
                title: currentDraft.title,
                content: currentDraft.content,
                instructions: message,
                nonce: cronicle_ajax.nonce
            };
        } else {
            var selectedMode = $modeSelect.val() || "draft";
            ajaxData = {
                action: "cronicle_chat_message",
                message: message,
                mode: selectedMode,
                nonce: cronicle_ajax.nonce
            };
        }

        $.ajax({
            url: cronicle_ajax.ajax_url,
            type: "POST",
            data: ajaxData,
            success: function(response) {
                if (response.success && response.data.content) {
                    addMessage("assistant", response.data.content, response.data);
                    if (response.data.is_post_content && response.data.post_data) {
                        currentDraft = response.data.post_data;
                        showPreview(currentDraft);
                    }
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

        // Hide welcome message on first interaction
        if ($welcomeMessage.length) {
            $welcomeMessage.hide();
        }
        
        var $message = $("<div>").addClass("cronicle-message").addClass(type);
        var $content = $("<div>").addClass("cronicle-message-content").text(content);
        
        // Add post creation button and preview if this is post content
        if (data.is_post_content && data.post_data) {
            currentDraft = data.post_data;
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

        currentDraft = postData;
        
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
            var $closeBtn = $("<button>")
                .addClass("cronicle-preview-close-btn")
                .text("Close Preview");

            $previewActions
                .empty()
                .append($createBtn)
                .append($closeBtn);

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

                    currentDraft = null;
                    $previewContainer.removeClass("active");
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