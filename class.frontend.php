<?php


// Exit If Accessed Directly
if( ! defined( 'ABSPATH' ) ) { exit; }


// Load Scripts
add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_script(
		'bmew_frontend', plugin_dir_url( __FILE__ ) . 'frontend.js', [ 'jquery' ], null
	);
	wp_localize_script(
		'bmew_frontend', 'bmew_ajax_object', [ 'ajax_url' => admin_url( 'admin-ajax.php' ) ]
	);
} );


// AJAX Handler
add_action( 'wp_ajax_bmew_action', 'wp_ajax__bmew_action' );
add_action( 'wp_ajax_nopriv_bmew_action', 'wp_ajax__bmew_action' );
function wp_ajax__bmew_action() {

	// Sync Action Is Requested
	if( empty( $_POST['sync'] ) ) { return; }
	switch( $_POST['sync'] ) {


		// API Key
		case 'get_api_key':
			if( empty( $_POST['user'] ) || empty( $_POST['pass'] ) ) { return; }
			$response = bmew_api::get_api_key( $_POST['user'], $_POST['pass'] );
			echo $response ? $response : __( 'Error - Please try again', 'woo-benchmark-email' );
			wp_die();


		// Customer Sync
		case 'sync_customers':

			// Find Appropriate Contact List
			$listID = bmew_frontend::match_list( 'customers' );
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
			echo $page;

			// Exit
			wp_die();


		// Abandoned Cart
		case 'abandoned_cart':
			global $woocommerce;

			// Find Appropriate Contact List
			$listID = bmew_frontend::match_list( 'abandons' );
			if( ! $listID ) { return; }

			// Get Fields From Order
			$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

			// Skip If No Email Provided
			if( ! $email ) { return; }

			// Get Cart Items
			$products = bmew_frontend::get_products();

			// Add Contact To List
			$args = [
				'first' => isset( $_POST['billing_first_name'] )
					? sanitize_text_field( $_POST['billing_first_name'] ) : '',
				'last' => isset( $_POST['billing_last_name'] )
					? sanitize_text_field( $_POST['billing_last_name'] ) : '',
				'product1' => isset( $products[0] ) ? $products[0] : '',
				'product2' => isset( $products[1] ) ? $products[1] : '',
				'product3' => isset( $products[2] ) ? $products[2] : '',
				'total' => get_woocommerce_currency_symbol() . $woocommerce->cart->total,
				'url' => wc_get_cart_url(),
			];
			$response = bmew_api::add_contact( $listID, $email, $args );

			// Dev Analytics
			bmew_api::tracker( 'abandon-checkout' );

			// Exit
			wp_die();
	}

}


// Reorder Checkout Contact Fields
add_filter( 'woocommerce_billing_fields', function( $fields ) {
	$bmew_checkout_reorder = get_option( 'bmew_checkout_reorder' );
	if( $bmew_checkout_reorder != 'yes' ) { return $fields; }
	$fields['billing_email']['priority'] = 21;
	$fields['billing_phone']['priority'] = 29;
	return $fields;
} );


// Filter WooCommerce Checkout Fields - Moves Email Field Up
add_filter( 'woocommerce_checkout_fields', function( $fields ) {

	// Get Opt-In Field Label Setting
	$bmew_checkout_optin_label = get_option( 'bmew_checkout_optin_label' );

	// If Opt-In Unset, Skip It
	if( ! $bmew_checkout_optin_label ) { return $fields; }

	// Determine Field Display Priority
	$bmew_checkout_reorder = get_option( 'bmew_checkout_reorder' );
	$priority = $bmew_checkout_reorder == 'yes' ? 22 : 122;

	// Add Opt-In Form Field
	$fields['billing']['bmew_subscribe'] = [
		'class' => [ 'form-row-wide' ],
		'default' => true,
		'label' => $bmew_checkout_optin_label,
		'priority' => $priority,
		'required' => false,
		'type' => 'checkbox',
	];

	// Return Data
	return $fields;
} );


// At Order Creation - Save Custom Checkout Fields
add_action( 'woocommerce_checkout_update_order_meta', function( $order_id ) {

	// Get Email Field
	$email = isset( $_POST['billing_email'] ) ? $_POST['billing_email'] : '';

	// Skip If No Email Provided
	if( ! $email ) { return; }

	// Remove From Abandons List
	$listID = bmew_frontend::match_list( 'abandons' );
	bmew_api::delete_contact_by_email( $listID, $email );

	// Proceed Only If Opt-In Is Selected Or Missing
	if( isset( $_POST['bmew_subscribe'] ) && $_POST['bmew_subscribe'] !== '1' ) { return; }

	// Find Customers List
	$listID = bmew_frontend::match_list( 'customers' );
	if( ! $listID ) { return; }

	// Save Subscription Action To Order
	update_post_meta( $order_id, '_bmew_subscribed', 'yes' );

	// Get Order Details
	$args = bmew_frontend::get_order_details( $order_id, $email );

	// Add Contact To List
	bmew_api::add_contact( $listID, $email, $args );

	// Dev Analytics
	bmew_api::tracker( 'add-customer' );
} );


// Hooked Into Woo Add To Cart - Capture Abandons
add_action( 'woocommerce_add_to_cart', function() {
	global $woocommerce;

	// Logged In Users Or Previous Woo Sessions Only
	$session_customer = $woocommerce->session->get( 'customer' );
	$email = isset( $session_customer[ 'email' ] ) ? $session_customer[ 'email' ] : '';
	$last_name = isset( $session_customer[ 'last_name' ] ) ? $session_customer[ 'last_name' ] : '';
	$first_name = isset( $session_customer[ 'first_name' ] ) ? $session_customer[ 'first_name' ] : '';

	// Skip If No Email Provided
	if( ! $email ) { return; }

	// Find Appropriate Contact List
	$listID = bmew_frontend::match_list( 'abandons' );
	if( ! $listID ) { return; }

	// Get Cart Items
	$products = bmew_frontend::get_products();

	// Add Contact To List
	$args = [
		'first' => $first_name,
		'last' => $last_name,
		'product1' => isset( $products[0] ) ? $products[0] : '',
		'product2' => isset( $products[1] ) ? $products[1] : '',
		'product3' => isset( $products[2] ) ? $products[2] : '',
		'total' => get_woocommerce_currency_symbol() . $woocommerce->cart->total,
		'url' => wc_get_cart_url(),
	];
	bmew_api::add_contact( $listID, $email, $args );

	// Dev Analytics
	bmew_api::tracker( 'abandon-cart' );
} );


// Add To Cart Redirects To Checkout
add_filter( 'woocommerce_add_to_cart_redirect', function( $wc_cart_url ) {
	global $woocommerce;
	$bmew_skip_cart = get_option( 'bmew_skip_cart' );
	if( $bmew_skip_cart != 'yes' ) { return $wc_cart_url; }
	return wc_get_checkout_url();
} );


// Front End Plugin Logic
class bmew_frontend {


	// Get Cart Details - Helper Function
	static function get_products( $order_id = false ) {

		// Using Order Object
		if( $order_id ) {
			$_order = wc_get_order( $order_id );
			$items = $_order->get_items();
		}

		// Using Cart Session
		else {
			global $woocommerce;
			$items = $woocommerce->cart->get_cart();
		}

		// Loop Order Items
		$products = [];
		foreach( $items as $item ) {
			$_product = wc_get_product( $item['product_id'] );
			$products[] = $_product->get_title()
				. ', quantity ' . $item['quantity']
				. ', price ' . get_woocommerce_currency_symbol()
				. get_post_meta( $item['product_id'] , '_price', true );
		}

		// Return Products
		return $products;
	}


	// Get Order Details - Helper Function
	static function get_order_details( $order_id, $email ) {

		// Get Order Object
		$_order = wc_get_order( $order_id );

		// Get Cart Items
		$products = bmew_frontend::get_products( $order_id );

		// Get Order History
		$total_spent = 0;
		$order_timestamps = [];
		$order_history = get_posts( [
			'meta_key' => '_billing_email',
			'meta_value' => $email,
			'numberposts' => -1,
			'post_status' => [ 'wc-processing', 'wc-completed', 'wc-on-hold' ],
			'post_type' => wc_get_order_types(),
		] );
		if( is_array( $order_history ) ) {
			foreach( $order_history as $post ) {
				$order_historic = wc_get_order( $post->ID );
				$total_spent += $order_historic->get_total();
				$order_date = $order_historic->get_date_created();
				$order_timestamps[] = $order_date->getTimestamp();
			}
		}

		// Output
		return [

			// Order Details
			'company' => get_post_meta( $order_id, '_billing_company', true ),
			'first' => get_post_meta( $order_id, '_billing_first_name', true ),
			'last' => get_post_meta( $order_id, '_billing_last_name', true ),
			'phone' => get_post_meta( $order_id, '_billing_phone', true ),
			'product1' => isset( $products[0] ) ? $products[0] : '',
			'product2' => isset( $products[1] ) ? $products[1] : '',
			'product3' => isset( $products[2] ) ? $products[2] : '',
			'total' => get_woocommerce_currency_symbol() . $_order->get_total(),
			'url' => $_order->get_view_order_url(),

			// Order History
			'first_order_date' => date( 'c', min( $order_timestamps ) ),
			'total_spent' => number_format( $total_spent, 2 ),
			'total_orders' => sizeof( $order_history ),

			// Billing Address
			'b_address' => sprintf(
				'%s %s',
				get_post_meta( $order_id, '_billing_address_1', true ),
				get_post_meta( $order_id, '_billing_address_2', true )
			),
			'b_city' => get_post_meta( $order_id, '_billing_city', true ),
			'b_state' => get_post_meta( $order_id, '_billing_state', true ),
			'b_zip' => get_post_meta( $order_id, '_billing_postcode', true ),
			'b_country' => get_post_meta( $order_id, '_billing_country', true ),

			// Shipping Address
			's_address' => sprintf(
				'%s %s',
				get_post_meta( $order_id, '_shipping_address_1', true ),
				get_post_meta( $order_id, '_shipping_address_2', true )
			),
			's_city' => get_post_meta( $order_id, '_shipping_city', true ),
			's_state' => get_post_meta( $order_id, '_shipping_state', true ),
			's_zip' => get_post_meta( $order_id, '_shipping_postcode', true ),
			's_country' => get_post_meta( $order_id, '_shipping_country', true ),
		];
	}


	// Match a Contact List - Helper Function
	static function match_list( $list_slug ) {

		// Default List Names
		$default_list_names = [
			'abandons' => [
				strtolower( 'WooCommerce Abandoned Carts' ),
				strtolower( 'Carritos Abandonados de WooCommerce' ),
				strtolower( 'Carrinhos Abandonados do WooCommerce' ),
				strtolower( 'WooCommerce カゴ落ち' ),
			],
			'customers' => [
				strtolower( 'WooCommerce Customers' ),
				strtolower( 'Clientes de WooCommerce' ),
				strtolower( 'Clientes do WooCommerce' ),
				strtolower( 'WooCommerce ユーザー' ),
			],
		];
 
		// Load Lists, If Not Already Loaded
		$lists = bmew_api::get_lists();
		if( ! is_array( $lists ) ) { return false; }

		// Loop Contact Lists
		foreach( $lists as $list ) {
			if( empty( $list->ID ) || empty( $list->Name ) ) { continue; }
			foreach( $default_list_names[$list_slug] as $default_list_name ) {
				if( strtolower( $list->Name ) == $default_list_name ) {
					return $list->ID;
				}
			}
		}

		// Add Missing Contact List
		return bmew_api::add_list( $default_list_names[$list_slug][0] );
	}

}