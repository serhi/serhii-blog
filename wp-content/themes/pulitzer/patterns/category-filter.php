<?php
/**
 * Title: Category filter
 * Slug: pulitzer/category-filter
 * Categories: pulitzer
 * Inserter: false
 */

$terms = get_terms( [
	'taxonomy'   => 'category',
	'hide_empty' => true,
	'orderby'    => 'name',
] );

if ( empty( $terms ) || is_wp_error( $terms ) ) return;

$current  = get_queried_object();
$blog_url = get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/' );
$all_active = is_home() || is_front_page();
?>
<!-- wp:html -->
<div class="vinyl-genre-filter">
	<a href="<?php echo esc_url( $blog_url ); ?>"
	   class="vinyl-genre-filter__tag<?php echo $all_active ? ' is-active' : ''; ?>">
		Всі
	</a>
	<?php foreach ( $terms as $term ) : ?>
	<a href="<?php echo esc_url( get_category_link( $term->term_id ) ); ?>"
	   class="vinyl-genre-filter__tag<?php echo ( $current instanceof WP_Term && $current->term_id === $term->term_id ) ? ' is-active' : ''; ?>">
		<?php echo esc_html( $term->name ); ?>
	</a>
	<?php endforeach; ?>
</div>
<!-- /wp:html -->
