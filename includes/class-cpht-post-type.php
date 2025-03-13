<?php
/**
 * Custom Post Type functionality for CPHT Plugin.
 *
 * This class handles template routing and hooks for the CPHT post type,
 * but delegates registration to the ACF Manager.
 *
 * @since      1.0.0
 */
class CPHT_Post_Type {

    /**
     * The name of the custom post type.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $post_type    The name of the custom post type.
     */
    protected $post_type = 'cpht_post';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        cpht_plugin_log('Initializing CPHT_Post_Type class');
    }

    /**
     * Register hooks related to the custom post type.
     *
     * @since    1.0.0
     */
    public function register() {
        cpht_plugin_log('Registering CPHT post type hooks');

        // Add hooks for post type functioning - use template_include filter
        // to force the use of plugin templates
        add_filter('template_include', array($this, 'post_template'), 99);

        // Check if post type exists (for logging purposes)
        add_action('init', array($this, 'check_post_type_exists'), 20);
    }

    /**
     * Check if the post type exists, if not log a warning.
     *
     * @since    1.0.0
     * @return   bool    Whether the post type exists.
     */
    public function check_post_type_exists() {
        $exists = post_type_exists($this->post_type);

        if (!$exists) {
            cpht_plugin_log('CPHT post type does not exist yet - waiting for ACF sync', 'warning');
        } else {
            cpht_plugin_log('CPHT post type exists and is registered');
        }

        return $exists;
    }

    /**
     * Include custom template for single post.
     * Force the use of plugin templates, not allowing theme overrides.
     *
     * @since    1.0.0
     * @param    string   $template    Current template path.
     * @return   string                Modified template path.
     */
    public function post_template($template) {
        // Only modify for single post
        if (is_singular($this->post_type)) {
            cpht_plugin_log('Loading single cpht post template');

            // Use our plugin template directly (not allowing theme overrides)
            $plugin_template = CPHT_PLUGIN_TEMPLATES_DIR . 'single-cpht-post.php';

            if (file_exists($plugin_template)) {
                cpht_plugin_log('Using plugin template: ' . $plugin_template);
                return $plugin_template;
            } else {
                cpht_plugin_log('Plugin template not found: ' . $plugin_template, 'error');
            }
        } elseif (is_post_type_archive($this->post_type)) {
            cpht_plugin_log('Loading archive template for cpht posts');

            // Use our plugin template directly (not allowing theme overrides)
            $plugin_template = CPHT_PLUGIN_TEMPLATES_DIR . 'archive-cpht-post.php';

            if (file_exists($plugin_template)) {
                cpht_plugin_log('Using plugin archive template: ' . $plugin_template);
                return $plugin_template;
            } else {
                cpht_plugin_log('Plugin archive template not found: ' . $plugin_template, 'error');
            }
        }

        return $template;
    }
}