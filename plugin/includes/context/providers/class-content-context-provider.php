<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Content Context Provider
 * 
 * Provides related content, SEO data, and content analysis
 */
class Cronicle_Content_Context_Provider extends Cronicle_Context_Provider_Base {
    
    /**
     * Get provider name
     * 
     * @return string Provider name
     */
    public function get_name() {
        return __('Content Context', 'cronicle');
    }
    
    /**
     * Get provider description
     * 
     * @return string Provider description
     */
    public function get_description() {
        return __('Provides related content, trending topics, and SEO insights to help create contextually relevant content.', 'cronicle');
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
        
        // Related content based on topic/keywords
        if (isset($options['topic']) || isset($options['keywords'])) {
            $related_content = $this->get_related_content($options);
            if (!empty($related_content)) {
                $context['related_content'] = $related_content;
            }
        }
        
        // Trending topics/categories
        $trending_content = $this->get_trending_content($options);
        if (!empty($trending_content)) {
            $context['trending_content'] = $trending_content;
        }
        
        // Content gaps analysis
        $content_gaps = $this->analyze_content_gaps($options);
        if (!empty($content_gaps)) {
            $context['content_opportunities'] = $content_gaps;
        }
        
        // SEO context if available
        $seo_context = $this->get_seo_context($options);
        if (!empty($seo_context)) {
            $context['seo_context'] = $seo_context;
        }
        
        // Seasonal/timely content suggestions
        $seasonal_context = $this->get_seasonal_context($options);
        if (!empty($seasonal_context)) {
            $context['seasonal_context'] = $seasonal_context;
        }
        
        // Cache the result
        $this->set_cached_context($cache_key, $context);
        
        return $context;
    }
    
    /**
     * Get related content based on topic or keywords
     * 
     * @param array $options Context options
     * @return array Related content data
     */
    private function get_related_content($options) {
        $related_content = array();
        
        // Get search terms from topic or keywords
        $search_terms = array();
        
        if (isset($options['topic'])) {
            $search_terms = array_merge($search_terms, $this->extract_keywords($options['topic']));
        }
        
        if (isset($options['keywords'])) {
            if (is_array($options['keywords'])) {
                $search_terms = array_merge($search_terms, $options['keywords']);
            } else {
                $search_terms[] = $options['keywords'];
            }
        }
        
        if (empty($search_terms)) {
            return $related_content;
        }
        
        // Search for related posts
        $max_related = $options['max_related_content'] ?? min($this->config['max_items'], 5);
        
        // Use WordPress search to find related content
        $search_query = implode(' ', array_slice($search_terms, 0, 3));
        
        $related_posts = get_posts(array(
            's' => $search_query,
            'numberposts' => $max_related,
            'post_status' => 'publish',
            'orderby' => 'relevance',
            'meta_query' => array(
                array(
                    'key' => '_cronicle_generated',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        if (!empty($related_posts)) {
            foreach ($related_posts as $post) {
                $related_content[] = array(
                    'title' => get_the_title($post),
                    'excerpt' => wp_trim_words(get_the_excerpt($post), 20),
                    'url' => get_permalink($post),
                    'categories' => wp_get_post_categories($post->ID, array('fields' => 'names')),
                    'date' => get_the_date('Y-m-d', $post)
                );
            }
        }
        
        return $related_content;
    }
    
    /**
     * Get trending content based on recent activity
     * 
     * @param array $options Context options
     * @return array Trending content data
     */
    private function get_trending_content($options) {
        $trending = array();
        
        // Get posts with most comments in last 30 days
        $trending_posts = get_posts(array(
            'numberposts' => 5,
            'orderby' => 'comment_count',
            'order' => 'DESC',
            'date_query' => array(
                array(
                    'after' => '30 days ago'
                )
            ),
            'post_status' => 'publish'
        ));
        
        if (!empty($trending_posts)) {
            foreach ($trending_posts as $post) {
                if (get_comments_number($post->ID) > 0) {
                    $trending[] = array(
                        'title' => get_the_title($post),
                        'comment_count' => get_comments_number($post->ID),
                        'categories' => wp_get_post_categories($post->ID, array('fields' => 'names'))
                    );
                }
            }
        }
        
        // Get most used tags in recent posts
        $recent_tags = $this->get_trending_tags();
        if (!empty($recent_tags)) {
            $trending['popular_tags'] = $recent_tags;
        }
        
        return $trending;
    }
    
    /**
     * Analyze content gaps and opportunities
     * 
     * @param array $options Context options
     * @return array Content opportunities
     */
    private function analyze_content_gaps($options) {
        $opportunities = array();
        
        // Find categories with few recent posts
        $categories = get_categories(array(
            'orderby' => 'count',
            'order' => 'ASC',
            'number' => 10,
            'hide_empty' => true
        ));
        
        $underserved_categories = array();
        foreach ($categories as $category) {
            // Check for recent posts in this category
            $recent_posts = get_posts(array(
                'category' => $category->term_id,
                'numberposts' => 1,
                'date_query' => array(
                    array(
                        'after' => '60 days ago'
                    )
                )
            ));
            
            if (empty($recent_posts) && $category->count > 2) {
                $underserved_categories[] = $category->name;
            }
        }
        
        if (!empty($underserved_categories)) {
            $opportunities['underserved_categories'] = array_slice($underserved_categories, 0, 3);
        }
        
        // Suggest content based on successful past posts
        $successful_posts = $this->get_successful_posts();
        if (!empty($successful_posts)) {
            $opportunities['successful_content_patterns'] = $successful_posts;
        }
        
        return $opportunities;
    }
    
    /**
     * Get SEO context and recommendations
     * 
     * @param array $options Context options
     * @return array SEO context data
     */
    private function get_seo_context($options) {
        $seo_context = array();
        
        // Check if SEO plugins are active
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            // Yoast SEO integration
            $seo_context['seo_plugin'] = 'yoast';
            
            // Get focus keywords from recent posts
            $focus_keywords = $this->get_yoast_focus_keywords();
            if (!empty($focus_keywords)) {
                $seo_context['recent_focus_keywords'] = $focus_keywords;
            }
        } elseif (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
            // All in One SEO integration
            $seo_context['seo_plugin'] = 'aioseo';
        }
        
        // Generic SEO recommendations based on content analysis
        if (isset($options['topic'])) {
            $seo_suggestions = $this->generate_seo_suggestions($options['topic']);
            if (!empty($seo_suggestions)) {
                $seo_context['suggestions'] = $seo_suggestions;
            }
        }
        
        return $seo_context;
    }
    
    /**
     * Get seasonal and timely content context
     * 
     * @param array $options Context options
     * @return array Seasonal context data
     */
    private function get_seasonal_context($options) {
        $seasonal = array();
        
        // Current month and season
        $current_month = date('n');
        $current_season = $this->get_current_season($current_month);
        
        $seasonal['current_season'] = $current_season;
        $seasonal['current_month'] = date('F');
        
        // Upcoming holidays/events
        $upcoming_events = $this->get_upcoming_events();
        if (!empty($upcoming_events)) {
            $seasonal['upcoming_events'] = $upcoming_events;
        }
        
        // Seasonal content suggestions
        $seasonal_suggestions = $this->get_seasonal_content_suggestions($current_season, $current_month);
        if (!empty($seasonal_suggestions)) {
            $seasonal['content_suggestions'] = $seasonal_suggestions;
        }
        
        return $seasonal;
    }
    
    /**
     * Extract keywords from topic text
     * 
     * @param string $topic Topic text
     * @return array Keywords
     */
    private function extract_keywords($topic) {
        // Simple keyword extraction - split by common delimiters and filter
        $words = preg_split('/[\s,.-]+/', strtolower($topic));
        
        // Filter out common stop words
        $stop_words = array('the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should', 'could', 'can', 'may', 'might', 'must', 'shall');
        
        $keywords = array_filter($words, function($word) use ($stop_words) {
            return strlen($word) > 2 && !in_array($word, $stop_words);
        });
        
        return array_values($keywords);
    }
    
    /**
     * Get trending tags from recent posts
     * 
     * @return array Trending tags
     */
    private function get_trending_tags() {
        $recent_posts = get_posts(array(
            'numberposts' => 20,
            'date_query' => array(
                array(
                    'after' => '30 days ago'
                )
            ),
            'fields' => 'ids'
        ));
        
        if (empty($recent_posts)) {
            return array();
        }
        
        $tag_counts = array();
        
        foreach ($recent_posts as $post_id) {
            $tags = wp_get_post_tags($post_id);
            foreach ($tags as $tag) {
                $tag_counts[$tag->name] = ($tag_counts[$tag->name] ?? 0) + 1;
            }
        }
        
        // Sort by count and return top tags
        arsort($tag_counts);
        return array_slice(array_keys($tag_counts), 0, 8);
    }
    
    /**
     * Get successful posts based on engagement metrics
     * 
     * @return array Successful post patterns
     */
    private function get_successful_posts() {
        $successful_posts = get_posts(array(
            'numberposts' => 3,
            'orderby' => 'comment_count',
            'order' => 'DESC',
            'post_status' => 'publish',
            'date_query' => array(
                array(
                    'after' => '90 days ago'
                )
            )
        ));
        
        $patterns = array();
        foreach ($successful_posts as $post) {
            if (get_comments_number($post->ID) > 0) {
                $patterns[] = array(
                    'title_pattern' => $this->analyze_title_pattern($post->post_title),
                    'categories' => wp_get_post_categories($post->ID, array('fields' => 'names')),
                    'word_count' => str_word_count(strip_tags($post->post_content)),
                    'engagement' => get_comments_number($post->ID) . ' comments'
                );
            }
        }
        
        return $patterns;
    }
    
    /**
     * Analyze title pattern for successful content
     * 
     * @param string $title Post title
     * @return string Title pattern
     */
    private function analyze_title_pattern($title) {
        // Simple pattern detection
        if (preg_match('/^\d+/', $title)) {
            return 'numbered_list';
        } elseif (stripos($title, 'how to') === 0) {
            return 'how_to_guide';
        } elseif (stripos($title, '?') !== false) {
            return 'question_format';
        } elseif (preg_match('/ultimate|complete|comprehensive|definitive/i', $title)) {
            return 'comprehensive_guide';
        } else {
            return 'standard';
        }
    }
    
    /**
     * Get Yoast SEO focus keywords from recent posts
     * 
     * @return array Focus keywords
     */
    private function get_yoast_focus_keywords() {
        $recent_posts = get_posts(array(
            'numberposts' => 10,
            'fields' => 'ids',
            'date_query' => array(
                array(
                    'after' => '60 days ago'
                )
            )
        ));
        
        $keywords = array();
        foreach ($recent_posts as $post_id) {
            $focus_keyword = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
            if (!empty($focus_keyword)) {
                $keywords[] = $focus_keyword;
            }
        }
        
        return array_unique($keywords);
    }
    
    /**
     * Generate SEO suggestions based on topic
     * 
     * @param string $topic Topic
     * @return array SEO suggestions
     */
    private function generate_seo_suggestions($topic) {
        $suggestions = array();
        
        $keywords = $this->extract_keywords($topic);
        if (!empty($keywords)) {
            $suggestions['suggested_keywords'] = array_slice($keywords, 0, 3);
            $suggestions['long_tail_suggestion'] = implode(' ', array_slice($keywords, 0, 2));
        }
        
        return $suggestions;
    }
    
    /**
     * Get current season
     * 
     * @param int $month Current month
     * @return string Season name
     */
    private function get_current_season($month) {
        if (in_array($month, array(12, 1, 2))) {
            return 'winter';
        } elseif (in_array($month, array(3, 4, 5))) {
            return 'spring';
        } elseif (in_array($month, array(6, 7, 8))) {
            return 'summer';
        } else {
            return 'autumn';
        }
    }
    
    /**
     * Get upcoming events and holidays
     * 
     * @return array Upcoming events
     */
    private function get_upcoming_events() {
        $events = array();
        $current_month = date('n');
        $current_day = date('j');
        
        // Define some common events by month
        $monthly_events = array(
            1 => array('New Year', 'Winter Sales'),
            2 => array('Valentine\'s Day', 'Winter Activities'),
            3 => array('Spring Cleaning', 'St. Patrick\'s Day'),
            4 => array('Easter', 'Spring Activities'),
            5 => array('Mother\'s Day', 'Spring Gardening'),
            6 => array('Father\'s Day', 'Summer Prep'),
            7 => array('Independence Day', 'Summer Activities'),
            8 => array('Back to School Prep', 'Summer Travel'),
            9 => array('Back to School', 'Fall Prep'),
            10 => array('Halloween', 'Fall Activities'),
            11 => array('Thanksgiving', 'Holiday Prep'),
            12 => array('Christmas', 'Holiday Activities', 'Year-End')
        );
        
        // Get events for current and next month
        if (isset($monthly_events[$current_month])) {
            $events = array_merge($events, $monthly_events[$current_month]);
        }
        
        $next_month = ($current_month % 12) + 1;
        if (isset($monthly_events[$next_month])) {
            $events = array_merge($events, $monthly_events[$next_month]);
        }
        
        return array_unique($events);
    }
    
    /**
     * Get seasonal content suggestions
     * 
     * @param string $season Current season
     * @param int $month Current month
     * @return array Content suggestions
     */
    private function get_seasonal_content_suggestions($season, $month) {
        $suggestions = array();
        
        $seasonal_topics = array(
            'winter' => array('holiday planning', 'winter wellness', 'indoor activities', 'comfort food'),
            'spring' => array('spring cleaning', 'gardening', 'renewal', 'outdoor activities'),
            'summer' => array('travel tips', 'outdoor adventures', 'summer recipes', 'vacation planning'),
            'autumn' => array('back to school', 'fall activities', 'seasonal recipes', 'productivity')
        );
        
        if (isset($seasonal_topics[$season])) {
            $suggestions = $seasonal_topics[$season];
        }
        
        return $suggestions;
    }
    
    /**
     * Format context as structured text
     * 
     * @param array $context_data Context data
     * @return string Formatted context
     */
    protected function format_as_structured($context_data) {
        $lines = array();
        $lines[] = 'CONTENT CONTEXT:';
        
        // Related content
        if (isset($context_data['related_content']) && !empty($context_data['related_content'])) {
            $related_titles = array_column($context_data['related_content'], 'title');
            $lines[] = '- Related Content: ' . implode(', ', array_slice($related_titles, 0, 2));
        }
        
        // Trending content
        if (isset($context_data['trending_content']['popular_tags']) && !empty($context_data['trending_content']['popular_tags'])) {
            $lines[] = '- Trending Tags: ' . implode(', ', array_slice($context_data['trending_content']['popular_tags'], 0, 5));
        }
        
        // Content opportunities
        if (isset($context_data['content_opportunities']['underserved_categories'])) {
            $lines[] = '- Content Opportunities: ' . implode(', ', $context_data['content_opportunities']['underserved_categories']);
        }
        
        // SEO context
        if (isset($context_data['seo_context']['suggestions']['suggested_keywords'])) {
            $lines[] = '- SEO Keywords: ' . implode(', ', $context_data['seo_context']['suggestions']['suggested_keywords']);
        }
        
        // Seasonal context
        if (isset($context_data['seasonal_context'])) {
            $seasonal = $context_data['seasonal_context'];
            if (isset($seasonal['current_season'])) {
                $lines[] = '- Season: ' . ucfirst($seasonal['current_season']);
            }
            if (isset($seasonal['upcoming_events'])) {
                $lines[] = '- Upcoming Events: ' . implode(', ', array_slice($seasonal['upcoming_events'], 0, 3));
            }
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
        // Content context is always available
        return parent::is_available($options);
    }
}