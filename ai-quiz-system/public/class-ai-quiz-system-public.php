<?php
/**
 * The public-facing functionality of the plugin.
 */
class AI_Quiz_System_Public {

    /**
     * Initialize the class and set properties.
     */
    public function __construct() {
        // Constructor code
    }

    /**
     * Register the stylesheets for the public-facing side.
     */
    public function enqueue_styles() {
        // Enqueue CSS with a version timestamp for cache busting
        wp_enqueue_style(
            'aiqs-public', 
            AIQS_PLUGIN_URL . 'public/css/ai-quiz-system-public.css', 
            array(), 
            AIQS_VERSION . '.' . time()  // Use timestamp for cache busting during development
        );
    }

    /**
     * Register the JavaScript for the public-facing side.
     */
    public function enqueue_scripts() {
        // Enqueue JS with a version timestamp for cache busting
        wp_enqueue_script(
            'aiqs-public', 
            AIQS_PLUGIN_URL . 'public/js/ai-quiz-system-public.js', 
            array('jquery'), 
            AIQS_VERSION . '.' . time(),  // Use timestamp for cache busting during development
            true
        );
        
        // Add localized script data
        wp_localize_script('aiqs-public', 'aiqs_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => esc_url_raw(rest_url('ai-quiz-system/v1')),
            'nonce' => wp_create_nonce('aiqs_public_nonce'),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'plugin_url' => AIQS_PLUGIN_URL,
            'debug' => WP_DEBUG,
            'i18n' => array(
                'loading' => __('Loading...', 'ai-quiz-system'),
                'please_wait' => __('Please wait...', 'ai-quiz-system'),
                'time_up' => __('Time is up!', 'ai-quiz-system'),
                'confirm_submit' => __('Are you sure you want to submit your quiz? You can\'t go back after submission.', 'ai-quiz-system'),
                'error_occurred' => __('An error occurred. Please try again.', 'ai-quiz-system'),
                'network_error' => __('Network error. Please check your connection.', 'ai-quiz-system')
            )
        ));
    }

    /**
     * Register REST API routes for the public-facing functionality.
     */
    public function register_rest_routes() {
        // Quiz routes
        register_rest_route('ai-quiz-system/v1', '/quiz/start', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_start_quiz'),
            'permission_callback' => '__return_true',
            'args' => array(
                'exam_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ),
                'subject_ids' => array(
                    'required' => true,
                    'type' => 'array'
                ),
                'question_source' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        register_rest_route('ai-quiz-system/v1', '/quiz/submit-answer', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_submit_answer'),
            'permission_callback' => '__return_true',
            'args' => array(
                'session_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'question_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ),
                'answer' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'time_spent' => array(
                    'required' => false,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        register_rest_route('ai-quiz-system/v1', '/quiz/finish', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_finish_quiz'),
            'permission_callback' => '__return_true',
            'args' => array(
                'session_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // Performance routes
        register_rest_route('ai-quiz-system/v1', '/performance', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_performance'),
            'permission_callback' => function() {
                return is_user_logged_in();
            },
            'args' => array(
                'exam_id' => array(
                    'required' => false,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // Chatbot routes
        register_rest_route('ai-quiz-system/v1', '/chatbot/query', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_chatbot_query'),
            'permission_callback' => '__return_true',
            'args' => array(
                'query' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'session_id' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
    }

    /**
     * API: Start a quiz.
     */
    public function api_start_quiz($request) {
        $exam_id = $request->get_param('exam_id');
        $subject_ids = $request->get_param('subject_ids');
        $question_source = $request->get_param('question_source');

        if (!$exam_id || empty($subject_ids)) {
            return new WP_Error('invalid_params', __('Invalid parameters.', 'ai-quiz-system'), array('status' => 400));
        }

        $db = new AI_Quiz_System_DB();
        
        // Validate exam exists
        $exam = $db->get_exam($exam_id);
        if (!$exam) {
            return new WP_Error('exam_not_found', __('Exam not found.', 'ai-quiz-system'), array('status' => 404));
        }

        // Convert subject IDs to integers and validate
        $subject_ids = array_map('absint', $subject_ids);
        $subject_ids = array_filter($subject_ids);

        if (empty($subject_ids)) {
            return new WP_Error('no_subjects', __('No valid subjects selected.', 'ai-quiz-system'), array('status' => 400));
        }

        // Generate session ID
        $session_id = wp_generate_uuid4();
        $user_id = get_current_user_id();

        // Get questions based on source preference and fallback settings
        $ai_settings = get_option('aiqs_ai_settings', array());
        $default_source = isset($ai_settings['question_source']) ? $ai_settings['question_source'] : 'stored';
        $fallback_enabled = isset($ai_settings['fallback_to_stored']) ? $ai_settings['fallback_to_stored'] : true;
        
        // Use provided source or fall back to default
        $source = !empty($question_source) ? $question_source : $default_source;
        
        $questions = array();
        
        // Try to get questions based on selected source
        if ($source === 'ai_generated') {
            $ai_engine = new AI_Quiz_System_Engine();
            if ($ai_engine->is_configured()) {
                foreach ($subject_ids as $subject_id) {
                    $subject = $db->get_subject($subject_id);
                    if ($subject) {
                        $ai_questions = $ai_engine->generate_questions(
                            $subject->name,
                            $exam->questions_per_subject,
                            $exam->difficulty_mix
                        );
                        
                        if (!is_wp_error($ai_questions) && !empty($ai_questions)) {
                            $questions = array_merge($questions, $ai_questions);
                        }
                    }
                }
            }
            
            // Fallback to stored questions if AI generation failed and fallback is enabled
            if (empty($questions) && $fallback_enabled) {
                aiqs_debug_log('AI generation failed, falling back to stored questions');
                $source = 'stored';
            }
        }
        
        // Get stored questions (either as primary source or fallback)
        if ($source === 'stored' || empty($questions)) {
            foreach ($subject_ids as $subject_id) {
                $stored_questions = $db->get_questions_by_subject(
                    $subject_id,
                    $exam->questions_per_subject,
                    $exam->difficulty_mix
                );
                
                if (!empty($stored_questions)) {
                    $questions = array_merge($questions, $stored_questions);
                }
            }
        }
        
        // Final fallback to demo questions if no questions found
        if (empty($questions)) {
            aiqs_debug_log('No stored questions found, using demo questions');
            $ai_engine = new AI_Quiz_System_Engine();
            foreach ($subject_ids as $subject_id) {
                $subject = $db->get_subject($subject_id);
                if ($subject) {
                    $demo_questions = $ai_engine->generate_demo_questions(
                        $subject->name,
                        $exam->questions_per_subject,
                        $exam->difficulty_mix
                    );
                    
                    if (!empty($demo_questions)) {
                        $questions = array_merge($questions, $demo_questions);
                    }
                }
            }
        }

        if (empty($questions)) {
            return new WP_Error('no_questions', __('No questions available for the selected subjects.', 'ai-quiz-system'), array('status' => 404));
        }

        // Shuffle questions
        shuffle($questions);

        // Limit to max questions
        if (count($questions) > $exam->max_questions) {
            $questions = array_slice($questions, 0, $exam->max_questions);
        }

        // Create quiz session
        $session_data = array(
            'session_id' => $session_id,
            'user_id' => $user_id ?: null,
            'exam_id' => $exam_id,
            'subject_ids' => $subject_ids,
            'questions' => $questions,
            'current_question' => 0,
            'start_time' => current_time('mysql'),
            'time_limit' => $exam->time_limit,
            'answers' => array(),
            'question_source' => $source
        );

        // Store session in database
        $session_stored = $db->create_quiz_session($session_data);
        
        if (!$session_stored) {
            return new WP_Error('session_error', __('Failed to create quiz session.', 'ai-quiz-system'), array('status' => 500));
        }

        aiqs_debug_log('Quiz session created', array(
            'session_id' => $session_id,
            'exam_id' => $exam_id,
            'questions_count' => count($questions),
            'source' => $source
        ));

        // Return quiz data
        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'session_id' => $session_id,
                'exam' => $exam,
                'total_questions' => count($questions),
                'current_question' => 1,
                'question' => $questions[0],
                'time_limit' => $exam->time_limit,
                'question_source' => $source
            )
        ));
    }

    /**
     * API: Submit an answer.
     */
    public function api_submit_answer($request) {
        $session_id = $request->get_param('session_id');
        $question_id = $request->get_param('question_id');
        $answer = $request->get_param('answer');
        $time_spent = $request->get_param('time_spent');

        if (!$session_id || !$question_id || !$answer) {
            return new WP_Error('invalid_params', __('Invalid parameters.', 'ai-quiz-system'), array('status' => 400));
        }

        $db = new AI_Quiz_System_DB();
        
        // Get quiz session
        $session = $db->get_quiz_session($session_id);
        if (!$session) {
            return new WP_Error('session_not_found', __('Quiz session not found.', 'ai-quiz-system'), array('status' => 404));
        }

        // Validate question
        $questions = json_decode($session->questions, true);
        $current_question_index = $session->current_question;
        
        if (!isset($questions[$current_question_index])) {
            return new WP_Error('question_not_found', __('Question not found.', 'ai-quiz-system'), array('status' => 404));
        }

        $current_question = $questions[$current_question_index];
        
        // Verify question ID matches
        if (isset($current_question['id']) && $current_question['id'] != $question_id) {
            return new WP_Error('question_mismatch', __('Question ID mismatch.', 'ai-quiz-system'), array('status' => 400));
        }

        // Record answer
        $answer_data = array(
            'question_id' => $question_id,
            'answer' => $answer,
            'time_spent' => $time_spent ?: 0,
            'timestamp' => current_time('mysql')
        );

        // Check if answer is correct
        $correct_answer = isset($current_question['correct_answer']) ? $current_question['correct_answer'] : '';
        $is_correct = (strtoupper($answer) === strtoupper($correct_answer));
        $answer_data['is_correct'] = $is_correct;

        // Update session with answer
        $answers = json_decode($session->answers, true) ?: array();
        $answers[] = $answer_data;
        
        // Move to next question
        $next_question_index = $current_question_index + 1;
        $has_next = isset($questions[$next_question_index]);

        // Update session in database
        $update_data = array(
            'answers' => json_encode($answers),
            'current_question' => $next_question_index
        );

        $updated = $db->update_quiz_session($session_id, $update_data);
        
        if (!$updated) {
            return new WP_Error('update_error', __('Failed to update quiz session.', 'ai-quiz-system'), array('status' => 500));
        }

        aiqs_debug_log('Answer submitted', array(
            'session_id' => $session_id,
            'question_id' => $question_id,
            'answer' => $answer,
            'is_correct' => $is_correct,
            'has_next' => $has_next
        ));

        // Prepare response
        $response_data = array(
            'success' => true,
            'is_correct' => $is_correct,
            'correct_answer' => $correct_answer,
            'explanation' => isset($current_question['explanation']) ? $current_question['explanation'] : '',
            'has_next' => $has_next,
            'current_question' => $next_question_index + 1,
            'total_questions' => count($questions)
        );

        // Include next question if available
        if ($has_next) {
            $response_data['next_question'] = $questions[$next_question_index];
        }

        return rest_ensure_response($response_data);
    }

    /**
     * API: Finish quiz.
     */
    public function api_finish_quiz($request) {
        $session_id = $request->get_param('session_id');

        if (!$session_id) {
            return new WP_Error('invalid_params', __('Invalid parameters.', 'ai-quiz-system'), array('status' => 400));
        }

        $db = new AI_Quiz_System_DB();
        
        // Get quiz session
        $session = $db->get_quiz_session($session_id);
        if (!$session) {
            return new WP_Error('session_not_found', __('Quiz session not found.', 'ai-quiz-system'), array('status' => 404));
        }

        // Calculate results
        $answers = json_decode($session->answers, true) ?: array();
        $questions = json_decode($session->questions, true) ?: array();
        
        $total_questions = count($questions);
        $answered_questions = count($answers);
        $correct_answers = array_filter($answers, function($answer) {
            return isset($answer['is_correct']) && $answer['is_correct'];
        });
        $correct_count = count($correct_answers);
        
        $score = $total_questions > 0 ? round(($correct_count / $total_questions) * 100, 2) : 0;
        
        // Calculate time taken
        $start_time = strtotime($session->start_time);
        $end_time = time();
        $time_taken = $end_time - $start_time;

        // Store final results
        $result_data = array(
            'user_id' => $session->user_id,
            'exam_id' => $session->exam_id,
            'session_id' => $session_id,
            'score' => $score,
            'correct_answers' => $correct_count,
            'total_questions' => $total_questions,
            'time_taken' => $time_taken,
            'completed_at' => current_time('mysql'),
            'answers' => json_encode($answers),
            'questions' => $session->questions
        );

        $result_saved = $db->save_quiz_result($result_data);
        
        if (!$result_saved) {
            return new WP_Error('save_error', __('Failed to save quiz results.', 'ai-quiz-system'), array('status' => 500));
        }

        // Mark session as completed
        $db->update_quiz_session($session_id, array('status' => 'completed'));

        aiqs_debug_log('Quiz completed', array(
            'session_id' => $session_id,
            'score' => $score,
            'correct_count' => $correct_count,
            'total_questions' => $total_questions
        ));

        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'score' => $score,
                'correct_answers' => $correct_count,
                'total_questions' => $total_questions,
                'answered_questions' => $answered_questions,
                'time_taken' => $time_taken,
                'percentage' => $score,
                'passed' => $score >= 50, // Adjust pass threshold as needed
                'answers' => $answers,
                'questions' => $questions
            )
        ));
    }

    /**
     * API: Get user performance.
     */
    public function api_get_performance($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('unauthorized', __('You must be logged in to view performance data.', 'ai-quiz-system'), array('status' => 401));
        }

        $user_id = get_current_user_id();
        $exam_id = $request->get_param('exam_id');

        $db = new AI_Quiz_System_DB();
        $performance_data = $db->get_user_performance($user_id, $exam_id);

        return rest_ensure_response(array(
            'success' => true,
            'data' => $performance_data
        ));
    }

    /**
     * API: Process chatbot query.
     */
    public function api_chatbot_query($request) {
        $query = $request->get_param('query');
        $session_id = $request->get_param('session_id');

        if (!$query) {
            return new WP_Error('invalid_params', __('Query is required.', 'ai-quiz-system'), array('status' => 400));
        }

        $user_id = get_current_user_id();
        $performance_data = array();

        // Get user performance data if logged in
        if ($user_id) {
            $db = new AI_Quiz_System_DB();
            $performance_data = $db->get_user_performance($user_id);
        }

        // Process the query with AI
        $ai_engine = new AI_Quiz_System_Engine();
        $response = $ai_engine->process_chatbot_query($query, $user_id ? get_userdata($user_id) : null, $performance_data);

        if (is_wp_error($response)) {
            return new WP_Error('ai_error', $response->get_error_message(), array('status' => 500));
        }

        // Record the interaction
        if ($user_id || $session_id) {
            $db = new AI_Quiz_System_DB();
            $db->record_chatbot_interaction($user_id, $session_id, $query, $response);
        }

        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'response' => $response
            )
        ));
    }

    /**
     * AJAX handler for starting a quiz.
     */
    public function start_quiz() {
        check_ajax_referer('aiqs_public_nonce', 'nonce');

        $exam_id = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : 0;
        $subject_ids = isset($_POST['subject_ids']) ? $_POST['subject_ids'] : array();
        $question_source = isset($_POST['question_source']) ? sanitize_text_field($_POST['question_source']) : '';

        if (!$exam_id || empty($subject_ids)) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'ai-quiz-system')));
        }

        // Convert subject IDs to integers
        $subject_ids = array_map('intval', $subject_ids);

        $request = new WP_REST_Request();
        $request->set_param('exam_id', $exam_id);
        $request->set_param('subject_ids', $subject_ids);

        // Add custom question source if provided
        if (!empty($question_source)) {
            $request->set_param('question_source', $question_source);
        }

        $response = $this->api_start_quiz($request);

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message()
            ));
        } else {
            wp_send_json_success($response->get_data());
        }
    }

    /**
     * AJAX handler for finishing a quiz.
     */
    public function finish_quiz() {
        check_ajax_referer('aiqs_public_nonce', 'nonce');

        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';

        if (!$session_id) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'ai-quiz-system')));
        }

        $request = new WP_REST_Request();
        $request->set_param('session_id', $session_id);

        $response = $this->api_finish_quiz($request);

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message()
            ));
        } else {
            wp_send_json_success($response->get_data());
        }
    }

    /**
     * AJAX handler for submitting an answer.
     */
    public function submit_answer() {
        check_ajax_referer('aiqs_public_nonce', 'nonce');

        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
        $answer = isset($_POST['answer']) ? sanitize_text_field($_POST['answer']) : '';
        $time_spent = isset($_POST['time_spent']) ? intval($_POST['time_spent']) : 0;

        if (!$session_id || !$question_id || !$answer) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'ai-quiz-system')));
        }

        $request = new WP_REST_Request();
        $request->set_param('session_id', $session_id);
        $request->set_param('question_id', $question_id);
        $request->set_param('answer', $answer);
        $request->set_param('time_spent', $time_spent);

        $response = $this->api_submit_answer($request);

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message()
            ));
        } else {
            wp_send_json_success($response->get_data());
        }
    }

    /**
     * AJAX handler for getting performance.
     */
    public function get_performance() {
        check_ajax_referer('aiqs_public_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to view performance data.', 'ai-quiz-system')));
        }

        $exam_id = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : null;

        $request = new WP_REST_Request();
        if ($exam_id) {
            $request->set_param('exam_id', $exam_id);
        }

        $response = $this->api_get_performance($request);

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message()
            ));
        } else {
            wp_send_json_success($response->get_data());
        }
    }

    /**
     * AJAX handler for chatbot query.
     */
    public function chatbot_query() {
        check_ajax_referer('aiqs_public_nonce', 'nonce');

        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';

        if (!$query) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'ai-quiz-system')));
        }

        $request = new WP_REST_Request();
        $request->set_param('query', $query);
        $request->set_param('session_id', $session_id);

        $response = $this->api_chatbot_query($request);

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message()
            ));
        } else {
            wp_send_json_success($response->get_data());
        }
    }
}/**
     * AJAX handler for getting exam subjects for frontend.
     */
    public function get_exam_subjects() {
        check_ajax_referer('aiqs_public_nonce', 'nonce');
        
        $exam_id = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : 0;
        
        if ($exam_id <= 0) {
            wp_send_json_error(['message' => __('Invalid exam ID.', 'ai-quiz-system')]);
        }
        
        $db = new AI_Quiz_System_DB();
        $subjects = $db->get_exam_subjects($exam_id);
        
        if (empty($subjects)) {
            wp_send_json_error(['message' => __('No subjects found for this exam.', 'ai-quiz-system')]);
        }
        
        wp_send_json_success($subjects);
    }