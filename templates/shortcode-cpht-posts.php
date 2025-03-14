<?php
/**
 * Template for displaying CPHT posts in a grid layout
 *
 * @package CPHT_Plugin
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Variables available from shortcode processing:
// $posts_query - WP_Query object
// $columns - Number of columns (1-4)
// $categories - Array of category objects
// $category_filter - Currently active category filter
// $shortcode_atts - All shortcode attributes
?>

<div class="cpht-posts-wrapper">
    <!-- Filter Section -->
	<?php if (!empty($categories)) : ?>
        <div class="cpht-filter-section">
            <div class="cpht-filter-container">
                <div class="cpht-filter-label">FILTER STORIES</div>
                <div class="cpht-filter-wrapper">
                    <select id="cpht-category-filter" class="cpht-filter-select" data-nonce="<?php echo wp_create_nonce('cpht_filter_nonce'); ?>">
                        <option value="">Filter by Category</option>
						<?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($category_filter, $category->slug); ?>>
								<?php echo esc_html($category->name); ?>
                            </option>
						<?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
	<?php endif; ?>

    <!-- Content Area -->
    <div class="cpht-content-area">
		<?php if ($posts_query->have_posts()) : ?>
            <!-- Posts Grid -->
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
						'current' => max(1, get_query_var('paged')),
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
    </div>
</div>