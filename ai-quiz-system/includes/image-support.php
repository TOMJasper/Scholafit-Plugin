<?php
/**
 * Updates to support images for questions
 */

/**
 * Add image_url column to questions table if it doesn't exist
 */
function aiqs_add_image_support() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aiqs_questions';
    
    // Check if image_url column exists
    $column_exists = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'image_url'",
        $wpdb->dbname,
        $table_name
    ));
    
    // Add column if it doesn't exist
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN image_url VARCHAR(255) DEFAULT NULL");
        aiqs_debug_log('Added image_url column to questions table');
    }
}
register_activation_hook(AIQS_PLUGIN_BASENAME, 'aiqs_add_image_support');

// Also run this function on admin_init to ensure column exists for existing installations
add_action('admin_init', 'aiqs_add_image_support');

/**
 * Add image upload functionality to question editor
 */
function aiqs_add_image_upload_field() {
    // Add media uploader scripts
    wp_enqueue_media();
    
    // Add custom script for media uploader
    wp_enqueue_script(
        'aiqs-media-upload', 
        AIQS_PLUGIN_URL . 'admin/js/media-upload.js', 
        array('jquery'), 
        AIQS_VERSION . '.' . time(), 
        true
    );
}
add_action('admin_enqueue_scripts', 'aiqs_add_image_upload_field');

/**
 * Create media upload JavaScript file
 */
function aiqs_create_media_upload_js() {
    $js_dir = AIQS_PLUGIN_DIR . 'admin/js/';
    $js_file = $js_dir . 'media-upload.js';
    
    // Create directory if it doesn't exist
    if (!file_exists($js_dir)) {
        wp_mkdir_p($js_dir);
    }
    
    // Create JS file if it doesn't exist
    if (!file_exists($js_file)) {
        $js_content = "/**
 * Media uploader for question images
 */
jQuery(document).ready(function($) {
    // Initialize media uploader for existing and dynamically added elements
    function initMediaUploader() {
        $('.aiqs-image-upload-button').off('click').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var imageField = button.siblings('.aiqs-image-url');
            var imagePreview = button.siblings('.aiqs-image-preview');
            
            // Create media frame
            var frame = wp.media({
                title: 'Select or Upload Image',
                button: {
                    text: 'Use This Image'
                },
                multiple: false
            });
            
            // When image is selected, update field and preview
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                imageField.val(attachment.url);
                
                // Update preview
                if (imagePreview.length) {
                    if (attachment.url) {
                        imagePreview.html('<img src=\"' + attachment.url + '\" style=\"max-width:100%; max-height:150px;\">');
                    } else {
                        imagePreview.html('');
                    }
                }
            });
            
            // Open media library
            frame.open();
        });
        
        // Clear image button
        $('.aiqs-image-clear-button').off('click').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var imageField = button.siblings('.aiqs-image-url');
            var imagePreview = button.siblings('.aiqs-image-preview');
            
            // Clear image URL and preview
            imageField.val('');
            imagePreview.html('');
        });
    }
    
    // Initialize uploader
    initMediaUploader();
    
    // Re-initialize when question edit modal is opened
    $(document).on('click', '.edit-question', function() {
        setTimeout(function() {
            initMediaUploader();
        }, 500);
    });
    
    // Re-initialize when add question button is clicked
    $(document).on('click', '#add-question', function() {
        setTimeout(function() {
            initMediaUploader();
        }, 500);
    });
});";
        
        file_put_contents($js_file, $js_content);
    }
}
add_action('admin_init', 'aiqs_create_media_upload_js');

/**
 * Update the question edit modal to include image upload field
 */
function aiqs_update_question_modal() {
    add_action('admin_footer', function() {
        // Only add to questions page
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'ai-quiz-system_page_ai-quiz-system-questions') {
            return;
        }
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Add image field to question edit modal
            var imageFieldHTML = '<div class="aiqs-form-row">' +
                '<label for="edit-image-url"><?php _e('Question Image', 'ai-quiz-system'); ?>:</label>' +
                '<input type="text" id="edit-image-url" name="image_url" class="aiqs-image-url" placeholder="<?php _e('Image URL', 'ai-quiz-system'); ?>">' +
                '<button type="button" class="button aiqs-image-upload-button"><?php _e('Upload Image', 'ai-quiz-system'); ?></button>' +
                '<button type="button" class="button aiqs-image-clear-button"><?php _e('Clear', 'ai-quiz-system'); ?></button>' +
                '<div class="aiqs-image-preview"></div>' +
                '</div>';
            
            // Add the image field before the explanation field
            var $explanationField = $('#edit-question-form .aiqs-form-row:has(#edit-explanation)');
            if ($explanationField.length) {
                $(imageFieldHTML).insertBefore($explanationField);
            }
            
            // Update the original edit-question function to include image URL
            var originalEditQuestion = window.editQuestion;
            if (typeof originalEditQuestion === 'function') {
                window.editQuestion = function(questionId) {
                    // Call the original function
                    originalEditQuestion(questionId);
                    
                    // Update to include image URL in the form
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'aiqs_get_question',
                            nonce: aiqs_admin_data.nonce,
                            question_id: questionId
                        },
                        success: function(response) {
                            if (response.success && response.data) {
                                var question = response.data;
                                $('#edit-image-url').val(question.image_url || '');
                                
                                // Update preview
                                var $preview = $('.aiqs-image-preview');
                                if (question.image_url) {
                                    $preview.html('<img src="' + question.image_url + '" style="max-width:100%; max-height:150px;">');
                                } else {
                                    $preview.html('');
                                }
                            }
                        }
                    });
                };
            }
        });
        </script>
        <?php
    });
}
add_action('admin_init', 'aiqs_update_question_modal');

/**
 * Update the question view template to show images
 */
function aiqs_update_question_view_template() {
    add_action('admin_footer', function() {
        // Only add to questions page
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'ai-quiz-system_page_ai-quiz-system-questions') {
            return;
        }
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Override the view-question handler to show image
            $(document).on('click', '.view-question', function() {
                var questionId = $(this).data('id');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'aiqs_get_question',
                        nonce: aiqs_admin_data.nonce,
                        question_id: questionId
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            var question = response.data;
                            var html = '<div class="aiqs-question-details">';
                            html += '<p><strong>Question:</strong> ' + question.question + '</p>';
                            
                            // Add image if available
                            if (question.image_url) {
                                html += '<p><strong>Image:</strong></p>';
                                html += '<p><img src="' + question.image_url + '" style="max-width:100%; max-height:300px;"></p>';
                            }
                            
                            html += '<p><strong>Option A:</strong> ' + question.option_a + '</p>';
                            html += '<p><strong>Option B:</strong> ' + question.option_b + '</p>';
                            html += '<p><strong>Option C:</strong> ' + question.option_c + '</p>';
                            html += '<p><strong>Option D:</strong> ' + question.option_d + '</p>';
                            html += '<p><strong>Correct Answer:</strong> ' + question.correct_answer + '</p>';
                            
                            if (question.explanation) {
                                html += '<p><strong>Explanation:</strong> ' + question.explanation + '</p>';
                            }
                            
                            html += '<p><strong>Difficulty:</strong> ' + question.difficulty + '</p>';
                            html += '<p><strong>Source:</strong> ' + question.source + '</p>';
                            html += '</div>';
                            
                            $('#question-view-content').html(html);
                            $('#question-view-modal').show();
                        } else {
                            alert('Failed to load question details.');
                        }
                    },
                    error: function() {
                        alert('Server error while loading question details.');
                    }
                });
            });
        });
        </script>
        <?php
    });
}
add_action('admin_init', 'aiqs_update_question_view_template');

/**
 * Update the public question rendering to show images
 */
function aiqs_update_public_question_template() {
    add_action('wp_footer', function() {
        // Only add if quiz container exists
        if (!is_admin() && is_a($GLOBALS['post'], 'WP_Post') && has_shortcode($GLOBALS['post']->post_content, 'ai_quiz')) {
            ?>
            <script>
            jQuery(document).ready(function($) {
                // Override the renderQuestion function to include image support
                var originalRenderQuestion = window.renderQuestion;
                if (typeof originalRenderQuestion === 'function') {
                    window.renderQuestion = function(index) {
                        if (!quizData.questions || index >= quizData.questions.length) {
                            console.error('Invalid question index or no questions available');
                            return;
                        }
                        
                        const question = quizData.questions[index];
                        const userAnswer = quizData.answers[question.id] || null;
                        
                        let html = '<div class="aiqs-question">';
                        html += '<div class="aiqs-question-text">' + question.question + '</div>';
                        
                        // Show image if available
                        if (question.image_url) {
                            html += '<div class="aiqs-question-image-container">';
                            html += '<img src="' + question.image_url + '" class="aiqs-question-image" alt="Question Image">';
                            html += '</div>';
                        }
                        
                        // Options
                        html += '<div class="aiqs-options">';
                        html += renderOption('A', question.option_a, userAnswer);
                        html += renderOption('B', question.option_b, userAnswer);
                        html += renderOption('C', question.option_c, userAnswer);
                        html += renderOption('D', question.option_d, userAnswer);
                        html += '</div>';
                        
                        html += '</div>';
                        
                        $('#aiqs-question-container').html(html);
                        
                        // Update navigation
                        updateNavigation(index);
                        
                        // Update buttons
                        updateNavigationButtons(index);
                    };
                }
            });
            </script>
            <?php
        }
    });
}
add_action('wp_enqueue_scripts', 'aiqs_update_public_question_template');

/**
 * Add image styles to public CSS
 */
function aiqs_add_image_styles() {
    add_action('wp_head', function() {
        ?>
        <style>
        .aiqs-question-image-container {
            margin: 15px 0;
            text-align: center;
        }
        
        .aiqs-question-image {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        </style>
        <?php
    });
}
add_action('wp_enqueue_scripts', 'aiqs_add_image_styles');