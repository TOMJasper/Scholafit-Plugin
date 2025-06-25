<?php
/**
 * Admin page for bulk importing questions
 */

// Exit if accessed directly
if (!defined('WPINC')) {
    die;
}

/**
 * Add bulk import page to admin menu
 */
function aiqs_add_bulk_import_page() {
    add_submenu_page(
        'ai-quiz-system',
        __('Bulk Import Questions', 'ai-quiz-system'),
        __('Bulk Import', 'ai-quiz-system'),
        'manage_options',
        'ai-quiz-system-bulk-import',
        'aiqs_display_bulk_import_page'
    );
}
add_action('admin_menu', 'aiqs_add_bulk_import_page', 20);

/**
 * Display the bulk import page
 */
function aiqs_display_bulk_import_page() {
    // Check if form was submitted
    if (isset($_POST['aiqs_import_submit']) && check_admin_referer('aiqs_bulk_import', 'aiqs_bulk_import_nonce')) {
        // Process the import
        aiqs_process_bulk_import();
    }

    $db = new AI_Quiz_System_DB();
    $exams = $db->get_exams();
    ?>
    <div class="wrap">
        <h1><?php _e('Bulk Import Questions', 'ai-quiz-system'); ?></h1>

        <div class="aiqs-admin-card">
            <h2><?php _e('Import Questions from CSV', 'ai-quiz-system'); ?></h2>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('aiqs_bulk_import', 'aiqs_bulk_import_nonce'); ?>
                
                <div class="aiqs-form-row">
                    <label for="aiqs_exam_id"><?php _e('Select Exam:', 'ai-quiz-system'); ?></label>
                    <select id="aiqs_exam_id" name="aiqs_exam_id" required>
                        <option value=""><?php _e('-- Select Exam --', 'ai-quiz-system'); ?></option>
                        <?php foreach ($exams as $exam): ?>
                            <option value="<?php echo esc_attr($exam->id); ?>"><?php echo esc_html($exam->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="aiqs-form-row">
                    <label for="aiqs_subject_id"><?php _e('Select Subject:', 'ai-quiz-system'); ?></label>
                    <select id="aiqs_subject_id" name="aiqs_subject_id" required>
                        <option value=""><?php _e('-- First Select an Exam --', 'ai-quiz-system'); ?></option>
                    </select>
                </div>
                
                <div class="aiqs-form-row">
                    <label for="aiqs_csv_file"><?php _e('CSV File:', 'ai-quiz-system'); ?></label>
                    <input type="file" id="aiqs_csv_file" name="aiqs_csv_file" accept=".csv" required>
                    <p class="description">
                        <?php _e('The CSV file should have the following columns:', 'ai-quiz-system'); ?><br>
                        <code>question,option_a,option_b,option_c,option_d,correct_answer,explanation,difficulty,image_url</code><br>
                        <?php _e('For correct_answer, use A, B, C, or D.', 'ai-quiz-system'); ?><br>
                        <?php _e('For difficulty, use easy, medium, or hard.', 'ai-quiz-system'); ?><br>
                        <?php _e('The image_url column is optional.', 'ai-quiz-system'); ?>
                    </p>
                </div>
                
                <div class="aiqs-form-row">
                    <label>
                        <input type="checkbox" name="aiqs_has_header" value="1" checked>
                        <?php _e('CSV file has header row', 'ai-quiz-system'); ?>
                    </label>
                </div>
                
                <div class="aiqs-form-actions">
                    <button type="submit" name="aiqs_import_submit" class="button button-primary"><?php _e('Import Questions', 'ai-quiz-system'); ?></button>
                </div>
            </form>
        </div>
        
        <div class="aiqs-admin-card">
            <h2><?php _e('CSV Template', 'ai-quiz-system'); ?></h2>
            <p><?php _e('Download a CSV template to get started:', 'ai-quiz-system'); ?></p>
            <a href="<?php echo esc_url(AIQS_PLUGIN_URL . 'admin/templates/questions_template.csv'); ?>" class="button"><?php _e('Download Template', 'ai-quiz-system'); ?></a>
            
            <h3><?php _e('Example CSV Content:', 'ai-quiz-system'); ?></h3>
            <pre>question,option_a,option_b,option_c,option_d,correct_answer,explanation,difficulty,image_url
"What is the capital of Nigeria?","Lagos","Abuja","Kano","Port Harcourt","B","Abuja has been the capital city of Nigeria since 1991.","easy",""
"Which of these is NOT a state in Nigeria?","Kogi","Plateau","Ibadan","Kaduna","C","Ibadan is a city in Oyo State, not a state itself.","medium","https://example.com/map.jpg"</pre>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Load subjects when exam is selected
        $('#aiqs_exam_id').on('change', function() {
            var examId = $(this).val();
            var subjectSelect = $('#aiqs_subject_id');
            
            if (!examId) {
                subjectSelect.html('<option value=""><?php _e('-- First Select an Exam --', 'ai-quiz-system'); ?></option>');
                return;
            }
            
            subjectSelect.html('<option value=""><?php _e('Loading...', 'ai-quiz-system'); ?></option>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiqs_get_subjects',
                    exam_id: examId,
                    nonce: '<?php echo wp_create_nonce('aiqs_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var options = '<option value=""><?php _e('-- Select Subject --', 'ai-quiz-system'); ?></option>';
                        
                        $.each(response.data, function(index, subject) {
                            options += '<option value="' + subject.id + '">' + subject.name + '</option>';
                        });
                        
                        subjectSelect.html(options);
                    } else {
                        subjectSelect.html('<option value=""><?php _e('No subjects found', 'ai-quiz-system'); ?></option>');
                    }
                },
                error: function() {
                    subjectSelect.html('<option value=""><?php _e('Error loading subjects', 'ai-quiz-system'); ?></option>');
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Process the bulk import from CSV
 */
function aiqs_process_bulk_import() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Validate file upload
    if (!isset($_FILES['aiqs_csv_file']) || $_FILES['aiqs_csv_file']['error'] !== UPLOAD_ERR_OK) {
        aiqs_display_admin_notice('error', __('Error uploading file. Please try again.', 'ai-quiz-system'));
        return;
    }
    
    // Get subject ID
    $subject_id = isset($_POST['aiqs_subject_id']) ? intval($_POST['aiqs_subject_id']) : 0;
    if (!$subject_id) {
        aiqs_display_admin_notice('error', __('Please select a valid subject.', 'ai-quiz-system'));
        return;
    }
    
    // Check if file is CSV
    $file_info = pathinfo($_FILES['aiqs_csv_file']['name']);
    if ($file_info['extension'] !== 'csv') {
        aiqs_display_admin_notice('error', __('Only CSV files are allowed.', 'ai-quiz-system'));
        return;
    }
    
    // Open the file
    $file = fopen($_FILES['aiqs_csv_file']['tmp_name'], 'r');
    if (!$file) {
        aiqs_display_admin_notice('error', __('Error opening CSV file.', 'ai-quiz-system'));
        return;
    }
    
    $has_header = isset($_POST['aiqs_has_header']) && $_POST['aiqs_has_header'] === '1';
    $line_count = 0;
    $success_count = 0;
    $db = new AI_Quiz_System_DB();
    
    // Skip header row if needed
    if ($has_header) {
        fgetcsv($file);
    }
    
    // Read data from CSV file and import
    while (($data = fgetcsv($file)) !== false) {
        $line_count++;
        
        // Validate data
        if (count($data) < 7) {
            continue; // Skip invalid rows
        }
        
        // Map CSV columns to question data
        $question_data = [
            'subject_id' => $subject_id,
            'question' => isset($data[0]) ? sanitize_text_field($data[0]) : '',
            'option_a' => isset($data[1]) ? sanitize_text_field($data[1]) : '',
            'option_b' => isset($data[2]) ? sanitize_text_field($data[2]) : '',
            'option_c' => isset($data[3]) ? sanitize_text_field($data[3]) : '',
            'option_d' => isset($data[4]) ? sanitize_text_field($data[4]) : '',
            'correct_answer' => isset($data[5]) ? strtoupper(sanitize_text_field($data[5])) : '',
            'explanation' => isset($data[6]) ? sanitize_textarea_field($data[6]) : '',
            'difficulty' => isset($data[7]) ? sanitize_text_field($data[7]) : 'medium',
            'image_url' => isset($data[8]) ? esc_url_raw($data[8]) : '',
            'source' => 'imported'
        ];
        
        // Validate correct answer
        if (!in_array($question_data['correct_answer'], ['A', 'B', 'C', 'D'])) {
            continue; // Skip invalid correct answer
        }
        
        // Validate difficulty
        if (!in_array($question_data['difficulty'], ['easy', 'medium', 'hard'])) {
            $question_data['difficulty'] = 'medium'; // Default to medium
        }
        
        // Add question to database
        $result = $db->add_question($question_data);
        
        if ($result) {
            $success_count++;
        }
    }
    
    fclose($file);
    
    // Display result message
    if ($success_count > 0) {
        aiqs_display_admin_notice('success', sprintf(
            __('Successfully imported %d questions out of %d entries.', 'ai-quiz-system'),
            $success_count,
            $line_count
        ));
    } else {
        aiqs_display_admin_notice('error', __('No valid questions found in the CSV file.', 'ai-quiz-system'));
    }
}

/**
 * Display admin notice
 */
function aiqs_display_admin_notice($type, $message) {
    // Store the notice for display
    add_action('admin_notices', function() use ($type, $message) {
        ?>
        <div class="notice notice-<?php echo $type; ?> is-dismissible">
            <p><?php echo $message; ?></p>
        </div>
        <?php
    });
}

/**
 * AJAX handler for getting subjects by exam
 */
function aiqs_get_subjects_ajax() {
    // Verify nonce
    check_ajax_referer('aiqs_admin_nonce', 'nonce');
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error();
    }
    
    $exam_id = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : 0;
    
    if (!$exam_id) {
        wp_send_json_error();
    }
    
    $db = new AI_Quiz_System_DB();
    $subjects = $db->get_exam_subjects($exam_id);
    
    wp_send_json_success($subjects);
}
add_action('wp_ajax_aiqs_get_subjects', 'aiqs_get_subjects_ajax');

/**
 * Create template CSV file if it doesn't exist
 */
function aiqs_create_csv_template() {
    $template_dir = AIQS_PLUGIN_DIR . 'admin/templates/';
    $template_file = $template_dir . 'questions_template.csv';
    
    // Create directory if it doesn't exist
    if (!file_exists($template_dir)) {
        wp_mkdir_p($template_dir);
    }
    
    // Create template file if it doesn't exist
    if (!file_exists($template_file)) {
        $template_content = "question,option_a,option_b,option_c,option_d,correct_answer,explanation,difficulty,image_url\n";
        $template_content .= "\"What is the capital of Nigeria?\",\"Lagos\",\"Abuja\",\"Kano\",\"Port Harcourt\",\"B\",\"Abuja has been the capital city of Nigeria since 1991.\",\"easy\",\"\"\n";
        $template_content .= "\"Which of these is NOT a state in Nigeria?\",\"Kogi\",\"Plateau\",\"Ibadan\",\"Kaduna\",\"C\",\"Ibadan is a city in Oyo State, not a state itself.\",\"medium\",\"https://example.com/map.jpg\"";
        
        file_put_contents($template_file, $template_content);
    }
}
add_action('admin_init', 'aiqs_create_csv_template');