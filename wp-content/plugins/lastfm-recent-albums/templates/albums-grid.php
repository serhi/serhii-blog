<?php
/**
 * Template for displaying Last.fm albums
 * File: templates/albums-grid.php
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="lastfm-albums-grid alignwide wp-block-tabor-cards is-layout-grid">
    <?php foreach ($albums as $album) : ?>
        <a href="<?php echo esc_url($album['url']); ?>" 
           class="lastfm-album-card wp-block-tabor-card" 
           target="_blank" 
           rel="noopener noreferrer">
            
            <?php if (!empty($album['image'])) : ?>
                <span class="wp-block-tabor-card__image">
                    <img src="<?php echo esc_url($album['image']); ?>" 
                         alt="<?php echo esc_attr($album['name'] . ' by ' . $album['artist']); ?>">
                    <img src="<?php echo esc_url($album['image']); ?>" 
                         class="wp-block-tabor-card__image-blur"
                         alt="">
                </span>
            <?php endif; ?>
            
            <span class="wp-block-tabor-card__content<?php echo !empty($album['image']) ? ' has-image' : ''; ?>">
                <span class="wp-block-tabor-card__title is-overflow"><?php echo esc_html($album['name']); ?></span>
                <span class="wp-block-tabor-card__description"><?php echo esc_html($album['artist']); ?></span>
            </span>
        </a>
    <?php endforeach; ?>
</div>