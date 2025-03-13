<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 */
class CPHT_Deactivator {

    /**
     * Deactivate the plugin.
     *
     * Performs necessary cleanup and finalization when the plugin is deactivated.
     * Settings are preserved, but temporary files and caches are cleaned up.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        cpht_plugin_log('Plugin deactivation started');

        // Clean up any temporary files or caches
        self::cleanup_temporary_files();

        // Flush rewrite rules to remove custom post type routes
        self::flush_rewrite_rules();

        cpht_plugin_log('Plugin deactivation completed');
    }

    /**
     * Clean up any temporary files or caches created by the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function cleanup_temporary_files() {
        cpht_plugin_log('Cleaning up temporary files and caches');

        // Clean cache directory but preserve the directory itself
        $cache_dir = CPHT_PLUGIN_DIR . 'cache';
        if (file_exists($cache_dir) && is_dir($cache_dir)) {
            cpht_plugin_log('Processing cache directory: ' . $cache_dir);

            $files = glob($cache_dir . '/*');
            if (is_array($files)) {
                foreach ($files as $file) {
                    // Skip .htaccess and index files
                    if (basename($file) === '.htaccess' || basename($file) === 'index.php') {
                        cpht_plugin_log('Skipping protection file: ' . basename($file));
                        continue;
                    }

                    if (is_file($file)) {
                        cpht_plugin_log('Removing cache file: ' . basename($file));
                        @unlink($file);
                    }
                }
            }

            cpht_plugin_log('Cache directory cleaned');
        } else {
            cpht_plugin_log('Cache directory not found: ' . $cache_dir);
        }

        // Remove any transients
        $transient_prefix = 'cpht_';
        cpht_plugin_log('Cleaning up transients with prefix: ' . $transient_prefix);

        global $wpdb;
        $transient_keys = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM $wpdb->options 
                 WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like('_transient_' . $transient_prefix) . '%',
                $wpdb->esc_like('_transient_timeout_' . $transient_prefix) . '%'
            )
        );

        if (!empty($transient_keys)) {
            cpht_plugin_log('Found ' . count($transient_keys) . ' transients to clean up');

            foreach ($transient_keys as $key) {
                if (strpos($key, '_transient_timeout_') !== false) {
                    continue; // Skip timeout entries, they'll be removed automatically
                }

                $transient_name = str_replace('_transient_', '', $key);
                cpht_plugin_log('Deleting transient: ' . $transient_name);
                delete_transient($transient_name);
            }
        } else {
            cpht_plugin_log('No transients found with the plugin prefix');
        }
    }

    /**
     * Flush rewrite rules to remove custom post type routes.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function flush_rewrite_rules() {
        cpht_plugin_log('Flushing rewrite rules');
        flush_rewrite_rules();
    }
}