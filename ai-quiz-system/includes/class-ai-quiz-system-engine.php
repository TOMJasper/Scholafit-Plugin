<?php
/**
 * AI Integration for generating questions and providing feedback.
 */
class AI_Quiz_System_Engine {

    /**
     * AI provider (openai or claude)
     */
    private $provider;
    
    /**
     * API key
     */
    private $api_key;
    
    /**
     * API endpoint
     */
    private $endpoint;

    /**
     * API model
     */
    private $model;
    
    /**
     * Initialize the class and set properties.
     */
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
        
        aiqs_debug_log('AI Engine initialized with provider: ' . $this->provider);
    }
    
    /**
     * Check if AI is configured.
     */
    public function is_configured() {
        $configured = !empty($this->api_key);
        aiqs_debug_log('AI configured check: ' . ($configured ? 'Yes' : 'No'));
        return $configured;
    }
    
    /**
     * Test AI connection.
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
     * Send request to AI API.
     */
    private function send_request($prompt, $system_prompt = null, $max_tokens = 2000) {
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
            if ($system_prompt) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $system_prompt
                ];
            }
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
            
            $system = $system_prompt ? $system_prompt : 'You are an expert educational quiz system helping to generate questions for students in Nigeria and Africa.';
            
            $body = [
                'model' => $this->model,
                'max_tokens' => $max_tokens,
                'temperature' => 0.7,
                'system' => $system,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ]
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
            'body' => $body
        ]);
        
        $response = wp_remote_post($this->endpoint, $args);
        
        if (is_wp_error($response)) {
            aiqs_debug_log('AI API Error', $response->get_error_message());
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        aiqs_debug_log('AI API Response', [
            'status' => $status_code,
            'body_excerpt' => substr($body, 0, 500) . '...'
        ]);
        
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
                aiqs_debug_log('OpenAI Unexpected Structure', json_encode($data));
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
                aiqs_debug_log('Claude Unexpected Structure', json_encode($data));
                return new WP_Error('claude_format_error', __('Unexpected response format from Claude AI.', 'ai-quiz-system'));
            }
        }
    }
    
    /**
     * Generate questions for a subject with improved error handling.
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
            
            aiqs_debug_log('AI Response received', [
                'length' => strlen($response),
                'preview' => substr($response, 0, 200) . (strlen($response) > 200 ? '...' : '')
            ]);
            
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
            
            // Make sure $questions is an array
            if (!is_array($questions)) {
                aiqs_debug_log('Questions is not an array');
                return $this->generate_demo_questions($subject, $count, $difficulty);
            }
            
            // Validate questions format
            $valid_questions = [];
            foreach ($questions as $key => $question) {
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
                
                // Ensure correct_answer is a valid option (A, B, C, D)
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
            
            // If we don't have enough valid questions, generate some demo ones to fill the gap
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
     * Try to fix common JSON issues
     */
    private function fix_json_content($content) {
        // Replace single quotes with double quotes
        $content = str_replace("'", '"', $content);
        
        // Fix missing quotes around keys
        $content = preg_replace('/([{,])\s*([a-zA-Z0-9_]+)\s*:/', '$1"$2":', $content);
        
        // Remove trailing commas in arrays and objects
        $content = preg_replace('/,\s*(\}|\])/', '$1', $content);
        
        // Convert None/null/undefined values to empty strings
        $content = preg_replace('/"[^"]+"\s*:\s*(null|None|undefined)/', '"$1": ""', $content);
        
        return $content;
    }
    
    /**
     * Generate demo questions as a fallback - FIXED to handle all subjects.
     */
    public function generate_demo_questions($subject, $count, $difficulty) {
        aiqs_debug_log("Generating $count demo questions for $subject with $difficulty difficulty");
        
        $demo_questions = [];
        $subject_lower = strtolower($subject);
        
        // Get subject-specific questions first
        $subject_specific_questions = $this->get_subject_specific_questions($subject);
        
        // If we have subject-specific questions, use those primarily
        if (!empty($subject_specific_questions)) {
            $templates = $subject_specific_questions;
            
            // Add some generic ones if we need more
            if (count($templates) < $count) {
                $generic_templates = $this->get_generic_templates($subject);
                $templates = array_merge($templates, $generic_templates);
            }
        } else {
            // Fall back to generic templates with subject replacement
            $templates = $this->get_generic_templates($subject);
        }
        
        // Select random templates up to the requested count
        if (count($templates) <= $count) {
            $selected_indices = range(0, count($templates) - 1);
        } else {
            $selected_indices = array_rand($templates, $count);
            if (!is_array($selected_indices)) {
                $selected_indices = [$selected_indices];
            }
        }
        
        foreach ($selected_indices as $index) {
            $template = $templates[$index];
            
            // Replace {subject} placeholder with actual subject
            foreach ($template as $key => $value) {
                $template[$key] = str_replace('{subject}', $subject, $value);
            }
            
            // Set requested difficulty if specific
            if ($difficulty !== 'mixed') {
                $template['difficulty'] = $difficulty;
            }
            
            $demo_questions[] = $template;
        }
        
        // If we need more questions, repeat some with variations
        while (count($demo_questions) < $count) {
            $template = $templates[array_rand($templates)];
            
            $template['question'] = "Regarding " . $subject . ": " . $template['question'];
            
            // Replace {subject} placeholder
            foreach ($template as $key => $value) {
                $template[$key] = str_replace('{subject}', $subject, $value);
            }
            
            if ($difficulty !== 'mixed') {
                $template['difficulty'] = $difficulty;
            }
            
            $demo_questions[] = $template;
        }
        
        aiqs_debug_log('Demo questions generated', ['count' => count($demo_questions)]);
        
        return $demo_questions;
    }
    
    /**
     * Get subject-specific question templates.
     */
    private function get_subject_specific_questions($subject) {
        $subject_lower = strtolower($subject);
        $questions = [];
        
        // Mathematics questions
        if (strpos($subject_lower, 'math') !== false || strpos($subject_lower, 'arithmetic') !== false || strpos($subject_lower, 'algebra') !== false) {
            $questions = [
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
                ],
                [
                    'question' => 'If 3x + 5 = 20, what is the value of x?',
                    'option_a' => '4',
                    'option_b' => '5',
                    'option_c' => '6',
                    'option_d' => '7',
                    'correct_answer' => 'B',
                    'explanation' => '3x + 5 = 20, so 3x = 15, therefore x = 5',
                    'difficulty' => 'medium'
                ]
            ];
        }
        
        // English questions
        elseif (strpos($subject_lower, 'english') !== false || strpos($subject_lower, 'language') !== false) {
            $questions = [
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
                ],
                [
                    'question' => 'Which sentence is grammatically correct?',
                    'option_a' => 'Me and John went to the store',
                    'option_b' => 'John and I went to the store',
                    'option_c' => 'John and me went to the store',
                    'option_d' => 'I and John went to the store',
                    'correct_answer' => 'B',
                    'explanation' => 'When using compound subjects, use "I" not "me", and put yourself second.',
                    'difficulty' => 'medium'
                ]
            ];
        }
        
        // Science questions
        elseif (strpos($subject_lower, 'science') !== false || strpos($subject_lower, 'biology') !== false || strpos($subject_lower, 'chemistry') !== false || strpos($subject_lower, 'physics') !== false) {
            $questions = [
                [
                    'question' => 'What is the chemical symbol for water?',
                    'option_a' => 'H2O',
                    'option_b' => 'CO2',
                    'option_c' => 'O2',
                    'option_d' => 'NaCl',
                    'correct_answer' => 'A',
                    'explanation' => 'Water consists of two hydrogen atoms and one oxygen atom (H2O).',
                    'difficulty' => 'easy'
                ],
                [
                    'question' => 'How many bones are in the adult human body?',
                    'option_a' => '206',
                    'option_b' => '208',
                    'option_c' => '210',
                    'option_d' => '212',
                    'correct_answer' => 'A',
                    'explanation' => 'The adult human body has 206 bones.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'What is the speed of light in a vacuum?',
                    'option_a' => '299,792,458 m/s',
                    'option_b' => '300,000,000 m/s',
                    'option_c' => '186,000 miles/s',
                    'option_d' => 'All of the above are approximately correct',
                    'correct_answer' => 'D',
                    'explanation' => 'The speed of light is approximately 299,792,458 m/s, often rounded to 300,000,000 m/s or 186,000 miles/s.',
                    'difficulty' => 'hard'
                ]
            ];
        }
        
        // Geography questions
        elseif (strpos($subject_lower, 'geography') !== false || strpos($subject_lower, 'social') !== false) {
            $questions = [
                [
                    'question' => 'What is the capital of Nigeria?',
                    'option_a' => 'Lagos',
                    'option_b' => 'Abuja',
                    'option_c' => 'Kano',
                    'option_d' => 'Port Harcourt',
                    'correct_answer' => 'B',
                    'explanation' => 'Abuja has been the capital city of Nigeria since 1991.',
                    'difficulty' => 'easy'
                ],
                [
                    'question' => 'Which is the longest river in Africa?',
                    'option_a' => 'Congo River',
                    'option_b' => 'Niger River',
                    'option_c' => 'Nile River',
                    'option_d' => 'Zambezi River',
                    'correct_answer' => 'C',
                    'explanation' => 'The Nile River is the longest river in Africa and the world.',
                    'difficulty' => 'medium'
                ]
            ];
        }
        
        // History questions
        elseif (strpos($subject_lower, 'history') !== false) {
            $questions = [
                [
                    'question' => 'In what year did Nigeria gain independence?',
                    'option_a' => '1960',
                    'option_b' => '1963',
                    'option_c' => '1958',
                    'option_d' => '1965',
                    'correct_answer' => 'A',
                    'explanation' => 'Nigeria gained independence from Britain on October 1, 1960.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Who was the first President of Nigeria?',
                    'option_a' => 'Nnamdi Azikiwe',
                    'option_b' => 'Abubakar Tafawa Balewa',
                    'option_c' => 'Obafemi Awolowo',
                    'option_d' => 'Ahmadu Bello',
                    'correct_answer' => 'A',
                    'explanation' => 'Dr. Nnamdi Azikiwe was the first President of Nigeria.',
                    'difficulty' => 'medium'
                ]
            ];
        }
        
        return $questions;
    }
    
    /**
     * Get generic question templates that can be adapted to any subject.
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
            ],
            [
                'question' => 'In the study of {subject}, which approach is most effective?',
                'option_a' => 'Passive reading only',
                'option_b' => 'Active learning and practice',
                'option_c' => 'Ignoring fundamental concepts',
                'option_d' => 'Avoiding challenging topics',
                'correct_answer' => 'B',
                'explanation' => 'Active learning and practice are essential for mastering {subject}.',
                'difficulty' => 'medium'
            ],
            [
                'question' => 'Which statement about {subject} is most accurate?',
                'option_a' => 'It has no real-world applications',
                'option_b' => 'It connects to many aspects of life',
                'option_c' => 'It should be studied in isolation',
                'option_d' => 'It requires no critical thinking',
                'correct_answer' => 'B',
                'explanation' => '{subject} connects to many aspects of daily life and other fields of study.',
                'difficulty' => 'medium'
            ]
        ];
    }
    
    /**
     * Generate personalized feedback for a user's quiz performance.
     */
    public function generate_feedback($user_data, $attempt_data, $answers_data) {
        if (!$this->is_configured()) {
            return new WP_Error('ai_not_configured', __('AI is not configured for feedback generation.', 'ai-quiz-system'));
        }
        
        $user_name = $user_data->display_name ?: $user_data->user_login;
        $score_percentage = round($attempt_data->score, 1);
        $correct_count = $attempt_data->correct_answers;
        $total_questions = $attempt_data->total_questions;
        
        $system_prompt = "You are an expert educational tutor providing personalized feedback to students in Nigeria/Africa. Be encouraging, specific, and helpful.";
        
        $prompt = "Generate personalized feedback for {$user_name} who just completed a {$attempt_data->exam_name} quiz. 

Performance Summary:
- Score: {$score_percentage}% ({$correct_count}/{$total_questions} correct)
- Subject areas covered: " . implode(', ', array_unique(array_column($answers_data, 'subject_name'))) . "

Detailed Answer Analysis:";
        
        // Add details about incorrect answers
        $incorrect_answers = array_filter($answers_data, function($answer) {
            return !$answer['is_correct'];
        });
        
        if (!empty($incorrect_answers)) {
            $prompt .= "\n\nAreas needing improvement:";
            foreach ($incorrect_answers as $answer) {
                $prompt .= "\n- {$answer['subject_name']}: Question about '{$answer['question']}' - Student answered '{$answer['user_answer']}', correct answer was '{$answer['correct_answer']}'";
            }
        }
        
        $prompt .= "\n\nProvide:
1. Overall performance assessment
2. Specific strengths observed
3. Areas for improvement with study suggestions
4. Encouraging words and next steps
5. Recommended focus areas for future study

Keep the feedback positive, constructive, and tailored to a Nigerian/African educational context. Limit to 300 words.";
        
        $response = $this->send_request($prompt, $system_prompt, 500);
        
        if (is_wp_error($response)) {
            aiqs_debug_log('AI feedback generation failed', $response->get_error_message());
            return $this->generate_default_feedback($attempt_data);
        }
        
        return $response;
    }
    
    /**
     * Generate default feedback when AI is not available.
     */
    private function generate_default_feedback($attempt_data) {
        $score = $attempt_data->score;
        
        if ($score >= 80) {
            return "Excellent work! You scored {$score}% on this quiz. Your strong performance shows you have a solid understanding of the material. Keep up the great work and continue to challenge yourself with more advanced topics.";
        } elseif ($score >= 70) {
            return "Good job! You scored {$score}% on this quiz. You're on the right track and showing good understanding. Focus on reviewing the questions you missed to strengthen your knowledge further.";
        } elseif ($score >= 60) {
            return "You scored {$score}% on this quiz. This shows you have some understanding of the material, but there's room for improvement. Review the topics covered in this quiz and practice more questions to build your confidence.";
        } else {
            return "You scored {$score}% on this quiz. Don't be discouraged - learning takes time and practice. Review the material covered in this quiz, ask for help if needed, and try again. Each attempt helps you learn and improve.";
        }
    }
    
    /**
     * Process chatbot query for personalized learning assistance.
     */
    public function process_chatbot_query($query, $user_data = null, $performance_data = null) {
        if (!$this->is_configured()) {
            return $this->generate_default_chatbot_response($query);
        }
        
        $system_prompt = "You are Rita, a friendly and knowledgeable AI tutor for Nigerian/African students. You help with academic questions, provide study guidance, and offer encouragement. Be helpful, culturally aware, and educational.";
        
        $context = "Student query: {$query}";
        
        if ($user_data) {
            $context .= "\nStudent name: {$user_data->display_name}";
        }
        
        if ($performance_data && !empty($performance_data)) {
            $context .= "\nRecent performance context: ";
            if (isset($performance_data['average_score'])) {
                $context .= "Average score: {$performance_data['average_score']}%, ";
            }
            if (isset($performance_data['total_attempts'])) {
                $context .= "Total quiz attempts: {$performance_data['total_attempts']}, ";
            }
            if (isset($performance_data['weak_subjects'])) {
                $context .= "Areas needing improvement: " . implode(', ', $performance_data['weak_subjects']);
            }
        }
        
        $prompt = "{$context}

Please provide a helpful, encouraging response. If it's an academic question, explain concepts clearly. If it's about study strategies, provide practical advice suitable for Nigerian/African students. Keep responses concise but informative (under 200 words).";
        
        $response = $this->send_request($prompt, $system_prompt, 300);
        
        if (is_wp_error($response)) {
            return $this->generate_default_chatbot_response($query);
        }
        
        return $response;
    }
    
    /**
     * Generate default chatbot response when AI is not available.
     */
    private function generate_default_chatbot_response($query) {
        $query_lower = strtolower($query);
        
        // Simple keyword-based responses
        if (strpos($query_lower, 'hello') !== false || strpos($query_lower, 'hi') !== false) {
            return "Hello! I'm Rita, your study assistant. How can I help you with your studies today?";
        }
        
        if (strpos($query_lower, 'help') !== false) {
            return "I'm here to help! You can ask me questions about your subjects, request study tips, or get guidance on quiz preparation. What would you like to know?";
        }
        
        if (strpos($query_lower, 'math') !== false) {
            return "Mathematics can be challenging, but with practice it becomes easier! Try breaking down complex problems into smaller steps, practice regularly, and don't hesitate to ask for help when needed.";
        }
        
        if (strpos($query_lower, 'english') !== false) {
            return "English language skills improve with reading and practice! Try reading different types of texts, practice writing regularly, and focus on grammar fundamentals.";
        }
        
        if (strpos($query_lower, 'study') !== false || strpos($query_lower, 'learn') !== false) {
            return "Here are some effective study tips: 1) Create a study schedule, 2) Take regular breaks, 3) Practice active recall, 4) Form study groups, 5) Use different learning methods. What subject are you focusing on?";
        }
        
        // Default response
        return "Thank you for your question! While I'd love to give you a detailed answer, I'm currently working with limited capabilities. For the best help with your studies, please try asking more specific questions about particular subjects or study strategies.";
    }
}
?>