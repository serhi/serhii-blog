<?php
/**
 * Settings page for the Letterboxd Movies Block plugin.
 *
 * @package LetterboxdMoviesBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Register plugin settings.
 */
function lbm_register_settings() {
	register_setting(
		'lbm_settings_group',
		'lbm_username',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);
}
add_action( 'admin_init', 'lbm_register_settings' );


/**
 * Add the settings page to the Settings menu.
 */
function lbm_add_settings_page() {
	add_options_page(
		__( 'Letterboxd Movies', 'letterboxd-movies-block' ),
		__( 'Letterboxd Movies', 'letterboxd-movies-block' ),
		'manage_options',
		'letterboxd-movies-block',
		'lbm_render_settings_page'
	);
}
add_action( 'admin_menu', 'lbm_add_settings_page' );


/**
 * Render the settings page.
 */
function lbm_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle "Clear cache" action.
	if (
		isset( $_POST['lbm_clear_cache'] ) &&
		check_admin_referer( 'lbm_clear_cache_nonce', 'lbm_cache_nonce' )
	) {
		lbm_delete_all_transients();
		add_settings_error( 'lbm_messages', 'lbm_cache_cleared', __( 'Cache cleared.', 'letterboxd-movies-block' ), 'updated' );
	}

	settings_errors( 'lbm_messages' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Letterboxd Movies', 'letterboxd-movies-block' ); ?></h1>

		<form method="post" action="options.php">
			<?php
			settings_fields( 'lbm_settings_group' );
			do_settings_sections( 'letterboxd-movies-block' );
			?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="lbm_username">
							<?php esc_html_e( 'Letterboxd Username', 'letterboxd-movies-block' ); ?>
						</label>
					</th>
					<td>
						<input
							type="text"
							id="lbm_username"
							name="lbm_username"
							value="<?php echo esc_attr( get_option( 'lbm_username', '' ) ); ?>"
							class="regular-text"
							placeholder="yourusername"
						>
						<p class="description">
							<?php
							printf(
								/* translators: %s: example Letterboxd profile URL */
								esc_html__( 'Your Letterboxd username. Found in your profile URL: %s', 'letterboxd-movies-block' ),
								'<code>letterboxd.com/<strong>yourusername</strong></code>'
							);
							?>
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<hr>

		<h2><?php esc_html_e( 'Cache', 'letterboxd-movies-block' ); ?></h2>
		<p><?php esc_html_e( 'Movie data is cached for 30 minutes. Clear the cache to fetch fresh data immediately.', 'letterboxd-movies-block' ); ?></p>
		<form method="post">
			<?php wp_nonce_field( 'lbm_clear_cache_nonce', 'lbm_cache_nonce' ); ?>
			<input type="hidden" name="lbm_clear_cache" value="1">
			<?php submit_button( __( 'Clear Cache', 'letterboxd-movies-block' ), 'secondary' ); ?>
		</form>
	</div>
	<?php
}


/**
 * Delete all cached Letterboxd transients.
 */
function lbm_delete_all_transients() {
	// No WordPress API exists for bulk transient deletion by pattern; direct query is required here.
	global $wpdb;
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options}
			 WHERE option_name LIKE %s
			    OR option_name LIKE %s",
			'_transient_lbm_%',
			'_transient_timeout_lbm_%'
		)
	);
}
