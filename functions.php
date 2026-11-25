<?php
/**
 * Theme functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Define theme constants.
 */
function constants(): void {
	if ( ! defined( 'AKTHEME_VERSION' ) ) {
		define( 'AKTHEME_VERSION', wp_get_theme()->get( 'Version' ) );
	}

	if ( ! defined( 'AKTHEME_PATH' ) ) {
		define( 'AKTHEME_PATH', untrailingslashit( get_template_directory() ) );
	}

	if ( ! defined( 'AKTHEME_BUILD_URI' ) ) {
		define( 'AKTHEME_BUILD_URI', untrailingslashit( get_template_directory_uri() ) . '/assets/build' );
	}

	if ( ! defined( 'AKTHEME_BUILD_DIR' ) ) {
		define( 'AKTHEME_BUILD_DIR', untrailingslashit( get_template_directory() ) . '/assets/build' );
	}

	if ( ! defined( 'AKTHEME_ENABLE_TAILWIND' ) ) {
		define( 'AKTHEME_ENABLE_TAILWIND', false );
	}
}

constants();

// If the autoloader fails, we cannot proceed.
require_once AKTHEME_PATH . '/inc/Autoloader.php';
if ( ! class_exists( Autoloader::class ) || ! Autoloader::autoload() ) {
	return;
}

// Instantiate the theme.
if ( class_exists( Main::class ) ) {
	Main::get_instance();
}
