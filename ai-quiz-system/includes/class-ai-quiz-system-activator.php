<?php
/**
 * Fired during plugin activation.
 */
class AI_Quiz_System_Activator {

    /**
     * Create necessary database tables and setup initial data.
     */
    public static function activate() {
        // Create database tables
        AI_Quiz_System_DB::create_tables();
        
        // Create default settings
        if (!get_option('aiqs_ai_settings')) {
            update_option('aiqs_ai_settings', [
                'provider' => 'openai',
                'api_key' => '',
                'enable_chatbot' => true,
                'question_source' => 'ai',
                'fallback_to_stored' => true
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
     * Create demo data if no exams exist.
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
                'description' => 'Unified Tertiary Matriculation Examination',
                'subject_count' => 4,
                'time_limit' => 7200, // 2 hours
                'passing_score' => 60,
                'question_count_per_subject' => 20,
                'status' => 'active'
            ]
        );
        
        $utme_id = $wpdb->insert_id;
        
        // Create UTME subjects
        $utme_subjects = [
            'English Language',
            'Mathematics',
            'Physics',
            'Chemistry',
            'Biology',
            'Literature',
            'Government',
            'Economics',
            'Geography',
            'CRK',
            'IRK',
            'History',
            'French'
        ];
        
        foreach ($utme_subjects as $subject) {
            $wpdb->insert(
                $wpdb->prefix . 'aiqs_subjects',
                [
                    'name' => $subject,
                    'exam_id' => $utme_id,
                    'status' => 'active'
                ]
            );
        }
        
        // Create WAEC exam
        $wpdb->insert(
            $wpdb->prefix . 'aiqs_exams',
            [
                'name' => 'WAEC',
                'description' => 'West African Examinations Council',
                'subject_count' => 9,
                'time_limit' => 10800, // 3 hours
                'passing_score' => 50,
                'question_count_per_subject' => 50,
                'status' => 'active'
            ]
        );
        
        $waec_id = $wpdb->insert_id;
        
        // Create WAEC subjects (same as UTME)
        foreach ($utme_subjects as $subject) {
            $wpdb->insert(
                $wpdb->prefix . 'aiqs_subjects',
                [
                    'name' => $subject,
                    'exam_id' => $waec_id,
                    'status' => 'active'
                ]
            );
        }
        
        // Create demo questions for English Language
        $english_subject_id_utme = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}aiqs_subjects WHERE exam_id = %d AND name = 'English Language'",
                $utme_id
            )
        );
        
        $english_subject_id_waec = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}aiqs_subjects WHERE exam_id = %d AND name = 'English Language'",
                $waec_id
            )
        );
        
        // Demo questions
        $demo_questions = [
            [
                'question' => 'Which of the following is a noun?',
                'option_a' => 'Run',
                'option_b' => 'Quickly',
                'option_c' => 'Beautiful',
                'option_d' => 'House',
                'correct_answer' => 'D',
                'explanation' => 'A noun is a word that names a person, place, thing, or idea. "House" is a noun as it names a thing.',
                'difficulty' => 'easy',
                'source' => 'demo'
            ],
            [
                'question' => 'Identify the correct sentence:',
                'option_a' => 'She don\'t like apples.',
                'option_b' => 'She doesn\'t likes apples.',
                'option_c' => 'She doesn\'t like apples.',
                'option_d' => 'She not like apples.',
                'correct_answer' => 'C',
                'explanation' => 'In the present simple tense with the third person singular (she), we use "doesn\'t" followed by the base form of the verb.',
                'difficulty' => 'medium',
                'source' => 'demo'
            ],
            [
                'question' => 'What is the meaning of "ubiquitous"?',
                'option_a' => 'Rare',
                'option_b' => 'Present everywhere',
                'option_c' => 'Unique',
                'option_d' => 'Useful',
                'correct_answer' => 'B',
                'explanation' => 'Ubiquitous means present, appearing, or found everywhere.',
                'difficulty' => 'hard',
                'source' => 'demo'
            ]
        ];
        
        // Add demo questions to both UTME and WAEC English subjects
        foreach ($demo_questions as $question) {
            // For UTME
            if ($english_subject_id_utme) {
                $wpdb->insert(
                    $wpdb->prefix . 'aiqs_questions',
                    array_merge(
                        $question,
                        ['subject_id' => $english_subject_id_utme]
                    )
                );
            }
            
            // For WAEC
            if ($english_subject_id_waec) {
                $wpdb->insert(
                    $wpdb->prefix . 'aiqs_questions',
                    array_merge(
                        $question,
                        ['subject_id' => $english_subject_id_waec]
                    )
                );
            }
        }
    }
}