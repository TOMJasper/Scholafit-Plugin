<?php
/**
 * Quiz interface template.
 *
 * @package AI_Quiz_System
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="aiqs-container aiqs-quiz-wrapper">
    <div class="aiqs-section" id="aiqs-quiz-selection">
        <?php if ($show_title): ?>
        <h2 class="aiqs-heading">AI Quiz System</h2>
        <?php endif; ?>
        
        <?php if ($show_description): ?>
        <p>Select an exam and the required subjects to start your practice quiz.</p>
        <?php endif; ?>
        
        <div class="aiqs-exam-select">
            <label for="aiqs-exam-select"><?php _e('Select Exam:', 'ai-quiz-system'); ?></label>
            <select id="aiqs-exam-select" class="aiqs-select">
                <option value=""><?php _e('-- Select an Exam --', 'ai-quiz-system'); ?></option>
                <?php foreach ($exams as $exam): ?>
                <option value="<?php echo esc_attr($exam->id); ?>" data-subjects="<?php echo esc_attr($exam->subject_count); ?>">
                    <?php echo esc_html($exam->name); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="aiqs-subjects-select" id="aiqs-subjects-container"></div>
        
        <div class="aiqs-quiz-actions">
            <button id="aiqs-start-quiz-btn" class="aiqs-button" disabled><?php _e('Start Quiz', 'ai-quiz-system'); ?></button>
        </div>
    </div>
    
    <div id="aiqs-quiz-container"></div>
</div>

<style>
/* Fix for chatbot disappearance issue - ensure chatbot remains visible */
.aiqs-quiz-wrapper {
    display: block;
    position: relative;
}
.aiqs-chatbot {
    z-index: 100; /* Keep above other elements */
    position: relative;
}
</style>

<script>
// Add debugging to help identify issue locations
document.addEventListener('DOMContentLoaded', function() {
    console.log('Quiz template loaded and initialized!');
});
</script>