<?php

// Exit If Accessed Directly
if( ! defined( 'ABSPATH' ) ) { exit; }

// Administrative / Settings Class
class bmew_admin {


	/**********************
		Admin Messaging
	**********************/

	// Admin Dashboard Diagnostics Function
	static function wp_dashboard_setup() {


		/******************
			Diagnostics
		******************/

		$message = '';
		//$message = get_option( 'bmew_lists' );
		//$message = bmew_api::add_list( 'WooCommerce Test List' );
		//$message = bmew_api::get_lists();
		//$message = bmew_frontend::match_list( 'abandons' );
		//$message = bmew_api::get_contact( ###, ### );
		//$message = bmew_api::add_contact( ###, 'sean_test01@codedcommerce.com', [ 'first' => 'Test' ] );
		//$message = bmew_api::delete_contact( ###, ### );
		//$message = bmew_api::find_contact( 'sean+test01@codedcommerce.com' );


		/*********************
			Sister Product
		*********************/

		// Handle Dismissal Request
		if( ! empty( $_REQUEST['bmew_dismiss_sister'] ) && check_admin_referer( 'bmew_dismiss_sister' ) ) {
			update_option( 'bmew_sister_dismissed', current_time( 'timestamp') );
		}

		// Check Sister Product
		$bmew_sister_dismissed = get_option( 'bmew_sister_dismissed' );
		if(
			$bmew_sister_dismissed < current_time( 'timestamp') - 86400 * 90
			&& is_plugin_inactive( 'benchmark-email-lite/benchmark-email-lite.php' )
			&& current_user_can( 'activate_plugins' )
		) {

			// Plugin Installed But Not Activated
			if( file_exists( WP_PLUGIN_DIR . '/benchmark-email-lite/benchmark-email-lite.php' ) ) {
				$message =
					__( 'Activate our sister product Benchmark Email Lite to view campaign statistics.', 'woo-benchmark-email' )
					. sprintf(
						' &nbsp; <strong style="font-size:1.25em;"><a href="%s">%s</a></strong>',
						bmew_admin::get_sister_activate_link(),
						__( 'Activate Now', 'woo-benchmark-email' )
					);

			// Plugin Not Installed
			} else {
				$message =
					__( 'Install our sister product Benchmark Email Lite to view campaign statistics.', 'woo-benchmark-email' )
					. sprintf(
						' &nbsp; <strong style="font-size:1.25em;"><a href="%s">%s</a></strong>',
						bmew_admin::get_sister_install_link(),
						__( 'Install Now', 'woo-benchmark-email' )
					);
			}

			// Dismiss Link
			$message .= sprintf(
				' <a style="float:right;" href="%s">%s</a>',
				bmew_admin::get_sister_dismiss_link(),
				__( 'dismiss for 90 days', 'woo-benchmark-email' )
			);
		}

		// Output Message
		if( $message ) {
			echo sprintf(
				'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
				print_r( $message, true )
			);
		}
	}

	// Sister Install Link
	static function get_sister_install_link() {
		$action = 'install-plugin';
		$slug = 'benchmark-email-lite';
		return wp_nonce_url(
			add_query_arg(
				array( 'action' => $action, 'plugin' => $slug ),
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
				array( 'action' => $action, 'plugin' => $plugin, 'plugin_status' => 'all', 'paged' => '1&s' ),
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


	/***************************
		WooCommerce Settings
	***************************/

	// Create The Section Beneath The Advanced Tab
	static function woocommerce_get_sections_advanced( $sections ) {
		$sections['bmew'] = 'Benchmark Email';
		return $sections;
	}

	// Create The Setting Within The Custom Section
	static function woocommerce_get_settings_advanced( $settings ) {

		// Check The Current Section Is What We Want
		if( ! isset( $_REQUEST['section'] ) || $_REQUEST['section'] != 'bmew' ) {
			return $settings;
		}

		// Response Data
		return array(

			// Add Section Title
			array( 'desc' => '', 'id' => 'bmew', 'name' => 'Benchmark Email', 'type' => 'title' ),

			// Add API Key Field
			array(
				'desc' => '<br>' . __( 'Skips the cart step and redirects customers to the checkout form that conveniently displays a mini cart.', 'woo-benchmark-email' )
					. '<br>' . __( "If they need to edit their cart, they will have to click on your theme's cart link in order to do so.", 'woo-benchmark-email' ),
				'desc_tip' => __( 'This may improve the chances of the email address being provided by customers and thus available to abandoned cart offers.', 'woo-benchmark-email' ),
				'id' => 'bmew_skip_cart',
				'name' => __( 'Skip the cart step', 'woo-benchmark-email' ),
				'type' => 'checkbox',
			),

			// Add API Key Field
			array(
				'desc' => '<br>' . __( 'Moves the email address and phone number fields up and underneath the name fields.', 'woo-benchmark-email' ),
				'desc_tip' => __( 'This may improve the chances of the email address being provided by customers and thus available to abandoned cart offers.', 'woo-benchmark-email' ),
				'id' => 'bmew_checkout_reorder',
				'name' => __( 'Move email field up', 'woo-benchmark-email' ),
				'type' => 'checkbox',
			),

			// Add Text Field Option
			array(
				'default' => __( 'Opt-in to receive exclusive customer communications', 'woo-benchmark-email' ),
				'desc' => '<br>' . __( 'Checkout form opt-in field label', 'woo-benchmark-email' ),
				'desc_tip' => __( 'Label for checkout form opt-in checkbox field.', 'woo-benchmark-email' ) . ' '
					. __( 'Leave this setting blank to eliminate the opt-in field from your checkout form.', 'woo-benchmark-email' ),
				'id' => 'bmew_checkout_optin_label',
				'name' => __( 'Checkout Opt-In Field', 'woo-benchmark-email' ),
				'type' => 'text',
			),

			// Add API Key Field
			array(
				'desc' => '<br>' . __( 'API Key from your Benchmark Email account', 'woo-benchmark-email' ),
				'desc_tip' => __( 'Log into https://ui.benchmarkemail.com and copy your API key here.', 'woo-benchmark-email' ),
				'id' => 'bmew_key',
				'name' => __( 'API Key', 'woo-benchmark-email' ),
				'type' => 'text',
			),

			// Add API Key Field
			array(
				'desc' => '<br>' . __( 'For temporary use, saves all API communications into WooCommerce > Status > Logs.', 'woo-benchmark-email' ),
				'desc_tip' => __( "For a nicer logs UI, set `define( 'WC_LOG_HANDLER', 'WC_Log_Handler_DB' );` inside your  `wp-config.php`.", 'woo-benchmark-email' ),
				'id' => 'bmew_debug',
				'name' => __( 'Log debug messages?', 'woo-benchmark-email' ),
				'type' => 'checkbox',
			),

			// Add API Key Field
			array(
				'desc' => '
					<p>
						<a id="sync_customers" class="button" href="#">Sync Customers to Benchmark Email</a>
					</p>
					<p>
						<span id="sync_in_progress" style="display:none;">
							' . sprintf(
								"<strong>%s</strong> %s",
								__( 'Please wait.', 'woo-benchmark-email' ),
								__( 'Syncing at 10 orders per page, completed pages...', 'woo-benchmark-email' )
							) . '
						</span>
						<span id="sync_progress_bar"></span>
						<span id="sync_complete" style="display:none;">
							' . __( 'Finished Customer Sync.', 'woo-benchmark-email' ) . '
						</span>
					</p>
				',
				'desc_tip' => __( 'This will sync all historic customers to Benchmark Email.', 'woo-benchmark-email' ),
				'id' => 'bmew_sync',
				'name' => __( 'Sync historic customers', 'woo-benchmark-email' ),
				'type' => 'checkbox',
			),

			// End Section
			array( 'id' => 'bmew', 'type' => 'sectionend' ),
		);
	}

	// AJAX Load Script
	static function admin_enqueue_scripts() {
		wp_enqueue_script( 'bmew_admin', plugin_dir_url( __FILE__ ) . 'admin.js', array( 'jquery' ), null );
	}

	// Customer Sync AJAX Submit
	static function wp_ajax__bmew_action__sync_customers() {

		// Find Appropriate Contact List
		$key = get_option( 'bmew_key' );
		$lists = get_option( 'bmew_lists' );
		$listID = isset( $lists[$key]['customers'] ) ? $lists[$key]['customers'] : false;
		if( ! $listID ) { return; }

		// Query Orders Not Already Sync'd
		$page = empty( $_POST['page'] ) ? 1 : intval( $_POST['page'] );
		$args = array(
			'limit' => 10,
			'meta_compare' => 'NOT EXISTS',
			'meta_key' => '_bmew_syncd',
			'order' => 'ASC',
			'orderby' => 'ID',
			'page' => $page,
			'return' => 'ids',
		);
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

			// Get Cart Items
			$products = bmew_frontend::get_products( $_order );

			// Add Contact To List
			$args = array(
				'first' => get_post_meta( $order_id, '_billing_first_name', true ),
				'last' => get_post_meta( $order_id, '_billing_last_name', true ),
				'product1' => isset( $products[0] ) ? $products[0] : '',
				'product2' => isset( $products[1] ) ? $products[1] : '',
				'product3' => isset( $products[2] ) ? $products[2] : '',
				'total' => get_woocommerce_currency_symbol() . $_order->get_total(),
				'url' => $_order->get_view_order_url(),

				// Order Details
				'phone' => get_post_meta( $order_id, '_billing_phone', true ),
				'company' => get_post_meta( $order_id, '_billing_company', true ),
				'b_address' => sprintf(
					'%s %s',
					get_post_meta( $order_id, '_billing_address_1', true )
					get_post_meta( $order_id, '_billing_address_2', true )
				),
				'b_city' => get_post_meta( $order_id, '_billing_city', true ),
				'b_state' => get_post_meta( $order_id, '_billing_state', true ),
				'b_zip' => get_post_meta( $order_id, '_billing_postcode', true ),
				'b_country' => get_post_meta( $order_id, '_billing_country', true ),
				's_address' => sprintf(
					'%s %s',
					get_post_meta( $order_id, '_shipping_address_1', true )
					get_post_meta( $order_id, '_shipping_address_2', true )
				),
				's_city' => get_post_meta( $order_id, '_shipping_city', true ),
				's_state' => get_post_meta( $order_id, '_shipping_state', true ),
				's_zip' => get_post_meta( $order_id, '_shipping_postcode', true ),
				's_country' => get_post_meta( $order_id, '_shipping_country', true ),
			);
			$response = bmew_api::add_contact( $listID, $email, $args );

			// If Successful, Mark Order As Sync'd
			if( intval( $response ) > 0 ) {
				update_post_meta( $order_id, '_bmew_syncd', current_time( 'timestamp' ) );
			}
		}

		// Handle Finish
		if( ! $orders ) { $page = 0; }

		// Exit With Progress Level
		echo $page;
		wp_die();
	}
}
