<?php

// ReST API Class
class bmew_api {

	// Endpoint
	static $url = 'https://clientapi.benchmarkemail.com/';

	// Adds a Contact To a List
	static function add_contact( $listID, $email, $first, $last ) {
		$key = get_option( 'bmew_key' );
		$headers = array(
			'AuthToken' => $key,
			'Content-Type' => 'application/json',
		);
		$body = array(
			'Data' => array(
				'Email' => $email,
				'EmailPerm' => 1,
				'FirstName' => $first,
				'IPAddress' => bmew_api::get_client_ip(),
				'LastName' => $last,
			),
		);
		$args = array(
			'body' => json_encode( $body ),
			'headers' => $headers,
			'method' => 'POST',
		);
		$url = bmew_api::$url . 'Contact/' . $listID . '/ContactDetails';
		$response = wp_remote_request( $url, $args );
		if( is_wp_error( $response ) ) { return; }
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response );
		return $response;
	}

	// Adds a Contact List
	static function add_list( $name ) {
		$key = get_option( 'bmew_key' );
		$headers = array(
			'AuthToken' => $key,
			'Content-Type' => 'application/json',
		);
		$body = array(
			'Data' => array(
				'Description' => $name,
				'Name' => $name,
			),
		);
		$args = array(
			'body' => json_encode( $body ),
			'headers' => $headers,
			'method' => 'POST',
		);
		$url = bmew_api::$url . 'Contact';
		$response = wp_remote_request( $url, $args );
		if( is_wp_error( $response ) ) { return; }
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response );
		if( ! empty( $response->Response->Data->ID ) ) {
			return intval( $response->Response->Data->ID );
		}
	}

	// Get Contact From a List
	static function get_contact( $listID, $contactID ) {
		$key = get_option( 'bmew_key' );
		$headers = array(
			'AuthToken' => $key,
			'Content-Type' => 'application/json',
		);
		$args = array(
			'body' => null,
			'headers' => $headers,
			'method' => 'GET',
		);
		$url = bmew_api::$url . 'Contact/' . $listID . '/ContactDetails/' . $contactID;
		$response = wp_remote_request( $url, $args );
		if( is_wp_error( $response ) ) { return; }
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response );
		if( isset( $response->Response->Data ) ) {
			$response = $response->Response->Data;
		}
		return $response;
	}

	// Gets Client IP Address
	function get_client_ip() {
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
		$key = get_option( 'bmew_key' );
		$headers = array(
			'AuthToken' => $key,
			'Content-Type' => 'application/json',
		);
		$args = array(
			'body' => null,
			'headers' => $headers,
			'method' => 'GET',
		);
		$url = bmew_api::$url . 'Contact/';
		$response = wp_remote_request( $url, $args );
		if( is_wp_error( $response ) ) { return; }
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response );
		if( isset( $response->Response->Data ) ) {
			$response = $response->Response->Data;
		}
		return $response;
	}

}
