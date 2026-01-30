<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'authenticate', function ( $user, $username, $password ) {
	if ( $user instanceof WP_User ) return $user;
	if ( empty( $username ) || empty( $password ) ) return $user;
	if ( ! wcu_is_phone_like( $username ) ) return $user;

	$normalized = wcu_normalize_phone( $username );
	if ( $normalized === '' ) return $user;

	$users = get_users( array(
		'meta_key' => 'billing_phone',
		'meta_value' => $normalized,
		'number' => 1,
		'count_total' => false,
	) );
	if ( empty( $users ) || ! $users[0] instanceof WP_User ) return $user;

	$u = $users[0];
	if ( wp_check_password( $password, $u->user_pass, $u->ID ) ) return $u;

	return new WP_Error( 'invalid_phone_password', __( '<strong>Error</strong>: The password you entered for the phone number is incorrect.', 'wcu' ) );
}, 20, 3 );