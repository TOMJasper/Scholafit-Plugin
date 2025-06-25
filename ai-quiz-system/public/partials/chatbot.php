<?php
/**
 * Chatbot interface template.
 *
 * @package AI_Quiz_System
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get history if user is logged in or has a session
$history = [];
$chatbot = new AI_Quiz_System_Chatbot();

if (is_user_logged_in()) {
    $history = $chatbot->get_history(get_current_user_id());
} elseif (!empty($session_id)) {
    $history = $chatbot->get_history(null, $session_id);
}

// Generate greeting if no history
$greeting = '';
if (empty($history)) {
    $greeting = $welcome_message;
    
    if (is_user_logged_in()) {
        $user_data = wp_get_current_user();
        $greeting = $chatbot->generate_greeting($user_data);
    }
}
?>

<div class="aiqs-container">
    <div class="aiqs-chatbot" id="aiqs-chatbot-container" data-session="<?php echo esc_attr($session_id); ?>">
        <div class="aiqs-chatbot-header">
            <div class="aiqs-chatbot-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4e73df" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
            </div>
            <div class="aiqs-chatbot-title"><?php echo esc_html($title); ?></div>
        </div>
        
        <div class="aiqs-chatbot-messages">
            <?php if (!empty($greeting)): ?>
                <div class="aiqs-message aiqs-message-bot"><?php echo esc_html($greeting); ?></div>
            <?php endif; ?>
            
            <?php foreach ($history as $message): ?>
                <div class="aiqs-message aiqs-message-user"><?php echo esc_html($message->query); ?></div>
                <div class="aiqs-message aiqs-message-bot"><?php echo esc_html($message->response); ?></div>
            <?php endforeach; ?>
        </div>
        
        <div class="aiqs-chatbot-input">
            <input type="text" id="aiqs-chatbot-input" placeholder="<?php echo esc_attr($placeholder); ?>">
            <button type="button" id="aiqs-chatbot-send">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
        </div>
    </div>
</div>