<?php
/**
 * Define the internationalization functionality.
 *
 * @package AI_Quiz_System
 */

class AI_Quiz_System_i18n {

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'ai-quiz-system',
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
        );
    }
}