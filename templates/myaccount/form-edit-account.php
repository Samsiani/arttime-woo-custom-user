<?php
defined( 'ABSPATH' ) || exit;
do_action( 'woocommerce_before_edit_account_form' ); ?>
<form class="woocommerce-EditAccountForm edit-account" action="" method="post" <?php do_action( 'woocommerce_edit_account_form_tag' ); ?> >
	<?php do_action( 'woocommerce_edit_account_form_start' ); ?>
	<fieldset class="wcu-fieldset">
		<legend><?php esc_html_e( 'Additional Details', 'wcu' ); ?></legend>
		<div class="wcu-grid">
			<p class="form-row wcu-half">
				<label for="account_first_name"><?php esc_html_e( 'First name', 'woocommerce' ); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr( wp_get_current_user()->first_name ); ?>" />
			</p>
			<p class="form-row wcu-half">
				<label for="account_last_name"><?php esc_html_e( 'Last name', 'woocommerce' ); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr( wp_get_current_user()->last_name ); ?>" />
			</p>
			<p class="form-row wcu-half">
				<label for="account_personal_id"><?php esc_html_e( 'Personal ID Number', 'wcu' ); ?> <small><?php esc_html_e( '(optional)', 'wcu' ); ?></small></label>
				<input type="text" name="account_personal_id" id="account_personal_id" class="input-text"
				       value="<?php echo esc_attr( get_user_meta( get_current_user_id(), '_personal_id', true ) ); ?>"
				       inputmode="numeric" pattern="^\d{11}$" maxlength="11" placeholder="<?php esc_attr_e( '11 digits', 'wcu' ); ?>" />
			</p>
			<p class="form-row wcu-half">
				<label for="account_phone"><?php esc_html_e( 'Phone', 'wcu' ); ?> <span class="required">*</span></label>
				<input type="tel" name="account_phone" id="account_phone" class="input-text"
				       value="<?php echo esc_attr( get_user_meta( get_current_user_id(), 'billing_phone', true ) ); ?>"
				       autocomplete="tel" inputmode="tel" pattern="^[0-9+\s\-\(\)]+$"
				       placeholder="<?php esc_attr_e( 'e.g. +995 599 123 456', 'wcu' ); ?>" />
			</p>
			<p class="form-row wcu-half">
				<label for="account_email"><?php esc_html_e( 'Email', 'woocommerce' ); ?> <span class="required">*</span></label>
				<input type="email" class="input-text" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" />
			</p>
			<?php
			$user_id = get_current_user_id();
			$sms_consent = wcu_get_sms_consent( $user_id );
			if ( $sms_consent === '' ) $sms_consent = 'yes';
			$call_consent = wcu_get_call_consent( $user_id );
			if ( $call_consent === '' ) $call_consent = 'yes';
			?>
			<p class="form-row wcu-half" style="grid-column:1 / -1;">
				<div class="wcu-inline-control wcu-inline-control--center wcu-inline-control--highlight" style="width:100%;">
					<span class="wcu-inline-control__label"><?php esc_html_e( 'Would you like to receive SMS', 'wcu' ); ?></span>
					<div class="wcu-radio-inline">
						<label><input type="radio" name="account_sms_consent" value="yes" <?php checked( $sms_consent,'yes'); ?> /> <?php esc_html_e( 'Yes', 'wcu' ); ?></label>
						<label><input type="radio" name="account_sms_consent" value="no"  <?php checked( $sms_consent,'no');  ?> /> <?php esc_html_e( 'No', 'wcu' ); ?></label>
					</div>
				</div>
			</p>
			<p class="form-row wcu-half" style="grid-column:1 / -1;">
				<div class="wcu-inline-control wcu-inline-control--center wcu-inline-control--highlight" style="width:100%;">
					<span class="wcu-inline-control__label"><?php esc_html_e( 'თანხმობა სატელეფონო ზარზე', 'wcu' ); ?></span>
					<div class="wcu-radio-inline">
						<label><input type="radio" name="account_call_consent" value="yes" <?php checked( $call_consent,'yes'); ?> /> <?php esc_html_e( 'დიახ', 'wcu' ); ?></label>
						<label><input type="radio" name="account_call_consent" value="no"  <?php checked( $call_consent,'no');  ?> /> <?php esc_html_e( 'არა', 'wcu' ); ?></label>
					</div>
				</div>
			</p>
			<?php
			$terms_text_html = wcu_get_terms_content_html();
			$terms_url       = wcu_get_terms_url();
			$print_url       = wcu_get_print_terms_url();
			?>
			<p class="form-row wcu-half" style="grid-column:1 / -1;display:flex;flex-direction:column;gap:8px;">
				<label>
					<input type="checkbox" name="wcu_terms_agree" id="wcu_terms_agree" value="1" <?php checked( get_user_meta( $user_id,'_wcu_terms_accepted', true ) ); ?> />
					<?php esc_html_e( 'I agree to the terms and conditions', 'wcu' ); ?>
				</label>
				<div class="wcu-terms-actions" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
					<?php if ( $terms_url ) : ?>
						<a class="wcu-link" href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Read the full terms & conditions', 'wcu' ); ?></a>
					<?php elseif ( $terms_text_html ) : ?>
						<details class="wcu-terms-details" style="margin-top:0;">
							<summary style="cursor:pointer;"><?php esc_html_e( 'Read the full terms & conditions', 'wcu' ); ?></summary>
							<div class="wcu-terms-body" style="margin-top:8px;"><?php echo $terms_text_html; ?></div>
						</details>
					<?php endif; ?>
					<a class="button" href="<?php echo esc_url( $print_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Print Terms', 'wcu' ); ?></a>
				</div>
			</p>
			<?php
			$club_card = get_user_meta( $user_id, '_club_card_coupon', true );
			$has_club  = ! empty( $club_card );
			?>
			<p class="form-row wcu-half">
				<label>
					<input type="checkbox" id="wcu_has_club_card_account" name="wcu_has_club_card" value="1" <?php checked( $has_club ); ?>
						data-wcu-toggle data-target="#wcu_club_card_account" />
					<?php esc_html_e( 'I have a Club Card', 'wcu' ); ?>
				</label>
				<span class="wcu-help"><?php esc_html_e( 'If you have a Club Card, check this to enter your card code.', 'wcu' ); ?></span>
			</p>
			<p class="form-row wcu-half" id="wcu_club_card_account">
				<label for="account_club_card"><?php esc_html_e( 'Club Card', 'wcu' ); ?> <small><?php esc_html_e( '(optional)', 'wcu' ); ?></small></label>
				<input type="text" name="account_club_card" id="account_club_card" class="input-text" value="<?php echo esc_attr( $club_card ); ?>" <?php echo $has_club ? '' : 'disabled'; ?> />
			</p>
		</div>
	</fieldset>

	<fieldset>
		<legend><?php esc_html_e( 'Password change', 'woocommerce' ); ?></legend>
		<p class="form-row wcu-half">
			<label for="password_current"><?php esc_html_e( 'Current password (leave blank to leave unchanged)', 'woocommerce' ); ?></label>
			<input type="password" class="input-text" name="password_current" id="password_current" autocomplete="off" />
		</p>
		<p class="form-row wcu-half">
			<label for="password_1"><?php esc_html_e( 'New password (leave blank to leave unchanged)', 'woocommerce' ); ?></label>
			<input type="password" class="input-text" name="password_1" id="password_1" autocomplete="off" />
		</p>
		<p class="form-row wcu-half">
			<label for="password_2"><?php esc_html_e( 'Confirm new password', 'woocommerce' ); ?></label>
			<input type="password" class="input-text" name="password_2" id="password_2" autocomplete="off" />
		</p>
	</fieldset>

	<?php do_action( 'woocommerce_edit_account_form' ); ?>
	<p>
		<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
		<button type="submit" class="woocommerce-Button button" name="save_account_details" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
		<input type="hidden" name="action" value="save_account_details" />
	</p>
	<?php do_action( 'woocommerce_edit_account_form_end' ); ?>
</form>
<?php do_action( 'woocommerce_after_edit_account_form' ); ?>