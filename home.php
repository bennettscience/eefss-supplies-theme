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
                          'child_of'            => 0,
                          'current_category'    => 0,
                          'depth'               => 0,
                          'echo'                => 1,
                          'exclude'             => '',
                          'exclude_tree'        => '',
                          'feed'                => '',
                          'feed_image'          => '',
                          'feed_type'           => '',
                          'hide_empty'          => 0,
                          'hide_title_if_empty' => false,
                          'hierarchical'        => true,
                          'order'               => 'ASC',
                          'orderby'             => 'name',
                          'separator'           => '<br />',
                          'show_count'          => 0,
                          'show_option_all'     => '',
                          'show_option_none'    => __( 'No categories' ),
                          'style'               => 'list',
                          'taxonomy'            => 'category',
                          'title_li'            => __( 'Categories' ),
                          'use_desc_for_title'  => 1,
                        );
                        
                        $categories = wp_list_categories($args);

                        foreach($categories as $cat) {
                            echo '<div class="col-md-4"><a href="' . get_category_link($category->term_id) . '">' . $category->name . '</a></div>';
                        }
                    ?>

				</main><!-- #main -->

			</div><!-- #primary -->

		</div><!-- .row end -->

	</div><!-- #content -->

</div><!-- #full-width-page-wrapper -->

<?php get_footer(); ?>
