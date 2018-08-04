<?php

// Exit If Accessed Directly
if( ! defined( 'ABSPATH' ) ) { exit; }

// Front End Plugin Logic
class bmew_frontend {

	// Class Properties
	static $list_names = array(
		'abandons' => 'WooCommerce Abandoned Carts',
		'customers' => 'WooCommerce Customers',
	);

	// Load Translations
	static function plugins_loaded() {
		load_plugin_textdomain( 'benchmark-email-woo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	// AJAX Load Script
	static function wp_enqueue_scripts() {
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() ) { return; }
		wp_enqueue_script( 'bmew_frontend', plugin_dir_url( __FILE__ ) . 'frontend.js', array( 'jquery' ), null );
		wp_localize_script( 'bmew_frontend', 'bmew_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}

	// Initialize Contact Lists
	static function init_contact_lists() {

		// Exit If No API Key Is Set
		$key = get_option( 'bmew_key' );
		if( empty( $key ) ) { return; }

		// Exit If Already Set-Up
		$lists = get_option( 'bmew_lists' );
		if(
			! empty( $lists[$key]['handshake'] )
			&& ! empty( $lists[$key]['abandons'] )
			&& ! empty( $lists[$key]['customers'] )
		) {
			return;
		}

		// Not Already Set-Up
		if( ! is_array( $lists ) ) { $lists = array(); }

		// Register Vendor With API Key
		if( empty( $lists[$key]['handshake'] ) ) {
			bmew_api::benchmark_query_legacy( 'UpdatePartner', $key, 'beautomated' );
			$lists[$key]['handshake'] = current_time( 'timestamp' );
		}

		// Check For Abandons List
		if( empty( $lists[$key]['abandons'] ) ) {
			$lists[$key]['abandons'] = bmew_frontend::match_list( 'abandons' );
		}

		// Check For Registered Customers List
		if( empty( $lists[$key]['customers'] ) ) {
			$lists[$key]['customers'] = bmew_frontend::match_list( 'customers' );
		}

		// Update Stored Setting
		update_option( 'bmew_lists', $lists );
	}

	// Add To Cart Redirects To Checkout
	static function woocommerce_add_to_cart_redirect( $wc_cart_url ) {
		global $woocommerce;
		$bmew_skip_cart = get_option( 'bmew_skip_cart' );
		if( $bmew_skip_cart != 'yes' ) { return $wc_cart_url; }
		return wc_get_checkout_url();
	}

	// Reorder checkout contact fields
	static function woocommerce_billing_fields( $fields ) {
		$bmew_checkout_reorder = get_option( 'bmew_checkout_reorder' );
		if( $bmew_checkout_reorder != 'yes' ) { return $fields; }
		$fields['billing_email']['priority'] = 21;
		$fields['billing_phone']['priority'] = 29;
		return $fields;
	}

	// AJAX Routing
	static function wp_ajax__bmew_action() {

		// Verify Action Is Requested
		if( empty( $_POST['sync'] ) ) { return; }

		// Back End Routing
		if( $_POST['sync'] == 'sync_customers' ) {
			bmew_admin::wp_ajax__bmew_action__sync_customers();
		}

		// Front End Routing
		else if( $_POST['sync'] == 'abandoned_cart' ) {
			return bmew_frontend::wp_ajax__bmew_action__abandoned_cart();
		}
	}

	// Abandoned Cart Submission
	static function wp_ajax__bmew_action__abandoned_cart() {
		global $woocommerce;

		// Find Appropriate Contact List
		$key = get_option( 'bmew_key' );
		$lists = get_option( 'bmew_lists' );
		$listID = $lists[$key]['abandons'];
		if( ! $listID ) { return; }

		// Get Fields From Order
		$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

		// Exit If No Email Provided
		if( ! $email ) { return; }

		// Get Cart Items
		$products = bmew_frontend::get_products();

		// Add Contact To List
		$args = array(
			'first' => isset( $_POST['billing_first_name'] ) ? sanitize_text_field( $_POST['billing_first_name'] ) : '',
			'last' => isset( $_POST['billing_last_name'] ) ? sanitize_text_field( $_POST['billing_last_name'] ) : '',
			'product1' => isset( $products[0] ) ? $products[0] : '',
			'product2' => isset( $products[1] ) ? $products[1] : '',
			'product3' => isset( $products[2] ) ? $products[2] : '',
			'total' => get_woocommerce_currency_symbol() . $woocommerce->cart->total,
			'url' => wc_get_cart_url(),
		);
		print_r( bmew_api::add_contact( $listID, $email, $args ) );

		// Exit
		wp_die();
	}

	// Get Cart Details
	static function get_products() {
		global $woocommerce;
		$products = array();
		foreach( $woocommerce->cart->get_cart() as $item ) {
			$_product = wc_get_product( $item['product_id'] );
			$products[] = $_product->get_title()
				. ', quantity ' . $item['quantity']
				. ', price ' . get_woocommerce_currency_symbol()
				. get_post_meta( $item['product_id'] , '_price', true );
		}
		return $products;
	}

	// Filter WooCommerce Checkout Fields
	static function woocommerce_checkout_fields( $fields ) {

		// Get Opt-In Field Label Setting
		$bmew_checkout_optin_label = get_option( 'bmew_checkout_optin_label' );

		// If Opt-In Unset, Skip It
		if( ! $bmew_checkout_optin_label ) { return $fields; }

		// Determine Field Display Priority
		$bmew_checkout_reorder = get_option( 'bmew_checkout_reorder' );
		$priority = $bmew_checkout_reorder == 'yes' ? 22 : 122;

		// Add Opt-In Form Field
		$fields['billing']['bmew_subscribe'] = array(
			'class' => array( 'form-row-wide' ),
			'default' => true,
			'label' => $bmew_checkout_optin_label,
			'priority' => $priority,
			'required' => false,
			'type' => 'checkbox',
    	);

    	// Return Data
		return $fields;
	}

	// At Order Creation Save Custom Checkout Fields
	static function woocommerce_checkout_update_order_meta( $order_id ) {

		// Proceed Only If Subscribe Selected
		if( empty( $_POST['bmew_subscribe'] ) || $_POST['bmew_subscribe'] !== '1' ) { return; }

		// Save Subscription Action To Order
		update_post_meta( $order_id, '_bmew_subscribed', 'yes' );

		// Get Fields
		$email = isset( $_POST['billing_email'] ) ? $_POST['billing_email'] : '';

		// Exit If No Email Provided
		if( ! $email ) { return; }

		// Get Lists
		$key = get_option( 'bmew_key' );
		$lists = get_option( 'bmew_lists' );

		// Remove From Abandons List
		$listID = $lists[$key]['abandons'];
		bmew_api::delete_contact_by_email( 'abandons', $listID, $email );

		// Find Customers List
		$listID = $lists[$key]['customers'];
		if( ! $listID ) { return; }

		// Get Cart Items
		$products = bmew_frontend::get_products();

		// Get Order Record
		$_order = wc_get_order( $order_id );

		// Add Contact To List
		$args = array(
			'first' => isset( $_POST['billing_first_name'] ) ? sanitize_text_field( $_POST['billing_first_name'] ) : '',
			'last' => isset( $_POST['billing_last_name'] ) ? sanitize_text_field( $_POST['billing_last_name'] ) : '',
			'product1' => isset( $products[0] ) ? $products[0] : '',
			'product2' => isset( $products[1] ) ? $products[1] : '',
			'product3' => isset( $products[2] ) ? $products[2] : '',
			'total' => get_woocommerce_currency_symbol() . $_order->get_total(),
			'url' => $_order->get_view_order_url(),
		);
		bmew_api::add_contact( $listID, $email, $args );
	}

	// Helper To Match a Contact List
	static function match_list( $list_slug ) {

		// Load Lists, If Not Already Loaded
		$lists = bmew_api::get_lists();

		// Loop Contact Lists
		foreach( $lists as $list ) {

			// Skip Bad Result
			if( empty( $list->ID ) || empty( $list->Name ) ) { continue; }

			// Handle a Match
			if( $list->Name == bmew_frontend::$list_names[$list_slug] ) {
				return $list->ID;
			}
		}

		// Add Missing Contact List
		return bmew_api::add_list( bmew_frontend::$list_names[$list_slug] );
	}

}
