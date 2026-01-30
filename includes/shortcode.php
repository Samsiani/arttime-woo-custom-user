<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Assets for user data check
 */
function wcu_udc_enqueue_assets() {
	wp_enqueue_style( 'wcu-shortcode', WCU_URL . 'assets/css/shortcode.css', array(), WCU_VERSION );
	wp_enqueue_script( 'wcu-shortcode', WCU_URL . 'assets/js/shortcode.js', array(), WCU_VERSION, true );
	wp_localize_script( 'wcu-shortcode', 'wcuUdc', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'wcu_udc_ajax' ),
		'i18n' => array(
			'searching' => __( 'Searching…', 'wcu' ),
			'no_query'  => __( 'Please enter a value to search.', 'wcu' ),
			'error'     => __( 'Something went wrong. Please try again.', 'wcu' ),
		),
	) );
}

/**
 * Build result HTML - advanced layout
 */
function wcu_udc_render_results_html( $user ) {
	if ( ! ( $user instanceof WP_User ) ) return '';

	$data = array(
		'email'      => $user->user_email,
		'phone'      => wcu_get_user_phone( $user->ID ),
		'personal'   => get_user_meta( $user->ID, '_personal_id', true ),
		'club_card'  => get_user_meta( $user->ID, '_club_card_coupon', true ),
		'sms_consent'=> wcu_get_sms_consent( $user->ID ),
	);

	$labels = array(
		'email'      => 'ელ.ფოსტა',
		'phone'      => 'ტელეფონის ნომერი',
		'personal'   => 'პირადი ნომერი',
		'club_card'  => 'კლუბის ბარათი',
		'sms_consent'=> 'SMS თანხმობა',
	);

	$filled   = array();
	$missing  = array();

	foreach ( $data as $key => $value ) {
		if ( $key === 'sms_consent' ) {
			if ( $value === 'yes' || $value === 'no' ) {
				$filled[] = $labels[$key];
			} else {
				$missing[] = $labels[$key];
			}
			continue;
		}
		if ( $value !== '' ) {
			$filled[] = $labels[$key];
		} else {
			$missing[] = $labels[$key];
		}
	}

	$sms_display = '';
	if ( $data['sms_consent'] === 'yes' ) {
		$sms_display = '<span class="wcu-badge wcu-badge--yes">' . esc_html__( 'ვეთანხმები', 'wcu' ) . '</span>';
	} elseif ( $data['sms_consent'] === 'no' ) {
		$sms_display = '<span class="wcu-badge wcu-badge--no">' . esc_html__( 'არ ვეთანხმები', 'wcu' ) . '</span>';
	} else {
		$sms_display = '<span class="wcu-badge wcu-badge--unset">' . esc_html__( 'არ არის არჩეული', 'wcu' ) . '</span>';
	}

	ob_start();
	?>
	<div class="wcu-udc-results-layout">

		<div class="wcu-udc-panel wcu-udc-panel--details">
			<div class="wcu-udc-panel__header">
				<h3><?php esc_html_e( 'მომხმარებელი', 'wcu' ); ?></h3>
			</div>
			<div class="wcu-udc-panel__body">
				<ul class="wcu-detail-list">
					<li>
						<span class="wcu-dl-label"><?php echo esc_html( $labels['email'] ); ?>:</span>
						<span class="wcu-dl-value"><?php echo $data['email'] ? esc_html( $data['email'] ) : '<em>' . esc_html__( 'არ არის', 'wcu' ) . '</em>'; ?></span>
					</li>
					<li>
						<span class="wcu-dl-label"><?php echo esc_html( $labels['phone'] ); ?>:</span>
						<span class="wcu-dl-value"><?php echo $data['phone'] ? esc_html( $data['phone'] ) : '<em>' . esc_html__( 'არ არის', 'wcu' ) . '</em>'; ?></span>
					</li>
					<li>
						<span class="wcu-dl-label"><?php echo esc_html( $labels['personal'] ); ?>:</span>
						<span class="wcu-dl-value"><?php echo $data['personal'] ? esc_html( $data['personal'] ) : '<em>' . esc_html__( 'არ არის', 'wcu' ) . '</em>'; ?></span>
					</li>
					<li>
						<span class="wcu-dl-label"><?php echo esc_html( $labels['club_card'] ); ?>:</span>
						<span class="wcu-dl-value"><?php echo $data['club_card'] ? esc_html( $data['club_card'] ) : '<em>' . esc_html__( 'არ არის', 'wcu' ) . '</em>'; ?></span>
					</li>
					<li>
						<span class="wcu-dl-label"><?php echo esc_html( $labels['sms_consent'] ); ?>:</span>
						<span class="wcu-dl-value"><?php echo $sms_display; ?></span>
					</li>
				</ul>
			</div>
		</div>

		<div class="wcu-udc-panel wcu-udc-panel--status">
			<div class="wcu-udc-panel__header">
				<h3><?php esc_html_e( 'ინფორმაცია', 'wcu' ); ?></h3>
			</div>
			<div class="wcu-udc-panel__body wcu-udc-status-body">
				<div class="wcu-status-section">
					<h4><?php esc_html_e( 'შევსებული ველები', 'wcu' ); ?></h4>
					<div class="wcu-chip-row">
						<?php foreach ( $filled as $f ): ?>
							<span class="wcu-chip wcu-chip--filled"><?php echo esc_html( $f ); ?></span>
						<?php endforeach; if ( empty( $filled ) ) echo '<span class="wcu-empty-note">' . esc_html__( 'არაფერი', 'wcu' ) . '</span>'; ?>
					</div>
				</div>
				<div class="wcu-status-section">
					<h4><?php esc_html_e( 'შეუვსებელი ველები', 'wcu' ); ?></h4>
					<div class="wcu-chip-row">
						<?php foreach ( $missing as $m ): ?>
							<span class="wcu-chip wcu-chip--missing"><?php echo esc_html( $m ); ?></span>
						<?php endforeach; if ( empty( $missing ) ) echo '<span class="wcu-empty-note">' . esc_html__( 'არაფერი', 'wcu' ) . '</span>'; ?>
					</div>
				</div>
			</div>
		</div>

	</div>
	<?php
	return ob_get_clean();
}

/**
 * AJAX handler
 */
add_action( 'wp_ajax_wcu_udc_search', 'wcu_udc_ajax_handler' );
add_action( 'wp_ajax_nopriv_wcu_udc_search', 'wcu_udc_ajax_handler' );
function wcu_udc_ajax_handler() {
	check_ajax_referer( 'wcu_udc_ajax', 'nonce' );
	$query = isset( $_POST['query'] ) ? trim( (string) wp_unslash( $_POST['query'] ) ) : '';
	if ( $query === '' ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a value to search.', 'wcu' ) ) );
	}

	$result = null;

	if ( is_email( $query ) ) {
		$result = get_user_by( 'email', $query );
	}

	if ( ! $result && wcu_is_phone_like( $query ) ) {
		$norm = wcu_normalize_phone( $query );
		if ( $norm ) {
			$users = get_users( array(
				'meta_key' => 'billing_phone',
				'meta_value' => $norm,
				'number' => 1,
				'count_total' => false,
			) );
			if ( ! empty( $users ) ) $result = $users[0];
		}
	}

	if ( ! $result && wcu_validate_personal_id( $query ) ) {
		$users = get_users( array(
			'meta_key' => '_personal_id',
			'meta_value' => $query,
			'number' => 1,
			'count_total' => false,
		) );
		if ( ! empty( $users ) ) $result = $users[0];
	}

	if ( ! $result ) {
		$users = get_users( array(
			'meta_key' => '_club_card_coupon',
			'meta_value' => $query,
			'number' => 1,
			'count_total' => false,
		) );
		if ( ! empty( $users ) ) $result = $users[0];
	}

	if ( ! $result ) {
		wp_send_json_error( array( 'message' => __( 'No matching user was found.', 'wcu' ) ) );
	}

	wp_send_json_success( array( 'html' => wcu_udc_render_results_html( $result ) ) );
}

/**
 * Shortcode
 */
add_shortcode( 'user_data_check', function () {
	wcu_udc_enqueue_assets();
	ob_start(); ?>
	<div class="wcu-udc wcu-udc--modern">
		<form method="post" class="wcu-udc__form" novalidate>
			<label for="wcu_udc_query" class="wcu-udc__label"><?php esc_html_e( 'ტელეფონით, ელფოსტით, პირადი ID-ით ან კლუბის ბარათით ძიება', 'wcu' ); ?></label>
			<div class="wcu-udc__input-group">
				<input type="text" id="wcu_udc_query" name="wcu_udc_query" class="wcu-udc__input" placeholder="<?php esc_attr_e( 'მაგ: user@example.com, +995 599..., 12345678901, CARD2024', 'wcu' ); ?>" />
				<button type="submit" class="wcu-udc__btn"><?php esc_html_e( 'ძიება', 'wcu' ); ?></button>
			</div>
		</form>

		<div class="wcu-udc__notice wcu-udc__notice--error" data-wcu-udc-error style="display:none;"></div>
		<div class="wcu-udc__loading" data-wcu-udc-loading style="display:none;">
			<span class="wcu-spinner" aria-hidden="true"></span>
			<span class="wcu-udc__loading-text"><?php esc_html_e( 'Searching…', 'wcu' ); ?></span>
		</div>
		<div class="wcu-udc__results" data-wcu-udc-results></div>
	</div>
	<?php
	return ob_get_clean();
} );

/**
 * Print terms button shortcode
 */
add_shortcode( 'wcu_print_terms_button', function ( $atts ) {
	$atts = shortcode_atts( array(
		'label' => __( 'Print Terms', 'wcu' ),
		'class' => 'button'
	), $atts, 'wcu_print_terms_button' );
	return sprintf(
		'<a class="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
		esc_attr( $atts['class'] ),
		esc_url( wcu_get_print_terms_url() ),
		esc_html( $atts['label'] )
	);
} );