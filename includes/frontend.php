<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wcu_get_edit_account_url() {
	if ( function_exists( 'wc_get_account_endpoint_url' ) )
		return wc_get_account_endpoint_url( 'edit-account' );
	return wc_get_endpoint_url( 'edit-account', '', wc_get_page_permalink( 'myaccount' ) );
}

add_filter( 'woocommerce_locate_template', function ( $template, $name, $path ) {
	$plugin_path = WCU_DIR . 'templates/';
	$targets = array(
		'myaccount/form-edit-account.php',
		'myaccount/form-login.php',
	);
	if ( in_array( $name, $targets, true ) ) {
		$file = $plugin_path . $name;
		if ( file_exists( $file ) ) return $file;
	}
	return $template;
}, 10, 3 );

add_action( 'wp_enqueue_scripts', function () {
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		wp_enqueue_style( 'wcu-account', WCU_URL . 'assets/css/account.css', array(), WCU_VERSION );
		wp_enqueue_style( 'wcu-frontend', WCU_URL . 'assets/css/frontend.css', array(), WCU_VERSION );
		wp_enqueue_script( 'wcu-club-card', WCU_URL . 'assets/js/club-card.js', array(), WCU_VERSION, true );
		wp_enqueue_script( 'wcu-account', WCU_URL . 'assets/js/account.js', array(), WCU_VERSION, true );
	}
}, 20 );

add_action( 'woocommerce_register_form', function () {
	wp_enqueue_style( 'wcu-frontend', WCU_URL . 'assets/css/frontend.css', array(), WCU_VERSION );

	$first_name  = isset( $_POST['account_first_name'] ) ? wp_unslash( (string) $_POST['account_first_name'] ) : '';
	$last_name   = isset( $_POST['account_last_name'] ) ? wp_unslash( (string) $_POST['account_last_name'] ) : '';
	$personal_id = isset( $_POST['_personal_id'] ) ? wp_unslash( (string) $_POST['_personal_id'] ) : '';
	$phone       = isset( $_POST['billing_phone'] ) ? wp_unslash( (string) $_POST['billing_phone'] ) : '';
	$email       = isset( $_POST['email'] ) ? wp_unslash( (string) $_POST['email'] ) : '';
	$sms_consent = isset( $_POST['_sms_consent'] ) ? strtolower( (string) wp_unslash( $_POST['_sms_consent'] ) ) : 'yes';
	if ( ! in_array( $sms_consent, array( 'yes','no' ), true ) ) $sms_consent = 'yes';

	$terms_html = wcu_get_terms_content_html();
	$terms_url  = wcu_get_terms_url();
	$print_url  = wcu_get_print_terms_url();
	?>
	<p class="form-row form-row-first">
		<label for="reg_first_name"><?php esc_html_e( 'First name', 'wcu' ); ?> <span class="required">*</span></label>
		<input type="text" class="input-text" name="account_first_name" id="reg_first_name" value="<?php echo esc_attr( $first_name ); ?>" />
	</p>
	<p class="form-row form-row-last">
		<label for="reg_last_name"><?php esc_html_e( 'Last name', 'wcu' ); ?> <span class="required">*</span></label>
		<input type="text" class="input-text" name="account_last_name" id="reg_last_name" value="<?php echo esc_attr( $last_name ); ?>" />
	</p>
	<p class="form-row form-row-wide">
		<label for="reg_personal_id"><?php esc_html_e( 'Personal ID Number', 'wcu' ); ?></label>
		<input type="text" class="input-text" name="_personal_id" id="reg_personal_id" value="<?php echo esc_attr( $personal_id ); ?>" placeholder="<?php esc_attr_e( '11 digits (optional)', 'wcu' ); ?>" />
	</p>
	<p class="form-row form-row-wide">
		<label for="reg_billing_phone"><?php esc_html_e( 'Phone', 'wcu' ); ?> <span class="required">*</span></label>
		<input type="tel" class="input-text" name="billing_phone" id="reg_billing_phone" value="<?php echo esc_attr( $phone ); ?>" placeholder="<?php esc_attr_e( 'e.g. +995 599 123 456', 'wcu' ); ?>" />
	</p>
	<p class="form-row form-row-wide">
		<label for="reg_email"><?php esc_html_e( 'Email', 'woocommerce' ); ?> <span class="required">*</span></label>
		<input type="email" class="input-text" name="email" id="reg_email" value="<?php echo esc_attr( $email ); ?>" />
	</p>
	<p class="form-row form-row-wide">
		<label for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
		<input type="password" class="input-text" name="password" id="reg_password" />
	</p>
	<p class="form-row form-row-wide">
		<div class="wcu-inline-control wcu-inline-control--center wcu-inline-control--highlight" style="width:100%;">
			<span class="wcu-inline-control__label"><?php esc_html_e( 'Would you like to receive SMS', 'wcu' ); ?></span>
			<div class="wcu-radio-inline">
				<label><input type="radio" name="_sms_consent" value="yes" <?php checked( $sms_consent,'yes'); ?> /> <?php esc_html_e( 'Yes', 'wcu' ); ?></label>
				<label><input type="radio" name="_sms_consent" value="no" <?php checked( $sms_consent,'no'); ?> /> <?php esc_html_e( 'No', 'wcu' ); ?></label>
			</div>
		</div>
	</p>
	<p class="form-row form-row-wide" style="display:flex;flex-direction:column;gap:8px;">
		<label>
			<input type="checkbox" name="wcu_terms_agree" id="wcu_terms_agree" value="1" <?php checked( isset($_POST['wcu_terms_agree']) ); ?> />
			<?php esc_html_e( 'I agree to the terms and conditions', 'wcu' ); ?>
		</label>
		<div class="wcu-terms-actions" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
			<?php if ( $terms_url ) : ?>
				<a class="wcu-link" href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Read the full terms & conditions', 'wcu' ); ?></a>
			<?php elseif ( $terms_html ) : ?>
				<details class="wcu-terms-details" style="margin-top:0;">
					<summary style="cursor:pointer;"><?php esc_html_e( 'Read the full terms & conditions', 'wcu' ); ?></summary>
					<div class="wcu-terms-body" style="margin-top:8px;"><?php echo $terms_html; ?></div>
				</details>
			<?php endif; ?>
			<a class="button" href="<?php echo esc_url( $print_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Print Terms', 'wcu' ); ?></a>
		</div>
	</p>
	<?php
},10,1);

add_action( 'woocommerce_created_customer', function ( $customer_id ) {
	$val = isset( $_POST['_sms_consent'] ) ? strtolower( (string) wp_unslash( $_POST['_sms_consent'] ) ) : '';
	if ( in_array( $val, array( 'yes','no' ), true ) ) update_user_meta( $customer_id, '_sms_consent', $val );

	if ( isset( $_POST['billing_phone'] ) )
		update_user_meta( $customer_id, 'billing_phone', (string) wp_unslash( $_POST['billing_phone'] ) );

	if ( isset( $_POST['_personal_id'] ) )
		update_user_meta( $customer_id, '_personal_id', sanitize_text_field( wp_unslash( $_POST['_personal_id'] ) ) );

	if ( isset( $_POST['wcu_terms_agree'] ) )
		update_user_meta( $customer_id, '_wcu_terms_accepted', current_time( 'mysql' ) );
},10,1);

add_action( 'woocommerce_save_account_details', function ( $user_id ) {
	if ( isset( $_POST['account_phone'] ) )
		update_user_meta( $user_id, 'billing_phone', (string) wp_unslash( $_POST['account_phone'] ) );
	if ( isset( $_POST['account_personal_id'] ) )
		update_user_meta( $user_id, '_personal_id', sanitize_text_field( wp_unslash( $_POST['account_personal_id'] ) ) );
	if ( isset( $_POST['account_club_card'] ) || isset( $_POST['wcu_has_club_card'] ) ) {
		$cc = isset( $_POST['account_club_card'] ) ? (string) wp_unslash( $_POST['account_club_card'] ) : '';
		update_user_meta( $user_id, '_club_card_coupon', $cc );
	}
	if ( isset( $_POST['account_sms_consent'] ) ) {
		$val = strtolower( (string) wp_unslash( $_POST['account_sms_consent'] ) );
		if ( in_array( $val, array('yes','no'), true ) )
			update_user_meta( $user_id, '_sms_consent', $val );
	}
	if ( isset( $_POST['wcu_terms_agree'] ) )
		update_user_meta( $user_id, '_wcu_terms_accepted', current_time( 'mysql' ) );
	else
		delete_user_meta( $user_id, '_wcu_terms_accepted' );
},10,1);

add_action( 'template_redirect', function () {
	if ( ! isset( $_GET['wcu-print-terms'] ) ) return;
	$terms_html = wcu_get_terms_content_html();
	nocache_headers();
	header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
	$title = get_bloginfo( 'name' ) . ' — ' . __( 'Terms & Conditions', 'wcu' );
	?>
	<!DOCTYPE html><html <?php language_attributes(); ?>><head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title><?php echo esc_html( $title ); ?></title>
	<style>
		body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;margin:0;background:#fff;color:#111827}
		.print-wrap{max-width:820px;margin:24px auto;padding:0 20px}
		h1{font-size:22px;margin:0 0 16px}
		.wcu-terms-print p{margin:0 0 12px;line-height:1.55}
		.header-actions{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
		.header-actions .btn{background:#111827;color:#fff;padding:8px 14px;border-radius:6px;text-decoration:none;font-size:13px}
		.header-actions .btn:hover{opacity:.85}
		@media print{.no-print{display:none!important}}
	</style></head><body>
	<div class="print-wrap">
		<div class="header-actions no-print">
			<h1><?php esc_html_e( 'Terms & Conditions', 'wcu' ); ?></h1>
			<div>
				<a href="#" onclick="window.print();return false;" class="btn"><?php esc_html_e('Print','wcu'); ?></a>
				<a href="#" onclick="window.close();return false;" class="btn"><?php esc_html_e('Close','wcu'); ?></a>
			</div>
		</div>
		<div class="wcu-terms-print">
			<?php
			if ( $terms_html ) {
				echo $terms_html;
			} else {
				echo '<p>' . esc_html__( 'Terms & Conditions content is not configured.', 'wcu' ) . '</p>';
			}
			?>
		</div>
	</div>
	<script>setTimeout(function(){try{window.print()}catch(e){}},300);</script>
	</body></html>
	<?php
	exit;
} );

add_filter( 'woocommerce_save_account_details_required_fields', function( $fields ){
	unset( $fields['account_display_name'] );
	return $fields;
}, 20 );