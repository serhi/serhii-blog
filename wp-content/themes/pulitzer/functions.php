<?php
/**
 * Pulitzer functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Pulitzer
 * @since Pulitzer 1.0
 */


/**
 * Declare theme supports.
 *
 * @since Pulitzer 1.0
 * @return void
 */
function pulitzer_setup() {
	add_editor_style( array( 'style.css', 'assets/css/custom.css' ) );
}
add_action( 'after_setup_theme', 'pulitzer_setup' );


/**
 * Enqueue stylesheets.
 *
 * @since Pulitzer 1.0
 * @return void
 */
function pulitzer_styles() {
	wp_enqueue_style( 'pulitzer-styles', get_template_directory_uri() . '/style.css', array(), wp_get_theme( 'pulitzer' )->get( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'pulitzer_styles' );


/**
 * Add custom template part areas.
 */
if ( ! function_exists( 'pulitzer_template_part_areas' ) ) :
	/**
	 * Add custom template part areas
	 *
	 * @since Pulitzer 1.0
	 * @return array
	 */
	function pulitzer_template_part_areas( array $areas ) {
		$areas[] = array(
			'area'        => 'posts',
			'area_tag'    => 'section',
			'icon'        => 'symbolFilledIcon',
			'label'       => __( 'Posts', 'pulitzer' ),
			'description' => __( 'Different layouts for the posts list', 'pulitzer' )
		);

		return $areas;
	}
endif;

add_filter( 'default_wp_template_part_areas', 'pulitzer_template_part_areas' );


/**
 * Register block styles.
 */
if ( ! function_exists( 'pulitzer_block_styles' ) ) :
	/**
	 * Register custom block styles
	 *
	 * @since Pulitzer 1.0
	 * @return void
	 */
	function pulitzer_block_styles() {

		register_block_style(
			'core/comment-edit-link',
			array(
				'name'	=> 'pulitzer-comment-edit-link',
				'label'	=> __( 'Button', 'pulitzer' )
			)
		);

		register_block_style(
			'core/comment-reply-link',
			array(
				'name'	=> 'pulitzer-comment-reply-link',
				'label'	=> __( 'Button', 'pulitzer' )
			)
		);

		register_block_style(
			'core/list',
			array(
				'name'	=> 'pulitzer-list-checkmark',
				'label'	=> __( 'Checkmark list', 'pulitzer' )
			)
		);

		register_block_style(
			'core/list',
			array(
				'name'	=> 'pulitzer-list-checkmark-disc',
				'label'	=> __( 'Circled checkmark list', 'pulitzer' )
			)
		);

		register_block_style(
			'core/post-comments-number',
			array(
				'name'	=> 'pulitzer-post-comments-number-icon',
				'label'	=> __( 'With icon', 'pulitzer' )
			)
		);

		register_block_style(
			'core/post-excerpt',
			array(
				'name'	=> 'pulitzer-clamp-lines-2',
				'label'	=> __( 'Clamp: 2 lines', 'pulitzer' )
			)
		);

		register_block_style(
			'core/post-excerpt',
			array(
				'name'	=> 'pulitzer-clamp-lines-3',
				'label'	=> __( 'Clamp: 3 lines', 'pulitzer' )
			)
		);

		register_block_style(
			'core/post-terms',
			array(
				'name'	=> 'pulitzer-post-terms',
				'label'	=> __( 'Outlined terms', 'pulitzer' )
			)
		);
		
	}
endif;

add_action( 'init', 'pulitzer_block_styles' );


/**
 * Enqueue block stylesheets.
 */
if ( ! function_exists( 'pulitzer_block_stylesheets' ) ) :
	/**
	 * Enqueue custom block stylesheets
	 *
	 * @since Pulitzer 1.0
	 * @return void
	 */
	function pulitzer_block_stylesheets() {
		
		$pulitzer_styled_blocks = array(
			'core/comments'                 => 'comments',
			'core/footnotes'                => 'footnotes',
			'core/list'                     => 'list',
			'core/navigation'               => 'navigation',
			'core/paragraph'                => 'paragraph',
			'core/post-comments-form'       => 'post-comments-form',
			'core/post-excerpt'             => 'post-excerpt',
			'core/post-featured-image'      => 'post-featured-image',
			'core/post-terms'               => 'post-terms',
			'core/query-pagination-numbers' => 'query-pagination-numbers',
			'core/search'                   => 'search',
			'core/social-links'             => 'social-links',
			'jetpack/sharing-buttons'       => 'jetpack-sharing-buttons',
			'jetpack/subscriptions'         => 'jetpack-subscriptions',
		);

		foreach ( $pulitzer_styled_blocks as $block_name_with_namespace => $block_name ) {
			wp_enqueue_block_style(
				$block_name_with_namespace,
				array(
					'handle' => 'pulitzer-' . $block_name,
					'src'    => get_template_directory_uri() . '/assets/css/blocks/' . $block_name . '.css',
					'path'   => get_template_directory() . '/assets/css/blocks/' . $block_name . '.css',
				)
			);
		}

	}
endif;

add_action( 'init', 'pulitzer_block_stylesheets' );


/**
 * Register pattern categories.
 */
if ( ! function_exists( 'pulitzer_pattern_categories' ) ) :
	/**
	 * Register pattern categories
	 *
	 * @since Pulitzer 1.0
	 * @return void
	 */
	function pulitzer_pattern_categories() {

		register_block_pattern_category(
			'pulitzer',
			array(
				'label'       => _x( 'Pulitzer', 'Block pattern category', 'pulitzer' ),
				'description' => __( 'Patterns included in the Pulitzer theme.', 'pulitzer' ),
			)
		);

		register_block_pattern_category(
			'pulitzer-pages',
			array(
				'label'       => _x( 'Pulitzer Page Layouts', 'Block pattern category', 'pulitzer' ),
				'description' => __( 'Full page layouts.', 'pulitzer' ),
			)
		);
	}
endif;

add_action( 'init', 'pulitzer_pattern_categories' );


/**
 * Check if a block is registered.
 */
if ( ! function_exists( 'pulitzer_is_block_registered' ) ) :
	/**
	 * Check if a block is registered
	 *
	 * @since Pulitzer 1.0
	 * @return bool
	 */
	function pulitzer_is_block_registered( $block_name ) {
		$registry = WP_Block_Type_Registry::get_instance();
 		return $registry->get_registered( $block_name );
	}
endif;


/**
 * Register custom block bindings.
 */
if ( ! function_exists( 'pulitzer_register_block_bindings' ) ) :
	/**
	 * Register custom block bindings
	 *
	 * @since Pulitzer 1.0
	 * @return void
	 */
	function pulitzer_register_block_bindings() {

		/*
		 * Copyright character with current year.
		 */
		register_block_bindings_source( 
			'pulitzer/copyright-year', 
			array(
				'label'              => __( 'Copyright year', 'pulitzer' ),
				'get_value_callback' => 'pulitzer_block_binding_callback_copyright_year'
			)
		);

		/*
		 * Comments count for the current post.
		 */
		register_block_bindings_source( 
			'pulitzer/post-comments-count', 
			array(
				'label'              => __( 'Post comments count', 'pulitzer' ),
				'get_value_callback' => 'pulitzer_block_binding_callback_post_comments_count'
			)
		);

		/*
		 * Post reading time for the current post.
		 */
		register_block_bindings_source(
			'pulitzer/post-reading-time',
			array(
				'label'              => __( 'Post reading time', 'pulitzer' ),
				'get_value_callback' => 'pulitzer_block_binding_callback_post_reading_time'
			)
		);

		/*
		 * Days since the full-scale invasion of Ukraine (24 Feb 2022).
		 */
		register_block_bindings_source(
			'pulitzer/war-days-counter',
			array(
				'label'              => __( 'War days counter', 'pulitzer' ),
				'get_value_callback' => 'pulitzer_block_binding_callback_war_days_counter'
			)
		);

		/*
		 * Number of published vinyl records in the collection.
		 */
		register_block_bindings_source(
			'pulitzer/vinyl-count',
			array(
				'label'              => __( 'Vinyl count', 'pulitzer' ),
				'get_value_callback' => 'pulitzer_block_binding_callback_vinyl_count'
			)
		);

	}
endif;

add_action( 'init', 'pulitzer_register_block_bindings' );


/*
 * Block bindings callback:
 * Copyright character with current year.
 */
if ( ! function_exists( 'pulitzer_block_binding_callback_copyright_year' ) ) :
	/**
	 * Block bindings callback
	 * Copyright character with current year
	 *
	 * @since Pulitzer 1.0
	 * @return string
	 */
	function pulitzer_block_binding_callback_copyright_year() {
		return '&copy; ' . date( 'Y' );
	}
endif;


/*
 * Block bindings callback:
 * Post comments count.
 */

if ( ! function_exists( 'pulitzer_block_binding_callback_post_comments_count' ) ) :
	/**
	 * Block bindings callback
	 * Post comments count.
	 *
	 * @since Pulitzer 1.0
	 * @return string
	 */
	function pulitzer_block_binding_callback_post_comments_count( array $source_args, WP_Block $block_instance, string $attribute_name ) {
		$post_id = $block_instance->context['postId'] ?? get_the_ID();

		if ( ! comments_open( $post_id ) ) return false;

		$comments_link = '<a class="pulitzer-comment-count-link" href="' . esc_url( get_comments_link( $post_id ) ) . '">';
		$comments_link .= '<span class="count">' . esc_html( get_comments_number( $post_id ) ) . '</span>';
		$comments_link .= '</a>';

		return $comments_link;

	}
endif;


/*
 * Block bindings callback:
 * Post reading time.
 */
if ( ! function_exists( 'pulitzer_block_binding_callback_post_reading_time' ) ) :
	/**
	 * Block bindings callback
	 * Post reading time.
	 *
	 * @since Pulitzer 1.0
	 * @return string
	 */
	function pulitzer_block_binding_callback_post_reading_time( array $source_args, WP_Block $block_instance, string $attribute_name ) {

		// 1. НАДІЙНЕ ОТРИМАННЯ ID ЗАПИСУ З КОНТЕКСТУ БЛОКУ
		$post_id = $block_instance->context['postId'] ?? get_the_ID();

		if ( ! $post_id ) {
			return '';
		}

		// Set words per minute to 200.
		$words_per_min = 200;

		// 2. ОТРИМАННЯ ВМІСТУ ТА ПІДРАХУНОК СЛІВ (з підтримкою кирилиці)
		$post_content = get_post_field( 'post_content', $post_id );
		$filtered_content = apply_filters( 'the_content', $post_content );
		$raw_text = strip_tags( $filtered_content );
		
		// Використовуємо preg_match_all з модифікатором 'u' для коректного підрахунку слів у UTF-8 (кирилиця).
		$count = 0;
		if ( function_exists( 'preg_match_all' ) ) {
			$count = preg_match_all( '/\p{L}+/u', $raw_text, $matches );
		}

		// Get the ceiling for minutes.
		$time_mins  = intval( ceil( $count / $words_per_min ) );
		$time_hours = 0;

		// Calculate hours and leftover minutes.
		if ( 60 <= $time_mins ) {
			$time_hours = intval( floor( $time_mins / 60 ) );
			$time_mins  = intval( $time_mins % 60 );
		}
        
		// --- УКРАЇНСЬКА ЛОКАЛІЗАЦІЯ (ВБУДОВАНА ЛОГІКА МНОЖИНИ) ---
        
		// Форми: 0 - Однина (1), 1 - Множина 1 (2-4), 2 - Множина 2 (5+)
		$hour_forms   = [ 'година', 'години', 'годин' ];
		$minute_forms = [ 'хвилина', 'хвилини', 'хвилин' ];

		/**
		 * Хелпер для визначення правильної форми множини в українській мові.
		 * Використовуємо його, щоб не залежати від локалізації WordPress.
		 */
		$ukr_plural_logic = function( $number, $forms ) {
			if ( $number === 0 ) {
				return $forms[2];
			}
			$last_digit      = $number % 10;
			$last_two_digits = $number % 100;

			if ( $last_two_digits >= 11 && $last_two_digits <= 14 ) {
				return $forms[2]; // 11-14 (хвилин)
			} elseif ( $last_digit === 1 ) {
				return $forms[0]; // 1, 21, 31 (хвилина)
			} elseif ( $last_digit >= 2 && $last_digit <= 4 ) {
				return $forms[1]; // 2, 3, 4, 22-24 (хвилини)
			}
			return $forms[2]; // 5-10, 15-20, 25+ (хвилин)
		};

		// --- Set up text for hours ---
		$hours_form = $ukr_plural_logic( $time_hours, $hour_forms );
		$text_hours = sprintf(
			'%s %s',
			number_format_i18n( $time_hours ),
			$hours_form
		);

		// --- Set up text for minutes ---
		$mins_form = $ukr_plural_logic( $time_mins, $minute_forms );
		$text_mins = sprintf(
			'%s %s',
			number_format_i18n( $time_mins ),
			$mins_form
		);

		// --- ЛОГІКА ВИВЕДЕННЯ ---

		// Якщо немає годин.
		if ( 0 >= $time_hours ) {
			
			// Якщо кількість хвилин 0 (тобто час менше 1 хвилини).
			if ( 0 >= $time_mins ) {
				// Використовуємо esc_html_x для можливості перекладу цього статичного рядка.
				return esc_html_x( 'Менше хвилини читання', 'Text for very short posts (less than 1 minute)', 'pulitzer' );
			}
			
			// Виводимо лише хвилини (1-59).
			return sprintf( esc_html_x( '%s читання', '%s = minutes', 'pulitzer' ), $text_mins );
		}
		
		// Якщо є години, але немає хвилин.
		elseif ( 0 >= $time_mins ) {
			return sprintf( esc_html_x( '%s читання', '%s = hours', 'pulitzer' ), $text_hours );
		}

		// Об'єднати текст годин + хвилин.
		// Формат: "1 година 30 хвилин читання"
		return sprintf( esc_html_x( '%1$s %2$s читання', '%1$s = hours, %2$s = minutes', 'pulitzer' ), $text_hours, $text_mins );

	}
endif;


/* ==========================================================================
   Custom additions
   ========================================================================== */


/**
 * Enqueue custom stylesheets and scripts.
 */
function pulitzer_custom_assets() {
    wp_enqueue_style(
        'pulitzer-custom',
        get_theme_file_uri( 'assets/css/custom.css' ),
        array(),
        filemtime( get_theme_file_path( 'assets/css/custom.css' ) )
    );

    wp_enqueue_style(
        'pulitzer-dark',
        get_theme_file_uri( 'assets/css/dark-mode.css' ),
        array( 'pulitzer-custom' ),
        filemtime( get_theme_file_path( 'assets/css/dark-mode.css' ) )
    );

    wp_enqueue_script(
        'pulitzer-spoiler',
        get_theme_file_uri( 'assets/js/spoiler.js' ),
        array(),
        filemtime( get_theme_file_path( 'assets/js/spoiler.js' ) ),
        true
    );

    if ( is_post_type_archive( 'vinyl' ) ) {
        wp_enqueue_script(
            'pulitzer-vinyl-blur',
            get_theme_file_uri( 'assets/js/vinyl-blur.js' ),
            array(),
            filemtime( get_theme_file_path( 'assets/js/vinyl-blur.js' ) ),
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'pulitzer_custom_assets' );


/* --------------------------------------------------------------------------
   SVG uploads
   -------------------------------------------------------------------------- */

add_filter(
	'upload_mimes',
	function ( $upload_mimes ) {
		if ( ! current_user_can( 'administrator' ) ) {
			return $upload_mimes;
		}
		$upload_mimes['svg']  = 'image/svg+xml';
		$upload_mimes['svgz'] = 'image/svg+xml';
		return $upload_mimes;
	}
);

add_filter(
	'wp_check_filetype_and_ext',
	function ( $wp_check_filetype_and_ext, $file, $filename, $mimes, $real_mime ) {
		if ( ! $wp_check_filetype_and_ext['type'] ) {
			$check_filetype  = wp_check_filetype( $filename, $mimes );
			$ext             = $check_filetype['ext'];
			$type            = $check_filetype['type'];
			$proper_filename = $filename;

			if ( $type && 0 === strpos( $type, 'image/' ) && 'svg' !== $ext ) {
				$ext  = false;
				$type = false;
			}

			$wp_check_filetype_and_ext = compact( 'ext', 'type', 'proper_filename' );
		}
		return $wp_check_filetype_and_ext;
	},
	10,
	5
);


/* --------------------------------------------------------------------------
   External posts – redirect to external URL (CPT: external_post)
   -------------------------------------------------------------------------- */

if ( ! function_exists( 'pulitzer_redirect_to_external_url' ) ) :
	function pulitzer_redirect_to_external_url() {
		if ( ! is_singular( 'external_post' ) ) {
			return;
		}
		$post_id      = get_the_ID();
		$external_url = get_field( 'external_url', $post_id );

		if ( $external_url && filter_var( $external_url, FILTER_VALIDATE_URL ) ) {
			wp_redirect( esc_url( $external_url ), 301 );
			exit;
		}
	}
	add_action( 'template_redirect', 'pulitzer_redirect_to_external_url' );
endif;

function pulitzer_add_featured_class( $classes ) {
	$post_id = get_the_ID();
	if ( $post_id && 'external_post' === get_post_type( $post_id ) ) {
		if ( get_post_meta( $post_id, 'is_featured', true ) ) {
			$classes[] = 'is-featured';
		}
	}
	return $classes;
}
add_filter( 'post_class', 'pulitzer_add_featured_class' );


/* --------------------------------------------------------------------------
   Spoiler shortcode [spoiler]...[/spoiler]
   -------------------------------------------------------------------------- */

function custom_spoiler_shortcode( $atts, $content = null ) {
	$content = do_shortcode( $content );
	return '<span class="spoiler">' . esc_html( $content ) . '</span>';
}
add_shortcode( 'spoiler', 'custom_spoiler_shortcode' );


/* --------------------------------------------------------------------------
   War days counter [war_days_counter]
   -------------------------------------------------------------------------- */

function pulitzer_get_day_plural( $number ) {
	$forms  = array( 'день', 'дні', 'днів' );
	$number = abs( $number );

	if ( $number % 100 >= 11 && $number % 100 <= 14 ) {
		return $forms[2];
	}

	$last = $number % 10;
	if ( $last === 1 )              return $forms[0];
	if ( $last >= 2 && $last <= 4 ) return $forms[1];
	return $forms[2];
}

function pulitzer_block_binding_callback_war_days_counter() {
	$tz    = new DateTimeZone( 'Europe/Kyiv' );
	$start = new DateTime( '2022-02-24 00:00:00', $tz );
	$now   = new DateTime( 'now', $tz );
	$start->setTime( 0, 0, 0 );
	$now->setTime( 0, 0, 0 );

	$days   = (int) $start->diff( $now )->format( '%a' );
	$plural = pulitzer_get_day_plural( $days );
	$number = number_format( $days, 0, '', ' ' );

	return sprintf( '<span class="war-days-count-number">%s</span> %s', $number, $plural );
}
add_shortcode( 'war_days_counter', 'pulitzer_block_binding_callback_war_days_counter' );



/* --------------------------------------------------------------------------
   Vinyl genre filter – archive query
   -------------------------------------------------------------------------- */

function pulitzer_filter_vinyl_by_genre( $query_vars, $block ) {
	if ( ! is_post_type_archive( 'vinyl' ) ) return $query_vars;
	if ( ( $query_vars['post_type'] ?? '' ) !== 'vinyl' ) return $query_vars;

	$genre = isset( $_GET['vinyl_genre'] ) ? sanitize_text_field( wp_unslash( $_GET['vinyl_genre'] ) ) : '';
	if ( empty( $genre ) ) return $query_vars;

	$query_vars['tax_query'] = [ [
		'taxonomy' => 'vinyl_genre',
		'field'    => 'slug',
		'terms'    => $genre,
	] ];

	return $query_vars;
}
add_filter( 'query_loop_block_query_vars', 'pulitzer_filter_vinyl_by_genre', 10, 2 );


/* --------------------------------------------------------------------------
   Vinyl genre filter – shortcode [vinyl_genre_filter]
   -------------------------------------------------------------------------- */

function pulitzer_vinyl_genre_filter_shortcode() {
	$terms = get_terms( [
		'taxonomy'   => 'vinyl_genre',
		'hide_empty' => true,
		'orderby'    => 'name',
	] );

	if ( empty( $terms ) || is_wp_error( $terms ) ) return '';

	$current  = isset( $_GET['vinyl_genre'] ) ? sanitize_text_field( wp_unslash( $_GET['vinyl_genre'] ) ) : '';
	$base_url = get_post_type_archive_link( 'vinyl' );

	$html = '<div class="vinyl-genre-filter">';
	$html .= '<a href="' . esc_url( $base_url ) . '" class="vinyl-genre-filter__tag' . ( $current === '' ? ' is-active' : '' ) . '">Всі</a>';

	foreach ( $terms as $term ) {
		$url   = add_query_arg( 'vinyl_genre', $term->slug, $base_url );
		$class = $current === $term->slug ? ' is-active' : '';
		$html .= '<a href="' . esc_url( $url ) . '" class="vinyl-genre-filter__tag' . $class . '">' . esc_html( $term->name ) . '</a>';
	}

	$html .= '</div>';
	return $html;
}
add_shortcode( 'vinyl_genre_filter', 'pulitzer_vinyl_genre_filter_shortcode' );


/* --------------------------------------------------------------------------
   Discogs redirect – CPT 'vinyl'
   -------------------------------------------------------------------------- */

function pulitzer_redirect_to_discogs( $permalink, $post ) {
	if ( get_post_type( $post ) !== 'vinyl' ) {
		return $permalink;
	}
	$discogs_url = get_post_meta( $post->ID, 'discogs', true );
	if ( ! empty( $discogs_url ) ) {
		return esc_url_raw( $discogs_url );
	}
	return $permalink;
}
add_filter( 'post_type_link', 'pulitzer_redirect_to_discogs', 10, 2 );
add_filter( 'post_link',      'pulitzer_redirect_to_discogs', 10, 2 );


/* --------------------------------------------------------------------------
   Vinyl collection counter [vinyl_count]
   -------------------------------------------------------------------------- */

function pulitzer_get_cpt_count( $post_type = 'post' ) {
	$counts = wp_count_posts( $post_type );
	if ( $counts && is_object( $counts ) && isset( $counts->publish ) ) {
		return (int) $counts->publish;
	}
	return 0;
}

function pulitzer_get_vinyl_plural( $number ) {
	$forms = array( 'платівка', 'платівки', 'платівок' );

	if ( $number >= 11 && $number <= 14 ) {
		return $forms[2];
	}

	$last = $number % 10;
	if ( $last === 1 )              return $forms[0];
	if ( $last >= 2 && $last <= 4 ) return $forms[1];
	return $forms[2];
}

function pulitzer_block_binding_callback_vinyl_count() {
	$count  = pulitzer_get_cpt_count( 'vinyl' );
	$plural = pulitzer_get_vinyl_plural( $count );
	$number = number_format( $count, 0, '', ' ' );

	return sprintf( '<span class="vinyl-count-number">%s</span> %s', $number, $plural );
}
add_shortcode( 'vinyl_count', 'pulitzer_block_binding_callback_vinyl_count' );