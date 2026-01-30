<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wcu_normalize_phone( $raw ) {
	if ( ! is_string( $raw ) ) return '';
	$digits = preg_replace( '/\D+/', '', $raw );
	if ( strlen( $digits ) < 9 ) return '';
	return substr( $digits, -9 );
}
function wcu_is_phone_like( $raw ) {
	return is_string( $raw ) && (bool) preg_match( '/^[0-9\+\s\-\(\)]+$/', $raw );
}
function wcu_validate_personal_id( $raw ) {
	return is_string( $raw ) && (bool) preg_match( '/^\d{11}$/', $raw );
}
function wcu_get_sms_consent( $user_id ) {
	$val = get_user_meta( $user_id, '_sms_consent', true );
	$val = is_string( $val ) ? strtolower( $val ) : '';
	return in_array( $val, array( 'yes', 'no' ), true ) ? $val : '';
}
function wcu_get_call_consent( $user_id ) {
	$val = get_user_meta( $user_id, '_call_consent', true );
	$val = is_string( $val ) ? strtolower( $val ) : '';
	return in_array( $val, array( 'yes', 'no' ), true ) ? $val : '';
}
function wcu_get_user_phone( $user_id ) {
	$val = get_user_meta( $user_id, 'billing_phone', true );
	return is_string( $val ) ? $val : '';
}
function wcu_get_admin_notify_email() {
	$opt = get_option( 'wcu_admin_email', '' );
	return ( is_string( $opt ) && is_email( $opt ) ) ? $opt : get_option( 'admin_email' );
}
function wcu_maybe_send_sms_consent_notification( $user_id, $old, $new, $context = '' ) {
	$new = strtolower( (string) $new );
	if ( ! in_array( $new, array( 'yes', 'no' ), true ) ) return;
	$old_norm = strtolower( (string) $old );
	if ( $old_norm === $new && $context !== 'registration' ) return;

	$user = get_userdata( $user_id );
	if ( ! $user ) return;

	$full_name = trim( $user->first_name . ' ' . $user->last_name );
	if ( $full_name === '' ) $full_name = $user->display_name ?: $user->user_login;
	$phone_display = ( $p = wcu_get_user_phone( $user_id ) ) ? $p : __( '(not provided)', 'wcu' );
	$admin_email   = wcu_get_admin_notify_email();
	if ( ! $admin_email ) return;

	$site_name  = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	$subject    = sprintf( __( '[%s] SMS consent update', 'wcu' ), $site_name );
	$agree_str  = ( $new === 'yes' ) ? __( 'now agrees to receive SMS.', 'wcu' ) : __( 'does not agree to receive SMS.', 'wcu' );
	$context_str= $context ? sprintf( __( 'Context: %s', 'wcu' ), $context ) : '';
	$body       = sprintf( __( 'User %1$s, phone number: %2$s, %3$s %4$s', 'wcu' ), $full_name, $phone_display, $agree_str, $context_str );
	wp_mail( $admin_email, $subject, $body );
}
function wcu_phone_exists_for_another_user( $normalized_phone, $current_user_id = 0 ) {
	$normalized_phone = trim( (string) $normalized_phone );
	if ( $normalized_phone === '' ) return false;

	$exact = new WP_User_Query( array(
		'number' => 1,
		'fields' => 'ids',
		'meta_query' => array(
			array( 'key' => 'billing_phone', 'value' => $normalized_phone ),
		),
	) );
	$ids = $exact->get_results();
	if ( ! empty( $ids ) ) {
		$found_id = (int) $ids[0];
		if ( $found_id && $found_id !== (int) $current_user_id ) return true;
	}

	$candidates = new WP_User_Query( array(
		'number' => 50,
		'fields' => 'ids',
		'meta_query' => array(
			array( 'key' => 'billing_phone', 'value' => $normalized_phone, 'compare' => 'LIKE' ),
		),
	) );
	foreach ( $candidates->get_results() as $uid ) {
		if ( (int) $uid === (int) $current_user_id ) continue;
		$stored_norm = wcu_normalize_phone( (string) get_user_meta( $uid, 'billing_phone', true ) );
		if ( $stored_norm && $stored_norm === $normalized_phone ) return true;
	}
	return false;
}
function wcu_get_terms_url() {
	$opt = get_option( 'wcu_terms_url', '' );
	if ( $opt ) return esc_url( $opt );
	$wc_terms_page_id = get_option( 'woocommerce_terms_page_id' );
	if ( $wc_terms_page_id ) {
		$url = get_permalink( (int) $wc_terms_page_id );
		if ( $url ) return esc_url( $url );
	}
	return '';
}
function wcu_get_terms_content_html() {
	$terms_text = get_option( 'wcu_terms_text', '' );
	if ( is_string( $terms_text ) && trim( $terms_text ) !== '' ) {
		return wp_kses_post( $terms_text );
	}
	if ( get_option( 'woocommerce_enable_terms_and_conditions' ) ) {
		$terms_page_id = get_option( 'woocommerce_terms_page_id' );
		if ( $terms_page_id ) {
			$content = get_post_field( 'post_content', (int) $terms_page_id );
			if ( $content ) return wp_kses_post( $content );
		}
	}
	return '';
}
function wcu_get_print_terms_url() {
	return add_query_arg( 'wcu-print-terms', '1', home_url( '/' ) );
}