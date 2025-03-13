<?php
/**
 * ACF Integration Manager for CPHT Plugin
 *
 * This class handles all ACF Pro integration, including JSON synchronization,
 * post type registration, and field groups management.
 *
 * @package CPHT_Plugin
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CPHT_ACF_Manager
 *
 * Manages all aspects of ACF integration for the CPHT plugin.
 */
class CPHT_ACF_Manager {

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     * @access protected
     * @var CPHT_ACF_Manager $instance The single instance of the class.
     */
    protected static $instance = null;

    /**
     * Field group key.
     *
     * @since 1.0.0
     * @access protected
     * @var string $field_group_key The key for the main field group.
     */
    protected $field_group_key = 'group_cpht_post_fields';

    /**
     * Post type key.
     *
     * @since 1.0.0
     * @access protected
     * @var string $post_type_key The key for the main post type.
     */
    protected $post_type_key = 'post_type_cpht_post';

    /**
     * Field group JSON filename.
     *
     * @since 1.0.0
     * @access protected
     * @var string $field_group_filename The filename for the field group JSON.
     */
    protected $field_group_filename = 'group_cpht_post_fields.json';

    /**
     * Post type JSON filename.
     *
     * @since 1.0.0
     * @access protected
     * @var string $post_type_filename The filename for the post type JSON.
     */
    protected $post_type_filename = 'post_type_cpht_post.json';

    /**
     * The path to the JSON files directory.
     *
     * @since 1.0.0
     * @access protected
     * @var string $json_dir Path to the JSON files directory.
     */
    protected $json_dir;

    /**
     * Return an instance of this class.
     *
     * @since 1.0.0
     * @return CPHT_ACF_Manager A single instance of this class.
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
            cpht_plugin_log('CPHT ACF Manager: Instance created');
        }
        return self::$instance;
    }

    /**
     * Delayed sync method to ensure ACF is fully loaded.
     *
     * @since 1.0.0
     */
    public function delayed_sync() {
        cpht_plugin_log('CPHT ACF Manager: Running delayed sync');

        if (!function_exists('acf_get_field_group') || !function_exists('acf_get_field_groups')) {
            cpht_plugin_log('CPHT ACF Manager: ACF still not available, sync aborted');
            // Try again later with a lower priority
            add_action('admin_init', array($this, 'final_sync_attempt'), 999);
            return;
        }

        // Clean up duplicates and import field groups/post types
        $this->cleanup_duplicates();
        $this->import_post_types();
        $this->import_field_groups();
    }

    /**
     * Final attempt to sync ACF fields.
     *
     * @since 1.0.0
     */
    public function final_sync_attempt() {
        cpht_plugin_log('CPHT ACF Manager: Final sync attempt');

        if (!function_exists('acf_get_field_group') || !function_exists('acf_get_field_groups')) {
            cpht_plugin_log('CPHT ACF Manager: ACF still not available, giving up');
            return;
        }

        // Import field groups and post types without cleanup
        $this->import_post_types();
        $this->import_field_groups();
    }

    /**
     * Clean up duplicate field groups and post types.
     *
     * @since 1.0.0
     */
    private function cleanup_duplicates() {
        cpht_plugin_log('CPHT ACF Manager: Cleaning up duplicates');

        // Check for existing field groups with our key pattern to avoid duplicates
        if (function_exists('acf_get_field_groups')) {
            $field_groups = acf_get_field_groups();
            $cpht_groups = [];

            foreach ($field_groups as $group) {
                if (isset($group['key']) && $group['key'] === $this->field_group_key) {
                    $cpht_groups[] = $group;
                }
            }

            // If we have multiple field groups with the same key, clean them up
            if (count($cpht_groups) > 1) {
                cpht_plugin_log('CPHT ACF Manager: Found ' . count($cpht_groups) . ' duplicate field groups, cleaning up');

                // Keep the first one, delete the rest
                for ($i = 1; $i < count($cpht_groups); $i++) {
                    cpht_plugin_log('CPHT ACF Manager: Deleting duplicate field group ID: ' . $cpht_groups[$i]['ID']);
                    acf_delete_field_group($cpht_groups[$i]['ID']);
                }
            }
        }

        // Check for existing post types with our key pattern to avoid duplicates
        if (function_exists('acf_get_post_types')) {
            $post_types = acf_get_post_types();
            $cpht_post_types = [];

            foreach ($post_types as $post_type) {
                if (isset($post_type['key']) && $post_type['key'] === $this->post_type_key) {
                    $cpht_post_types[] = $post_type;
                }
            }

            // If we have multiple post types with the same key, clean them up
            if (count($cpht_post_types) > 1) {
                cpht_plugin_log('CPHT ACF Manager: Found ' . count($cpht_post_types) . ' duplicate post types, cleaning up');

                // Keep the first one, delete the rest
                for ($i = 1; $i < count($cpht_post_types); $i++) {
                    $post_type_id = null;
                    if (is_array($cpht_post_types[$i]) && isset($cpht_post_types[$i]['ID'])) {
                        $post_type_id = $cpht_post_types[$i]['ID'];
                    } elseif (is_object($cpht_post_types[$i]) && isset($cpht_post_types[$i]->ID)) {
                        $post_type_id = $cpht_post_types[$i]->ID;
                    }

                    if ($post_type_id) {
                        cpht_plugin_log('CPHT ACF Manager: Deleting duplicate post type ID: ' . $post_type_id);
                        if (function_exists('acf_delete_post_type')) {
                            acf_delete_post_type($post_type_id);
                        }
                    }
                }
            }
        }
    }

    /**
     * Initialize the class and set its properties.
     * This constructor mirrors the behavior of Viewpoints_ACF by setting up
     * hooks and filters immediately.
     *
     * @since 1.0.0
     */
    private function __construct() {
        cpht_plugin_log('Initializing CPHT_ACF_Manager class');
        $this->json_dir = CPHT_PLUGIN_DIR . 'acf-json/';

        // IMPORTANT: Register local JSON save point immediately
        add_filter('acf/settings/save_json', array($this, 'acf_json_save_point'));

        // IMPORTANT: Register local JSON load point immediately
        add_filter('acf/settings/load_json', array($this, 'acf_json_load_point'));

        // Make sure ACF is loaded before we try to use it
        add_action('plugins_loaded', function() {
            // IMPORTANT: Hook into ACF initialization with priority 20
            add_action('acf/init', array($this, 'initialize_acf_sync'), 20);
        });

        // Add admin notices for syncing
        add_action('admin_notices', array($this, 'sync_admin_notice'));

        // Add an action to handle syncing
        add_action('admin_post_cpht_sync_acf', array($this, 'handle_sync_action'));

        cpht_plugin_log('CPHT ACF Manager initialization completed');
    }

    /**
     * Register method to support the original registration pattern.
     * This is called explicitly from the main plugin file.
     *
     * @since 1.0.0
     */
    public function register() {
        cpht_plugin_log('Registering CPHT ACF Manager hooks');

        // Ensure directories exist
        $this->ensure_json_directory();

        // Add JSON loading point
        add_filter('acf/settings/load_json', array($this, 'add_acf_json_load_point'));

        // Listen for field group sync completion
        add_action('acf/include_fields', array($this, 'on_fields_sync'));

        cpht_plugin_log('CPHT ACF Manager hooks registered');
    }

    /**
     * Set ACF JSON save point.
     *
     * @since 1.0.0
     * @param string $path Path to save ACF JSON files.
     * @return string Modified path.
     */
    public function acf_json_save_point($path) {
        cpht_plugin_log('CPHT ACF Manager: Setting save point to ' . $this->json_dir);
        return $this->json_dir;
    }

    /**
     * Add custom ACF JSON load point.
     *
     * @since 1.0.0
     * @param array $paths Existing ACF JSON load paths.
     * @return array Modified paths.
     */
    public function acf_json_load_point($paths) {
        // Add our path to the existing load paths
        $paths[] = $this->json_dir;
        cpht_plugin_log('CPHT ACF Manager: Adding load point ' . $this->json_dir);
        return $paths;
    }

    /**
     * Add custom ACF JSON load point - alias method.
     * This ensures compatibility with different naming conventions.
     *
     * @since 1.0.0
     * @param array $paths Existing ACF JSON load paths.
     * @return array Modified paths.
     */
    public function add_acf_json_load_point($paths) {
        // Simply call the original method for consistency
        return $this->acf_json_load_point($paths);
    }

    /**
     * Check if the local JSON directory exists and create it if needed.
     *
     * @since 1.0.0
     * @return bool Whether the directory exists or was created successfully
     */
    public function ensure_json_directory() {
        cpht_plugin_log('Checking ACF JSON directory: ' . $this->json_dir);

        if (!file_exists($this->json_dir)) {
            cpht_plugin_log('Creating ACF JSON directory at: ' . $this->json_dir);

            $created = wp_mkdir_p($this->json_dir);

            if (!$created) {
                cpht_plugin_log('Failed to create ACF JSON directory', 'error');
                return false;
            }

            // Add index.php for security
            $index_file = $this->json_dir . 'index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, '<?php // Silence is golden');
                cpht_plugin_log('Created index.php protection file in ACF JSON directory');
            }

            // Add .htaccess for additional security
            $htaccess_file = $this->json_dir . '.htaccess';
            if (!file_exists($htaccess_file)) {
                $htaccess_content = "# Protect JSON files from direct access\n";
                $htaccess_content .= "<Files ~ \"\.json$\">\n";
                $htaccess_content .= "    <IfModule mod_authz_core.c>\n";
                $htaccess_content .= "        Require all denied\n";
                $htaccess_content .= "    </IfModule>\n";
                $htaccess_content .= "    <IfModule !mod_authz_core.c>\n";
                $htaccess_content .= "        Order deny,allow\n";
                $htaccess_content .= "        Deny from all\n";
                $htaccess_content .= "    </IfModule>\n";
                $htaccess_content .= "</Files>";
                file_put_contents($htaccess_file, $htaccess_content);
                cpht_plugin_log('Created .htaccess protection file in ACF JSON directory');
            }

            cpht_plugin_log('ACF JSON directory created successfully');
            return true;
        }

        cpht_plugin_log('ACF JSON directory already exists');
        return true;
    }

    /**
     * Initialize ACF sync during acf/init hook.
     * This is the main entry point for synchronization.
     *
     * @since 1.0.0
     */
    public function initialize_acf_sync() {
        cpht_plugin_log('CPHT ACF Manager: initialize_acf_sync called');
        cpht_plugin_log('CPHT ACF Manager: Field group filename looking for: ' . $this->field_group_filename);

        // Check if we're in the admin
        if (!is_admin()) {
            cpht_plugin_log('CPHT ACF Manager: Skipping sync - not in admin');
            return;
        }

        // Wait for ACF to be fully loaded
        if (!function_exists('acf_get_field_group') || !function_exists('acf_get_field_groups')) {
            cpht_plugin_log('CPHT ACF Manager: ACF functions not available yet, will try again later');
            add_action('admin_init', array($this, 'delayed_sync'), 99);
            return;
        }

        // Clean up duplicates
        $this->cleanup_duplicates();

        // Import post type definitions
        $this->import_post_types();

        // Import field groups
        $this->import_field_groups();

        // Force sync on plugin activation or update
        if (isset($_GET['activated']) || isset($_GET['updated']) || isset($_GET['activated-multisite'])) {
            cpht_plugin_log('CPHT ACF Manager: Plugin activation or update detected, forcing sync');
            $this->handle_sync_action(true);
        }
    }

    /**
     * Import post type definitions from JSON.
     *
     * @since 1.0.0
     */
    public function import_post_types() {
        cpht_plugin_log('CPHT ACF Manager: Starting import_post_types');

        if (!function_exists('acf_get_post_type_post') || !function_exists('acf_update_post_type')) {
            cpht_plugin_log('CPHT ACF Manager: ACF functions for post types not available');
            return;
        }

        $json_file = $this->json_dir . $this->post_type_filename;
        cpht_plugin_log('CPHT ACF Manager: Looking for post type JSON file: ' . $json_file);

        // Check if the file exists
        if (!file_exists($json_file)) {
            cpht_plugin_log('CPHT ACF Manager: Post type JSON file not found: ' . $json_file);
            return;
        }

        $json_content = file_get_contents($json_file);
        if (empty($json_content)) {
            cpht_plugin_log('CPHT ACF Manager: Empty JSON file: ' . $json_file);
            return;
        }

        $post_type_data = json_decode($json_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            cpht_plugin_log('CPHT ACF Manager: JSON decode error: ' . json_last_error_msg() . ' in file: ' . $json_file);
            return;
        }

        // Skip if not an array or missing required keys
        if (!is_array($post_type_data) || !isset($post_type_data['key'])) {
            cpht_plugin_log('CPHT ACF Manager: Invalid post type data structure, missing key');
            return;
        }

        try {
            // Get post type key
            $post_type_key = $post_type_data['key'];
            cpht_plugin_log('CPHT ACF Manager: Processing post type: ' . $post_type_key);

            // Check if this post type already exists in ACF
            $existing = false;
            if (function_exists('acf_get_post_type_post')) {
                $existing = acf_get_post_type_post($post_type_key);
            }

            // Set import info
            $post_type_data['import_source'] = 'cpht-plugin';
            $post_type_data['import_date'] = date('Y-m-d H:i:s');

            if (!$existing) {
                // If it doesn't exist, create it
                cpht_plugin_log('CPHT ACF Manager: Importing post type: ' . $post_type_data['title']);

                // Different versions of ACF might require different approaches
                if (function_exists('acf_update_post_type')) {
                    acf_update_post_type($post_type_data);
                    cpht_plugin_log('CPHT ACF Manager: Successfully imported post type via acf_update_post_type()');
                } else {
                    // Fallback to native WordPress registration if ACF function not available
                    $this->register_post_type_fallback($post_type_data);
                }
            } else {
                // If it exists, update it instead of skipping
                cpht_plugin_log('CPHT ACF Manager: Post type already exists, updating: ' . $post_type_key);

                // Preserve the ID and other important properties
                if (is_array($existing) && isset($existing['ID'])) {
                    $post_type_data['ID'] = $existing['ID'];
                } elseif (is_object($existing) && isset($existing->ID)) {
                    $post_type_data['ID'] = $existing->ID;
                }

                // Update the post type
                if (function_exists('acf_update_post_type')) {
                    acf_update_post_type($post_type_data);
                    cpht_plugin_log('CPHT ACF Manager: Successfully updated post type via acf_update_post_type()');
                } else {
                    // Fallback to native WordPress registration if ACF function not available
                    $this->register_post_type_fallback($post_type_data);
                }
            }
        } catch (Exception $e) {
            cpht_plugin_log('CPHT ACF Manager: Error importing post type: ' . $e->getMessage());
        }
    }

    /**
     * Fallback post type registration using WordPress native functions.
     * Used if ACF post type registration fails.
     *
     * @since 1.0.0
     * @param array $post_type_data The post type definition from JSON
     */
    private function register_post_type_fallback($post_type_data) {
        // Only run if this isn't already registered
        if (post_type_exists($post_type_data['post_type'])) {
            cpht_plugin_log('CPHT ACF Manager: Post type already exists via WordPress, skipping fallback registration');
            return;
        }

        cpht_plugin_log('CPHT ACF Manager: Using fallback post type registration for: ' . $post_type_data['post_type']);

        // Get labels from the data or use defaults
        $labels = isset($post_type_data['labels']) ? $post_type_data['labels'] : array();

        // Basic arguments
        $args = array(
            'labels'             => $labels,
            'description'        => isset($post_type_data['description']) ? $post_type_data['description'] : '',
            'public'             => isset($post_type_data['public']) ? $post_type_data['public'] : true,
            'hierarchical'       => isset($post_type_data['hierarchical']) ? $post_type_data['hierarchical'] : false,
            'exclude_from_search' => isset($post_type_data['exclude_from_search']) ? $post_type_data['exclude_from_search'] : false,
            'publicly_queryable' => isset($post_type_data['publicly_queryable']) ? $post_type_data['publicly_queryable'] : true,
            'show_ui'            => isset($post_type_data['show_ui']) ? $post_type_data['show_ui'] : true,
            'show_in_menu'       => isset($post_type_data['show_in_menu']) ? $post_type_data['show_in_menu'] : true,
            'show_in_admin_bar'  => isset($post_type_data['show_in_admin_bar']) ? $post_type_data['show_in_admin_bar'] : false,
            'show_in_nav_menus'  => isset($post_type_data['show_in_nav_menus']) ? $post_type_data['show_in_nav_menus'] : true,
            'show_in_rest'       => isset($post_type_data['show_in_rest']) ? $post_type_data['show_in_rest'] : true,
            'menu_position'      => isset($post_type_data['menu_position']) ? $post_type_data['menu_position'] : null,
            'menu_icon'          => isset($post_type_data['menu_icon']) ? $post_type_data['menu_icon'] : 'dashicons-admin-post',
            'capability_type'    => 'post',
            'supports'           => isset($post_type_data['supports']) ? $post_type_data['supports'] : array('title', 'editor'),
            'taxonomies'         => isset($post_type_data['taxonomies']) ? $post_type_data['taxonomies'] : array(),
            'has_archive'        => isset($post_type_data['has_archive']) ? $post_type_data['has_archive'] : true,
        );

        // Handle rewrite rules
        if (isset($post_type_data['rewrite'])) {
            $rewrite = $post_type_data['rewrite'];
            $args['rewrite'] = array();

            // Handle simple or complex rewrite array formats
            if (is_array($rewrite)) {
                // If slug is specified
                if (isset($rewrite['slug'])) {
                    $args['rewrite']['slug'] = $rewrite['slug'];
                } else {
                    $args['rewrite']['slug'] = $post_type_data['post_type'];
                }

                // Handle feeds
                if (isset($rewrite['feeds'])) {
                    $args['rewrite']['feeds'] = ($rewrite['feeds'] === '1' || $rewrite['feeds'] === true);
                } else {
                    $args['rewrite']['feeds'] = false;
                }

                // Handle with_front if set
                if (isset($rewrite['with_front'])) {
                    $args['rewrite']['with_front'] = ($rewrite['with_front'] === '1' || $rewrite['with_front'] === true);
                }

                // Handle pages if set
                if (isset($rewrite['pages'])) {
                    $args['rewrite']['pages'] = ($rewrite['pages'] === '1' || $rewrite['pages'] === true);
                }
            } else if ($rewrite === false) {
                // If rewrite is explicitly set to false
                $args['rewrite'] = false;
            }
        }

        // Register the post type
        register_post_type($post_type_data['post_type'], $args);
        cpht_plugin_log('CPHT ACF Manager: Fallback post type registration complete');
    }

    /**
     * Import field groups from JSON.
     *
     * @since 1.0.0
     */
    public function import_field_groups() {
        cpht_plugin_log('CPHT ACF Manager: Starting import_field_groups');

        if (!function_exists('acf_get_field_group') || !function_exists('acf_import_field_group')) {
            cpht_plugin_log('CPHT ACF Manager: ACF functions for field groups not available');
            return;
        }

        $json_file = $this->json_dir . $this->field_group_filename;
        cpht_plugin_log('CPHT ACF Manager: Looking for field group JSON file: ' . $json_file);
        cpht_plugin_log('CPHT ACF Manager: File exists check: ' . (file_exists($json_file) ? 'YES' : 'NO'));

        if (!file_exists($json_file)) {
            cpht_plugin_log('CPHT ACF Manager: Field group JSON file not found: ' . $json_file);

            // Check directory contents to see what files are actually there
            $dir_contents = scandir($this->json_dir);
            cpht_plugin_log('CPHT ACF Manager: Directory contents: ' . print_r($dir_contents, true));
            return;
        }

        $json_content = file_get_contents($json_file);
        cpht_plugin_log('CPHT ACF Manager: JSON file length: ' . strlen($json_content));
        if (empty($json_content)) {
            cpht_plugin_log('CPHT ACF Manager: Empty field group JSON file: ' . $json_file);
            return;
        }

        $field_group = json_decode($json_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            cpht_plugin_log('CPHT ACF Manager: Field group JSON decode error: ' . json_last_error_msg());
            cpht_plugin_log('CPHT ACF Manager: First 100 characters of JSON: ' . substr($json_content, 0, 100) . '...');
            return;
        }

        cpht_plugin_log('CPHT ACF Manager: JSON decoded successfully with structure: ' . print_r(array_keys($field_group), true));

        if (!is_array($field_group) || !isset($field_group['key'])) {
            cpht_plugin_log('CPHT ACF Manager: Invalid field group JSON structure');
            return;
        }

        cpht_plugin_log('CPHT ACF Manager: Field group key from JSON: ' . $field_group['key']);
        cpht_plugin_log('CPHT ACF Manager: Field group title from JSON: ' . $field_group['title']);

        // Force the field group to be imported regardless of whether it exists
        $this->import_single_field_group($field_group, true);
    }

    /**
     * Import a single field group.
     *
     * @since 1.0.0
     * @param array $field_group Field group definition
     * @param bool $force_import Whether to force import even if the field group exists
     */
    private function import_single_field_group($field_group, $force_import = false) {
        cpht_plugin_log('CPHT ACF Manager: Importing field group: ' . $field_group['key']);
        cpht_plugin_log('CPHT ACF Manager: Field group has ' . count($field_group['fields']) . ' fields');

        // Make sure ACF is fully loaded before proceeding
        if (!function_exists('acf_get_field_group') || !function_exists('acf_update_field_group')) {
            cpht_plugin_log('CPHT ACF Manager: ACF functions not available, skipping field group import');
            return;
        }

        // Check if this field group already exists
        $existing = acf_get_field_group($field_group['key']);
        cpht_plugin_log('CPHT ACF Manager: Existing field group check: ' . ($existing ? 'EXISTS' : 'DOES NOT EXIST'));

        try {
            if (!$existing) {
                // If it doesn't exist, create it
                cpht_plugin_log('CPHT ACF Manager: Creating new field group');

                // Make sure we're not trying to set an ID that doesn't exist
                if (isset($field_group['ID'])) {
                    unset($field_group['ID']);
                }

                // Import the field group
                acf_import_field_group($field_group);
                cpht_plugin_log('CPHT ACF Manager: Successfully created field group: ' . $field_group['title']);
            } else if ($force_import) {
                // If it exists and we're forcing import, update it instead of deleting and recreating
                cpht_plugin_log('CPHT ACF Manager: Updating existing field group');

                // Preserve the ID and other important properties
                $field_group['ID'] = $existing['ID'];

                // Update the field group
                acf_update_field_group($field_group);
                cpht_plugin_log('CPHT ACF Manager: Successfully updated field group: ' . $field_group['title']);
            } else {
                cpht_plugin_log('CPHT ACF Manager: Field group already exists and force_import is false, skipping');
            }
        } catch (Exception $e) {
            cpht_plugin_log('CPHT ACF Manager: Error importing field group: ' . $e->getMessage());
        }
    }

    /**
     * Callback for when fields are included/synced.
     *
     * @since 1.0.0
     */
    public function on_fields_sync() {
        cpht_plugin_log('CPHT ACF Manager: ACF fields have been included/synced - checking field group status');

        // Additional operations after field groups are synced can be added here
        if (function_exists('acf_get_field_groups')) {
            $field_groups = acf_get_field_groups();
            $cpht_groups = 0;

            foreach ($field_groups as $field_group) {
                if (isset($field_group['title']) && stripos($field_group['title'], 'cpht') !== false) {
                    $cpht_groups++;
                    cpht_plugin_log('CPHT ACF Manager: Found CPHT field group: ' . $field_group['title']);
                }
            }

            cpht_plugin_log('CPHT ACF Manager: Total CPHT field groups found: ' . $cpht_groups);
        }
    }

    /**
     * Display admin notice if there are field groups that need syncing.
     *
     * @since 1.0.0
     */
    public function sync_admin_notice() {
        // Only show on ACF admin pages
        $screen = get_current_screen();
        if (!$screen || !is_object($screen) || !isset($screen->id) || strpos($screen->id, 'acf-field-group') === false) {
            return;
        }

        cpht_plugin_log('CPHT ACF Manager: Checking for field groups requiring sync');
        $sync_required = $this->get_field_groups_requiring_sync();

        if (!empty($sync_required) && is_array($sync_required)) {
            cpht_plugin_log('CPHT ACF Manager: Found ' . count($sync_required) . ' field groups requiring sync');
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <?php
                    printf(
                        _n(
                            'There is %d CPHT field group that requires synchronization.',
                            'There are %d CPHT field groups that require synchronization.',
                            count($sync_required),
                            'cpht-plugin'
                        ),
                        count($sync_required)
                    );
                    ?>
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=cpht_sync_acf'), 'cpht_sync_acf')); ?>" class="button button-primary">
                        <?php _e('Sync Field Groups', 'cpht-plugin'); ?>
                    </a>
                </p>
            </div>
            <?php
        } else {
            cpht_plugin_log('CPHT ACF Manager: No field groups require sync');
        }
    }

    /**
     * Get field groups that require synchronization.
     *
     * @since 1.0.0
     * @return array Array of field groups that require synchronization
     */
    private function get_field_groups_requiring_sync() {
        if (!function_exists('acf_get_field_group')) {
            cpht_plugin_log('CPHT ACF Manager: acf_get_field_group function not available');
            return array();
        }

        $sync_required = array();
        $json_file = $this->json_dir . $this->field_group_filename;

        if (file_exists($json_file)) {
            $json_content = file_get_contents($json_file);
            $json_group = json_decode($json_content, true);

            if (is_array($json_group) && isset($json_group['key'])) {
                // Get database version
                $db_group = acf_get_field_group($json_group['key']);
                cpht_plugin_log('CPHT ACF Manager: Checking sync status for: ' . $json_group['key']);

                // If DB version doesn't exist or has a different modified time, it needs sync
                if (!$db_group) {
                    cpht_plugin_log('CPHT ACF Manager: Field group not found in database, sync required');
                    $sync_required[] = $json_group;
                } else if (isset($json_group['modified']) && isset($db_group['modified']) && $db_group['modified'] != $json_group['modified']) {
                    cpht_plugin_log('CPHT ACF Manager: Field group modified time mismatch, sync required');
                    cpht_plugin_log('CPHT ACF Manager: JSON modified: ' . $json_group['modified'] . ', DB modified: ' . $db_group['modified']);
                    $sync_required[] = $json_group;
                }
            } else {
                cpht_plugin_log('CPHT ACF Manager: Invalid field group JSON structure or missing key');
            }
        } else {
            cpht_plugin_log('CPHT ACF Manager: Field group JSON file not found: ' . $json_file);
        }

        return $sync_required;
    }

    /**
     * Handle the synchronization action.
     *
     * @since 1.0.0
     * @param bool $skip_security_check Whether to skip security checks (for internal calls)
     */
    public function handle_sync_action($skip_security_check = false) {
        cpht_plugin_log('CPHT ACF Manager: Handling sync action');

        // Security check - skip if called internally
        if (!$skip_security_check) {
            if (!current_user_can('manage_options')) {
                cpht_plugin_log('CPHT ACF Manager: Security check failed - insufficient permissions');
                wp_die(__('You do not have sufficient permissions to access this page.', 'cpht-plugin'));
            }

            // Verify nonce for security
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'cpht_sync_acf')) {
                cpht_plugin_log('CPHT ACF Manager: Security check failed - invalid nonce');
                wp_die(__('Security check failed.', 'cpht-plugin'));
            }

            cpht_plugin_log('CPHT ACF Manager: Security checks passed, proceeding with sync');
        } else {
            cpht_plugin_log('CPHT ACF Manager: Security checks skipped (internal call), proceeding with sync');
        }

        // Check for existing field groups with our key pattern to avoid duplicates
        if (function_exists('acf_get_field_groups')) {
            $field_groups = acf_get_field_groups();
            $cpht_groups = [];

            foreach ($field_groups as $group) {
                if (isset($group['key']) && $group['key'] === $this->field_group_key) {
                    $cpht_groups[] = $group;
                }
            }

            // If we have multiple field groups with the same key, clean them up
            if (count($cpht_groups) > 1) {
                cpht_plugin_log('CPHT ACF Manager: Found ' . count($cpht_groups) . ' duplicate field groups, cleaning up');

                // Keep the first one, delete the rest
                for ($i = 1; $i < count($cpht_groups); $i++) {
                    cpht_plugin_log('CPHT ACF Manager: Deleting duplicate field group ID: ' . $cpht_groups[$i]['ID']);
                    acf_delete_field_group($cpht_groups[$i]['ID']);
                }
            }
        }

        // Check for existing post types with our key pattern to avoid duplicates
        if (function_exists('acf_get_post_types')) {
            $post_types = acf_get_post_types();
            $cpht_post_types = [];

            foreach ($post_types as $post_type) {
                if (isset($post_type['key']) && $post_type['key'] === $this->post_type_key) {
                    $cpht_post_types[] = $post_type;
                }
            }

            // If we have multiple post types with the same key, clean them up
            if (count($cpht_post_types) > 1) {
                cpht_plugin_log('CPHT ACF Manager: Found ' . count($cpht_post_types) . ' duplicate post types, cleaning up');

                // Keep the first one, delete the rest
                for ($i = 1; $i < count($cpht_post_types); $i++) {
                    $post_type_id = null;
                    if (is_array($cpht_post_types[$i]) && isset($cpht_post_types[$i]['ID'])) {
                        $post_type_id = $cpht_post_types[$i]['ID'];
                    } elseif (is_object($cpht_post_types[$i]) && isset($cpht_post_types[$i]->ID)) {
                        $post_type_id = $cpht_post_types[$i]->ID;
                    }

                    if ($post_type_id) {
                        cpht_plugin_log('CPHT ACF Manager: Deleting duplicate post type ID: ' . $post_type_id);
                        if (function_exists('acf_delete_post_type')) {
                            acf_delete_post_type($post_type_id);
                        }
                    }
                }
            }
        }

        // Import post types
        $this->import_post_types();

        // Import field groups
        $this->import_field_groups();

        cpht_plugin_log('CPHT ACF Manager: Sync completed');

        // Only redirect if this is a manual sync action
        if (!$skip_security_check) {
            cpht_plugin_log('CPHT ACF Manager: Redirecting after manual sync');
            // Redirect to the main ACF field groups list
            wp_redirect(add_query_arg(array(
                'post_type' => 'acf-field-group',
                'sync' => 'complete',
                'count' => 1
            ), admin_url('edit.php')));
            exit;
        }
    }

    /**
     * Check if there are field groups available for sync.
     * This is a helper method for debugging purposes.
     *
     * @since 1.0.0
     * @return bool Whether field groups are available for sync.
     */
    public function check_sync_available() {
        // Only check if ACF Pro is active and needed functions exist
        if (!function_exists('acf_get_local_json_files') || !function_exists('acf_get_field_group')) {
            cpht_plugin_log('CPHT ACF Manager: ACF functions not available to check sync status', 'warning');
            return false;
        }

        cpht_plugin_log('CPHT ACF Manager: Checking for available field group sync');

        // Get local JSON files
        $json_files = acf_get_local_json_files();
        if (empty($json_files)) {
            cpht_plugin_log('CPHT ACF Manager: No local JSON files found');
            return false;
        }

        cpht_plugin_log('CPHT ACF Manager: Found ' . count($json_files) . ' local JSON files');

        // Check each file to see if it's synced
        $sync_available = false;
        foreach ($json_files as $key => $file) {
            // Get the field group from the file
            $local_field_group = json_decode(file_get_contents($file), true);
            if (!$local_field_group) {
                continue;
            }

            // Only care about our plugin's field groups
            if (isset($local_field_group['title']) && stripos($local_field_group['title'], 'cpht') === false) {
                continue;
            }

            // Check if this field group exists in the database
            $db_field_group = acf_get_field_group($key);

            if (!$db_field_group) {
                cpht_plugin_log('CPHT ACF Manager: Field group needs sync: ' . $local_field_group['title']);
                $sync_available = true;
            } else {
                // Check if the database version matches the file version
                $db_modified = $db_field_group['modified'] ?? 0;
                $file_modified = $local_field_group['modified'] ?? 0;

                if ($file_modified > $db_modified) {
                    cpht_plugin_log('CPHT ACF Manager: Field group needs update: ' . $local_field_group['title']);
                    $sync_available = true;
                }
            }
        }

        return $sync_available;
    }
}