<?php

// Exit If Accessed Directly
if( ! defined( 'ABSPATH' ) ) { exit; }

// ReST API Class
class bmew_api {

	// Endpoint
	static $url = 'https://clientapi.benchmarkemail.com/';

	// Adds a Contact To a List
	static function add_contact( $listID, $email, $first='', $last='', $url='' ) {
		$key = get_option( 'bmew_key' );
		$headers = array(
			'AuthToken' => $key,
			'Content-Type' => 'application/json',
		);
		$body = array(
			'Data' => array(
				'Field19' => current_time( 'm/d/Y' ),
				'Field21' => $url,
				'Email' => $email,
				'EmailPerm' => 1,
				'IPAddress' => bmew_api::get_client_ip(),
			),
		);
		if( $first) { $body['Data']['FirstName'] = $first; }
		if( $last) { $body['Data']['LastName'] = $last; }
			//Field22 Product1
			//Field23 Product2
			//Field24 Total
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

	// Find Contact ID On a List
	static function find_contact( $email ) {
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
		$url = bmew_api::$url . 'Contact/ContactDetails?Search=' . $email;
		$response = wp_remote_request( $url, $args );
		if( is_wp_error( $response ) ) { return; }
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response );
		return $response;
	}

	// Deletes a Contact
	static function delete_contact( $listID, $contactID ) {
		$key = get_option( 'bmew_key' );
		$headers = array(
			'AuthToken' => $key,
			'Content-Type' => 'application/json',
		);
		$body = array(
			'ContactID' => $contactID,
			'ListID' => $listID,
		);
		$args = array(
			'body' => json_encode( $body ),
			'headers' => $headers,
			'method' => 'DELETE',
		);

		$url = bmew_api::$url . 'Contact/ContactDetails';
		$response = wp_remote_request( $url, $args );
		if( is_wp_error( $response ) ) { return; }
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response );
		return $response;

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
