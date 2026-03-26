<?php
/**
 * Block registration, data fetching, and render callback.
 *
 * @package LetterboxdMoviesBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Fetch recent movies from a Letterboxd RSS feed, cached for 30 minutes.
 *
 * @param string $username  Letterboxd username.
 * @param int    $limit     Number of movies to return.
 * @return array
 */
function lbm_get_recent_movies( $username, $limit = 4 ) {
	$transient_key = 'lbm_recent_' . sanitize_key( $username ) . '_' . (int) $limit;
	$cached        = get_transient( $transient_key );

	if ( $cached !== false ) {
		return $cached;
	}

	$rss_url  = 'https://letterboxd.com/' . rawurlencode( $username ) . '/rss/';
	$response = wp_remote_get( $rss_url, array( 'timeout' => 10 ) );

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return array();
	}

	$xml = simplexml_load_string( wp_remote_retrieve_body( $response ) );
	if ( ! $xml ) {
		return array();
	}

	$movies = array();
	$count  = 0;

	foreach ( $xml->channel->item as $item ) {
		if ( $count >= $limit ) {
			break;
		}

		// Strip trailing rating appended to the title by Letterboxd (e.g. "Film, 2024 – ★★★½").
		$title = preg_replace( '/\s+[\x{2013}\x{2014}-]\s+[\x{2605}\x{00BD}]+$/u', '', (string) $item->title );
		$link  = (string) $item->link;

		// Extract poster image from description HTML.
		preg_match( '/<img[^>]+src="([^">]+)"/', (string) $item->description, $m );
		$image = $m[1] ?? '';

		// Extract member rating and rewatch flag from Letterboxd custom XML namespace.
		$lb_ns   = $item->children( 'letterboxd', true );
		$rating  = isset( $lb_ns->memberRating ) ? (float) $lb_ns->memberRating : null;
		$rewatch = isset( $lb_ns->rewatch ) && 'Yes' === (string) $lb_ns->rewatch;

		$movies[] = array(
			'title'   => $title,
			'link'    => $link,
			'image'   => $image,
			'rating'  => $rating,
			'rewatch' => $rewatch,
		);

		$count++;
	}

	set_transient( $transient_key, $movies, 30 * MINUTE_IN_SECONDS );

	return $movies;
}


/**
 * Convert a numeric Letterboxd rating (0.5–5.0) to a Unicode star string.
 *
 * @param float $rating
 * @return string e.g. "★★★★½"
 */
function lbm_rating_to_stars( $rating ) {
	$full = (int) floor( $rating );
	$half = ( $rating - $full ) >= 0.5;
	return str_repeat( '★', $full ) . ( $half ? '½' : '' );
}


/**
 * Server-side render callback for the letterboxd-movies/movies block.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function lbm_render_callback( $attributes ) {
	$username = sanitize_text_field( get_option( 'lbm_username', '' ) );

	if ( empty( $username ) ) {
		return '<p>' . esc_html__( 'Please set your Letterboxd username in Settings → Letterboxd Movies.', 'letterboxd-movies-block' ) . '</p>';
	}

	$columns     = isset( $attributes['columns'] )     ? (int)  $attributes['columns']     : 4;
	$count       = isset( $attributes['moviesCount'] ) ? (int)  $attributes['moviesCount'] : 4;
	$show_image  = isset( $attributes['showImage'] )   ? (bool) $attributes['showImage']   : true;
	$show_title  = isset( $attributes['showTitle'] )   ? (bool) $attributes['showTitle']   : true;
	$show_rating = isset( $attributes['showRating'] )  ? (bool) $attributes['showRating']  : false;

	$movies = lbm_get_recent_movies( $username, $count );

	if ( empty( $movies ) ) {
		return '<p>' . esc_html__( 'No movies found. Check your username or try again later.', 'letterboxd-movies-block' ) . '</p>';
	}

	$grid_style = '--lbm-columns: ' . $columns . ';';

	ob_start();
	?>
	<div class="lbm-grid" style="<?php echo esc_attr( $grid_style ); ?>">
		<?php foreach ( $movies as $movie ) : ?>
			<a href="<?php echo esc_url( $movie['link'] ); ?>" target="_blank" rel="noopener noreferrer" class="lbm-card">
				<?php if ( $show_image && ! empty( $movie['image'] ) ) : ?>
					<div class="lbm-image-wrap">
						<img src="<?php echo esc_url( $movie['image'] ); ?>"
						     alt="<?php echo esc_attr( $movie['title'] ); ?>"
						     loading="lazy">
						<img src="<?php echo esc_url( $movie['image'] ); ?>"
						     alt=""
						     aria-hidden="true"
						     loading="lazy"
						     class="lbm-image-blur">
					</div>
				<?php endif; ?>
				<?php if ( $show_title ) : ?>
					<p class="lbm-title"><?php echo esc_html( $movie['title'] ); ?></p>
				<?php endif; ?>
				<?php if ( $show_rating && ( $movie['rating'] !== null || $movie['rewatch'] ) ) : ?>
					<span class="lbm-rating">
						<?php if ( $movie['rewatch'] ) : ?>
							<span class="lbm-rewatch" title="<?php esc_attr_e( 'Rewatch', 'letterboxd-movies-block' ); ?>">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="12" height="12" aria-hidden="true"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/></svg>
							</span>
						<?php endif; ?>
						<?php if ( $movie['rating'] !== null ) : ?>
							<?php echo esc_html( lbm_rating_to_stars( $movie['rating'] ) ); ?>
						<?php endif; ?>
					</span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}


/**
 * Register the Gutenberg block.
 */
function lbm_register_block() {
	register_block_type(
		LMB_PLUGIN_DIR . 'blocks/letterboxd-movies',
		array(
			'render_callback' => 'lbm_render_callback',
		)
	);

	wp_set_script_translations(
		'letterboxd-movies-movies-editor-script',
		'letterboxd-movies-block',
		LMB_PLUGIN_DIR . 'languages'
	);
}
add_action( 'init', 'lbm_register_block' );
