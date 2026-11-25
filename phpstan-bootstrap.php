<?php
/**
 * PHPStan Bootstrap file.
 *
 * This is just for the constants defined in the theme. This file is used by PHPStan to bootstrap the analysis.
 *
 * @package AK\Theme
 */

declare(strict_types = 1);

define( 'AKTHEME_VERSION', '1.0.0' );
define( 'AKTHEME_PATH', __DIR__ );
define( 'AKTHEME_BUILD_DIR', __DIR__ . '/assets/build' );
define( 'AKTHEME_BUILD_URI', 'https://example.com/wp-content/themes/aktheme/assets/build' );
define( 'AKTHEME_ENABLE_TAILWIND', false );
