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
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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
if (!function_exists('aiqs_debug_log')) {
    function aiqs_debug_log($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($data !== null) {
                error_log('AI Quiz Debug: ' . $message . ' - Data: ' . print_r($data, true));
            } else {
                error_log('AI Quiz Debug: ' . $message);
            }
        }
    }
}

/**
 * Check for required WordPress version and PHP version
 */
function aiqs_check_requirements() {
    global $wp_version;
    
    $required_wp_version = '5.0';
    $required_php_version = '7.4';
    
    if (version_compare($wp_version, $required_wp_version, '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            sprintf(
                __('AI Quiz System requires WordPress %s or higher. Your current version is %s.', 'ai-quiz-system'),
                $required_wp_version,
                $wp_version
            )
        );
    }
    
    if (version_compare(phpversion(), $required_php_version, '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            sprintf(
                __('AI Quiz System requires PHP %s or higher. Your current version is %s.', 'ai-quiz-system'),
                $required_php_version,
                phpversion()
            )
        );
    }
}
register_activation_hook(__FILE__, 'aiqs_check_requirements');

/**
 * The code that runs during plugin activation.
 */
function activate_ai_quiz_system() {
    // Check requirements first
    aiqs_check_requirements();
    
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

/**
 * Load plugin files with proper error handling
 */
function aiqs_load_plugin_files() {
    $required_files = array(
        'includes/class-ai-quiz-system.php',
        'includes/class-ai-quiz-system-db.php',
        'includes/class-ai-quiz-system-engine.php',
        'includes/class-ai-quiz-system-chatbot.php',
        'includes/class-ai-quiz-system-shortcodes.php',
        'includes/class-ai-quiz-system-loader.php',
        'includes/class-ai-quiz-system-i18n.php',
        'includes/class-ai-quiz-system-activator.php',
        'includes/class-ai-quiz-system-deactivator.php',
        'admin/class-ai-quiz-system-admin.php',
        'public/class-ai-quiz-system-public.php'
    );
    
    foreach ($required_files as $file) {
        $file_path = AIQS_PLUGIN_DIR . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            aiqs_debug_log('Missing required file: ' . $file);
            add_action('admin_notices', function() use ($file) {
                echo '<div class="notice notice-error"><p>';
                echo sprintf(__('AI Quiz System: Missing required file %s', 'ai-quiz-system'), esc_html($file));
                echo '</p></div>';
            });
        }
    }
}
add_action('plugins_loaded', 'aiqs_load_plugin_files', 1);

/**
 * Include optional enhancement files
 */
function aiqs_load_enhancement_files() {
    $enhancement_files = array(
        'elementor-integration.php',
        'admin/bulk-import.php',
        'includes/image-support.php'
    );
    
    foreach ($enhancement_files as $file) {
        $file_path = AIQS_PLUGIN_DIR . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
}
add_action('plugins_loaded', 'aiqs_load_enhancement_files', 5);

/**
 * Initialize the plugin
 */
function run_ai_quiz_system() {
    // Check if the main class exists
    if (!class_exists('AI_Quiz_System')) {
        aiqs_debug_log('AI_Quiz_System class not found');
        return;
    }
    
    try {
        $plugin = new AI_Quiz_System();
        $plugin->run();
        aiqs_debug_log('AI Quiz System initialized successfully');
    } catch (Exception $e) {
        aiqs_debug_log('Error initializing AI Quiz System: ' . $e->getMessage());
        add_action('admin_notices', function() use ($e) {
            echo '<div class="notice notice-error"><p>';
            echo sprintf(__('AI Quiz System initialization error: %s', 'ai-quiz-system'), esc_html($e->getMessage()));
            echo '</p></div>';
        });
    }
}
add_action('plugins_loaded', 'run_ai_quiz_system', 10);

/**
 * Add admin notices for missing dependencies
 */
function aiqs_admin_notices() {
    // Check if required WordPress functions exist
    if (!function_exists('wp_enqueue_script') || !function_exists('wp_localize_script')) {
        echo '<div class="notice notice-error"><p>';
        echo __('AI Quiz System: WordPress core functions are missing. Please check your WordPress installation.', 'ai-quiz-system');
        echo '</p></div>';
    }
    
    // Check database connection
    global $wpdb;
    if (!$wpdb || !$wpdb->ready) {
        echo '<div class="notice notice-error"><p>';
        echo __('AI Quiz System: Database connection issue detected.', 'ai-quiz-system');
        echo '</p></div>';
    }
}
add_action('admin_notices', 'aiqs_admin_notices');

/**
 * Add safety checks for settings retrieval
 */
function aiqs_get_safe_option($option_name, $default = array()) {
    $option = get_option($option_name, $default);
    
    // Ensure we always return an array for settings
    if (!is_array($option)) {
        aiqs_debug_log('Invalid option format for: ' . $option_name);
        return $default;
    }
    
    return $option;
}

/**
 * Error handler for plugin-specific errors
 */
function aiqs_error_handler($errno, $errstr, $errfile, $errline) {
    // Only handle errors from our plugin
    if (strpos($errfile, AIQS_PLUGIN_DIR) !== false) {
        aiqs_debug_log("Plugin Error: {$errstr} in {$errfile} on line {$errline}");
        
        // For fatal errors, try to prevent white screen
        if ($errno === E_ERROR || $errno === E_PARSE || $errno === E_CORE_ERROR) {
            if (!headers_sent()) {
                http_response_code(500);
                echo 'AI Quiz System encountered a critical error. Please check the error logs.';
            }
            return true;
        }
    }
    
    return false; // Let WordPress handle other errors
}
set_error_handler('aiqs_error_handler');

/**
 * Plugin safety check on admin pages
 */
function aiqs_admin_safety_check() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Check if plugin files are corrupted
    $critical_files = array(
        'includes/class-ai-quiz-system-db.php',
        'includes/class-ai-quiz-system-engine.php',
        'admin/class-ai-quiz-system-admin.php'
    );
    
    $corrupted_files = array();
    foreach ($critical_files as $file) {
        $file_path = AIQS_PLUGIN_DIR . $file;
        if (!file_exists($file_path) || filesize($file_path) < 100) {
            $corrupted_files[] = $file;
        }
    }
    
    if (!empty($corrupted_files)) {
        add_action('admin_notices', function() use ($corrupted_files) {
            echo '<div class="notice notice-error"><p>';
            echo __('AI Quiz System: Corrupted or missing files detected: ', 'ai-quiz-system');
            echo esc_html(implode(', ', $corrupted_files));
            echo '</p></div>';
        });
    }
}
add_action('admin_init', 'aiqs_admin_safety_check');

/**
 * Memory limit check
 */
function aiqs_memory_check() {
    $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
    $recommended_memory = 64 * 1024 * 1024; // 64MB
    
    if ($memory_limit < $recommended_memory) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning"><p>';
            echo __('AI Quiz System: Your PHP memory limit may be too low for optimal performance. Consider increasing it to at least 64MB.', 'ai-quiz-system');
            echo '</p></div>';
        });
    }
}
add_action('admin_init', 'aiqs_memory_check');

/**
 * Clean up on uninstall
 */
function aiqs_uninstall_cleanup() {
    // Only run during actual uninstall
    if (!defined('WP_UNINSTALL_PLUGIN')) {
        return;
    }
    
    // Remove plugin options
    delete_option('aiqs_ai_settings');
    delete_option('aiqs_general_settings');
    
    // Clean up transients
    delete_transient('aiqs_quiz_*');
    
    // Drop tables if configured to do so
    $cleanup_option = get_option('aiqs_cleanup_on_uninstall', false);
    if ($cleanup_option) {
        require_once AIQS_PLUGIN_DIR . 'includes/class-ai-quiz-system-db.php';
        AI_Quiz_System_DB::drop_tables();
    }
}
register_uninstall_hook(__FILE__, 'aiqs_uninstall_cleanup');
