<?php
/**
 * Shortcodes for AI Quiz System.
 */
class AI_Quiz_System_Shortcodes {

    /**
     * Register all shortcodes.
     */
    public function register_shortcodes() {
        add_shortcode('ai_quiz', array($this, 'quiz_shortcode'));
        add_shortcode('ai_performance', array($this, 'performance_shortcode'));
        add_shortcode('ai_chatbot', array($this, 'chatbot_shortcode'));
    }

    /**
     * Shortcode for displaying the quiz interface.
     */
    public function quiz_shortcode($atts) {
        $attributes = shortcode_atts(
            array(
                'exam_id' => 0,
                'show_title' => 'yes',
                'show_description' => 'yes',
                'question_source' => '' // New parameter for question source
            ),
            $atts
        );
        
        $exam_id = intval($attributes['exam_id']);
        $show_title = $attributes['show_title'] === 'yes';
        $show_description = $attributes['show_description'] === 'yes';
        $question_source = sanitize_text_field($attributes['question_source']);
        
        // Get exams
        $db = new AI_Quiz_System_DB();
        $exams = $db->get_exams();
        
        // Start output buffering
        ob_start();
        
        // Store the original question source setting
        $original_question_source = null;
        
        // Override global question source setting if specified in shortcode
        if (!empty($question_source) && in_array($question_source, array('ai', 'stored', 'demo'))) {
            $ai_settings = get_option('aiqs_ai_settings', array());
            $original_question_source = isset($ai_settings['question_source']) ? $ai_settings['question_source'] : null;
            
            // Temporarily modify the global setting
            $ai_settings['question_source'] = $question_source;
            update_option('aiqs_ai_settings', $ai_settings);
            
            // Add a hidden field to indicate we're using a custom source
            echo '<input type="hidden" id="aiqs-custom-source" data-source="' . esc_attr($question_source) . '" />';
        }
        
        // Include template
        include AIQS_PLUGIN_DIR . 'public/partials/quiz.php';
        
        // Restore original question source setting if we changed it
        if ($original_question_source !== null) {
            $ai_settings = get_option('aiqs_ai_settings', array());
            $ai_settings['question_source'] = $original_question_source;
            update_option('aiqs_ai_settings', $ai_settings);
        }
        
        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Shortcode for displaying performance tracking.
     */
    public function performance_shortcode($atts) {
        $attributes = shortcode_atts(
            array(
                'exam_id' => 0,
                'show_title' => 'yes'
            ),
            $atts
        );
        
        $exam_id = intval($attributes['exam_id']);
        $show_title = $attributes['show_title'] === 'yes';
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="aiqs-login-required">' . 
                __('Please log in to view your performance data.', 'ai-quiz-system') . 
                '</div>';
        }
        
        // Get exams
        $db = new AI_Quiz_System_DB();
        $exams = $db->get_exams();
        
        // Get performance data if exam is specified
        $performance = array();
        $attempts = array();
        
        if ($exam_id > 0) {
            $performance = $db->get_user_performance(get_current_user_id(), $exam_id);
        } else {
            $performance = $db->get_user_performance(get_current_user_id());
        }
        
        $attempts = $db->get_user_attempts(get_current_user_id());
        
        // Start output buffering
        ob_start();
        
        // Include template
        include AIQS_PLUGIN_DIR . 'public/partials/performance.php';
        
        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Shortcode for displaying the AI chatbot.
     */
    public function chatbot_shortcode($atts) {
        $attributes = shortcode_atts(
            array(
                'title' => __('Rita - AI Study Assistant', 'ai-quiz-system'),
                'placeholder' => __('Ask Rita about your studies...', 'ai-quiz-system'),
                'welcome_message' => __('Hi! I\'m Rita, your AI study assistant. How can I help you today?', 'ai-quiz-system')
            ),
            $atts
        );
        
        $title = sanitize_text_field($attributes['title']);
        $placeholder = sanitize_text_field($attributes['placeholder']);
        $welcome_message = sanitize_text_field($attributes['welcome_message']);
        
        // Check if chatbot is enabled
        $ai_settings = get_option('aiqs_ai_settings');
        
        if (empty($ai_settings['enable_chatbot'])) {
            return '<div class="aiqs-notice">' . 
                __('The AI chatbot is currently disabled.', 'ai-quiz-system') . 
                '</div>';
        }
        
        // Generate a unique session ID for non-logged-in users
        $session_id = '';
        if (!is_user_logged_in()) {
            if (isset($_COOKIE['aiqs_chatbot_session'])) {
                $session_id = sanitize_text_field($_COOKIE['aiqs_chatbot_session']);
            } else {
                $session_id = 'chat_' . uniqid();
                setcookie('aiqs_chatbot_session', $session_id, time() + 86400, '/'); // 24 hours
            }
        }
        
        // Start output buffering
        ob_start();
        
        // Include template
        include AIQS_PLUGIN_DIR . 'public/partials/chatbot.php';
        
        // Return the buffered content
        return ob_get_clean();
    }
}