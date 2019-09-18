<?php
/**
 * Search results partial template.
 *
 * @package understrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">

	<header class="entry-header">

		<?php
		the_title(
			sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ),
			'</a></h2>'
		);
		?>

			<div class="entry-meta">

				<span class="type">
					<?php 
						if(get_post_type() == 'eefss_community_ad') {
							echo '<span class="text">Community</span>';
							understrap_posted_on();
						} else if(get_post_type() == 'eefss_warehouse_ad') {
							echo '<span class="text">Warehouse</span>';
						} else if(get_post_type() == 'eefss_special_ad') {
							echo '<span class="text">EEF Special</span>';
						} else {
							echo '<span></span>';
						}
					?>
				
				</span>

			</div><!-- .entry-meta -->

	</header><!-- .entry-header -->

	<div class="entry-summary">

		<?php the_excerpt(); ?>

	</div><!-- .entry-summary -->

	<footer class="entry-footer">

		<?php understrap_entry_footer(); ?>

	</footer><!-- .entry-footer -->

</article><!-- #post-## -->
