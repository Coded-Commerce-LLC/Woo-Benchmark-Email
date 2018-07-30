<?php

// Exit If Accessed Directly
if( ! defined( 'ABSPATH' ) ) { exit; }

// ReST API Class
class bmew_api {

	// Endpoint
	static $url = 'https://clientapi.benchmarkemail.com/';

	// Adds a Contact To a List
	static function add_contact( $listID, $email, $args = array() ) {
		extract( $args );

		// Build Body
		$body = array(
			'Data' => array(
				'Email' => $email,
				'EmailPerm' => 1,
				'Field19' => current_time( 'm/d/Y' ),
				'IPAddress' => bmew_api::get_client_ip(),
			),
		);
		if( isset( $first ) ) { $body['Data']['FirstName'] = $first; }
		if( isset( $last ) ) { $body['Data']['LastName'] = $last; }
		if( isset( $product1 ) ) { $body['Data']['Field21'] = $product1; }
		if( isset( $product2 ) ) { $body['Data']['Field22'] = $product2; }
		if( isset( $product3 ) ) { $body['Data']['Field23'] = $product3; }
		if( isset( $total ) ) { $body['Data']['Field24'] = $total; }
		if( isset( $url ) ) { $body['Data']['Field18'] = $url; }

		// Search Existing Records
		$matches = bmew_api::find_contact( $email );
		foreach( $matches as $match ) {

			// Found Match, Update Record
			if( $match->ContactMasterID == $listID ) {
				$uri = 'Contact/' . $listID . '/ContactDetails/' . $match->ID;
				$response = bmew_api::benchmark_query( $uri, 'PATCH', $body );
				return isset( $response->ID ) ? intval( $response->ID ) : $response;
			}
		}

		// Add Record
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
		$body = array( 'ContactID' => $contactID, 'ListID' => $listID );
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
		$body = array( 'Data' => array( 'Description' => $name, 'Name' => $name ) );
		$response = bmew_api::benchmark_query( 'Contact', 'POST', $body );
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
		return bmew_api::benchmark_query( 'Contact' );
	}

	// Talk To Benchmark ReST API
	static function benchmark_query( $uri = '', $method = 'GET', $body = null ) {

		// Organize Request
		if( $body ) { $body = json_encode( $body ); }
		$key = get_option( 'bmew_key' );
		$headers = array( 'AuthToken' => $key, 'Content-Type' => 'application/json' );
		$args = array( 'body' => $body, 'headers' => $headers, 'method' => $method );
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
		$context = array( 'source' => 'benchmark-email-woo' );
		$request = print_r( $request, true );
		$response = print_r( $response, true );
		$logger->info( "==URL== " . $url, $context );
		$logger->debug( "==REQUEST== " . $request, $context );
		$logger->debug( "==RESPONSE== " . $response, $context );
	}

}
