<?php
/**
 * Test the PageStyles class.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

use AK\Theme\Modules\Blocks\PageStyles;
use AK\Theme\Tests\TestCase;
use rtCamp\WPPrimitives\Contracts\Interfaces\Registrable;

/**
 * Class PageStylesTest
 *
 * @since 1.1.94
 */
class PageStylesTest extends TestCase {

	/**
	 * PageStyles instance.
	 *
	 * @var PageStyles
	 */
	private PageStyles $instance;

	/**
	 * Setup test.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->instance = new PageStyles();
	}

	/**
	 * Test class implements Registrable.
	 */
	public function test_implements_registrable(): void {
		$this->assertInstanceOf( Registrable::class, $this->instance );
	}

	/**
	 * Test hooks are registered.
	 */
	public function test_register_hooks(): void {
		$this->instance->register_hooks();

		$this->assertGreaterThan( 0, has_action( 'init', [ $this->instance, 'register_styles' ] ) );
		$this->assertGreaterThan( 0, has_filter( 'render_block', [ $this->instance, 'collect' ] ) );
		$this->assertGreaterThan( 0, has_action( 'wp_enqueue_scripts', [ $this->instance, 'enqueue_needed' ] ) );
		$this->assertGreaterThan( 0, has_action( 'enqueue_block_assets', [ $this->instance, 'enqueue_in_editor' ] ) );
		$this->assertLessThan( has_action( 'wp_enqueue_scripts', [ $this->instance, 'enqueue_needed' ] ), has_action( 'wp_enqueue_scripts', [ $this->instance, 'enqueue_base' ] ), 'The shared styles are enqueued before the page styles are ordered.' );
	}

	/**
	 * The footer (on every page) uses the shared form styles: the shared styles load on pages without AK blocks too
	 * (the blog archives, search, 404), where the footer's newsletter form was unstyled and overflowed at 1024px.
	 */
	public function test_shared_styles_load_on_every_front_end_page(): void {
		wp_register_style( 'ak-base', 'https://example.test/base-end.css', PageStyles::base_handles(), '1' );
		$this->instance->register_styles();
		wp_dequeue_style( 'ak-base' );
		$this->assertFalse( wp_style_is( 'ak-base', 'enqueued' ) );

		$this->instance->enqueue_base();
		$this->instance->enqueue_needed();

		$this->assertTrue( wp_style_is( 'ak-base', 'enqueued' ) );
		$this->assertSame( PageStyles::base_handles(), wp_styles()->query( 'ak-base' )->deps, 'No page styles on a page without AK blocks.' );

		wp_dequeue_style( 'ak-base' );
	}

	/**
	 * A page's own class prefix selects its stylesheet; other pages' stylesheets stay out.
	 */
	public function test_matches_the_page_prefix(): void {
		$html = '<section class="wp-block-ak-section alignfull ak-section tp-hero"><div class="tp-body is-active"></div></section>';

		$this->assertSame( [ 'text-page' ], $this->instance->matches( $html ) );
	}

	/**
	 * Only class names count: the same letters in text, a link or another attribute don't load anything.
	 */
	public function test_ignores_prefixes_outside_class_attributes(): void {
		$html = '<section class="ak-section"><p>ab-testing and bl-ue</p><a href="/bp-guide/" data-x="tm-1">x</a></section>';

		$this->assertSame( [], $this->instance->matches( $html ) );
	}

	/**
	 * A prefix must start the class name: "stp-panel" is not the text page's "tp-", "abp-x" not the blog post's "bp-".
	 */
	public function test_prefix_must_start_a_class_name(): void {
		$this->assertSame( [], $this->instance->matches( '<div class="stp-panel abp-x ak-section"></div>' ) );
	}

	/**
	 * Pages that reuse another page's styles get both, in the order base.scss loaded them (cascade order).
	 */
	public function test_several_pages_keep_the_cascade_order(): void {
		$html = '<section class="ak-section tp-hero"></section><div class="bp-grid"></div>';

		$this->assertSame( [ 'blog-post', 'text-page' ], $this->instance->matches( $html ) );
	}

	/**
	 * Only AK blocks are scanned (every split selector sits under .ak-section); what they need is enqueued in order.
	 */
	public function test_collects_from_aktheme_blocks_only(): void {
		$this->instance->collect( '<div class="bp-content"></div>', [ 'blockName' => 'core/group' ] );
		$this->assertSame( [], $this->instance->needed() );

		$this->instance->collect( '<section class="ak-section tp-body"></section>', [ 'blockName' => 'ak/section' ] );
		$this->instance->collect( '<section class="ak-section bp-hero"></section>', [ 'blockName' => 'ak/section' ] );
		$this->assertSame( [ 'blog-post', 'text-page' ], $this->instance->needed() );
	}

	/**
	 * The collect() filter returns the block markup unchanged.
	 */
	public function test_collect_returns_markup_unchanged(): void {
		$html = '<section class="ak-section tp-hero">x</section>';

		$this->assertSame( $html, $this->instance->collect( $html, [ 'blockName' => 'ak/section' ] ) );
	}

	/**
	 * Every split stylesheet is listed with a prefix, and handles carry the theme prefix.
	 */
	public function test_handles(): void {
		foreach ( PageStyles::STYLES as $name => $prefixes ) {
			$this->assertNotEmpty( $prefixes, $name );
			$this->assertSame( 'ak-page-' . $name, PageStyles::handle( $name ) );
		}
	}

	/**
	 * The ak-base handle depends on the shared parts and the page's stylesheets in the original cascade order.
	 */
	public function test_dependencies_keep_the_original_cascade_order(): void {
		$this->assertSame(
			[ 'ak-base-start', 'ak-base-sections' ],
			$this->instance->dependencies( [] ),
			'A page without page styles still gets every shared part.'
		);

		$this->assertSame(
			[ 'ak-base-start', 'ak-base-sections', 'ak-page-blog-post', 'ak-page-text-page' ],
			$this->instance->dependencies( [ 'text-page', 'blog-post' ] )
		);
	}

	/**
	 * Every page stylesheet has a place in the cascade order, and the shared parts are the default dependencies.
	 */
	public function test_every_page_style_is_in_the_order(): void {
		foreach ( array_keys( PageStyles::STYLES ) as $name ) {
			$this->assertContains( $name, PageStyles::ORDER, $name );
		}

		$this->assertSame( [ 'ak-base-start', 'ak-base-sections' ], PageStyles::base_handles() );
	}

	/**
	 * On a page with AK blocks, ak-base gets the page's stylesheets as dependencies and they are enqueued.
	 */
	public function test_enqueue_needed_sets_the_order_on_aktheme_base(): void {
		wp_register_style( 'ak-base', 'https://example.test/base-end.css', PageStyles::base_handles(), '1' );
		$this->instance->register_styles();
		wp_enqueue_style( 'ak-base' );

		$this->instance->collect( '<section class="ak-section tp-page"></section>', [ 'blockName' => 'ak/section' ] );
		$this->instance->enqueue_needed();

		$this->assertSame(
			[ 'ak-base-start', 'ak-base-sections', 'ak-page-text-page' ],
			wp_styles()->query( 'ak-base' )->deps
		);
		$this->assertTrue( wp_style_is( 'ak-page-text-page', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'ak-page-blog-post', 'enqueued' ) );

		wp_dequeue_style( 'ak-base' );
	}

	/**
	 * The page stylesheets are no longer in the shared styles, and each one is a build entry.
	 */
	public function test_split_out_of_base(): void {
		$shared = '';
		foreach ( [ 'base', 'base-sections', 'base-end' ] as $file ) {
			$shared .= (string) file_get_contents( AKTHEME_PATH . '/src/blocks-shared/' . $file . '.scss' );
		}
		$config = (string) file_get_contents( AKTHEME_PATH . '/webpack.blocks.config.js' );

		foreach ( array_keys( PageStyles::STYLES ) as $name ) {
			$this->assertStringNotContainsString( "load-css('" . $name . "')", $shared, $name );
			$this->assertFileExists( AKTHEME_PATH . '/src/blocks-shared/_' . $name . '.scss' );
			$this->assertStringContainsString( "'" . $name . "'", $config, $name );
		}

		foreach ( [ 'shared/base', 'shared/base-sections', 'shared/base-end' ] as $entry ) {
			$this->assertStringContainsString( "'" . $entry . "'", $config, $entry );
		}
	}
}
