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

/**************************/
/** ITEM REQUEST SCRIPTS **/
/**************************/

/** Register AJAX scripts */
function eefss_register_request_scripts() {

	wp_register_script( 'request-handler', get_template_directory_uri() . '/js/eefss.js' );
	wp_enqueue_script( 'request-handler', array('jquery'), '1.0.0', true );

    $local_arr = array(
        'ajaxurl'   => admin_url( 'admin-ajax.php' ),
		'ajax_nonce'  => wp_create_nonce( 'request_item' ),
		'user_id' => get_current_user_id(),
	);
	
    // Assign that data to our script as an JS object
    wp_localize_script( 'request-handler', 'ajax_object', $local_arr );
} 
add_action( 'wp_enqueue_scripts', 'eefss_register_request_scripts' );

/** AJAX handler for requesting warehouse items */
add_action( 'wp_ajax_request_item', 'eefss_request_item_callback');
add_action( 'wp_ajax_nopriv_request_item', 'eefss_request_item_callback' );
function eefss_request_item_callback() {

	// access the database
	global $wpdb;

	// convert vars passed through AJAX to PHP variables
	$request = $_POST['action'];
	$user_id = $_POST['user_id'];
	$post_id = $_POST['post_id'];
	$quant = $_POST['quant'];

	// Get the user
	$user = get_user_by('id', intval($user_id));

        // Get the post
        $post = get_post( $post_id );

	// Set a date
	$date = new DateTime();

	// Check the action key and run the appropriate function.

	$item_title = get_the_title( $post_id );
	$item_lot = get_field( 'lot', $post_id, true );
	$building = get_user_meta($user->ID, 'building', true);

	if($request === 'request_item') {

		// Subtract the requested quantity from the total available
		$available = get_field('quantity', $post_id, true);

		$available = intval($available) - intval($quant);

		if(intval($available) < 0) {
			// If the user requested too many items somehow, return an error.

			header('HTTP/1.1 500 Internal Error');
        	header('Content-Type: application/json; charset=UTF-8');
			wp_die(json_encode(array('message' => '<span class="error">Error</span> - You requested more items than were available. Please update your quantity and try again.', 'value' => $available)));
			
		} elseif(intval($available) == 0) {

			$row = array(
				'requested_by' => $user->user_email,
				'requested_quantity' => $quant,
				'requested_date' => $date->format('m-d-Y'),
				'completed' => 0,
				'completed_date' => '',
			);

			add_row('requests', $row, intval($post_id));

			// If there are none left, set `fulfilled` to true
			update_field('quantity', $available, intval($post_id));

			// Update the post taxonomy to `fulfilled` and remove it from the query results
			wp_set_object_terms(intval($post_id), 'fulfilled', 'status' );
			wp_remove_object_terms( intval($post_id), 'active', 'status' );

		} else {
			// There is more available. Set the new quantity and return.
			$row = array(
				'requester_id' => $user->ID,
				'requested_by' => $user->user_email,
				'requested_quantity' => $quant,
				'requested_date' => $date->format('m-d-Y'),
				'completed' => 0,
				'completed_date' => '',
			);

			add_row('requests', $row, intval($post_id));

			// There are some left, so subtract and return an updated quantity
			update_field('quantity', intval($available), intval($post_id));

			// Mark the post as `requested` to filter into the admin dashboard
			wp_set_object_terms( intval($post_id), 'request_pending', 'status' );
			wp_remove_object_terms( intval($post_id), 'active', 'status' );
		}
	}

        if(get_post_type($post) == 'eefss_special_ad') {
		$emailTo = 'ashley@elkhartedfoundation.org,stephanie@elkhartedfoundation.org';
	} else if (get_post_type($post) == 'eefss_warehouse_ad') {
		$emailTo = 'rcrum@elkhart.k12.in.us,dpaulson@elkhart.k12.in.us,bdrehmel@elkhart.k12.in.us';
	}

	// TODO: Update recipient email address
	$subject = 'New warehouse request';
	$body = eefss_warehouse_request_body($user, $building, $item_title, $quant, $item_lot);
	wp_mail($emailTo, $subject, $body);

	wp_die(json_encode(array('message' => '<span class="success">Success!</span> Your request has been filed.', 'remaining' => $available)));
}

add_filter('wp_mail_content_type', 'eefss_set_email_content_type');
function eefss_set_email_content_type() {
    return 'text/html';
}

// Store the Gravity Form complete submission in the correct post meta
add_action('gform_after_submission_3', 'eefss_update_community_donors', 10, 2);
function eefss_update_community_donors( $entry, $form ) {

	$post_id = intval(rgar($entry, 13));

	// error_log($post_id);

	$post = get_post($post_id);

	$row = array(
		'contact_date' => date('m/d/Y'),
		'contact_name' => rgar($entry, 9),
		'contact_email' => rgar($entry, 6),
		'contact_phone' => rgar($entry, 10),
		);
	
	add_row('contacts', $row, intval($post_id));

}

add_action( 'query_vars', 'eefss_add_query_vars' );
function eefss_add_query_vars( $vars ) {
	$vars[] = 'request';
	$vars[] = 'auth';
	$vars[] = 'userid';
	$vars[] = 'postid';
	$vars[] = 'item';

	return $vars;
}

// template the email
function eefss_warehouse_request_body($user, $building, $item, $quant, $lot) {
	$body = '<p>Requester: <b>' . $user->first_name . ' ' . $user->last_name . '</b></p>';
	$body .= '<p>Requester email: <b>' . $user->user_email . '</b></p>';
	$body .= '<p>Building: <b>' . $building . '</b></p>';
	$body .= '<p>Quantity: <b>' . $quant . '</b></p>';
	$body .= '<p>Item: <b>' . $item . "</b></p>";
	$body .= '<p>Lot #: <b>' . $lot . '</b></p>';

        // error_log("Sent to " . $body);
	return $body;
}

// Create the body of the tickle email to users
function eefss_email_template($type, $postid, $title, $userid) {

	$template['subject'] = "Schoolhouse Supply Store Check In";
	
	// Send a body string based on the type
	if($type === 'warehouse') {
		
		$template['body'] = 'Thanks for placing a request with the EEF Schoolhouse Supply Store. You recently made the following request: ';
		$template['body'] .= '<br/><br />' . $title . '<br/><br />';

		// Send the post ID and user ID as query strings
		$template['body'] .= '<b>Please take a moment to <a href="https://supply-store.onecityonemission.org/order-update/?userid='. $userid . '&postid=' . $postid .'&item=' . urlencode($title) .'">submit this form</a> to update the status of your request.</b>';
		$template['body'] .= '<br /><br />Thank you for using the Schoolhouse Supply Store!';

	} else if($type === 'community') {

		$template['body'] = 'Thank you for posting to the EEF Schoolhouse Supply Store Community! About a week ago, you were contacted by a member of our community with an offer to help with your project. We want to check in and make sure you were able to make arrangements for supplies, volunteer time, or financial support.';

		// Send the post ID and user ID as query strings
		$template['body'] .= '<br /><br /><b>Please take a moment to <a href="https://supply-store.onecityonemission.org/community-request-update/?userid='. $userid . '&postid=' . $postid .'&item=' . urlencode($title) .'">submit this form</a> to update the status of your request.</b>';
		$template['body'] .= '<br /><br />Thank you for using the Schoolhouse Supply Store!';

	}

	return $template;
}

/***********************/
/** USER REGISTRATION **/
/***********************/
add_action( 'gform_after_submission_4', 'eefss_save_user_building', 5, 2);
function eefss_save_user_building($entry, $form) {
	
	$user_id = rgar($entry, 5);
	$building = rgar($entry, 6);

	$allowed = get_buildings();

	if ( is_allowed_building(strval( $building ) ) ) {
		update_user_meta( intval($user_id), 'building', strval( $building ) );
	}

}

/** Redirect to index on logout **/
add_action( 'wp_logout', 'eefss_redirect_user_on_logout');
function eefss_redirect_user_on_logout() {
	wp_redirect( home_url() );
	exit();
}

/** Create appropriate nav menus for login conditions **/
add_action('admin_init', 'eefss_main_nav_menus',20,2);
function eefss_main_nav_menus() {
	$logged_in_menu = 'logged-in';
	$logged_out_menu = 'logged-out';

	$logged_in_exists = wp_get_nav_menu_object( $logged_in_menu );
	$logged_out_exists = wp_get_nav_menu_object( $logged_out_menu );

	// If it doesn't exist, let's create it.
	if( !$logged_in_exists) {
		$menu_id = wp_create_nav_menu($logged_in_menu);

		// Set up default menu items
		wp_update_nav_menu_item($menu_id, 0, array(
			'menu-item-title' =>  __('Home'),
			'menu-item-classes' => 'home',
			'menu-item-url' => home_url( '/' ), 
			'menu-item-status' => 'publish'));

		wp_update_nav_menu_item($menu_id, 0, array(
			'menu-item-title' =>  __('Community'),
			'menu-item-url' => home_url( '/eefss_community_ad/' ), 
			'menu-item-status' => 'publish'));

		wp_update_nav_menu_item($menu_id, 0, array(
			'menu-item-title' =>  __('Warehouse'),
			'menu-item-url' => home_url( '/eefss_warehouse_ad/' ), 
			'menu-item-status' => 'publish'));

		wp_update_nav_menu_item($menu_id, 0, array(
			'menu-item-title' =>  __('Logout'),
			'menu-item-url' => wp_logout_url('/'), 
			'menu-item-status' => 'publish'));
	}

	if( !$logged_out_exists ) {
		$menu_id = wp_create_nav_menu($logged_out_menu);

		wp_update_nav_menu_item($menu_id, 0, array(
			'menu-item-title' =>  __('Community'),
			'menu-item-url' => home_url( '/eefss_community_ad/' ), 
			'menu-item-status' => 'publish'));

		wp_update_nav_menu_item($menu_id, 0, array(
			'menu-item-title' =>  __('Login'),
			'menu-item-url' => wp_login_url('index.php'),
			'menu-item-status' => 'publish'));
		
		wp_update_nav_menu_item($menu_id, 0, array(
			'menu-item-title' =>  __('Register'),
			'menu-item-url' => home_url( '/wp-login.php?action=register' ), 
			'menu-item-status' => 'publish'));
	}
}

/** Check the current user's login status and return the correct menu **/
add_filter('wp_nav_menu_args', 'eefss_user_login_check');
function eefss_user_login_check($args = '') {
	if( is_user_logged_in() ) { 
    	$args['menu'] = 'logged-in';
	} else { 
    	$args['menu'] = 'logged-out';
	} 
    return $args;
}

function get_buildings() {

	$buildings = array(
		'Administration',
		'Beardsley',
		'Beck',
		'Bristol',
		'Central',
		'Cleveland',
		'Daly',
		'Eastwood',
		'Elkhart Academy',
		'Feeser',
		'Hawthorne',
		'Memorial',
		'Monger',
		'North Side',
		'Osolo',
		'Pierre Moran',
		'Pinewood',
		'Riverview',
		'Roosevelt',
		'West Side',
		'Woodland',
	);

	return $buildings;
}

function is_allowed_building($loc) {
	
	$allowed = get_buildings();

	if( in_array($loc, $allowed) ) {
		return true;
	} else {
		return false;
	}

}

/** Show the user's location as a field in the Profile page **/
// TODO: Convert location display into dropdown in case a user needs to update the field.
add_action( 'show_user_profile', 'eefss_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'eefss_show_extra_profile_fields' );
function eefss_show_extra_profile_fields( $user ) {
	
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

/**********************************************/
/** CUSTOM STATUSES, POSTS, CATEGORIES, ROLES */
/**********************************************/

/** Create the custom user roles **/
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

/** Register Community Ad post type **/
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
		'publicly_queryable' => true,
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'rewrite' => true,
		'has_archive' => true,
		'capability_type' => array('eefss_community_ad', 'eefss_community_ads'),
		'taxonomies' => array('category', 'status'),
		'map_meta_cap' => true,
	);
    register_post_type('eefss_community_ad', $args);
}

/** Register Special Ad post type **/
add_action('init', 'eefss_register_special_ads');
function eefss_register_special_ads() {

	$labels = array(
		'name' => __('Special Ads', 'en'),
		'singular_name' => __('EEF Special'),
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
		'label' => __('eefss_special_ad', 'eefss_special_ads'),
		'description' => __('Specialty Item', 'eefss_special_ads'),
		'labels' => $labels,
		'supports' => array(
            'title',
            'editor',
			'custom-fields',
			'thumbnail',
			'post-formats'
		),
		'hierarchical' => true,
		'publicly_queryable' => true,
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'has_archive' => true,
		'rewrite' => true,
		'capability_type' => array('eefss_special_ad', 'eefss_special_ads'),
		'taxonomies' => array('category', 'status'),
		'map_meta_cap' => true,
	);
    register_post_type('eefss_special_ad', $args);
}

/** Register Warehouse Ad post type **/
add_action('init', 'eefss_register_warehouse_ads');
function eefss_register_warehouse_ads() {

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
			'custom-fields',
			'thumbnail',
			'post-formats'
		),
		'hierarchical' => true,
		'publicly_queryable' => true,
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'has_archive' => true,
		'rewrite' => true,
		'capability_type' => array('eefss_warehouse_ad', 'eefss_warehouse_ads'),
		'taxonomies' => array('category', 'status'),
		'map_meta_cap' => true,
	);
    register_post_type('eefss_warehouse_ad', $args);
}

add_action( 'init', 'eefss_register_status_taxonomy', 0);
function eefss_register_status_taxonomy() {
	$labels = array(
		'name' => _x( 'Item Status', 'taxonomy general name' ),
		'singular_name' => _x( 'Item Status', 'taxonomy singular name' ),
		'search_items' =>  __( 'Search Statuses' ),
		'all_items' => __( 'All Statuses' ),
		'edit_item' => __( 'Edit Status' ), 
		'update_item' => __( 'Update Status' ),
		'parent_item' => null,
		'parent_item_colon' => null,
		'add_new_item' => __( 'Add New Status' ),
		'new_item_name' => __( 'New Status Name' ),
		'menu_name' => __( 'Statuses' ),
	);
	register_taxonomy('status', array('eefss_warehouse_ad', 'eefss_community_ad', 'eefss_special_ad'), array(
		'hierarchical' => true,
		'labels' => $labels,
		'show_ui' => true,
		'show_admin_column' => true,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'status' ),
	));
}

/** Include custom posts in the search results **/
add_filter( 'pre_get_posts', 'eefss_site_search' );
function eefss_site_search( $query ) {
	
    if ( $query->is_search ) {
		$query->set( 'post_type', array( 'eefss_warehouse_ad', 'eefss_community_ad', 'eefss_special_ad' ) );
		$query->set( 'post_status', array( 'publish' ) );
		$query->set( 'status', array( 'active' ) ); // Check the custom taxonomy
    }
    
    return $query;
    
}

/** Add search to the nav manu if not on home page **/
add_filter('wp_nav_menu_items','eefss_add_search_box_to_menu', 10, 2);
function eefss_add_search_box_to_menu( $items, $args ) {
	if( !is_front_page() && !is_home() ) {
		ob_start();
		get_search_form();
		$searchform = ob_get_contents();
		ob_end_clean();

		$items .= '<li class="navbar-search">' . $searchform . '</li>';

		return $items;
	}
	else {
		return $items;
	}
	
}

/** Include custom post types in the Category view **/
add_filter('pre_get_posts', 'eefss_query_post_type');
function eefss_query_post_type($query) {
  if( is_category() ) {
    $post_type = get_query_var('post_type');
    if($post_type)
        $post_type = $post_type;
    else
        $post_type = array('nav_menu_item', 'eefss_warehouse_ad', 'eefss_community_ad');
    $query->set('post_type',$post_type);
    return $query;
    }
}

//TODO: Add in admin roles
/** Add role capabilities for managers **/
add_action('init', 'eefss_add_manager_role_caps');
function eefss_add_manager_role_caps() {

		$role = get_role('eefss_manager');

		$role->add_cap( 'read' );
		$role->add_cap( 'read_eefss_warehouse_ad' );
		$role->add_cap( 'read_eefss_community_ad' );
		$role->add_cap( 'read_eefss_special_ad' );
		$role->add_cap( 'edit_eefss_warehouse_ad' );
		$role->add_cap( 'edit_eefss_community_ad' );
		$role->add_cap( 'edit_eefss_special_ad' );
		$role->add_cap( 'edit_eefss_warehouse_ads' );
		$role->add_cap( 'edit_eefss_community_ads' );
		$role->add_cap( 'edit_eefss_special_ads' );
		$role->add_cap( 'edit_others_eefss_warehouse_ads' );
		$role->add_cap( 'edit_others_eefss_community_ads' );
		$role->add_cap( 'edit_others_eefss_special_ads' );
		$role->add_cap( 'edit_published_eefss_warehouse_ads' );
		$role->add_cap( 'edit_published_eefss_community_ads' );
		$role->add_cap( 'edit_published_eefss_special_ads' );
		$role->add_cap( 'publish_eefss_warehouse_ads' );
		$role->add_cap( 'publish_eefss_community_ads' );
		$role->add_cap( 'publish_eefss_special_ads' );
		$role->add_cap( 'delete_eefss_warehouse_ads' );
		$role->add_cap( 'delete_eefss_community_ads' );
		$role->add_cap( 'delete_eefss_special_ads' );
		$role->add_cap( 'delete_others_eefss_warehouse_ads' );
		$role->add_cap( 'delete_others_eefss_community_ads' );
		$role->add_cap( 'delete_others_eefss_special_ads' );
		$role->add_cap( 'delete_published_eefss_warehouse_ads' );
		$role->add_cap( 'delete_published_eefss_community_ads' );
		$role->add_cap( 'delete_published_eefss_special_ads' );

}
/** Add role capabilities for teachers **/
add_action('init', 'eefss_add_teacher_caps');
function eefss_add_teacher_caps() {

		$role = get_role('eefss_teacher');

		$role->add_cap( 'read' );
		$role->add_cap( 'read_eefss_community_ad' );
		$role->add_cap( 'edit_eefss_community_ad' );
		$role->add_cap( 'edit_eefss_community_ads' );
		$role->add_cap( 'edit_published_eefss_community_ads' );

}

/******************************/
/** DASHBOARD CUSTOMIZATIONS **/
/******************************/

/** Remove unnecessary menu items from the dashboard.
 *  Thanks to Tom Woodward for the snippets here:
 *  http://bionicteaching.com/minimal-wordpress/
 */
add_action('admin_menu', 'remove_admin_menu_items');
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

/** Hide ads from other authors **/
add_filter('pre_get_posts', 'posts_for_current_author');
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

/** Add dashboard metaboxes for users based on role **/
add_action( 'do_meta_boxes', 'eefss_dashboard_meta_boxes');
function eefss_dashboard_meta_boxes() {
	if(current_user_can('eefss_manager')) {
		add_meta_box('eefss-ad-stats', __('Site Stats'), 'eefss_manager_dash_meta_display', 'dashboard', 'normal', 'high');

		remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
		remove_meta_box('dashboard_activity', 'dashboard', 'normal');
		remove_meta_box('dashboard_primary', 'dashboard', 'side');
		remove_meta_box('dashboard_quick_press', 'dashboard', 'side');

	} elseif(current_user_can('eefss_teacher')) {
		add_meta_box('user-community-ads', __('Your Ads'), 'eefss_teacher_dash_meta_display', 'dashboard', 'normal', 'high');
		add_meta_box('eefss-requested-items', __('Warehouse Requests'), 'eefss_teacher_dash_requests', 'dashboard', 'side');

		remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
		remove_meta_box('dashboard_activity', 'dashboard', 'normal');
		remove_meta_box('dashboard_primary', 'dashboard', 'side');
	} else {
		add_meta_box('eefss-ad-stats', __('Site Stats'), 'eefss_manager_dash_meta_display', 'dashboard', 'normal', 'high');

	}
}

/** Define metabox for Manager role on dashboard **/
function eefss_manager_dash_meta_display($data) {

	wp_nonce_field(basename(__FILE__), "meta-box-nonce");

	$all_active = new WP_Query(array(
		'post_type' => array('eefss_warehouse_ad'),
		'post_status' => 'publish',
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
	));

	wp_reset_query();

	// Get the number of warehouse posts marked requested
	$warehouse_query = new WP_Query(array(
		'post_type' => array('eefss_warehouse_ad'),
		'post_status' => 'publish',
		'tax_query' => array(
			'relation' => 'AND',
			array(
				'taxonomy' => 'status',
				'field' => 'slug',
				'terms' => array('request_pending'),
				'operator' => 'IN',
			),
			array(
				'taxonomy' => 'status',
				'field' => 'slug',
				'terms' => array('active', 'fulfilled'),
				'operator' => 'NOT IN',
			)
		)
	));

	// Build a table
	?>
		<table>
			<thead>
				<tr>
					<th>Active Warehouse Ads</th>
					<th>Pending Warehouse Requests</th>
					<th>Community Ads</th>
					<th>Pending Community Posts</th>
				</tr>
			</thead>
			<tbody style="font-size:32px; text-align:center;">
				<td>
					<a href="<?php echo admin_url( '/edit.php?post_type=eefss_warehouse_ad&status=active'); ?>"><?php echo $all_active->found_posts; ?></a>
				</td>
				<td>
					<a href="<?php echo admin_url('/edit.php?post_type=eefss_warehouse_ad&status=request_pending'); ?>"><?php echo $warehouse_query->found_posts; ?></a>
				</td>
				<td>
					<?php echo wp_count_posts('eefss_community_ad')->publish; ?>
				</td>
				<td>
					<a href="<?php echo home_url('wp-admin/edit.php?post_status=pending&post_type=eefss_community_ad'); ?>"><?php echo wp_count_posts('eefss_community_ad')->pending; ?></a>
				</td>
			</tbody>
		</table>
	<?php
	wp_reset_query();

}

/** Define metabox for Teacher role **/
function eefss_teacher_dash_meta_display($data) {

	wp_nonce_field(basename(__FILE__), "meta-box-nonce");

	// Get all posts by this author
	$query = new WP_Query(array(
		'post_type' => 'eefss_community_ad',
		'post_status' => 'publish',
		'author' => get_current_user_id(),
		'orderby' => 'post_date',
		'order' => 'ASC',
		'tax_query' => array(
			'taxonomy' => 'status',
			'field' => 'slug',
			'terms' => array('active'),
		)
	));

	?>

	<table style="border-spacing:25px 5px; border-collapse:separate">
		<thead style="font-size:18px; text-align:left;">
			<tr>
				<th>Title</th>
				<th>Posted On</th>
				<th>Views</th>
			</tr>
		</thead>
		<tbody>

		<?php while ( $query->have_posts() ) : $query->the_post(); ?>
			<tr>
				<td>
					<a href="<?php echo get_edit_post_link(); ?>"><?php echo the_title(); ?></a>
				</td>
				<td>
					<?php the_time( get_option( date_format ) ); ?>
				</td>
				<td>
					<?php echo eefss_get_post_view(); ?>
				</td>
			</tr>
		
		<?php endwhile; ?>

		</tbody>
	</table>

	<?php
	wp_reset_query();
}

function eefss_teacher_dash_requests($data) {

	$user_id = get_current_user_id();
	$user_email = get_user_by('id', intval($user_id))->user_email;
	
	$query = new WP_Query(array(
		'numberposts' => -1,
		'post_type' => 'eefss_warehouse_ad',
		'meta_key' => 'requested_by',
		'meta_value' => $user_email,
		'orderby' => 'post_date',
		'order' => 'ASC',
	));

	?>

	<table style="border-spacing:25px 5px; border-collapse:separate;">
		<thead style="font-size:18px;text-align:left;">
			<th>Title</th>
			<th>Status</th>
		</thead>
		<tbody>
		<?php while ( $query->have_posts() ) : $query->the_post(); ?>
			<tr>
				<td>
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</td>
				<td>
					<!-- get the custom field matching this user -->
				</td>
			</tr>
		
		<?php endwhile; ?>

		</tbody>
	</table>

	<?php
	wp_reset_query();
}

add_filter( 'manage_posts_columns', 'eefss_posts_column_views' );
add_action( 'manage_posts_custom_column', 'eefss_posts_custom_column_views' );
add_action( 'wp_dashboard_setup', 'eefss_get_post_view');
/** Return the number of views a post has **/
function eefss_get_post_view() {
    $count = get_post_meta( get_the_ID(), 'post_views_count', true );
    return $count;
}

/** Store a view of a post **/
function eefss_set_post_view() {
    $key = 'post_views_count';
    $post_id = get_the_ID();
    $count = (int) get_post_meta( $post_id, $key, true );
    $count++;
    update_post_meta( $post_id, $key, $count );
}

/** Add custom columns in the admin post list **/
function eefss_posts_column_views( $columns ) {
	$columns['post_views'] = 'Views';
    return $columns;
}

/** Populate custom columns **/
function eefss_posts_custom_column_views( $column ) {
    if ( $column == 'post_views') {
        echo eefss_get_post_view();
	}
}

/** Add ACF meta shortcode **/
add_shortcode( 'post-data', 'eefss_post_acf_data' );
function eefss_post_acf_data() {
	global $post;

	$the_post = get_post();

	// Get the post metadata
	$post_id = $the_post->ID;
	$author_id = $the_post->post_author;
	
	$author = get_userdata($author_id);

	$acf_data = get_fields($the_post->ID);

	$complete = ($acf_data['complete']) ? 'Completed' : 'In Progress';

	$user_string .= $author->first_name . ' ' . $author->last_name;

	$string = "<div class='eefss_community_ad_data'>
		<h2>Staff Member Info</h2>
		<div class='info'>
			<span class='name'>" . $author->first_name . ' ' . $author->last_name . ", <span class='building'>". $author->building . "</span>
			<span class='assignment'>" . $author->assignment ."<span>
		</div>
		<div class='about'>" . $author->description . "</div>
		<hr />
		<h4>Project Details</h4>
		<div class='status'>Status: ". $complete . "</div>
		<div class='cost'>Est. Cost: $" . $acf_data['cost_estimate'] . "</div>
		<button type='button' class='btn btn-info mt-2' data-toggle='modal' data-postid='" . $post_id . "' data-useremail='" . $author->user_email . "' data-userstring='" . $user_string . "' data-target='#teacherContact'>Contact Teacher</button>
	</div>";

	return $string;
}

add_shortcode( 'author-data', 'eefss_author_posts' );
function eefss_author_posts() {
	global $wpdb, $post;

	if(is_page()) {
		$author = wp_get_current_user();
	} else {
		$author_id = $post->post_author;
		$author = get_userdata($author_id);
	}

	$query = get_posts(array(
		'post_type' => 'eefss_community_ad',
		'author' => $author->ID,
		'orderby' => 'post_date',
		'order' => 'DESC',
		'numberposts' => 5
	));

	$string = '<div class="teacher-data">
		<h4>More by '. $author->first_name .'</h4>
		<hr />';

		if(count($query) > 0) {

			$string .= '<ol>';

			foreach($query as $item) {

				$string .= '<li><a href="'. $item->guid .'">'. $item->post_title .'</a></li>';

			}

			$string .= '</ol>';

		} else {
			$string .= '<p>You don\'t have any posts yet! <a href="/staff-request">Make one now.</a></p>';
		}

	$string .= '</div>';

	return $string;
}
add_shortcode( 'warehouse-data', 'eefss_warehouse_acf_data');
function eefss_warehouse_acf_data() {
	global $post;

	$the_post = get_post();

	$acf_data = get_fields($the_post->ID);

	if(is_user_logged_in()) {
		$button = "<button type='button' class='btn btn-info mt-2' data-toggle='modal' data-target='#requestItem'>Place Request</button>";
	} else {
		$button = "<button type='button' class='btn btn-info mt-2' data-toggle='modal' data-target='#signInPrompt'>Place Request</button>";
	}

	$string = "
		<div class='eefss_warehouse_ad_data'>
			<h2>Item Details</h2>
			<hr />
			<div class='unit-quant'><strong>Unit quantity:</strong> " . $acf_data['unit_quantity'] . "</div>
			<div class='avail'><strong>Stock available:</strong> 
				<span id='avail-quant'>". $acf_data['quantity'] . "</span>
			</div>". 
			$button
		."</div>";

	return $string;
}

// Clean up the Gravity Forms styles with Bootstrap classes
add_filter( 'gform_field_container', 'eefss_add_bootstrap_container_class', 10, 6 );
function eefss_add_bootstrap_container_class( $field_container, $field, $form, $css_class, $style, $field_content ) {
  $id = $field->id;
  $field_id = is_admin() || empty( $form ) ? "field_{$id}" : 'field_' . $form['id'] . "_$id";
  return '<li id="' . $field_id . '" class="' . $css_class . ' form-group">{FIELD_CONTENT}</li>';
}

// Edit the Gravity Forms submit button
add_filter( 'gform_submit_button', 'eefss_add_custom_css_classes', 10, 2 );
function eefss_add_custom_css_classes( $button, $form ) {
    $dom = new DOMDocument();
    $dom->loadHTML( $button );
    $input = $dom->getElementsByTagName( 'input' )->item(0);
    $classes = $input->getAttribute( 'class' );
    $classes .= "btn btn-secondary";
    $input->setAttribute( 'class', $classes );
    return $dom->saveHtml( $input );
}

// Capture Gravity Forms submission and return data for the donor
add_filter( 'gform_confirmation', 'eefss_financial_confirmation', 10, 4);
function eefss_financial_confirmation($confirmation, $form, $entry, $ajax) {
	
	if($form["id"] == "3") {
		$type = rgar($entry, '4');

		if($type === 'financial') {

			$string = 'Please apply this to ' . rgar($entry, '11') .'\'s project, ' . rgar($entry, '2') . '.';

			$confirmation = '
				<br/>
				<div class="confirmationbuild">
				<h2>Thank you for offering a financial contribution!</h2> 
	
				<p>You can make your secure donation via the EEF online donation page. On the donation form, please select <b>Tools for Schools</b> for your donation type and paste in the following information:</p>
	
				<textarea class="form-control rounded-1" rows="5" cols="25" id="confirmation-select" onclick="this.focus();this.select()" readonly="readonly">' . $string .'</textarea>
	
				<a class="btn btn-primary" href="https://elkharteducationfoundation.networkforgood.com/projects/37979-make-a-gift-today">Make my donation</a>
	
				</div>';

			return $confirmation;
		}

	} else {

		return $confirmation;

	}

}

if( !wp_next_scheduled( 'eefss_cron_hook' )) {

	wp_schedule_event( time(), 'hourly', 'eefss_cron_hook' );

}

// Send warehouse checks to all users
// Run with WP_Cron daily
// TODO: Figure out where to hook this function
add_action('eefss_cron_hook', 'eefss_send_warehouse_reminders');
function eefss_send_warehouse_reminders() {

	// Get active warehouse posts
	$all_active = new WP_Query(array(
		'post_type' => array('eefss_warehouse_ad'),
		'post_status' => 'publish',
		'posts_per_page' => -1,
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
	));

	wp_reset_postdata();

	foreach($all_active->posts as $post) {

		// If there are rows in the requests repeater
		if(have_rows('requests', intval($post->ID))) {

			while(have_rows('requests', intval($post->ID))) : the_row(); 

				// Check the sent value first
				$sent = get_sub_field('reminder_sent');

				if(!$sent) {

					$input = get_sub_field('requested_date');
	
					$requested_on = date('m/d/Y', strtotime($input));
	
					$past = date('m/d/Y', strtotime('-1 week'));
	
					// If the request was one week ago, send an email
					if($requested_on === $past) {
	
						// Get the email, user, post ID and post title for the email
						$recip = get_sub_field('requested_by');
						$user_id = get_sub_field('requester_id');
	
						$post_id = $post->ID;
						$post_title = $post->post_title;
	
						// Template the email
						$template = eefss_email_template('warehouse', $post_id, $post_title, $user_id);
	
						// Send an email to check the status of the request from the user
						add_filter('wp_mail_content_type', create_function('', 'return "text/html"; '));
	
						$emailTo = $recip;
						$subject = $template['subject'];
						$body = $template['body'];
						wp_mail($emailTo, $subject, $body);
	
						// Mark a reminder a sent so they're not bombarded with emails.
						update_sub_field('reminder_sent', 1);
	
					}

				}

			endwhile;
			
		} else {
			
			error_log('No rows found');

		}

	}

}

// Send donor checks to all teachers
// Ask if they've heard from the donor
add_action('eefss_cron_hook', 'eefss_send_community_reminders');
function eefss_send_community_reminders() {

	// query only posts with at least one row
	$meta_query = [
	    [
			'key'     => 'contacts_0_contact_date',
			'compare' => 'EXISTS',
		]
	];

	// Get active warehouse posts
	$all_active = new WP_Query(array(
		'post_type' => array('eefss_community_ad'),
		'post_status' => 'publish',
		'posts_per_page' => -1,
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
		),
		'meta_query' => $meta_query,
	));

	wp_reset_postdata();

	// We only queried posts with contacts, so this should loop without any errors.
	// Only check for a donor who did not submit contact info?
	foreach($all_active->posts as $post) {

		// Get post meta for sending emails
		$user_id = $post->post_author;
		$user = get_user_by( 'id', $user_id );
		$recip = $user->user_email;

		while(have_rows('contacts', intval($post->ID))) : the_row(); 

				// Check the sent value first
				$sent = get_field('reminder_sent');

				// If an email hasn't been sent yet...
				if(!$sent) {

					// Get the first contacted date
					$input = get_sub_field('contact_date');
	
					$contacted_on = date('m/d/Y', strtotime($input));
	
					$past = date('m/d/Y', strtotime('-1 week'));
	
					// If the request was one week ago, send an email
					if($contacted_on === $past) {

						$template = eefss_email_template('community', $post_id, $post_title, $user_id);

						// Send an email to check the status of the request from the user
						add_filter('wp_mail_content_type', create_function('', 'return "text/html"; '));

						$emailTo = $recip;
						$subject = $template['subject'];
						$body = $template['body'];
						wp_mail($emailTo, $subject, $body);

						update_field( 'reminder_sent', 1, $post_id );
	
					}

				}

			endwhile;

		// Get the date of the first contact and compate

		// Contact the post author via email to check on the status.
		$post_id = $post->ID;
		$post_title = $post->post_title;

		// Template the email
		$template = eefss_email_template('community', $post_id, $post_title, $user_id);

		// Send an email to check the status of the request from the user
		add_filter('wp_mail_content_type', create_function('', 'return "text/html"; '));

		$emailTo = $recip;
		$subject = $template['subject'];
		$body = $template['body'];
		wp_mail($emailTo, $subject, $body);

		update_field( 'reminder_sent', 1, $post_id );

	}

}

// Store the Warehouse Compelte form on the requester's row
add_action('gform_after_submission_6', 'eefss_update_warehouse_requests', 10, 2);
function eefss_update_warehouse_requests( $entry, $form ) {

	$post_id = intval(rgar($entry, 4));
	$user_id = intval(rgar($entry, 3));
	$complete = rgar($entry, 5);

	$post = get_post($post_id);

	if( have_rows('requests', $post_id) ):

		while( have_rows('requests', $post_id) ) : the_row();

			update_sub_field('completed', $complete, $post_id);
			update_sub_field('completed_date', date('m/d/Y'), $post_id);

		endwhile;

		$the_post = get_post($post_id);
		wp_update_post($the_post);
	
	else:

		error_log('No rows found');

	endif;

}

// Mark the appropriate community post based on the teacher feedback
add_action('gform_after_submission_7', 'eefss_update_community_request', 10, 2);
function eefss_update_community_request( $entry, $form ) {

	$user_id = intval(rgar($entry, 1));
	$post_id = intval(rgar($entry, 2));
	$complete = rgar($entry, 4);

	$post = get_post($post_id);

	update_field( 'complete', $complete, $post_id);

	if($complete) {

		wp_set_object_terms( intval($post_id), 'fulfilled', 'status' );
		wp_remove_object_terms( intval($post_id), 'active', 'status' );

	}

}

