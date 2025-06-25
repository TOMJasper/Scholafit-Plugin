<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
    <!-- Add Subject Form -->
    <div class="aiqs-admin-card">
        <h2><?php _e('Add New Subject', 'ai-quiz-system'); ?></h2>
        
        <form id="aiqs-subject-form" class="aiqs-admin-form">
            <div class="aiqs-form-row">
                <label for="exam_id"><?php _e('Select Exam', 'ai-quiz-system'); ?>:</label>
                <select id="exam_id" name="exam_id" required>
                    <option value=""><?php _e('-- Select Exam --', 'ai-quiz-system'); ?></option>
                    <?php foreach ($exams as $exam): ?>
                    <option value="<?php echo esc_attr($exam->id); ?>">
                        <?php echo esc_html($exam->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="aiqs-form-row">
                <label for="subject_name"><?php _e('Subject Name', 'ai-quiz-system'); ?>:</label>
                <input type="text" id="subject_name" name="subject_name" placeholder="<?php _e('Enter subject name (e.g., Mathematics, English, Physics)', 'ai-quiz-system'); ?>" required>
            </div>
            
            <div class="aiqs-form-row">
                <label for="description"><?php _e('Description (Optional)', 'ai-quiz-system'); ?>:</label>
                <textarea id="description" name="description" placeholder="<?php _e('Brief description of the subject', 'ai-quiz-system'); ?>"></textarea>
            </div>
            
            <div class="aiqs-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Add Subject', 'ai-quiz-system'); ?></button>
                <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-subjects'); ?>" class="button"><?php _e('Cancel', 'ai-quiz-system'); ?></a>
            </div>
        </form>
    </div>
    
    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])): ?>
    <!-- Edit Subject Form -->
    <?php
    $subject_id = intval($_GET['id']);
    $current_subject = $db->get_subject($subject_id);
    ?>
    
    <div class="aiqs-admin-card">
        <h2><?php _e('Edit Subject', 'ai-quiz-system'); ?></h2>
        
        <form id="aiqs-subject-form" class="aiqs-admin-form">
            <input type="hidden" name="subject_id" value="<?php echo esc_attr($subject_id); ?>">
            
            <div class="aiqs-form-row">
                <label for="exam_id"><?php _e('Exam', 'ai-quiz-system'); ?>:</label>
                <select id="exam_id" name="exam_id" required>
                    <?php foreach ($exams as $exam): ?>
                    <option value="<?php echo esc_attr($exam->id); ?>" <?php selected($current_subject->exam_id, $exam->id); ?>>
                        <?php echo esc_html($exam->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="aiqs-form-row">
                <label for="subject_name"><?php _e('Subject Name', 'ai-quiz-system'); ?>:</label>
                <input type="text" id="subject_name" name="subject_name" value="<?php echo esc_attr($current_subject->name); ?>" required>
            </div>
            
            <div class="aiqs-form-row">
                <label for="description"><?php _e('Description', 'ai-quiz-system'); ?>:</label>
                <textarea id="description" name="description"><?php echo esc_textarea($current_subject->description ?? ''); ?></textarea>
            </div>
            
            <div class="aiqs-form-row">
                <label for="status"><?php _e('Status', 'ai-quiz-system'); ?>:</label>
                <select id="status" name="status">
                    <option value="active" <?php selected($current_subject->status, 'active'); ?>><?php _e('Active', 'ai-quiz-system'); ?></option>
                    <option value="inactive" <?php selected($current_subject->status, 'inactive'); ?>><?php _e('Inactive', 'ai-quiz-system'); ?></option>
                </select>
            </div>
            
            <div class="aiqs-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Update Subject', 'ai-quiz-system'); ?></button>
                <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-subjects'); ?>" class="button"><?php _e('Cancel', 'ai-quiz-system'); ?></a>
            </div>
        </form>
    </div>
    
    <?php else: ?>
    <!-- Subjects List -->
    <div class="aiqs-admin-actions">
        <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-subjects&action=add'); ?>" class="button button-primary"><?php _e('Add New Subject', 'ai-quiz-system'); ?></a>
    </div>
    
    <div class="aiqs-admin-card">
        <h2><?php _e('Filter Subjects', 'ai-quiz-system'); ?></h2>
        <form method="get" action="">
            <input type="hidden" name="page" value="ai-quiz-system-subjects">
            <select name="exam_filter" onchange="this.form.submit()">
                <option value=""><?php _e('All Exams', 'ai-quiz-system'); ?></option>
                <?php foreach ($exams as $exam): ?>
                <option value="<?php echo esc_attr($exam->id); ?>" <?php selected(isset($_GET['exam_filter']) ? $_GET['exam_filter'] : '', $exam->id); ?>>
                    <?php echo esc_html($exam->name); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Subject Name', 'ai-quiz-system'); ?></th>
                <th><?php _e('Exam', 'ai-quiz-system'); ?></th>
                <th><?php _e('Questions Count', 'ai-quiz-system'); ?></th>
                <th><?php _e('Status', 'ai-quiz-system'); ?></th>
                <th><?php _e('Actions', 'ai-quiz-system'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($subjects)): ?>
            <tr>
                <td colspan="5"><?php _e('No subjects found.', 'ai-quiz-system'); ?></td>
            </tr>
            <?php else: ?>
                <?php foreach ($subjects as $subject): ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($subject->name); ?></strong>
                        <?php if (!empty($subject->description)): ?>
                        <div class="row-actions">
                            <span class="description"><?php echo esc_html($subject->description); ?></span>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($subject->exam_name); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-questions&exam_id=' . $subject->exam_id . '&subject_id=' . $subject->id); ?>">
                            <?php echo esc_html($subject->question_count); ?> <?php _e('questions', 'ai-quiz-system'); ?>
                        </a>
                    </td>
                    <td>
                        <span class="aiqs-status aiqs-status-<?php echo esc_attr($subject->status); ?>">
                            <?php echo esc_html(ucfirst($subject->status)); ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-subjects&action=edit&id=' . $subject->id); ?>" class="button button-small">
                            <?php _e('Edit', 'ai-quiz-system'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-questions&exam_id=' . $subject->exam_id . '&subject_id=' . $subject->id); ?>" class="button button-small">
                            <?php _e('Manage Questions', 'ai-quiz-system'); ?>
                        </a>
                        <button class="button button-small delete-subject" data-id="<?php echo esc_attr($subject->id); ?>">
                            <?php _e('Delete', 'ai-quiz-system'); ?>
                        </button>
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
    // Handle subject form submission
    $('#aiqs-subject-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var action = $('input[name="subject_id"]').length > 0 ? 'aiqs_update_subject' : 'aiqs_add_subject';
        
        formData += '&action=' + action + '&nonce=' + aiqs_admin_data.nonce;
        
        $.ajax({
            url: aiqs_admin_data.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.href = '<?php echo admin_url('admin.php?page=ai-quiz-system-subjects'); ?>';
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('Server error occurred.');
            }
        });
    });
    
    // Handle subject deletion
    $('.delete-subject').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to delete this subject? This will also delete all associated questions.', 'ai-quiz-system'); ?>')) {
            return;
        }
        
        var subjectId = $(this).data('id');
        var $row = $(this).closest('tr');
        
        $.ajax({
            url: aiqs_admin_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aiqs_delete_subject',
                subject_id: subjectId,
                nonce: aiqs_admin_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                    alert(response.data.message);
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('Server error occurred.');
            }
        });
    });
});
</script>