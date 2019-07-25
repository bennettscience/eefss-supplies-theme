<?php
/**
 * Template Name: Home
 *
 * Home page template
 *
 * @package understrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();
$container = get_theme_mod( 'understrap_container_type' );
?>

<?php if ( is_front_page() ) : ?>
  <?php get_template_part( 'global-templates/hero' ); ?>
<?php endif; ?>


<div class="wrapper" id="full-width-page-wrapper">

	<div class="<?php echo esc_attr( $container ); ?>" id="content">

		<div class="row">

			<div class="col-md-12 content-area" id="primary">

                <main class="site-main" id="main" role="main">

                    <?php get_template_part( 'searchform' ); ?>

                    <?php
                      $args = array(
                        'taxonomy' => 'category',
                        'parent' => 0,
                        'order'               => 'ASC',
                        'orderby'             => 'name',
                        'hide_empty' => false,
                      );

                      $categories = get_terms($args);

                      foreach($categories as $cat) {
                        echo '<div id="cat-' . $cat->term_id . '">' . $cat->name;

                        $subargs = array(
                          'taxonomy' => 'category',
                          'parent' => $cat->term_id,
                          'hide_empty' => false,
                        );

                        $subs = get_terms($subargs);

                        foreach($subs as $subcat) {
                          echo '<span id="cat-' . $cat->term_id . '-sub-' . $subcat->term_id .'">' . $subcat->name . '</span>';
                        }

                        echo '</div>';
                      }
                    ?>

				</main><!-- #main -->

			</div><!-- #primary -->

		</div><!-- .row end -->

	</div><!-- #content -->

</div><!-- #full-width-page-wrapper -->

<?php get_footer(); ?>
