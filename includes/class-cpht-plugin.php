<?php
/**
 * The main plugin class.
 *
 * This is used to define hooks and coordinate other plugin components.
 *
 * @since      1.0.0
 */
class CPHT_Plugin {

    /**
     * The singleton instance of this class.
     *
     * @since    1.0.0
     * @access   private
     * @var      CPHT_Plugin    $instance    The single instance of the class.
     */
    private static $instance = null;

    /**
     * The post type instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      CPHT_Post_Type    $post_type    Handles custom post type registration.
     */
    protected $post_type;

    /**
     * The ACF Manager instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      CPHT_ACF_Manager    $acf_manager    Handles ACF integration.
     */
    protected $acf_manager;

    /**
     * Main CPHT_Plugin Instance.
     *
     * @since    1.0.0
     * @return CPHT_Plugin - Main instance.
     */
    public static function get_instance() {
        cpht_plugin_log('Getting plugin instance');
        if (is_null(self::$instance)) {
            cpht_plugin_log('Creating new plugin instance');
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    private function __construct() {
        cpht_plugin_log('Plugin constructor started');
        $this->setup_dependencies();
        cpht_plugin_log('Plugin constructor completed');
    }

    /**
     * Setup plugin dependencies.
     *
     * @since    1.0.0
     * @access   private
     */
    private function setup_dependencies() {
        cpht_plugin_log('Setting up dependencies');

        // Initialize components
        $this->post_type = new CPHT_Post_Type();
        cpht_plugin_log('Post Type component initialized');

        $this->acf_manager = CPHT_ACF_Manager::get_instance();
        cpht_plugin_log('ACF Manager component initialized');
    }

    /**
     * Run the plugin functionalities.
     *
     * @since    1.0.0
     */
    public function run() {
        cpht_plugin_log('Plugin run started');

        // Initialize various components
        cpht_plugin_log('Registering post type');
        $this->post_type->register();

        cpht_plugin_log('Registering ACF Manager');
        $this->acf_manager->register();

        cpht_plugin_log('Plugin run completed');
    }
}