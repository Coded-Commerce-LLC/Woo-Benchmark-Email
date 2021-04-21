<?php
/**
 * Plugin Name: Woo Benchmark Email
 * Plugin URI: https://codedcommerce.com/product/woo-benchmark-email
 * Description: Connects WooCommerce with Benchmark Email for syncing customers and abandoned carts.
 * Version: 1.5
 * Author: Coded Commerce, LLC
 * Author URI: https://codedcommerce.com
 * Developer: Sean Conklin
 * Developer URI: https://seanconklin.wordpress.com
 * Text Domain: woo-benchmark-email
 * Domain Path: /languages
 *
 * WC requires at least: 3.0
 * WC tested up to: 4.0
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
}