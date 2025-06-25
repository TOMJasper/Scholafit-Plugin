<?php
/**
 * Plugin Name: AI Quiz System
 * Plugin URI: https://jasperphygitals.com
 * Description: AI-powered quiz platform with admin-controlled exam settings for Nigerian/African educational syllabus
 * Version: 1.0.0
 * Author: Scholafit
 * Author URI: https://jasperphygitals.com
 * Text Domain: ai-quiz-system
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('AIQS_VERSION', '1.0.0');
define('AIQS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AIQS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AIQS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Add debug logging function for troubleshooting
 */
function aiqs_debug_log($message, $data = null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        if ($data !== null) {
            error_log('AI Quiz Debug: ' . $message . ' - Data: ' . print_r($data, true));
        } else {
            error_log('AI Quiz Debug: ' . $message);
        }
    }
}

/**
 * Add diagnostic page for troubleshooting
 */
add_action('admin_menu', 'aiqs_add_diagnostic_page', 99);
function aiqs_add_diagnostic_page() {
    add_submenu_page(
        'ai-quiz-system',
        'Diagnostics',
        'Diagnostics',
        'manage_options',
        'aiqs-diagnostics',
        'aiqs_display_diagnostics'
    );
}

function aiqs_display_diagnostics() {
    global $wpdb;
    echo '<div class="wrap"><h1>AI Quiz System Diagnostics</h1>';
    
    // Test API connection
    echo '<h2>API Test</h2>';
    $ai_engine = new AI_Quiz_System_Engine();
    $result = $ai_engine->test_connection();
    echo '<pre>' . print_r($result, true) . '</pre>';
    
    // Check database tables
    echo '<h2>Database Tables</h2>';
    $tables = [
        $wpdb->prefix . 'aiqs_exams',
        $wpdb->prefix . 'aiqs_subjects',
        $wpdb->prefix . 'aiqs_questions',
        $wpdb->prefix . 'aiqs_quiz_attempts',
        $wpdb->prefix . 'aiqs_quiz_answers',
        $wpdb->prefix . 'aiqs_quiz_performance',
        $wpdb->prefix . 'aiqs_chatbot_history'
    ];
    
    foreach ($tables as $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        echo $table . ': ' . ($exists ? 'Exists' : 'Missing') . '<br>';
        
        if ($exists) {
            // Show table count
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
            echo '&nbsp;&nbsp;Record count: ' . $count . '<br>';
            
            // Show table structure
            $structure = $wpdb->get_results("DESCRIBE $table");
            echo '&nbsp;&nbsp;Structure: <pre>' . print_r($structure, true) . '</pre><br>';
        }
    }
    
    // Check session handling
    echo '<h2>Session Test</h2>';
    $test_value = 'test_' . time();
    set_transient('aiqs_test_transient', $test_value, 300);
    $retrieved = get_transient('aiqs_test_transient');
    echo 'Session test: ' . ($retrieved === $test_value ? 'Working' : 'Failed') . '<br>';
    
    // Check AI settings
    echo '<h2>AI Settings</h2>';
    $ai_settings = get_option('aiqs_ai_settings', []);
    echo 'Provider: ' . (isset($ai_settings['provider']) ? $ai_settings['provider'] : 'Not set') . '<br>';
    echo 'API Key: ' . (isset($ai_settings['api_key']) && !empty($ai_settings['api_key']) ? 'Set (hidden)' : 'Not set') . '<br>';
    echo 'Question Source: ' . (isset($ai_settings['question_source']) ? $ai_settings['question_source'] : 'Not set') . '<br>';
    echo 'Fallback to Stored: ' . (isset($ai_settings['fallback_to_stored']) && $ai_settings['fallback_to_stored'] ? 'Yes' : 'No') . '<br>';
    
    // Plugin file check
    echo '<h2>Plugin Files Check</h2>';
    $files = [
        'ai-quiz-system.php',
        'elementor-integration.php',
        'includes/class-ai-quiz-system.php',
        'includes/class-ai-quiz-system-activator.php',
        'includes/class-ai-quiz-system-deactivator.php',
        'includes/class-ai-quiz-system-db.php',
        'includes/class-ai-quiz-system-engine.php',
        'includes/class-ai-quiz-system-chatbot.php',
        'includes/class-ai-quiz-system-shortcodes.php',
        'includes/class-ai-quiz-system-loader.php',
        'includes/image-support.php',
        'admin/bulk-import.php',
        'public/class-ai-quiz-system-public.php',
        'public/partials/quiz.php',
        'public/partials/performance.php',
        'public/partials/chatbot.php',
        'public/js/ai-quiz-system-public.js',
        'public/css/ai-quiz-system-public.css',
    ];
    
    foreach ($files as $file) {
        $path = AIQS_PLUGIN_DIR . $file;
        echo $file . ': ' . (file_exists($path) ? 'Exists' : 'Missing') . '<br>';
        if (file_exists($path)) {
            echo '&nbsp;&nbsp;Size: ' . filesize($path) . ' bytes<br>';
            echo '&nbsp;&nbsp;Modified: ' . date('Y-m-d H:i:s', filemtime($path)) . '<br>';
        }
    }
    
    // WordPress info
    echo '<h2>WordPress Environment</h2>';
    echo 'WordPress Version: ' . get_bloginfo('version') . '<br>';
    echo 'PHP Version: ' . phpversion() . '<br>';
    echo 'MySQL Version: ' . $wpdb->db_version() . '<br>';
    echo 'WP_DEBUG: ' . (defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled') . '<br>';
    echo 'Active Plugins: ' . implode(', ', get_option('active_plugins')) . '<br>';
    
    // Test demo questions generation
    echo '<h2>Demo Questions Test</h2>';
    $demo_subject = "Mathematics";
    $ai_engine = new AI_Quiz_System_Engine();
    
    // Using the reflection API to access the private method
    try {
        $reflection = new ReflectionClass($ai_engine);
        $method = $reflection->getMethod('generate_demo_questions');
        $method->setAccessible(true);
        $demo_questions = $method->invokeArgs($ai_engine, [$demo_subject, 3, 'mixed']);
        
        echo 'Demo questions generated: ' . count($demo_questions) . '<br>';
        echo '<pre>' . print_r($demo_questions, true) . '</pre>';
    } catch (Exception $e) {
        echo 'Error testing demo questions: ' . $e->getMessage() . '<br>';
    }
    
    // End diagnostics
    echo '</div>';
}

/**
 * The code that runs during plugin activation.
 */
function activate_ai_quiz_system() {
    require_once AIQS_PLUGIN_DIR . 'includes/class-ai-quiz-system-activator.php';
    AI_Quiz_System_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_ai_quiz_system() {
    require_once AIQS_PLUGIN_DIR . 'includes/class-ai-quiz-system-deactivator.php';
    AI_Quiz_System_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_ai_quiz_system');
register_deactivation_hook(__FILE__, 'deactivate_ai_quiz_system');

// Include Elementor integration
require_once AIQS_PLUGIN_DIR . 'elementor-integration.php';

// Include bulk import and image support features
require_once AIQS_PLUGIN_DIR . 'admin/bulk-import.php';
require_once AIQS_PLUGIN_DIR . 'includes/image-support.php';

/**
 * The core plugin class
 */
require AIQS_PLUGIN_DIR . 'includes/class-ai-quiz-system.php';

/**
 * Begins execution of the plugin.
 */
function run_ai_quiz_system() {
    $plugin = new AI_Quiz_System();
    $plugin->run();
}
run_ai_quiz_system();