<?php
/**
 * Plugin Name:       Woo Custom User (WCU)
 * Description:       Extends WooCommerce user registration, authentication and account UI: custom fields, phone login, SMS consent, club card coupon, terms & conditions with admin-editable text and accordion. Includes user data search & printable terms.
 * Version:           1.0.6
 * Author:            Samsiani
 * Text Domain:       wcu
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCU_VERSION', '1.0.6' );
define( 'WCU_FILE', __FILE__ );
define( 'WCU_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCU_URL', plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'wcu', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Woo Custom User requires WooCommerce to be active.', 'wcu' ) . '</p></div>';
		} );
		return;
	}

	require_once WCU_DIR . 'includes/helpers.php';
	require_once WCU_DIR . 'includes/frontend.php';
	require_once WCU_DIR . 'includes/auth.php';
	require_once WCU_DIR . 'includes/admin.php';
	require_once WCU_DIR . 'includes/shortcode.php';
} );