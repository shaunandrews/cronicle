<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Writing Style Provider
 * 
 * Provides writing style preferences and tone guidelines
 */
class Cronicle_Writing_Style_Provider extends Cronicle_Context_Provider_Base {
    
    /**
     * Get provider name
     * 
     * @return string Provider name
     */
    public function get_name() {
        return __('Writing Style', 'cronicle');
    }
    
    /**
     * Get provider description
     * 
     * @return string Provider description
     */
    public function get_description() {
        return __('Provides writing style preferences, tone guidelines, and content formatting preferences to ensure consistent voice.', 'cronicle');
    }
    
    /**
     * Get context data
     * 
     * @param array $options Context gathering options
     * @return array Context data
     */
    public function get_context($options = array()) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return array();
        }
        
        $cache_key = $this->generate_cache_key($options);
        $cached_context = $this->get_cached_context($cache_key);
        
        if ($cached_context !== false) {
            return $cached_context;
        }
        
        $context = array();
        
        // Get user's writing style preferences
        $style_preferences = $this->get_user_style_preferences($user_id);
        if (!empty($style_preferences)) {
            $context['style_preferences'] = $style_preferences;
        }
        
        // Analyze user's existing content for style patterns
        $style_analysis = $this->analyze_user_writing_style($user_id, $options);
        if (!empty($style_analysis)) {
            $context['writing_patterns'] = $style_analysis;
        }
        
        // Get site-wide style guidelines
        $site_guidelines = $this->get_site_style_guidelines();
        if (!empty($site_guidelines)) {
            $context['site_guidelines'] = $site_guidelines;
        }
        
        // Get content formatting preferences
        $formatting_preferences = $this->get_formatting_preferences($user_id);
        if (!empty($formatting_preferences)) {
            $context['formatting'] = $formatting_preferences;
        }
        
        // Cache the result
        $this->set_cached_context($cache_key, $context);
        
        return $context;
    }
    
    /**
     * Get user's writing style preferences
     * 
     * @param int $user_id User ID
     * @return array Style preferences
     */
    private function get_user_style_preferences($user_id) {
        $preferences = array();
        
        // Check if preferences engine is available
        if (function_exists('cronicle_preferences_engine')) {
            $engine = cronicle_preferences_engine();
            $user_prefs = $engine->get_user_preferences($user_id);
            
            if (!empty($user_prefs['writing_style'])) {
                $preferences = array_merge($preferences, $user_prefs['writing_style']);
            }
        }
        
        // Get individual style preferences from user meta
        $style_fields = array(
            'cronicle_writing_tone' => 'tone',
            'cronicle_writing_voice' => 'voice',
            'cronicle_target_audience' => 'target_audience',
            'cronicle_content_complexity' => 'complexity_level',
            'cronicle_writing_perspective' => 'perspective',
            'cronicle_content_length_preference' => 'preferred_length',
            'cronicle_use_humor' => 'use_humor',
            'cronicle_include_examples' => 'include_examples',
            'cronicle_call_to_action_style' => 'cta_style'
        );
        
        foreach ($style_fields as $meta_key => $pref_key) {
            $value = get_user_meta($user_id, $meta_key, true);
            if (!empty($value)) {
                $preferences[$pref_key] = $value;
            }
        }
        
        // Set defaults if no preferences are set
        if (empty($preferences)) {
            $preferences = $this->get_default_style_preferences();
        }
        
        return $preferences;
    }
    
    /**
     * Analyze user's existing writing style from published content
     * 
     * @param int $user_id User ID
     * @param array $options Context options
     * @return array Writing style analysis
     */
    private function analyze_user_writing_style($user_id, $options) {
        $analysis = array();
        
        // Get user's recent posts for analysis
        $posts = get_posts(array(
            'author' => $user_id,
            'numberposts' => 10,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
            'date_query' => array(
                array(
                    'after' => '6 months ago'
                )
            )
        ));
        
        if (empty($posts)) {
            return $analysis;
        }
        
        // Analyze writing patterns
        $analysis['content_analysis'] = $this->analyze_content_patterns($posts);
        $analysis['structure_analysis'] = $this->analyze_structure_patterns($posts);
        $analysis['tone_analysis'] = $this->analyze_tone_patterns($posts);
        
        return $analysis;
    }
    
    /**
     * Analyze content patterns from user's posts
     * 
     * @param array $posts User's posts
     * @return array Content analysis
     */
    private function analyze_content_patterns($posts) {
        $analysis = array();
        
        $total_word_count = 0;
        $paragraph_counts = array();
        $sentence_lengths = array();
        $heading_usage = 0;
        $list_usage = 0;
        
        foreach ($posts as $post) {
            $content = strip_tags($post->post_content);
            $word_count = str_word_count($content);
            $total_word_count += $word_count;
            
            // Analyze paragraph structure
            $paragraphs = preg_split('/\n\s*\n/', $content);
            $paragraph_counts[] = count($paragraphs);
            
            // Analyze sentence structure
            $sentences = preg_split('/[.!?]+/', $content);
            foreach ($sentences as $sentence) {
                $sentence_word_count = str_word_count(trim($sentence));
                if ($sentence_word_count > 0) {
                    $sentence_lengths[] = $sentence_word_count;
                }
            }
            
            // Check for headings and lists in original content
            if (preg_match('/<h[2-6]/', $post->post_content)) {
                $heading_usage++;
            }
            
            if (preg_match('/<[uo]l>|<li>/', $post->post_content)) {
                $list_usage++;
            }
        }
        
        $post_count = count($posts);
        
        $analysis['avg_word_count'] = round($total_word_count / $post_count);
        $analysis['avg_paragraphs'] = round(array_sum($paragraph_counts) / $post_count);
        $analysis['avg_sentence_length'] = round(array_sum($sentence_lengths) / count($sentence_lengths));
        $analysis['uses_headings'] = ($heading_usage / $post_count) > 0.5;
        $analysis['uses_lists'] = ($list_usage / $post_count) > 0.5;
        
        // Determine content complexity
        if ($analysis['avg_sentence_length'] > 20) {
            $analysis['complexity'] = 'complex';
        } elseif ($analysis['avg_sentence_length'] > 15) {
            $analysis['complexity'] = 'moderate';
        } else {
            $analysis['complexity'] = 'simple';
        }
        
        return $analysis;
    }
    
    /**
     * Analyze structure patterns from user's posts
     * 
     * @param array $posts User's posts
     * @return array Structure analysis
     */
    private function analyze_structure_patterns($posts) {
        $analysis = array();
        
        $introduction_patterns = array(
            'engaging_opening' => 0,
            'data_driven' => 0,
            'direct' => 0
        );
        $conclusion_patterns = array();
        $uses_questions = 0;
        $uses_numbered_points = 0;
        
        foreach ($posts as $post) {
            $content = $post->post_content;
            
            // Analyze introduction patterns
            $first_paragraph = $this->get_first_paragraph($content);
            if (preg_match('/^(In this|Today|Let me|Have you|Imagine|Picture)/', $first_paragraph)) {
                $introduction_patterns['engaging_opening']++;
            } elseif (preg_match('/^(According to|Research shows|Studies indicate)/', $first_paragraph)) {
                $introduction_patterns['data_driven']++;
            } else {
                $introduction_patterns['direct']++;
            }
            
            // Check for questions in content
            if (preg_match_all('/\?/', $content) > 2) {
                $uses_questions++;
            }
            
            // Check for numbered lists/points
            if (preg_match('/\d+\.|\b(first|second|third|finally)\b/i', $content)) {
                $uses_numbered_points++;
            }
        }
        
        $post_count = count($posts);
        
        if (!empty($introduction_patterns)) {
            arsort($introduction_patterns);
            $analysis['preferred_opening_style'] = key($introduction_patterns);
        }
        
        $analysis['uses_questions'] = ($uses_questions / $post_count) > 0.5;
        $analysis['uses_numbered_structure'] = ($uses_numbered_points / $post_count) > 0.5;
        
        return $analysis;
    }
    
    /**
     * Analyze tone patterns from user's posts
     * 
     * @param array $posts User's posts
     * @return array Tone analysis
     */
    private function analyze_tone_patterns($posts) {
        $analysis = array();
        
        $personal_pronouns = 0;
        $exclamation_usage = 0;
        $casual_words = 0;
        $formal_words = 0;
        $total_words = 0;
        
        // Define word lists for tone analysis
        $casual_indicators = array('awesome', 'cool', 'great', 'amazing', 'love', 'hate', 'super', 'really', 'pretty', 'quite');
        $formal_indicators = array('therefore', 'however', 'furthermore', 'consequently', 'nevertheless', 'moreover', 'thus', 'hence');
        
        foreach ($posts as $post) {
            $content = strtolower(strip_tags($post->post_content));
            $words = str_word_count($content, 1);
            $total_words += count($words);
            
            // Count personal pronouns
            $personal_pronouns += preg_match_all('/\b(i|we|you|my|our|your)\b/', $content);
            
            // Count exclamation marks
            $exclamation_usage += preg_match_all('/!/', $post->post_content);
            
            // Count casual vs formal language
            foreach ($words as $word) {
                if (in_array($word, $casual_indicators)) {
                    $casual_words++;
                } elseif (in_array($word, $formal_indicators)) {
                    $formal_words++;
                }
            }
        }
        
        // Calculate tone indicators
        $personal_pronoun_ratio = $personal_pronouns / $total_words;
        $casual_ratio = $casual_words / $total_words;
        $formal_ratio = $formal_words / $total_words;
        
        if ($personal_pronoun_ratio > 0.02) {
            $analysis['tone'] = 'personal';
        } elseif ($formal_ratio > $casual_ratio && $formal_ratio > 0.005) {
            $analysis['tone'] = 'formal';
        } elseif ($casual_ratio > 0.01) {
            $analysis['tone'] = 'casual';
        } else {
            $analysis['tone'] = 'neutral';
        }
        
        $analysis['uses_exclamations'] = $exclamation_usage > (count($posts) * 0.5);
        $analysis['personal_style'] = $personal_pronoun_ratio > 0.015;
        
        return $analysis;
    }
    
    /**
     * Get site-wide style guidelines
     * 
     * @return array Site guidelines
     */
    private function get_site_style_guidelines() {
        $guidelines = array();
        
        // Get site-level style settings
        $site_style_options = get_option('cronicle_site_style_guidelines', array());
        
        if (!empty($site_style_options)) {
            $guidelines = $site_style_options;
        } else {
            // Infer guidelines from site context
            $guidelines = $this->infer_site_guidelines();
        }
        
        return $guidelines;
    }
    
    /**
     * Get formatting preferences
     * 
     * @param int $user_id User ID
     * @return array Formatting preferences
     */
    private function get_formatting_preferences($user_id) {
        $preferences = array();
        
        // Get user-specific formatting preferences
        $formatting_options = array(
            'cronicle_use_bold_emphasis' => 'use_bold',
            'cronicle_use_italics_emphasis' => 'use_italics',
            'cronicle_paragraph_spacing' => 'paragraph_spacing',
            'cronicle_heading_style' => 'heading_style',
            'cronicle_list_style_preference' => 'list_style',
            'cronicle_quote_style' => 'quote_style',
            'cronicle_image_placement' => 'image_placement'
        );
        
        foreach ($formatting_options as $meta_key => $pref_key) {
            $value = get_user_meta($user_id, $meta_key, true);
            if (!empty($value)) {
                $preferences[$pref_key] = $value;
            }
        }
        
        // Set defaults based on user's existing content
        if (empty($preferences)) {
            $preferences = $this->get_default_formatting_preferences();
        }
        
        return $preferences;
    }
    
    /**
     * Get default style preferences
     * 
     * @return array Default preferences
     */
    private function get_default_style_preferences() {
        return array(
            'tone' => 'professional',
            'voice' => 'informative',
            'target_audience' => 'general',
            'complexity_level' => 'moderate',
            'perspective' => 'third_person',
            'preferred_length' => 'medium',
            'use_humor' => false,
            'include_examples' => true,
            'cta_style' => 'subtle'
        );
    }
    
    /**
     * Get default formatting preferences
     * 
     * @return array Default formatting preferences
     */
    private function get_default_formatting_preferences() {
        return array(
            'use_bold' => true,
            'use_italics' => true,
            'paragraph_spacing' => 'normal',
            'heading_style' => 'descriptive',
            'list_style' => 'bullet',
            'quote_style' => 'standard'
        );
    }
    
    /**
     * Infer site guidelines from site context
     * 
     * @return array Inferred guidelines
     */
    private function infer_site_guidelines() {
        $guidelines = array();
        
        // Analyze site name and tagline for tone clues
        $site_name = get_bloginfo('name');
        $site_tagline = get_bloginfo('description');
        
        $combined_text = strtolower($site_name . ' ' . $site_tagline);
        
        // Business/professional indicators
        if (preg_match('/\b(business|professional|corporate|enterprise|solution|service)\b/', $combined_text)) {
            $guidelines['suggested_tone'] = 'professional';
            $guidelines['suggested_voice'] = 'authoritative';
        }
        // Creative/personal indicators
        elseif (preg_match('/\b(creative|personal|blog|journey|story|life)\b/', $combined_text)) {
            $guidelines['suggested_tone'] = 'casual';
            $guidelines['suggested_voice'] = 'personal';
        }
        // Technical/educational indicators
        elseif (preg_match('/\b(tech|education|learn|guide|tutorial|how)\b/', $combined_text)) {
            $guidelines['suggested_tone'] = 'informative';
            $guidelines['suggested_voice'] = 'educational';
        }
        
        return $guidelines;
    }
    
    /**
     * Get first paragraph from content
     * 
     * @param string $content Post content
     * @return string First paragraph
     */
    private function get_first_paragraph($content) {
        // Strip HTML and get first paragraph
        $clean_content = strip_tags($content);
        $paragraphs = preg_split('/\n\s*\n/', $clean_content);
        
        return isset($paragraphs[0]) ? trim($paragraphs[0]) : '';
    }
    
    /**
     * Format context as structured text
     * 
     * @param array $context_data Context data
     * @return string Formatted context
     */
    protected function format_as_structured($context_data) {
        $lines = array();
        $lines[] = 'WRITING STYLE CONTEXT:';
        
        // Style preferences
        if (isset($context_data['style_preferences'])) {
            $prefs = $context_data['style_preferences'];
            
            $style_parts = array();
            if (isset($prefs['tone'])) {
                $style_parts[] = 'tone: ' . $prefs['tone'];
            }
            if (isset($prefs['voice'])) {
                $style_parts[] = 'voice: ' . $prefs['voice'];
            }
            if (isset($prefs['target_audience'])) {
                $style_parts[] = 'audience: ' . $prefs['target_audience'];
            }
            
            if (!empty($style_parts)) {
                $lines[] = '- Writing Style: ' . implode(', ', $style_parts);
            }
            
            if (isset($prefs['complexity_level'])) {
                $lines[] = '- Complexity Level: ' . $prefs['complexity_level'];
            }
            
            if (isset($prefs['preferred_length'])) {
                $lines[] = '- Preferred Length: ' . $prefs['preferred_length'];
            }
        }
        
        // Writing patterns from analysis
        if (isset($context_data['writing_patterns']['content_analysis'])) {
            $analysis = $context_data['writing_patterns']['content_analysis'];
            
            if (isset($analysis['avg_word_count'])) {
                $lines[] = '- Typical Length: ~' . $analysis['avg_word_count'] . ' words';
            }
            
            if (isset($analysis['complexity'])) {
                $lines[] = '- Sentence Style: ' . $analysis['complexity'];
            }
            
            $structure_elements = array();
            if (isset($analysis['uses_headings']) && $analysis['uses_headings']) {
                $structure_elements[] = 'headings';
            }
            if (isset($analysis['uses_lists']) && $analysis['uses_lists']) {
                $structure_elements[] = 'lists';
            }
            
            if (!empty($structure_elements)) {
                $lines[] = '- Structure Elements: ' . implode(', ', $structure_elements);
            }
        }
        
        // Tone analysis
        if (isset($context_data['writing_patterns']['tone_analysis']['tone'])) {
            $lines[] = '- Detected Tone: ' . $context_data['writing_patterns']['tone_analysis']['tone'];
        }
        
        // Site guidelines
        if (isset($context_data['site_guidelines'])) {
            $guidelines = $context_data['site_guidelines'];
            
            $guideline_parts = array();
            if (isset($guidelines['suggested_tone'])) {
                $guideline_parts[] = 'tone: ' . $guidelines['suggested_tone'];
            }
            if (isset($guidelines['suggested_voice'])) {
                $guideline_parts[] = 'voice: ' . $guidelines['suggested_voice'];
            }
            
            if (!empty($guideline_parts)) {
                $lines[] = '- Site Guidelines: ' . implode(', ', $guideline_parts);
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
        // Writing style context is available for logged-in users
        return is_user_logged_in() && parent::is_available($options);
    }
}