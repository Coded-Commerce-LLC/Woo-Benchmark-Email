<?php

// Exit If Accessed Directly
if( ! defined( 'ABSPATH' ) ) { exit; }

// Administrative / Settings Class
class bmew_admin {

	// Admin Dashboard Diagnostics Function
	static function wp_dashboard_setup() {

		/*
			Diagnostics
		*/
		$response = get_option( 'bmew_lists' );
		//$response = bmew_api::add_list( 'WooCommerce Test List' );
		//$response = bmew_api::get_lists();
		//$response = bmew_frontend::match_list( 'abandons' );
		//$response = bmew_api::get_contact( ###, ### );
		//$response = bmew_api::add_contact( ###, 'sean_test01@codedcommerce.com', [ 'first' => 'Test' ] );
		//$response = bmew_api::delete_contact( ###, ### );
		//$response = bmew_api::find_contact( 'sean+test01@codedcommerce.com' );

		// Output Diagnostic Results
		echo sprintf(
			'<div class="notice notice-info is-dismissible"><p><pre>%s</pre></p></div>',
			print_r( $response, true )
		);
	}

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
				'desc' => '<br>' . __( 'Skips the cart step and redirects customers to the checkout form that conveniently displays a mini cart.', 'benchmark-email-woo' )
					. '<br>' . __( "If they need to edit their cart, they will have to click on your theme's cart link in order to do so.", 'benchmark-email-woo' ),
				'desc_tip' => __( 'This may improve the chances of the email address being provided by customers and thus available to abandoned cart offers.', 'benchmark-email-woo' ),
				'id' => 'bmew_skip_cart',
				'name' => __( 'Skip the cart step', 'benchmark-email-woo' ),
				'type' => 'checkbox',
			),

			// Add API Key Field
			array(
				'desc' => '<br>' . __( 'Moves the email address and phone number fields up and underneath the name fields.', 'benchmark-email-woo' ),
				'desc_tip' => __( 'This may improve the chances of the email address being provided by customers and thus available to abandoned cart offers.', 'benchmark-email-woo' ),
				'id' => 'bmew_checkout_reorder',
				'name' => __( 'Move email field up', 'benchmark-email-woo' ),
				'type' => 'checkbox',
			),

			// Add Text Field Option
			array(
				'default' => __( 'Opt-in to receive exclusive customer communications', 'benchmark-email-woo' ),
				'desc' => '<br>' . __( 'Checkout form opt-in field label', 'benchmark-email-woo' ),
				'desc_tip' => __( 'Label for checkout form opt-in checkbox field.', 'benchmark-email-woo' ) . ' '
					. __( 'Leave this setting blank to eliminate the opt-in field from your checkout form.', 'benchmark-email-woo' ),
				'id' => 'bmew_checkout_optin_label',
				'name' => __( 'Checkout Opt-In Field', 'benchmark-email-woo' ),
				'type' => 'text',
			),

			// Add API Key Field
			array(
				'desc' => '<br>' . __( 'API Key from your Benchmark Email account', 'benchmark-email-woo' ),
				'desc_tip' => __( 'Log into https://ui.benchmarkemail.com and copy your API key here.', 'benchmark-email-woo' ),
				'id' => 'bmew_key',
				'name' => __( 'API Key', 'benchmark-email-woo' ),
				'type' => 'text',
			),

			// Add API Key Field
			array(
				'desc' => '<br>' . __( 'For temporary use, saves all API communications into WooCommerce > Status > Logs.', 'benchmark-email-woo' ),
				'desc_tip' => __( "For a nicer logs UI, set `define( 'WC_LOG_HANDLER', 'WC_Log_Handler_DB' );` inside your  `wp-config.php`.", 'benchmark-email-woo' ),
				'id' => 'bmew_debug',
				'name' => __( 'Log debug messages?', 'benchmark-email-woo' ),
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
								__( 'Please wait.', 'benchmark-email-woo' ),
								__( 'Syncing at 10 orders per page, completed pages...', 'benchmark-email-woo' )
							) . '
						</span>
						<span id="sync_progress_bar"></span>
						<span id="sync_complete" style="display:none;">
							' . __( 'Finished Customer Sync.', 'benchmark-email-woo' ) . '
						</span>
					</p>
				',
				'desc_tip' => __( 'This will sync all historic customers to Benchmark Email.', 'benchmark-email-woo' ),
				'id' => 'bmew_sync',
				'name' => __( 'Sync historic customers', 'benchmark-email-woo' ),
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
