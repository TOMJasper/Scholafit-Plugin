<?php
/**
 * Performance tracking interface template.
 *
 * @package AI_Quiz_System
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="aiqs-container">
    <div class="aiqs-section">
        <?php if ($show_title): ?>
        <h2 class="aiqs-heading">Your Performance</h2>
        <?php endif; ?>
        
        <div class="aiqs-exam-select">
            <label for="aiqs-performance-exam-select"><?php _e('Filter by Exam:', 'ai-quiz-system'); ?></label>
            <select id="aiqs-performance-exam-select" class="aiqs-select">
                <option value=""><?php _e('-- All Exams --', 'ai-quiz-system'); ?></option>
                <?php foreach ($exams as $exam): ?>
                <option value="<?php echo esc_attr($exam->id); ?>" <?php selected($exam_id, $exam->id); ?>>
                    <?php echo esc_html($exam->name); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <div id="aiqs-performance-container">
        <?php if (empty($performance)): ?>
            <div class="aiqs-section">
                <p><?php _e('No performance data available yet. Take some quizzes to see your progress!', 'ai-quiz-system'); ?></p>
            </div>
        <?php else: ?>
            <div class="aiqs-performance-container">
                <?php foreach ($performance as $item): ?>
                    <div class="aiqs-performance-card">
                        <div class="aiqs-performance-subject"><?php echo esc_html($item->subject_name); ?></div>
                        <div class="aiqs-performance-exam"><?php echo esc_html($item->exam_name); ?></div>
                        
                        <div class="aiqs-performance-stats">
                            <div class="aiqs-performance-stat">
                                <div class="aiqs-performance-stat-value"><?php echo round($item->average_score); ?>%</div>
                                <div class="aiqs-performance-stat-label"><?php _e('Average Score', 'ai-quiz-system'); ?></div>
                            </div>
                            
                            <div class="aiqs-performance-stat">
                                <div class="aiqs-performance-stat-value"><?php echo esc_html($item->total_attempts); ?></div>
                                <div class="aiqs-performance-stat-label"><?php _e('Attempts', 'ai-quiz-system'); ?></div>
                            </div>
                            
                            <div class="aiqs-performance-stat">
                                <div class="aiqs-performance-stat-value">
                                    <?php 
                                    // Fix division by zero error by checking for zero first
                                    if ($item->total_questions > 0) {
                                        echo round(($item->correct_answers / $item->total_questions) * 100);
                                    } else {
                                        echo "0";
                                    }
                                    ?>%
                                </div>
                                <div class="aiqs-performance-stat-label"><?php _e('Accuracy', 'ai-quiz-system'); ?></div>
                            </div>
                        </div>
                        
                        <div class="aiqs-performance-progress">
                            <div class="aiqs-performance-progress-bar" style="width: <?php echo esc_attr($item->average_score); ?>%"></div>
                        </div>
                        
                        <?php if (!empty($item->strengths)): ?>
                            <div class="aiqs-performance-feedback">
                                <div class="aiqs-performance-feedback-title"><?php _e('Strengths:', 'ai-quiz-system'); ?></div>
                                <div class="aiqs-performance-feedback-content"><?php echo esc_html($item->strengths); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($item->weaknesses)): ?>
                            <div class="aiqs-performance-feedback">
                                <div class="aiqs-performance-feedback-title"><?php _e('Areas for Improvement:', 'ai-quiz-system'); ?></div>
                                <div class="aiqs-performance-feedback-content"><?php echo esc_html($item->weaknesses); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($item->recommendations)): ?>
                            <div class="aiqs-performance-feedback">
                                <div class="aiqs-performance-feedback-title"><?php _e('Recommendations:', 'ai-quiz-system'); ?></div>
                                <div class="aiqs-performance-feedback-content"><?php echo esc_html($item->recommendations); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (!empty($attempts)): ?>
                <div class="aiqs-section">
                    <h3 class="aiqs-heading"><?php _e('Recent Quiz Attempts', 'ai-quiz-system'); ?></h3>
                    
                    <table class="aiqs-attempts-table">
                        <tr>
                            <th><?php _e('Exam', 'ai-quiz-system'); ?></th>
                            <th><?php _e('Date', 'ai-quiz-system'); ?></th>
                            <th><?php _e('Score', 'ai-quiz-system'); ?></th>
                            <th><?php _e('Correct', 'ai-quiz-system'); ?></th>
                            <th><?php _e('Total', 'ai-quiz-system'); ?></th>
                        </tr>
                        
                        <?php foreach ($attempts as $attempt): ?>
                            <tr>
                                <td><?php echo esc_html($attempt->exam_name); ?></td>
                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($attempt->end_time)); ?></td>
                                <td><?php echo round($attempt->score); ?>%</td>
                                <td><?php echo esc_html($attempt->correct_answers); ?></td>
                                <td><?php echo esc_html($attempt->total_questions); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>