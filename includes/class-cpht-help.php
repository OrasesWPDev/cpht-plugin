<?php
/**
 * Help Documentation Page
 *
 * @package CPHT_Plugin
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class to handle CPHT help documentation.
 */
class CPHT_Help {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the class.
	 */
	private function __construct() {
		// Add submenu page
		add_action('admin_menu', array($this, 'add_help_page'), 30);

		// Add admin-specific styles
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));

		// Add plugin action links
		add_filter('plugin_action_links_' . CPHT_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		if (null == self::$instance) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Add Help/Documentation page for the plugin
	 */
	public function add_help_page() {
		add_submenu_page(
			'edit.php?post_type=cpht_post',     // Parent menu slug
			'CPHT Plugin Help',                  // Page title
			'Help & Documentation',              // Menu title
			'edit_posts',                        // Capability
			'cpht-plugin-help',                  // Menu slug
			array($this, 'help_page_content')    // Callback function
		);
	}

	/**
	 * Add plugin action links
	 *
	 * @param array $links Existing plugin action links
	 * @return array Modified links
	 */
	public function add_plugin_action_links($links) {
		$help_link = '<a href="' . admin_url('edit.php?post_type=cpht_post&page=cpht-plugin-help') . '">' . __('Help', 'cpht-plugin') . '</a>';
		array_unshift($links, $help_link);
		return $links;
	}

	/**
	 * Enqueue styles for admin help page
	 *
	 * @param string $hook Current admin page
	 */
	public function enqueue_admin_styles($hook) {
		// Only load on our help page
		if ('cpht_post_page_cpht-plugin-help' !== $hook) {
			return;
		}

		// Add inline styles for help page
		wp_add_inline_style('wp-admin', $this->get_admin_styles());
	}

	/**
	 * Get admin styles for help page
	 *
	 * @return string CSS styles
	 */
	private function get_admin_styles() {
		return '
            .cpht-help-wrap {
                max-width: 1300px;
                margin: 20px 20px 0 0;
            }
            .cpht-help-header {
                background: #fff;
                padding: 20px;
                border-radius: 3px;
                margin-bottom: 20px;
                border-left: 4px solid #0073aa;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .cpht-help-section {
                background: #fff;
                padding: 20px;
                border-radius: 3px;
                margin-bottom: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                overflow-x: auto;
            }
            .cpht-help-section h2 {
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
                margin-top: 0;
            }
            .cpht-help-section h3 {
                margin-top: 1.5em;
                margin-bottom: 0.5em;
            }
            .cpht-help-section table {
                border-collapse: collapse;
                width: 100%;
                margin: 1em 0;
                table-layout: fixed;
            }
            .cpht-help-section table th,
            .cpht-help-section table td {
                text-align: left;
                padding: 8px;
                border: 1px solid #ddd;
                vertical-align: top;
                word-wrap: break-word;
                word-break: break-word;
                hyphens: auto;
            }
            .cpht-help-section table th:nth-child(1),
            .cpht-help-section table td:nth-child(1) {
                width: 15%;
            }
            .cpht-help-section table th:nth-child(2),
            .cpht-help-section table td:nth-child(2) {
                width: 25%;
            }
            .cpht-help-section table th:nth-child(3),
            .cpht-help-section table td:nth-child(3) {
                width: 10%;
            }
            .cpht-help-section table th:nth-child(4),
            .cpht-help-section table td:nth-child(4) {
                width: 20%;
            }
            .cpht-help-section table th:nth-child(5),
            .cpht-help-section table td:nth-child(5) {
                width: 30%;
            }
            .cpht-help-section table th {
                background-color: #f8f8f8;
                font-weight: 600;
            }
            .cpht-help-section table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .cpht-help-section code {
                background: #f8f8f8;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 13px;
                color: #0073aa;
                display: inline-block;
                max-width: 100%;
                overflow-wrap: break-word;
                white-space: normal;
            }
            .cpht-shortcode-example {
                background: #f8f8f8;
                padding: 15px;
                border-left: 4px solid #0073aa;
                font-family: monospace;
                margin: 10px 0;
                overflow-x: auto;
                white-space: pre-wrap;
                word-break: break-word;
            }
        ';
	}

	/**
	 * Content for help page
	 */
	public function help_page_content() {
		?>
		<div class="wrap cpht-help-wrap">
			<div class="cpht-help-header">
				<h1><?php esc_html_e('CPHT Plugin - Documentation', 'cpht-plugin'); ?></h1>
				<p><?php esc_html_e('This page provides documentation on how to use CPHT Plugin shortcodes and features.', 'cpht-plugin'); ?></p>
			</div>

			<!-- Overview Section -->
			<div class="cpht-help-section">
				<h2><?php esc_html_e('Overview', 'cpht-plugin'); ?></h2>
				<p><?php esc_html_e('CPHT Plugin allows you to create and display CpHT posts on your site. The plugin provides shortcodes to display posts in a grid layout.', 'cpht-plugin'); ?></p>
				<ul>
					<li><code>[cpht_posts]</code> - <?php esc_html_e('Display multiple CpHT posts in a grid layout', 'cpht-plugin'); ?></li>
					<li><code>[cpht_breadcrumbs]</code> - <?php esc_html_e('Display breadcrumb navigation for CpHT posts', 'cpht-plugin'); ?></li>
				</ul>
			</div>

			<!-- Shortcode Section -->
			<div class="cpht-help-section">
				<h2><?php esc_html_e('Shortcode: [cpht_posts]', 'cpht-plugin'); ?></h2>
				<p><?php esc_html_e('This shortcode displays a grid of CpHT Posts with various customization options.', 'cpht-plugin'); ?></p>

				<h3><?php esc_html_e('Basic Usage', 'cpht-plugin'); ?></h3>
				<div class="cpht-shortcode-example">
					[cpht_posts]
				</div>

				<h3><?php esc_html_e('Display Options', 'cpht-plugin'); ?></h3>
				<table>
					<tr>
						<th><?php esc_html_e('Parameter', 'cpht-plugin'); ?></th>
						<th><?php esc_html_e('Description', 'cpht-plugin'); ?></th>
						<th><?php esc_html_e('Default', 'cpht-plugin'); ?></th>
						<th><?php esc_html_e('Options', 'cpht-plugin'); ?></th>
						<th><?php esc_html_e('Examples', 'cpht-plugin'); ?></th>
					</tr>
					<tr>
						<td><code>columns</code></td>
						<td><?php esc_html_e('Number of columns in grid view', 'cpht-plugin'); ?></td>
						<td><code>3</code></td>
						<td><?php esc_html_e('1-4', 'cpht-plugin'); ?></td>
						<td><code>columns="2"</code><br><code>columns="4"</code></td>
					</tr>
					<tr>
						<td><code>posts_per_page</code></td>
						<td><?php esc_html_e('Number of posts to display', 'cpht-plugin'); ?></td>
						<td><code>9</code></td>
						<td><?php esc_html_e('any number, -1 for all', 'cpht-plugin'); ?></td>
						<td><code>posts_per_page="6"</code><br><code>posts_per_page="-1"</code></td>
					</tr>
				</table>

				<h3><?php esc_html_e('Ordering Parameters', 'cpht-plugin'); ?></h3>
				<table>
					<tr>
						<th><?php esc_html_e('Parameter', 'cpht-plugin'); ?></th>
						<th><?php esc_html_e('Description', 'cpht-plugin'); ?></th>
						<th><?php esc_html_e('Default', 'cpht-plugin'); ?></th>
						<th><?php esc_html_e('Options', 'cpht-plugin'); ?></th>
						<th><?php esc_html_e('Examples', 'cpht-plugin'); ?></th>
					</tr>
					<tr>
						<td><code>order</code></td>
						<td><?php esc_html_e('Sort order', 'cpht-plugin'); ?></td>
						<td><code>DESC</code></td>
						<td><code>ASC</code>, <code>DESC</code></td>
						<td><code>order="ASC"</code></td>
					</tr>
					<tr>
						<td><code>orderby</code></td>
						<td><?php esc_html_e('Field to order by', 'cpht-plugin'); ?></td>
						<td><code>date</code></td>
						<td><code>date</code>, <code>title</code>, <code>menu_order</code>, <code>rand</code></td>
						<td><code>orderby="title"</code><br><code>orderby="rand"</code></td>
					</tr>
				</table>

				<h3><?php esc_html_e('Filtering Parameters', 'cpht-plugin'); ?></h3>
				<table>
					<tr>
						<th><?php esc_html_e('Parameter', 'cpht-plugin'); ?></th>
						<th><?php esc_html_e('Description', 'cpht-plugin'); ?></th>
						<th><?php esc_html_e('Default', 'cpht-plugin'); ?></th>
						<th><?php esc_html_e('Options', 'cpht-plugin'); ?></th>
						<th><?php esc_html_e('Examples', 'cpht-plugin'); ?></th>
					</tr>
					<tr>
						<td><code>category</code></td>
						<td><?php esc_html_e('Filter by category', 'cpht-plugin'); ?></td>
						<td><code>''</code></td>
						<td><?php esc_html_e('category slug', 'cpht-plugin'); ?></td>
						<td><code>category="featured"</code></td>
					</tr>
				</table>

				<h3><?php esc_html_e('Example Shortcodes', 'cpht-plugin'); ?></h3>
				<p><?php esc_html_e('Basic grid with 3 columns:', 'cpht-plugin'); ?></p>
				<div class="cpht-shortcode-example">
					[cpht_posts columns="3" posts_per_page="6"]
				</div>

				<p><?php esc_html_e('Display stories from a specific category:', 'cpht-plugin'); ?></p>
				<div class="cpht-shortcode-example">
					[cpht_posts category="featured" posts_per_page="12"]
				</div>

				<p><?php esc_html_e('Display stories in a 2-column layout, ordered by title:', 'cpht-plugin'); ?></p>
				<div class="cpht-shortcode-example">
					[cpht_posts columns="2" orderby="title" order="ASC"]
				</div>
			</div>

			<!-- Breadcrumbs Shortcode Section -->
			<div class="cpht-help-section">
				<h2><?php esc_html_e('Shortcode: [cpht_breadcrumbs]', 'cpht-plugin'); ?></h2>
				<p><?php esc_html_e('This shortcode displays breadcrumb navigation for CpHT posts.', 'cpht-plugin'); ?></p>

				<h3><?php esc_html_e('Basic Usage', 'cpht-plugin'); ?></h3>
				<div class="cpht-shortcode-example">
					[cpht_breadcrumbs]
				</div>

				<p><?php esc_html_e('The breadcrumbs will display:', 'cpht-plugin'); ?></p>
				<ul>
					<li><?php esc_html_e('Home > CPhT Strong (when on the archive page)', 'cpht-plugin'); ?></li>
					<li><?php esc_html_e('Home > CPhT Strong > Post Title (when on a single post page)', 'cpht-plugin'); ?></li>
				</ul>
			</div>

			<!-- Finding IDs Section -->
			<div class="cpht-help-section">
				<h2><?php esc_html_e('Finding Post IDs', 'cpht-plugin'); ?></h2>
				<p><?php esc_html_e('To find the ID of a CpHT Post:', 'cpht-plugin'); ?></p>
				<ol>
					<li><?php esc_html_e('Go to CpHT Posts in the admin menu', 'cpht-plugin'); ?></li>
					<li><?php esc_html_e('Hover over a post\'s title', 'cpht-plugin'); ?></li>
					<li><?php esc_html_e('Look at the URL that appears in your browser\'s status bar', 'cpht-plugin'); ?></li>
					<li><?php esc_html_e('The ID is the number after "post=", e.g., post=42', 'cpht-plugin'); ?></li>
				</ol>
				<p><?php esc_html_e('Alternatively, open a post for editing and the ID will be visible in the URL.', 'cpht-plugin'); ?></p>
			</div>

			<!-- Creating Posts Section -->
			<div class="cpht-help-section">
				<h2><?php esc_html_e('Creating CpHT Posts', 'cpht-plugin'); ?></h2>
				<p><?php esc_html_e('To create a new CpHT Post:', 'cpht-plugin'); ?></p>
				<ol>
					<li><?php esc_html_e('Go to CpHT Posts > Add New in the admin menu', 'cpht-plugin'); ?></li>
					<li><?php esc_html_e('Add a title for your post', 'cpht-plugin'); ?></li>
					<li><?php esc_html_e('Set a featured image - this will be displayed in the grid view', 'cpht-plugin'); ?></li>
					<li><?php esc_html_e('Fill in the custom fields in the CpHT Post Fields section', 'cpht-plugin'); ?></li>
					<li><?php esc_html_e('Publish your post when ready', 'cpht-plugin'); ?></li>
				</ol>
				<p><?php esc_html_e('The featured image is particularly important as it is what displays in the grid view on archive pages and in the shortcode output.', 'cpht-plugin'); ?></p>
			</div>

			<!-- Need Help Section -->
			<div class="cpht-help-section">
				<h2><?php esc_html_e('Need More Help?', 'cpht-plugin'); ?></h2>
				<p><?php esc_html_e('If you need further assistance:', 'cpht-plugin'); ?></p>
				<ul>
					<li><?php esc_html_e('Contact your website administrator', 'cpht-plugin'); ?></li>
					<li><?php esc_html_e('Refer to the WordPress documentation for general shortcode usage', 'cpht-plugin'); ?></li>
				</ul>
			</div>
		</div>
		<?php
	}
}