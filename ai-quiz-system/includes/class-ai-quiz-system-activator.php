<?php
/**
 * Enhanced Database Schema for AI Quiz System with Conversational AI
 * Add this to your class-ai-quiz-system-activator.php
 */

class AI_Quiz_System_Activator {
    
    public static function activate() {
        // Create all database tables
        self::create_tables();
        
        // Create default settings
        if (!get_option('aiqs_ai_settings')) {
            update_option('aiqs_ai_settings', [
                'provider' => 'openai',
                'api_key' => '',
                'enable_chatbot' => true,
                'question_source' => 'ai',
                'fallback_to_stored' => true,
                'enable_memory' => true,
                'enable_personalization' => true
            ]);
        }
        
        if (!get_option('aiqs_general_settings')) {
            update_option('aiqs_general_settings', [
                'default_time_limit' => 3600,
                'default_passing_score' => 60,
                'default_questions_per_subject' => 20,
                'allow_guest_access' => true,
                'show_explanations' => true,
                'enable_performance_tracking' => true
            ]);
        }
        
        // Create demo data if no exams exist
        self::create_demo_data();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create all database tables including enhanced conversational AI tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Original tables (enhanced)
        $sql = "
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aiqs_exams (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            subject_count int NOT NULL DEFAULT 4,
            time_limit int DEFAULT 3600,
            passing_score int DEFAULT 60,
            question_count_per_subject int DEFAULT 20,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status)
        ) $charset_collate;
        
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aiqs_subjects (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            exam_id mediumint(9) NOT NULL,
            description text,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY exam_id (exam_id),
            KEY status (status),
            FOREIGN KEY (exam_id) REFERENCES {$wpdb->prefix}aiqs_exams(id) ON DELETE CASCADE
        ) $charset_collate;
        
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aiqs_questions (
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
            image_url varchar(500) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY subject_id (subject_id),
            KEY difficulty (difficulty),
            KEY source (source),
            FOREIGN KEY (subject_id) REFERENCES {$wpdb->prefix}aiqs_subjects(id) ON DELETE CASCADE
        ) $charset_collate;
        
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aiqs_quiz_attempts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            exam_id mediumint(9) NOT NULL,
            session_id varchar(255) NOT NULL,
            start_time datetime DEFAULT CURRENT_TIMESTAMP,
            end_time datetime DEFAULT NULL,
            score float DEFAULT 0,
            total_questions int DEFAULT 0,
            correct_answers int DEFAULT 0,
            status varchar(20) DEFAULT 'ongoing',
            ip_address varchar(45) DEFAULT NULL,
            questions text,
            answers text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY exam_id (exam_id),
            KEY session_id (session_id),
            KEY status (status),
            FOREIGN KEY (exam_id) REFERENCES {$wpdb->prefix}aiqs_exams(id) ON DELETE CASCADE
        ) $charset_collate;
        
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aiqs_quiz_answers (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            attempt_id mediumint(9) NOT NULL,
            question_id mediumint(9) NOT NULL,
            user_answer char(1) DEFAULT NULL,
            is_correct tinyint(1) DEFAULT 0,
            time_spent int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY attempt_id (attempt_id),
            KEY question_id (question_id),
            FOREIGN KEY (attempt_id) REFERENCES {$wpdb->prefix}aiqs_quiz_attempts(id) ON DELETE CASCADE
        ) $charset_collate;
        
        -- ENHANCED CONVERSATIONAL AI TABLES --
        
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aiqs_student_profiles (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            learning_style varchar(50) DEFAULT 'mixed',
            strong_subjects text,
            weak_subjects text,
            preferred_difficulty varchar(20) DEFAULT 'medium',
            study_goals text,
            personality_traits text,
            communication_style varchar(50) DEFAULT 'friendly',
            last_activity datetime DEFAULT CURRENT_TIMESTAMP,
            total_study_time int DEFAULT 0,
            total_conversations int DEFAULT 0,
            average_session_length int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) $charset_collate;
        
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aiqs_conversations (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            session_id varchar(255) NOT NULL,
            conversation_title varchar(255) DEFAULT NULL,
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_message_at datetime DEFAULT CURRENT_TIMESTAMP,
            message_count int DEFAULT 0,
            conversation_summary text,
            learning_context text,
            mood_detected varchar(50) DEFAULT 'neutral',
            topics_covered text,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY status (status),
            KEY last_message_at (last_message_at)
        ) $charset_collate;
        
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aiqs_conversation_messages (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            conversation_id mediumint(9) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            message_type enum('user', 'ai') NOT NULL,
            user_message text,
            ai_response text,
            context_data text,
            performance_data_used text,
            emotion_detected varchar(50) DEFAULT NULL,
            topics_mentioned text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            tokens_used int DEFAULT 0,
            response_time float DEFAULT 0,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY user_id (user_id),
            KEY message_type (message_type),
            KEY timestamp (timestamp),
            FOREIGN KEY (conversation_id) REFERENCES {$wpdb->prefix}aiqs_conversations(id) ON DELETE CASCADE
        ) $charset_collate;
        
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aiqs_study_recommendations (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            recommendation_type varchar(50) NOT NULL,
            subject_id mediumint(9) DEFAULT NULL,
            recommendation_text text NOT NULL,
            priority_level varchar(20) DEFAULT 'medium',
            status varchar(20) DEFAULT 'pending',
            generated_by varchar(50) DEFAULT 'ai',
            generated_date datetime DEFAULT CURRENT_TIMESTAMP,
            completed_date datetime DEFAULT NULL,
            effectiveness_score float DEFAULT NULL,
            user_feedback text,
            expires_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY subject_id (subject_id),
            KEY status (status),
            KEY priority_level (priority_level),
            KEY generated_date (generated_date),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES {$wpdb->prefix}aiqs_subjects(id) ON DELETE SET NULL
        ) $charset_collate;
        
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aiqs_learning_analytics (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            session_date date NOT NULL,
            activity_type varchar(50) NOT NULL,
            subject_focus varchar(255) DEFAULT NULL,
            time_spent int DEFAULT 0,
            questions_answered int DEFAULT 0,
            questions_correct int DEFAULT 0,
            accuracy_rate float DEFAULT 0,
            conversation_quality_score float DEFAULT 0,
            engagement_level varchar(20) DEFAULT 'medium',
            learning_progress_score float DEFAULT 0,
            insights text,
            recommendations_generated int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_date (session_date),
            KEY activity_type (activity_type),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) $charset_collate;
        
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aiqs_quiz_performance (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            exam_id mediumint(9) NOT NULL,
            subject_id mediumint(9) NOT NULL,
            total_attempts int DEFAULT 0,
            correct_answers int DEFAULT 0,
            total_questions int DEFAULT 0,
            average_score float DEFAULT 0,
            best_score float DEFAULT 0,
            latest_score float DEFAULT 0,
            improvement_trend varchar(20) DEFAULT 'stable',
            strengths text,
            weaknesses text,
            recommendations text,
            last_attempt_id mediumint(9) DEFAULT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_exam_subject (user_id, exam_id, subject_id),
            KEY user_id (user_id),
            KEY exam_id (exam_id),
            KEY subject_id (subject_id),
            FOREIGN KEY (exam_id) REFERENCES {$wpdb->prefix}aiqs_exams(id) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES {$wpdb->prefix}aiqs_subjects(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add indexes for better performance
        self::add_performance_indexes();
    }
    
    /**
     * Add performance indexes
     */
    private static function add_performance_indexes() {
        global $wpdb;
        
        // Add composite indexes for better query performance
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_user_activity ON {$wpdb->prefix}aiqs_learning_analytics (user_id, session_date, activity_type)",
            "CREATE INDEX IF NOT EXISTS idx_conversation_lookup ON {$wpdb->prefix}aiqs_conversation_messages (conversation_id, timestamp)",
            "CREATE INDEX IF NOT EXISTS idx_recommendations_active ON {$wpdb->prefix}aiqs_study_recommendations (user_id, status, priority_level)",
            "CREATE INDEX IF NOT EXISTS idx_performance_tracking ON {$wpdb->prefix}aiqs_quiz_performance (user_id, updated_at)"
        ];
        
        foreach ($indexes as $index) {
            $wpdb->query($index);
        }
    }
    
    /**
     * Create demo data - enhanced version
     */
    private static function create_demo_data() {
        global $wpdb;
        
        // Check if any exams exist
        $exams_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aiqs_exams");
        
        if ($exams_count > 0) {
            return; // Skip demo data creation if exams already exist
        }
        
        // Create UTME exam
        $wpdb->insert(
            $wpdb->prefix . 'aiqs_exams',
            [
                'name' => 'UTME',
                'description' => 'Unified Tertiary Matriculation Examination - Practice tests for university admission',
                'subject_count' => 4,
                'time_limit' => 7200, // 2 hours
                'passing_score' => 60,
                'question_count_per_subject' => 20,
                'status' => 'active'
            ]
        );
        
        $utme_id = $wpdb->insert_id;
        
        // Create WAEC exam
        $wpdb->insert(
            $wpdb->prefix . 'aiqs_exams',
            [
                'name' => 'WAEC',
                'description' => 'West African Examinations Council - Secondary school certification exams',
                'subject_count' => 6,
                'time_limit' => 10800, // 3 hours
                'passing_score' => 50,
                'question_count_per_subject' => 25,
                'status' => 'active'
            ]
        );
        
        $waec_id = $wpdb->insert_id;
        
        // Create NECO exam
        $wpdb->insert(
            $wpdb->prefix . 'aiqs_exams',
            [
                'name' => 'NECO',
                'description' => 'National Examinations Council - Alternative to WAEC certification',
                'subject_count' => 6,
                'time_limit' => 10800, // 3 hours
                'passing_score' => 50,
                'question_count_per_subject' => 25,
                'status' => 'active'
            ]
        );
        
        $neco_id = $wpdb->insert_id;
        
        // Create subjects for each exam
        $subjects_data = [
            'Core Subjects' => [
                'English Language',
                'Mathematics',
                'Physics',
                'Chemistry',
                'Biology'
            ],
            'Arts Subjects' => [
                'Literature in English',
                'Government',
                'History',
                'Christian Religious Studies',
                'Islamic Religious Studies'
            ],
            'Commercial Subjects' => [
                'Economics',
                'Commerce',
                'Accounting',
                'Business Studies'
            ],
            'Social Sciences' => [
                'Geography',
                'Civic Education',
                'Social Studies'
            ],
            'Languages' => [
                'French',
                'Hausa',
                'Igbo',
                'Yoruba'
            ]
        ];
        
        $all_subjects = [];
        foreach ($subjects_data as $category => $subjects) {
            $all_subjects = array_merge($all_subjects, $subjects);
        }
        
        // Add subjects to all exams
        foreach ([$utme_id, $waec_id, $neco_id] as $exam_id) {
            foreach ($all_subjects as $subject_name) {
                $wpdb->insert(
                    $wpdb->prefix . 'aiqs_subjects',
                    [
                        'name' => $subject_name,
                        'exam_id' => $exam_id,
                        'description' => "Practice questions for {$subject_name}",
                        'status' => 'active'
                    ]
                );
            }
        }
        
        // Create demo questions for English Language
        self::create_demo_questions();
    }
    
    /**
     * Create comprehensive demo questions
     */
    private static function create_demo_questions() {
        global $wpdb;
        
        // Get English Language subject IDs
        $english_subjects = $wpdb->get_results(
            "SELECT id, exam_id FROM {$wpdb->prefix}aiqs_subjects 
             WHERE name = 'English Language' AND status = 'active'"
        );
        
        // Enhanced demo questions with better variety
        $demo_questions = [
            [
                'question' => 'Which of the following is a noun?',
                'option_a' => 'Run quickly',
                'option_b' => 'Beautiful garden',
                'option_c' => 'The house',
                'option_d' => 'Very tall',
                'correct_answer' => 'C',
                'explanation' => 'A noun is a word that names a person, place, thing, or idea. "House" is a noun as it names a thing.',
                'difficulty' => 'easy',
                'source' => 'demo'
            ],
            [
                'question' => 'Identify the correct sentence:',
                'option_a' => 'She don\'t like apples',
                'option_b' => 'She doesn\'t likes apples',
                'option_c' => 'She doesn\'t like apples',
                'option_d' => 'She not like apples',
                'correct_answer' => 'C',
                'explanation' => 'In the present simple tense with third person singular (she), we use "doesn\'t" followed by the base form of the verb.',
                'difficulty' => 'medium',
                'source' => 'demo'
            ],
            [
                'question' => 'What is the meaning of "ubiquitous"?',
                'option_a' => 'Rare and uncommon',
                'option_b' => 'Present everywhere',
                'option_c' => 'Unique and special',
                'option_d' => 'Useful and practical',
                'correct_answer' => 'B',
                'explanation' => 'Ubiquitous means present, appearing, or found everywhere; omnipresent.',
                'difficulty' => 'hard',
                'source' => 'demo'
            ],
            [
                'question' => 'Choose the word that best completes the sentence: "The student was _____ for arriving late to class."',
                'option_a' => 'complemented',
                'option_b' => 'complimented',
                'option_c' => 'reprimanded',
                'option_d' => 'recommended',
                'correct_answer' => 'C',
                'explanation' => 'Reprimanded means to rebuke or scold someone, which fits the context of punishment for being late.',
                'difficulty' => 'medium',
                'source' => 'demo'
            ],
            [
                'question' => 'Which literary device is used in "The wind whispered through the trees"?',
                'option_a' => 'Metaphor',
                'option_b' => 'Simile',
                'option_c' => 'Personification',
                'option_d' => 'Alliteration',
                'correct_answer' => 'C',
                'explanation' => 'Personification gives human characteristics to non-human things. Here, the wind is given the human ability to whisper.',
                'difficulty' => 'medium',
                'source' => 'demo'
            ]
        ];
        
        // Add questions to all English Language subjects
        foreach ($english_subjects as $subject) {
            foreach ($demo_questions as $question) {
                $wpdb->insert(
                    $wpdb->prefix . 'aiqs_questions',
                    array_merge(
                        $question,
                        ['subject_id' => $subject->id]
                    )
                );
            }
        }
    }
}
