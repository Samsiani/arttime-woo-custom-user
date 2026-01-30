<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'manage_users_columns', function ( $columns ) {
	$columns['wcu_phone'] = __( 'Phone Number', 'wcu' );
	$columns['wcu_sms']   = __( 'SMS accept', 'wcu' );
	return $columns;
} );
add_filter( 'manage_users_custom_column', function ( $out, $name, $user_id ) {
	if ( $name === 'wcu_phone' ) {
		return esc_html( get_user_meta( $user_id, 'billing_phone', true ) );
	}
	if ( $name === 'wcu_sms' ) {
		$consent = wcu_get_sms_consent( $user_id );
		if ( $consent === 'yes' ) return '<span style="color:#2e7d32;font-weight:600;">' . esc_html__( 'Yes', 'wcu' ) . '</span>';
		if ( $consent === 'no' )  return '<span style="color:#c62828;font-weight:600;">' . esc_html__( 'No', 'wcu' ) . '</span>';
		return '<span style="color:#616161;">' . esc_html__( '(blank)', 'wcu' ) . '</span>';
	}
	return $out;
}, 10, 3 );

add_action( 'admin_menu', function () {
	add_options_page(
		__( 'Custom User Settings', 'wcu' ),
		__( 'Custom User Settings', 'wcu' ),
		'manage_options',
		'wcu-settings',
		'wcu_render_settings_page'
	);
} );
add_action( 'admin_init', function () {
	register_setting( 'wcu_settings_group', 'wcu_admin_email', array(
		'type' => 'string', 'sanitize_callback' => 'sanitize_email', 'default' => '',
	) );
	register_setting( 'wcu_settings_group', 'wcu_terms_url', array(
		'type' => 'string', 'sanitize_callback' => 'esc_url_raw', 'default' => '',
	) );
	register_setting( 'wcu_settings_group', 'wcu_terms_text', array(
		'type' => 'string', 'sanitize_callback' => 'wp_kses_post', 'default' => '',
	) );
	register_setting( 'wcu_settings_group', 'wcu_auto_apply_club', array(
		'type' => 'boolean', 'sanitize_callback' => function($v){return (bool)$v;}, 'default' => false,
	) );

	add_settings_section( 'wcu_main_section', __( 'Notifications', 'wcu' ),
		function(){ echo '<p>' . esc_html__( 'Configure where to send SMS consent notifications.', 'wcu' ) . '</p>';},
		'wcu_settings'
	);
	add_settings_field( 'wcu_admin_email', __( 'Administrator Email', 'wcu' ), function () {
		$val = esc_attr( get_option( 'wcu_admin_email', '' ) );
		echo '<input type="email" name="wcu_admin_email" value="' . $val . '" class="regular-text" placeholder="' . esc_attr( get_option( 'admin_email' ) ) . '"/>';
	}, 'wcu_settings', 'wcu_main_section' );

	add_settings_section( 'wcu_terms_section', __( 'Terms & Conditions', 'wcu' ),
		function(){ echo '<p>' . esc_html__( 'Provide the Terms & Conditions page URL or paste the full Terms text. The registration/account form shows a checkbox & expandable text.', 'wcu' ) . '</p>';},
		'wcu_settings'
	);
	add_settings_field( 'wcu_terms_url', __( 'Terms & Conditions URL', 'wcu' ), function () {
		$val = esc_url( get_option( 'wcu_terms_url', '' ) );
		echo '<input type="url" name="wcu_terms_url" value="' . $val . '" class="regular-text" placeholder="https://example.com/terms" />';
		$wc_terms_page_id = get_option( 'woocommerce_terms_page_id' );
		if ( $wc_terms_page_id ) {
			$wc_url = get_permalink( (int) $wc_terms_page_id );
			if ( $wc_url ) {
				echo '<p class="description">' . sprintf(
					esc_html__( 'WooCommerce Terms page detected: %s (used as fallback).', 'wcu' ),
					'<a href="' . esc_url( $wc_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $wc_url ) . '</a>'
				) . '</p>';
			}
		}
	}, 'wcu_settings', 'wcu_terms_section' );

	add_settings_field( 'wcu_terms_text', __( 'Terms & Conditions text', 'wcu' ), function () {
		$val = get_option( 'wcu_terms_text', '' );
		echo '<textarea name="wcu_terms_text" class="large-text" rows="8" placeholder="' .
		     esc_attr__( 'Paste full terms & conditions HTML (optional).', 'wcu' ) . '">' .
		     esc_textarea( $val ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Allowed HTML will be kept (sanitized).', 'wcu' ) . '</p>';
	}, 'wcu_settings', 'wcu_terms_section' );

	add_settings_section( 'wcu_club_section', __( 'Club Card', 'wcu' ),
		function(){ echo '<p>' . esc_html__( 'Settings related to Club Card coupon.', 'wcu' ) . '</p>';},
		'wcu_settings'
	);
	add_settings_field( 'wcu_auto_apply_club', __( 'Auto-apply Club Card at checkout', 'wcu' ), function () {
		$val = (bool) get_option( 'wcu_auto_apply_club', false );
		echo '<label><input type="checkbox" name="wcu_auto_apply_club" value="1" ' . checked( $val, true, false ) . ' /> ' .
		     esc_html__( 'Automatically apply the saved Club Card coupon (once per session).', 'wcu' ) . '</label>';
	}, 'wcu_settings', 'wcu_club_section' );
} );

function wcu_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$export_nonce  = wp_create_nonce( 'wcu_export_users' );
	$example_nonce = wp_create_nonce( 'wcu_download_import_example' );
	$export_url  = add_query_arg( array( 'action'=>'wcu_export_users','_wpnonce'=>$export_nonce ), admin_url( 'admin-post.php' ) );
	$example_url = add_query_arg( array( 'action'=>'wcu_download_import_example','_wpnonce'=>$example_nonce ), admin_url( 'admin-post.php' ) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Custom User Settings', 'wcu' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'wcu_settings_group' );
			do_settings_sections( 'wcu_settings' );
			submit_button(); ?>
		</form>
		<hr/>
		<h2><?php esc_html_e( 'Bulk Import: Set SMS Consent from CSV', 'wcu' ); ?></h2>
		<p><?php esc_html_e( 'Upload CSV first column: phone. Will set consent for matched users.', 'wcu' ); ?></p>
		<p><a class="button" href="<?php echo esc_url( $example_url ); ?>"><?php esc_html_e( 'Download import example CSV', 'wcu' ); ?></a></p>
		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'wcu_import_sms', 'wcu_import_nonce' ); ?>
			<p><label for="wcu_csv_file"><strong><?php esc_html_e( 'CSV File', 'wcu' ); ?></strong></label><br/>
				<input type="file" id="wcu_csv_file" name="wcu_csv_file" accept=".csv,text/csv" required /></p>
			<p><strong><?php esc_html_e( 'Set consent status to:', 'wcu' ); ?></strong><br/>
				<label><input type="radio" name="wcu_import_consent" value="yes" checked/> <?php esc_html_e( 'Yes', 'wcu' ); ?></label>
				<label style="margin-left:1em;"><input type="radio" name="wcu_import_consent" value="no" /> <?php esc_html_e( 'No', 'wcu' ); ?></label></p>
			<?php submit_button( __( 'Run Import', 'wcu' ), 'primary', 'wcu_run_import' ); ?>
		</form>
		<hr/>
		<h2><?php esc_html_e( 'Export Users (CSV)', 'wcu' ); ?></h2>
		<p><?php esc_html_e( 'Exports users with phone & SMS consent (only users having phone).', 'wcu' ); ?></p>
		<p><a class="button button-primary" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Export users CSV', 'wcu' ); ?></a></p>
	</div>
	<?php
}