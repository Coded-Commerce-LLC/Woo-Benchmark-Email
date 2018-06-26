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

	// Hooked Into WooCommerce Checkout Submission
	static function woocommerce_after_checkout_validation( $data ) {

		// Get Fields
		$email = isset( $data['billing_email'] ) ? $data['billing_email'] : '';
		$first = isset( $data['billing_first_name'] ) ? $data['billing_first_name'] : '';
		$last = isset( $data['billing_last_name'] ) ? $data['billing_last_name'] : '';

		// Exit If No Email
		if( ! $email ) { return; }

		// Find Contact List
		$listID = bmew_frontend::match_list( 'customers' );
		if( ! $listID ) { return; }

		// Add Contact
		bmew_api::add_contact( $listID, $email, $first, $last );
	}

}
