<?php
/**
 * Plugin Name: CPHT Plugin
 * Plugin URI: https://github.com/OrasesWPDev/cpht-plugin/blob/main/cpht-plugin.php
 * Description: Honors the nation's pharmacy technicians who showed heroic strength, courage, and hope as they faced unprecedented demands in the battle against COVID-19
 * Version: 1.0.0
 * Author: Orases
 * Author URI: https://orases.com
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cpht-plugin
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

// Define plugin constants
define('CPHT_PLUGIN_VERSION', '1.0.0');
define('CPHT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CPHT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CPHT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('CPHT_PLUGIN_FILE', __FILE__);
define('CPHT_PLUGIN_INCLUDES_DIR', CPHT_PLUGIN_DIR . 'includes/');
define('CPHT_PLUGIN_TEMPLATES_DIR', CPHT_PLUGIN_DIR . 'templates/');
define('CPHT_PLUGIN_ASSETS_DIR', CPHT_PLUGIN_DIR . 'assets/');
define('CPHT_PLUGIN_ASSETS_URL', CPHT_PLUGIN_URL . 'assets/');
define('CPHT_PLUGIN_LOGS_DIR', CPHT_PLUGIN_DIR . 'logs/');

// Define debug constant based on WP_DEBUG
define('CPHT_PLUGIN_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

/**
 * Log debug message.
 *
 * @param mixed $message Message to log.
 * @param string $level Log level (debug, info, warning, error).
 */
function cpht_plugin_log($message, $level = 'debug') {
	// Skip if debugging is not enabled
	if (!defined('CPHT_PLUGIN_DEBUG') || !CPHT_PLUGIN_DEBUG) {
		return;
	}

	// Format message
	if (is_array($message) || is_object($message)) {
		$message = print_r($message, true);
	}

	// Prepend time and level
	$message = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $message;

	// Log file path
	$log_file = CPHT_PLUGIN_LOGS_DIR . 'debug.log';

	// Create logs directory if it doesn't exist
	$logs_dir = dirname($log_file);
	if (!file_exists($logs_dir)) {
		wp_mkdir_p($logs_dir);

		// Add index.php for security
		$index_file = $logs_dir . '/index.php';
		if (!file_exists($index_file)) {
			file_put_contents($index_file, '<?php // Silence is golden');
		}

		// Add .htaccess to protect log files
		$htaccess_file = $logs_dir . '/.htaccess';
		if (!file_exists($htaccess_file)) {
			$htaccess_content = "# Deny access to all files\n";
			$htaccess_content .= "<Files ~ \"\.log$\">\n";
			$htaccess_content .= "  Order Allow,Deny\n";
			$htaccess_content .= "  Deny from all\n";
			$htaccess_content .= "</Files>\n";
			file_put_contents($htaccess_file, $htaccess_content);
		}
	}

	// Append to log file
	error_log($message . PHP_EOL, 3, $log_file);
}

/**
 * Check if ACF Pro is active
 *
 * @return bool True if ACF Pro is active, false otherwise
 */
function cpht_plugin_is_acf_pro_active() {
	cpht_plugin_log('Checking ACF Pro status');
	return class_exists('acf') && function_exists('acf_add_local_field_group');
}

/**
 * Admin notice for missing ACF Pro dependency
 */
function cpht_plugin_acf_pro_admin_notice() {
	?>
    <div class="notice notice-error">
        <p><?php _e('CPHT Plugin requires Advanced Custom Fields PRO to be installed and activated.', 'cpht-plugin'); ?></p>
    </div>
	<?php
}

/**
 * Check for plugin dependencies
 *
 * @return bool True if all dependencies are met, false otherwise
 */
function cpht_plugin_check_dependencies() {
	cpht_plugin_log('Checking plugin dependencies');
	if (!cpht_plugin_is_acf_pro_active()) {
		cpht_plugin_log('ACF Pro not active, adding admin notice', 'warning');
		add_action('admin_notices', 'cpht_plugin_acf_pro_admin_notice');
		return false;
	}
	cpht_plugin_log('All dependencies satisfied');
	return true;
}

// Register activation and deactivation hooks
register_activation_hook(CPHT_PLUGIN_FILE, 'cpht_plugin_activate');
register_deactivation_hook(CPHT_PLUGIN_FILE, 'cpht_plugin_deactivate');

/**
 * Plugin activation function.
 */
function cpht_plugin_activate() {
	cpht_plugin_log('Plugin activation started');

	// Check ACF Pro dependency
	if (!cpht_plugin_is_acf_pro_active()) {
		// Deactivate the plugin
		deactivate_plugins(CPHT_PLUGIN_BASENAME);

		// Bail and show admin notice
		wp_die(__('CPHT Plugin requires Advanced Custom Fields PRO to be installed and activated.', 'cpht-plugin'), 'Plugin dependency check', array('back_link' => true));
		return;
	}

	// Include the activator class
	require_once CPHT_PLUGIN_INCLUDES_DIR . 'class-cpht-activator.php';
	CPHT_Activator::activate();

	// Flush rewrite rules
	flush_rewrite_rules();

	cpht_plugin_log('Plugin activation completed');
}

/**
 * Plugin deactivation function.
 */
function cpht_plugin_deactivate() {
	cpht_plugin_log('Plugin deactivation started');

	// Include the deactivator class
	require_once CPHT_PLUGIN_INCLUDES_DIR . 'class-cpht-deactivator.php';
	CPHT_Deactivator::deactivate();

	// Flush rewrite rules
	flush_rewrite_rules();

	cpht_plugin_log('Plugin deactivation completed');
}

/**
 * Get the version number to use for assets.
 *
 * Uses file modification time for cache busting when in development,
 * or plugin version in production.
 *
 * @param string $file_path Full server path to the file
 * @return string Version number to use
 */
function cpht_get_asset_version($file_path) {
	// If the file exists, use its modification time for cache busting
	if (file_exists($file_path)) {
		return filemtime($file_path);
	}

	// Otherwise use the plugin version
	return CPHT_PLUGIN_VERSION;
}

/**
 * Enqueue admin scripts and styles.
 */
function cpht_plugin_admin_enqueue_scripts() {
	$screen = get_current_screen();

	// Auto-detect and load all CSS files in the assets/css directory
	$css_dir = CPHT_PLUGIN_ASSETS_DIR . 'css/';
	if (is_dir($css_dir)) {
		$css_files = glob($css_dir . '*.css');
		foreach ($css_files as $css_file) {
			$file_name = basename($css_file, '.css');
			$css_url = CPHT_PLUGIN_ASSETS_URL . 'css/' . basename($css_file);
			$css_version = cpht_get_asset_version($css_file);

			// Only load certain CSS files on specific screens
			if (strpos($file_name, 'admin') !== false) {
				// Admin CSS - load on all admin screens
				wp_enqueue_style('cpht-' . $file_name, $css_url, array(), $css_version);
			} elseif (strpos($file_name, 'public') !== false && $screen && $screen->post_type === 'cpht_post') {
				// Public CSS - only load on our post type screens
				wp_enqueue_style('cpht-' . $file_name, $css_url, array(), $css_version);
			} elseif (strpos($file_name, 'single') !== false && $screen && $screen->post_type === 'cpht_post') {
				// Single post CSS - only load on our post type screens
				wp_enqueue_style('cpht-' . $file_name, $css_url, array(), $css_version);
			} elseif ($screen && $screen->id === 'cpht_post_page_cpht-plugin-help' && strpos($file_name, 'help') !== false) {
				// Help CSS - only load on our help page
				wp_enqueue_style('cpht-' . $file_name, $css_url, array(), $css_version);
			}
		}
	}

	// Auto-detect and load all JS files in the assets/js directory
	$js_dir = CPHT_PLUGIN_ASSETS_DIR . 'js/';
	if (is_dir($js_dir)) {
		$js_files = glob($js_dir . '*.js');
		foreach ($js_files as $js_file) {
			$file_name = basename($js_file, '.js');
			$js_url = CPHT_PLUGIN_ASSETS_URL . 'js/' . basename($js_file);
			$js_version = cpht_get_asset_version($js_file);

			// Only load certain JS files on specific screens
			if (strpos($file_name, 'admin') !== false) {
				// Admin JS - load on all admin screens
				wp_enqueue_script('cpht-' . $file_name, $js_url, array('jquery'), $js_version, true);
			} elseif ($screen && $screen->post_type === 'cpht_post') {
				// Post type specific JS - only load on our post type screens
				wp_enqueue_script('cpht-' . $file_name, $js_url, array('jquery'), $js_version, true);
			}
		}
	}
}

/**
 * Enqueue public scripts and styles.
 */
function cpht_plugin_public_enqueue_scripts() {
	// First, determine if we're on a relevant page
	$is_cpht_page = is_singular('cpht_post') || is_post_type_archive('cpht_post');

	// Also check if we're on a page with the shortcode
	if (!$is_cpht_page && is_singular('page')) {
		global $post;
		if ($post && has_shortcode($post->post_content, 'cpht_posts')) {
			$is_cpht_page = true;
		}
	}

	if (!$is_cpht_page) {
		return;
	}

	// Auto-detect and load all CSS files from the assets/css directory
	$css_dir = CPHT_PLUGIN_ASSETS_DIR . 'css/';
	if (is_dir($css_dir)) {
		$css_files = glob($css_dir . '*.css');
		foreach ($css_files as $css_file) {
			$file_name = basename($css_file, '.css');
			$css_url = CPHT_PLUGIN_ASSETS_URL . 'css/' . basename($css_file);
			$css_version = cpht_get_asset_version($css_file);

			// Determine which CSS files to load based on context
			if (strpos($file_name, 'public') !== false) {
				// Always load public CSS on CPHT pages
				wp_enqueue_style('cpht-' . $file_name, $css_url, array(), $css_version);
			} elseif (strpos($file_name, 'single') !== false && is_singular('cpht_post')) {
				// Only load single CSS on single post pages
				wp_enqueue_style('cpht-' . $file_name, $css_url, array(), $css_version);
			}
		}
	}

	// Auto-detect and load JS files
	$js_dir = CPHT_PLUGIN_ASSETS_DIR . 'js/';
	if (is_dir($js_dir)) {
		$js_files = glob($js_dir . '*.js');
		foreach ($js_files as $js_file) {
			$file_name = basename($js_file, '.js');
			$js_url = CPHT_PLUGIN_ASSETS_URL . 'js/' . basename($js_file);
			$js_version = cpht_get_asset_version($js_file);

			// Load JS files based on context
			if (strpos($file_name, 'public') !== false) {
				// Add Ajax URL for the script
				wp_enqueue_script('cpht-' . $file_name, $js_url, array('jquery'), $js_version, true);
				wp_localize_script('cpht-' . $file_name, 'cpht_params', array(
					'ajax_url' => admin_url('admin-ajax.php'),
				));
			}
		}
	}
}

/**
 * Initialize the plugin.
 * Loads required files and starts the plugin if dependencies are met.
 */
function cpht_plugin_init() {
	cpht_plugin_log('Plugin initialization started');

	// Check if dependencies are met before proceeding
	if (!cpht_plugin_check_dependencies()) {
		cpht_plugin_log('Dependencies not met, aborting initialization', 'warning');
		return;
	}

	// Load all PHP files from the includes directory
	$includes_dir = CPHT_PLUGIN_INCLUDES_DIR;
	if (is_dir($includes_dir)) {
		$files = scandir($includes_dir);
		foreach ($files as $file) {
			$file_path = $includes_dir . $file;
			if (is_file($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) === 'php' && $file !== 'index.php') {
				cpht_plugin_log('Loading file: ' . $file);
				require_once $file_path;
			}
		}
	} else {
		cpht_plugin_log('Includes directory not found: ' . $includes_dir, 'error');
		return;
	}

	// Initialize the main plugin class
	if (class_exists('CPHT_Plugin')) {
		$plugin = CPHT_Plugin::get_instance();
		$plugin->run();
	} else {
		cpht_plugin_log('CPHT_Plugin class not found', 'error');
	}

	// Initialize help documentation if we're in admin
	if (is_admin() && class_exists('CPHT_Help')) {
		CPHT_Help::get_instance();
		cpht_plugin_log('CPHT Help documentation initialized');
	}

	// Register admin scripts and styles
	add_action('admin_enqueue_scripts', 'cpht_plugin_admin_enqueue_scripts');

	// Register public scripts and styles
	add_action('wp_enqueue_scripts', 'cpht_plugin_public_enqueue_scripts');

	cpht_plugin_log('Plugin initialization completed');
}

// Hook into WordPress init with a priority that ensures ACF is loaded first
add_action('plugins_loaded', 'cpht_plugin_init');