<?php
/**
 * Menu registration.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Core;

use rtCamp\WPPrimitives\Contracts\Interfaces\Registrable;

/**
 * Class Menu
 *
 * @since 1.0.0
 */
class Menu implements Registrable {

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks(): void {
		add_action( 'after_setup_theme', [ $this, 'register_menus' ] );
	}

	/**
	 * Register navigation menus.
	 *
	 * @since 1.0.0
	 */
	public function register_menus(): void {
		register_nav_menus(
			[
				'primary'         => esc_html__( 'Header menu', 'aktheme' ),
				'footer-main'     => esc_html__( 'Footer: Column 1', 'aktheme' ),
				'footer-col-2'    => esc_html__( 'Footer: Column 2', 'aktheme' ),
				'footer-col-3'    => esc_html__( 'Footer: Column 3', 'aktheme' ),
				'footer-col-4'    => esc_html__( 'Footer: Column 4', 'aktheme' ),
				'footer-region-1' => esc_html__( 'Footer: Region 1', 'aktheme' ),
				'footer-region-2' => esc_html__( 'Footer: Region 2', 'aktheme' ),
				'footer-region-3' => esc_html__( 'Footer: Region 3', 'aktheme' ),
				'blog'            => esc_html__( 'Blog: Category bar (single articles)', 'aktheme' ),
			]
		);
	}
}
