<?php
/**
 * Plugin Name:       Woo Custom User (WCU)
 * Description:       Extends WooCommerce user registration, authentication and account UI: custom fields, phone login, SMS consent, club card coupon, terms & conditions with admin-editable text and accordion. Includes user data search & printable terms.
 * Version:           1.1.0
 * Author:            Samsiani
 * Text Domain:       wcu
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCU_VERSION', '1.1.0' );
define( 'WCU_FILE', __FILE__ );
define( 'WCU_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCU_URL', plugin_dir_url( __FILE__ ) );

/**
 * Create external phones table on activation
 */
function wcu_create_external_phones_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'club_anketa_external_phones';
	$charset_collate = $wpdb->get_charset_collate();
	
	$sql = "CREATE TABLE $table_name (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		phone VARCHAR(20) NOT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY phone (phone)
	) $charset_collate;";
	
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
	
	update_option( 'wcu_external_phones_db_version', '1.0' );
}

register_activation_hook( __FILE__, 'wcu_create_external_phones_table' );

/**
 * Check DB version on admin_init and upgrade if needed
 */
add_action( 'admin_init', function() {
	$current_version = get_option( 'wcu_external_phones_db_version', '' );
	if ( $current_version !== '1.0' ) {
		wcu_create_external_phones_table();
	}
} );

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