<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="aiqs-admin-dashboard">
        <div class="aiqs-admin-card">
            <h2><?php _e('Quiz Statistics', 'ai-quiz-system'); ?></h2>
            <div class="aiqs-admin-stats">
                <div class="aiqs-admin-stat">
                    <span class="aiqs-stat-value"><?php echo esc_html($exams_count); ?></span>
                    <span class="aiqs-stat-label"><?php _e('Exams', 'ai-quiz-system'); ?></span>
                </div>
                <div class="aiqs-admin-stat">
                    <span class="aiqs-stat-value"><?php echo esc_html($questions_count); ?></span>
                    <span class="aiqs-stat-label"><?php _e('Questions', 'ai-quiz-system'); ?></span>
                </div>
                <div class="aiqs-admin-stat">
                    <span class="aiqs-stat-value"><?php echo esc_html($attempts_count); ?></span>
                    <span class="aiqs-stat-label"><?php _e('Attempts', 'ai-quiz-system'); ?></span>
                </div>
            </div>
        </div>
        
        <?php if (!empty($recent_attempts)): ?>
        <div class="aiqs-admin-card">
            <h2><?php _e('Recent Quiz Attempts', 'ai-quiz-system'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Student', 'ai-quiz-system'); ?></th>
                        <th><?php _e('Exam', 'ai-quiz-system'); ?></th>
                        <th><?php _e('Score', 'ai-quiz-system'); ?></th>
                        <th><?php _e('Date', 'ai-quiz-system'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_attempts as $attempt): ?>
                    <tr>
                        <td><?php echo esc_html($attempt->display_name ? $attempt->display_name : __('Guest', 'ai-quiz-system')); ?></td>
                        <td><?php echo esc_html($attempt->exam_name); ?></td>
                        <td><?php echo esc_html(round($attempt->score)) . '%'; ?></td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($attempt->end_time))); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="aiqs-admin-card">
            <h2><?php _e('Quick Actions', 'ai-quiz-system'); ?></h2>
            <div class="aiqs-admin-actions">
                <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-exams'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <?php _e('Manage Exams', 'ai-quiz-system'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-subjects'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-category"></span>
                    <?php _e('Manage Subjects', 'ai-quiz-system'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-questions'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-editor-help"></span>
                    <?php _e('Manage Questions', 'ai-quiz-system'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-settings'); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Configure Settings', 'ai-quiz-system'); ?>
                </a>
            </div>
        </div>
        
        <div class="aiqs-admin-card">
            <h2><?php _e('System Status', 'ai-quiz-system'); ?></h2>
            <div class="aiqs-system-status">
                <?php
                // Check AI configuration
                $ai_settings = get_option('aiqs_ai_settings', []);
                $ai_configured = !empty($ai_settings['api_key']);
                ?>
                <div class="aiqs-status-item">
                    <span class="aiqs-status-icon <?php echo $ai_configured ? 'success' : 'warning'; ?>">
                        <?php echo $ai_configured ? '✓' : '⚠'; ?>
                    </span>
                    <span class="aiqs-status-text">
                        <?php echo $ai_configured ? __('AI Integration: Configured', 'ai-quiz-system') : __('AI Integration: Not Configured', 'ai-quiz-system'); ?>
                    </span>
                    <?php if (!$ai_configured): ?>
                    <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-settings'); ?>" class="button button-small">
                        <?php _e('Configure Now', 'ai-quiz-system'); ?>
                    </a>
                    <?php endif; ?>
                </div>
                
                <?php
                // Check if there are exams
                $has_exams = $exams_count > 0;
                ?>
                <div class="aiqs-status-item">
                    <span class="aiqs-status-icon <?php echo $has_exams ? 'success' : 'warning'; ?>">
                        <?php echo $has_exams ? '✓' : '⚠'; ?>
                    </span>
                    <span class="aiqs-status-text">
                        <?php echo $has_exams ? __('Exams: Available', 'ai-quiz-system') : __('Exams: None Created', 'ai-quiz-system'); ?>
                    </span>
                    <?php if (!$has_exams): ?>
                    <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-exams&action=add'); ?>" class="button button-small">
                        <?php _e('Create Exam', 'ai-quiz-system'); ?>
                    </a>
                    <?php endif; ?>
                </div>
                
                <?php
                // Check if there are questions
                $has_questions = $questions_count > 0;
                ?>
                <div class="aiqs-status-item">
                    <span class="aiqs-status-icon <?php echo $has_questions ? 'success' : 'info'; ?>">
                        <?php echo $has_questions ? '✓' : 'i'; ?>
                    </span>
                    <span class="aiqs-status-text">
                        <?php echo $has_questions ? sprintf(__('Questions: %d Available', 'ai-quiz-system'), $questions_count) : __('Questions: Demo Questions Only', 'ai-quiz-system'); ?>
                    </span>
                    <?php if (!$has_questions): ?>
                    <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-questions'); ?>" class="button button-small">
                        <?php _e('Add Questions', 'ai-quiz-system'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="aiqs-admin-card">
            <h2><?php _e('Getting Started Guide', 'ai-quiz-system'); ?></h2>
            <div class="aiqs-getting-started">
                <div class="aiqs-step <?php echo $ai_configured ? 'completed' : 'current'; ?>">
                    <span class="aiqs-step-number">1</span>
                    <div class="aiqs-step-content">
                        <h3><?php _e('Configure AI Settings', 'ai-quiz-system'); ?></h3>
                        <p><?php _e('Set up your OpenAI or Claude AI API key to enable AI question generation.', 'ai-quiz-system'); ?></p>
                        <?php if (!$ai_configured): ?>
                        <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-settings'); ?>" class="button">
                            <?php _e('Configure AI', 'ai-quiz-system'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="aiqs-step <?php echo $has_exams ? 'completed' : ($ai_configured ? 'current' : ''); ?>">
                    <span class="aiqs-step-number">2</span>
                    <div class="aiqs-step-content">
                        <h3><?php _e('Create Your First Exam', 'ai-quiz-system'); ?></h3>
                        <p><?php _e('Set up exam configurations like UTME, WAEC, or custom exams with subjects.', 'ai-quiz-system'); ?></p>
                        <?php if ($ai_configured && !$has_exams): ?>
                        <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-exams&action=add'); ?>" class="button">
                            <?php _e('Create Exam', 'ai-quiz-system'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="aiqs-step <?php echo $has_questions ? 'completed' : ($has_exams ? 'current' : ''); ?>">
                    <span class="aiqs-step-number">3</span>
                    <div class="aiqs-step-content">
                        <h3><?php _e('Add Questions', 'ai-quiz-system'); ?></h3>
                        <p><?php _e('Generate AI questions, import from CSV, or add questions manually for your subjects.', 'ai-quiz-system'); ?></p>
                        <?php if ($has_exams && !$has_questions): ?>
                        <a href="<?php echo admin_url('admin.php?page=ai-quiz-system-questions'); ?>" class="button">
                            <?php _e('Add Questions', 'ai-quiz-system'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="aiqs-step <?php echo ($has_exams && $has_questions) ? 'current' : ''; ?>">
                    <span class="aiqs-step-number">4</span>
                    <div class="aiqs-step-content">
                        <h3><?php _e('Deploy Quiz on Frontend', 'ai-quiz-system'); ?></h3>
                        <p><?php _e('Use shortcodes or Elementor widgets to display quizzes on your website.', 'ai-quiz-system'); ?></p>
                        <div class="aiqs-shortcodes">
                            <code>[ai_quiz]</code> - <?php _e('Display quiz interface', 'ai-quiz-system'); ?><br>
                            <code>[ai_performance]</code> - <?php _e('Show performance tracking', 'ai-quiz-system'); ?><br>
                            <code>[ai_chatbot]</code> - <?php _e('Add AI learning assistant', 'ai-quiz-system'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.aiqs-admin-dashboard {
    display: grid;
    gap: 20px;
}

.aiqs-admin-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.aiqs-admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.aiqs-admin-stat {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 4px;
}

.aiqs-stat-value {
    display: block;
    font-size: 32px;
    font-weight: bold;
    color: #2271b1;
}

.aiqs-stat-label {
    display: block;
    font-size: 14px;
    color: #646970;
    margin-top: 5px;
}

.aiqs-admin-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.aiqs-admin-actions .button {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 15px 20px;
    height: auto;
    text-align: center;
    text-decoration: none;
}

.aiqs-admin-actions .button .dashicons {
    margin-right: 8px;
    font-size: 16px;
}

.aiqs-system-status {
    margin-top: 15px;
}

.aiqs-status-item {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f1;
}

.aiqs-status-item:last-child {
    border-bottom: none;
}

.aiqs-status-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    font-weight: bold;
    color: #fff;
}

.aiqs-status-icon.success {
    background-color: #00a32a;
}

.aiqs-status-icon.warning {
    background-color: #dba617;
}

.aiqs-status-icon.info {
    background-color: #2271b1;
}

.aiqs-status-text {
    flex: 1;
    margin-right: 12px;
}

.aiqs-getting-started {
    margin-top: 15px;
}

.aiqs-step {
    display: flex;
    align-items: flex-start;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f1;
    opacity: 0.6;
}

.aiqs-step.current,
.aiqs-step.completed {
    opacity: 1;
}

.aiqs-step:last-child {
    border-bottom: none;
}

.aiqs-step-number {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #ddd;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 15px;
    flex-shrink: 0;
}

.aiqs-step.current .aiqs-step-number {
    background-color: #2271b1;
}

.aiqs-step.completed .aiqs-step-number {
    background-color: #00a32a;
}

.aiqs-step-content h3 {
    margin: 0 0 8px 0;
    font-size: 16px;
}

.aiqs-step-content p {
    margin: 0 0 10px 0;
    color: #646970;
}

.aiqs-shortcodes {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 12px;
    line-height: 1.6;
}

.aiqs-shortcodes code {
    background: #2271b1;
    color: #fff;
    padding: 2px 6px;
    border-radius: 3px;
    margin-right: 10px;
}
</style>