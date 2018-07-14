<?php
/**
 * Plugin Name: Benchmark Email WooCommerce
 * Plugin URI: http://woocommerce.com/products/benchmark-email-woocommerce/
 * Description: Connects WooCommerce with Benchmark Email for syncing customers and abandoned carts.
 * Version: 1.0.0
 * Author: WooCommerce
 * Author URI: http://woocommerce.com/
 * Developer: Sean Conklin
 * Developer URI: https://codedcommerce.com/
 * Text Domain: benchmark-email-woo
 * Domain Path: /languages
 *
 * Woo: 12345:342928dfsfhsf8429842374wdf4234sfd
 * WC requires at least: 3.0
 * WC tested up to: 3.4.3
 *
 * Copyright: © 2009-2015 WooCommerce.
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
	add_action( 'init', array( 'bmew_frontend', 'init_contact_lists' ) );
	add_action( 'woocommerce_checkout_update_order_meta', array( 'bmew_frontend', 'woocommerce_checkout_update_order_meta' ) );
	add_filter( 'woocommerce_checkout_fields' , array( 'bmew_frontend', 'woocommerce_checkout_fields' ) );
	add_action( 'wp_enqueue_scripts', array( 'bmew_frontend', 'wp_enqueue_scripts' ) );

	// Admin Hooks
	add_action( 'admin_enqueue_scripts', array( 'bmew_admin', 'admin_enqueue_scripts' ) );
	add_action( 'woocommerce_settings_bmew', array( 'bmew_admin', 'woocommerce_settings_bmew' ) );
	add_filter( 'woocommerce_get_sections_advanced', array( 'bmew_admin', 'woocommerce_get_sections_advanced' ) );
	add_filter( 'woocommerce_get_settings_advanced', array( 'bmew_admin', 'woocommerce_get_settings_advanced' ) );

	// Diagnostics Hook
	add_action( 'wp_dashboard_setup', array( 'bmew_admin', 'wp_dashboard_setup' ) );

	// AJAX Hooks
	add_action( 'wp_ajax_bmew_action', array( 'bmew_frontend', 'wp_ajax__bmew_action' ) );
	add_action( 'wp_ajax_nopriv_bmew_action', array( 'bmew_frontend', 'wp_ajax__bmew_action' ) );

	// Internationalization
	add_action( 'plugins_loaded',  array( 'bmew_frontend', 'plugins_loaded' ) );
}
