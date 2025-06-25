<?php
/**
 * AI Quiz System Diagnostics
 * 
 * Add this file to your plugin directory and access it via your browser:
 * https://yoursite.com/wp-content/plugins/ai-quiz-system/diagnostics.php
 * 
 * IMPORTANT: DELETE THIS FILE AFTER USE (for security reasons)
 */

// Basic security check - require WordPress
if (!file_exists('../../../../wp-load.php')) {
    die("Cannot find WordPress. This file must be in the ai-quiz-system plugin directory.");
}

// Load WordPress
require_once('../../../../wp-load.php');

// Only allow admin users to access this file
if (!current_user_can('manage_options')) {
    die("Access denied. Only administrators can use this tool.");
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
?>

<!DOCTYPE html>
<html>
<head>
    <title>AI Quiz System - Diagnostics</title>
    <style>
        body {
            font-family: sans-serif;
            line-height: 1.6;
            margin: 20px;
            color: #333;
        }
        h1 {
            color: #2271b1;
        }
        h2 {
            color: #135e96;
            margin-top: 30px;
        }
        .success {
            color: #00a32a;
        }
        .error {
            color: #d63638;
        }
        .warning {
            color: #dba617;
        }
        pre {
            background-color: #f6f7f7;
            padding: 15px;
            overflow: auto;
            border-radius: 3px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f6f7f7;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <h1>AI Quiz System - Diagnostics Tool</h1>
    
    <h2>Plugin Information</h2>
    <?php
    // Plugin information
    echo "<p>Plugin Version: " . AIQS_VERSION . "</p>";
    echo "<p>Plugin Directory: " . AIQS_PLUGIN_DIR . "</p>";
    echo "<p>Plugin URL: " . AIQS_PLUGIN_URL . "</p>";
    
    // WordPress information
    echo "<p>WordPress Version: " . get_bloginfo('version') . "</p>";
    echo "<p>PHP Version: " . phpversion() . "</p>";
    ?>
    
    <h2>Database Tables</h2>
    <?php
    global $wpdb;
    
    $tables = [
        $wpdb->prefix . 'aiqs_exams',
        $wpdb->prefix . 'aiqs_subjects',
        $wpdb->prefix . 'aiqs_questions',
        $wpdb->prefix . 'aiqs_quiz_attempts',
        $wpdb->prefix . 'aiqs_quiz_answers',
        $wpdb->prefix . 'aiqs_quiz_performance',
        $wpdb->prefix . 'aiqs_chatbot_history'
    ];
    
    echo "<table>";
    echo "<tr><th>Table Name</th><th>Status</th><th>Row Count</th></tr>";
    
    foreach ($tables as $table) {
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
        
        if ($table_exists) {
            $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
            $status_class = "success";
            $status_text = "Exists";
        } else {
            $row_count = "N/A";
            $status_class = "error";
            $status_text = "Missing";
        }
        
        echo "<tr>";
        echo "<td>{$table}</td>";
        echo "<td class='{$status_class}'>{$status_text}</td>";
        echo "<td>{$row_count}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    ?>
    
    <h2>Database Records</h2>
    
    <h3>Exams</h3>
    <?php
    $exams_table = $wpdb->prefix . 'aiqs_exams';
    if ($wpdb->get_var("SHOW TABLES LIKE '$exams_table'") == $exams_table) {
        $exams = $wpdb->get_results("SELECT * FROM $exams_table");
        
        if (!empty($exams)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Subject Count</th><th>Questions Per Subject</th><th>Time Limit</th><th>Status</th></tr>";
            
            foreach ($exams as $exam) {
                echo "<tr>";
                echo "<td>{$exam->id}</td>";
                echo "<td>{$exam->name}</td>";
                echo "<td>{$exam->subject_count}</td>";
                echo "<td>{$exam->question_count_per_subject}</td>";
                echo "<td>{$exam->time_limit} seconds</td>";
                echo "<td>{$exam->status}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p class='warning'>No exams found in the database.</p>";
        }
    } else {
        echo "<p class='error'>Exams table does not exist.</p>";
    }
    ?>
    
    <h3>Subjects</h3>
    <?php
    $subjects_table = $wpdb->prefix . 'aiqs_subjects';
    if ($wpdb->get_var("SHOW TABLES LIKE '$subjects_table'") == $subjects_table) {
        $subjects = $wpdb->get_results("SELECT s.*, e.name as exam_name 
                                        FROM $subjects_table s
                                        LEFT JOIN {$wpdb->prefix}aiqs_exams e ON s.exam_id = e.id
                                        ORDER BY e.name, s.name");
        
        if (!empty($subjects)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Exam</th><th>Subject Name</th><th>Status</th></tr>";
            
            foreach ($subjects as $subject) {
                echo "<tr>";
                echo "<td>{$subject->id}</td>";
                echo "<td>{$subject->exam_name}</td>";
                echo "<td>{$subject->name}</td>";
                echo "<td>{$subject->status}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p class='warning'>No subjects found in the database.</p>";
        }
    } else {
        echo "<p class='error'>Subjects table does not exist.</p>";
    }
    ?>
    
    <h3>Questions (Sample)</h3>
    <?php
    $questions_table = $wpdb->prefix . 'aiqs_questions';
    if ($wpdb->get_var("SHOW TABLES LIKE '$questions_table'") == $questions_table) {
        $question_count = $wpdb->get_var("SELECT COUNT(*) FROM $questions_table");
        echo "<p>Total questions: {$question_count}</p>";
        
        $questions = $wpdb->get_results("SELECT q.*, s.name as subject_name 
                                         FROM $questions_table q
                                         LEFT JOIN {$wpdb->prefix}aiqs_subjects s ON q.subject_id = s.id
                                         LIMIT 5");
        
        if (!empty($questions)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Subject</th><th>Question</th><th>Correct Answer</th><th>Source</th></tr>";
            
            foreach ($questions as $question) {
                echo "<tr>";
                echo "<td>{$question->id}</td>";
                echo "<td>{$question->subject_name}</td>";
                echo "<td>" . substr($question->question, 0, 100) . (strlen($question->question) > 100 ? '...' : '') . "</td>";
                echo "<td>{$question->correct_answer}</td>";
                echo "<td>{$question->source}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p class='warning'>No questions found in the database.</p>";
        }
    } else {
        echo "<p class='error'>Questions table does not exist.</p>";
    }
    ?>
    
    <h2>Plugin Settings</h2>
    <?php
    $ai_settings = get_option('aiqs_ai_settings');
    $general_settings = get_option('aiqs_general_settings');
    
    echo "<h3>AI Settings</h3>";
    if (!empty($ai_settings)) {
        echo "<pre>";
        $ai_settings_display = $ai_settings;
        // Don't display full API key for security
        if (isset($ai_settings_display['api_key']) && !empty($ai_settings_display['api_key'])) {
            $ai_settings_display['api_key'] = substr($ai_settings_display['api_key'], 0, 4) . '...' . substr($ai_settings_display['api_key'], -4);
        }
        print_r($ai_settings_display);
        echo "</pre>";
    } else {
        echo "<p class='warning'>AI settings not found.</p>";
    }
    
    echo "<h3>General Settings</h3>";
    if (!empty($general_settings)) {
        echo "<pre>";
        print_r($general_settings);
        echo "</pre>";
    } else {
        echo "<p class='warning'>General settings not found.</p>";
    }
    ?>
    
    <h2>REST API Endpoints</h2>
    <?php
    $rest_url = rest_url('ai-quiz-system/v1');
    
    echo "<p>Base REST URL: {$rest_url}</p>";
    
    $endpoints = [
        'exams' => $rest_url . '/exams',
        'subjects' => $rest_url . '/subjects/{exam_id}',
        'start-quiz' => $rest_url . '/start-quiz',
        'submit-answer' => $rest_url . '/submit-answer',
        'finish-quiz' => $rest_url . '/finish-quiz',
        'performance' => $rest_url . '/performance',
        'chatbot-query' => $rest_url . '/chatbot-query'
    ];
    
    echo "<table>";
    echo "<tr><th>Endpoint</th><th>URL</th></tr>";
    
    foreach ($endpoints as $name => $url) {
        echo "<tr>";
        echo "<td>{$name}</td>";
        echo "<td>{$url}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Test the exams endpoint
    echo "<h3>Testing 'exams' endpoint</h3>";
    $response = wp_remote_get($endpoints['exams']);
    
    if (!is_wp_error($response)) {
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        echo "<p>Status Code: {$status_code}</p>";
        echo "<pre>";
        echo htmlspecialchars($body);
        echo "</pre>";
    } else {
        echo "<p class='error'>Error: " . $response->get_error_message() . "</p>";
    }
    ?>
    
    <h2>AJAX Capability Test</h2>
    <p>Test if AJAX is working properly. Click the button below to test:</p>
    
    <button id="test-ajax">Test AJAX</button>
    <div id="ajax-result"></div>
    
    <script>
    document.getElementById('test-ajax').addEventListener('click', function() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            document.getElementById('ajax-result').innerHTML = '<pre>' + xhr.responseText + '</pre>';
        };
        xhr.onerror = function() {
            document.getElementById('ajax-result').innerHTML = '<p class="error">AJAX request failed</p>';
        };
        xhr.send('action=aiqs_test_ajax_callback&nonce=<?php echo wp_create_nonce('aiqs_test_ajax'); ?>');
    });
    </script>
    
    <?php
    // Add the AJAX callback
    add_action('wp_ajax_aiqs_test_ajax_callback', function() {
        check_ajax_referer('aiqs_test_ajax', 'nonce');
        echo json_encode([
            'success' => true,
            'message' => 'AJAX working correctly',
            'time' => current_time('mysql')
        ]);
        wp_die();
    });
    ?>
    
    <h2>Log File (Last 50 lines)</h2>
    <?php
    $log_file = ini_get('error_log');
    if (file_exists($log_file) && is_readable($log_file)) {
        $log_content = file($log_file);
        $last_lines = array_slice($log_content, -50);
        
        echo "<pre>";
        foreach ($last_lines as $line) {
            echo htmlspecialchars($line);
        }
        echo "</pre>";
    } else {
        echo "<p class='warning'>Log file not accessible.</p>";
    }
    ?>
    
    <p><strong>IMPORTANT: Remember to delete this file after use for security reasons.</strong></p>
</body>
</html>