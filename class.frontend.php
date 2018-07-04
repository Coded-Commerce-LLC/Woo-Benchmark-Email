<?php

// Front End Plugin Logic
class bmew_frontend {

	// Class Properties
	static $all_lists = array();
	static $list_names = array(
		'abandons' => 'WooCommerce Abandoned Carts',
		'customers' => 'WooCommerce Customers',
	);

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
