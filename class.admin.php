<?php

// Administrative / Settings Class
class bmew_admin {

	// Admin Dashboard Test Function
	static function wp_dashboard_setup() {
		$response = 'Placeholder';
		$response = get_option( 'bmew_lists' );
		//$response = bmew_api::add_list( 'WooCommerce Customers' );
		//$response = bmew_api::get_lists();
		//$response = bmew_api::get_contact( 3649970, 211633335 );
		//$response = bmew_api::add_contact( 3649970, 'sean+test04@codedcommerce.com', 'Tester', 'Testing' );
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

		$settings = array();

		// Add Title To The Settings
		$settings[] = array( 'desc' => '', 'id' => 'bmew', 'name' => 'Benchmark Email', 'type' => 'title' );

		// Add Text Field Option
		$settings[] = array(
			'desc_tip' => __( 'Log into https://ui.benchmarkemail.com', 'bmew' ),
			'desc' => '<br>' . __( 'Enter your API Key from your Benchmark Email account.', 'bmew' ),
			'id' => 'bmew_key',
			'name' => __( 'API Key', 'bmew' ),
			'type' => 'text',
		);
		$settings[] = array( 'id' => 'bmew', 'type' => 'sectionend' );
		return $settings;
	}
}
