<?php
/**
 * Admin settings page for Garmin Fitness Widgets.
 * Loaded only in wp-admin context via garmin-fitness-widgets.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Menu registration
// ---------------------------------------------------------------------------

add_action( 'admin_menu', function () {
	add_options_page(
		'Garmin Widgets',
		'Garmin Widgets',
		'manage_options',
		'garmin-fitness-widgets',
		'gfw_render_settings_page'
	);
} );

// ---------------------------------------------------------------------------
// Enqueue admin assets
// ---------------------------------------------------------------------------

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( $hook !== 'settings_page_garmin-fitness-widgets' ) {
		return;
	}
	wp_enqueue_style(
		'gfw-admin',
		GFW_URL . 'assets/admin.css',
		[],
		GFW_VERSION
	);
} );

// ---------------------------------------------------------------------------
// Password encryption helpers
// ---------------------------------------------------------------------------

function gfw_encrypt_password( $plaintext ) {
	if ( empty( $plaintext ) ) {
		return '';
	}
	$key    = substr( hash( 'sha256', AUTH_KEY ), 0, 32 );
	$iv_len = openssl_cipher_iv_length( 'AES-256-CBC' );
	$iv     = openssl_random_pseudo_bytes( $iv_len );
	$enc    = openssl_encrypt( $plaintext, 'AES-256-CBC', $key, 0, $iv );
	return base64_encode( $iv . $enc );
}

function gfw_decrypt_password( $stored ) {
	if ( empty( $stored ) ) {
		return '';
	}
	$key    = substr( hash( 'sha256', AUTH_KEY ), 0, 32 );
	$raw    = base64_decode( $stored );
	$iv_len = openssl_cipher_iv_length( 'AES-256-CBC' );
	$iv     = substr( $raw, 0, $iv_len );
	$enc    = substr( $raw, $iv_len );
	return openssl_decrypt( $enc, 'AES-256-CBC', $key, 0, $iv );
}

// ---------------------------------------------------------------------------
// Save settings handler
// ---------------------------------------------------------------------------

add_action( 'admin_post_gfw_save_settings', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Недостатньо прав.' );
	}
	check_admin_referer( 'gfw_settings_nonce' );

	$email = sanitize_email( $_POST['gfw_email'] ?? '' );
	update_option( 'gfw_garmin_email', $email );

	// Only update password if a new one was submitted
	$new_password = $_POST['gfw_password'] ?? '';
	if ( $new_password !== '' ) {
		update_option( 'gfw_garmin_password', gfw_encrypt_password( $new_password ) );
	}

	$schedule = in_array( $_POST['gfw_cron_schedule'] ?? '', [ 'daily', 'twicedaily', 'hourly' ], true )
		? $_POST['gfw_cron_schedule']
		: 'daily';
	update_option( 'gfw_cron_schedule', $schedule );

	wp_redirect( admin_url( 'options-general.php?page=garmin-fitness-widgets&saved=1' ) );
	exit;
} );

// ---------------------------------------------------------------------------
// AJAX: Run fetch now
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_gfw_run_fetch', function () {
	check_ajax_referer( 'gfw_run_fetch', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'error' => 'Недостатньо прав.' ] );
	}

	// Check if shell_exec is disabled
	$disabled = array_map( 'trim', explode( ',', ini_get( 'disable_functions' ) ) );
	if ( in_array( 'shell_exec', $disabled, true ) ) {
		wp_send_json_error( [
			'error' => 'shell_exec вимкнено в php.ini (disable_functions). Запустіть скрипт вручну або через cron.',
		] );
	}

	$email          = get_option( 'gfw_garmin_email', '' );
	$encrypted_pass = get_option( 'gfw_garmin_password', '' );
	$password       = gfw_decrypt_password( $encrypted_pass );

	if ( empty( $email ) || empty( $password ) ) {
		wp_send_json_error( [ 'error' => 'Email або пароль не налаштовано. Збережіть налаштування.' ] );
	}

	$plugin_path = GFW_PATH;
	$cmd = sprintf(
		'cd %s && GARMIN_EMAIL=%s GARMIN_PASSWORD=%s python3 fetch_garmin.py 2>&1',
		escapeshellarg( $plugin_path ),
		escapeshellarg( $email ),
		escapeshellarg( $password )
	);

	$output = shell_exec( $cmd );

	// Read updated_at from JSON if fetch succeeded
	$json_file = GFW_PATH . 'garmin_data.json';
	if ( file_exists( $json_file ) ) {
		$json_data = json_decode( file_get_contents( $json_file ) );
		$updated_at = $json_data->updated_at ?? null;
	} else {
		$updated_at = null;
	}

	if ( $updated_at ) {
		wp_send_json_success( [
			'updated_at' => $updated_at,
			'output'     => $output,
		] );
	} else {
		wp_send_json_error( [
			'error'  => 'Скрипт виконано, але дані не збережено. Перевірте вивід нижче.',
			'output' => $output,
		] );
	}
} );

// ---------------------------------------------------------------------------
// Render settings page
// ---------------------------------------------------------------------------

function gfw_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$saved_email    = get_option( 'gfw_garmin_email', '' );
	$has_password   = ! empty( get_option( 'gfw_garmin_password', '' ) );
	$schedule       = get_option( 'gfw_cron_schedule', 'daily' );
	$saved_notice   = ! empty( $_GET['saved'] );

	// Read data status from JSON
	$json_file  = GFW_PATH . 'garmin_data.json';
	$updated_at = null;
	$is_fresh   = false;
	if ( file_exists( $json_file ) ) {
		$json_data = json_decode( file_get_contents( $json_file ) );
		if ( $json_data && ! empty( $json_data->updated_at ) ) {
			$updated_at    = $json_data->updated_at;
			$updated_ts    = strtotime( $updated_at );
			$hours_ago     = ( time() - $updated_ts ) / 3600;
			$is_fresh      = $hours_ago < 25;
		}
	}

	// Auto-generate cron command
	$plugin_path = rtrim( GFW_PATH, '/' );
	$schedule_map = [
		'daily'      => '0 6 * * *',
		'twicedaily' => '0 6,18 * * *',
		'hourly'     => '0 * * * *',
	];
	$cron_time = $schedule_map[ $schedule ] ?? '0 6 * * *';
	$email_placeholder = $saved_email ?: 'your@email.com';
	$cron_cmd = "{$cron_time} cd {$plugin_path} && GARMIN_EMAIL={$email_placeholder} GARMIN_PASSWORD=yourpassword python3 fetch_garmin.py >> garmin_fetch.log 2>&1";

	// Check if shell_exec is available
	$disabled       = array_map( 'trim', explode( ',', ini_get( 'disable_functions' ) ) );
	$shell_disabled = in_array( 'shell_exec', $disabled, true );

	// Format updated_at for display
	$updated_display = 'Дані відсутні';
	if ( $updated_at ) {
		$ts = strtotime( $updated_at );
		$updated_display = date_i18n( 'j F Y, H:i', $ts );
	}

	$nonce_run = wp_create_nonce( 'gfw_run_fetch' );
	$ajax_url  = admin_url( 'admin-ajax.php' );
	?>
	<div class="wrap gfw-admin-wrap">
		<h1>Garmin Fitness Widgets</h1>

		<?php if ( $saved_notice ) : ?>
		<div class="notice notice-success is-dismissible"><p>Налаштування збережено.</p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gfw_save_settings">
			<?php wp_nonce_field( 'gfw_settings_nonce' ); ?>

			<!-- Section 1: Credentials -->
			<h2>Garmin Connect</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="gfw_email">Email</label></th>
					<td>
						<input
							type="email"
							id="gfw_email"
							name="gfw_email"
							class="regular-text"
							value="<?php echo esc_attr( $saved_email ); ?>"
							placeholder="your@email.com"
						>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="gfw_password">Пароль</label></th>
					<td>
						<input
							type="password"
							id="gfw_password"
							name="gfw_password"
							class="regular-text"
							placeholder="<?php echo $has_password ? '••••••••  (залиш порожнім, щоб не змінювати)' : 'Введіть пароль'; ?>"
							autocomplete="new-password"
						>
						<label class="gfw-show-password">
							<input type="checkbox" id="gfw_show_password"> Показати пароль
						</label>
						<p class="description">
							⚠ Пароль зберігається у зашифрованому вигляді в базі даних WordPress.
						</p>
					</td>
				</tr>
			</table>

			<!-- Section 3: Cron schedule (radio) -->
			<h2>Розклад оновлень</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Оновлювати</th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="gfw_cron_schedule" value="daily" <?php checked( $schedule, 'daily' ); ?>>
								Раз на добу
							</label><br>
							<label>
								<input type="radio" name="gfw_cron_schedule" value="twicedaily" <?php checked( $schedule, 'twicedaily' ); ?>>
								Двічі на добу
							</label><br>
							<label>
								<input type="radio" name="gfw_cron_schedule" value="hourly" <?php checked( $schedule, 'hourly' ); ?>>
								Щогодини
							</label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">Cron команда для сервера</th>
					<td>
						<div class="gfw-cron-block">
							<code id="gfw-cron-cmd" class="gfw-cron-cmd"><?php echo esc_html( $cron_cmd ); ?></code>
							<button type="button" class="button gfw-copy-btn" data-target="gfw-cron-cmd">Скопіювати</button>
						</div>
						<p class="description">Додайте цей рядок у crontab: <code>crontab -e</code></p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" class="button-primary" value="Зберегти налаштування">
			</p>
		</form>

		<!-- Section 2: Status -->
		<h2>Статус даних</h2>
		<div class="gfw-status-block">
			<span class="gfw-status-dot <?php echo $is_fresh ? 'gfw-status--fresh' : ( $updated_at ? 'gfw-status--stale' : 'gfw-status--missing' ); ?>"></span>
			<span id="gfw-updated-at" class="gfw-status-text">
				<?php echo esc_html( $updated_at ? 'Останнє оновлення: ' . $updated_display : 'Дані відсутні' ); ?>
			</span>
		</div>

		<?php if ( $shell_disabled ) : ?>
		<div class="notice notice-warning">
			<p>
				<strong>Увага:</strong> <code>shell_exec</code> вимкнено в <code>php.ini</code> (<code>disable_functions</code>).
				Кнопка «Оновити зараз» не працюватиме. Використовуйте cron-команду вище.
			</p>
		</div>
		<?php endif; ?>

		<p>
			<button
				type="button"
				id="gfw-run-fetch"
				class="button button-secondary"
				<?php disabled( $shell_disabled ); ?>
			>Оновити зараз</button>
			<span id="gfw-fetch-spinner" class="gfw-spinner" style="display:none;"></span>
		</p>
		<div id="gfw-fetch-result" class="gfw-fetch-result" style="display:none;"></div>

	</div><!-- .wrap -->

	<script>
	(function () {
		// Show/hide password
		var cbShowPwd = document.getElementById('gfw_show_password');
		var pwdField  = document.getElementById('gfw_password');
		if (cbShowPwd && pwdField) {
			cbShowPwd.addEventListener('change', function () {
				pwdField.type = this.checked ? 'text' : 'password';
			});
		}

		// Copy cron command
		document.querySelectorAll('.gfw-copy-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var target = document.getElementById(this.dataset.target);
				if (!target) return;
				navigator.clipboard.writeText(target.textContent.trim()).then(function () {
					btn.textContent = 'Скопійовано!';
					setTimeout(function () { btn.textContent = 'Скопіювати'; }, 2000);
				}).catch(function () {
					// Fallback for older browsers
					var range = document.createRange();
					range.selectNode(target);
					window.getSelection().removeAllRanges();
					window.getSelection().addRange(range);
					document.execCommand('copy');
					window.getSelection().removeAllRanges();
					btn.textContent = 'Скопійовано!';
					setTimeout(function () { btn.textContent = 'Скопіювати'; }, 2000);
				});
			});
		});

		// Run fetch AJAX
		var runBtn    = document.getElementById('gfw-run-fetch');
		var spinner   = document.getElementById('gfw-fetch-spinner');
		var resultBox = document.getElementById('gfw-fetch-result');
		var updatedEl = document.getElementById('gfw-updated-at');

		if (runBtn) {
			runBtn.addEventListener('click', function () {
				runBtn.disabled = true;
				spinner.style.display = 'inline-block';
				resultBox.style.display = 'none';
				resultBox.className = 'gfw-fetch-result';

				var formData = new FormData();
				formData.append('action', 'gfw_run_fetch');
				formData.append('nonce', '<?php echo esc_js( $nonce_run ); ?>');

				fetch('<?php echo esc_js( $ajax_url ); ?>', {
					method: 'POST',
					body: formData,
					credentials: 'same-origin',
				})
				.then(function (r) { return r.json(); })
				.then(function (resp) {
					spinner.style.display = 'none';
					runBtn.disabled = false;
					resultBox.style.display = 'block';

					if (resp.success) {
						resultBox.classList.add('gfw-fetch-result--success');
						var ts = resp.data.updated_at || '';
						resultBox.textContent = 'Оновлено успішно' + (ts ? ': ' + ts : '') + '.';
						if (updatedEl && ts) {
							updatedEl.textContent = 'Останнє оновлення: ' + ts;
						}
					} else {
						resultBox.classList.add('gfw-fetch-result--error');
						var msg = (resp.data && resp.data.error) ? resp.data.error : 'Невідома помилка.';
						var out = (resp.data && resp.data.output) ? '\n\n' + resp.data.output : '';
						resultBox.textContent = msg + out;
					}
				})
				.catch(function (err) {
					spinner.style.display = 'none';
					runBtn.disabled = false;
					resultBox.style.display = 'block';
					resultBox.classList.add('gfw-fetch-result--error');
					resultBox.textContent = 'Помилка запиту: ' + err.message;
				});
			});
		}
	})();
	</script>
	<?php
}
