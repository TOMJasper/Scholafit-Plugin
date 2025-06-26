<?php
/**
 * Database handler for AI Quiz System - FIXED VERSION
 */
class AI_Quiz_System_DB {

    /**
     * Table names
     */
    private $table_exams;
    private $table_subjects;
    private $table_questions;
    private $table_quiz_attempts;
    private $table_quiz_answers;
    private $table_quiz_performance;
    private $table_chatbot_history;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        global $wpdb;
        
        $this->table_exams = $wpdb->prefix . 'aiqs_exams';
        $this->table_subjects = $wpdb->prefix . 'aiqs_subjects';
        $this->table_questions = $wpdb->prefix . 'aiqs_questions';
        $this->table_quiz_attempts = $wpdb->prefix . 'aiqs_quiz_attempts';
        $this->table_quiz_answers = $wpdb->prefix . 'aiqs_quiz_answers';
        $this->table_quiz_performance = $wpdb->prefix . 'aiqs_quiz_performance';
        $this->table_chatbot_history = $wpdb->prefix . 'aiqs_chatbot_history';
    }

    /**
     * Create database tables on plugin activation.
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_exams = $wpdb->prefix . 'aiqs_exams';
        $table_subjects = $wpdb->prefix . 'aiqs_subjects';
        $table_questions = $wpdb->prefix . 'aiqs_questions';
        $table_quiz_attempts = $wpdb->prefix . 'aiqs_quiz_attempts';
        $table_quiz_answers = $wpdb->prefix . 'aiqs_quiz_answers';
        $table_quiz_performance = $wpdb->prefix . 'aiqs_quiz_performance';
        $table_chatbot_history = $wpdb->prefix . 'aiqs_chatbot_history';
        
        $sql = "CREATE TABLE $table_exams (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            subject_count int NOT NULL,
            time_limit int DEFAULT 0,
            passing_score int DEFAULT 60,
            question_count_per_subject int DEFAULT 20,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;
        
        CREATE TABLE $table_subjects (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            exam_id mediumint(9) NOT NULL,
            description text,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY exam_id (exam_id)
        ) $charset_collate;
        
        CREATE TABLE $table_questions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            subject_id mediumint(9) NOT NULL,
            question text NOT NULL,
            option_a text NOT NULL,
            option_b text NOT NULL,
            option_c text NOT NULL,
            option_d text NOT NULL,
            correct_answer char(1) NOT NULL,
            explanation text,
            difficulty varchar(20) DEFAULT 'medium',
            source varchar(20) DEFAULT 'ai',
            image_url varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY subject_id (subject_id)
        ) $charset_collate;
        
        CREATE TABLE $table_quiz_attempts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            exam_id mediumint(9) NOT NULL,
            start_time datetime DEFAULT CURRENT_TIMESTAMP,
            end_time datetime DEFAULT NULL,
            score float DEFAULT 0,
            total_questions int DEFAULT 0,
            correct_answers int DEFAULT 0,
            status varchar(20) DEFAULT 'ongoing',
            ip_address varchar(45) DEFAULT NULL,
            session_id varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY exam_id (exam_id)
        ) $charset_collate;
        
        CREATE TABLE $table_quiz_answers (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            attempt_id mediumint(9) NOT NULL,
            question_id mediumint(9) NOT NULL,
            user_answer char(1) DEFAULT NULL,
            is_correct tinyint(1) DEFAULT 0,
            time_spent int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY attempt_id (attempt_id),
            KEY question_id (question_id)
        ) $charset_collate;
        
        CREATE TABLE $table_quiz_performance (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            exam_id mediumint(9) NOT NULL,
            subject_id mediumint(9) NOT NULL,
            total_attempts int DEFAULT 0,
            correct_answers int DEFAULT 0,
            total_questions int DEFAULT 0,
            average_score float DEFAULT 0,
            strengths text,
            weaknesses text,
            recommendations text,
            last_attempt_id mediumint(9) DEFAULT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_exam_subject (user_id, exam_id, subject_id),
            KEY user_id (user_id),
            KEY exam_id (exam_id),
            KEY subject_id (subject_id)
        ) $charset_collate;
        
        CREATE TABLE $table_chatbot_history (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            session_id varchar(255) DEFAULT NULL,
            query text NOT NULL,
            response text NOT NULL,
            context text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Drop database tables on plugin uninstallation.
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'aiqs_exams',
            $wpdb->prefix . 'aiqs_subjects',
            $wpdb->prefix . 'aiqs_questions',
            $wpdb->prefix . 'aiqs_quiz_attempts',
            $wpdb->prefix . 'aiqs_quiz_answers',
            $wpdb->prefix . 'aiqs_quiz_performance',
            $wpdb->prefix . 'aiqs_chatbot_history'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    /**
     * Get all exams.
     */
    public function get_exams() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$this->table_exams} WHERE status = 'active' ORDER BY name ASC"
        );
    }

    /**
     * Get exam by ID.
     */
    public function get_exam($id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_exams} WHERE id = %d", $id)
        );
    }

    /**
     * Get a single subject by ID.
     */
    public function get_subject($id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_subjects} WHERE id = %d", $id)
        );
    }

    /**
     * Get subjects for an exam.
     */
    public function get_exam_subjects($exam_id, $subject_id = null) {
        global $wpdb;
        
        if ($subject_id) {
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$this->table_subjects} WHERE exam_id = %d AND id = %d AND status = 'active'",
                    $exam_id, $subject_id
                )
            );
        }
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_subjects} WHERE exam_id = %d AND status = 'active' ORDER BY name ASC",
                $exam_id
            )
        );
    }

    /**
     * Get subjects with details (including exam name and question count).
     */
    public function get_subjects_with_details($exam_id = null) {
        global $wpdb;
        
        $sql = "SELECT s.*, e.name as exam_name, 
                COUNT(q.id) as question_count
                FROM {$this->table_subjects} s
                LEFT JOIN {$this->table_exams} e ON s.exam_id = e.id
                LEFT JOIN {$this->table_questions} q ON s.id = q.subject_id
                WHERE s.status = 'active'";
        
        $params = array();
        if ($exam_id) {
            $sql .= " AND s.exam_id = %d";
            $params[] = $exam_id;
        }
        
        $sql .= " GROUP BY s.id ORDER BY e.name, s.name";
        
        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($sql, $params));
        } else {
            return $wpdb->get_results($sql);
        }
    }

    /**
     * Get questions for a subject, with optional limit.
     */
    public function get_subject_questions($subject_id, $limit = 0, $source = 'all') {
        global $wpdb;
        
        aiqs_debug_log('Getting subject questions', array(
            'subject_id' => $subject_id,
            'limit' => $limit,
            'source' => $source
        ));
        
        $sql = "SELECT * FROM {$this->table_questions} WHERE subject_id = %d";
        $params = array($subject_id);
        
        // Filter by source if specified
        if ($source !== 'all') {
            $sql .= " AND source = %s";
            $params[] = $source;
        }
        
        $sql .= " ORDER BY RAND()";
        
        if ($limit > 0) {
            $sql .= " LIMIT %d";
            $params[] = $limit;
        }
        
        $questions = $wpdb->get_results($wpdb->prepare($sql, $params));
        
        aiqs_debug_log('Retrieved questions', array(
            'count' => count($questions),
            'subject_id' => $subject_id,
            'source' => $source
        ));
        
        return $questions;
    }

    /**
     * Get questions by subject with fallback options.
     */
    public function get_questions_by_subject($subject_id, $limit = 10, $difficulty = 'mixed') {
        global $wpdb;
        
        $sql = "SELECT * FROM {$this->table_questions} WHERE subject_id = %d";
        $params = array($subject_id);
        
        if ($difficulty !== 'mixed') {
            $sql .= " AND difficulty = %s";
            $params[] = $difficulty;
        }
        
        $sql .= " ORDER BY RAND() LIMIT %d";
        $params[] = $limit;
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    /**
     * Get a single question by ID.
     */
    public function get_question($id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_questions} WHERE id = %d", $id)
        );
    }

    /**
     * Add a question to the database.
     */
    public function add_question($data) {
        global $wpdb;
        
        // Validate required fields
        $required_fields = array('subject_id', 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer');
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                aiqs_debug_log('Missing required field for question', array('field' => $field));
                return false;
            }
        }
        
        // Set defaults for optional fields
        $defaults = array(
            'explanation' => '',
            'difficulty' => 'medium',
            'source' => 'ai',
            'image_url' => null
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Validate correct_answer
        if (!in_array(strtoupper($data['correct_answer']), array('A', 'B', 'C', 'D'))) {
            aiqs_debug_log('Invalid correct_answer', array('correct_answer' => $data['correct_answer']));
            return false;
        }
        
        $result = $wpdb->insert(
            $this->table_questions,
            array(
                'subject_id' => intval($data['subject_id']),
                'question' => sanitize_text_field($data['question']),
                'option_a' => sanitize_text_field($data['option_a']),
                'option_b' => sanitize_text_field($data['option_b']),
                'option_c' => sanitize_text_field($data['option_c']),
                'option_d' => sanitize_text_field($data['option_d']),
                'correct_answer' => strtoupper($data['correct_answer']),
                'explanation' => sanitize_textarea_field($data['explanation']),
                'difficulty' => sanitize_text_field($data['difficulty']),
                'source' => sanitize_text_field($data['source']),
                'image_url' => !empty($data['image_url']) ? esc_url_raw($data['image_url']) : null
            )
        );
        
        if ($result === false) {
            aiqs_debug_log('Failed to add question', $wpdb->last_error);
            return false;
        }
        
        $question_id = $wpdb->insert_id;
        aiqs_debug_log('Added question to database', array(
            'question_id' => $question_id,
            'subject_id' => $data['subject_id'],
            'source' => $data['source']
        ));
        
        return $question_id;
    }

    /**
     * Update a question.
     */
    public function update_question($question_id, $question_data) {
        global $wpdb;
        
        $question_data['updated_at'] = current_time('mysql');
        
        return $wpdb->update(
            $this->table_questions,
            $question_data,
            array('id' => $question_id)
        );
    }

    /**
     * Delete a question.
     */
    public function delete_question($question_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_questions,
            array('id' => $question_id)
        );
    }

    /**
     * Create a new quiz attempt.
     */
    public function create_quiz_attempt($user_id, $exam_id, $session_id, $ip_address) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_quiz_attempts,
            array(
                'user_id' => $user_id,
                'exam_id' => $exam_id,
                'session_id' => $session_id,
                'ip_address' => $ip_address,
                'status' => 'ongoing'
            )
        );
        
        if ($result === false) {
            aiqs_debug_log('Failed to create quiz attempt', $wpdb->last_error);
            return false;
        }
        
        $attempt_id = $wpdb->insert_id;
        aiqs_debug_log('Created quiz attempt', array('attempt_id' => $attempt_id));
        
        return $attempt_id;
    }

    /**
     * Create quiz session.
     */
    public function create_quiz_session($session_data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_quiz_attempts,
            array(
                'user_id' => $session_data['user_id'],
                'exam_id' => $session_data['exam_id'],
                'session_id' => $session_data['session_id'],
                'status' => 'ongoing',
                'start_time' => $session_data['start_time'],
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            )
        );
        
        if ($result) {
            $attempt_id = $wpdb->insert_id;
            
            // Store session data in transient
            set_transient('aiqs_quiz_' . $session_data['session_id'], $session_data, 3600); // 1 hour
            
            return $attempt_id;
        }
        
        return false;
    }

    /**
     * Get quiz session.
     */
    public function get_quiz_session($session_id) {
        // Try to get from transient first
        $session_data = get_transient('aiqs_quiz_' . $session_id);
        
        if ($session_data) {
            return (object) $session_data;
        }
        
        // Fallback to database
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_quiz_attempts} WHERE session_id = %s",
                $session_id
            )
        );
    }

    /**
     * Update quiz session.
     */
    public function update_quiz_session($session_id, $update_data) {
        // Update transient
        $session_data = get_transient('aiqs_quiz_' . $session_id);
        if ($session_data) {
            $session_data = array_merge($session_data, $update_data);
            set_transient('aiqs_quiz_' . $session_id, $session_data, 3600);
        }
        
        // Update database
        global $wpdb;
        
        return $wpdb->update(
            $this->table_quiz_attempts,
            $update_data,
            array('session_id' => $session_id)
        );
    }

    /**
     * Record a quiz answer.
     */
    public function record_answer($attempt_id, $question_id, $user_answer, $is_correct, $time_spent) {
        global $wpdb;
        
        // Check if this answer already exists to prevent duplicates
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->table_quiz_answers} 
                WHERE attempt_id = %d AND question_id = %d",
                $attempt_id, $question_id
            )
        );
        
        if ($existing) {
            // Update existing answer
            $result = $wpdb->update(
                $this->table_quiz_answers,
                array(
                    'user_answer' => $user_answer,
                    'is_correct' => $is_correct ? 1 : 0,
                    'time_spent' => $time_spent
                ),
                array('id' => $existing)
            );
            
            aiqs_debug_log('Updated existing answer', array(
                'answer_id' => $existing,
                'question_id' => $question_id,
                'is_correct' => $is_correct
            ));
            
            return $existing;
        }
        
        // Insert new answer
        $result = $wpdb->insert(
            $this->table_quiz_answers,
            array(
                'attempt_id' => $attempt_id,
                'question_id' => $question_id,
                'user_answer' => $user_answer,
                'is_correct' => $is_correct ? 1 : 0,
                'time_spent' => $time_spent
            )
        );
        
        if ($result === false) {
            aiqs_debug_log('Failed to record answer', $wpdb->last_error);
            return false;
        }
        
        $answer_id = $wpdb->insert_id;
        aiqs_debug_log('Recorded new answer', array(
            'answer_id' => $answer_id,
            'question_id' => $question_id,
            'is_correct' => $is_correct
        ));
        
        return $answer_id;
    }

    /**
     * Complete a quiz attempt.
     */
    public function complete_quiz_attempt($attempt_id, $score, $total_questions, $correct_answers) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->table_quiz_attempts,
            array(
                'end_time' => current_time('mysql'),
                'score' => $score,
                'total_questions' => $total_questions,
                'correct_answers' => $correct_answers,
                'status' => 'completed'
            ),
            array('id' => $attempt_id)
        );
        
        if ($result === false) {
            aiqs_debug_log('Failed to complete quiz attempt', $wpdb->last_error);
        } else {
            aiqs_debug_log('Completed quiz attempt', array(
                'attempt_id' => $attempt_id,
                'score' => $score,
                'correct_answers' => $correct_answers,
                'total_questions' => $total_questions
            ));
        }
        
        return $result;
    }

    /**
     * Save quiz result.
     */
    public function save_quiz_result($result_data) {
        global $wpdb;
        
        return $wpdb->insert(
            $this->table_quiz_attempts,
            array(
                'user_id' => $result_data['user_id'],
                'exam_id' => $result_data['exam_id'],
                'session_id' => $result_data['session_id'],
                'score' => $result_data['score'],
                'total_questions' => $result_data['total_questions'],
                'correct_answers' => $result_data['correct_answers'],
                'end_time' => $result_data['completed_at'],
                'status' => 'completed'
            )
        );
    }

    /**
     * Update user performance.
     */
    public function update_performance($user_id, $exam_id, $subject_id, $attempt_id, $is_correct = null, $ai_feedback = array()) {
        global $wpdb;
        
        // Skip for guest users
        if (!$user_id) {
            return false;
        }
        
        // Make sure ai_feedback is an array if it's NULL
        if (empty($ai_feedback)) {
            $ai_feedback = array();
        }
        
        // Check if we're updating after completed attempt
        $is_final_update = ($is_correct === null);
        
        if ($is_final_update) {
            // Load attempt data
            $attempt = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->table_quiz_attempts} WHERE id = %d",
                    $attempt_id
                )
            );
            
            if (!$attempt) {
                return false;
            }
            
            // Get subject-specific data for this attempt
            $subject_data = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT 
                        COUNT(a.id) as total_questions,
                        SUM(a.is_correct) as correct_answers
                    FROM {$this->table_quiz_answers} a
                    JOIN {$this->table_questions} q ON a.question_id = q.id
                    WHERE a.attempt_id = %d AND q.subject_id = %d",
                    $attempt_id, $subject_id
                )
            );
            
            if (!$subject_data || $subject_data->total_questions == 0) {
                return false;
            }
            
            $correct_count = intval($subject_data->correct_answers);
            $total_count = intval($subject_data->total_questions);
            $subject_score = ($total_count > 0) ? ($correct_count / $total_count) * 100 : 0;
        }
        
        // Check if performance record exists
        $performance = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_quiz_performance} 
                WHERE user_id = %d AND exam_id = %d AND subject_id = %d",
                $user_id, $exam_id, $subject_id
            )
        );
        
        if ($performance) {
            // Update existing record
            if ($is_final_update) {
                // Final update with calculated subject data
                $total_attempts = $performance->total_attempts + 1;
                $correct_answers = $performance->correct_answers + $correct_count;
                $total_questions = $performance->total_questions + $total_count;
                $average_score = ($total_questions > 0) ? ($correct_answers / $total_questions) * 100 : 0;
                
                // Prepare update data
                $update_data = array(
                    'total_attempts' => $total_attempts,
                    'correct_answers' => $correct_answers,
                    'total_questions' => $total_questions,
                    'average_score' => $average_score,
                    'last_attempt_id' => $attempt_id
                );
                
                // Add AI feedback if provided
                if (!empty($ai_feedback['strengths'])) {
                    $update_data['strengths'] = $ai_feedback['strengths'];
                }
                
                if (!empty($ai_feedback['weaknesses'])) {
                    $update_data['weaknesses'] = $ai_feedback['weaknesses'];
                }
                
                if (!empty($ai_feedback['recommendations'])) {
                    $update_data['recommendations'] = $ai_feedback['recommendations'];
                }
                
                // Update the record
                $result = $wpdb->update(
                    $this->table_quiz_performance,
                    $update_data,
                    array('id' => $performance->id)
                );
                
            } else {
                // Incremental update for a single answer
                $correct_answers = $performance->correct_answers + ($is_correct ? 1 : 0);
                $total_questions = $performance->total_questions + 1;
                $average_score = ($total_questions > 0) ? ($correct_answers / $total_questions) * 100 : 0;
                
                $wpdb->update(
                    $this->table_quiz_performance,
                    array(
                        'correct_answers' => $correct_answers,
                        'total_questions' => $total_questions,
                        'average_score' => $average_score,
                        'last_attempt_id' => $attempt_id
                    ),
                    array('id' => $performance->id)
                );
            }
            
            return $performance->id;
        } else {
            // Create new record
            if ($is_final_update) {
                // Create with calculated subject data
                $insert_data = array(
                    'user_id' => $user_id,
                    'exam_id' => $exam_id,
                    'subject_id' => $subject_id,
                    'total_attempts' => 1,
                    'correct_answers' => $correct_count,
                    'total_questions' => $total_count,
                    'average_score' => $subject_score,
                    'last_attempt_id' => $attempt_id,
                    'strengths' => isset($ai_feedback['strengths']) ? $ai_feedback['strengths'] : '',
                    'weaknesses' => isset($ai_feedback['weaknesses']) ? $ai_feedback['weaknesses'] : '',
                    'recommendations' => isset($ai_feedback['recommendations']) ? $ai_feedback['recommendations'] : ''
                );
                
            } else {
                // Create with single answer data
                $insert_data = array(
                    'user_id' => $user_id,
                    'exam_id' => $exam_id,
                    'subject_id' => $subject_id,
                    'total_attempts' => 0, // Will be set to 1 on quiz completion
                    'correct_answers' => $is_correct ? 1 : 0,
                    'total_questions' => 1,
                    'average_score' => $is_correct ? 100 : 0,
                    'last_attempt_id' => $attempt_id,
                    'strengths' => isset($ai_feedback['strengths']) ? $ai_feedback['strengths'] : '',
                    'weaknesses' => isset($ai_feedback['weaknesses']) ? $ai_feedback['weaknesses'] : '',
                    'recommendations' => isset($ai_feedback['recommendations']) ? $ai_feedback['recommendations'] : ''
                );
            }
            
            $wpdb->insert(
                $this->table_quiz_performance,
                $insert_data
            );
            
            $new_id = $wpdb->insert_id;
            
            return $new_id;
        }
    }

    /**
     * Record chatbot interaction.
     */
    public function record_chatbot_interaction($user_id, $session_id, $query, $response, $context = '') {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_chatbot_history,
            array(
                'user_id' => $user_id,
                'session_id' => $session_id,
                'query' => $query,
                'response' => $response,
                'context' => $context
            )
        );
        
        return $wpdb->insert_id;
    }

    /**
     * Get user's quiz performance data.
     */
    public function get_user_performance($user_id, $exam_id = null) {
        global $wpdb;
        
        if (!$user_id) {
            return array();
        }
        
        $sql = "SELECT p.*, e.name as exam_name, s.name as subject_name 
                FROM {$this->table_quiz_performance} p
                JOIN {$this->table_exams} e ON p.exam_id = e.id
                JOIN {$this->table_subjects} s ON p.subject_id = s.id
                WHERE p.user_id = %d";
        
        $params = array($user_id);
        
        if ($exam_id) {
            $sql .= " AND p.exam_id = %d";
            $params[] = $exam_id;
        }
        
        $sql .= " ORDER BY p.updated_at DESC";
        
        $results = $wpdb->get_results(
            $wpdb->prepare($sql, $params)
        );
        
        return $results ? $results : array();
    }

    /**
     * Get recent quiz attempts for a user.
     */
    public function get_user_attempts($user_id, $limit = 10) {
        global $wpdb;
        
        if (!$user_id) {
            return array();
        }
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT a.*, e.name as exam_name 
                FROM {$this->table_quiz_attempts} a
                JOIN {$this->table_exams} e ON a.exam_id = e.id
                WHERE a.user_id = %d AND a.status = 'completed'
                ORDER BY a.end_time DESC
                LIMIT %d",
                $user_id, $limit
            )
        );
        
        return $results ? $results : array();
    }

    /**
     * Get details of a specific quiz attempt.
     */
    public function get_attempt_details($attempt_id) {
        global $wpdb;
        
        $attempt = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT a.*, e.name as exam_name 
                FROM {$this->table_quiz_attempts} a
                JOIN {$this->table_exams} e ON a.exam_id = e.id
                WHERE a.id = %d",
                $attempt_id
            )
        );
        
        if (!$attempt) {
            return false;
        }
        
        $answers = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ans.*, q.question, q.correct_answer, q.explanation, q.option_a, q.option_b, q.option_c, q.option_d,
                s.name as subject_name
                FROM {$this->table_quiz_answers} ans
                JOIN {$this->table_questions} q ON ans.question_id = q.id
                JOIN {$this->table_subjects} s ON q.subject_id = s.id
                WHERE ans.attempt_id = %d
                ORDER BY ans.id ASC",
                $attempt_id
            )
        );
        
        return array(
            'attempt' => $attempt,
            'answers' => $answers ? $answers : array()
        );
    }
    
    /**
     * Clear existing performance data for a user
     */
    public function clear_performance_data($user_id) {
        global $wpdb;
        
        if (!$user_id) {
            return false;
        }
        
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_quiz_performance} WHERE user_id = %d",
                $user_id
            )
        );
        
        return $result;
    }
    
    /**
     * Recalculate performance data for a user
     */
    public function rebuild_performance($user_id) {
        global $wpdb;
        
        if (!$user_id) {
            return false;
        }
        
        // First, clear existing performance data
        $this->clear_performance_data($user_id);
        
        // Get all completed attempts for this user
        $attempts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, exam_id FROM {$this->table_quiz_attempts} 
                WHERE user_id = %d AND status = 'completed'
                ORDER BY end_time ASC",
                $user_id
            )
        );
        
        if (!$attempts) {
            return false;
        }
        
        $updated_subjects = array();
        
        // Process each attempt
        foreach ($attempts as $attempt) {
            // Get all subjects from this attempt
            $subjects = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DISTINCT q.subject_id
                    FROM {$this->table_quiz_answers} a
                    JOIN {$this->table_questions} q ON a.question_id = q.id
                    WHERE a.attempt_id = %d",
                    $attempt->id
                )
            );
            
            if (!$subjects) continue;
            
            // Process each subject
            foreach ($subjects as $subject) {
                // Get performance data for this subject in this attempt
                $subject_data = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT 
                            COUNT(a.id) as total_questions,
                            SUM(a.is_correct) as correct_answers
                        FROM {$this->table_quiz_answers} a
                        JOIN {$this->table_questions} q ON a.question_id = q.id
                        WHERE a.attempt_id = %d AND q.subject_id = %d",
                        $attempt->id, $subject->subject_id
                    )
                );
                
                if (!$subject_data || $subject_data->total_questions == 0) continue;
                
                // Calculate subject score
                $correct_count = intval($subject_data->correct_answers);
                $total_count = intval($subject_data->total_questions);
                $subject_score = ($total_count > 0) ? ($correct_count / $total_count) * 100 : 0;
                
                // Update performance for this subject
                $key = $attempt->exam_id . '-' . $subject->subject_id;
                
                if (isset($updated_subjects[$key])) {
                    // Update existing record
                    $perf = $updated_subjects[$key];
                    $perf['total_attempts']++;
                    $perf['correct_answers'] += $correct_count;
                    $perf['total_questions'] += $total_count;
                    $perf['average_score'] = ($perf['total_questions'] > 0) ? 
                        ($perf['correct_answers'] / $perf['total_questions']) * 100 : 0;
                    $perf['last_attempt_id'] = $attempt->id;
                    
                    $updated_subjects[$key] = $perf;
                    
                } else {
                    // Create new record
                    $updated_subjects[$key] = array(
                        'user_id' => $user_id,
                        'exam_id' => $attempt->exam_id,
                        'subject_id' => $subject->subject_id,
                        'total_attempts' => 1,
                        'correct_answers' => $correct_count,
                        'total_questions' => $total_count,
                        'average_score' => $subject_score,
                        'last_attempt_id' => $attempt->id
                    );
                }
            }
        }
        
        // Save all updated performance records
        foreach ($updated_subjects as $subject_data) {
            $wpdb->insert($this->table_quiz_performance, $subject_data);
        }
        
        return count($updated_subjects);
    }

    /**
     * Get questions count by source for a subject.
     */
    public function get_questions_count_by_source($subject_id, $source = 'all') {
        global $wpdb;
        
        if ($source === 'all') {
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_questions} WHERE subject_id = %d",
                    $subject_id
                )
            );
        } else {
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_questions} WHERE subject_id = %d AND source = %s",
                    $subject_id, $source
                )
            );
        }
        
        aiqs_debug_log('Questions count by source', array(
            'subject_id' => $subject_id,
            'source' => $source,
            'count' => $count
        ));
        
        return intval($count);
    }
}
