<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Context Provider
 * 
 * Provides current user information, writing history, and preferences
 */
class Cronicle_User_Context_Provider extends Cronicle_Context_Provider_Base {
    
    /**
     * Get provider name
     * 
     * @return string Provider name
     */
    public function get_name() {
        return __('Author Information', 'cronicle');
    }
    
    /**
     * Get provider description
     * 
     * @return string Provider description
     */
    public function get_description() {
        return __('Provides current user profile information, writing history, and preferences to personalize AI responses.', 'cronicle');
    }
    
    /**
     * Get context data
     * 
     * @param array $options Context gathering options
     * @return array Context data
     */
    public function get_context($options = array()) {
        $user = wp_get_current_user();
        
        if (!$user || $user->ID === 0) {
            return array();
        }
        
        $cache_key = $this->generate_cache_key($options);
        $cached_context = $this->get_cached_context($cache_key);
        
        if ($cached_context !== false) {
            return $cached_context;
        }
        
        $context = array();
        
        // Basic user information
        $context['display_name'] = $user->display_name;
        $context['user_email'] = $user->user_email;
        $context['roles'] = $user->roles;
        
        // Extended user profile
        if (!isset($options['minimal']) || !$options['minimal']) {
            $context = array_merge($context, $this->get_extended_user_info($user, $options));
        }
        
        // Cache the result
        $this->set_cached_context($cache_key, $context);
        
        return $context;
    }
    
    /**
     * Get extended user information
     * 
     * @param WP_User $user User object
     * @param array $options Context options
     * @return array Extended context data
     */
    private function get_extended_user_info($user, $options) {
        $context = array();
        
        // Profile information
        $first_name = get_user_meta($user->ID, 'first_name', true);
        $last_name = get_user_meta($user->ID, 'last_name', true);
        
        if (!empty($first_name)) {
            $context['first_name'] = $first_name;
        }
        
        if (!empty($last_name)) {
            $context['last_name'] = $last_name;
        }
        
        // Bio/description
        $description = get_user_meta($user->ID, 'description', true);
        if (!empty($description)) {
            $context['bio'] = wp_trim_words($description, 50);
        }
        
        // Website
        if (!empty($user->user_url)) {
            $context['website'] = $user->user_url;
        }
        
        // Writing statistics
        $writing_stats = $this->get_user_writing_stats($user->ID);
        if (!empty($writing_stats)) {
            $context['writing_stats'] = $writing_stats;
        }
        
        // Recent posts for style analysis
        $recent_posts = $this->get_user_recent_posts($user->ID, $options);
        if (!empty($recent_posts)) {
            $context['recent_posts'] = $recent_posts;
        }
        
        // Content preferences
        $content_preferences = $this->get_user_content_preferences($user->ID);
        if (!empty($content_preferences)) {
            $context['content_preferences'] = $content_preferences;
        }
        
        // User's popular categories
        $popular_categories = $this->get_user_popular_categories($user->ID);
        if (!empty($popular_categories)) {
            $context['preferred_categories'] = $popular_categories;
        }
        
        // Cronicle-specific user preferences
        $cronicle_preferences = $this->get_cronicle_user_preferences($user->ID);
        if (!empty($cronicle_preferences)) {
            $context['cronicle_preferences'] = $cronicle_preferences;
        }
        
        return $context;
    }
    
    /**
     * Get user writing statistics
     * 
     * @param int $user_id User ID
     * @return array Writing statistics
     */
    private function get_user_writing_stats($user_id) {
        $stats = array();
        
        // Post counts
        $post_count = count_user_posts($user_id, 'post');
        $stats['total_posts'] = $post_count;
        
        // Page counts
        $page_count = count_user_posts($user_id, 'page');
        $stats['total_pages'] = $page_count;
        
        // Registration date for experience context
        $user = get_user_by('id', $user_id);
        if ($user) {
            $registered = strtotime($user->user_registered);
            $months_active = max(1, round((time() - $registered) / (30 * 24 * 60 * 60)));
            $stats['months_active'] = $months_active;
            $stats['posts_per_month'] = round($post_count / $months_active, 1);
        }
        
        // Cronicle-generated posts
        $cronicle_posts = get_posts(array(
            'author' => $user_id,
            'meta_key' => '_cronicle_generated',
            'meta_value' => true,
            'post_status' => array('publish', 'draft'),
            'numberposts' => -1,
            'fields' => 'ids'
        ));
        
        $stats['cronicle_generated_posts'] = count($cronicle_posts);
        
        return $stats;
    }
    
    /**
     * Get user's recent posts for style analysis
     * 
     * @param int $user_id User ID
     * @param array $options Context options
     * @return array Recent posts data
     */
    private function get_user_recent_posts($user_id, $options) {
        $max_posts = $options['max_user_posts'] ?? min($this->config['max_items'], 5);
        
        $posts = get_posts(array(
            'author' => $user_id,
            'numberposts' => $max_posts,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($posts)) {
            return array();
        }
        
        $posts_data = array();
        foreach ($posts as $post) {
            $posts_data[] = array(
                'title' => get_the_title($post),
                'excerpt' => wp_trim_words(get_the_excerpt($post), 15),
                'word_count' => str_word_count(strip_tags($post->post_content)),
                'categories' => wp_get_post_categories($post->ID, array('fields' => 'names')),
                'date' => get_the_date('Y-m-d', $post),
                'is_cronicle_generated' => get_post_meta($post->ID, '_cronicle_generated', true)
            );
        }
        
        return $posts_data;
    }
    
    /**
     * Get user content preferences
     * 
     * @param int $user_id User ID
     * @return array Content preferences
     */
    private function get_user_content_preferences($user_id) {
        $preferences = array();
        
        // Default post format
        $default_format = get_user_meta($user_id, 'default_post_format', true);
        if (!empty($default_format)) {
            $preferences['default_format'] = $default_format;
        }
        
        // Rich editing preference
        $rich_editing = get_user_meta($user_id, 'rich_editing', true);
        $preferences['rich_editing'] = ($rich_editing !== 'false');
        
        // Admin color scheme (might indicate style preferences)
        $admin_color = get_user_meta($user_id, 'admin_color', true);
        if (!empty($admin_color)) {
            $preferences['admin_color_scheme'] = $admin_color;
        }
        
        return $preferences;
    }
    
    /**
     * Get user's most popular categories
     * 
     * @param int $user_id User ID
     * @return array Popular categories
     */
    private function get_user_popular_categories($user_id) {
        global $wpdb;
        
        $query = "
            SELECT t.name, COUNT(*) as count
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE p.post_author = %d
            AND p.post_status = 'publish'
            AND p.post_type = 'post'
            AND tt.taxonomy = 'category'
            GROUP BY t.term_id
            ORDER BY count DESC
            LIMIT %d
        ";
        
        $max_categories = min($this->config['max_items'], 5);
        $results = $wpdb->get_results($wpdb->prepare($query, $user_id, $max_categories));
        
        if (empty($results)) {
            return array();
        }
        
        return array_map(function($result) {
            return $result->name;
        }, $results);
    }
    
    /**
     * Get Cronicle-specific user preferences
     * 
     * @param int $user_id User ID
     * @return array Cronicle preferences
     */
    private function get_cronicle_user_preferences($user_id) {
        $preferences = array();
        
        // Check if preferences engine is available and get user preferences
        if (function_exists('cronicle_preferences_engine')) {
            $engine = cronicle_preferences_engine();
            $user_prefs = $engine->get_user_preferences($user_id);
            
            if (!empty($user_prefs)) {
                // Extract relevant preferences for context
                if (isset($user_prefs['writing_style'])) {
                    $preferences['writing_style'] = $user_prefs['writing_style'];
                }
                
                if (isset($user_prefs['content_type'])) {
                    $preferences['preferred_content_type'] = $user_prefs['content_type'];
                }
                
                if (isset($user_prefs['tone'])) {
                    $preferences['preferred_tone'] = $user_prefs['tone'];
                }
                
                if (isset($user_prefs['target_audience'])) {
                    $preferences['target_audience'] = $user_prefs['target_audience'];
                }
            }
        }
        
        return $preferences;
    }
    
    /**
     * Format context as structured text (override for user-specific formatting)
     * 
     * @param array $context_data Context data
     * @return string Formatted context
     */
    protected function format_as_structured($context_data) {
        $lines = array();
        $lines[] = 'AUTHOR CONTEXT:';
        
        // Basic author info
        if (isset($context_data['display_name'])) {
            $lines[] = '- Author: ' . $context_data['display_name'];
        }
        
        // Bio
        if (isset($context_data['bio']) && !empty($context_data['bio'])) {
            $lines[] = '- Bio: ' . $context_data['bio'];
        }
        
        // Writing stats
        if (isset($context_data['writing_stats'])) {
            $stats = $context_data['writing_stats'];
            $lines[] = '- Writing Stats: ' . ($stats['total_posts'] ?? 0) . ' posts published';
            
            if (isset($stats['months_active']) && $stats['months_active'] > 1) {
                $lines[] = '- Experience: ' . $stats['months_active'] . ' months active, ~' . 
                          ($stats['posts_per_month'] ?? 0) . ' posts/month';
            }
            
            if (isset($stats['cronicle_generated_posts']) && $stats['cronicle_generated_posts'] > 0) {
                $lines[] = '- AI Experience: ' . $stats['cronicle_generated_posts'] . ' AI-assisted posts';
            }
        }
        
        // Preferred categories
        if (isset($context_data['preferred_categories']) && !empty($context_data['preferred_categories'])) {
            $lines[] = '- Preferred Categories: ' . implode(', ', array_slice($context_data['preferred_categories'], 0, 3));
        }
        
        // Writing style preferences
        if (isset($context_data['cronicle_preferences'])) {
            $prefs = $context_data['cronicle_preferences'];
            $pref_parts = array();
            
            if (isset($prefs['writing_style'])) {
                $style_value = is_array($prefs['writing_style']) ? 
                    (isset($prefs['writing_style']['tone']) ? $prefs['writing_style']['tone'] : 'mixed') : 
                    $prefs['writing_style'];
                $pref_parts[] = 'style: ' . $style_value;
            }
            
            if (isset($prefs['preferred_tone'])) {
                $tone_value = is_array($prefs['preferred_tone']) ? implode(', ', $prefs['preferred_tone']) : $prefs['preferred_tone'];
                $pref_parts[] = 'tone: ' . $tone_value;
            }
            
            if (isset($prefs['target_audience'])) {
                $audience_value = is_array($prefs['target_audience']) ? implode(', ', $prefs['target_audience']) : $prefs['target_audience'];
                $pref_parts[] = 'audience: ' . $audience_value;
            }
            
            if (!empty($pref_parts)) {
                $lines[] = '- Writing Preferences: ' . implode(', ', $pref_parts);
            }
        }
        
        // Recent posts for style reference
        if (isset($context_data['recent_posts']) && !empty($context_data['recent_posts'])) {
            $recent_titles = array_column($context_data['recent_posts'], 'title');
            $lines[] = '- Recent Posts: ' . implode(', ', array_slice($recent_titles, 0, 2));
        }
        
        // Website
        if (isset($context_data['website']) && !empty($context_data['website'])) {
            $lines[] = '- Website: ' . $context_data['website'];
        }
        
        return implode("\n", $lines);
    }
    
    /**
     * Check if provider is available
     * 
     * @param array $options Context options
     * @return bool Whether provider is available
     */
    public function is_available($options = array()) {
        // User context is available if user is logged in
        return is_user_logged_in() && parent::is_available($options);
    }
}