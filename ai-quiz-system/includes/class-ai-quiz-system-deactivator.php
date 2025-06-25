<?php
/**
 * Fired during plugin deactivation.
 */
class AI_Quiz_System_Deactivator {

    /**
     * Clean up when plugin is deactivated.
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}