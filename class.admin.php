<?php


// Exit If Accessed Directly
if( ! defined( 'ABSPATH' ) ) { exit; }


// AJAX Load Script
add_action( 'admin_enqueue_scripts', function() {
	wp_enqueue_script( 'bmew_admin', plugin_dir_url( __FILE__ ) . 'admin.js', [ 'jquery' ], null );
} );


// Plugin Action Links
add_filter(
	'plugin_action_links_woo-benchmark-email/woo-benchmark-email.php',
	function( $links ) {
	$settings = [
		'settings' => sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=wc-settings&tab=bmew' ),
			__( 'Settings', 'woo-benchmark-email' )
		),
	];
	return array_merge( $settings, $links );
} );


// Admin Dashboard Notifications
add_action( 'wp_dashboard_setup', function() {

	// Ensure is_plugin_active() Exists
	if( ! function_exists( 'is_plugin_active' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	$messages = [];

	// Handle Sister Product Dismissal Request
	if( ! empty( $_REQUEST['bmew_dismiss_sister'] ) && check_admin_referer( 'bmew_dismiss_sister' ) ) {
		update_option( 'bmew_sister_dismissed', current_time( 'timestamp') );
	}

	// Check Sister Product
	$bmew_sister_dismissed = get_option( 'bmew_sister_dismissed' );
	if(
		$bmew_sister_dismissed < current_time( 'timestamp') - 86400 * 90
		&& ! is_plugin_active( 'benchmark-email-lite/benchmark-email-lite.php' )
		&& current_user_can( 'activate_plugins' )
	) {

		// Plugin Installed But Not Activated
		if( file_exists( WP_PLUGIN_DIR . '/benchmark-email-lite/benchmark-email-lite.php' ) ) {
			$messages[] = sprintf(
				'
					%s &nbsp; <strong><a href="%s">%s</a></strong>
					<a style="float:right;" href="%s">%s</a>
				',
				__( 'Activate our sister product Benchmark Email Lite to view campaign statistics.', 'woo-benchmark-email' ),
				bmew_admin::get_sister_activate_link(),
				__( 'Activate Now', 'woo-benchmark-email' ),
				bmew_admin::get_sister_dismiss_link(),
				__( 'dismiss for 90 days', 'woo-benchmark-email' )
			);

		// Plugin Not Installed
		} else {
			$messages[] = sprintf(
				'
					%s &nbsp; <strong><a href="%s">%s</a></strong>
					<a style="float:right;" href="%s">%s</a>
				',
				__( 'Install our sister product Benchmark Email Lite to view campaign statistics.', 'woo-benchmark-email' ),
				bmew_admin::get_sister_install_link(),
				__( 'Install Now', 'woo-benchmark-email' ),
				bmew_admin::get_sister_dismiss_link(),
				__( 'dismiss for 90 days', 'woo-benchmark-email' )
			);
		}
	}

	// Message If Plugin Isn't Configured
	if( empty( get_option( 'bmew_key' ) ) ) {
		$messages[] = sprintf(
			'%s &nbsp; <strong><a href="admin.php?page=wc-settings&tab=bmew">%s</a></strong>',
			__( 'Please configure your API Key to use Woo Benchmark Email.', 'woo-benchmark-email' ),
			__( 'Configure Now', 'woo-benchmark-email' )
		);
	}

	// Output Message
	if( $messages ) {
		foreach( $messages as $message ) {
			echo sprintf(
				'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
				print_r( $message, true )
			);
		}
	}
} );


// Load Settings API Class
add_filter( 'woocommerce_get_settings_pages', function( $settings ) {
	$settings[] = include( 'class.wc-settings.php' );
	return $settings;
} );


// Administrative Class
class bmew_admin {


	// Sister Install Link
	static function get_sister_install_link() {
		$action = 'install-plugin';
		$slug = 'benchmark-email-lite';
		return wp_nonce_url(
			add_query_arg(
				[ 'action' => $action, 'plugin' => $slug ],
				admin_url( 'update.php' )
			),
			$action . '_' . $slug
		);
	}


	// Sister Activate Link
	static function get_sister_activate_link( $action='activate' ) {
		$plugin = 'benchmark-email-lite/benchmark-email-lite.php';
		$_REQUEST['plugin'] = $plugin;
		return wp_nonce_url(
			add_query_arg(
				[ 'action' => $action, 'plugin' => $plugin, 'plugin_status' => 'all', 'paged' => '1&s' ],
				admin_url( 'plugins.php' )
			),
			$action . '-plugin_' . $plugin
		);
	}


	// Sister Dismiss Notice Link
	static function get_sister_dismiss_link() {
		$url = wp_nonce_url( 'index.php?bmew_dismiss_sister=1', 'bmew_dismiss_sister' );
		return $url;
	}

}