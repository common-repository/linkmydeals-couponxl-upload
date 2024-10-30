<?php

/**
* Plugin Name: LinkMyDeals - CouponXL Upload
* Plugin URI: http://linkmydeals.com/couponxl-plugin.php
* Description: LinkMyDeals.com provides Coupon & Deal Feeds from 4000+ Online Stores. You can use this plugin to automate/upload the feeds into your CouponXL Installation.
* Version: 2.6
* Author: LinkMyDeals Team
**/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function downloadLogs() {
	include 'downloadLogs.php';
}

function pullFeed( ) {
	include 'pullFeed.php';
}

function processBatch($loop) {
	include 'processBatch.php';
}

function lmd_displayPluginPage() {	
	include 'main.php';
	//Bootstrap CSS
	wp_register_style( 'bootstrap.min', plugins_url('css/bootstrap.min.css', __FILE__));
	wp_enqueue_style('bootstrap.min');
	//Custom CSS
	wp_register_style('lmd_css', plugins_url('css/style.css', __FILE__));
	wp_enqueue_style('lmd_css');	
}

function lmd_admin_menu() {
	add_menu_page("LinkMyDeals Feed Upload", "LinkMyDeals", 1, "linkmydeals", "lmd_displayPluginPage", "dashicons-randomize",9);
	//Bootstrap JS
	wp_register_script( 'bootstrap.min', plugins_url('js/bootstrap.min.js', __FILE__));
	wp_enqueue_script('bootstrap.min');
	//Bootstrap Switch Plugin
	wp_register_style( 'bootstrap-switch.min', plugins_url('css/bootstrap-switch.min.css', __FILE__));
	wp_enqueue_style('bootstrap-switch.min');
	wp_register_script( 'bootstrap-switch.min', plugins_url('js/bootstrap-switch.min.js', __FILE__), array( 'jquery' ));
	wp_enqueue_script('bootstrap-switch.min');
	// Input Mask Plugin
	wp_register_script( 'inputmask', plugins_url('js/inputmask.js', __FILE__));
	wp_enqueue_script('inputmask');
	wp_register_script( 'inputmask.date.extensions', plugins_url('js/inputmask.date.extensions.js', __FILE__), array( 'jquery' ));
	wp_enqueue_script('inputmask.date.extensions');
}

add_action('admin_menu', 'lmd_admin_menu');
add_action('process_batch', 'processBatch');
add_action('pull_feed', 'pullFeed');
add_action('admin_post_logs', 'downloadLogs');

function lmd_activate() {
	include 'activate.php';
}
register_activation_hook( __FILE__, 'lmd_activate' );

?>
