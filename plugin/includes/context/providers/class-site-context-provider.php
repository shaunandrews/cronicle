<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Site Context Provider
 * 
 * Provides site information, categories, and content patterns
 */
class Cronicle_Site_Context_Provider extends Cronicle_Context_Provider_Base {
    
    /**
     * Get provider name
     * 
     * @return string Provider name
     */
    public function get_name() {
        return __('Site Information', 'cronicle');
    }
    
    /**
     * Get provider description
     * 
     * @return string Provider description
     */
    public function get_description() {
        return __('Provides site name, tagline, categories, and recent content patterns to help the AI understand your site\'s context and style.', 'cronicle');
    }
    
    /**
     * Get context data
     * 
     * @param array $options Context gathering options
     * @return array Context data
     */
    public function get_context($options = array()) {
        $cache_key = $this->generate_cache_key($options);
        $cached_context = $this->get_cached_context($cache_key);
        
        if ($cached_context !== false) {
            return $cached_context;
        }
        
        $context = array();
        
        // Basic site information
        $context['site_name'] = get_bloginfo('name');
        $context['site_tagline'] = get_bloginfo('description');
        $context['site_url'] = get_site_url();
        $context['language'] = get_locale();
        $context['timezone'] = get_option('timezone_string') ?: 'UTC';
        
        // Include additional site info if requested
        if (!isset($options['minimal']) || !$options['minimal']) {
            $context = array_merge($context, $this->get_extended_site_info($options));
        }
        
        // Cache the result
        $this->set_cached_context($cache_key, $context);
        
        return $context;
    }
    
    /**
     * Get extended site information
     * 
     * @param array $options Context options
     * @return array Extended context data
     */
    private function get_extended_site_info($options) {
        $context = array();
        
        // Categories information
        $categories = $this->get_site_categories($options);
        if (!empty($categories)) {
            $context['categories'] = $categories;
        }
        
        // Tags information
        $tags = $this->get_site_tags($options);
        if (!empty($tags)) {
            $context['popular_tags'] = $tags;
        }
        
        // Recent posts for content pattern analysis
        $recent_posts = $this->get_recent_posts_info($options);
        if (!empty($recent_posts)) {
            $context['recent_posts'] = $recent_posts;
        }
        
        // Content statistics
        $content_stats = $this->get_content_statistics();
        if (!empty($content_stats)) {
            $context['content_stats'] = $content_stats;
        }
        
        // Theme information
        $theme_info = $this->get_theme_info();
        if (!empty($theme_info)) {
            $context['theme'] = $theme_info;
        }
        
        // Site settings that might affect content
        $content_settings = $this->get_content_settings();
        if (!empty($content_settings)) {
            $context['settings'] = $content_settings;
        }
        
        return $context;
    }
    
    /**
     * Get site categories with post counts
     * 
     * @param array $options Context options
     * @return array Categories data
     */
    private function get_site_categories($options) {
        $max_categories = $options['max_categories'] ?? $this->config['max_items'];
        
        $categories = get_categories(array(
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => $max_categories,
            'hide_empty' => true
        ));
        
        if (empty($categories)) {
            return array();
        }
        
        $category_data = array();
        foreach ($categories as $category) {
            $category_data[] = array(
                'name' => $category->name,
                'count' => $category->count,
                'description' => $category->description
            );
        }
        
        return $category_data;
    }
    
    /**
     * Get popular tags
     * 
     * @param array $options Context options
     * @return array Tags data
     */
    private function get_site_tags($options) {
        $max_tags = $options['max_tags'] ?? min($this->config['max_items'], 15);
        
        $tags = get_tags(array(
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => $max_tags,
            'hide_empty' => true
        ));
        
        if (empty($tags)) {
            return array();
        }
        
        return array_map(function($tag) {
            return $tag->name;
        }, $tags);
    }
    
    /**
     * Get recent posts information for pattern analysis
     * 
     * @param array $options Context options
     * @return array Recent posts data
     */
    private function get_recent_posts_info($options) {
        $max_posts = $options['max_recent_posts'] ?? min($this->config['max_items'], 5);
        
        $posts = get_posts(array(
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
                'excerpt' => wp_trim_words(get_the_excerpt($post), 20),
                'categories' => wp_get_post_categories($post->ID, array('fields' => 'names')),
                'word_count' => str_word_count(strip_tags($post->post_content)),
                'date' => get_the_date('Y-m-d', $post)
            );
        }
        
        return $posts_data;
    }
    
    /**
     * Get content statistics
     * 
     * @return array Content statistics
     */
    private function get_content_statistics() {
        $stats = array();
        
        // Post counts by status
        $post_counts = wp_count_posts('post');
        $stats['total_posts'] = $post_counts->publish ?? 0;
        $stats['draft_posts'] = $post_counts->draft ?? 0;
        
        // Page count
        $page_counts = wp_count_posts('page');
        $stats['total_pages'] = $page_counts->publish ?? 0;
        
        // Comment count
        $comment_count = wp_count_comments();
        $stats['total_comments'] = $comment_count->approved ?? 0;
        
        // User count
        $user_count = count_users();
        $stats['total_authors'] = $user_count['total_users'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Get theme information
     * 
     * @return array Theme data
     */
    private function get_theme_info() {
        $theme = wp_get_theme();
        
        return array(
            'name' => $theme->get('Name'),
            'description' => wp_trim_words($theme->get('Description'), 20),
            'version' => $theme->get('Version')
        );
    }
    
    /**
     * Get content-related settings
     * 
     * @return array Content settings
     */
    private function get_content_settings() {
        $settings = array();
        
        // Date/time format
        $settings['date_format'] = get_option('date_format');
        $settings['time_format'] = get_option('time_format');
        
        // Posts per page
        $settings['posts_per_page'] = get_option('posts_per_page');
        
        // Default category
        $default_category = get_option('default_category');
        if ($default_category) {
            $category = get_category($default_category);
            if ($category && !is_wp_error($category)) {
                $settings['default_category'] = $category->name;
            }
        }
        
        // Comment settings
        $settings['comments_enabled'] = get_option('default_comment_status') === 'open';
        
        return $settings;
    }
    
    /**
     * Format context as structured text (override for site-specific formatting)
     * 
     * @param array $context_data Context data
     * @return string Formatted context
     */
    protected function format_as_structured($context_data) {
        $lines = array();
        $lines[] = 'SITE CONTEXT:';
        
        // Basic site info
        if (isset($context_data['site_name'])) {
            $lines[] = '- Site Name: ' . $context_data['site_name'];
        }
        
        if (isset($context_data['site_tagline']) && !empty($context_data['site_tagline'])) {
            $lines[] = '- Site Tagline: ' . $context_data['site_tagline'];
        }
        
        // Categories
        if (isset($context_data['categories']) && !empty($context_data['categories'])) {
            $category_names = array_column($context_data['categories'], 'name');
            $lines[] = '- Popular Categories: ' . implode(', ', array_slice($category_names, 0, 5));
        }
        
        // Tags
        if (isset($context_data['popular_tags']) && !empty($context_data['popular_tags'])) {
            $lines[] = '- Popular Tags: ' . implode(', ', array_slice($context_data['popular_tags'], 0, 8));
        }
        
        // Recent post titles for content style context
        if (isset($context_data['recent_posts']) && !empty($context_data['recent_posts'])) {
            $recent_titles = array_column($context_data['recent_posts'], 'title');
            $lines[] = '- Recent Post Titles: ' . implode(', ', array_slice($recent_titles, 0, 3));
        }
        
        // Content statistics (summary)
        if (isset($context_data['content_stats'])) {
            $stats = $context_data['content_stats'];
            $lines[] = '- Content Stats: ' . ($stats['total_posts'] ?? 0) . ' posts, ' . 
                      ($stats['total_pages'] ?? 0) . ' pages, ' . ($stats['total_authors'] ?? 0) . ' authors';
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
        // Site context is always available
        return parent::is_available($options);
    }
}