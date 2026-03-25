<?php
/**
 * Uninstall: remove all plugin data from the database.
 *
 * Runs when the plugin is deleted (not just deactivated) via the WordPress admin.
 *
 * @package LetterboxdMoviesBlock
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin option.
delete_option( 'lbm_username' );

// Delete all cached transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
