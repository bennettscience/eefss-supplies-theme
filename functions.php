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

	wp_register_script( 'request-handler', get_template_directory_uri() . '/js/request-handler.js' );
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

	$request = $_POST['action'];
	$user_id = $_POST['user_id'];
	$post_id = $_POST['post_id'];
	$quant = $_POST['quant'];

	$user = get_user_by('id', intval($user_id));

	$date = new DateTime();

	// Check the action key and run the appropriate function.

	if($request === 'request_item') {

		// Subtract the requested quantity from the total available
		$available = get_field('quantity', $post_id, true);

		$available = intval($available) - intval($quant);

		if(intval($available) < 0) {
			// If the user requested too many items somehow, return an error.

			header('HTTP/1.1 500 Internal Error');
        	header('Content-Type: application/json; charset=UTF-8');
			wp_die(json_encode(array('message' => 'ERROR - You requested more items than were available. Please update your quantity and try again.', 'value' => $available)));
			
		} elseif(intval($available) == 0) {

			$row = array(
				'requested_by' => $user->user_email,
				'requested_quantity' => $quant,
				'requested_date' => $date->format('m-d-Y'),
				'completed' => 0,
				'completed_date' => '',
			);

			add_row('requests', $row, intval($post_id));

			// If there are none left, set `expired` to true
			update_field('expired', 1, intval($post_id));
			update_field('quantity', $available, intval($post_id));

			// Update the post status to `expired` and remove it from the query results
			$update = array(
				'ID' => intval($post_id),
				'post_status' => 'expired',
			);

			wp_update_post($update);

		} else {
			// There is more available. Set the new quantity and return.
			$row = array(
				'requested_by' => $user->user_email,
				'requested_quantity' => $quant,
				'requested_date' => $date->format('m-d-Y'),
				'completed' => 0,
				'completed_date' => '',
			);

		add_row('requests', $row, intval($post_id));
			// There are some left, so subtract and return an updated quantity
			update_field('quantity', intval($available), intval($post_id));
		}
	}

	wp_die(json_encode(array('message' => 'SUCCESS - Your request has been filed.', 'value' => $available)));
}

/***********************/
/** USER REGISTRATION **/
/***********************/

/** Custom user registration form */
add_action('register_form', 'eefss_register_form');
function eefss_register_form() {
	
	$allowed = array(
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
		'North Side',
		'Osolo',
		'Pierre Moran',
		'Pinewood',
		'Riverview',
		'Roosevelt',
		'West Side',
		'Woodland',
	);

	$building = ! empty( $_POST['building'] ) ? strval( $_POST['building'] ) : '';

	$first_name = ( ! empty( $_POST['first_name'] ) ) ? sanitize_text_field( $_POST['first_name'] ) : '';
	$last_name = ( ! empty($_POST['last_name'] ) ) ? sanitize_text_field( $_POST['last_name' ]) : '';

	?>

	<p>
		<label for="first_name"><?php esc_html_e( 'First Name', 'crf'); ?><br />
		<input type="text" id="first-name" name="first_name" class="input" value="<?php echo esc_attr( $first_name ); ?>" />

		<label for="last_name"><?php esc_html_e( 'Last Name', 'crf'); ?><br />
		<input type="text" id="last-name" name="last_name" class="input" value="<?php echo esc_attr(  $last_name  ); ?>" />

		<label for="building"><?php esc_html_e( 'Building', 'crf' ) ?><br/>
			<select
			       id="building"
			       name="building"
			       class="input"
			>

			<?php 
				foreach($allowed as $the_building) {
					echo '<option value="'. $the_building .'">' . $the_building .'</option>';
				}
			?>
			</select>
		</label>
	</p>
	<?php
}

/** Custom registration errors to display if needed */
add_filter( 'registration_errors', 'eefss_registration_errors', 10, 3 );
function eefss_registration_errors( $errors, $sanitized_user_login, $user_email ) {

	$allowed = array(
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
		'North Side',
		'Osolo',
		'Pierre Moran',
		'Pinewood',
		'Riverview',
		'Roosevelt',
		'West Side',
		'Woodland',
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

	// Sanitize the text inputs
	if ( empty( $_POST['first_name'] ) || ! empty( $_POST['first_name'] ) && trim( $_POST['first_name'] ) == '' ) {
		$errors->add( 'first_name_error', sprintf('<strong>%s</strong>: %s',__( 'ERROR', 'understrap' ),__( 'You must include a first name.', 'understrap' ) ) );
	}

	if ( empty( $_POST['last_name'] ) || ! empty( $_POST['last_name'] ) && trim( $_POST['last_name'] ) == '' ) {
		$errors->add( 'last_name_error', sprintf('<strong>%s</strong>: %s',__( 'ERROR', 'understrap' ),__( 'You must include a last name.', 'understrap' ) ) );
	}

	return $errors;
}

/** Register the new user */
add_action( 'user_register', 'eefss_user_register' );
function eefss_user_register( $user_id ) {

	$allowed = array(
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
		'North Side',
		'Osolo',
		'Pierre Moran',
		'Pinewood',
		'Riverview',
		'Roosevelt',
		'West Side',
		'Woodland',
	);

	// Make sure a building is submitted to the DB.
	if ( empty( $_POST['building'] ) ) {
		$errors->add( 'building_error', __( '<strong>ERROR</strong>: Please enter a valid building name.', 'crf' ) );
	}

	// Validate the building submission one last time
	if ( ! empty( $_POST['building'] ) && in_array(strval( $_POST['building'] ), $allowed, true) ) {
		update_user_meta( $user_id, 'building', strval( $_POST['building'] ) );
	}

	if ( ! empty( $_POST['first_name'] ) ) {
		update_user_meta( $user_id, 'first_name', sanitize_text_field( $_POST['first_name'] ) );
	}

	if ( ! empty( $_POST['last_name'] ) ) {
		update_user_meta( $user_id, 'last_name', sanitize_text_field( $_POST['last_name'] ) );
	}

}

/** Redirect teachers to index instead of the dashboard after login **/
add_action( 'login_redirect', 'eefss_redirect_teacher_on_login', 10, 3);
function eefss_redirect_teacher_on_login( $redirect, $request, $user ) {
	return (is_array($user->roles) && in_array('administrator', $user->roles)) ? admin_url() : site_url();
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


/** Show the user's location as a field in the Profile page **/
// TODO: Convert location display into dropdown in case a user needs to update the field.
add_action( 'show_user_profile', 'eefss_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'eefss_show_extra_profile_fields' );
function eefss_show_extra_profile_fields( $user ) {

	$allowed = array(
		'Beardsley',
		'Beck',
		'Bristol',
		'Central HS',
		'Cleveland',
		'Daly',
		'Eastwood',
		'Elkhart Academy',
		'Feeser',
		'Hawthorne',
		'Memorial HS',
		'North Side MS',
		'Osolo',
		'Pierre Moran MS',
		'Pinewood',
		'Riverview',
		'Roosevelt',
		'West Side MS',
		'Woodland',
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

/**********************************************/
/** CUSTOM STATUSES, POSTS, CATEGORIES, ROLES */
/**********************************************/

/** Register a custom 'Requested' and 'Complete' post status */
add_action( 'init', 'eefss_custom_requested_status' );
function eefss_custom_requested_status() {
	register_post_status( 'requested', array(
		'label'                     => _x( 'Requested', 'post' ),
		'post_type'					=> 'eefss_warehouse_ad',
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Requested (%s)', 'Requested (%s)' ),
	) );

	register_post_status( 'completed', array(
		'label' 					=> _x('Completed', 'post'),
		'post_type'					=> 'eefss_warehouse_ad',
		'public' 					=> true,
		'exclude_from_search' 		=> false,
		'show_in_admin_all_list' 	=> true,
		'show_in_admin_status_list' => true,
		'label_count' 				=> _n_noop( 'Completed (%s)', 'Completed (%s)'),
	));
}

//TODO: custom statuses in quick edit
/** Add the post statuses to the admin menus **/
add_action('admin_footer-post.php', 'eefss_append_post_status');
function eefss_append_post_status() {

	global $wp_post_statuses, $post;
	// Get all non-builtin post status and add them as <option>
	$options = $display = '';
	foreach ( $wp_post_statuses as $status )
	{
		if ( ! $status->_builtin )
		{
			// Match against the current posts status
			$selected = selected( $post->post_status, $status->name, false );
			// If we one of our custom post status is selected, remember it
			$selected AND $display = $status->label;
			// Build the options
			$options .= "<option{$selected} value='{$status->name}'>{$status->label}</option>";
		}
	}
	?>
	<script type="text/javascript">
		jQuery( document ).ready( function($) 
		{
			let appended = false;

			<?php
			// Add the selected post status label to the "Status: [Name] (Edit)" 
			if ( ! empty( $display ) ) : 
			?>
				$( '#post-status-display' ).html( '<?php echo $display; ?>' )
			<?php 
			endif; 
			// Add the options to the <select> element
			?>
			$( '.edit-post-status' ).on( 'click', function()
			{
				if(!appended) {
					let select = $( '#post-status-select' ).find( 'select' );
					$( select ).append( "<?php echo $options; ?>" );
					appended = true;
				
				}
				
			} );

			$( '.save-post-status ').on('click', function() {
				let select = $('#post-status-select').find('select').val();
				console.log(select);
				switch(select){
					case('requested'):
						$('#save-post').val('Set Requested Status');
						break;
					case('completed'):
						$('#save-post').val('Mark as Complete');
						break;
					default:
						$('#save-post').val('Save');
				}
			})
		} );
	</script>
	<?php
};

/** Add custom bulk options to warehouse item list **/
add_filter('bulk_actions-edit-eefss_warehouse_ad', 'eefss_warehouse_bulk_actions');
function eefss_warehouse_bulk_actions($bulk_array) {
	$bulk_array['eefss_mark_complete'] = 'Mark as Complete';

	return $bulk_array;
}

/** Handle the action submit when using the bulk edit menu **/
add_filter('handle_bulk_actions-edit-eefss_warehouse_ad', 'eefss_handle_warehouse_bulk_actions', 10, 3);
function eefss_handle_warehouse_bulk_actions($redirect, $action, $obj_ids) {
	// remove redirects
	$redirect = remove_query_arg( array( 'eefss_mark_completed_done' ), $redirect );

	if($action == 'eefss_mark_completed') {
		foreach($obj_ids as $post_id) {
			wp_update_post(array( 
				'ID' => $post_id,
				'post_status' => 'completed',
			));
		}

		$redirect = add_query_arg( 'eefss_mark_completed_done', count( $object_ids ), $redirect );

	}

	return $redirect;
}

/** Show a notice after the posts have been updated **/
add_action('admin_notices', 'eefss_notify_bulk_update');
function eefss_notify_bulk_update() {
	if ( ! empty( $_REQUEST['eefss_mark_completed_done'] ) ) {
		echo '<div id="message" class="updated notice is-dismissible">
			<p>Posts updated.</p>
		</div>';
	}
}

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
		'map_meta_cap' => true,
	);
    register_post_type('eefss_community_ad', $args);
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
		'taxonomies' => array('category', 'post_tag'),
		'map_meta_cap' => true,
	);
    register_post_type('eefss_warehouse_ad', $args);
}

/** Add role capabilities for managers **/
add_action('init', 'eefss_add_manager_role_caps');
function eefss_add_manager_role_caps() {

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

/** Add default categories on theme activation **/
// TODO: Add default categories
// add_action('init', 'eefss_register_default_categories');
// function eefss_register_default_categories() {
// 	$cats = array(
// 		array(
// 			'Consumables',
// 			'category',
// 			array(
// 				'description' => 'Items that are used once, like pencils, pens, erasers, etc.',
// 				'slug' => 'consumables'
// 			),
// 		),
// 		array(
// 			'Furniture',
// 			'category',
// 			array(
// 				'description' => 'All things furniture. Big and small.',
// 				'slug' => 'furniture',
// 			),
// 		),
// 	);

// 	foreach($cats as $the_cat) {
// 		if( !term_exists($the_cat) ) {
// 			wp_insert_term($the_cat);
// 		}
// 	}
// }

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

	// Get the number of warehouse posts with requested and !complete
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
					<?php echo wp_count_posts('eefss_warehouse_ad')->publish; ?>
				</td>
				<td>
					<a href="<?php echo home_url('wp-admin/edit.php?post_type=eefss_warehouse_ad&post_status=requested'); ?>"><?php echo $warehouse_query->found_posts; ?></a>
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
		<tbody style="text-align:center;">

		<?php while ( $query->have_posts() ) : $query->the_post(); ?>
			<tr>
				<td>
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
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
					<?php echo get_post_status(); ?>
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
	$columns['requested_by'] = 'Requested By';
    return $columns;
}

/** Populate custom columns **/
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

	$string = "<div class='eefss_community_ad_data'>
		<div class='name'>" . $author->first_name . ' ' . $author->last_name . "</div>
		<div class='building'>" . $author->building . "</div>
		<h4>About " . $author->first_name . "</h4>
		<div class='about'>" . $author->description . "</div>
		<hr />
		<h4>Project Details</h4>
		<div class='cost'>Est. Cost: " . $acf_data['cost_estimate'] . "</div>
		<a class='btn btn-secondary' id='listing-" . $post_id . "' href='#'>Contact " . $author->first_name . "</a>
	</div>";

	return $string;
}

add_shortcode( 'warehouse-data', 'eefss_warehouse_acf_data');
function eefss_warehouse_acf_data() {
	global $post;

	$the_post = get_post();

	$acf_data = get_fields($the_post->ID);

	$string = "<div class='eefss_warehouse_ad_data'>
		<div id='avail-quant'>" . $acf_data['quantity'] . "</div>
		<div class='unit-quant'>Unit quantity: " . $acf_data['unit_quantity'] . "</div>
	</div>";

	return $string;
}

add_shortcode( 'author-data', 'eefss_author_posts' );
function eefss_author_posts() {
	global $wpdb, $post;

	$author_id = $post->post_author;
	$author = get_userdata($author_id);

	$query = new WP_Query(array(
		'post_type' => 'eefss_community_ad',
		'author' => $author_id,
		'orderby' => 'post_date',
		'order' => 'ASC',
		'numberposts' => 5
	));

	?>
	<h4>More by <?php echo $author->first_name; ?></h4>
	<hr />
	<ol>
	<?php

	if($query->have_posts()) {
	
		while($query->have_posts()) : $query->the_post() ?>

			<li><a href='<?php the_permalink(); ?>'><?php the_title(); ?></a></li>

	<?php endwhile;

	}
}