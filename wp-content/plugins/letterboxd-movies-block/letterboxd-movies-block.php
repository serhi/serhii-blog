<?php
/**
 * Plugin Name:       Letterboxd Movies Block
 * Plugin URI:        https://github.com/serhiikorolchuk/letterboxd-movies-block
 * Description:       Displays your recently watched Letterboxd movies as a configurable Gutenberg block. Choose the number of columns, how many films to show, and toggle poster, title, and star rating visibility.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Serhii Korolchuk
 * Author URI:        https://serhii.blog
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       letterboxd-movies-block
 * Domain Path:       /languages
 *
 * @package LetterboxdMoviesBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LMB_VERSION', '1.0.0' );
define( 'LMB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LMB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

add_action( 'init', function () {
	load_plugin_textdomain(
		'letterboxd-movies-block',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
} );

require_once LMB_PLUGIN_DIR . 'includes/block.php';
require_once LMB_PLUGIN_DIR . 'includes/settings.php';
