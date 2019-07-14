<?php
/**
 * Understrap functions and definitions
 *
 * @package understrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$understrap_includes = array(
	'/theme-settings.php',                  // Initialize theme default settings.
	'/setup.php',                           // Theme setup and custom theme supports.
	'/widgets.php',                         // Register widget area.
	'/enqueue.php',                         // Enqueue scripts and styles.
	'/template-tags.php',                   // Custom template tags for this theme.
	'/pagination.php',                      // Custom pagination for this theme.
	'/hooks.php',                           // Custom hooks.
	'/extras.php',                          // Custom functions that act independently of the theme templates.
	'/customizer.php',                      // Customizer additions.
	'/custom-comments.php',                 // Custom Comments file.
	'/jetpack.php',                         // Load Jetpack compatibility file.
	'/class-wp-bootstrap-navwalker.php',    // Load custom WordPress nav walker.
	'/editor.php',                          // Load Editor functions.
	'/deprecated.php',                      // Load deprecated functions.
);

foreach ( $understrap_includes as $file ) {
	$filepath = locate_template( 'inc' . $file );
	if ( ! $filepath ) {
		trigger_error( sprintf( 'Error locating /inc%s for inclusion', $file ), E_USER_ERROR );
	}
	require_once $filepath;
}

function eefss_register_request_scripts() {
wp_register_script( 'request-handler', get_template_directory_uri() . '/js/request-handler.js' );
wp_enqueue_script( 'request-handler', array('jquery'), '1.0.0', true );
   $local_arr = array(
        'ajaxurl'   => admin_url( 'admin-ajax.php' ),
		'ajax_nonce'  => wp_create_nonce( 'request_item' ),
		'user_id' => get_current_user_id(),
    );
    // Assign that data to our script as an JS object
    wp_localize_script( 'request-handler', 'ajax_object', $local_arr );
} // end custom_register_admin_scripts
add_action( 'wp_enqueue_scripts', 'eefss_register_request_scripts' );

/** AJAX handler for requesting warehouse items */
add_action( 'wp_ajax_request_item', 'eefss_request_item_callback');
add_action( 'wp_ajax_nopriv_request_item', 'eefss_request_item_callback' );
function eefss_request_item_callback() {

	global $wpdb;

	$request = $_POST['action'];
	$user_id = $_POST['user_id'];
	$post_id = $_POST['post_id'];

	$user = get_user_by('id', intval($user_id));

	$date = new DateTime();

	// echo json_encode(array("success" => true, "result" => $user->display_name));

	if($request === 'request_item') {
		// get the ACF for the post
		update_field('requested', true, intval($post_id));
		update_field('requested_by', $user->user_email, intval($post_id));
		update_field('requested_on', $date->format('m-d-Y'), intval($post_id));

		$update = array(
			'ID' => intval($post_id),
			'post_status' => 'requested',
		);

		wp_update_post($update);
	}

	wp_die();
}


/** Custom user registration form */
add_action('register_form', 'eefss_register_form');
function eefss_register_form() {
	
	$allowed = array(
		'Beardsley',
		'Beck',
		'Bristol',
	);

	$building = ! empty( $_POST['building'] ) ? strval( $_POST['building'] ) : '';

	?>
	<p>
		<label for="building"><?php esc_html_e( 'Building', 'crf' ) ?><br/>
			<select

			       id="building"
			       name="building"
			       value="<?php echo esc_attr( $building ); ?>"
			       class="input"
			>
			<?php 
				foreach($allowed as $the_building) {
					echo '<option value='. $the_building .'>' . $the_building .'</option>';
				}
			?>
			</select>
		</label>
	</p>
	<?php
}

/** Custom registration errors */
add_filter( 'registration_errors', 'eefss_registration_errors', 10, 3 );
function eefss_registration_errors( $errors, $sanitized_user_login, $user_email ) {

	$allowed = array(
		'Beardsley',
		'Beck',
		'Bristol',
	);

	// Make sure a building is selected.
	if ( empty( $_POST['building'] ) ) {
		$errors->add( 'building_error', __( '<strong>ERROR</strong>: Please enter a valid building name.', 'crf' ) );
	}

	// Validate the building input.
	if ( ! empty( $_POST['building'] ) && !in_array(strval( $_POST['building'] ), $allowed, true ) ) {
		$errors->add( 'building_error', __( '<strong>ERROR</strong>: The building you submitted is not allowed. Please try again.', 'crf' ) );
	}

	// Validate the submitted email address.
	if ( ! empty( $_POST['user_email'] ) && ! preg_match_all('/(\@elkhart\.k12\.in\.us)/i', $_POST['user_email'])) {
		$errors->add('user_email_error', __('<strong>ERROR</strong>: Please use a valid ECS email address to register.', 'crf') );
	}

	return $errors;
}

add_action( 'user_register', 'eefss_user_register' );
function eefss_user_register( $user_id ) {

	// TODO: Set the username value

	$allowed = array(
		'Beardsley',
		'Beck',
		'Bristol',
	);

	// Make sure a building is submitted to the DB.
	if ( empty( $_POST['building'] ) ) {
		$errors->add( 'building_error', __( '<strong>ERROR</strong>: Please enter a valid building name.', 'crf' ) );
	}

	// Validate the building submission
	if ( ! empty( $_POST['building'] ) && in_array(strval( $_POST['building'] ), $allowed, true) ) {
		update_user_meta( $user_id, 'building', strval( $_POST['building'] ) );
	}

}

add_action( 'init', 'eefss_custom_requested_status' );
function eefss_custom_requested_status() {
	register_post_status( 'requested', array(
		'label'                     => _x( 'Requested', 'eefss_warehouse_ad' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Requested (%s)', 'Requested (%s)' ),
	) );
}

add_action( 'show_user_profile', 'eefss_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'eefss_show_extra_profile_fields' );
function eefss_show_extra_profile_fields( $user ) {
	// TODO: Convert location display into dropdown in case a user needs to update the field.

	$allowed = array(
		'Beardsley',
		'Beck',
		'Bristol',
	);

	?>
	<h3><?php esc_html_e( 'Personal Information', 'crf' ); ?></h3>

	<table class="form-table">
		<tr>
			<th><label for="building"><?php esc_html_e( 'Building', 'crf' ); ?></label></th>
			<td><?php echo esc_html( get_the_author_meta( 'building', $user->ID ) ); ?></td>
		</tr>
	</table>
	<?php
}

/** Create the custom user roles, defining specific
 *  capabilities for each.
 */

add_action( 'init', 'eefss_add_custom_roles' );
function eefss_add_custom_roles() {

	add_role('eefss_manager', 'EFF Manager', array(
		'read' => true,
		'edit_posts' => false,
		'publish_posts' => false,
		'delete_posts' => false,
		'upload_files' => true,
	));

	add_role('eefss_teacher', 'Teacher', array(
		'read' => true,
		'edit_posts' => false,
		'publish_posts' => false,
		'delete_posts' => false,
		'upload_files' => true,
	));
}

add_action('admin_menu', 'remove_admin_menu_items');
/** Remove unnecessary menu items from the dashboard.
 *  Thanks to Tom Woodward for the snippets here:
 *  http://bionicteaching.com/minimal-wordpress/
 */

function remove_admin_menu_items() {
	if( current_user_can( 'administrator' ) ) { }
		else {  
		$remove_menu_items = array(
			__('Posts'),
			__('Media'),
			__('Pages'),
			__('Appearance'),
			__('Plugins'),
			__('Tools'),
			__('Settings'),
			__('Comments'),
			__('Custom Fields')
		);

		global $menu;
		end ($menu);
		while (prev($menu)){
			$item = explode(' ',$menu[key($menu)][0]);
			if(in_array($item[0] != NULL?$item[0]:"" , $remove_menu_items)){
				unset($menu[key($menu)]);
			}
		}
	}
}

/** Hide ads from other authors */
function posts_for_current_author($query) {
    global $pagenow;
 
    if( 'edit.php' != $pagenow || !$query->is_admin )
        return $query;
 
    if( !current_user_can( 'manage_options' ) ) {
        global $user_ID;
        $query->set('author', $user_ID );
    }
    return $query;
}
add_filter('pre_get_posts', 'posts_for_current_author');

add_action('init', 'eefss_register_community_ads');
function eefss_register_community_ads() {
	$labels = array(
		'name' => __('Community Ads', 'en'),
		'singular_name' => __('Community Ad'),
		'add_new' => __('New Listing'),
		'add_new_item' => __('New Listing'),
		'edit_item' => __('Edit Item Details'),
		'new_item' => __('New Listing'),
		'view_item' => __('View Item'),
		'search_items' => __('Search Listings'),
		'not_found' => __('No Items Found'),
		'not_found_in_trash' => __('No items found in trash')
	);

	$args = array(
		'label' => __('eefss_community_ad', 'eefss_community_ads'),
		'description' => __('Community Ads', 'eefss_community_ads'),
		'labels' => $labels,
		'supports' => array(
            'title',
            'editor',
            'revisions',
			'custom-fields',
			'thumbnail',
			'author',
		),
		'hierarchical' => false,
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'rewrite' => true,
		'has_archive' => true,
		'capability_type' => array('eefss_community_ad', 'eefss_community_ads'),
		'map_meta_cap' => true,
	);
    register_post_type('eefss_community_ad', $args);
}

add_action('init', 'eefss_register_warehouse_ads');
function eefss_register_warehouse_ads() {
/**
     * Register a custom post types
     * EEF Ad - posted by a moderator
     */

	$labels = array(
		'name' => __('Warehouse Ads', 'en'),
		'singular_name' => __('Warehouse Ad'),
		'add_new' => __('New Listing'),
		'add_new_item' => __('New Listing'),
		'edit_item' => __('Edit Item Details'),
		'new_item' => __('New Listing'),
		'view_item' => __('View Item'),
		'search_items' => __('Search Listings'),
		'not_found' => __('No Items Found'),
		'not_found_in_trash' => __('No items found in trash')
	);

	$args = array(
		'label' => __('eefss_warehouse_ad', 'eefss_warehouse_ads'),
		'description' => __('Listings', 'eefss_warehouse_ads'),
		'labels' => $labels,
		'supports' => array(
            'title',
            'editor',
            'revisions',
			'custom-fields',
			'thumbnail',
		),
		'hierarchical' => false,
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'has_archive' => true,
		'rewrite' => true,
		'capability_type' => array('eefss_warehouse_ad', 'eefss_warehouse_ads'),
		'map_meta_cap' => true,
	);
    register_post_type('eefss_warehouse_ad', $args);
}

add_action('init', 'eefss_add_role_caps');
function eefss_add_role_caps() {

		$role = get_role('eefss_manager');

		$role->add_cap( 'read' );
		$role->add_cap( 'read_eefss_warehouse_ad' );
		$role->add_cap( 'read_eefss_community_ad' );
		$role->add_cap( 'edit_eefss_warehouse_ad' );
		$role->add_cap( 'edit_eefss_community_ad' );
		$role->add_cap( 'edit_eefss_warehouse_ads' );
		$role->add_cap( 'edit_eefss_community_ads' );
		$role->add_cap( 'edit_others_eefss_warehouse_ads' );
		$role->add_cap( 'edit_others_eefss_community_ads' );
		$role->add_cap( 'edit_published_eefss_warehouse_ads' );
		$role->add_cap( 'edit_published_eefss_community_ads' );
		$role->add_cap( 'publish_eefss_warehouse_ads' );
		$role->add_cap( 'publish_eefss_community_ads' );
		$role->add_cap( 'delete_eefss_warehouse_ads' );
		$role->add_cap( 'delete_others_eefss_warehouse_ads' );
		$role->add_cap( 'delete_others_eefss_community_ads' );
		$role->add_cap( 'delete_published_eefss_warehouse_ads' );
		$role->add_cap( 'delete_published_eefss_community_ads' );

}

add_action('init', 'eefss_add_teacher_caps');
function eefss_add_teacher_caps() {

		$role = get_role('eefss_teacher');

		$role->add_cap( 'read' );
		$role->add_cap( 'read_eefss_community_ad' );
		$role->add_cap( 'edit_eefss_community_ad' );
		$role->add_cap( 'edit_eefss_community_ads' );
		$role->add_cap( 'edit_published_eefss_community_ads' );

}

function eefss_manager_dash_meta_display($data) {

	wp_nonce_field(basename(__FILE__), "meta-box-nonce");

	// Get the number of warehouse posts with requested and !completed
	$warehouse_query = new WP_Query(array(
		'post_type' => 'eefss_warehouse_ad',
		'post_status' => 'requested',
		'meta_query' => array(
			'relation' => "AND",
			array(
				'key' => 'requested',
				'value' => 1,  // True/False ACF field stored as int
			),
			array(
				'key' => 'complete',
				'value' => 0,
			),
		),
	));

	// Display the number of pending community requests	
	?>
		<table>
			<thead>
				<th>Active Warehouse Ads</th>
				<th>Pending Warehouse Requests</th>
				<th>Community Ads</th>
				<th>Pending Community Posts</th>
			</thead>
			<tbody style="font-size:32px;">
			<td><?php echo wp_count_posts('eefss_warehouse_ad')->publish; ?></td>
			<td>
				<a href="<?php echo home_url('wp-admin/edit.php?post_type=eefss_warehouse_ad&post_status=requested'); ?>"><?php echo $warehouse_query->found_posts; ?></a>
			</td>
			<td><?php echo wp_count_posts('eefss_community_ad')->publish; ?></td>
			<td><a href="<?php echo home_url('wp-admin/edit.php?post_status=pending&post_type=eefss_community_ad'); ?>"><?php echo wp_count_posts('eefss_community_ad')->pending; ?></a></td>
			</tbody>
			</table>
	<?php

}

function eefss_teacher_dash_meta_display($data) {

	$query = new WP_Query(array(
		'post_type' => 'eefss_community_ad',
		'post_status' => 'publish',
		'author' => get_current_user_id(),
		'orderby' => 'post_date',
		'order' => 'ASC',
	));

	?>

	<table>
		<thead>
			<th>Title</th>
			<th>Posted On</th>
		</thead>
		<tbody>

		<?php while ( $query->have_posts() ) : $query->the_post(); ?>
			<tr>
				<td><?php the_title(); ?></td>
				<td><?php the_time( get_option( date_format ) ); ?></td>
			</tr>
		
		<?php endwhile; ?>

		</tbody>
	</table>

	<?php
	wp_reset_query();
}

/** Add dashboard metaboxes for users */
function eefss_dashboard_meta_boxes() {
	if(!current_user_can('eefss_teacher')) {
		add_meta_box('eefss-ad-stats', __('Site Stats'), 'eefss_manager_dash_meta_display', 'dashboard', 'side', 'high');
	} else {
		add_meta_box('user-community-ads', __('Your Ads'), 'eefss_teacher_dash_meta_display', 'dashboard','normal');
	}
}
add_action( 'wp_dashboard_setup', 'eefss_dashboard_meta_boxes');


add_filter( 'manage_posts_columns', 'eefss_posts_column_views' );
add_action( 'manage_posts_custom_column', 'eefss_posts_custom_column_views' );
add_action( 'wp_dashboard_setup', 'eefss_get_post_view');

function eefss_get_post_view() {
    $count = get_post_meta( get_the_ID(), 'post_views_count', true );
    return $count;
}
function eefss_set_post_view() {
    $key = 'post_views_count';
    $post_id = get_the_ID();
    $count = (int) get_post_meta( $post_id, $key, true );
    $count++;
    update_post_meta( $post_id, $key, $count );
}
function eefss_posts_column_views( $columns ) {
	$columns['post_views'] = 'Views';
	$columns['requested_by'] = 'Requested By';
    return $columns;
}
function eefss_posts_custom_column_views( $column ) {
    if ( $column === 'post_views') {
        echo eefss_get_post_view();
	}
	if ( $column === 'requested_by' ) {
		global $post;
		$user_email = get_field('requested_by', $post->ID);

		echo $user_email;
	}
}

add_action('quick_edit_custom_box',  'eefss_custom_quick_edit', 10, 2);
 
function eefss_custom_quick_edit($column_name, $post_type) {
    if ($column_name != 'widget_set') return;
    ?>
    <fieldset class="inline-edit-col-left">
    <div class="inline-edit-col">
        <span class="title">Widget Set</span>
        <input type="hidden" name="shiba_widget_set_noncename" id="shiba_widget_set_noncename" value="" />
        <?php // Get all widget sets
            $widget_sets = get_posts( array( 'post_type' => 'widget_set',
                            'numberposts' => -1,
                            'post_status' => 'publish') );
        ?>
        <select name='post_widget_set' id='post_widget_set'>
            <option class='widget-option' value='0'>None</option>
            <?php 
            foreach ($widget_sets as $widget_set) {
                echo "<option class='widget-option' value='{$widget_set->ID}'>{$widget_set->post_title}</option>\n";
            }
                ?>
        </select>
    </div>
    </fieldset>
    <?php
}