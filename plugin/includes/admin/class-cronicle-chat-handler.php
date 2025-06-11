<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cronicle Chat Handler Class
 * 
 * Handles AJAX chat messages and post creation
 */
class Cronicle_Chat_Handler {
    
    /**
     * Register hooks
     */
    public function register_hooks() {
        add_action('wp_ajax_cronicle_chat_message', array($this, 'handle_chat_message'));
        add_action('wp_ajax_cronicle_create_post', array($this, 'create_draft_post'));
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
            'model' => 'claude-3-5-sonnet-20241022',
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
} 