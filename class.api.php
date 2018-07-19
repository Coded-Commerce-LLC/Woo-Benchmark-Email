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
		$body = array(
			'Data' => array(
				'Field19' => current_time( 'm/d/Y' ),
				'Email' => $email,
				'EmailPerm' => 1,
				'IPAddress' => bmew_api::get_client_ip(),
			),
		);
		if( isset( $first ) ) { $body['Data']['FirstName'] = $first; }
		if( isset( $last ) ) { $body['Data']['LastName'] = $last; }
		if( isset( $product1 ) ) { $body['Data']['Field22'] = $product1; }
		if( isset( $product2 ) ) { $body['Data']['Field23'] = $product2; }
		if( isset( $total ) ) { $body['Data']['Field24'] = $total; }
		if( isset( $url ) ) { $body['Data']['Field21'] = $url; }
		$uri = 'Contact/' . $listID . '/ContactDetails';
		$response = bmew_api::benchmark_query( $uri, 'POST', $body );
		return isset( $response->ID ) ? intval( $response->ID ) : $response;
	}

	// Find Contact ID On a List
	static function find_contact( $email ) {
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
		if( empty( $results->Response->Data ) ) { return; }
		$results = $results->Response->Data;
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
		if( $body ) { $body = json_encode( $body ); }
		$key = get_option( 'bmew_key' );
		$headers = array( 'AuthToken' => $key, 'Content-Type' => 'application/json' );
		$args = array( 'body' => $body, 'headers' => $headers, 'method' => $method );
		$url = bmew_api::$url . $uri;
		$response = wp_remote_request( $url, $args );
		if( is_wp_error( $response ) ) { return $response; }
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response );
		if( isset( $response->Response->Data ) ) {
			$response = $response->Response->Data;
		}
		return $response;
	}

}
