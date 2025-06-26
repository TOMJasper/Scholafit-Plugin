<?php
/**
 * Enhanced Public Class with Fixed AJAX and Conversational AI
 * Replace your existing public/class-ai-quiz-system-public.php
 */

class AI_Quiz_System_Public {

    public function __construct() {
        // Constructor code
    }

    /**
     * Register the stylesheets for the public-facing side.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'aiqs-public', 
            AIQS_PLUGIN_URL . 'public/css/ai-quiz-system-public.css', 
            array(), 
            AIQS_VERSION
        );
    }

    /**
     * Register the JavaScript for the public-facing side.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'aiqs-public', 
            AIQS_PLUGIN_URL . 'public/js/ai-quiz-system-public.js', 
            array('jquery'), 
            AIQS_VERSION,
            true
        );
        
        // Add localized script data with enhanced debugging
        wp_localize_script('aiqs-public', 'aiqs_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => esc_url_raw(rest_url('ai-quiz-system/v1')),
            'nonce' => wp_create_nonce('aiqs_public_nonce'),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'plugin_url' => AIQS_PLUGIN_URL,
            'debug' => WP_DEBUG,
            'current_user_id' => get_current_user_id(),
            'i18n' => array(
                'loading' => __('Loading...', 'ai-quiz-system'),
                'please_wait' => __('Please wait...', 'ai-quiz-system'),
                'time_up' => __('Time is up!', 'ai-quiz-system'),
                'confirm_submit' => __('Are you sure you want to submit your quiz?', 'ai-quiz-system'),
                'error_occurred' => __('An error occurred. Please try again.', 'ai-quiz-system'),
                'network_error' => __('Network error. Please check your connection.', 'ai-quiz-system'),
                'no_subjects' => __('No subjects found for this exam.', 'ai-quiz-system'),
                'select_exam' => __('Please select an exam first.', 'ai-quiz-system'),
                'select_subjects' => __('Please select at least one subject.', 'ai-quiz-system')
            )
        ));
    }

    /**
     * FIXED: Get exam subjects for frontend
     */
    public function get_exam_subjects() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aiqs_public_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'ai-quiz-system')]);
            return;
        }
        
        $exam_id = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : 0;
        
        aiqs_debug_log('Frontend get_exam_subjects called', ['exam_id' => $exam_id]);
        
        if ($exam_id <= 0) {
            wp_send_json_error(['message' => __('Invalid exam ID.', 'ai-quiz-system')]);
            return;
        }
        
        $db = new AI_Quiz_System_DB();
        $subjects = $db->get_exam_subjects($exam_id);
        
        aiqs_debug_log('Subjects retrieved', ['count' => count($subjects)]);
        
        if (empty($subjects)) {
            wp_send_json_error(['message' => __('No subjects found for this exam.', 'ai-quiz-system')]);
            return;
        }
        
        // Format subjects for frontend
        $formatted_subjects = array_map(function($subject) {
            return [
                'id' => $subject->id,
                'name' => $subject->name,
                'description' => $subject->description ?? ''
            ];
        }, $subjects);
        
        wp_send_json_success($formatted_subjects);
    }

    /**
     * ENHANCED: Start quiz with better error handling
     */
    public function start_quiz() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aiqs_public_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'ai-quiz-system')]);
            return;
        }

        $exam_id = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : 0;
        $subject_ids = isset($_POST['subject_ids']) ? array_map('intval', $_POST['subject_ids']) : [];
        $question_source = isset($_POST['question_source']) ? sanitize_text_field($_POST['question_source']) : '';

        aiqs_debug_log('Start quiz called', [
            'exam_id' => $exam_id,
            'subject_ids' => $subject_ids,
            'question_source' => $question_source
        ]);

        if (!$exam_id || empty($subject_ids)) {
            wp_send_json_error(['message' => __('Please select an exam and at least one subject.', 'ai-quiz-system')]);
            return;
        }

        $db = new AI_Quiz_System_DB();
        
        // Validate exam exists
        $exam = $db->get_exam($exam_id);
        if (!$exam) {
            wp_send_json_error(['message' => __('Exam not found.', 'ai-quiz-system')]);
            return;
        }

        // Generate session ID
        $session_id = wp_generate_uuid4();
        $user_id = get_current_user_id();

        // Get AI settings for question source
        $ai_settings = get_option('aiqs_ai_settings', array());
        $default_source = $ai_settings['question_source'] ?? 'stored';
        $fallback_enabled = $ai_settings['fallback_to_stored'] ?? true;
        
        // Use provided source or fall back to default
        $source = !empty($question_source) ? $question_source : $default_source;
        
        $questions = [];
        $ai_engine = new AI_Quiz_System_Engine();
        
        // Try to get questions based on selected source
        if ($source === 'ai' && $ai_engine->is_configured()) {
            foreach ($subject_ids as $subject_id) {
                $subject = $db->get_subject($subject_id);
                if ($subject) {
                    $ai_questions = $ai_engine->generate_questions(
                        $subject->name,
                        $exam->question_count_per_subject,
                        'mixed'
                    );
                    
                    if (!is_wp_error($ai_questions) && !empty($ai_questions)) {
                        // Add subject_id to each question
                        foreach ($ai_questions as &$question) {
                            $question['subject_id'] = $subject_id;
                            $question['id'] = 'ai_' . uniqid(); // Temporary ID for AI questions
                        }
                        $questions = array_merge($questions, $ai_questions);
                    }
                }
            }
            
            // Fallback to stored questions if AI generation failed
            if (empty($questions) && $fallback_enabled) {
                aiqs_debug_log('AI generation failed, falling back to stored questions');
                $source = 'stored';
            }
        }
        
        // Get stored questions (either as primary source or fallback)
        if ($source === 'stored' || empty($questions)) {
            foreach ($subject_ids as $subject_id) {
                $stored_questions = $db->get_subject_questions(
                    $subject_id,
                    $exam->question_count_per_subject,
                    'all' // Get all sources
                );
                
                if (!empty($stored_questions)) {
                    $questions = array_merge($questions, $stored_questions);
                }
            }
        }
        
        // Final fallback to demo questions
        if (empty($questions)) {
            aiqs_debug_log('No stored questions found, using demo questions');
            foreach ($subject_ids as $subject_id) {
                $subject = $db->get_subject($subject_id);
                if ($subject) {
                    $demo_questions = $ai_engine->generate_demo_questions(
                        $subject->name,
                        $exam->question_count_per_subject,
                        'mixed'
                    );
                    
                    if (!empty($demo_questions)) {
                        // Add subject_id to each question
                        foreach ($demo_questions as &$question) {
                            $question['subject_id'] = $subject_id;
                            $question['id'] = 'demo_' . uniqid(); // Temporary ID for demo questions
                        }
                        $questions = array_merge($questions, $demo_questions);
                    }
                }
            }
        }

        if (empty($questions)) {
            wp_send_json_error(['message' => __('No questions available for the selected subjects.', 'ai-quiz-system')]);
            return;
        }

        // Shuffle questions for randomness
        shuffle($questions);

        // Create quiz session data
        $session_data = [
            'session_id' => $session_id,
            'user_id' => $user_id ?: null,
            'exam_id' => $exam_id,
            'subject_ids' => $subject_ids,
            'questions' => json_encode($questions),
            'current_question' => 0,
            'start_time' => current_time('mysql'),
            'answers' => json_encode([]),
            'status' => 'ongoing'
        ];

        // Store session in transient (for 2 hours)
        set_transient('aiqs_quiz_' . $session_id, $session_data, 7200);

        // Also create attempt record in database
        $attempt_id = $db->create_quiz_attempt($user_id, $exam_id, $session_id, $_SERVER['REMOTE_ADDR'] ?? '');

        if (!$attempt_id) {
            wp_send_json_error(['message' => __('Failed to create quiz session.', 'ai-quiz-system')]);
            return;
        }

        aiqs_debug_log('Quiz session created', [
            'session_id' => $session_id,
            'attempt_id' => $attempt_id,
            'questions_count' => count($questions),
            'source' => $source
        ]);

        // Return first question
        wp_send_json_success([
            'session_id' => $session_id,
            'exam' => [
                'id' => $exam->id,
                'name' => $exam->name,
                'time_limit' => $exam->time_limit
            ],
            'total_questions' => count($questions),
            'current_question' => 1,
            'question' => $questions[0],
            'question_source' => $source
        ]);
    }

    /**
     * ENHANCED: Submit answer with better session handling
     */
    public function submit_answer() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aiqs_public_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'ai-quiz-system')]);
            return;
        }

        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $question_id = isset($_POST['question_id']) ? sanitize_text_field($_POST['question_id']) : '';
        $answer = isset($_POST['answer']) ? sanitize_text_field($_POST['answer']) : '';
        $time_spent = isset($_POST['time_spent']) ? intval($_POST['time_spent']) : 0;

        aiqs_debug_log('Submit answer called', [
            'session_id' => $session_id,
            'question_id' => $question_id,
            'answer' => $answer
        ]);

        if (!$session_id || !$question_id || !$answer) {
            wp_send_json_error(['message' => __('Invalid parameters.', 'ai-quiz-system')]);
            return;
        }

        // Get session data
        $session_data = get_transient('aiqs_quiz_' . $session_id);
        if (!$session_data) {
            wp_send_json_error(['message' => __('Quiz session expired.', 'ai-quiz-system')]);
            return;
        }

        $questions = json_decode($session_data['questions'], true);
        $answers = json_decode($session_data['answers'], true) ?: [];
        
        // Calculate results
        $total_questions = count($questions);
        $answered_questions = count($answers);
        $correct_count = 0;
        
        foreach ($answers as $answer) {
            if ($answer['is_correct']) {
                $correct_count++;
            }
        }
        
        $score = $total_questions > 0 ? round(($correct_count / $total_questions) * 100, 2) : 0;
        
        // Calculate time taken
        $start_time = strtotime($session_data['start_time']);
        $end_time = time();
        $time_taken = $end_time - $start_time;

        // Update database attempt record
        $db = new AI_Quiz_System_DB();
        $db->complete_quiz_attempt(
            $session_data['attempt_id'] ?? 0,
            $score,
            $total_questions,
            $correct_count
        );

        // Update performance tracking for logged-in users
        if ($session_data['user_id']) {
            foreach ($session_data['subject_ids'] as $subject_id) {
                $db->update_performance(
                    $session_data['user_id'],
                    $session_data['exam_id'],
                    $subject_id,
                    $session_data['attempt_id'] ?? 0
                );
            }
        }

        // Clean up session
        delete_transient('aiqs_quiz_' . $session_id);

        aiqs_debug_log('Quiz completed', [
            'session_id' => $session_id,
            'score' => $score,
            'correct_count' => $correct_count,
            'total_questions' => $total_questions
        ]);

        wp_send_json_success([
            'score' => $score,
            'correct_answers' => $correct_count,
            'total_questions' => $total_questions,
            'answered_questions' => $answered_questions,
            'time_taken' => $time_taken,
            'passed' => $score >= 60, // Configurable pass threshold
            'detailed_results' => $answers
        ]);
    }

    /**
     * ENHANCED: Conversational AI chatbot query
     */
    public function chatbot_query() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aiqs_public_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'ai-quiz-system')]);
            return;
        }

        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';

        aiqs_debug_log('Chatbot query received', [
            'query_length' => strlen($query),
            'session_id' => $session_id,
            'user_id' => get_current_user_id()
        ]);

        if (!$query) {
            wp_send_json_error(['message' => __('Please enter a message.', 'ai-quiz-system')]);
            return;
        }

        $user_id = get_current_user_id();
        
        // Initialize enhanced AI engine
        $ai_engine = new AI_Quiz_System_Engine();
        
        // Process conversational query with memory and personalization
        $result = $ai_engine->process_conversational_query($query, $user_id, $session_id);

        if (is_array($result) && $result['success']) {
            wp_send_json_success([
                'response' => $result['response'],
                'conversation_id' => $result['conversation_id'] ?? null,
                'student_insights' => $result['student_insights'] ?? [],
                'recommendations' => $result['recommendations'] ?? []
            ]);
        } else {
            // Fallback response
            $fallback_response = $ai_engine->generate_fallback_response($query);
            wp_send_json_success([
                'response' => $fallback_response,
                'is_fallback' => true
            ]);
        }
    }

    /**
     * Get user performance data
     */
    public function get_performance() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aiqs_public_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'ai-quiz-system')]);
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in to view performance data.', 'ai-quiz-system')]);
            return;
        }

        $user_id = get_current_user_id();
        $exam_id = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : null;

        $db = new AI_Quiz_System_DB();
        $performance_data = $db->get_user_performance($user_id, $exam_id);

        wp_send_json_success([
            'performance' => $performance_data,
            'user_id' => $user_id
        ]);
    }

    /**
     * Get conversation history for chatbot
     */
    public function get_conversation_history() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aiqs_public_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'ai-quiz-system')]);
            return;
        }

        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $user_id = get_current_user_id();

        if (!$user_id && !$session_id) {
            wp_send_json_error(['message' => __('No session information available.', 'ai-quiz-system')]);
            return;
        }

        global $wpdb;
        
        $history = [];
        
        if ($user_id) {
            // Get recent conversations for logged-in user
            $conversations = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT c.id, c.conversation_title, c.last_message_at,
                            COUNT(m.id) as message_count
                     FROM {$wpdb->prefix}aiqs_conversations c
                     LEFT JOIN {$wpdb->prefix}aiqs_conversation_messages m ON c.id = m.conversation_id
                     WHERE c.user_id = %d AND c.status = 'active'
                     GROUP BY c.id
                     ORDER BY c.last_message_at DESC
                     LIMIT 5",
                    $user_id
                ),
                ARRAY_A
            );
            
            $history['conversations'] = $conversations;
            
            // Get recent messages from current conversation
            if (!empty($conversations)) {
                $recent_messages = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT user_message, ai_response, timestamp
                         FROM {$wpdb->prefix}aiqs_conversation_messages
                         WHERE conversation_id = %d
                         ORDER BY timestamp DESC
                         LIMIT 10",
                        $conversations[0]['id']
                    ),
                    ARRAY_A
                );
                
                $history['recent_messages'] = array_reverse($recent_messages);
            }
        }

        wp_send_json_success($history);
    }

    /**
     * Get study recommendations for user
     */
    public function get_study_recommendations() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aiqs_public_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'ai-quiz-system')]);
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in to view recommendations.', 'ai-quiz-system')]);
            return;
        }

        $user_id = get_current_user_id();
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;

        global $wpdb;
        
        $recommendations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.*, s.name as subject_name
                 FROM {$wpdb->prefix}aiqs_study_recommendations r
                 LEFT JOIN {$wpdb->prefix}aiqs_subjects s ON r.subject_id = s.id
                 WHERE r.user_id = %d AND r.status = 'pending'
                 AND (r.expires_at IS NULL OR r.expires_at > NOW())
                 ORDER BY 
                    CASE r.priority_level 
                        WHEN 'high' THEN 3 
                        WHEN 'medium' THEN 2 
                        ELSE 1 
                    END DESC,
                    r.generated_date DESC
                 LIMIT %d",
                $user_id, $limit
            ),
            ARRAY_A
        );

        wp_send_json_success([
            'recommendations' => $recommendations,
            'count' => count($recommendations)
        ]);
    }

    /**
     * Mark recommendation as completed
     */
    public function complete_recommendation() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aiqs_public_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'ai-quiz-system')]);
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'ai-quiz-system')]);
            return;
        }

        $recommendation_id = isset($_POST['recommendation_id']) ? intval($_POST['recommendation_id']) : 0;
        $feedback = isset($_POST['feedback']) ? sanitize_textarea_field($_POST['feedback']) : '';
        $effectiveness_score = isset($_POST['effectiveness_score']) ? floatval($_POST['effectiveness_score']) : 3.0;

        if (!$recommendation_id) {
            wp_send_json_error(['message' => __('Invalid recommendation ID.', 'ai-quiz-system')]);
            return;
        }

        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'aiqs_study_recommendations',
            [
                'status' => 'completed',
                'completed_date' => current_time('mysql'),
                'user_feedback' => $feedback,
                'effectiveness_score' => $effectiveness_score
            ],
            [
                'id' => $recommendation_id,
                'user_id' => get_current_user_id()
            ]
        );

        if ($result !== false) {
            wp_send_json_success(['message' => __('Recommendation marked as completed.', 'ai-quiz-system')]);
        } else {
            wp_send_json_error(['message' => __('Failed to update recommendation.', 'ai-quiz-system')]);
        }
    }

    /**
     * Register REST API endpoints (enhanced)
     */
    public function register_rest_routes() {
        // Quiz routes
        register_rest_route('ai-quiz-system/v1', '/quiz/start', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_start_quiz'],
            'permission_callback' => '__return_true',
            'args' => [
                'exam_id' => ['required' => true, 'type' => 'integer'],
                'subject_ids' => ['required' => true, 'type' => 'array'],
                'question_source' => ['required' => false, 'type' => 'string']
            ]
        ]);

        // Chatbot routes
        register_rest_route('ai-quiz-system/v1', '/chatbot/query', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_chatbot_query'],
            'permission_callback' => '__return_true',
            'args' => [
                'query' => ['required' => true, 'type' => 'string'],
                'session_id' => ['required' => false, 'type' => 'string']
            ]
        ]);

        // Performance routes
        register_rest_route('ai-quiz-system/v1', '/performance', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_performance'],
            'permission_callback' => function() {
                return is_user_logged_in();
            }
        ]);

        // Recommendations routes
        register_rest_route('ai-quiz-system/v1', '/recommendations', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_recommendations'],
            'permission_callback' => function() {
                return is_user_logged_in();
            }
        ]);
    }

    /**
     * REST API wrapper for start quiz
     */
    public function rest_start_quiz($request) {
        $_POST['exam_id'] = $request->get_param('exam_id');
        $_POST['subject_ids'] = $request->get_param('subject_ids');
        $_POST['question_source'] = $request->get_param('question_source');
        $_POST['nonce'] = wp_create_nonce('aiqs_public_nonce');
        
        ob_start();
        $this->start_quiz();
        $output = ob_get_clean();
        
        return json_decode($output, true);
    }

    /**
     * REST API wrapper for chatbot
     */
    public function rest_chatbot_query($request) {
        $_POST['query'] = $request->get_param('query');
        $_POST['session_id'] = $request->get_param('session_id');
        $_POST['nonce'] = wp_create_nonce('aiqs_public_nonce');
        
        ob_start();
        $this->chatbot_query();
        $output = ob_get_clean();
        
        return json_decode($output, true);
    }

    /**
     * REST API wrapper for performance
     */
    public function rest_get_performance($request) {
        $_POST['exam_id'] = $request->get_param('exam_id');
        $_POST['nonce'] = wp_create_nonce('aiqs_public_nonce');
        
        ob_start();
        $this->get_performance();
        $output = ob_get_clean();
        
        return json_decode($output, true);
    }

    /**
     * REST API wrapper for recommendations
     */
    public function rest_get_recommendations($request) {
        $_POST['limit'] = $request->get_param('limit') ?: 5;
        $_POST['nonce'] = wp_create_nonce('aiqs_public_nonce');
        
        ob_start();
        $this->get_study_recommendations();
        $output = ob_get_clean();
        
        return json_decode($output, true);
    }
}Quiz session expired. Please start again.', 'ai-quiz-system')]);
            return;
        }

        $questions = json_decode($session_data['questions'], true);
        $answers = json_decode($session_data['answers'], true) ?: [];
        $current_index = $session_data['current_question'];

        if (!isset($questions[$current_index])) {
            wp_send_json_error(['message' => __('Question not found.', 'ai-quiz-system')]);
            return;
        }

        $current_question = $questions[$current_index];
        $correct_answer = $current_question['correct_answer'];
        $is_correct = (strtoupper($answer) === strtoupper($correct_answer));

        // Record answer
        $answer_data = [
            'question_id' => $question_id,
            'answer' => $answer,
            'correct_answer' => $correct_answer,
            'is_correct' => $is_correct,
            'time_spent' => $time_spent,
            'timestamp' => current_time('mysql')
        ];

        $answers[] = $answer_data;

        // Move to next question
        $next_index = $current_index + 1;
        $has_next = isset($questions[$next_index]);

        // Update session data
        $session_data['current_question'] = $next_index;
        $session_data['answers'] = json_encode($answers);
        set_transient('aiqs_quiz_' . $session_id, $session_data, 7200);

        aiqs_debug_log('Answer submitted', [
            'is_correct' => $is_correct,
            'has_next' => $has_next,
            'next_index' => $next_index
        ]);

        // Prepare response
        $response_data = [
            'is_correct' => $is_correct,
            'correct_answer' => $correct_answer,
            'explanation' => $current_question['explanation'] ?? '',
            'has_next' => $has_next,
            'current_question' => $next_index + 1,
            'total_questions' => count($questions)
        ];

        // Include next question if available
        if ($has_next) {
            $response_data['next_question'] = $questions[$next_index];
        }

        wp_send_json_success($response_data);
    }

    /**
     * ENHANCED: Finish quiz with comprehensive results
     */
    public function finish_quiz() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aiqs_public_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'ai-quiz-system')]);
            return;
        }

        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';

        if (!$session_id) {
            wp_send_json_error(['message' => __('Invalid session ID.', 'ai-quiz-system')]);
            return;
        }

        // Get session data
        $session_data = get_transient('aiqs_quiz_' . $session_id);
        if (!$session_data) {
            wp_send_json_error(['message' => __('
