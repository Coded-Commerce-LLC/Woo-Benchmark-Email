<?php
/*
Plugin Name: Benchmark Email WooCommerce
Description: Benchmark Email WooCommerce
Version: 1.0
Author: seanconklin
Author URI: https://codedcommerce.com/
License: GPLv3
Text Domain: bmew
Domain Path: /languages/
*/

// File Includes
require_once( 'class.admin.php' );
require_once( 'class.api.php' );
require_once( 'class.frontend.php' );

// Front End Hooks
add_action( 'init', array( 'bmew_frontend', 'init_contact_lists' ) );
//add_action( 'woocommerce_after_checkout_validation', array( 'bmew_frontend', 'woocommerce_after_checkout_validation' ) );
add_action( 'woocommerce_checkout_update_order_meta', array( 'bmew_frontend', 'woocommerce_checkout_update_order_meta' ) );
add_filter( 'woocommerce_checkout_fields' , array( 'bmew_frontend', 'woocommerce_checkout_fields' ) );

// Admin Hooks
add_action( 'admin_enqueue_scripts', array( 'bmew_admin', 'admin_enqueue_scripts' ) );
add_action( 'wp_ajax_bmew_action', array( 'bmew_admin', 'wp_ajax_bmew_action' ) );
add_action( 'wp_dashboard_setup', array( 'bmew_admin', 'wp_dashboard_setup' ) );
add_filter( 'woocommerce_get_sections_advanced', array( 'bmew_admin', 'woocommerce_get_sections_advanced' ) );
add_filter( 'woocommerce_get_settings_advanced', array( 'bmew_admin', 'woocommerce_get_settings_advanced' ) );
