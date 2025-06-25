<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if ($edit_mode): ?>
    <!-- Edit Exam Form -->
    <div class="aiqs-admin-card">
        <h2><?php echo $current_exam ? __('Edit Exam', 'ai-quiz-system') : __('Add New Exam', 'ai-quiz-system'); ?></h2>
        
        <form id="aiqs-exam-form" class="aiqs-admin-form">
            <input type="hidden" id="exam_id" name="exam_id" value="<?php echo $current_exam ? esc_attr($current_exam->id) : ''; ?>">
            
            <div class="aiqs-form-row">
                <label for="name"><?php _e('Exam Name', 'ai-quiz-system'); ?>:</label>
                <input type="text" id="name" name="name" value="<?php echo $current_exam ? esc_attr($current_exam->name) : ''; ?>" required>
            </div>
            
            <div class="aiqs-form-row">
                <label for="description"><?php _e('Description', 'ai-quiz-system'); ?>:</label>
                <textarea id="description" name="description"><?php echo $current_exam ? esc_textarea($current_exam->description) : ''; ?></textarea>
            </div>
            
            <div class="aiqs-form-row">
                <label for="subject_count"><?php _e('Number of Required Subjects', 'ai-quiz-system'); ?>:</label>
                <input type="number" id="subject_count" name="subject_count" min="1" value="<?php echo $current_exam ? esc_attr($current_exam->subject_count) : '4'; ?>" required>
                <p class="description"><?php _e('How many subjects students must select for this exam', 'ai-quiz-system'); ?></p>
            </div>
            
            <div class="aiqs-form-row">
                <label for="time_limit"><?php _e('Time Limit (seconds)', 'ai-quiz-system'); ?>:</label>
                <input type="number" id="time_limit" name="time_limit" min="0" value="<?php echo $current_exam ? esc_attr($current_exam->time_limit) : '3600'; ?>" required>
                <p class="description"><?php _e('Time limit in seconds (3600 = 1 hour)', 'ai-quiz-system'); ?></p>
            </div>
            
            <div class="aiqs-form-row">
                <label for="passing_score"><?php _e('Passing Score (%)', 'ai-quiz-system'); ?>:</label>
                <input type="number" id="passing_score" name="passing_score" min="0" max="100" value="<?php echo $current_exam ? esc_attr($current_exam->passing_score) : '60'; ?>" required>
            </div>
            
            <div class="aiqs-form-row">
                <label for="question_count"><?php _e('Questions Per Subject', 'ai-quiz-system'); ?>:</label>
                <input type="number" id="question_count" name="question_count" min="1" value="<?php echo $current_exam ? esc_attr($current_exam->question_count_per_subject) : '20'; ?>" required>
            </div>
            
            <h3><?php _e('Subjects', 'ai-quiz-system'); ?></h3>
            
            <div id="subjects-container">
                <?php
                $subject_list = array();
                if ($current_exam) {
                    $subjects = $db->get_exam_subjects($current_exam->id);
                    foreach ($subjects as $subject) {
                        $subject_list[] = $subject->name;
                    }
                }
                
                if (empty($subject_list)) {
                    // Add empty field
                    echo '<div class="aiqs-subject-field">
                        <input type="text" name="subjects[]" placeholder="' . esc_attr__('Enter subject name', 'ai-quiz-system') . '">
                        <button type="button" class="button remove-subject">' . esc_html__('Remove', 'ai-quiz-system') . '</button>
                    </div>';
                } else {
                    foreach ($subject_list as $subject) {
                        echo '<div class="aiqs-subject-field">
                            <input type="text" name="subjects[]" value="' . esc_attr($subject) . '">
                            <button type="button" class="button remove-subject">' . esc_html__('Remove', 'ai-quiz-system') . '</button>
                        </div>';
                    }
                }
                ?>
            </div>
            
            <button type="button" id="add-subject" class="button"><?php _e('Add Subject', 'ai-quiz-system'); ?></button>
            
            <div class="aiqs-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Save Exam', 'ai-quiz-system'); ?></button>
                <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-exams'); ?>" class="button"><?php _e('Cancel', 'ai-quiz-system'); ?></a>
            </div>
        </form>
    </div>
    
    <?php else: ?>
    <!-- Exams List -->
    <div class="aiqs-admin-actions">
        <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-exams&action=add'); ?>" class="button button-primary"><?php _e('Add New Exam', 'ai-quiz-system'); ?></a>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Name', 'ai-quiz-system'); ?></th>
                <th><?php _e('Subjects', 'ai-quiz-system'); ?></th>
                <th><?php _e('Questions Per Subject', 'ai-quiz-system'); ?></th>
                <th><?php _e('Time Limit', 'ai-quiz-system'); ?></th>
                <th><?php _e('Actions', 'ai-quiz-system'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($exams)): ?>
            <tr>
                <td colspan="5"><?php _e('No exams found.', 'ai-quiz-system'); ?></td>
            </tr>
            <?php else: ?>
                <?php foreach ($exams as $exam): ?>
                <tr>
                    <td>
                        <strong><a href="<?php echo admin_url('admin.php?page=ai-quiz-system-exams&action=edit&id=' . $exam->id); ?>"><?php echo esc_html($exam->name); ?></a></strong>
                        <?php if (!empty($exam->description)): ?>
                        <div class="row-actions">
                            <span class="description"><?php echo esc_html($exam->description); ?></span>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($exam->subject_count); ?></td>
                    <td><?php echo esc_html($exam->question_count_per_subject); ?></td>
                    <td><?php echo esc_html(human_time_diff(0, $exam->time_limit)); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-exams&action=edit&id=' . $exam->id); ?>" class="button button-small"><?php _e('Edit', 'ai-quiz-system'); ?></a>
                        <button class="button button-small delete-exam" data-id="<?php echo esc_attr($exam->id); ?>" data-name="<?php echo esc_attr($exam->name); ?>"><?php _e('Delete', 'ai-quiz-system'); ?></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
    jQuery(document).ready(function($) {
        // Add subject field
        $('#add-subject').on('click', function() {
            var subjectField = '<div class="aiqs-subject-field">' +
                '<input type="text" name="subjects[]" placeholder="Enter subject name">' +
                '<button type="button" class="button remove-subject">Remove</button>' +
                '</div>';
            $('#subjects-container').append(subjectField);
        });
        
        // Remove subject field
        $(document).on('click', '.remove-subject', function() {
            $(this).parent().remove();
        });
        
        // Save exam
        $('#aiqs-exam-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiqs_save_exam_config',
                    nonce: '<?php echo wp_create_nonce('aiqs_admin_nonce'); ?>',
                    ...formData
                },
                beforeSend: function() {
                    $('#aiqs-exam-form button[type="submit"]').prop('disabled', true).text('Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = '<?php echo admin_url('admin.php?page=ai-quiz-system-exams'); ?>';
                    } else {
                        alert(response.data.message || 'Error saving exam');
                        $('#aiqs-exam-form button[type="submit"]').prop('disabled', false).text('Save Exam');
                    }
                },
                error: function() {
                    alert('Server error');
                    $('#aiqs-exam-form button[type="submit"]').prop('disabled', false).text('Save Exam');
                }
            });
        });
        
        // Delete exam
        $('.delete-exam').on('click', function() {
            var examId = $(this).data('id');
            var examName = $(this).data('name');
            
            if (confirm('Are you sure you want to delete exam "' + examName + '"? This action cannot be undone.')) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'aiqs_delete_exam',
                        nonce: '<?php echo wp_create_nonce('aiqs_admin_nonce'); ?>',
                        exam_id: examId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || 'Error deleting exam');
                        }
                    },
                    error: function() {
                        alert('Server error');
                    }
                });
            }
        });
    });
</script>