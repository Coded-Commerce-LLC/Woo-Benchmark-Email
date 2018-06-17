<?php
/*
Plugin Name: Benchmark Email WooCommerce
Description: Benchmark Email WooCommerce
Version: 1.0
Author: seanconklin
Author URI: https://codedcommerce.com/
License: GPLv3
Text Domain: benchmark-email-woo
Domain Path: /languages/
*/

// Hooks
add_action( 'wp_dashboard_setup', array( 'bmew', 'debug' ) );

// Plugin Class
class bmew {

	// Endpoint
	static $url = 'https://clientapi.benchmarkemail.com/';
	static $key = 'e01c3d95-bb28-4dd0-af3b-4aa9689cdd80';

	static function debug() {
		//$response = bmew::get_lists();
		//$response = bmew::get_contact();
		$response = bmew::add_contact();
		echo sprintf(
			'<div class="notice notice-info is-dismissible"><p><pre>%s</pre></p></div>',
			print_r( $response, true )
		);
	}

	// Get Contact Lists
	static function get_lists() {
		$headers = array(
			'AuthToken' => bmew::$key,
			'Content-Type' => 'application/json',
		);
		$args = array(
			'body' => null,
			'headers' => $headers,
			'method' => 'GET',
		);
		$url = bmew::$url . 'Contact';
		$response = wp_remote_request( $url, $args );
		if( is_wp_error( $response ) ) { return; }
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response );
		if( isset( $response->Response->Data ) ) { $response = $response->Response->Data; }
		return $response;
	}

	// Get Contact
	static function get_contact( $listID=175166, $contactID=208797114 ) {
		$headers = array(
			'AuthToken' => bmew::$key,
			'Content-Type' => 'application/json',
		);
		$args = array(
			'body' => null,
			'headers' => $headers,
			'method' => 'GET',
		);
		$url = bmew::$url . 'Contact/' . $listID . '/ContactDetails/' . $contactID;
		$response = wp_remote_request( $url, $args );
		if( is_wp_error( $response ) ) { return; }
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response );
		if( isset( $response->Response->Data ) ) { $response = $response->Response->Data; }
		return $response;
	}

	// Save Contact
	static function add_contact( $listID=3649970 ) {
		$headers = array(
			'AuthToken' => bmew::$key,
			'Content-Type' => 'application/json',
		);
		$body = array(
			'Data' => array(
				'Email' => 'seanconklin@yahoo.com',
				'EmailPerm' => 1,
				'EmailType' => 2,
				'FirstName' => 'Tester',
				'IPAddress' => bmew::get_client_ip(),
				'LastName' => 'Testing',
				'Optin' => 1,
				'OptinDate' => '6/17/2018 12:00:00 AM',
				'OptinIP' => bmew::get_client_ip(),
			),
		);
		$args = array(
			'body' => json_encode( $body ),
			'headers' => $headers,
			'method' => 'POST',
		);
		$url = bmew::$url . 'Contact/' . $listID . '/ContactDetails';
		$response = wp_remote_request( $url, $args );
		if( is_wp_error( $response ) ) { return; }
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response );
		return $response;
	}

	// Gets Client IP Address
	function get_client_ip() {
		$ipaddress = '';
		if (isset($_SERVER['HTTP_CLIENT_IP']))
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_X_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		else if(isset($_SERVER['REMOTE_ADDR']))
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		return $ipaddress;
	}
}

