<?php
/**
 * Single post partial template.
 *
 * @package understrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">

	<header class="entry-header">

		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

		<div class="entry-meta">

			<?php understrap_posted_on(); ?>

		</div><!-- .entry-meta -->

	</header><!-- .entry-header -->

	<?php echo get_the_post_thumbnail( $post->ID, 'large' ); ?>

	<div class="entry-content">

		<?php the_content(); ?>	

		<?php if(get_post_type() == 'eefss_warehouse_ad') {
			$quant = get_field('quantity');

			echo '<input class="form-control" placeholder="1" id="quant" type="number" min="1" max="' . $quant . '" value="" />';

			echo '<button class="btn btn-primary mb-2" id="request-item-btn" data-id="'. $post->ID .'">Request Item</button>';
			echo '<span id="response"></span>';

		}
		?>	

		<!-- <?php
		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . __( 'Pages:', 'understrap' ),
				'after'  => '</div>',
			)
		);
		?> -->

	</div><!-- .entry-content -->

	<footer class="entry-footer">

		<?php understrap_entry_footer(); ?>

	</footer><!-- .entry-footer -->

</article><!-- #post-## -->
