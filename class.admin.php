<?php

// Administrative / Settings Class
class bmew_admin {

	// Admin Dashboard Test Function
	static function wp_dashboard_setup() {
		//$response = get_option( 'bmew_lists' );
		//$response = bmew_api::add_list( 'WooCommerce Customers' );
		//$response = bmew_api::get_lists();
		//$response = bmew_api::get_contact( 3649970, 211633335 );
		//$response = bmew_api::add_contact( 3649970, 'sean+test04@codedcommerce.com', 'Tester', 'Testing' );
		//echo sprintf(
		//	'<div class="notice notice-info is-dismissible"><p><pre>%s</pre></p></div>',
		//	print_r( $response, true )
		//);
	}

	// Create The Section Beneath The Advanced Tab
	static function woocommerce_get_sections_advanced( $sections ) {
		$sections['bmew'] = 'Benchmark Email';
		return $sections;
	}

	// Output Sync UI
	static function woocommerce_settings_bmew() {
		echo '
			<div class="notice notice-info is-dismissible">
				<p>
					<a id="sync_customers" class="button" href="#">Sync Customers to Benchmark Email</a>
				</p>
				<p>
					<span id="sync_in_progress" style="display:none;">
						' . sprintf(
							"<strong>%s</strong> %s",
							__( 'Please wait.', 'bmew' ),
							__( 'Syncing at 10 orders per page, completed pages...', 'bmew' )
						) . '
					</span>
					<span id="sync_progress_bar"></span>
					<span id="sync_complete" style="display:none;">
						' . __( 'Finished Customer Sync.', 'bmew' ) . '
					</span>
				</p>
			</div>
		';
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
				'desc_tip' => __( 'Log into https://ui.benchmarkemail.com and copy your API key here.', 'bmew' ),
				'desc' => '<br>' . __( 'API Key from your Benchmark Email account', 'bmew' ),
				'id' => 'bmew_key',
				'name' => __( 'API Key', 'bmew' ),
				'type' => 'text',
			),

			// Add Text Field Option
			array(
				'default' => __( 'Opt-in to receive exclusive customer communications', 'bmew' ),
				'desc_tip' => __( 'Label for checkout form opt-in checkbox field.', 'bmew' ) . ' '
					. __( 'Leave this setting blank to eliminate the opt-in field from your checkout form.', 'bmew' ),
				'desc' => '<br>' . __( 'Checkout form opt-in field label', 'bmew' ),
				'id' => 'bmew_checkout_optin_label',
				'name' => __( 'Checkout Opt-In Field', 'bmew' ),
				'type' => 'text',
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
		$listID = bmew_frontend::match_list( 'customers' );
		if( ! $listID ) { return; }

		// Query Orders
		$page = empty( $_POST['page'] ) ? 1 : intval( $_POST['page'] );
		$args = array(
			'limit' => 10,
			'page' => $page,
			'orderby' => 'ID',
			'order' => 'DESC',
			'return' => 'ids',
		);
		$query = new WC_Order_Query( $args );
		$orders = $query->get_orders();

		// Loop Results
		foreach( $orders as $post_id ) {

			// Get Fields From Order
			$email = get_post_meta( $post_id, '_billing_email', true );
			$first = get_post_meta( $post_id, '_billing_first_name', true );
			$last = get_post_meta( $post_id, '_billing_last_name', true );

			// Exit If No Email Provided
			if( ! $email ) { continue; }

			// Add Contact To List
			bmew_api::add_contact( $listID, $email, $first, $last );
		}
		if( ! $orders ) { $page = 0; }

		// Exit
		echo $page;
		wp_die();
	}
}
