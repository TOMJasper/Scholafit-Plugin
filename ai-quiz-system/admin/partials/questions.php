<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="aiqs-admin-filters">
        <form method="get">
            <input type="hidden" name="page" value="ai-quiz-system-questions">
            
            <select name="exam_id" id="exam-select">
                <option value=""><?php _e('-- Select Exam --', 'ai-quiz-system'); ?></option>
                <?php foreach ($exams as $exam): ?>
                <option value="<?php echo esc_attr($exam->id); ?>" <?php selected(isset($_GET['exam_id']) ? $_GET['exam_id'] : '', $exam->id); ?>>
                    <?php echo esc_html($exam->name); ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <?php if (!empty($subjects)): ?>
            <select name="subject_id" id="subject-select">
                <option value=""><?php _e('-- Select Subject --', 'ai-quiz-system'); ?></option>
                <?php foreach ($subjects as $subject): ?>
                <option value="<?php echo esc_attr($subject->id); ?>" <?php selected(isset($_GET['subject_id']) ? $_GET['subject_id'] : '', $subject->id); ?>>
                    <?php echo esc_html($subject->name); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            
            <button type="submit" class="button"><?php _e('Filter', 'ai-quiz-system'); ?></button>
        </form>
    </div>
    
    <?php if (isset($_GET['subject_id']) && !empty($_GET['subject_id'])): ?>
    <div class="aiqs-admin-actions">
        <button id="generate-ai-questions" class="button button-primary" data-subject="<?php echo esc_attr($_GET['subject_id']); ?>">
            <?php _e('Generate AI Questions', 'ai-quiz-system'); ?>
        </button>
        <button id="add-question" class="button" data-subject="<?php echo esc_attr($_GET['subject_id']); ?>">
            <?php _e('Add Question Manually', 'ai-quiz-system'); ?>
        </button>
    </div>
    
    <?php if (!empty($questions)): ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50%"><?php _e('Question', 'ai-quiz-system'); ?></th>
                <th><?php _e('Correct Answer', 'ai-quiz-system'); ?></th>
                <th><?php _e('Difficulty', 'ai-quiz-system'); ?></th>
                <th><?php _e('Source', 'ai-quiz-system'); ?></th>
                <th><?php _e('Actions', 'ai-quiz-system'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($questions as $question): ?>
            <tr>
                <td>
                    <?php echo esc_html($question->question); ?>
                </td>
                <td><?php echo esc_html($question->correct_answer); ?></td>
                <td><?php echo esc_html(ucfirst($question->difficulty)); ?></td>
                <td><?php echo esc_html(ucfirst($question->source)); ?></td>
                <td>
                    <button class="button button-small view-question" data-id="<?php echo esc_attr($question->id); ?>">
                        <?php _e('View', 'ai-quiz-system'); ?>
                    </button>
                    <button class="button button-small edit-question" data-id="<?php echo esc_attr($question->id); ?>">
                        <?php _e('Edit', 'ai-quiz-system'); ?>
                    </button>
                    <button class="button button-small delete-question" data-id="<?php echo esc_attr($question->id); ?>">
                        <?php _e('Delete', 'ai-quiz-system'); ?>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="aiqs-admin-notice">
        <p><?php _e('No questions found for this subject. Try generating some with AI or adding them manually.', 'ai-quiz-system'); ?></p>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="aiqs-admin-notice">
        <p><?php _e('Please select an exam and subject to manage questions.', 'ai-quiz-system'); ?></p>
    </div>
    <?php endif; ?>
</div>

<!-- Question View Modal -->
<div id="question-view-modal" class="aiqs-modal">
    <div class="aiqs-modal-content">
        <span class="aiqs-modal-close">&times;</span>
        <h2><?php _e('Question Details', 'ai-quiz-system'); ?></h2>
        <div id="question-view-content"></div>
    </div>
</div>

<!-- Question Edit Modal -->
<div id="question-edit-modal" class="aiqs-modal">
    <div class="aiqs-modal-content">
        <span class="aiqs-modal-close">&times;</span>
        <h2><?php _e('Edit Question', 'ai-quiz-system'); ?></h2>
        <form id="edit-question-form">
            <input type="hidden" id="edit-question-id" name="id">
            <input type="hidden" id="edit-subject-id" name="subject_id">
            
            <div class="aiqs-form-row">
                <label for="edit-question"><?php _e('Question', 'ai-quiz-system'); ?>:</label>
                <textarea id="edit-question" name="question" rows="3" required></textarea>
            </div>
            
            <div class="aiqs-form-row">
                <label for="edit-option-a"><?php _e('Option A', 'ai-quiz-system'); ?>:</label>
                <textarea id="edit-option-a" name="option_a" rows="2" required></textarea>
            </div>
            
            <div class="aiqs-form-row">
                <label for="edit-option-b"><?php _e('Option B', 'ai-quiz-system'); ?>:</label>
                <textarea id="edit-option-b" name="option_b" rows="2" required></textarea>
            </div>
            
            <div class="aiqs-form-row">
                <label for="edit-option-c"><?php _e('Option C', 'ai-quiz-system'); ?>:</label>
                <textarea id="edit-option-c" name="option_c" rows="2" required></textarea>
            </div>
            
            <div class="aiqs-form-row">
                <label for="edit-option-d"><?php _e('Option D', 'ai-quiz-system'); ?>:</label>
                <textarea id="edit-option-d" name="option_d" rows="2" required></textarea>
            </div>
            
            <div class="aiqs-form-row">
                <label for="edit-correct-answer"><?php _e('Correct Answer', 'ai-quiz-system'); ?>:</label>
                <select id="edit-correct-answer" name="correct_answer" required>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select>
            </div>
            
            <div class="aiqs-form-row">
                <label for="edit-explanation"><?php _e('Explanation', 'ai-quiz-system'); ?>:</label>
                <textarea id="edit-explanation" name="explanation" rows="3"></textarea>
            </div>
            
            <div class="aiqs-form-row">
                <label for="edit-difficulty"><?php _e('Difficulty', 'ai-quiz-system'); ?>:</label>
                <select id="edit-difficulty" name="difficulty" required>
                    <option value="easy"><?php _e('Easy', 'ai-quiz-system'); ?></option>
                    <option value="medium"><?php _e('Medium', 'ai-quiz-system'); ?></option>
                    <option value="hard"><?php _e('Hard', 'ai-quiz-system'); ?></option>
                </select>
            </div>
            
            <div class="aiqs-form-row">
                <label for="edit-source"><?php _e('Source', 'ai-quiz-system'); ?>:</label>
                <select id="edit-source" name="source" required>
                    <option value="manual"><?php _e('Manual', 'ai-quiz-system'); ?></option>
                    <option value="ai"><?php _e('AI', 'ai-quiz-system'); ?></option>
                    <option value="demo"><?php _e('Demo', 'ai-quiz-system'); ?></option>
                </select>
            </div>
            
            <div class="aiqs-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Save Question', 'ai-quiz-system'); ?></button>
                <button type="button" class="button aiqs-modal-close"><?php _e('Cancel', 'ai-quiz-system'); ?></button>
            </div>
        </form>
    </div>
</div>

<style>
    .aiqs-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }
    
    .aiqs-modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 800px;
        border-radius: 4px;
    }
    
    .aiqs-modal-close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .aiqs-form-row {
        margin-bottom: 15px;
    }
    
    .aiqs-form-row label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    
    .aiqs-form-row input[type="text"],
    .aiqs-form-row textarea,
    .aiqs-form-row select {
        width: 100%;
    }
    
    .aiqs-form-actions {
        margin-top: 20px;
    }
</style>