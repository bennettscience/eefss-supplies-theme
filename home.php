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
          
              <main class="site-main home-only" id="main" role="main">
                                
                <?php if(!is_user_logged_in()) { ?>
                  <div class="container user-message logged-out">

                  <div class="row justify-content-center">
                    <div class="col-md-8">
                      <h2>Create an account today</h2>
                      <p>By creating an account, you get access to all the beneifts of the EEF Schoolhouse Supply Store:</p>
                      <ul>
                          <li>Request always-in-stock items.</li>
                          <li>See special building services warehouse supplies available to staff members.</li>
                          <li>Post requests for community help with your special projects.</li>
                      </ul>
                      <p>Create an account today with your ECS employee email address!</p>
                    </div>
                  </div>
                  <div class="row justify-content-center"  id="acct-cta">
                    <div class="col-md-8">
                      <a class="btn btn-secondary" href="<?php echo home_url( '/register' ); ?>">Create Account</a>
                    </div>
                  </div>
                </div>
                
                <?php } else {; ?> 
                  <div class="container user-message logged-in">
                    <h1 class="col-lg-12 heading">Quick Actions</h1>

                    <div class="row justify-content-center" id="acct-cta">
                      <div class="col-lg-4">
                        <a class="btn btn-secondary" href="<?php echo home_url( '/stock-supply-request'); ?>">Request Stock Supplies</a>
                      </div>
                      <div class="col-lg-4">
                        <a class="btn btn-secondary" href="<?php echo home_url( '/staff-request' ); ?>">Post a Request</a>
                      </div>
                      <div class="col-lg-4">
                        <a class="btn btn-secondary" href="<?php echo home_url( '/eefss_warehouse_ad' ); ?>">Browse Items</a>
                    </div>
                  </div>
                </div>
                <?php }; ?>
                  <div class="container">
                    <div class="special-block row"> <!-- Specialty items -->
                      <h1 class="col-lg-12 heading">EEF Special Items</h1>
                      <p class="col-lg-12" style="text-align:center;">The items below are available for a limited time!</p>

                      <?php

                        $args = array(
                          'post_type' => 'eefss_special_ad',
                          'post_status' => 'publish',
                          'posts_per_page' => 3,
                          'tax_query' => array(
                            'relation' => 'AND',
                            array(
                              'taxonomy' => 'status',
                              'field' => 'slug',
                              'terms' => array('active'),
                              'operator' => 'IN',
                            ),
                            array(
                              'taxonomy' => 'status',
                              'field' => 'slug',
                              'terms' => array('fulfilled'),
                              'operator' => 'NOT IN',
                            )
                          )
                        );
                        
                        $query = new WP_Query($args);

                        if($query->have_posts()) {

                          while($query->have_posts()) : $query->the_post();

                        ?>

                        <div class="special col-sm-4">

                          <div class="well">
                            <div class="header">
                              <h3 class="headline"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            </div>
                            <div class="excerpt">
                              <?php the_excerpt('More...'); ?>
                            </div>

                          </div>

                        </div>

                        <?php
                          endwhile;

                        }

                        wp_reset_postdata();
                      ?>
                    </div>
                  </div>
                  <div class="container">
                  <div class="categories row"> <!-- category search -->
                      <h1 class="col-lg-12 heading">Browse by Category</h1>

                    <?php
                      $args = array(
                        'taxonomy'    => 'category',
                        'parent'      => 0,
                        'order'       => 'ASC',
                        'orderby'     => 'name',
                        'exclude'     => 1,
                        'hide_empty'  => false,
                      );

                      $categories = get_terms($args);

                      foreach($categories as $cat) {
                        echo '<div class="cat-block col-sm-4" id="cat-' . $cat->term_id . '">';
                        echo '<div class="well">';
                        echo '<h2 class="headline"><a href="' . get_category_link($cat->term_id) .'">' . $cat->name . '</a></h2>';

                        $subargs = array(
                          'taxonomy'    => 'category',
                          'parent'      => $cat->term_id,
                          'hide_empty'  => false,
                          'show_count'  => 1,
                        );

                        $subs = get_terms($subargs);

                        foreach($subs as $subcat) {
                          echo '<span id="cat-' . $cat->term_id . '-sub-' . $subcat->term_id .'"><a href="' . get_category_link($subcat->term_id) . '">' . $subcat->name . ' (' . $subcat->count . ')</a></span>';
                        }

                        echo '</div></div>';
                      }
                      wp_reset_postdata();
                    ?>
                  </div> <!-- /row -->
                  </div>

                  <div class="container">
                  <div class="about row">
                    
                    <div class="col-lg-12">
                      <?php the_content(); ?>
                    </div>
                    
                  </div>
                  </div>
                  
                  <div class="container">
                  <div class="row new-request-block"> <!-- recent teacher requests -->
                    <h1 class="col-lg-12 heading">Recent Teacher Requests</h1>
                  <?php

                       $args = array(
                          'post_type' => 'eefss_community_ad',
                          'post_status' => 'publish',
                          'posts_per_page' => 3,
                          'tax_query' => array(
                            'relation' => 'AND',
                            array(
                              'taxonomy' => 'status',
                              'field' => 'slug',
                              'terms' => array('active'),
                              'operator' => 'IN',
                            ),
                            array(
                              'taxonomy' => 'status',
                              'field' => 'slug',
                              'terms' => array('fulfilled'),
                              'operator' => 'NOT IN',
                            )
                          )
                        );

                  $query = new WP_Query( $args );

                  if($query->have_posts()) {

                    while($query->have_posts()) : $query->the_post();

                  ?>

                  <div class="new-request col-sm-4">

                    <div class="well">

                      <div class="header">
                        <h3 class="headline"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        <span class="author"><?php echo get_the_author_meta('display_name'); ?></span>
                      </div>
                      <div class="excerpt">
                        <?php the_excerpt('More...'); ?>
                      </div>

                    </div>

                  </div>

                  <?php
                    endwhile;

                  }

                  wp_reset_postdata();
                  ?>
                  </div>
                </div>

				</main><!-- #main -->

			</div><!-- #primary -->

    </div><!-- .row end -->


	</div>

</div>
<?php get_footer(); ?>
