<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Conversation Context Provider
 * 
 * Provides chat history and conversation context
 */
class Cronicle_Conversation_Context_Provider extends Cronicle_Context_Provider_Base {
    
    /**
     * Get provider name
     * 
     * @return string Provider name
     */
    public function get_name() {
        return __('Conversation History', 'cronicle');
    }
    
    /**
     * Get provider description
     * 
     * @return string Provider description
     */
    public function get_description() {
        return __('Provides recent conversation history and context to maintain continuity in AI responses.', 'cronicle');
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
        
        // Get current session context
        $current_session = $this->get_current_session_context($user_id, $options);
        if (!empty($current_session)) {
            $context['current_session'] = $current_session;
        }
        
        // Get recent conversation history
        $recent_history = $this->get_recent_conversation_history($user_id, $options);
        if (!empty($recent_history)) {
            $context['recent_conversations'] = $recent_history;
        }
        
        // Get conversation patterns and preferences
        $conversation_patterns = $this->analyze_conversation_patterns($user_id, $options);
        if (!empty($conversation_patterns)) {
            $context['conversation_patterns'] = $conversation_patterns;
        }
        
        // Cache the result (shorter cache for conversation data)
        $this->config['cache_duration'] = 60; // 1 minute cache
        $this->set_cached_context($cache_key, $context);
        
        return $context;
    }
    
    /**
     * Get current session context
     * 
     * @param int $user_id User ID
     * @param array $options Context options
     * @return array Current session data
     */
    private function get_current_session_context($user_id, $options) {
        $context = array();
        
        // Get chat history instance
        if (!function_exists('cronicle_chat_history')) {
            return $context;
        }
        
        $chat_history = cronicle_chat_history();
        $current_session = $chat_history->get_current_session($user_id);
        
        if (!$current_session) {
            return $context;
        }
        
        // Get session metadata
        $context['session_id'] = $current_session->ID;
        $context['session_started'] = get_the_date('Y-m-d H:i:s', $current_session);
        
        // Get recent messages from current session
        $messages = $chat_history->load_session_history($current_session);
        
        if (!empty($messages)) {
            $context['recent_messages'] = $this->format_messages_for_context($messages, 5);
            $context['message_count'] = count($messages);
            
            // Analyze current session patterns
            $session_analysis = $this->analyze_session_messages($messages);
            if (!empty($session_analysis)) {
                $context['session_analysis'] = $session_analysis;
            }
        }
        
        return $context;
    }
    
    /**
     * Get recent conversation history from other sessions
     * 
     * @param int $user_id User ID
     * @param array $options Context options
     * @return array Recent conversation data
     */
    private function get_recent_conversation_history($user_id, $options) {
        $context = array();
        
        if (!function_exists('cronicle_chat_history')) {
            return $context;
        }
        
        $chat_history = cronicle_chat_history();
        
        // Get recent sessions (excluding current session)
        $max_sessions = $options['max_recent_sessions'] ?? 3;
        $recent_sessions = $chat_history->get_user_sessions($user_id, $max_sessions + 1);
        
        if (empty($recent_sessions)) {
            return $context;
        }
        
        $session_summaries = array();
        foreach (array_slice($recent_sessions, 0, $max_sessions) as $session) {
            $summary = $this->get_session_summary($session, $chat_history);
            if (!empty($summary)) {
                $session_summaries[] = $summary;
            }
        }
        
        if (!empty($session_summaries)) {
            $context['recent_sessions'] = $session_summaries;
        }
        
        return $context;
    }
    
    /**
     * Analyze conversation patterns and user preferences
     * 
     * @param int $user_id User ID
     * @param array $options Context options
     * @return array Conversation patterns
     */
    private function analyze_conversation_patterns($user_id, $options) {
        $patterns = array();
        
        if (!function_exists('cronicle_chat_history')) {
            return $patterns;
        }
        
        $chat_history = cronicle_chat_history();
        
        // Get user's conversation statistics
        $stats = $this->get_conversation_statistics($user_id, $chat_history);
        if (!empty($stats)) {
            $patterns['statistics'] = $stats;
        }
        
        // Analyze preferred content types
        $content_preferences = $this->analyze_content_type_preferences($user_id, $chat_history);
        if (!empty($content_preferences)) {
            $patterns['content_preferences'] = $content_preferences;
        }
        
        // Analyze communication style preferences
        $communication_style = $this->analyze_communication_style($user_id, $chat_history);
        if (!empty($communication_style)) {
            $patterns['communication_style'] = $communication_style;
        }
        
        return $patterns;
    }
    
    /**
     * Format messages for context inclusion
     * 
     * @param array $messages Message objects
     * @param int $max_messages Maximum messages to include
     * @return array Formatted messages
     */
    private function format_messages_for_context($messages, $max_messages = 5) {
        $formatted_messages = array();
        
        // Reverse to get chronological order
        $messages = array_reverse(array_slice($messages, 0, $max_messages));
        
        foreach ($messages as $message) {
            // Messages from chat history are already arrays, not post objects
            if (!is_array($message) || !isset($message['type'])) {
                continue;
            }
            
            $formatted_message = array(
                'type' => $message['type'],
                'content' => wp_trim_words($message['content'] ?? '', 20),
                'timestamp' => $message['timestamp'] ?? current_time('Y-m-d H:i:s')
            );
            
            // Add additional context for specific message types
            if ($message['type'] === 'user') {
                if (isset($message['mode'])) {
                    $formatted_message['mode'] = $message['mode'];
                }
                if (isset($message['revision_request'])) {
                    $formatted_message['is_revision'] = true;
                }
            } elseif ($message['type'] === 'assistant') {
                if (isset($message['is_post_content'])) {
                    $formatted_message['generated_content'] = $message['is_post_content'];
                }
                if (isset($message['post_data']['title'])) {
                    $formatted_message['post_title'] = $message['post_data']['title'];
                }
            }
            
            $formatted_messages[] = $formatted_message;
        }
        
        return $formatted_messages;
    }
    
    /**
     * Analyze messages in current session
     * 
     * @param array $messages Session messages
     * @return array Session analysis
     */
    private function analyze_session_messages($messages) {
        $analysis = array();
        
        if (empty($messages)) {
            return $analysis;
        }
        
        $user_messages = 0;
        $assistant_messages = 0;
        $revision_requests = 0;
        $content_generated = 0;
        $modes_used = array();
        
        foreach ($messages as $message) {
            $message_data = json_decode($message->post_content, true);
            
            if (!$message_data || !isset($message_data['type'])) {
                continue;
            }
            
            if ($message_data['type'] === 'user') {
                $user_messages++;
                
                if (isset($message_data['revision_request'])) {
                    $revision_requests++;
                }
                
                if (isset($message_data['mode'])) {
                    $modes_used[] = $message_data['mode'];
                }
            } elseif ($message_data['type'] === 'assistant') {
                $assistant_messages++;
                
                if (isset($message_data['is_post_content']) && $message_data['is_post_content']) {
                    $content_generated++;
                }
            }
        }
        
        $analysis['message_counts'] = array(
            'user' => $user_messages,
            'assistant' => $assistant_messages,
            'total' => $user_messages + $assistant_messages
        );
        
        if ($revision_requests > 0) {
            $analysis['revision_requests'] = $revision_requests;
        }
        
        if ($content_generated > 0) {
            $analysis['content_pieces_generated'] = $content_generated;
        }
        
        if (!empty($modes_used)) {
            $mode_counts = array_count_values($modes_used);
            $analysis['modes_used'] = $mode_counts;
        }
        
        return $analysis;
    }
    
    /**
     * Get session summary
     * 
     * @param WP_Post $session Session post object
     * @param object $chat_history Chat history instance
     * @return array Session summary
     */
    private function get_session_summary($session, $chat_history) {
        $summary = array();
        
        $summary['session_date'] = get_the_date('Y-m-d', $session);
        
        // Get session messages for analysis
        $messages = $chat_history->load_session_history($session);
        
        if (empty($messages)) {
            return $summary;
        }
        
        $summary['message_count'] = count($messages);
        
        // Find generated content titles
        $generated_titles = array();
        foreach ($messages as $message) {
            $message_data = json_decode($message->post_content, true);
            
            if ($message_data && 
                isset($message_data['type']) && 
                $message_data['type'] === 'assistant' &&
                isset($message_data['post_data']['title'])) {
                $generated_titles[] = $message_data['post_data']['title'];
            }
        }
        
        if (!empty($generated_titles)) {
            $summary['generated_content'] = array_slice($generated_titles, 0, 2);
        }
        
        // Get first user message as session topic
        foreach (array_reverse($messages) as $message) {
            $message_data = json_decode($message->post_content, true);
            
            if ($message_data && 
                isset($message_data['type']) && 
                $message_data['type'] === 'user') {
                $summary['initial_topic'] = wp_trim_words($message_data['content'] ?? '', 10);
                break;
            }
        }
        
        return $summary;
    }
    
    /**
     * Get conversation statistics for user
     * 
     * @param int $user_id User ID
     * @param object $chat_history Chat history instance
     * @return array Statistics
     */
    private function get_conversation_statistics($user_id, $chat_history) {
        $stats = array();
        
        // Get recent sessions for analysis (use a larger limit to get more accurate count)
        $recent_sessions = $chat_history->get_user_sessions($user_id, 50);
        $total_sessions = count($recent_sessions);
        $stats['total_sessions'] = $total_sessions;
        
        if ($total_sessions === 0) {
            return $stats;
        }
        
        // Use first 10 sessions for detailed analysis
        $recent_sessions = array_slice($recent_sessions, 0, 10);
        
        if (!empty($recent_sessions)) {
            $total_messages = 0;
            $total_content_pieces = 0;
            
            foreach ($recent_sessions as $session) {
                $messages = $chat_history->load_session_history($session);
                $total_messages += count($messages);
                
                // Count generated content pieces
                foreach ($messages as $message) {
                    $message_data = json_decode($message->post_content, true);
                    if ($message_data && 
                        isset($message_data['type']) && 
                        $message_data['type'] === 'assistant' &&
                        isset($message_data['is_post_content']) &&
                        $message_data['is_post_content']) {
                        $total_content_pieces++;
                    }
                }
            }
            
            $stats['avg_messages_per_session'] = round($total_messages / count($recent_sessions), 1);
            $stats['total_content_generated'] = $total_content_pieces;
        }
        
        return $stats;
    }
    
    /**
     * Analyze user's content type preferences
     * 
     * @param int $user_id User ID
     * @param object $chat_history Chat history instance
     * @return array Content preferences
     */
    private function analyze_content_type_preferences($user_id, $chat_history) {
        $preferences = array();
        
        $recent_sessions = $chat_history->get_user_sessions($user_id, 5);
        
        if (empty($recent_sessions)) {
            return $preferences;
        }
        
        $modes_used = array();
        
        foreach ($recent_sessions as $session) {
            $messages = $chat_history->load_session_history($session);
            
            foreach ($messages as $message) {
                $message_data = json_decode($message->post_content, true);
                
                if ($message_data && 
                    isset($message_data['type']) && 
                    $message_data['type'] === 'user' &&
                    isset($message_data['mode'])) {
                    $modes_used[] = $message_data['mode'];
                }
            }
        }
        
        if (!empty($modes_used)) {
            $mode_counts = array_count_values($modes_used);
            arsort($mode_counts);
            $preferences['preferred_modes'] = array_keys($mode_counts);
            $preferences['mode_usage'] = $mode_counts;
        }
        
        return $preferences;
    }
    
    /**
     * Analyze user's communication style
     * 
     * @param int $user_id User ID
     * @param object $chat_history Chat history instance
     * @return array Communication style analysis
     */
    private function analyze_communication_style($user_id, $chat_history) {
        $style = array();
        
        $recent_sessions = $chat_history->get_user_sessions($user_id, 3);
        
        if (empty($recent_sessions)) {
            return $style;
        }
        
        $user_messages = array();
        $revision_count = 0;
        $total_word_count = 0;
        
        foreach ($recent_sessions as $session) {
            $messages = $chat_history->load_session_history($session);
            
            foreach ($messages as $message) {
                $message_data = json_decode($message->post_content, true);
                
                if ($message_data && isset($message_data['type']) && $message_data['type'] === 'user') {
                    $content = $message_data['content'] ?? '';
                    $user_messages[] = $content;
                    $total_word_count += str_word_count($content);
                    
                    if (isset($message_data['revision_request'])) {
                        $revision_count++;
                    }
                }
            }
        }
        
        if (!empty($user_messages)) {
            $style['avg_message_length'] = round($total_word_count / count($user_messages), 1);
            $style['uses_revisions'] = $revision_count > 0;
            
            if ($revision_count > 0) {
                $style['revision_frequency'] = round($revision_count / count($user_messages), 2);
            }
        }
        
        return $style;
    }
    
    /**
     * Format context as structured text
     * 
     * @param array $context_data Context data
     * @return string Formatted context
     */
    protected function format_as_structured($context_data) {
        $lines = array();
        $lines[] = 'CONVERSATION CONTEXT:';
        
        // Current session info
        if (isset($context_data['current_session'])) {
            $session = $context_data['current_session'];
            
            if (isset($session['message_count'])) {
                $lines[] = '- Current Session: ' . $session['message_count'] . ' messages';
            }
            
            if (isset($session['session_analysis']['content_pieces_generated'])) {
                $lines[] = '- Content Generated This Session: ' . $session['session_analysis']['content_pieces_generated'];
            }
            
            if (isset($session['session_analysis']['modes_used'])) {
                $modes = array_keys($session['session_analysis']['modes_used']);
                $lines[] = '- Modes Used: ' . implode(', ', $modes);
            }
        }
        
        // Recent conversation patterns
        if (isset($context_data['conversation_patterns'])) {
            $patterns = $context_data['conversation_patterns'];
            
            if (isset($patterns['statistics']['total_sessions'])) {
                $lines[] = '- Total Sessions: ' . $patterns['statistics']['total_sessions'];
            }
            
            if (isset($patterns['content_preferences']['preferred_modes'])) {
                $preferred = array_slice($patterns['content_preferences']['preferred_modes'], 0, 2);
                $lines[] = '- Preferred Content Types: ' . implode(', ', $preferred);
            }
            
            if (isset($patterns['communication_style']['uses_revisions']) && $patterns['communication_style']['uses_revisions']) {
                $lines[] = '- User Style: Uses content revisions';
            }
        }
        
        // Recent session topics
        if (isset($context_data['recent_conversations']['recent_sessions'])) {
            $recent_topics = array();
            foreach ($context_data['recent_conversations']['recent_sessions'] as $session) {
                if (isset($session['initial_topic'])) {
                    $recent_topics[] = $session['initial_topic'];
                }
            }
            if (!empty($recent_topics)) {
                $lines[] = '- Recent Topics: ' . implode('; ', array_slice($recent_topics, 0, 2));
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
        // Conversation context requires user to be logged in and chat history to be available
        return is_user_logged_in() && 
               function_exists('cronicle_chat_history') && 
               parent::is_available($options);
    }
}