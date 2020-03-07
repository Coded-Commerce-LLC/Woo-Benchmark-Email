<?php

// Exit If Accessed Directly
if( ! defined( 'ABSPATH' ) ) { exit; }

// ReST API Class
class bmew_api {


	// Endpoint
	static $url = 'https://clientapi.benchmarkemail.com/';
	static $url_tracker = 'https://ssl.google-analytics.com/';


	// Developer Analytics
	static function tracker( $action, $value='' ) {
 		$bmew_usage_disable = get_option( 'bmew_usage_disable' );
 		if( $bmew_usage_disable == '1' ) { return; }
 		$body = [
 			'v' => 1,
 			'tid' => 'UA-120661799-1',
 			'cid' => get_current_user_id(),
 			't' => 'event',
 			'ec' => 'BMEW1',
 			'ea' => $action,
 			'el' => ucwords( $action ),
 			'ev' => $value,
 		];
 		$args = [ 'body' => $body ];
 		$url = self::$url_tracker . 'collect';
 		$response = wp_remote_post( $url, $args );
 	}


	// Adds a Contact To a List
	static function add_contact( $listID, $email, $args = [] ) {
		extract( $args );

		// Build Body
		$body = [
			'Data' => [
				'Email' => $email,
				'EmailPerm' => 1,
				'Field19' => current_time( 'm/d/Y' ),
				'IPAddress' => bmew_api::get_client_ip(),
			],
		];

		// Order Details
		if( isset( $company ) ) { $body['Data']['Field9'] = $company; }
		if( isset( $first ) ) { $body['Data']['FirstName'] = $first; }
		if( isset( $last ) ) { $body['Data']['LastName'] = $last; }
		if( isset( $phone ) ) { $body['Data']['Field6'] = $phone; }
		if( isset( $product1 ) ) { $body['Data']['Field21'] = $product1; }
		if( isset( $product2 ) ) { $body['Data']['Field22'] = $product2; }
		if( isset( $product3 ) ) { $body['Data']['Field23'] = $product3; }
		if( isset( $total ) ) { $body['Data']['Field24'] = $total; }
		if( isset( $url ) ) { $body['Data']['Field18'] = $url; }

		// Order History
		if( isset( $first_order_date ) ) { $body['Data']['Field25'] = $first_order_date; }
		if( isset( $total_spent ) ) { $body['Data']['Field26'] = $total_spent; }
		if( isset( $total_orders ) ) { $body['Data']['Field27'] = $total_orders; }

		// Billing Address
		if( isset( $b_address ) ) { $body['Data']['Field1'] = $b_address; }
		if( isset( $b_city ) ) { $body['Data']['Field2'] = $b_city; }
		if( isset( $b_state ) ) { $body['Data']['Field3'] = $b_state; }
		if( isset( $b_zip ) ) { $body['Data']['Field4'] = $b_zip; }
		if( isset( $b_country ) ) { $body['Data']['Field5'] = $b_country; }

		// Shipping Address
		if( isset( $s_address ) ) { $body['Data']['Field13'] = $s_address; }
		if( isset( $s_city ) ) { $body['Data']['Field14'] = $s_city; }
		if( isset( $s_state ) ) { $body['Data']['Field15'] = $s_state; }
		if( isset( $s_zip ) ) { $body['Data']['Field16'] = $s_zip; }
		if( isset( $s_country ) ) { $body['Data']['Field17'] = $s_country; }

		// Search Existing Contacts
		$matches = bmew_api::find_contact( $email );
		foreach( $matches as $match ) {

			// Found Match, Update Record
			if( $match->ContactMasterID == $listID ) {
				$uri = 'Contact/' . $listID . '/ContactDetails/' . $match->ID;
				$response = bmew_api::benchmark_query( $uri, 'PATCH', $body );
				return isset( $response->ID ) ? intval( $response->ID ) : $response;
			}
		}

		// Add New Contact
		$uri = 'Contact/' . $listID . '/ContactDetails';
		$response = bmew_api::benchmark_query( $uri, 'POST', $body );

		// Response
		return isset( $response->ID ) ? intval( $response->ID ) : $response;
	}

	// Find Contact ID On a List
	static function find_contact( $email ) {
		$email = str_replace( '+', '%2B', $email );
		return bmew_api::benchmark_query( 'Contact/ContactDetails?Search=' . $email );
	}

	// Deletes a Contact
	static function delete_contact( $listID, $contactID ) {
		$body = [ 'ContactID' => $contactID, 'ListID' => $listID ];
		return bmew_api::benchmark_query( 'Contact/ContactDetails', 'DELETE', $body );
	}

	// Find a Contact By Email, Then Delete
	static function delete_contact_by_email( $list_slug, $listID, $email ) {
		$results = self::find_contact( $email );
		if( ! is_array( $results ) ) { return; }
		foreach( $results as $row ) {
			if( $row->ListName == bmew_frontend::$list_names[$list_slug] ) {
				bmew_api::delete_contact( $listID, $row->ID );
			}
		}
	}

	// Adds a Contact List
	static function add_list( $name ) {
		$body = [ 'Data' => [ 'Description' => $name, 'Name' => $name ] ];
		$response = bmew_api::benchmark_query( 'Contact/', 'POST', $body );
		return empty( $response->ID ) ? $response : intval( $response->ID );
	}

	// Get Contact From a List
	static function get_contact( $listID, $contactID ) {
		return bmew_api::benchmark_query( 'Contact/' . $listID . '/ContactDetails/' . $contactID );
	}

	// Gets Client IP Address
	static function get_client_ip() {
		if( isset( $_SERVER[ 'HTTP_CLIENT_IP' ] ) )
			return $_SERVER[ 'HTTP_CLIENT_IP' ];
		if( isset( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) )
			return $_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
		if( isset( $_SERVER[ 'HTTP_X_FORWARDED' ] ) )
			return $_SERVER[ 'HTTP_X_FORWARDED' ];
		if( isset( $_SERVER[ 'HTTP_FORWARDED_FOR' ] ) )
			return $_SERVER[ 'HTTP_FORWARDED_FOR' ];
		if( isset( $_SERVER[ 'HTTP_FORWARDED' ] ) )
			return $_SERVER[ 'HTTP_FORWARDED' ];
		if( isset( $_SERVER[ 'REMOTE_ADDR' ] ) )
			return $_SERVER[ 'REMOTE_ADDR' ];
	}

	// Get All Contact Lists
	static function get_lists() {
		return bmew_api::benchmark_query( 'Contact/' );
	}

	// Vendor Handshake
	static function update_partner() {
		$uri = 'Client/Partner';
		$body = [ 'PartnerLogin' => 'beautomated' ];
		$response = bmew_api::benchmark_query( $uri, 'POST', $body );
	}

	// Gets Temporary Token And API Key From User / Pass
	static function get_api_key( $user, $pass ) {

		// Get Temporary Token From User / Pass
		$body = [ 'Username' => $user, 'Password' => $pass ];
		$response = bmew_api::benchmark_query( 'Client/Authenticate', 'POST', $body );
		if( ! isset( $response->Response->Token ) ) { return false; }

		// Use Temporary Token To Get API Key
		$key = $response->Response->Token;
		$response = bmew_api::benchmark_query( 'Client/Setting', 'GET', null, $key );
		return isset( $response->Response->Token ) ? $response->Response->Token : false;
	}

	// Talk To Benchmark ReST API
	static function benchmark_query( $uri = '', $method = 'GET', $body = null, $key = null ) {

		// Organize Request
		if( $body ) { $body = json_encode( $body ); }
		$key = $key ? $key : get_option( 'bmew_key' );
		$headers = [ 'AuthToken' => $key, 'Content-Type' => 'application/json' ];
		$args = [ 'body' => $body, 'headers' => $headers, 'method' => $method ];
		$url = bmew_api::$url . $uri;

		// Perform And Log Transmission
		$response = wp_remote_request( $url, $args );
		bmew_api::logger( $url, $args, $response );

		// Process Response
		if( is_wp_error( $response ) ) { return $response; }
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response );

		// Return
		return isset( $response->Response->Data ) ? $response->Response->Data : $response;
	}

	// Log Communications
	static function logger( $url, $request, $response ) {
		$bmew_debug = get_option( 'bmew_debug' );
		if( ! $bmew_debug ) { return; }
		$logger = wc_get_logger();
		$context = [ 'source' => 'woo-benchmark-email' ];
		$request = print_r( $request, true );
		$response = print_r( $response, true );
		$logger->info( "==URL== " . $url, $context );
		$logger->debug( "==REQUEST== " . $request, $context );
		$logger->debug( "==RESPONSE== " . $response, $context );
	}

	// Legacy XML-RPC API
	static function benchmark_query_legacy() {
		require_once( ABSPATH . WPINC . '/class-IXR.php' );
		$url = 'https://api.benchmarkemail.com/1.3/';
		$client = new IXR_Client( $url, false, 443, 15 );
		$args = func_get_args();
		call_user_func_array( [ $client, 'query' ], $args );
		$response = $client->getResponse();
		bmew_api::logger( $url, $args, $response );
		return $response;
	}
}
