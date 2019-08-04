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

	<div class="row search justify-content-center">
                    
		<div class="col-lg-8">
		
			<?php get_template_part( 'searchform' ); ?>
		
		</div>
		
	

	<div class="container user-message logged-in">
                  <div class="row justify-content-center" id="acct-cta">
                    <div class="col-lg-4">
                      <a class="btn btn-secondary" href="<?php echo home_url( '/stock-supply-request'); ?>">Request Stock Supplies</a>
                    </div>
                    <div class="col-lg-4">
                      <a class="btn btn-secondary" href="<?php echo home_url( '/staff-request' ); ?>">Post a Request</a>
                    </div>
                  </div>
				</div>
				</div>

<?php endif; ?>
