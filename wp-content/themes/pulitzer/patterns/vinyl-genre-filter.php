<?php
/**
 * Title: Vinyl genre filter
 * Slug: pulitzer/vinyl-genre-filter
 * Categories: pulitzer
 * Inserter: false
 */

$terms = get_terms( [
	'taxonomy'   => 'vinyl_genre',
	'hide_empty' => true,
	'orderby'    => 'name',
] );

if ( empty( $terms ) || is_wp_error( $terms ) ) return;

$current  = isset( $_GET['vinyl_genre'] ) ? sanitize_text_field( $_GET['vinyl_genre'] ) : '';
$base_url = get_post_type_archive_link( 'vinyl' );
?>
<!-- wp:html -->
<div class="vinyl-genre-filter">
	<a href="<?php echo esc_url( $base_url ); ?>"
	   class="vinyl-genre-filter__tag<?php echo $current === '' ? ' is-active' : ''; ?>">
		Всі
	</a>
	<?php foreach ( $terms as $term ) : ?>
	<a href="<?php echo esc_url( add_query_arg( 'vinyl_genre', $term->slug, $base_url ) ); ?>"
	   class="vinyl-genre-filter__tag<?php echo $current === $term->slug ? ' is-active' : ''; ?>">
		<?php echo esc_html( $term->name ); ?>
	</a>
	<?php endforeach; ?>
</div>
<!-- /wp:html -->
