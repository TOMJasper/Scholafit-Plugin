<?php
/**
 * AI Chatbot functionality.
 */
class AI_Quiz_System_Chatbot {

    /**
     * AI Engine instance.
     */
    private $ai_engine;
    
    /**
     * Database instance.
     */
    private $db;
    
    /**
     * Initialize the chatbot.
     */
    public function __construct() {
        $this->ai_engine = new AI_Quiz_System_Engine();
        $this->db = new AI_Quiz_System_DB();
    }
    
    /**
     * Process a user query.
     */
    public function process_query($query, $user_id = null, $session_id = null) {
        if (!$this->ai_engine->is_configured()) {
            return [
                'success' => false,
                'message' => __('AI is not configured.', 'ai-quiz-system')
            ];
        }
        
        // Get user data if logged in
        $user_data = null;
        if ($user_id) {
            $user_data = get_userdata($user_id);
        }
        
        // Get performance data if logged in
        $performance_data = [];
        if ($user_id) {
            $performance_data = $this->db->get_user_performance($user_id);
        }
        
        // Process query with AI
        $response = $this->ai_engine->process_chatbot_query($query, $user_data, $performance_data);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }
        
        // Record interaction
        if ($user_id || $session_id) {
            $this->db->record_chatbot_interaction($user_id, $session_id, $query, $response);
        }
        
        return [
            'success' => true,
            'response' => $response
        ];
    }
    
    /**
     * Get chat history for a user or session.
     */
    public function get_history($user_id = null, $session_id = null, $limit = 50) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'aiqs_chatbot_history';
        $sql = "SELECT * FROM $table WHERE 1=1";
        $params = [];
        
        if ($user_id) {
            $sql .= " AND user_id = %d";
            $params[] = $user_id;
        } elseif ($session_id) {
            $sql .= " AND session_id = %s";
            $params[] = $session_id;
        } else {
            return [];
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;
        
        $history = $wpdb->get_results(
            $wpdb->prepare($sql, $params)
        );
        
        // Reverse to get chronological order
        return array_reverse($history);
    }
    
    /**
     * Generate a greeting based on user data and time.
     */
    public function generate_greeting($user_data = null) {
        $hour = (int) current_time('G');
        $greeting = '';
        
        if ($hour >= 5 && $hour < 12) {
            $greeting = __('Good morning', 'ai-quiz-system');
        } elseif ($hour >= 12 && $hour < 18) {
            $greeting = __('Good afternoon', 'ai-quiz-system');
        } else {
            $greeting = __('Good evening', 'ai-quiz-system');
        }
        
        if ($user_data && !empty($user_data->display_name)) {
            $greeting .= ', ' . $user_data->display_name;
        }
        
        $greeting .= '! ' . __('I\'m Rita, your AI study assistant. How can I help you today?', 'ai-quiz-system');
        
        return $greeting;
    }
}