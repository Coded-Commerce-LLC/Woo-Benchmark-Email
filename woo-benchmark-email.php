<?php
/**
 * Plugin Name: Woo Benchmark Email
 * Plugin URI: https://codedcommerce.com/product/woo-benchmark-email
 * Description: Connects WooCommerce with Benchmark Email for syncing customers and abandoned carts.
 * Version: 1.2
 * Author: Coded Commerce, LLC
 * Author URI: https://codedcommerce.com
 * Developer: Sean Conklin
 * Developer URI: https://seanconklin.wordpress.com
 * Text Domain: woo-benchmark-email
 * Domain Path: /languages
 *
 * WC requires at least: 3.0
 * WC tested up to: 3.5.0
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit If Accessed Directly
if( ! defined( 'ABSPATH' ) ) { exit; }

// Make Sure WooCommerce Is Activated
if(
	in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )
) {

	// Include Object Files
	require_once( 'class.admin.php' );
	require_once( 'class.api.php' );
	require_once( 'class.frontend.php' );

	// Front End Hooks
	add_action( 'init', [ 'bmew_frontend', 'init_contact_lists' ] );
	add_action( 'woocommerce_checkout_update_order_meta', [ 'bmew_frontend', 'woocommerce_checkout_update_order_meta' ] );
	add_action( 'wp_enqueue_scripts', [ 'bmew_frontend', 'wp_enqueue_scripts' ] );
	add_action( 'woocommerce_add_to_cart', [ 'bmew_frontend', 'woocommerce_add_to_cart' ] );
	add_filter( 'woocommerce_add_to_cart_redirect', [ 'bmew_frontend', 'woocommerce_add_to_cart_redirect' ] );
	add_filter( 'woocommerce_billing_fields', [ 'bmew_frontend', 'woocommerce_billing_fields' ] );
	add_filter( 'woocommerce_checkout_fields' , [ 'bmew_frontend', 'woocommerce_checkout_fields' ] );

	// Admin Hooks
	add_action( 'admin_enqueue_scripts', [ 'bmew_admin', 'admin_enqueue_scripts' ] );
	add_action( 'wp_dashboard_setup', [ 'bmew_admin', 'wp_dashboard_setup' ] );
	add_filter( 'woocommerce_get_settings_pages', [ 'bmew_admin', 'woocommerce_get_settings_pages' ] );

	// AJAX Hooks
	add_action( 'wp_ajax_bmew_action', [ 'bmew_frontend', 'wp_ajax__bmew_action' ] );
	add_action( 'wp_ajax_nopriv_bmew_action', [ 'bmew_frontend', 'wp_ajax__bmew_action' ] );

	// Internationalization
	add_action( 'plugins_loaded',  [ 'bmew_frontend', 'plugins_loaded' ] );
}
