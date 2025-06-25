<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="aiqs-admin-dashboard">
        <div class="aiqs-admin-row">
            <div class="aiqs-admin-card">
                <h2><?php _e('Overview', 'ai-quiz-system'); ?></h2>
                <div class="aiqs-admin-stats">
                    <div class="aiqs-admin-stat">
                        <span class="aiqs-stat-value"><?php echo esc_html($total_students); ?></span>
                        <span class="aiqs-stat-label"><?php _e('Students', 'ai-quiz-system'); ?></span>
                    </div>
                    <div class="aiqs-admin-stat">
                        <span class="aiqs-stat-value"><?php echo esc_html($total_attempts); ?></span>
                        <span class="aiqs-stat-label"><?php _e('Quiz Attempts', 'ai-quiz-system'); ?></span>
                    </div>
                    <div class="aiqs-admin-stat">
                        <span class="aiqs-stat-value"><?php echo round($average_score, 1); ?>%</span>
                        <span class="aiqs-stat-label"><?php _e('Average Score', 'ai-quiz-system'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="aiqs-admin-row">
            <div class="aiqs-admin-card">
                <h2><?php _e('Exam Statistics', 'ai-quiz-system'); ?></h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Exam', 'ai-quiz-system'); ?></th>
                            <th><?php _e('Attempts', 'ai-quiz-system'); ?></th>
                            <th><?php _e('Average Score', 'ai-quiz-system'); ?></th>
                            <th><?php _e('Min Score', 'ai-quiz-system'); ?></th>
                            <th><?php _e('Max Score', 'ai-quiz-system'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($exam_stats)): ?>
                        <tr>
                            <td colspan="5"><?php _e('No data available yet.', 'ai-quiz-system'); ?></td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($exam_stats as $stat): ?>
                            <tr>
                                <td><?php echo esc_html($stat->name); ?></td>
                                <td><?php echo esc_html($stat->attempt_count ? $stat->attempt_count : 0); ?></td>
                                <td><?php echo esc_html($stat->average_score ? round($stat->average_score, 1) . '%' : 'N/A'); ?></td>
                                <td><?php echo esc_html($stat->min_score ? round($stat->min_score, 1) . '%' : 'N/A'); ?></td>
                                <td><?php echo esc_html($stat->max_score ? round($stat->max_score, 1) . '%' : 'N/A'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="aiqs-admin-row">
            <div class="aiqs-admin-card">
                <h2><?php _e('Subject Statistics', 'ai-quiz-system'); ?></h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Subject', 'ai-quiz-system'); ?></th>
                            <th><?php _e('Exam', 'ai-quiz-system'); ?></th>
                            <th><?php _e('Attempts', 'ai-quiz-system'); ?></th>
                            <th><?php _e('Questions', 'ai-quiz-system'); ?></th>
                            <th><?php _e('Accuracy', 'ai-quiz-system'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subject_stats)): ?>
                        <tr>
                            <td colspan="5"><?php _e('No data available yet.', 'ai-quiz-system'); ?></td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($subject_stats as $stat): ?>
                            <tr>
                                <td><?php echo esc_html($stat->name); ?></td>
                                <td><?php echo esc_html($stat->exam_name); ?></td>
                                <td><?php echo esc_html($stat->attempt_count ? $stat->attempt_count : 0); ?></td>
                                <td><?php echo esc_html($stat->total_questions ? $stat->total_questions : 0); ?></td>
                                <td>
                                    <?php 
                                    if ($stat->accuracy) {
                                        echo esc_html(round($stat->accuracy, 1) . '%');
                                        
                                        // Add color indicator based on accuracy
                                        $color = '#1cc88a'; // Green for good
                                        if ($stat->accuracy < 40) {
                                            $color = '#e74a3b'; // Red for poor
                                        } elseif ($stat->accuracy < 70) {
                                            $color = '#f6c23e'; // Yellow for average
                                        }
                                        
                                        echo ' <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: ' . $color . ';"></span>';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .aiqs-admin-dashboard {
        margin-top: 20px;
    }
    
    .aiqs-admin-row {
        margin-bottom: 20px;
    }
    
    .aiqs-admin-card {
        background-color: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 3px;
        padding: 20px;
        box-shadow: 0 1px 1px rgba(0,0,0,0.04);
    }
    
    .aiqs-admin-stats {
        display: flex;
        justify-content: space-between;
        margin-top: 15px;
        flex-wrap: wrap;
    }
    
    .aiqs-admin-stat {
        text-align: center;
        padding: 15px;
        flex: 1;
        min-width: 150px;
        margin-bottom: 10px;
        background-color: #f8f9fc;
        border-radius: 3px;
    }
    
    .aiqs-stat-value {
        display: block;
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 5px;
        color: #4e73df;
    }
    
    .aiqs-stat-label {
        color: #5a5c69;
        font-size: 14px;
    }
</style>