<?php
/**
 * Enhanced AI Engine with Conversational Memory and Personalization
 * Replace your existing class-ai-quiz-system-engine.php with this enhanced version
 */

class AI_Quiz_System_Engine {
    private $provider;
    private $api_key;
    private $endpoint;
    private $model;
    private $db;
    
    public function __construct() {
        $options = get_option('aiqs_ai_settings', []);
        
        $this->provider = isset($options['provider']) ? $options['provider'] : 'openai';
        $this->api_key = isset($options['api_key']) ? $options['api_key'] : '';
        
        if ($this->provider === 'openai') {
            $this->endpoint = 'https://api.openai.com/v1/chat/completions';
            $this->model = 'gpt-3.5-turbo';
        } else {
            $this->endpoint = 'https://api.anthropic.com/v1/messages';
            $this->model = 'claude-3-haiku-20240307';
        }
        
        $this->db = new AI_Quiz_System_DB();
        
        aiqs_debug_log('Enhanced AI Engine initialized with provider: ' . $this->provider);
    }
    
    /**
     * Check if AI is configured
     */
    public function is_configured() {
        $configured = !empty($this->api_key);
        aiqs_debug_log('AI configured check: ' . ($configured ? 'Yes' : 'No'));
        return $configured;
    }
    
    /**
     * Test AI connection
     */
    public function test_connection() {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => __('AI API key is not configured.', 'ai-quiz-system')
            ];
        }
        
        $prompt = "Hello, this is a test message to confirm API connection. Please respond with 'Connection successful.'";
        
        $response = $this->send_request($prompt);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }
        
        return [
            'success' => true,
            'message' => __('Connection successful!', 'ai-quiz-system'),
            'response' => $response
        ];
    }
    
    /**
     * Enhanced send request with context awareness
     */
    private function send_request($prompt, $system_prompt = null, $max_tokens = 2000, $conversation_context = null) {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', __('AI API key is not configured.', 'ai-quiz-system'));
        }
        
        $headers = [];
        $body = [];
        
        if ($this->provider === 'openai') {
            $headers = [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ];
            
            $messages = [];
            
            // Add system prompt
            if ($system_prompt) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $system_prompt
                ];
            }
            
            // Add conversation context if available
            if ($conversation_context && !empty($conversation_context)) {
                foreach ($conversation_context as $context_message) {
                    $messages[] = [
                        'role' => $context_message['role'],
                        'content' => $context_message['content']
                    ];
                }
            }
            
            // Add current prompt
            $messages[] = [
                'role' => 'user',
                'content' => $prompt
            ];
            
            $body = [
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => $max_tokens,
                'temperature' => 0.7,
            ];
        } else {
            // Claude/Anthropic API
            $headers = [
                'x-api-key' => $this->api_key,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01'
            ];
            
            $system = $system_prompt ? $system_prompt : 'You are Rita, an expert AI tutor for Nigerian/African students. You are warm, encouraging, culturally aware, and provide personalized educational guidance.';
            
            $messages = [];
            
            // Add conversation context if available
            if ($conversation_context && !empty($conversation_context)) {
                foreach ($conversation_context as $context_message) {
                    $messages[] = [
                        'role' => $context_message['role'],
                        'content' => $context_message['content']
                    ];
                }
            }
            
            // Add current prompt
            $messages[] = [
                'role' => 'user',
                'content' => $prompt
            ];
            
            $body = [
                'model' => $this->model,
                'max_tokens' => $max_tokens,
                'temperature' => 0.7,
                'system' => $system,
                'messages' => $messages
            ];
        }
        
        $args = [
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 60,
            'redirection' => 5,
            'httpversion' => '1.1',
            'method' => 'POST',
            'data_format' => 'body',
            'sslverify' => true,
        ];
        
        aiqs_debug_log('AI API Request', [
            'provider' => $this->provider,
            'endpoint' => $this->endpoint,
            'messages_count' => count($messages ?? [])
        ]);
        
        $response = wp_remote_post($this->endpoint, $args);
        
        if (is_wp_error($response)) {
            aiqs_debug_log('AI API Error', $response->get_error_message());
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            aiqs_debug_log('AI API Error Response', [
                'status' => $status_code,
                'body' => $body
            ]);
            return new WP_Error('api_error', sprintf(__('API error (status code %d): %s', 'ai-quiz-system'), $status_code, $body));
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            aiqs_debug_log('AI API JSON Decode Error', json_last_error_msg());
            return new WP_Error('json_decode_error', __('Failed to decode JSON response.', 'ai-quiz-system'));
        }
        
        // Extract the content based on provider
        if ($this->provider === 'openai') {
            if (isset($data['choices'][0]['message']['content'])) {
                return $data['choices'][0]['message']['content'];
            } elseif (isset($data['error'])) {
                aiqs_debug_log('OpenAI Error', $data['error']);
                return new WP_Error('openai_error', $data['error']['message'] ?? __('Unknown OpenAI error', 'ai-quiz-system'));
            } else {
                return new WP_Error('openai_format_error', __('Unexpected response format from OpenAI.', 'ai-quiz-system'));
            }
        } else {
            // Claude/Anthropic API
            if (isset($data['content']) && is_array($data['content']) && !empty($data['content'][0]['text'])) {
                return $data['content'][0]['text'];
            } elseif (isset($data['completion'])) {
                return $data['completion'];
            } elseif (isset($data['error'])) {
                aiqs_debug_log('Claude Error', $data['error']);
                return new WP_Error('claude_error', $data['error']['message'] ?? __('Unknown Claude AI error', 'ai-quiz-system'));
            } else {
                return new WP_Error('claude_format_error', __('Unexpected response format from Claude AI.', 'ai-quiz-system'));
            }
        }
    }
    
    /**
     * Enhanced conversational AI with memory and personalization
     */
    public function process_conversational_query($query, $user_id = null, $session_id = null, $context = []) {
        aiqs_debug_log('Processing conversational query', [
            'user_id' => $user_id,
            'session_id' => $session_id,
            'query_length' => strlen($query)
        ]);
        
        if (!$this->is_configured()) {
            return $this->generate_fallback_response($query);
        }
        
        // Get or create student profile
        $student_profile = $this->get_or_create_student_profile($user_id);
        
        // Get or create conversation
        $conversation = $this->get_or_create_conversation($user_id, $session_id);
        
        // Get conversation history (last 10 messages for context)
        $conversation_history = $this->get_conversation_history($conversation['id'], 10);
        
        // Get recent performance data
        $performance_data = $this->get_student_performance_summary($user_id);
        
        // Detect emotion/mood from query
        $emotion = $this->detect_emotion($query);
        
        // Extract topics from query
        $topics = $this->extract_topics($query);
        
        // Build comprehensive context
        $full_context = $this->build_conversation_context([
            'student_profile' => $student_profile,
            'conversation_history' => $conversation_history,
            'performance_data' => $performance_data,
            'current_emotion' => $emotion,
            'topics' => $topics,
            'additional_context' => $context
        ]);
        
        // Create personalized system prompt
        $system_prompt = $this->create_personalized_system_prompt($student_profile, $performance_data);
        
        // Prepare conversation context for AI
        $ai_conversation_context = $this->prepare_ai_context($conversation_history);
        
        // Send request to AI with full context
        $ai_response = $this->send_request(
            $query,
            $system_prompt,
            1500, // max tokens
            $ai_conversation_context
        );
        
        if (is_wp_error($ai_response)) {
            $ai_response = $this->generate_fallback_response($query);
        }
        
        // Analyze response quality and extract insights
        $response_analysis = $this->analyze_response($query, $ai_response, $full_context);
        
        // Store the conversation message
        $message_id = $this->store_conversation_message([
            'conversation_id' => $conversation['id'],
            'user_id' => $user_id,
            'user_message' => $query,
            'ai_response' => $ai_response,
            'context_data' => json_encode($full_context),
            'emotion_detected' => $emotion,
            'topics_mentioned' => json_encode($topics),
            'response_analysis' => json_encode($response_analysis)
        ]);
        
        // Update conversation metadata
        $this->update_conversation_metadata($conversation['id'], [
            'last_message_at' => current_time('mysql'),
            'message_count' => $conversation['message_count'] + 1,
            'mood_detected' => $emotion,
            'topics_covered' => $this->merge_topics($conversation['topics_covered'], $topics)
        ]);
        
        // Update student profile based on interaction
        $this->update_student_profile($user_id, $query, $ai_response, $topics, $emotion);
        
        // Generate study recommendations if appropriate
        $this->generate_study_recommendations($user_id, $topics, $performance_data, $emotion);
        
        // Log learning analytics
        $this->log_learning_analytics($user_id, [
            'activity_type' => 'chatbot_conversation',
            'conversation_id' => $conversation['id'],
            'topics' => $topics,
            'emotion' => $emotion,
            'response_quality' => $response_analysis['quality_score'] ?? 0.5
        ]);
        
        return [
            'success' => true,
            'response' => $ai_response,
            'conversation_id' => $conversation['id'],
            'message_id' => $message_id,
            'student_insights' => $this->get_student_insights($user_id),
            'recommendations' => $this->get_recent_recommendations($user_id, 3)
        ];
    }
    
    /**
     * Get or create student profile
     */
    private function get_or_create_student_profile($user_id) {
        if (!$user_id) {
            return $this->get_default_profile();
        }
        
        global $wpdb;
        
        $profile = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aiqs_student_profiles WHERE user_id = %d",
                $user_id
            ),
            ARRAY_A
        );
        
        if (!$profile) {
            // Create new profile
            $user_data = get_userdata($user_id);
            $profile_data = [
                'user_id' => $user_id,
                'learning_style' => 'mixed',
                'strong_subjects' => json_encode([]),
                'weak_subjects' => json_encode([]),
                'preferred_difficulty' => 'medium',
                'study_goals' => '',
                'personality_traits' => json_encode(['curious', 'motivated']),
                'communication_style' => 'friendly',
                'total_conversations' => 0,
                'average_session_length' => 0
            ];
            
            $wpdb->insert($wpdb->prefix . 'aiqs_student_profiles', $profile_data);
            $profile = $profile_data;
            $profile['id'] = $wpdb->insert_id;
        }
        
        return $profile;
    }
    
    /**
     * Get or create conversation
     */
    private function get_or_create_conversation($user_id, $session_id) {
        global $wpdb;
        
        // Try to find existing active conversation
        $conversation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aiqs_conversations 
                 WHERE (user_id = %d OR session_id = %s) 
                 AND status = 'active' 
                 AND last_message_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 ORDER BY last_message_at DESC LIMIT 1",
                $user_id ?: 0, $session_id
            ),
            ARRAY_A
        );
        
        if (!$conversation) {
            // Create new conversation
            $conversation_data = [
                'user_id' => $user_id,
                'session_id' => $session_id,
                'conversation_title' => $this->generate_conversation_title(),
                'message_count' => 0,
                'learning_context' => json_encode([]),
                'topics_covered' => json_encode([]),
                'status' => 'active'
            ];
            
            $wpdb->insert($wpdb->prefix . 'aiqs_conversations', $conversation_data);
            $conversation = $conversation_data;
            $conversation['id'] = $wpdb->insert_id;
        }
        
        return $conversation;
    }
    
    /**
     * Get conversation history
     */
    private function get_conversation_history($conversation_id, $limit = 10) {
        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT user_message, ai_response, emotion_detected, topics_mentioned, timestamp
                 FROM {$wpdb->prefix}aiqs_conversation_messages 
                 WHERE conversation_id = %d 
                 ORDER BY timestamp DESC LIMIT %d",
                $conversation_id, $limit
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get student performance summary
     */
    private function get_student_performance_summary($user_id) {
        if (!$user_id) {
            return [];
        }
        
        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.name as subject_name, p.average_score, p.improvement_trend, 
                        p.strengths, p.weaknesses, p.total_attempts
                 FROM {$wpdb->prefix}aiqs_quiz_performance p
                 JOIN {$wpdb->prefix}aiqs_subjects s ON p.subject_id = s.id
                 WHERE p.user_id = %d AND p.total_attempts > 0
                 ORDER BY p.updated_at DESC LIMIT 10",
                $user_id
            ),
            ARRAY_A
        );
    }
    
    /**
     * Detect emotion from text
     */
    private function detect_emotion($text) {
        $text_lower = strtolower($text);
        
        $emotion_patterns = [
            'frustrated' => ['frustrated', 'angry', 'annoyed', 'difficult', 'hard', 'confused', 'stuck'],
            'excited' => ['excited', 'great', 'awesome', 'amazing', 'love', 'fantastic', 'wonderful'],
            'worried' => ['worried', 'anxious', 'nervous', 'scared', 'afraid', 'concerned'],
            'confident' => ['confident', 'ready', 'prepared', 'easy', 'understand', 'got it'],
            'curious' => ['how', 'why', 'what', 'explain', 'tell me', 'interested'],
            'tired' => ['tired', 'exhausted', 'sleepy', 'worn out', 'drained']
        ];
        
        foreach ($emotion_patterns as $emotion => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($text_lower, $pattern) !== false) {
                    return $emotion;
                }
            }
        }
        
        return 'neutral';
    }
    
    /**
     * Extract topics from text
     */
    private function extract_topics($text) {
        $text_lower = strtolower($text);
        $topics = [];
        
        $subject_patterns = [
            'mathematics' => ['math', 'mathematics', 'algebra', 'geometry', 'calculus', 'numbers'],
            'english' => ['english', 'grammar', 'writing', 'reading', 'literature', 'essay'],
            'physics' => ['physics', 'motion', 'force', 'energy', 'waves', 'electricity'],
            'chemistry' => ['chemistry', 'atoms', 'molecules', 'reactions', 'elements'],
            'biology' => ['biology', 'cells', 'genetics', 'evolution', 'anatomy', 'plants', 'animals'],
            'geography' => ['geography', 'maps', 'countries', 'climate', 'rivers', 'mountains'],
            'history' => ['history', 'past', 'ancient', 'war', 'civilization', 'timeline'],
            'economics' => ['economics', 'money', 'market', 'business', 'trade', 'finance']
        ];
        
        foreach ($subject_patterns as $subject => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($text_lower, $pattern) !== false) {
                    $topics[] = $subject;
                    break;
                }
            }
        }
        
        // Add study-related topics
        $study_patterns = [
            'study_methods' => ['study', 'learn', 'memorize', 'practice', 'review'],
            'exam_prep' => ['exam', 'test', 'quiz', 'preparation', 'waec', 'utme', 'neco'],
            'time_management' => ['time', 'schedule', 'plan', 'organize', 'manage'],
            'motivation' => ['motivation', 'inspire', 'encourage', 'goal', 'achieve']
        ];
        
        foreach ($study_patterns as $topic => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($text_lower, $pattern) !== false) {
                    $topics[] = $topic;
                    break;
                }
            }
        }
        
        return array_unique($topics);
    }
    
    /**
     * Build comprehensive conversation context
     */
    private function build_conversation_context($data) {
        return [
            'student_profile' => $data['student_profile'],
            'recent_performance' => $data['performance_data'],
            'conversation_history_count' => count($data['conversation_history']),
            'current_emotion' => $data['current_emotion'],
            'topics_discussed' => $data['topics'],
            'learning_context' => $data['additional_context'],
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Create personalized system prompt
     */
    private function create_personalized_system_prompt($profile, $performance_data) {
        $user_name = '';
        if ($profile['user_id']) {
            $user = get_userdata($profile['user_id']);
            $user_name = $user ? $user->display_name : '';
        }
        
        $system_prompt = "You are Rita, an AI tutor specifically designed for Nigerian and African students. You are warm, encouraging, culturally aware, and provide personalized educational guidance.\n\n";
        
        // Add student-specific context
        if ($user_name) {
            $system_prompt .= "You are currently helping {$user_name}. ";
        }
        
        // Add learning style information
        $system_prompt .= "Student's learning style: {$profile['learning_style']}. ";
        $system_prompt .= "Preferred difficulty: {$profile['preferred_difficulty']}. ";
        $system_prompt .= "Communication style: {$profile['communication_style']}. ";
        
        // Add performance context
        if (!empty($performance_data)) {
            $strong_subjects = [];
            $weak_subjects = [];
            
            foreach ($performance_data as $subject) {
                if ($subject['average_score'] >= 75) {
                    $strong_subjects[] = $subject['subject_name'];
                } elseif ($subject['average_score'] < 50) {
                    $weak_subjects[] = $subject['subject_name'];
                }
            }
            
            if (!empty($strong_subjects)) {
                $system_prompt .= "Student shows strength in: " . implode(', ', $strong_subjects) . ". ";
            }
            
            if (!empty($weak_subjects)) {
                $system_prompt .= "Student needs support in: " . implode(', ', $weak_subjects) . ". ";
            }
        }
        
        $system_prompt .= "\n\nAlways:\n";
        $system_prompt .= "- Be encouraging and supportive\n";
        $system_prompt .= "- Use examples relevant to Nigerian/African context\n";
        $system_prompt .= "- Provide practical study tips\n";
        $system_prompt .= "- Break down complex concepts into simple steps\n";
        $system_prompt .= "- Ask follow-up questions to ensure understanding\n";
        $system_prompt .= "- Celebrate progress and achievements\n";
        $system_prompt .= "- Be patient and understanding of learning challenges\n";
        $system_prompt .= "- Keep responses concise but informative (under 200 words usually)\n";
        
        return $system_prompt;
    }
    
    /**
     * Prepare AI conversation context
     */
    private function prepare_ai_context($conversation_history) {
        $context = [];
        
        // Convert conversation history to AI format (reverse order for chronological)
        $history = array_reverse($conversation_history);
        
        foreach ($history as $message) {
            if (!empty($message['user_message'])) {
                $context[] = [
                    'role' => 'user',
                    'content' => $message['user_message']
                ];
            }
            
            if (!empty($message['ai_response'])) {
                $context[] = [
                    'role' => 'assistant',
                    'content' => $message['ai_response']
                ];
            }
        }
        
        return $context;
    }
    
    /**
     * Analyze response quality and extract insights
     */
    private function analyze_response($query, $response, $context) {
        $analysis = [
            'quality_score' => 0.7, // Default score
            'topics_addressed' => [],
            'emotion_appropriate' => true,
            'personalization_level' => 'medium',
            'educational_value' => 'high'
        ];
        
        // Simple quality metrics
        $response_length = strlen($response);
        if ($response_length > 50 && $response_length < 500) {
            $analysis['quality_score'] += 0.2;
        }
        
        // Check if response addresses detected emotion
        $emotion = $context['current_emotion'] ?? 'neutral';
        if ($emotion === 'frustrated' && (stripos($response, 'understand') !== false || stripos($response, 'help') !== false)) {
            $analysis['emotion_appropriate'] = true;
            $analysis['quality_score'] += 0.1;
        }
        
        return $analysis;
    }
    
    /**
     * Store conversation message
     */
    private function store_conversation_message($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'aiqs_conversation_messages',
            [
                'conversation_id' => $data['conversation_id'],
                'user_id' => $data['user_id'],
                'message_type' => 'user',
                'user_message' => $data['user_message'],
                'ai_response' => $data['ai_response'],
                'context_data' => $data['context_data'],
                'emotion_detected' => $data['emotion_detected'],
                'topics_mentioned' => $data['topics_mentioned']
            ]
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update conversation metadata
     */
    private function update_conversation_metadata($conversation_id, $updates) {
        global $wpdb;
        
        return $wpdb->update(
            $wpdb->prefix . 'aiqs_conversations',
            $updates,
            ['id' => $conversation_id]
        );
    }
    
    /**
     * Update student profile based on interaction
     */
    private function update_student_profile($user_id, $query, $response, $topics, $emotion) {
        if (!$user_id) return;
        
        global $wpdb;
        
        // Get current profile
        $profile = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aiqs_student_profiles WHERE user_id = %d",
                $user_id
            ),
            ARRAY_A
        );
        
        if (!$profile) return;
        
        // Update conversation count and last activity
        $updates = [
            'total_conversations' => $profile['total_conversations'] + 1,
            'last_activity' => current_time('mysql')
        ];
        
        // Update personality traits based on detected emotion patterns
        $personality_traits = json_decode($profile['personality_traits'], true) ?: [];
        
        if ($emotion === 'curious' && !in_array('curious', $personality_traits)) {
            $personality_traits[] = 'curious';
        } elseif ($emotion === 'confident' && !in_array('confident', $personality_traits)) {
            $personality_traits[] = 'confident';
        }
        
        $updates['personality_traits'] = json_encode(array_unique($personality_traits));
        
        $wpdb->update(
            $wpdb->prefix . 'aiqs_student_profiles',
            $updates,
            ['user_id' => $user_id]
        );
    }
    
    /**
     * Generate study recommendations
     */
    private function generate_study_recommendations($user_id, $topics, $performance_data, $emotion) {
        if (!$user_id || empty($topics)) return;
        
        global $wpdb;
        
        foreach ($topics as $topic) {
            // Check if we have performance data for this topic
            $needs_help = false;
            foreach ($performance_data as $subject) {
                if (stripos($subject['subject_name'], $topic) !== false && $subject['average_score'] < 60) {
                    $needs_help = true;
                    break;
                }
            }
            
            if ($needs_help || $emotion === 'frustrated' || $emotion === 'worried') {
                $recommendation = $this->create_study_recommendation($topic, $emotion, $performance_data);
                
                if ($recommendation) {
                    // Check if similar recommendation already exists
                    $existing = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT id FROM {$wpdb->prefix}aiqs_study_recommendations 
                             WHERE user_id = %d AND recommendation_type = %s 
                             AND status = 'pending' AND generated_date > DATE_SUB(NOW(), INTERVAL 7 DAY)",
                            $user_id, $topic
                        )
                    );
                    
                    if (!$existing) {
                        $wpdb->insert(
                            $wpdb->prefix . 'aiqs_study_recommendations',
                            [
                                'user_id' => $user_id,
                                'recommendation_type' => $topic,
                                'recommendation_text' => $recommendation['text'],
                                'priority_level' => $recommendation['priority'],
                                'status' => 'pending',
                                'generated_by' => 'ai_conversation',
                                'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
                            ]
                        );
                    }
                }
            }
        }
    }
    
    /**
     * Create study recommendation
     */
    private function create_study_recommendation($topic, $emotion, $performance_data) {
        $recommendations = [
            'mathematics' => [
                'text' => 'Practice daily math problems, starting with basic concepts. Use visual aids and real-world examples to understand abstract concepts better.',
                'priority' => 'high'
            ],
            'english' => [
                'text' => 'Read Nigerian literature and practice writing essays. Focus on grammar rules and expand vocabulary through daily reading.',
                'priority' => 'high'
            ],
            'study_methods' => [
                'text' => 'Try the Pomodoro technique: Study for 25 minutes, then take a 5-minute break. Create a quiet study space and remove distractions.',
                'priority' => 'medium'
            ],
            'exam_prep' => [
                'text' => 'Create a study timetable leading up to your exam. Practice with past questions and time yourself to improve speed and accuracy.',
                'priority' => 'high'
            ]
        ];
        
        if (isset($recommendations[$topic])) {
            $rec = $recommendations[$topic];
            
            // Adjust priority based on emotion
            if ($emotion === 'frustrated' || $emotion === 'worried') {
                $rec['priority'] = 'high';
            }
            
            return $rec;
        }
        
        return null;
    }
    
    /**
     * Log learning analytics
     */
    private function log_learning_analytics($user_id, $data) {
        if (!$user_id) return;
        
        global $wpdb;
        
        $analytics_data = [
            'user_id' => $user_id,
            'session_date' => current_time('Y-m-d'),
            'activity_type' => $data['activity_type'],
            'subject_focus' => !empty($data['topics']) ? implode(',', $data['topics']) : null,
            'time_spent' => 5, // Estimate conversation time
            'conversation_quality_score' => $data['response_quality'] ?? 0.5,
            'engagement_level' => $this->calculate_engagement_level($data['emotion'] ?? 'neutral'),
            'insights' => json_encode([
                'emotion_detected' => $data['emotion'] ?? 'neutral',
                'topics_discussed' => $data['topics'] ?? [],
                'conversation_id' => $data['conversation_id'] ?? null
            ])
        ];
        
        // Check if entry exists for today
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}aiqs_learning_analytics 
                 WHERE user_id = %d AND session_date = %s AND activity_type = %s",
                $user_id, current_time('Y-m-d'), $data['activity_type']
            )
        );
        
        if ($existing) {
            // Update existing entry
            $wpdb->update(
                $wpdb->prefix . 'aiqs_learning_analytics',
                [
                    'time_spent' => 'time_spent + 5',
                    'conversation_quality_score' => $analytics_data['conversation_quality_score'],
                    'engagement_level' => $analytics_data['engagement_level']
                ],
                ['id' => $existing]
            );
        } else {
            // Create new entry
            $wpdb->insert($wpdb->prefix . 'aiqs_learning_analytics', $analytics_data);
        }
    }
    
    /**
     * Calculate engagement level
     */
    private function calculate_engagement_level($emotion) {
        $engagement_map = [
            'excited' => 'high',
            'curious' => 'high',
            'confident' => 'high',
            'frustrated' => 'medium',
            'worried' => 'medium',
            'tired' => 'low',
            'neutral' => 'medium'
        ];
        
        return $engagement_map[$emotion] ?? 'medium';
    }
    
    /**
     * Get student insights
     */
    private function get_student_insights($user_id) {
        if (!$user_id) return [];
        
        global $wpdb;
        
        $insights = [];
        
        // Get recent learning trends
        $recent_analytics = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aiqs_learning_analytics 
                 WHERE user_id = %d AND session_date > DATE_SUB(NOW(), INTERVAL 7 DAY)
                 ORDER BY session_date DESC",
                $user_id
            ),
            ARRAY_A
        );
        
        if (!empty($recent_analytics)) {
            $total_time = array_sum(array_column($recent_analytics, 'time_spent'));
            $avg_engagement = array_sum(array_map(function($a) {
                return $a['engagement_level'] === 'high' ? 3 : ($a['engagement_level'] === 'medium' ? 2 : 1);
            }, $recent_analytics)) / count($recent_analytics);
            
            $insights['weekly_study_time'] = $total_time;
            $insights['engagement_level'] = $avg_engagement > 2.5 ? 'high' : ($avg_engagement > 1.5 ? 'medium' : 'low');
            $insights['active_days'] = count($recent_analytics);
        }
        
        return $insights;
    }
    
    /**
     * Get recent recommendations
     */
    private function get_recent_recommendations($user_id, $limit = 3) {
        if (!$user_id) return [];
        
        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT recommendation_text, priority_level, recommendation_type, generated_date
                 FROM {$wpdb->prefix}aiqs_study_recommendations 
                 WHERE user_id = %d AND status = 'pending'
                 ORDER BY priority_level = 'high' DESC, generated_date DESC 
                 LIMIT %d",
                $user_id, $limit
            ),
            ARRAY_A
        );
    }
    
    /**
     * Generate fallback response
     */
    private function generate_fallback_response($query) {
        $query_lower = strtolower($query);
        
        // Enhanced fallback responses
        if (strpos($query_lower, 'hello') !== false || strpos($query_lower, 'hi') !== false) {
            return "Hello! I'm Rita, your AI study assistant. I'm here to help you with your studies. What subject would you like to work on today?";
        }
        
        if (strpos($query_lower, 'help') !== false) {
            return "I'm here to help! I can assist you with:\n• Understanding difficult concepts\n• Study tips and techniques\n• Exam preparation strategies\n• Subject-specific guidance\n• Motivation and encouragement\n\nWhat would you like to explore?";
        }
        
        if (strpos($query_lower, 'math') !== false) {
            return "Mathematics can be challenging, but with the right approach, it becomes much easier! Try breaking problems into smaller steps, practice regularly, and don't hesitate to ask for help. What specific math topic are you working on?";
        }
        
        if (strpos($query_lower, 'english') !== false) {
            return "English language skills improve with consistent practice! Focus on reading different types of texts, practice writing regularly, and pay attention to grammar. Reading Nigerian authors can also help you connect with the language. What aspect of English would you like to improve?";
        }
        
        if (strpos($query_lower, 'exam') !== false || strpos($query_lower, 'test') !== false) {
            return "Exam preparation is key to success! Create a study schedule, practice with past questions, get enough rest, and stay confident. Remember, preparation reduces anxiety. Which exam are you preparing for?";
        }
        
        // Default response
        return "I understand you're looking for help with your studies. While I have limited capabilities right now, I'm here to support your learning journey. Could you tell me more specifically what you'd like to work on? I'll do my best to guide you!";
    }
    
    /**
     * Generate conversation title
     */
    private function generate_conversation_title() {
        $titles = [
            'Study Session',
            'Learning Chat',
            'Academic Discussion',
            'Study Help',
            'Learning Support',
            'Educational Guidance'
        ];
        
        return $titles[array_rand($titles)] . ' - ' . date('M j, Y');
    }
    
    /**
     * Get default profile for guest users
     */
    private function get_default_profile() {
        return [
            'id' => 0,
            'user_id' => null,
            'learning_style' => 'mixed',
            'strong_subjects' => json_encode([]),
            'weak_subjects' => json_encode([]),
            'preferred_difficulty' => 'medium',
            'study_goals' => '',
            'personality_traits' => json_encode(['curious']),
            'communication_style' => 'friendly',
            'total_conversations' => 0,
            'average_session_length' => 0
        ];
    }
    
    /**
     * Merge topics arrays
     */
    private function merge_topics($existing_topics_json, $new_topics) {
        $existing_topics = json_decode($existing_topics_json, true) ?: [];
        $merged = array_unique(array_merge($existing_topics, $new_topics));
        return json_encode($merged);
    }
    
    /**
     * Enhanced question generation with better error handling and fallbacks
     */
    public function generate_questions($subject, $count = 5, $difficulty = 'mixed') {
        aiqs_debug_log('Generating questions', [
            'subject' => $subject,
            'count' => $count,
            'difficulty' => $difficulty
        ]);
        
        if (!$this->is_configured()) {
            aiqs_debug_log('AI not configured for question generation');
            return $this->generate_demo_questions($subject, $count, $difficulty);
        }
        
        try {
            $system_prompt = "You are an expert in creating educational quiz questions for {$subject} subject, tailored for Nigerian/African students. Create questions that follow the Nigerian/African curriculum and educational standards.";
            
            $prompt = "Create {$count} multiple-choice questions for {$subject} subject, suitable for Nigerian/African students. ";
            
            switch ($difficulty) {
                case 'easy':
                    $prompt .= "Make the questions relatively easy.";
                    break;
                case 'medium':
                    $prompt .= "Make the questions of medium difficulty.";
                    break;
                case 'hard':
                    $prompt .= "Make the questions challenging.";
                    break;
                default:
                    $prompt .= "Provide a mix of easy, medium, and hard questions.";
            }
            
            $prompt .= " For each question, provide exactly:
1. A question
2. Four options labeled A, B, C, and D
3. The correct answer (just the letter: A, B, C, or D)
4. A brief explanation of why the answer is correct
5. Difficulty level (easy, medium, or hard)

Return the result as a JSON array with the following format:
[
  {
    \"question\": \"Your question here?\",
    \"option_a\": \"Option A text\",
    \"option_b\": \"Option B text\",
    \"option_c\": \"Option C text\",
    \"option_d\": \"Option D text\",
    \"correct_answer\": \"A\",
    \"explanation\": \"Explanation text\",
    \"difficulty\": \"medium\"
  }
]

Make sure the JSON is valid and properly formatted. Do not include any text before or after the JSON array.";
            
            $response = $this->send_request($prompt, $system_prompt);
            
            if (is_wp_error($response)) {
                aiqs_debug_log('AI request failed', $response->get_error_message());
                return $this->generate_demo_questions($subject, $count, $difficulty);
            }
            
            // Clean up response to extract JSON
            $response = preg_replace('/```(?:json)?(.*?)```/s', '$1', $response);
            
            // Try to extract JSON array
            if (preg_match('/\[\s*\{.*\}\s*\]/s', $response, $matches)) {
                $json_content = $matches[0];
            } else {
                $start = strpos($response, '[');
                $end = strrpos($response, ']');
                
                if ($start !== false && $end !== false && $end > $start) {
                    $json_content = substr($response, $start, $end - $start + 1);
                } else {
                    aiqs_debug_log('No JSON structure found in response');
                    return $this->generate_demo_questions($subject, $count, $difficulty);
                }
            }
            
            // Try to decode the JSON
            $questions = json_decode($json_content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                aiqs_debug_log('JSON Decode Error', [
                    'error' => json_last_error_msg(),
                    'content_sample' => substr($json_content, 0, 200)
                ]);
                
                // Try to fix common JSON issues
                $json_content = $this->fix_json_content($json_content);
                $questions = json_decode($json_content, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->generate_demo_questions($subject, $count, $difficulty);
                }
            }
            
            // Validate questions format
            $valid_questions = [];
            foreach ($questions as $question) {
                if (!is_array($question)) {
                    continue;
                }
                
                // Ensure all required fields are present
                $required_fields = ['question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'explanation', 'difficulty'];
                $is_valid = true;
                
                foreach ($required_fields as $field) {
                    if (!isset($question[$field]) || empty($question[$field])) {
                        $question[$field] = $field === 'difficulty' ? 'medium' : ($field === 'explanation' ? 'No explanation provided.' : 'Missing content');
                        $is_valid = false;
                    }
                }
                
                // Ensure correct_answer is valid
                if (!in_array($question['correct_answer'], ['A', 'B', 'C', 'D'])) {
                    $answer = strtoupper(trim($question['correct_answer']));
                    if (in_array($answer, ['A', 'B', 'C', 'D'])) {
                        $question['correct_answer'] = $answer;
                    } else {
                        $question['correct_answer'] = 'A';
                        $is_valid = false;
                    }
                }
                
                $valid_questions[] = $question;
            }
            
            // Fill gap with demo questions if needed
            if (count($valid_questions) < $count) {
                $missing_count = $count - count($valid_questions);
                aiqs_debug_log("Not enough valid questions, adding $missing_count demo questions");
                
                $demo_questions = $this->generate_demo_questions($subject, $missing_count, $difficulty);
                $valid_questions = array_merge($valid_questions, $demo_questions);
            }
            
            aiqs_debug_log('Successfully generated questions', [
                'requested' => $count,
                'returned' => count($valid_questions)
            ]);
            
            return $valid_questions;
            
        } catch (Exception $e) {
            aiqs_debug_log('Exception in question generation', $e->getMessage());
            return $this->generate_demo_questions($subject, $count, $difficulty);
        }
    }
    
    /**
     * Generate demo questions as fallback
     */
    public function generate_demo_questions($subject, $count, $difficulty) {
        aiqs_debug_log("Generating $count demo questions for $subject with $difficulty difficulty");
        
        $demo_questions = [];
        $subject_lower = strtolower($subject);
        
        // Get subject-specific questions first
        $subject_specific_questions = $this->get_subject_specific_questions($subject);
        
        if (!empty($subject_specific_questions)) {
            $templates = $subject_specific_questions;
            
            // Add generic ones if needed
            if (count($templates) < $count) {
                $generic_templates = $this->get_generic_templates($subject);
                $templates = array_merge($templates, $generic_templates);
            }
        } else {
            $templates = $this->get_generic_templates($subject);
        }
        
        // Select questions up to requested count
        $selected_count = min($count, count($templates));
        $selected_indices = array_rand($templates, $selected_count);
        
        if (!is_array($selected_indices)) {
            $selected_indices = [$selected_indices];
        }
        
        foreach ($selected_indices as $index) {
            $template = $templates[$index];
            
            // Replace {subject} placeholder
            foreach ($template as $key => $value) {
                $template[$key] = str_replace('{subject}', $subject, $value);
            }
            
            // Set requested difficulty if specific
            if ($difficulty !== 'mixed') {
                $template['difficulty'] = $difficulty;
            }
            
            $demo_questions[] = $template;
        }
        
        // Repeat questions if we need more
        while (count($demo_questions) < $count) {
            $template = $templates[array_rand($templates)];
            
            foreach ($template as $key => $value) {
                $template[$key] = str_replace('{subject}', $subject, $value);
            }
            
            if ($difficulty !== 'mixed') {
                $template['difficulty'] = $difficulty;
            }
            
            $demo_questions[] = $template;
        }
        
        return array_slice($demo_questions, 0, $count);
    }
    
    /**
     * Get subject-specific question templates
     */
    private function get_subject_specific_questions($subject) {
        $subject_lower = strtolower($subject);
        
        if (strpos($subject_lower, 'math') !== false) {
            return [
                [
                    'question' => 'What is 15 + 27?',
                    'option_a' => '42',
                    'option_b' => '41',
                    'option_c' => '43',
                    'option_d' => '40',
                    'correct_answer' => 'A',
                    'explanation' => '15 + 27 = 42',
                    'difficulty' => 'easy'
                ],
                [
                    'question' => 'What is the square root of 64?',
                    'option_a' => '6',
                    'option_b' => '7',
                    'option_c' => '8',
                    'option_d' => '9',
                    'correct_answer' => 'C',
                    'explanation' => 'The square root of 64 is 8 because 8 × 8 = 64',
                    'difficulty' => 'medium'
                ]
            ];
        }
        
        if (strpos($subject_lower, 'english') !== false) {
            return [
                [
                    'question' => 'Which of the following is a noun?',
                    'option_a' => 'Run',
                    'option_b' => 'Beautiful',
                    'option_c' => 'Book',
                    'option_d' => 'Quickly',
                    'correct_answer' => 'C',
                    'explanation' => 'A noun is a person, place, or thing. "Book" is a thing.',
                    'difficulty' => 'easy'
                ],
                [
                    'question' => 'What is the past tense of "go"?',
                    'option_a' => 'Goes',
                    'option_b' => 'Going',
                    'option_c' => 'Gone',
                    'option_d' => 'Went',
                    'correct_answer' => 'D',
                    'explanation' => 'The past tense of "go" is "went".',
                    'difficulty' => 'easy'
                ]
            ];
        }
        
        // Add more subjects as needed
        return [];
    }
    
    /**
     * Get generic question templates
     */
    private function get_generic_templates($subject) {
        return [
            [
                'question' => 'Which of the following is most commonly associated with {subject}?',
                'option_a' => 'Basic concept A',
                'option_b' => 'Basic concept B',
                'option_c' => 'Basic concept C',
                'option_d' => 'Basic concept D',
                'correct_answer' => 'A',
                'explanation' => 'This is a fundamental concept in {subject}.',
                'difficulty' => 'easy'
            ],
            [
                'question' => 'What is a key characteristic of {subject}?',
                'option_a' => 'It involves systematic study',
                'option_b' => 'It requires memorization only',
                'option_c' => 'It has no practical applications',
                'option_d' => 'It is purely theoretical',
                'correct_answer' => 'A',
                'explanation' => '{subject} involves systematic study and understanding.',
                'difficulty' => 'medium'
            ]
        ];
    }
    
    /**
     * Fix common JSON issues
     */
    private function fix_json_content($content) {
        // Replace single quotes with double quotes
        $content = str_replace("'", '"', $content);
        
        // Fix missing quotes around keys
        $content = preg_replace('/([{,])\s*([a-zA-Z0-9_]+)\s*:/', '$1"$2":', $content);
        
        // Remove trailing commas
        $content = preg_replace('/,\s*(\}|\])/', '$1', $content);
        
        // Convert None/null/undefined to empty strings
        $content = preg_replace('/"[^"]+"\s*:\s*(null|None|undefined)/', '"$1": ""', $content);
        
        return $content;
    }
    
    /**
     * Process legacy chatbot query (for backward compatibility)
     */
    public function process_chatbot_query($query, $user_data = null, $performance_data = null) {
        $user_id = $user_data ? $user_data->ID : null;
        
        // Generate a session ID for legacy calls
        $session_id = 'legacy_' . uniqid();
        
        $result = $this->process_conversational_query($query, $user_id, $session_id);
        
        if (is_array($result) && isset($result['response'])) {
            return $result['response'];
        }
        
        return $result;
    }
}

?>
