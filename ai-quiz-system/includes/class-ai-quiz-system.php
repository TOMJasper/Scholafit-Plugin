<?php
/**
 * The core plugin class.
 */
class AI_Quiz_System {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     */
    protected $loader;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        // We've removed the Elementor hooks from here as they are now handled differently
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // The class responsible for orchestrating the actions and filters
        require_once AIQS_PLUGIN_DIR . 'includes/class-ai-quiz-system-loader.php';

        // The class responsible for defining internationalization
        require_once AIQS_PLUGIN_DIR . 'includes/class-ai-quiz-system-i18n.php';

        // The class responsible for defining all admin-specific functionality
        require_once AIQS_PLUGIN_DIR . 'admin/class-ai-quiz-system-admin.php';

        // The class responsible for defining all public-facing functionality
        require_once AIQS_PLUGIN_DIR . 'public/class-ai-quiz-system-public.php';

        // Database handlers
        require_once AIQS_PLUGIN_DIR . 'includes/class-ai-quiz-system-db.php';

        // API handlers for AI integration
        require_once AIQS_PLUGIN_DIR . 'includes/class-ai-quiz-system-engine.php';

        // AI Engine - handles both OpenAI and Claude APIs
        require_once AIQS_PLUGIN_DIR . 'includes/class-ai-quiz-system-chatbot.php';

        // Shortcodes
        require_once AIQS_PLUGIN_DIR . 'includes/class-ai-quiz-system-shortcodes.php';

        // Removed the Elementor integration here as it's now handled separately

        $this->loader = new AI_Quiz_System_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     */
    private function set_locale() {
        $plugin_i18n = new AI_Quiz_System_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

   /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function define_admin_hooks() {
        $plugin_admin = new AI_Quiz_System_Admin();

        // Admin scripts and styles
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Admin menu
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');

        // Register settings
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');

        // AJAX handlers for admin - Original handlers
        $this->loader->add_action('wp_ajax_aiqs_save_exam_config', $plugin_admin, 'save_exam_config');
        $this->loader->add_action('wp_ajax_aiqs_get_exam_config', $plugin_admin, 'get_exam_config');
        $this->loader->add_action('wp_ajax_aiqs_delete_exam', $plugin_admin, 'delete_exam');
        $this->loader->add_action('wp_ajax_aiqs_get_question_stats', $plugin_admin, 'get_question_stats');
        $this->loader->add_action('wp_ajax_aiqs_test_ai_connection', $plugin_admin, 'test_ai_connection');
        
        // Subject management AJAX handlers
        $this->loader->add_action('wp_ajax_aiqs_add_subject', $plugin_admin, 'add_subject');
        $this->loader->add_action('wp_ajax_aiqs_update_subject', $plugin_admin, 'update_subject');
        $this->loader->add_action('wp_ajax_aiqs_delete_subject', $plugin_admin, 'delete_subject');
        
        // Question management AJAX handlers
        $this->loader->add_action('wp_ajax_aiqs_generate_questions', $plugin_admin, 'generate_questions');
        $this->loader->add_action('wp_ajax_aiqs_get_question', $plugin_admin, 'get_question');
        $this->loader->add_action('wp_ajax_aiqs_save_question', $plugin_admin, 'save_question');
        $this->loader->add_action('wp_ajax_aiqs_delete_question', $plugin_admin, 'delete_question');
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     */
    private function define_public_hooks() {
        $plugin_public = new AI_Quiz_System_Public();

        // Public scripts and styles
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        // Register REST API endpoints
        $this->loader->add_action('rest_api_init', $plugin_public, 'register_rest_routes');

        // AJAX handlers for public
        $this->loader->add_action('wp_ajax_aiqs_start_quiz', $plugin_public, 'start_quiz');
        $this->loader->add_action('wp_ajax_aiqs_submit_answer', $plugin_public, 'submit_answer');
        $this->loader->add_action('wp_ajax_aiqs_finish_quiz', $plugin_public, 'finish_quiz');
        $this->loader->add_action('wp_ajax_aiqs_get_performance', $plugin_public, 'get_performance');
        $this->loader->add_action('wp_ajax_aiqs_chatbot_query', $plugin_public, 'chatbot_query');
        
        // Also allow non-logged in users if needed
        $this->loader->add_action('wp_ajax_nopriv_aiqs_start_quiz', $plugin_public, 'start_quiz');
        $this->loader->add_action('wp_ajax_nopriv_aiqs_submit_answer', $plugin_public, 'submit_answer');
        $this->loader->add_action('wp_ajax_nopriv_aiqs_finish_quiz', $plugin_public, 'finish_quiz');
        
        // Register shortcodes
        $shortcodes = new AI_Quiz_System_Shortcodes();
        $this->loader->add_action('init', $shortcodes, 'register_shortcodes');
		
		// AJAX handler for getting exam subjects on frontend
        $this->loader->add_action('wp_ajax_aiqs_get_exam_subjects', $plugin_public, 'get_exam_subjects');
        $this->loader->add_action('wp_ajax_nopriv_aiqs_get_exam_subjects', $plugin_public, 'get_exam_subjects');
	}

    /**
     * Run the loader to execute all the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
    }
}