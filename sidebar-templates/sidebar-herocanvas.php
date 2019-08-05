<?php
/**
 * Sidebar - hero canvas setup.
 *
 * @package understrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<?php if ( is_active_sidebar( 'herocanvas' ) ) : ?>

	<!-- ******************* The Hero Canvas Widget Area ******************* -->

	<?php dynamic_sidebar( 'herocanvas' ); ?>

	<div class="container">
		
		<div class="search">
		
			<div class="row justify-content-center">
							
				<div class="col-lg-8">
				
					<?php get_template_part( 'searchform' ); ?>
				
				</div>

			</div>	
		
		</div>

	</div>

<?php endif; ?>
