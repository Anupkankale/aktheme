<?php
/**
 * Test the template parts render from their patterns with portable addresses.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

use AK\Theme\Tests\TestCase;

/**
 * Class PartsTest
 *
 * @since 1.3.0
 */
class PartsTest extends TestCase {

	/**
	 * Test the header, footer and promo bar render their pattern, with links on the site address and images in the
	 * theme folder (never a root-relative "/wp-content/themes/..." path).
	 */
	public function test_parts_use_site_and_theme_addresses(): void {
		foreach ( [ 'header', 'footer', 'promo-bar' ] as $slug ) {
			$html = do_blocks( '<!-- wp:template-part {"slug":"' . $slug . '","theme":"' . get_stylesheet() . '"} /-->' );

			$this->assertNotSame( '', trim( $html ), $slug );
			$this->assertStringNotContainsString( 'src="/wp-content/', $html, $slug );
			$this->assertStringNotContainsString( 'href="/', $html, $slug );
		}

		$footer = do_blocks( '<!-- wp:template-part {"slug":"footer","theme":"' . get_stylesheet() . '"} /-->' );

		$this->assertStringContainsString( esc_url( get_theme_file_uri( 'assets/images/theme/world-map.svg' ) ), $footer );
		$this->assertStringContainsString( 'href="' . esc_url( home_url( '/about-us/' ) ) . '"', $footer );
	}
}
