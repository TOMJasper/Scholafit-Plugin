<?php
/**
 * Elementor integration for AI Quiz System.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Elementor category for AI Quiz System widgets.
 */
function aiqs_add_elementor_category($elements_manager) {
    $elements_manager->add_category(
        'ai-quiz-system',
        [
            'title' => __('AI Quiz System', 'ai-quiz-system'),
            'icon' => 'fa fa-graduation-cap',
        ]
    );
}
add_action('elementor/elements/categories_registered', 'aiqs_add_elementor_category');

/**
 * Register Elementor widgets.
 */
function aiqs_register_elementor_widgets() {
    // Check if Elementor is installed and active
    if (!did_action('elementor/loaded')) {
        return;
    }
    
    // Define class for Quiz widget
    if (!class_exists('AIQS_Quiz_Widget')) {
        class AIQS_Quiz_Widget extends \Elementor\Widget_Base {
            public function get_name() {
                return 'ai_quiz_widget';
            }
            
            public function get_title() {
                return __('AI Quiz', 'ai-quiz-system');
            }
            
            public function get_icon() {
                return 'eicon-form-horizontal';
            }
            
            public function get_categories() {
                return ['ai-quiz-system'];
            }
            
            protected function _register_controls() {
                $this->start_controls_section(
                    'section_content',
                    [
                        'label' => __('Content', 'ai-quiz-system'),
                    ]
                );
                
                // Get all exams for the dropdown
                $db = new AI_Quiz_System_DB();
                $exams = $db->get_exams();
                $exam_options = [0 => __('-- Select Exam --', 'ai-quiz-system')];
                
                foreach ($exams as $exam) {
                    $exam_options[$exam->id] = $exam->name;
                }
                
                $this->add_control(
                    'exam_id',
                    [
                        'label' => __('Select Exam', 'ai-quiz-system'),
                        'type' => \Elementor\Controls_Manager::SELECT,
                        'default' => 0,
                        'options' => $exam_options,
                        'description' => __('If selected, only this exam will be available for quiz.', 'ai-quiz-system'),
                    ]
                );
                
                $this->add_control(
                    'show_title',
                    [
                        'label' => __('Show Title', 'ai-quiz-system'),
                        'type' => \Elementor\Controls_Manager::SWITCHER,
                        'label_on' => __('Yes', 'ai-quiz-system'),
                        'label_off' => __('No', 'ai-quiz-system'),
                        'default' => 'yes',
                    ]
                );
                
                $this->add_control(
                    'show_description',
                    [
                        'label' => __('Show Description', 'ai-quiz-system'),
                        'type' => \Elementor\Controls_Manager::SWITCHER,
                        'label_on' => __('Yes', 'ai-quiz-system'),
                        'label_off' => __('No', 'ai-quiz-system'),
                        'default' => 'yes',
                    ]
                );
                
                // Add question source control
                $this->add_control(
                    'question_source',
                    [
                        'label' => __('Question Source', 'ai-quiz-system'),
                        'type' => \Elementor\Controls_Manager::SELECT,
                        'default' => '',
                        'options' => [
                            '' => __('Use Global Setting', 'ai-quiz-system'),
                            'ai' => __('AI-generated Questions', 'ai-quiz-system'),
                            'stored' => __('Stored Questions', 'ai-quiz-system'),
                            'demo' => __('Demo Questions', 'ai-quiz-system'),
                        ],
                        'description' => __('Override the global question source setting.', 'ai-quiz-system'),
                    ]
                );
                
                $this->end_controls_section();
            }
            
            protected function render() {
                $settings = $this->get_settings_for_display();
                
                $atts = [
                    'exam_id' => $settings['exam_id'],
                    'show_title' => $settings['show_title'],
                    'show_description' => $settings['show_description'],
                    'question_source' => $settings['question_source']
                ];
                
                // Call the shortcode function directly
                $shortcodes = new AI_Quiz_System_Shortcodes();
                echo $shortcodes->quiz_shortcode($atts);
            }
        }
    }
    
    // Define class for Performance widget
    if (!class_exists('AIQS_Performance_Widget')) {
        class AIQS_Performance_Widget extends \Elementor\Widget_Base {
            public function get_name() {
                return 'ai_performance_widget';
            }
            
            public function get_title() {
                return __('AI Performance Tracking', 'ai-quiz-system');
            }
            
            public function get_icon() {
                return 'eicon-skill-bar';
            }
            
            public function get_categories() {
                return ['ai-quiz-system'];
            }
            
            protected function _register_controls() {
                $this->start_controls_section(
                    'section_content',
                    [
                        'label' => __('Content', 'ai-quiz-system'),
                    ]
                );
                
                // Get all exams for the dropdown
                $db = new AI_Quiz_System_DB();
                $exams = $db->get_exams();
                $exam_options = [0 => __('-- All Exams --', 'ai-quiz-system')];
                
                foreach ($exams as $exam) {
                    $exam_options[$exam->id] = $exam->name;
                }
                
                $this->add_control(
                    'exam_id',
                    [
                        'label' => __('Filter by Exam', 'ai-quiz-system'),
                        'type' => \Elementor\Controls_Manager::SELECT,
                        'default' => 0,
                        'options' => $exam_options,
                        'description' => __('Show performance data for a specific exam.', 'ai-quiz-system'),
                    ]
                );
                
                $this->add_control(
                    'show_title',
                    [
                        'label' => __('Show Title', 'ai-quiz-system'),
                        'type' => \Elementor\Controls_Manager::SWITCHER,
                        'label_on' => __('Yes', 'ai-quiz-system'),
                        'label_off' => __('No', 'ai-quiz-system'),
                        'default' => 'yes',
                    ]
                );
                
                $this->end_controls_section();
            }
            
            protected function render() {
                $settings = $this->get_settings_for_display();
                
                $atts = [
                    'exam_id' => $settings['exam_id'],
                    'show_title' => $settings['show_title']
                ];
                
                // Call the shortcode function directly
                $shortcodes = new AI_Quiz_System_Shortcodes();
                echo $shortcodes->performance_shortcode($atts);
            }
        }
    }
    
    // Define class for Chatbot widget
    if (!class_exists('AIQS_Chatbot_Widget')) {
        class AIQS_Chatbot_Widget extends \Elementor\Widget_Base {
            public function get_name() {
                return 'ai_chatbot_widget';
            }
            
            public function get_title() {
                return __('AI Chatbot (Rita)', 'ai-quiz-system');
            }
            
            public function get_icon() {
                return 'eicon-chat';
            }
            
            public function get_categories() {
                return ['ai-quiz-system'];
            }
            
            protected function _register_controls() {
                $this->start_controls_section(
                    'section_content',
                    [
                        'label' => __('Content', 'ai-quiz-system'),
                    ]
                );
                
                $this->add_control(
                    'title',
                    [
                        'label' => __('Chatbot Title', 'ai-quiz-system'),
                        'type' => \Elementor\Controls_Manager::TEXT,
                        'default' => __('Rita - AI Study Assistant', 'ai-quiz-system'),
                    ]
                );
                
                $this->add_control(
                    'placeholder',
                    [
                        'label' => __('Input Placeholder', 'ai-quiz-system'),
                        'type' => \Elementor\Controls_Manager::TEXT,
                        'default' => __('Ask Rita about your studies...', 'ai-quiz-system'),
                    ]
                );
                
                $this->add_control(
                    'welcome_message',
                    [
                        'label' => __('Welcome Message', 'ai-quiz-system'),
                        'type' => \Elementor\Controls_Manager::TEXTAREA,
                        'default' => __('Hi! I\'m Rita, your AI study assistant. How can I help you today?', 'ai-quiz-system'),
                    ]
                );
                
                $this->end_controls_section();
            }
            
            protected function render() {
                $settings = $this->get_settings_for_display();
                
                $atts = [
                    'title' => $settings['title'],
                    'placeholder' => $settings['placeholder'],
                    'welcome_message' => $settings['welcome_message']
                ];
                
                // Call the shortcode function directly
                $shortcodes = new AI_Quiz_System_Shortcodes();
                echo $shortcodes->chatbot_shortcode($atts);
            }
        }
    }
    
    // Register the widgets
    \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new AIQS_Quiz_Widget());
    \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new AIQS_Performance_Widget());
    \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new AIQS_Chatbot_Widget());
}
add_action('elementor/widgets/widgets_registered', 'aiqs_register_elementor_widgets');