<?php


// Exit If Accessed Directly
if( ! defined( 'ABSPATH' ) ) { exit; }


// AJAX Load Script
add_action( 'admin_enqueue_scripts', function() {
	wp_enqueue_script( 'bmew_admin', plugin_dir_url( __FILE__ ) . 'admin.js', [ 'jquery' ], null );
} );


// Plugin Action Links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
	$settings = [
		'settings' => sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=wc-settings&tab=bmew' ),
			__( 'Settings', 'woo-benchmark-email' )
		),
	];
	return array_merge( $settings, $links );
} );


// Admin Dashboard Notifications
add_action( 'wp_dashboard_setup', function() {

	// Ensure is_plugin_active() Exists
	if( ! function_exists( 'is_plugin_active' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	$messages = [];

	// Handle Sister Product Dismissal Request
	if( ! empty( $_REQUEST['bmew_dismiss_sister'] ) && check_admin_referer( 'bmew_dismiss_sister' ) ) {
		update_option( 'bmew_sister_dismissed', current_time( 'timestamp') );
	}

	// Check Sister Product
	$bmew_sister_dismissed = get_option( 'bmew_sister_dismissed' );
	if(
		$bmew_sister_dismissed < current_time( 'timestamp') - 86400 * 90
		&& ! is_plugin_active( 'benchmark-email-lite/benchmark-email-lite.php' )
		&& current_user_can( 'activate_plugins' )
	) {

		// Plugin Installed But Not Activated
		if( file_exists( WP_PLUGIN_DIR . '/benchmark-email-lite/benchmark-email-lite.php' ) ) {
			$messages[] = sprintf(
				'
					%s &nbsp; <strong style="font-size:1.2em;"><a href="%s">%s</a></strong>
					<a style="float:right;" href="%s">%s</a>
				',
				__( 'Activate our sister product Benchmark Email Lite to view campaign statistics.', 'woo-benchmark-email' ),
				bmew_admin::get_sister_activate_link(),
				__( 'Activate Now', 'woo-benchmark-email' ),
				bmew_admin::get_sister_dismiss_link(),
				__( 'dismiss for 90 days', 'woo-benchmark-email' )
			);

		// Plugin Not Installed
		} else {
			$messages[] = sprintf(
				'
					%s &nbsp; <strong style="font-size:1.2em;"><a href="%s">%s</a></strong>
					<a style="float:right;" href="%s">%s</a>
				',
				__( 'Install our sister product Benchmark Email Lite to view campaign statistics.', 'woo-benchmark-email' ),
				bmew_admin::get_sister_install_link(),
				__( 'Install Now', 'woo-benchmark-email' ),
				bmew_admin::get_sister_dismiss_link(),
				__( 'dismiss for 90 days', 'woo-benchmark-email' )
			);
		}
	}

	// Message If Plugin Isn't Configured
	if( empty( get_option( 'bmew_key' ) ) ) {
		$messages[] = sprintf(
			'%s &nbsp; <strong style="font-size:1.2em;"><a href="admin.php?page=wc-settings&tab=bmew">%s</a></strong>',
			__( 'Please configure your API Key to use Woo Benchmark Email.', 'woo-benchmark-email' ),
			__( 'Configure Now', 'woo-benchmark-email' )
		);
	}

	// Output Message
	if( $messages ) {
		foreach( $messages as $message ) {
			echo sprintf(
				'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
				print_r( $message, true )
			);
		}
	}
} );


// Load Settings API Class
add_filter( 'woocommerce_get_settings_pages', function( $settings ) {
	$settings[] = include( 'class.wc-settings.php' );
	return $settings;
} );


// Administrative Class
class bmew_admin {


	// Sister Install Link
	static function get_sister_install_link() {
		$action = 'install-plugin';
		$slug = 'benchmark-email-lite';
		return wp_nonce_url(
			add_query_arg(
				[ 'action' => $action, 'plugin' => $slug ],
				admin_url( 'update.php' )
			),
			$action . '_' . $slug
		);
	}


	// Sister Activate Link
	static function get_sister_activate_link( $action='activate' ) {
		$plugin = 'benchmark-email-lite/benchmark-email-lite.php';
		$_REQUEST['plugin'] = $plugin;
		return wp_nonce_url(
			add_query_arg(
				[ 'action' => $action, 'plugin' => $plugin, 'plugin_status' => 'all', 'paged' => '1&s' ],
				admin_url( 'plugins.php' )
			),
			$action . '-plugin_' . $plugin
		);
	}


	// Sister Dismiss Notice Link
	static function get_sister_dismiss_link() {
		$url = wp_nonce_url( 'index.php?bmew_dismiss_sister=1', 'bmew_dismiss_sister' );
		return $url;
	}


	// Customer Sync AJAX Submit
	static function wp_ajax__bmew_action__sync_customers() {

		// Find Appropriate Contact List
		$key = get_option( 'bmew_key' );
		$lists = get_option( 'bmew_lists' );
		$listID = isset( $lists[$key]['customers'] ) ? $lists[$key]['customers'] : false;
		if( ! $listID ) { return; }
		$page = empty( $_POST['page'] ) ? 1 : intval( $_POST['page'] );

		// Dev Analytics
		if( $page == '1' ) {
			bmew_api::tracker( 'sync-customers' );
		}

		// Query Orders Not Already Sync'd
		$args = [
			'limit' => 10,
			'meta_compare' => 'NOT EXISTS',
			'meta_key' => '_bmew_syncd',
			'order' => 'ASC',
			'orderby' => 'ID',
			'page' => $page,
			'return' => 'ids',
		];
		$query = new WC_Order_Query( $args );
		$orders = $query->get_orders();

		// Loop Results
		foreach( $orders as $order_id ) {

			// Get Fields From Order
			$email = get_post_meta( $order_id, '_billing_email', true );

			// Skip If No Email Provided
			if( ! $email ) { continue; }

			// Get Order Record
			$_order = wc_get_order( $order_id );

			// Get Order Details
			$args = bmew_frontend::get_order_details( $order_id, $email );

			// Add Contact To List
			$response = bmew_api::add_contact( $listID, $email, $args );

			// If Successful, Mark Order As Sync'd
			if( intval( $response ) > 0 ) {
				update_post_meta( $order_id, '_bmew_syncd', current_time( 'timestamp' ) );
			}
		}

		// Handle Finish
		if( ! $orders ) { $page = 0; }

		// Return
		return $page;
	}
}