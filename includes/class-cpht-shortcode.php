<?php
/**
 * Shortcode functionality for CPHT Plugin.
 *
 * This class handles all shortcode registration, processing, and output
 * for displaying CPHT posts in a grid layout with filtering capabilities.
 *
 * @package CPHT_Plugin
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class CPHT_Shortcode
 *
 * Manages the shortcode that displays CPHT posts in a grid with category filtering.
 */
class CPHT_Shortcode {

	/**
	 * Shortcode tag name
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string $shortcode_tag The name of the shortcode tag.
	 */
	protected $shortcode_tag = 'cpht_posts';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		cpht_plugin_log('Initializing CPHT_Shortcode class');
	}

	/**
	 * Register shortcode and related hooks.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		cpht_plugin_log('Registering CPHT shortcode hooks');

		// Register the main shortcode
		add_shortcode($this->shortcode_tag, array($this, 'process_shortcode'));

		// Register the breadcrumbs shortcode
		add_shortcode('cpht_breadcrumbs', array($this, 'breadcrumbs_shortcode'));

		// Add query vars for pagination and filtering
		add_filter('query_vars', array($this, 'add_query_vars'));

		// Register AJAX handler
		add_action('wp_ajax_cpht_filter_posts', array($this, 'ajax_filter_posts'));
		add_action('wp_ajax_nopriv_cpht_filter_posts', array($this, 'ajax_filter_posts'));

		// Enqueue scripts and styles
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
	}

	/**
	 * Add custom query vars for pagination and filtering.
	 *
	 * @since 1.0.0
	 * @param array $vars The existing query vars.
	 * @return array Modified query vars.
	 */
	public function add_query_vars($vars) {
		$vars[] = 'cpht_category';
		$vars[] = 'cpht_paged';
		return $vars;
	}

	/**
	 * Enqueue scripts and styles for the shortcode.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script('cpht-public', CPHT_PLUGIN_ASSETS_URL . 'js/cpht-public.js', array('jquery'), CPHT_PLUGIN_VERSION, true);

		// Pass AJAX URL to script
		wp_localize_script('cpht-public', 'cpht_params', array(
			'ajax_url' => admin_url('admin-ajax.php'),
		));

		wp_enqueue_style('cpht-public', CPHT_PLUGIN_ASSETS_URL . 'css/cpht-public.css', array(), CPHT_PLUGIN_VERSION);
	}

	/**
	 * Process and render the shortcode.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered shortcode output.
	 */
	public function process_shortcode($atts) {
		cpht_plugin_log('Processing CPHT shortcode');

		// Start output buffering to capture template output
		ob_start();

		// Parse and prepare attributes
		$attributes = shortcode_atts(array(
			'columns' => 3,
			'category' => '',
			'posts_per_page' => 9,
			'orderby' => 'date',
			'order' => 'DESC',
		), $atts, $this->shortcode_tag);

		// Sanitize attributes
		$columns = absint($attributes['columns']);
		if ($columns < 1 || $columns > 4) {
			$columns = 3; // Default to 3 if out of range
		}

		$posts_per_page = absint($attributes['posts_per_page']);
		if ($posts_per_page < 1) {
			$posts_per_page = 9; // Default to 9 if invalid
		}

		// Get current page for pagination
		$paged = get_query_var('paged') ? get_query_var('paged') : 1;

		// Check for category filter from URL or shortcode attribute
		$category_filter = '';
		if (!empty($_GET['cpht_category'])) {
			$category_filter = sanitize_text_field($_GET['cpht_category']);
		} elseif (!empty($attributes['category'])) {
			$category_filter = sanitize_text_field($attributes['category']);
		}

		// Build query arguments
		$query_args = array(
			'post_type' => 'cpht_post',
			'posts_per_page' => $posts_per_page,
			'paged' => $paged,
			'orderby' => sanitize_text_field($attributes['orderby']),
			'order' => sanitize_text_field($attributes['order']),
		);

		// Add category filter if specified
		if (!empty($category_filter)) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'category',
					'field' => 'slug',
					'terms' => $category_filter,
				),
			);
		}

		// Get all categories for the filter dropdown
		$categories = get_categories(array(
			'taxonomy' => 'category',
			'hide_empty' => true,
			'object_ids' => $this->get_post_type_ids('cpht_post'),
		));

		// Run the main query
		$posts_query = new WP_Query($query_args);

		// Load the template with variables in scope
		$template_file = CPHT_PLUGIN_TEMPLATES_DIR . 'shortcode-cpht-posts.php';

		if (file_exists($template_file)) {
			// Make variables available to the template
			$template_args = array(
				'posts_query' => $posts_query,
				'columns' => $columns,
				'categories' => $categories,
				'category_filter' => $category_filter,
				'shortcode_atts' => $attributes,
			);

			// Extract variables to make them accessible in the template
			extract($template_args);

			// Include the template file
			include $template_file;
		} else {
			cpht_plugin_log('Template file not found: ' . $template_file, 'error');
			echo '<p>Error: CPHT Posts template file not found.</p>';
		}

		// Return the buffered output
		return ob_get_clean();
	}

	/**
	 * Get post IDs of a specific post type for filtering categories.
	 *
	 * @since 1.0.0
	 * @param string $post_type Post type to get IDs for.
	 * @return array Array of post IDs.
	 */
	private function get_post_type_ids($post_type) {
		$args = array(
			'post_type' => $post_type,
			'posts_per_page' => -1,
			'fields' => 'ids',
		);

		$query = new WP_Query($args);
		return $query->posts;
	}

	/**
	 * AJAX handler for post filtering.
	 *
	 * @since 1.0.0
	 */
	public function ajax_filter_posts() {
		// Make sure no output has occurred before this point
		if (ob_get_level()) {
			ob_clean();
		}

		cpht_plugin_log('AJAX filter_posts called');

		try {
			// Verify nonce
			if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cpht_filter_nonce')) {
				cpht_plugin_log('AJAX security check failed - invalid nonce');
				wp_send_json_error(array('message' => 'Security check failed'));
				die();
			}

			// Get filter parameters
			$category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
			$paged = isset($_POST['paged']) ? absint($_POST['paged']) : 1;
			$columns = isset($_POST['columns']) ? absint($_POST['columns']) : 3;

			cpht_plugin_log('AJAX parameters - category: ' . $category . ', page: ' . $paged . ', columns: ' . $columns);

			// Build query arguments
			$query_args = array(
				'post_type' => 'cpht_post',
				'posts_per_page' => get_option('posts_per_page', 9),
				'paged' => $paged,
				'orderby' => 'date',
				'order' => 'DESC',
			);

			// Add category filter ONLY if category is not empty
			if (!empty($category)) {
				cpht_plugin_log('AJAX adding category filter for: ' . $category);
				$query_args['tax_query'] = array(
					array(
						'taxonomy' => 'category',
						'field' => 'slug',
						'terms' => $category,
					),
				);
			} else {
				cpht_plugin_log('AJAX category is empty, showing all posts');
			}

			// Run the query
			$posts_query = new WP_Query($query_args);
			cpht_plugin_log('AJAX query found ' . $posts_query->found_posts . ' posts');

			// Start output buffering to capture template output
			ob_start();

			// Get all categories for the filter dropdown
			$categories = get_categories(array(
				'taxonomy' => 'category',
				'hide_empty' => true,
				'object_ids' => $this->get_post_type_ids('cpht_post'),
			));

			$template_args = array(
				'posts_query' => $posts_query,
				'columns' => $columns,
				'categories' => $categories,
				'category_filter' => $category,
				'shortcode_atts' => array(),
			);

			// Extract variables for the template
			extract($template_args);

			// Include content part of the template (posts grid and pagination only)
			?>
            <!-- Posts Grid -->
			<?php if ($posts_query->have_posts()) : ?>
                <div class="cpht-grid cpht-columns-<?php echo esc_attr($columns); ?>">
					<?php while ($posts_query->have_posts()) : $posts_query->the_post();
						// Get ACF fields
						$date = get_field('date', get_the_ID());
						$excerpt = get_field('excerpt', get_the_ID());
						?>
                        <div class="cpht-grid-item">
                            <a href="<?php the_permalink(); ?>" class="cpht-card-link">
                                <div class="cpht-card">
									<?php if (has_post_thumbnail()) : ?>
                                        <div class="cpht-card-image">
											<?php the_post_thumbnail('medium', array('class' => 'cpht-thumbnail')); ?>
                                        </div>
									<?php endif; ?>
                                    <div class="cpht-card-content">
										<?php if (!empty($date)) : ?>
                                            <div class="cpht-card-date">
												<?php echo esc_html($date); ?>
                                            </div>
										<?php endif; ?>
                                        <h3 class="cpht-card-title">
											<?php the_title(); ?>
                                        </h3>
										<?php if (!empty($excerpt)) : ?>
                                            <div class="cpht-card-excerpt">
												<?php echo esc_html($excerpt); ?>
                                            </div>
										<?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </div>
					<?php endwhile; ?>
                </div>
                <!-- Pagination Section -->
				<?php if ($posts_query->max_num_pages > 1) : ?>
                    <div class="cpht-pagination">
						<?php
						echo paginate_links(array(
							'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
							'format' => '?paged=%#%',
							'current' => $paged,
							'total' => $posts_query->max_num_pages,
							'prev_text' => '&laquo; Previous',
							'next_text' => 'Next &raquo;',
						));
						?>
                    </div>
				<?php endif; ?>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
                <div class="cpht-no-results">
                    <p><?php _e('No posts found.', 'cpht-plugin'); ?></p>
                </div>
			<?php endif; ?>
			<?php

			// Get the buffered content
			$content = ob_get_clean();
			cpht_plugin_log('AJAX generated content length: ' . strlen($content));

			// Send the response
			cpht_plugin_log('AJAX sending success response');
			wp_send_json_success(array(
				'content' => $content,
				'found_posts' => $posts_query->found_posts,
				'max_pages' => $posts_query->max_num_pages,
			));

		} catch (Exception $e) {
			cpht_plugin_log('AJAX error: ' . $e->getMessage());
			wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
		}

		// Always die at the end of an AJAX function
		die();
	}

	/**
	 * Breadcrumbs shortcode implementation
	 *
	 * @since 1.0.0
	 * @return string HTML markup for breadcrumbs
	 */
	public function breadcrumbs_shortcode() {
		ob_start();

		$home_url = home_url();
		$home_label = __('Home', 'cpht-plugin');
		$archive_url = home_url('cphtstrong');
		$archive_label = __('CPhT Strong', 'cpht-plugin');

		// Start breadcrumbs container
		echo '<div class="cpht-breadcrumbs">';

		// Home link
		echo '<a href="' . esc_url($home_url) . '">' . esc_html($home_label) . '</a>';
		echo '<span class="cpht-breadcrumb-divider">/</span>';

		// Always add the archive link, regardless of page type
		echo '<a href="' . esc_url($archive_url) . '">' . esc_html($archive_label) . '</a>';

		// For single posts, add the post title
		if (is_singular('cpht_post')) {
			echo '<span class="cpht-breadcrumb-divider">/</span>';
			echo '<span class="breadcrumb_last">' . get_the_title() . '</span>';
		}

		echo '</div>';

		return ob_get_clean();
	}
}