<?php
/**
 * The admin-specific functionality of the plugin.
 */
class AI_Quiz_System_Admin {

    /**
     * Initialize the class and set properties.
     */
    public function __construct() {
        // Constructor code
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style('aiqs-admin', AIQS_PLUGIN_URL . 'admin/css/ai-quiz-system-admin.css', [], AIQS_VERSION);
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script('aiqs-admin', AIQS_PLUGIN_URL . 'admin/js/ai-quiz-system-admin.js', ['jquery'], AIQS_VERSION, true);
        
        // Add localized script data
        wp_localize_script('aiqs-admin', 'aiqs_admin_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiqs_admin_nonce'),
            'i18n' => [
                'confirm_delete' => __('Are you sure you want to delete this exam? This action cannot be undone.', 'ai-quiz-system'),
                'error' => __('Error', 'ai-quiz-system'),
                'success' => __('Success', 'ai-quiz-system'),
                'saving' => __('Saving...', 'ai-quiz-system'),
                'testing' => __('Testing connection...', 'ai-quiz-system')
            ]
        ]);
    }

    /**
     * Add menu items to WordPress admin menu.
     */
    public function add_admin_menu() {
        add_menu_page(
            __('AI Quiz System', 'ai-quiz-system'),
            __('AI Quiz System', 'ai-quiz-system'),
            'manage_options',
            'ai-quiz-system',
            [$this, 'display_dashboard_page'],
            'dashicons-welcome-learn-more',
            25
        );
        
        add_submenu_page(
            'ai-quiz-system',
            __('Dashboard', 'ai-quiz-system'),
            __('Dashboard', 'ai-quiz-system'),
            'manage_options',
            'ai-quiz-system',
            [$this, 'display_dashboard_page']
        );
        
        add_submenu_page(
            'ai-quiz-system',
            __('Exams', 'ai-quiz-system'),
            __('Exams', 'ai-quiz-system'),
            'manage_options',
            'ai-quiz-system-exams',
            [$this, 'display_exams_page']
        );
        
        // Add Subjects management page
        add_submenu_page(
            'ai-quiz-system',
            __('Subjects', 'ai-quiz-system'),
            __('Subjects', 'ai-quiz-system'),
            'manage_options',
            'ai-quiz-system-subjects',
            [$this, 'display_subjects_page']
        );
        
        add_submenu_page(
            'ai-quiz-system',
            __('Questions', 'ai-quiz-system'),
            __('Questions', 'ai-quiz-system'),
            'manage_options',
            'ai-quiz-system-questions',
            [$this, 'display_questions_page']
        );
        
        add_submenu_page(
            'ai-quiz-system',
            __('Statistics', 'ai-quiz-system'),
            __('Statistics', 'ai-quiz-system'),
            'manage_options',
            'ai-quiz-system-stats',
            [$this, 'display_stats_page']
        );
        
        add_submenu_page(
            'ai-quiz-system',
            __('Settings', 'ai-quiz-system'),
            __('Settings', 'ai-quiz-system'),
            'manage_options',
            'ai-quiz-system-settings',
            [$this, 'display_settings_page']
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting('aiqs_ai_settings', 'aiqs_ai_settings');
        register_setting('aiqs_general_settings', 'aiqs_general_settings');
    }

    /**
     * Display dashboard page.
     */
    public function display_dashboard_page() {
        // Get some stats data
        global $wpdb;
        $db = new AI_Quiz_System_DB();
        
        $exams_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aiqs_exams WHERE status = 'active'");
        $questions_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aiqs_questions");
        $attempts_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aiqs_quiz_attempts WHERE status = 'completed'");
        
        // Get recent attempts
        $recent_attempts = $wpdb->get_results(
            "SELECT a.*, u.display_name, e.name as exam_name 
            FROM {$wpdb->prefix}aiqs_quiz_attempts a
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
            JOIN {$wpdb->prefix}aiqs_exams e ON a.exam_id = e.id
            WHERE a.status = 'completed'
            ORDER BY a.end_time DESC
            LIMIT 10"
        );
        
        include AIQS_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    /**
     * Display exams management page.
     */
    public function display_exams_page() {
        $db = new AI_Quiz_System_DB();
        $exams = $db->get_exams();
        
        // Check if we're adding/editing an exam
        $current_exam = null;
        $edit_mode = false;
        
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $exam_id = intval($_GET['id']);
            $current_exam = $db->get_exam($exam_id);
            
            if ($current_exam) {
                $edit_mode = true;
            }
        } elseif (isset($_GET['action']) && $_GET['action'] === 'add') {
            $edit_mode = true;
        }
        
        include AIQS_PLUGIN_DIR . 'admin/partials/exams.php';
    }

    /**
     * Display subjects management page.
     */
    public function display_subjects_page() {
        $db = new AI_Quiz_System_DB();
        $exams = $db->get_exams();
        
        // Get subjects with filtering
        $exam_filter = isset($_GET['exam_filter']) ? intval($_GET['exam_filter']) : null;
        
        if ($exam_filter) {
            $subjects = $db->get_subjects_with_details($exam_filter);
        } else {
            $subjects = $db->get_subjects_with_details();
        }
        
        include AIQS_PLUGIN_DIR . 'admin/partials/subjects.php';
    }

    /**
     * Display questions management page.
     */
    public function display_questions_page() {
        $db = new AI_Quiz_System_DB();
        $exams = $db->get_exams();
        
        $subjects = [];
        if (!empty($exams) && isset($_GET['exam_id'])) {
            $exam_id = intval($_GET['exam_id']);
            $subjects = $db->get_exam_subjects($exam_id);
        }
        
        $questions = [];
        if (!empty($subjects) && isset($_GET['subject_id'])) {
            $subject_id = intval($_GET['subject_id']);
            $questions = $db->get_subject_questions($subject_id);
        }
        
        include AIQS_PLUGIN_DIR . 'admin/partials/questions.php';
    }

    /**
     * Display statistics page.
     */
    public function display_stats_page() {
        include AIQS_PLUGIN_DIR . 'admin/partials/stats.php';
    }

    /**
     * Display settings page.
     */
    public function display_settings_page() {
        include AIQS_PLUGIN_DIR . 'admin/partials/settings.php';
    }

    /**
     * AJAX handler for saving exam configuration.
     */
    public function save_exam_config() {
        check_ajax_referer('aiqs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ai-quiz-system')]);
        }
        
        global $wpdb;
        
        $exam_id = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : 0;
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $subject_count = intval($_POST['subject_count']);
        $question_count_per_subject = intval($_POST['question_count_per_subject']);
        $time_limit = intval($_POST['time_limit']);
        $passing_score = floatval($_POST['passing_score']);
        
        if (empty($name) || $subject_count <= 0 || $question_count_per_subject <= 0) {
            wp_send_json_error(['message' => __('Please fill in all required fields.', 'ai-quiz-system')]);
        }
        
        $exam_data = [
            'name' => $name,
            'description' => $description,
            'subject_count' => $subject_count,
            'question_count_per_subject' => $question_count_per_subject,
            'time_limit' => $time_limit,
            'passing_score' => $passing_score,
            'status' => 'active'
        ];
        
        if ($exam_id > 0) {
            // Update existing exam
            $result = $wpdb->update(
                $wpdb->prefix . 'aiqs_exams',
                $exam_data,
                ['id' => $exam_id]
            );
        } else {
            // Create new exam
            $result = $wpdb->insert(
                $wpdb->prefix . 'aiqs_exams',
                $exam_data
            );
            $exam_id = $wpdb->insert_id;
        }
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to save exam.', 'ai-quiz-system')]);
        }
        
        // Handle subjects
        $subjects = isset($_POST['subjects']) ? $_POST['subjects'] : [];
        
        // Get existing subjects
        $existing_subjects = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, name FROM {$wpdb->prefix}aiqs_subjects WHERE exam_id = %d",
                $exam_id
            ),
            ARRAY_A
        );
        
        $existing_subject_names = array_column($existing_subjects, 'name');
        $existing_subject_ids = array_column($existing_subjects, 'id');
        
        // Add new subjects and update existing ones
        foreach ($subjects as $subject) {
            $subject_name = sanitize_text_field($subject);
            
            if (empty($subject_name)) {
                continue;
            }
            
            // Check if subject already exists
            $subject_index = array_search($subject_name, $existing_subject_names);
            
            if ($subject_index !== false) {
                // Subject exists, mark as processed
                unset($existing_subject_ids[$subject_index]);
            } else {
                // Add new subject
                $wpdb->insert(
                    $wpdb->prefix . 'aiqs_subjects',
                    [
                        'name' => $subject_name,
                        'exam_id' => $exam_id,
                        'status' => 'active'
                    ]
                );
            }
        }
        
        // Set unused subjects to inactive
        if (!empty($existing_subject_ids)) {
            $wpdb->query(
                "UPDATE {$wpdb->prefix}aiqs_subjects 
                SET status = 'inactive' 
                WHERE id IN (" . implode(',', array_map('intval', $existing_subject_ids)) . ")"
            );
        }
        
        wp_send_json_success([
            'message' => __('Exam saved successfully.', 'ai-quiz-system'),
            'exam_id' => $exam_id
        ]);
    }

    /**
     * AJAX handler for getting exam configuration.
     */
    public function get_exam_config() {
        check_ajax_referer('aiqs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ai-quiz-system')]);
        }
        
        $exam_id = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : 0;
        
        if ($exam_id <= 0) {
            wp_send_json_error(['message' => __('Invalid exam ID.', 'ai-quiz-system')]);
        }
        
        $db = new AI_Quiz_System_DB();
        $exam = $db->get_exam($exam_id);
        
        if (!$exam) {
            wp_send_json_error(['message' => __('Exam not found.', 'ai-quiz-system')]);
        }
        
        $subjects = $db->get_exam_subjects($exam_id);
        
        wp_send_json_success([
            'exam' => $exam,
            'subjects' => $subjects
        ]);
    }

    /**
     * AJAX handler for deleting an exam.
     */
    public function delete_exam() {
        check_ajax_referer('aiqs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ai-quiz-system')]);
        }
        
        $exam_id = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : 0;
        
        if ($exam_id <= 0) {
            wp_send_json_error(['message' => __('Invalid exam ID.', 'ai-quiz-system')]);
        }
        
        global $wpdb;
        
        // Set exam status to inactive instead of deleting
        $result = $wpdb->update(
            $wpdb->prefix . 'aiqs_exams',
            ['status' => 'inactive'],
            ['id' => $exam_id]
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to delete exam.', 'ai-quiz-system')]);
        }
        
        wp_send_json_success(['message' => __('Exam deleted successfully.', 'ai-quiz-system')]);
    }

    /**
     * AJAX handler for adding a subject.
     */
    public function add_subject() {
        check_ajax_referer('aiqs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ai-quiz-system')]);
        }
        
        $exam_id = intval($_POST['exam_id']);
        $subject_name = sanitize_text_field($_POST['subject_name']);
        $description = sanitize_textarea_field($_POST['description']);
        
        if (empty($subject_name) || $exam_id <= 0) {
            wp_send_json_error(['message' => __('Please fill in all required fields.', 'ai-quiz-system')]);
        }
        
        global $wpdb;
        $db = new AI_Quiz_System_DB();
        
        // Check if exam exists
        $exam = $db->get_exam($exam_id);
        if (!$exam) {
            wp_send_json_error(['message' => __('Exam not found.', 'ai-quiz-system')]);
        }
        
        // Check if subject already exists for this exam
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}aiqs_subjects WHERE exam_id = %d AND name = %s AND status = 'active'",
                $exam_id, $subject_name
            )
        );
        
        if ($existing) {
            wp_send_json_error(['message' => __('Subject already exists for this exam.', 'ai-quiz-system')]);
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'aiqs_subjects',
            [
                'name' => $subject_name,
                'description' => $description,
                'exam_id' => $exam_id,
                'status' => 'active'
            ]
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to add subject.', 'ai-quiz-system')]);
        }
        /**
 * AJAX handler for getting subjects by exam ID
 */
public function get_subjects_ajax() {
    check_ajax_referer('aiqs_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Permission denied.', 'ai-quiz-system')));
    }
    
    $exam_id = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : 0;
    
    if (!$exam_id) {
        wp_send_json_error(array('message' => __('Invalid exam ID.', 'ai-quiz-system')));
    }
    
    $db = new AI_Quiz_System_DB();
    $subjects = $db->get_exam_subjects($exam_id);
    
    wp_send_json_success($subjects);
}
        
        wp_send_json_success(['message' => __('Subject added successfully.', 'ai-quiz-system')]);
    }

    /**
     * AJAX handler for updating a subject.
     */
    public function update_subject() {
        check_ajax_referer('aiqs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ai-quiz-system')]);
        }
        
        $subject_id = intval($_POST['subject_id']);
        $exam_id = intval($_POST['exam_id']);
        $subject_name = sanitize_text_field($_POST['subject_name']);
        $description = sanitize_textarea_field($_POST['description']);
        $status = sanitize_text_field($_POST['status']);
        
        if (empty($subject_name) || $subject_id <= 0) {
            wp_send_json_error(['message' => __('Please fill in all required fields.', 'ai-quiz-system')]);
        }
        
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'aiqs_subjects',
            [
                'name' => $subject_name,
                'description' => $description,
                'exam_id' => $exam_id,
                'status' => $status
            ],
            ['id' => $subject_id]
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to update subject.', 'ai-quiz-system')]);
        }
        
        wp_send_json_success(['message' => __('Subject updated successfully.', 'ai-quiz-system')]);
    }

    /**
     * AJAX handler for deleting a subject.
     */
    public function delete_subject() {
        check_ajax_referer('aiqs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ai-quiz-system')]);
        }
        
        $subject_id = intval($_POST['subject_id']);
        
        if ($subject_id <= 0) {
            wp_send_json_error(['message' => __('Invalid subject ID.', 'ai-quiz-system')]);
        }
        
        global $wpdb;
        
        // Set subject status to inactive instead of deleting
        $result = $wpdb->update(
            $wpdb->prefix . 'aiqs_subjects',
            ['status' => 'inactive'],
            ['id' => $subject_id]
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to delete subject.', 'ai-quiz-system')]);
        }
        
        wp_send_json_success(['message' => __('Subject deleted successfully.', 'ai-quiz-system')]);
    }

    /**
     * AJAX handler for generating questions.
     */
    public function generate_questions() {
        check_ajax_referer('aiqs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ai-quiz-system')]);
        }
        
        $subject_id = intval($_POST['subject_id']);
        $count = isset($_POST['count']) ? intval($_POST['count']) : 5;
        $difficulty = isset($_POST['difficulty']) ? sanitize_text_field($_POST['difficulty']) : 'mixed';
        
        if ($subject_id <= 0) {
            wp_send_json_error(['message' => __('Invalid subject ID.', 'ai-quiz-system')]);
        }
        
        $db = new AI_Quiz_System_DB();
        $subject = $db->get_subject($subject_id);
        
        if (!$subject) {
            wp_send_json_error(['message' => __('Subject not found.', 'ai-quiz-system')]);
        }
        
        $ai_engine = new AI_Quiz_System_Engine();
        
        if (!$ai_engine->is_configured()) {
            wp_send_json_error(['message' => __('AI is not configured. Please set up your API key in settings.', 'ai-quiz-system')]);
        }
        
        $questions = $ai_engine->generate_questions($subject->name, $count, $difficulty);
        
        if (is_wp_error($questions)) {
            wp_send_json_error(['message' => $questions->get_error_message()]);
        }
        
        // Save generated questions to database
        $saved_count = 0;
        foreach ($questions as $question) {
            $question_data = [
                'subject_id' => $subject_id,
                'question' => $question['question'],
                'option_a' => $question['option_a'],
                'option_b' => $question['option_b'],
                'option_c' => $question['option_c'],
                'option_d' => $question['option_d'],
                'correct_answer' => $question['correct_answer'],
                'explanation' => $question['explanation'],
                'difficulty' => $question['difficulty'],
                'source' => 'ai'
            ];
            
            if ($db->add_question($question_data)) {
                $saved_count++;
            }
        }
        
        wp_send_json_success([
            'message' => sprintf(__('%d questions generated and saved successfully.', 'ai-quiz-system'), $saved_count),
            'questions' => $questions
        ]);
    }

    /**
     * AJAX handler for getting question details.
     */
    public function get_question() {
        check_ajax_referer('aiqs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ai-quiz-system')]);
        }
        
        $question_id = intval($_POST['question_id']);
        
        if ($question_id <= 0) {
            wp_send_json_error(['message' => __('Invalid question ID.', 'ai-quiz-system')]);
        }
        
        $db = new AI_Quiz_System_DB();
        $question = $db->get_question($question_id);
        
        if (!$question) {
            wp_send_json_error(['message' => __('Question not found.', 'ai-quiz-system')]);
        }
        
        wp_send_json_success(['question' => $question]);
    }

    /**
     * AJAX handler for saving a question.
     */
    public function save_question() {
        check_ajax_referer('aiqs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ai-quiz-system')]);
        }
        
        $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
        $subject_id = intval($_POST['subject_id']);
        $question = sanitize_textarea_field($_POST['question']);
        $option_a = sanitize_text_field($_POST['option_a']);
        $option_b = sanitize_text_field($_POST['option_b']);
        $option_c = sanitize_text_field($_POST['option_c']);
        $option_d = sanitize_text_field($_POST['option_d']);
        $correct_answer = sanitize_text_field($_POST['correct_answer']);
        $explanation = sanitize_textarea_field($_POST['explanation']);
        $difficulty = sanitize_text_field($_POST['difficulty']);
        $image_url = esc_url_raw($_POST['image_url']);
        
        if (empty($question) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d) || empty($correct_answer)) {
            wp_send_json_error(['message' => __('Please fill in all required fields.', 'ai-quiz-system')]);
        }
        
        if (!in_array($correct_answer, ['A', 'B', 'C', 'D'])) {
            wp_send_json_error(['message' => __('Correct answer must be A, B, C, or D.', 'ai-quiz-system')]);
        }
        
        $question_data = [
            'subject_id' => $subject_id,
            'question' => $question,
            'option_a' => $option_a,
            'option_b' => $option_b,
            'option_c' => $option_c,
            'option_d' => $option_d,
            'correct_answer' => $correct_answer,
            'explanation' => $explanation,
            'difficulty' => $difficulty,
            'image_url' => $image_url,
            'source' => 'manual'
        ];
        
        $db = new AI_Quiz_System_DB();
        
        if ($question_id > 0) {
            // Update existing question
            $result = $db->update_question($question_id, $question_data);
            $message = __('Question updated successfully.', 'ai-quiz-system');
        } else {
            // Add new question
            $result = $db->add_question($question_data);
            $message = __('Question added successfully.', 'ai-quiz-system');
        }
        
        if (!$result) {
            wp_send_json_error(['message' => __('Failed to save question.', 'ai-quiz-system')]);
        }
        
        wp_send_json_success(['message' => $message]);
    }

    /**
     * AJAX handler for deleting a question.
     */
    public function delete_question() {
        check_ajax_referer('aiqs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ai-quiz-system')]);
        }
        
        $question_id = intval($_POST['question_id']);
        
        if ($question_id <= 0) {
            wp_send_json_error(['message' => __('Invalid question ID.', 'ai-quiz-system')]);
        }
        
        $db = new AI_Quiz_System_DB();
        $result = $db->delete_question($question_id);
        
        if (!$result) {
            wp_send_json_error(['message' => __('Failed to delete question.', 'ai-quiz-system')]);
        }
        
        wp_send_json_success(['message' => __('Question deleted successfully.', 'ai-quiz-system')]);
    }

    /**
     * AJAX handler for getting question statistics.
     */
    public function get_question_stats() {
        check_ajax_referer('aiqs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ai-quiz-system')]);
        }
        
        global $wpdb;
        
        $stats = [
            'total_questions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aiqs_questions"),
            'ai_questions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aiqs_questions WHERE source = 'ai'"),
            'manual_questions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aiqs_questions WHERE source = 'manual'"),
            'demo_questions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aiqs_questions WHERE source = 'demo'"),
        ];
        
        wp_send_json_success(['stats' => $stats]);
    }

    /**
     * AJAX handler for testing AI connection.
     */
    public function test_ai_connection() {
        check_ajax_referer('aiqs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ai-quiz-system')]);
        }
        
        $ai_engine = new AI_Quiz_System_Engine();
        $result = $ai_engine->test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}
