<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Batch insert external phones into database
 * @param array $phones Array of normalized 9-digit phone numbers
 * @return int Number of rows inserted
 */
function wcu_batch_insert_external_phones( $phones ) {
	global $wpdb;
	if ( empty( $phones ) ) return 0;
	
	$table = $wpdb->prefix . 'club_anketa_external_phones';
	$placeholders = array();
	$values = array();
	
	foreach ( $phones as $phone ) {
		$placeholders[] = '(%s)';
		$values[] = $phone;
	}
	
	$sql = "INSERT IGNORE INTO $table (phone) VALUES " . implode( ',', $placeholders );
	$prepared = $wpdb->prepare( $sql, $values );
	$wpdb->query( $prepared );
	
	return $wpdb->rows_affected;
}

add_action( 'wp_ajax_wcu_bulk_link_club_cards', function () {
	check_ajax_referer( 'wcu_bulk_link_club_cards', '_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wcu' ) ) );
	}

	$offset  = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
	$per_page = 50;

	$user_query = new WP_User_Query( array(
		'number'  => $per_page,
		'offset'  => $offset,
		'fields'  => 'ids',
		'orderby' => 'ID',
		'order'   => 'ASC',
	) );

	$user_ids  = $user_query->get_results();
	$total     = $user_query->get_total();
	$linked    = 0;
	$errors    = 0;

	foreach ( $user_ids as $uid ) {
		$result = wcu_link_coupon_to_user( (int) $uid );
		if ( $result ) {
			$linked++;
		}
	}

	$processed = $offset + count( $user_ids );
	$done      = $processed >= $total;

	wp_send_json_success( array(
		'processed' => $processed,
		'linked'    => $linked,
		'errors'    => $errors,
		'total'     => $total,
		'done'      => $done,
	) );
} );

add_filter( 'manage_users_columns', function ( $columns ) {
	$columns['wcu_phone'] = __( 'Phone Number', 'wcu' );
	$columns['wcu_sms']   = __( 'SMS accept', 'wcu' );
	$columns['wcu_call']  = __( 'Call accept', 'wcu' );
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
	if ( $name === 'wcu_call' ) {
		$consent = wcu_get_call_consent( $user_id );
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
	
	global $wpdb;
	$external_table = $wpdb->prefix . 'club_anketa_external_phones';
	
	// Handle external phone import
	if ( isset( $_POST['wcu_run_external_import'] ) && check_admin_referer( 'wcu_import_external', 'wcu_import_external_nonce' ) ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'wcu' ) );
		}
		
		$imported = 0;
		$skipped = 0;
		
		if ( isset( $_FILES['wcu_external_csv_file'] ) && $_FILES['wcu_external_csv_file']['error'] === UPLOAD_ERR_OK ) {
			$file_tmp = $_FILES['wcu_external_csv_file']['tmp_name'];
			$file_name = $_FILES['wcu_external_csv_file']['name'];
			$file_type = $_FILES['wcu_external_csv_file']['type'];
			
			// Validate file extension
			$ext = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
			if ( $ext !== 'csv' ) {
				add_settings_error( 'wcu_external_import', 'invalid_extension', __( 'Please upload a CSV file.', 'wcu' ), 'error' );
			} elseif ( ! in_array( $file_type, array( 'text/csv', 'text/plain', 'application/csv' ), true ) ) {
				add_settings_error( 'wcu_external_import', 'invalid_mime', __( 'Invalid file type.', 'wcu' ), 'error' );
			} else {
				// Additional validation: check if file can be opened and parsed as CSV
				$handle = fopen( $file_tmp, 'r' );
				if ( ! $handle ) {
					add_settings_error( 'wcu_external_import', 'file_error', __( 'Unable to read file.', 'wcu' ), 'error' );
				} else {
					$batch = array();
					while ( ( $row = fgetcsv( $handle ) ) !== false ) {
						if ( empty( $row[0] ) ) continue;
						$normalized = wcu_normalize_phone( $row[0] );
						if ( $normalized && strlen( $normalized ) === 9 ) {
							$batch[] = $normalized;
							if ( count( $batch ) >= 1000 ) {
								$imported += wcu_batch_insert_external_phones( $batch );
								$batch = array();
							}
						} else {
							$skipped++;
						}
					}
					if ( ! empty( $batch ) ) {
						$imported += wcu_batch_insert_external_phones( $batch );
					}
					fclose( $handle );
					add_settings_error( 'wcu_external_import', 'import_success', 
						sprintf( __( 'Import completed. Imported: %d, Skipped: %d', 'wcu' ), $imported, $skipped ), 'success' );
				}
			}
		} else {
			add_settings_error( 'wcu_external_import', 'no_file', __( 'Please select a file to upload.', 'wcu' ), 'error' );
		}
	}
	
	// Handle clear external phones
	if ( isset( $_POST['wcu_clear_external_phones'] ) && check_admin_referer( 'wcu_clear_external', 'wcu_clear_external_nonce' ) ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'wcu' ) );
		}
		// Use query with explicit table name (wpdb->prefix is trusted as it comes from WP config)
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}club_anketa_external_phones" );
		add_settings_error( 'wcu_external_import', 'clear_success', __( 'External phone database cleared successfully.', 'wcu' ), 'success' );
	}
	
	// Get current row count
	$external_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $external_table" );
	
	$export_nonce  = wp_create_nonce( 'wcu_export_users' );
	$example_nonce = wp_create_nonce( 'wcu_download_import_example' );
	$export_url  = add_query_arg( array( 'action'=>'wcu_export_users','_wpnonce'=>$export_nonce ), admin_url( 'admin-post.php' ) );
	$example_url = add_query_arg( array( 'action'=>'wcu_download_import_example','_wpnonce'=>$example_nonce ), admin_url( 'admin-post.php' ) );
	
	settings_errors( 'wcu_external_import' );
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
		
		<hr/>
		<h2><?php esc_html_e( 'External Phone Database (SMS Consent Whitelist)', 'wcu' ); ?></h2>
		<p><?php esc_html_e( 'Import phone numbers of non-registered users who have given SMS consent. These numbers will be searchable via the user data check shortcode.', 'wcu' ); ?></p>
		<p><strong><?php printf( esc_html__( 'Current entries in database: %d', 'wcu' ), $external_count ); ?></strong></p>
		
		<form method="post" enctype="multipart/form-data" style="margin-bottom: 1em;">
			<?php wp_nonce_field( 'wcu_import_external', 'wcu_import_external_nonce' ); ?>
			<p><label for="wcu_external_csv_file"><strong><?php esc_html_e( 'CSV File (phone numbers)', 'wcu' ); ?></strong></label><br/>
				<input type="file" id="wcu_external_csv_file" name="wcu_external_csv_file" accept=".csv,text/csv" required /></p>
			<?php submit_button( __( 'Import External Phones', 'wcu' ), 'primary', 'wcu_run_external_import', false ); ?>
		</form>
		
		<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to clear all external phone numbers?', 'wcu' ) ); ?>');">
			<?php wp_nonce_field( 'wcu_clear_external', 'wcu_clear_external_nonce' ); ?>
			<?php submit_button( __( 'Clear All External Phones', 'wcu' ), 'secondary', 'wcu_clear_external_phones', false ); ?>
		</form>
		
		<hr/>
		<h2><?php esc_html_e( 'Link Club Cards to All Users', 'wcu' ); ?></h2>
		<p><?php esc_html_e( 'Scan all users and automatically link Club Card coupons from ERP Sync based on matching phone numbers. Processes 50 users per batch.', 'wcu' ); ?></p>
		<p>
			<button type="button" class="button button-primary" id="wcu-bulk-link-btn"><?php esc_html_e( 'Link Club Cards to All Users', 'wcu' ); ?></button>
		</p>
		<div id="wcu-bulk-link-status" style="display:none;margin-top:10px;">
			<div style="background:#f0f0f1;border:1px solid #c3c4c7;padding:12px 16px;border-radius:4px;">
				<p id="wcu-bulk-link-progress" style="margin:0 0 8px;font-weight:600;"></p>
				<div style="background:#ddd;border-radius:3px;height:20px;overflow:hidden;">
					<div id="wcu-bulk-link-bar" style="background:#2271b1;height:100%;width:0;transition:width .3s;"></div>
				</div>
				<p id="wcu-bulk-link-stats" style="margin:8px 0 0;color:#50575e;"></p>
			</div>
		</div>
		<script>
		(function(){
			var btn = document.getElementById('wcu-bulk-link-btn');
			var statusBox = document.getElementById('wcu-bulk-link-status');
			var progressEl = document.getElementById('wcu-bulk-link-progress');
			var barEl = document.getElementById('wcu-bulk-link-bar');
			var statsEl = document.getElementById('wcu-bulk-link-stats');
			var nonce = <?php echo wp_json_encode( wp_create_nonce( 'wcu_bulk_link_club_cards' ) ); ?>;
			var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;

			if (!btn) return;

			btn.addEventListener('click', function(){
				btn.disabled = true;
				statusBox.style.display = 'block';
				barEl.style.width = '0';
				var totalLinked = 0;
				var totalErrors = 0;

				function runBatch(offset) {
					var data = new FormData();
					data.append('action', 'wcu_bulk_link_club_cards');
					data.append('_nonce', nonce);
					data.append('offset', offset);

					progressEl.textContent = <?php echo wp_json_encode( __( 'Processing…', 'wcu' ) ); ?>;

					fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
						.then(function(r){ return r.json(); })
						.then(function(resp){
							if (!resp.success) {
								progressEl.textContent = <?php echo wp_json_encode( __( 'Error occurred.', 'wcu' ) ); ?>;
								btn.disabled = false;
								return;
							}
							var d = resp.data;
							totalLinked += d.linked;
							totalErrors += d.errors;
							var pct = d.total > 0 ? Math.round((d.processed / d.total) * 100) : 100;
							barEl.style.width = pct + '%';
							statsEl.textContent = d.processed + ' / ' + d.total + ' ' +
								<?php echo wp_json_encode( __( 'users processed', 'wcu' ) ); ?> + ', ' +
								totalLinked + ' ' + <?php echo wp_json_encode( __( 'coupons linked', 'wcu' ) ); ?> + ', ' +
								totalErrors + ' ' + <?php echo wp_json_encode( __( 'errors', 'wcu' ) ); ?>;

							if (d.done) {
								progressEl.textContent = <?php echo wp_json_encode( __( 'Completed!', 'wcu' ) ); ?>;
								btn.disabled = false;
							} else {
								runBatch(d.processed);
							}
						})
						.catch(function(){
							progressEl.textContent = <?php echo wp_json_encode( __( 'Request failed.', 'wcu' ) ); ?>;
							btn.disabled = false;
						});
				}

				runBatch(0);
			});
		})();
		</script>
	</div>
	<?php
}