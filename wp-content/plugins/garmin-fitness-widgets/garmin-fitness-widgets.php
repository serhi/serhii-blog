<?php
/**
 * Plugin Name: Garmin Fitness Widgets
 * Description: Відображає дані Garmin Connect (вага, остання активність, відстань за тиждень) через shortcode.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GFW_DIR', plugin_dir_path( __FILE__ ) );
define( 'GFW_URL', plugin_dir_url( __FILE__ ) );

// ---------------------------------------------------------------------------
// Assets
// ---------------------------------------------------------------------------

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'gfw-widgets',
		GFW_URL . 'assets/widgets.css',
		[],
		'1.0.0'
	);
	wp_enqueue_script(
		'gfw-widgets',
		GFW_URL . 'assets/widgets.js',
		[],
		'1.0.0',
		true
	);
} );

// ---------------------------------------------------------------------------
// JSON helper
// ---------------------------------------------------------------------------

function gfw_get_data(): ?object {
	$file = GFW_DIR . 'garmin_data.json';
	if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
		return null;
	}
	$json = file_get_contents( $file );
	if ( $json === false ) {
		return null;
	}
	$data = json_decode( $json );
	return ( $data && json_last_error() === JSON_ERROR_NONE ) ? $data : null;
}

// ---------------------------------------------------------------------------
// Date formatting helpers
// ---------------------------------------------------------------------------

/**
 * Format "2026-03-27" → "27 бер"
 */
function gfw_format_short_date( string $date_str ): string {
	$months_uk = [
		1  => 'січ', 2  => 'лют', 3  => 'бер', 4  => 'кві',
		5  => 'тра', 6  => 'чер', 7  => 'лип', 8  => 'сер',
		9  => 'вер', 10 => 'жов', 11 => 'лис', 12 => 'гру',
	];
	$ts = strtotime( $date_str );
	if ( ! $ts ) {
		return esc_html( $date_str );
	}
	$day   = (int) date( 'j', $ts );
	$month = (int) date( 'n', $ts );
	return $day . ' ' . ( $months_uk[ $month ] ?? '' );
}

/**
 * Format "2026-03-26" → "26 бер 2026"
 */
function gfw_format_long_date( string $date_str ): string {
	$months_uk = [
		1  => 'січ', 2  => 'лют', 3  => 'бер', 4  => 'кві',
		5  => 'тра', 6  => 'чер', 7  => 'лип', 8  => 'сер',
		9  => 'вер', 10 => 'жов', 11 => 'лис', 12 => 'гру',
	];
	$ts = strtotime( $date_str );
	if ( ! $ts ) {
		return esc_html( $date_str );
	}
	$day   = (int) date( 'j', $ts );
	$month = (int) date( 'n', $ts );
	$year  = date( 'Y', $ts );
	return $day . ' ' . ( $months_uk[ $month ] ?? '' ) . ' ' . $year;
}

// ---------------------------------------------------------------------------
// Trend helper
// ---------------------------------------------------------------------------

function gfw_trend_html( string $trend ): string {
	$map = [
		'down'   => [ 'class' => 'gfw-trend--down',   'arrow' => '↓' ],
		'up'     => [ 'class' => 'gfw-trend--up',     'arrow' => '↑' ],
		'stable' => [ 'class' => 'gfw-trend--stable', 'arrow' => '—' ],
	];
	$t = $map[ $trend ] ?? $map['stable'];
	return '<span class="gfw-trend ' . esc_attr( $t['class'] ) . '">' . $t['arrow'] . '</span>';
}

// ---------------------------------------------------------------------------
// Shortcode: [garmin_weight]
// ---------------------------------------------------------------------------

add_shortcode( 'garmin_weight', function (): string {
	$data = gfw_get_data();
	if ( ! $data || empty( $data->weight ) ) {
		return '';
	}
	$w = $data->weight;

	$number = isset( $w->current_kg ) ? number_format( (float) $w->current_kg, 1 ) : '';
	$trend  = isset( $w->trend ) ? gfw_trend_html( $w->trend ) : '';
	$meta   = isset( $w->measured_date ) ? 'виміряно ' . gfw_format_short_date( $w->measured_date ) : '';

	ob_start();
	?>
	<div class="gfw-widget gfw-weight">
		<div class="gfw-label">Вага</div>
		<div class="gfw-value">
			<span class="gfw-number"><?php echo esc_html( $number ); ?></span>
			<span class="gfw-unit">кг</span>
			<?php echo $trend; ?>
		</div>
		<div class="gfw-meta"><?php echo esc_html( $meta ); ?></div>
	</div>
	<?php
	return ob_get_clean();
} );

// ---------------------------------------------------------------------------
// Shortcode: [garmin_last_activity]
// ---------------------------------------------------------------------------

add_shortcode( 'garmin_last_activity', function (): string {
	$data = gfw_get_data();
	if ( ! $data || empty( $data->last_activity ) ) {
		return '';
	}
	$a = $data->last_activity;

	$type_label  = isset( $a->type_label ) ? $a->type_label : ( $a->type ?? '' );
	$duration    = isset( $a->duration_min ) ? (int) $a->duration_min : null;
	$distance    = isset( $a->distance_km ) && $a->distance_km !== null ? number_format( (float) $a->distance_km, 1 ) : null;
	$avg_hr      = isset( $a->avg_hr ) && $a->avg_hr !== null ? (int) $a->avg_hr : null;
	$date_str    = isset( $a->date ) ? gfw_format_long_date( $a->date ) : '';

	ob_start();
	?>
	<div class="gfw-widget gfw-activity">
		<div class="gfw-label">Остання активність</div>
		<div class="gfw-activity-type"><?php echo esc_html( $type_label ); ?></div>
		<div class="gfw-activity-stats">
			<?php if ( $duration !== null ) : ?>
				<span class="gfw-stat">
					<span class="gfw-stat-value"><?php echo esc_html( $duration ); ?></span><span class="gfw-stat-unit">хв</span>
				</span>
			<?php endif; ?>
			<?php if ( $distance !== null ) : ?>
				<span class="gfw-stat">
					<span class="gfw-stat-value"><?php echo esc_html( $distance ); ?></span><span class="gfw-stat-unit">км</span>
				</span>
			<?php endif; ?>
			<?php if ( $avg_hr !== null ) : ?>
				<span class="gfw-stat">
					<span class="gfw-stat-value"><?php echo esc_html( $avg_hr ); ?></span><span class="gfw-stat-unit">уд/хв</span>
				</span>
			<?php endif; ?>
		</div>
		<div class="gfw-meta"><?php echo esc_html( $date_str ); ?></div>
	</div>
	<?php
	return ob_get_clean();
} );

// ---------------------------------------------------------------------------
// Shortcode: [garmin_weekly_distance]
// ---------------------------------------------------------------------------

add_shortcode( 'garmin_weekly_distance', function (): string {
	$data = gfw_get_data();
	if ( ! $data || ! isset( $data->weekly_distance_km ) ) {
		return '';
	}

	$km = number_format( (float) $data->weekly_distance_km, 1 );

	ob_start();
	?>
	<div class="gfw-widget gfw-weekly">
		<div class="gfw-label">За тиждень</div>
		<div class="gfw-value">
			<span class="gfw-number"><?php echo esc_html( $km ); ?></span>
			<span class="gfw-unit">км</span>
		</div>
		<div class="gfw-meta">активні кілометри</div>
	</div>
	<?php
	return ob_get_clean();
} );

// ---------------------------------------------------------------------------
// Shortcode: [garmin_widgets] — all three together
// ---------------------------------------------------------------------------

add_shortcode( 'garmin_widgets', function (): string {
	$weight   = do_shortcode( '[garmin_weight]' );
	$activity = do_shortcode( '[garmin_last_activity]' );
	$weekly   = do_shortcode( '[garmin_weekly_distance]' );

	if ( ! $weight && ! $activity && ! $weekly ) {
		return '';
	}

	return '<div class="gfw-container">' . $weight . $activity . $weekly . '</div>';
} );

// ---------------------------------------------------------------------------
// Admin: settings page
// ---------------------------------------------------------------------------

add_action( 'admin_menu', function () {
	add_options_page(
		'Garmin Fitness Widgets',
		'Garmin Widgets',
		'manage_options',
		'garmin-fitness-widgets',
		'gfw_settings_page'
	);
} );

function gfw_shell_exec_available(): bool {
	if ( ! function_exists( 'shell_exec' ) ) {
		return false;
	}
	$disabled = array_map( 'trim', explode( ',', ini_get( 'disable_functions' ) ) );
	return ! in_array( 'shell_exec', $disabled, true );
}

function gfw_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle save
	$notice = '';
	$notice_type = 'success';
	if ( isset( $_POST['gfw_save'] ) && check_admin_referer( 'gfw_save_settings' ) ) {
		$email = sanitize_email( wp_unslash( $_POST['gfw_email'] ?? '' ) );
		update_option( 'gfw_email', $email );

		$password = sanitize_text_field( wp_unslash( $_POST['gfw_password'] ?? '' ) );
		if ( $password !== '' ) {
			update_option( 'gfw_password', $password );
		}
		$notice = 'Налаштування збережено.';
	}

	$email        = get_option( 'gfw_email', '' );
	$has_password = (bool) get_option( 'gfw_password', '' );
	$data         = gfw_get_data();
	$updated_at   = $data ? ( $data->updated_at ?? '' ) : '';
	$can_shell    = gfw_shell_exec_available();
	?>
	<div class="wrap">
		<h1>Garmin Fitness Widgets</h1>

		<?php if ( $notice ) : ?>
			<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
				<p><?php echo esc_html( $notice ); ?></p>
			</div>
		<?php endif; ?>

		<h2>Credentials Garmin Connect</h2>
		<p>Зберігаються у базі даних WordPress. Використовуються для запуску <code>fetch_garmin.py</code>.</p>

		<form method="post" action="">
			<?php wp_nonce_field( 'gfw_save_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="gfw_email">Email</label></th>
					<td>
						<input type="email" id="gfw_email" name="gfw_email"
							   value="<?php echo esc_attr( $email ); ?>"
							   class="regular-text" autocomplete="off">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="gfw_password">Пароль</label></th>
					<td>
						<input type="password" id="gfw_password" name="gfw_password"
							   value="" class="regular-text" autocomplete="new-password"
							   placeholder="<?php echo $has_password ? '••••••••' : 'Введіть пароль'; ?>">
						<p class="description">
							<?php echo $has_password
								? 'Пароль збережено. Залиш поле порожнім, щоб не змінювати.'
								: 'Пароль не встановлено.'; ?>
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Зберегти', 'primary', 'gfw_save' ); ?>
		</form>

		<hr>

		<h2>Статус даних</h2>
		<?php if ( $updated_at ) : ?>
			<p>Останнє оновлення: <strong><?php echo esc_html( $updated_at ); ?></strong></p>
		<?php elseif ( file_exists( GFW_DIR . 'garmin_data.json' ) ) : ?>
			<p>Файл <code>garmin_data.json</code> знайдено, але поле <code>updated_at</code> відсутнє.</p>
		<?php else : ?>
			<p><code>garmin_data.json</code> не знайдено. Запусти скрипт вперше.</p>
		<?php endif; ?>

		<?php if ( $email && $has_password ) : ?>
			<hr>
			<h2>Запустити отримання даних</h2>
			<?php if ( $can_shell ) : ?>
				<p>Запускає <code>fetch_garmin.py</code> безпосередньо з сервера зі збереженими credentials.</p>
				<button type="button" id="gfw-fetch-btn" class="button button-secondary">
					Отримати дані зараз
				</button>
				<span id="gfw-fetch-status" style="margin-left:10px;font-weight:600;"></span>
				<div id="gfw-fetch-output" style="display:none;margin-top:12px;">
					<pre id="gfw-fetch-pre" style="background:#f0f0f1;padding:12px;border-radius:4px;max-height:220px;overflow:auto;font-size:12px;white-space:pre-wrap;"></pre>
				</div>
			<?php else : ?>
				<p class="description">
					<code>shell_exec</code> вимкнено на цьому хостингу. Запускайте скрипт через cron або вручну (див. нижче).
				</p>
			<?php endif; ?>
		<?php elseif ( ! $email || ! $has_password ) : ?>
			<hr>
			<p class="description">Збережи email і пароль вище, щоб активувати кнопку запуску скрипту.</p>
		<?php endif; ?>

		<hr>

		<h2>Cron команда</h2>
		<p>Додай у <code>crontab -e</code> на сервері — запускатиметься щодня о 06:00:</p>
		<pre style="background:#f0f0f1;padding:12px;border-radius:4px;font-size:12px;overflow-x:auto;">0 6 * * * cd <?php echo esc_html( rtrim( GFW_DIR, '/' ) ); ?> &amp;&amp; GARMIN_EMAIL=<?php echo esc_html( $email ?: 'your@email.com' ); ?> GARMIN_PASSWORD=[ПАРОЛЬ] python3 fetch_garmin.py >> /var/log/garmin-fetch.log 2&gt;&amp;1</pre>

		<hr>

		<h2>Shortcodes</h2>
		<table class="widefat" style="max-width:480px;">
			<thead><tr><th>Shortcode</th><th>Що відображає</th></tr></thead>
			<tbody>
				<tr><td><code>[garmin_widgets]</code></td><td>Всі три картки у ряд</td></tr>
				<tr><td><code>[garmin_weight]</code></td><td>Вага + тренд</td></tr>
				<tr><td><code>[garmin_last_activity]</code></td><td>Остання активність</td></tr>
				<tr><td><code>[garmin_weekly_distance]</code></td><td>Відстань за тиждень</td></tr>
			</tbody>
		</table>
	</div>

	<?php if ( $email && $has_password && $can_shell ) : ?>
	<script>
	(function () {
		var btn    = document.getElementById('gfw-fetch-btn');
		var status = document.getElementById('gfw-fetch-status');
		var wrap   = document.getElementById('gfw-fetch-output');
		var pre    = document.getElementById('gfw-fetch-pre');
		if (!btn) return;

		btn.addEventListener('click', function () {
			btn.disabled = true;
			status.textContent = 'Запускаю…';
			status.style.color = '';
			wrap.style.display = 'none';

			fetch(ajaxurl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: 'gfw_fetch_now',
					_ajax_nonce: <?php echo wp_json_encode( wp_create_nonce( 'gfw_fetch_now' ) ); ?>
				})
			})
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (res.success) {
					status.textContent = '✓ Готово — дані оновлено';
					status.style.color = '#22c55e';
				} else {
					status.textContent = '✗ Помилка';
					status.style.color = '#ef4444';
				}
				var output = (res.data && res.data.output) ? res.data.output : '';
				if (output) {
					pre.textContent = output;
					wrap.style.display = 'block';
				}
				btn.disabled = false;
			})
			.catch(function () {
				status.textContent = '✗ Мережева помилка';
				status.style.color = '#ef4444';
				btn.disabled = false;
			});
		});
	})();
	</script>
	<?php endif; ?>
	<?php
}

// ---------------------------------------------------------------------------
// AJAX: run fetch_garmin.py with stored credentials
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_gfw_fetch_now', function () {
	check_ajax_referer( 'gfw_fetch_now' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'output' => 'Доступ заборонено.' ] );
	}

	if ( ! gfw_shell_exec_available() ) {
		wp_send_json_error( [ 'output' => 'shell_exec вимкнено на цьому сервері.' ] );
	}

	$email    = get_option( 'gfw_email', '' );
	$password = get_option( 'gfw_password', '' );

	if ( ! $email || ! $password ) {
		wp_send_json_error( [ 'output' => 'Credentials не налаштовано.' ] );
	}

	$script = GFW_DIR . 'fetch_garmin.py';
	if ( ! file_exists( $script ) ) {
		wp_send_json_error( [ 'output' => 'fetch_garmin.py не знайдено.' ] );
	}

	$cmd = sprintf(
		'GARMIN_EMAIL=%s GARMIN_PASSWORD=%s python3 %s 2>&1',
		escapeshellarg( $email ),
		escapeshellarg( $password ),
		escapeshellarg( $script )
	);

	// Change to plugin dir so relative paths inside the script resolve correctly
	$cmd = 'cd ' . escapeshellarg( GFW_DIR ) . ' && ' . $cmd;

	$output = shell_exec( $cmd );

	if ( $output === null ) {
		wp_send_json_error( [ 'output' => 'shell_exec повернув null — можливо вимкнено або python3 не знайдено.' ] );
	}

	wp_send_json_success( [ 'output' => $output ] );
} );
