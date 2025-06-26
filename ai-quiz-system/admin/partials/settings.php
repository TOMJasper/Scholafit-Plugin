<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Get settings with fallbacks
$ai_settings = get_option('aiqs_ai_settings', array(
    'provider' => 'openai',
    'api_key' => '',
    'enable_chatbot' => true,
    'question_source' => 'ai',
    'fallback_to_stored' => true
));

$general_settings = get_option('aiqs_general_settings', array(
    'default_time_limit' => 3600,
    'default_passing_score' => 60,
    'default_questions_per_subject' => 20,
    'allow_guest_access' => true,
    'show_explanations' => true,
    'enable_performance_tracking' => true
));
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="aiqs-admin-tabs">
        <ul class="aiqs-tabs-nav">
            <li class="active"><a href="#ai-settings"><?php _e('AI Settings', 'ai-quiz-system'); ?></a></li>
            <li><a href="#general-settings"><?php _e('General Settings', 'ai-quiz-system'); ?></a></li>
        </ul>
        
        <div class="aiqs-tabs-content">
            <!-- AI Settings Tab -->
            <div id="ai-settings" class="aiqs-tab-panel active">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('aiqs_ai_settings');
                    ?>
                    
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><?php _e('AI Provider', 'ai-quiz-system'); ?></th>
                            <td>
                                <select name="aiqs_ai_settings[provider]">
                                    <option value="openai" <?php selected($ai_settings['provider'], 'openai'); ?>><?php _e('OpenAI (GPT)', 'ai-quiz-system'); ?></option>
                                    <option value="claude" <?php selected($ai_settings['provider'], 'claude'); ?>><?php _e('Anthropic (Claude)', 'ai-quiz-system'); ?></option>
                                </select>
                                <p class="description"><?php _e('Select which AI provider to use for generating questions and feedback', 'ai-quiz-system'); ?></p>
                            </td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('API Key', 'ai-quiz-system'); ?></th>
                            <td>
                                <input type="password" name="aiqs_ai_settings[api_key]" value="<?php echo esc_attr($ai_settings['api_key']); ?>" class="regular-text" />
                                <p class="description"><?php _e('Enter your API key for the selected provider', 'ai-quiz-system'); ?></p>
                                <button type="button" id="test-ai-connection" class="button"><?php _e('Test Connection', 'ai-quiz-system'); ?></button>
                                <span id="connection-result"></span>
                            </td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('Enable Chatbot', 'ai-quiz-system'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aiqs_ai_settings[enable_chatbot]" value="1" <?php checked(!empty($ai_settings['enable_chatbot'])); ?> />
                                    <?php _e('Enable the "Rita" AI chatbot for student assistance', 'ai-quiz-system'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('Question Source', 'ai-quiz-system'); ?></th>
                            <td>
                                <select name="aiqs_ai_settings[question_source]">
                                    <option value="ai" <?php selected($ai_settings['question_source'], 'ai'); ?>><?php _e('AI-generated', 'ai-quiz-system'); ?></option>
                                    <option value="stored" <?php selected($ai_settings['question_source'], 'stored'); ?>><?php _e('Stored questions', 'ai-quiz-system'); ?></option>
                                    <option value="demo" <?php selected($ai_settings['question_source'], 'demo'); ?>><?php _e('Demo questions', 'ai-quiz-system'); ?></option>
                                </select>
                                <p class="description"><?php _e('Primary source for quiz questions', 'ai-quiz-system'); ?></p>
                            </td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('Fallback to Stored Questions', 'ai-quiz-system'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aiqs_ai_settings[fallback_to_stored]" value="1" <?php checked(!empty($ai_settings['fallback_to_stored'])); ?> />
                                    <?php _e('Use stored questions if AI generation fails or reaches limits', 'ai-quiz-system'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
            
            <!-- General Settings Tab -->
            <div id="general-settings" class="aiqs-tab-panel">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('aiqs_general_settings');
                    ?>
                    
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><?php _e('Default Time Limit', 'ai-quiz-system'); ?></th>
                            <td>
                                <input type="number" name="aiqs_general_settings[default_time_limit]" value="<?php echo esc_attr($general_settings['default_time_limit']); ?>" min="0" step="60" />
                                <p class="description"><?php _e('Default time limit in seconds for new exams (3600 = 1 hour)', 'ai-quiz-system'); ?></p>
                            </td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('Default Passing Score', 'ai-quiz-system'); ?></th>
                            <td>
                                <input type="number" name="aiqs_general_settings[default_passing_score]" value="<?php echo esc_attr($general_settings['default_passing_score']); ?>" min="0" max="100" />
                                <p class="description"><?php _e('Default passing score percentage for new exams', 'ai-quiz-system'); ?></p>
                            </td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('Default Questions Per Subject', 'ai-quiz-system'); ?></th>
                            <td>
                                <input type="number" name="aiqs_general_settings[default_questions_per_subject]" value="<?php echo esc_attr($general_settings['default_questions_per_subject']); ?>" min="1" />
                                <p class="description"><?php _e('Default number of questions per subject for new exams', 'ai-quiz-system'); ?></p>
                            </td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('Allow Guest Access', 'ai-quiz-system'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aiqs_general_settings[allow_guest_access]" value="1" <?php checked(!empty($general_settings['allow_guest_access'])); ?> />
                                    <?php _e('Allow non-logged-in users to take quizzes (performance tracking requires login)', 'ai-quiz-system'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('Show Explanations', 'ai-quiz-system'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aiqs_general_settings[show_explanations]" value="1" <?php checked(!empty($general_settings['show_explanations'])); ?> />
                                    <?php _e('Show explanations for correct answers after submitting', 'ai-quiz-system'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('Enable Performance Tracking', 'ai-quiz-system'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aiqs_general_settings[enable_performance_tracking]" value="1" <?php checked(!empty($general_settings['enable_performance_tracking'])); ?> />
                                    <?php _e('Track student performance and provide analytics (requires login)', 'ai-quiz-system'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Tabs
        $('.aiqs-tabs-nav a').on('click', function(e) {
            e.preventDefault();
            
            var targetId = $(this).attr('href');
            
            // Update tabs
            $('.aiqs-tabs-nav li').removeClass('active');
            $(this).parent().addClass('active');
            
            // Update panels
            $('.aiqs-tab-panel').removeClass('active');
            $(targetId).addClass('active');
        });
        
        // Test AI connection
        $('#test-ai-connection').on('click', function() {
            var button = $(this);
            var resultSpan = $('#connection-result');
            
            button.prop('disabled', true).text('<?php _e('Testing...', 'ai-quiz-system'); ?>');
            resultSpan.html('');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiqs_test_ai_connection',
                    nonce: '<?php echo wp_create_nonce('aiqs_admin_nonce'); ?>'
                },
                success: function(response) {
                    button.prop('disabled', false).text('<?php _e('Test Connection', 'ai-quiz-system'); ?>');
                    
                    if (response.success) {
                        resultSpan.html('<span style="color: green;">' + response.data.message + '</span>');
                    } else {
                        resultSpan.html('<span style="color: red;">' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    button.prop('disabled', false).text('<?php _e('Test Connection', 'ai-quiz-system'); ?>');
                    resultSpan.html('<span style="color: red;"><?php _e('Connection error', 'ai-quiz-system'); ?></span>');
                }
            });
        });
    });
</script>

<style>
    .aiqs-admin-tabs {
        margin-top: 20px;
    }
    
    .aiqs-tabs-nav {
        display: flex;
        margin: 0;
        padding: 0;
        list-style: none;
        border-bottom: 1px solid #ccc;
    }
    
    .aiqs-tabs-nav li {
        margin: 0 5px 0 0;
    }
    
    .aiqs-tabs-nav a {
        display: block;
        padding: 10px 15px;
        border: 1px solid #ccc;
        border-bottom: none;
        background: #f5f5f5;
        text-decoration: none;
        color: #555;
    }
    
    .aiqs-tabs-nav li.active a {
        background: #fff;
        border-bottom: 1px solid #fff;
        margin-bottom: -1px;
        color: #000;
    }
    
    .aiqs-tab-panel {
        display: none;
        padding: 20px;
        border: 1px solid #ccc;
        border-top: none;
    }
    
    .aiqs-tab-panel.active {
        display: block;
    }
</style>
