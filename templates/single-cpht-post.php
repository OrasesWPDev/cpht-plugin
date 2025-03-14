<?php
/**
 * The template for displaying single CpHT posts
 *
 * @package CPHT_Plugin
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

get_header();
?>

	<main id="main" class="<?php echo esc_attr(function_exists('flatsome_main_classes') ? flatsome_main_classes() : 'site-main'); ?>">
		<!-- Header Block (full width) -->
		<div class="cpht-section-wrapper cpht-post-header">
			<?php echo do_shortcode('[block id="single-cpht-story-header"]'); ?>
		</div>

		<!-- Content area (site width) -->
		<div class="row cpht-single-post">
			<div class="large-12 col">
				<article id="cpht-post-<?php the_ID(); ?>" <?php post_class('cpht-post'); ?>>
					<?php while (have_posts()) : the_post(); ?>

						<!-- Excerpt Section -->
						<?php if (function_exists('get_field') && $excerpt = get_field('excerpt')) : ?>
							<div class="cpht-post-excerpt-section">
								<div class="cpht-post-excerpt">
									<span class="cpht-label">Excerpt:</span> <?php echo esc_html($excerpt); ?>
								</div>
							</div>
						<?php endif; ?>

						<!-- Date Section -->
						<?php if (function_exists('get_field') && $date = get_field('date')) : ?>
							<div class="cpht-post-date-section">
								<div class="cpht-post-date">
									<span class="cpht-label">Date:</span> <?php echo esc_html($date); ?>
								</div>
							</div>
						<?php endif; ?>

						<!-- Content Sections -->
						<?php if (function_exists('get_field') && have_rows('content_section')) : ?>
							<div class="cpht-post-content-section">
								<?php while (have_rows('content_section')) : the_row(); ?>
									<?php if ($post_content = get_sub_field('post_content')) : ?>
										<div class="cpht-post-content-row">
											<?php
											// Process content to add special classes to blockquotes
											$processed_content = preg_replace(
												'/<blockquote>/i',
												'<blockquote class="cpht-blockquote">',
												$post_content
											);
											echo $processed_content;
											?>
										</div>
									<?php endif; ?>
								<?php endwhile; ?>
							</div>
						<?php endif; ?>

                        <!-- Post Navigation -->
                        <nav class="cpht-post-navigation container">
                            <div class="cpht-nav-links">
                                <div class="cpht-nav-button cpht-nav-previous">
									<?php if (get_previous_post()) : ?>
										<?php previous_post_link('%link', 'See Previous'); ?>
									<?php endif; ?>
                                </div>

                                <div class="cpht-nav-button cpht-nav-all">
                                    <a href="<?php echo esc_url(home_url('/cphtstrong/')); ?>">See All</a>
                                </div>

                                <div class="cpht-nav-button cpht-nav-next">
									<?php if (get_next_post()) : ?>
										<?php next_post_link('%link', 'See Next'); ?>
									<?php endif; ?>
                                </div>
                            </div>
                        </nav>

					<?php endwhile; ?>
				</article>
			</div>
		</div>
	</main>

<?php get_footer(); ?>