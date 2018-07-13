<?php

// Front End Plugin Logic
class bmew_frontend {

	// Class Properties
	static $all_lists = array();
	static $list_names = array(
		'abandons' => 'WooCommerce Abandoned Carts',
		'customers' => 'WooCommerce Customers',
	);

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
		if( ! empty( $lists['abandons'] ) && ! empty( $lists['customers'] ) ) { return; }

		// Not Already Set-Up
		if( ! is_array( $lists ) ) { $lists = array(); }
		$updated = false;

		// Check For Abandons List
		if( empty( $lists['abandons'] ) ) {
			$updated = true;
			$lists['abandons'] = bmew_frontend::match_list( 'abandons' );
		}

		// Check For Registered Customers List
		if( empty( $lists['customers'] ) ) {
			$updated = true;
			$lists['customers'] = bmew_frontend::match_list( 'customers' );
		}

		// Update Stored Setting
		if( $updated ) {
			update_option( 'bmew_lists', $lists );
		}
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

		// Find Appropriate Contact List
		$listID = bmew_frontend::match_list( 'abandons' );
		if( ! $listID ) { return; }

		// Get Fields From Order
		$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$first = isset( $_POST['first'] ) ? sanitize_text_field( $_POST['first'] ) : '';
		$last = isset( $_POST['last'] ) ? sanitize_text_field( $_POST['last'] ) : '';

		// Exit If No Email Provided
		if( ! $email ) { return; }

		// Add Contact To List
		bmew_api::add_contact( $listID, $email, $first, $last );

		// Exit
		echo $email;
		wp_die();
	}

	// Filter WooCommerce Checkout Fields
	static function woocommerce_checkout_fields( $fields ) {

		// Get Opt-In Field Label Setting
		$bmew_checkout_optin_label = get_option( 'bmew_checkout_optin_label' );

		// If Opt-In Unset, Skip It
		if( ! $bmew_checkout_optin_label ) { return $fields; }

		// Add Opt-In Form Field
		$fields['billing']['bmew_subscribe'] = array(
			'class' => array( 'form-row-wide' ),
			'default' => true,
			'label' => $bmew_checkout_optin_label,
			'priority' => 120,
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
		$first = isset( $_POST['billing_first_name'] ) ? $_POST['billing_first_name'] : '';
		$last = isset( $_POST['billing_last_name'] ) ? $_POST['billing_last_name'] : '';

		// Exit If No Email Provided
		if( ! $email ) { return; }

		// Find Appropriate Contact List
		$listID = bmew_frontend::match_list( 'customers' );
		if( ! $listID ) { return; }

		// Add Contact To List
		bmew_api::add_contact( $listID, $email, $first, $last );
	}

	// Helper To Match a Contact List
	static function match_list( $list_slug ) {

		// Load Lists, If Not Already Loaded
		if( ! bmew_frontend::$all_lists ) {
			bmew_frontend::$all_lists = bmew_api::get_lists();
		}

		// Loop Contact Lists
		foreach( bmew_frontend::$all_lists as $list ) {

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
