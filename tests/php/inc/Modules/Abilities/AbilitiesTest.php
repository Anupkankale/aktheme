<?php
/**
 * Test the theme's abilities.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

use AK\Theme\Core\Settings;
use AK\Theme\Modules\Abilities\Ability;
use AK\Theme\Modules\Abilities\Registrar;
use AK\Theme\Tests\TestCase;

/**
 * Class AbilitiesTest
 *
 * Runs each ability the way an Abilities API client does (wp_get_ability()->execute()), so the input schema, the
 * permission check and the output schema are all exercised.
 *
 * @since 1.3.0
 */
class AbilitiesTest extends TestCase {

	/**
	 * Skip on WordPress versions without the Abilities API (before 6.9).
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'The Abilities API needs WordPress 6.9.' );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * Run an ability.
	 *
	 * @param string $name  Ability name.
	 * @param mixed  $input Input.
	 *
	 * @return mixed Result.
	 */
	private function run_ability( string $name, $input = null ) {
		$ability = wp_get_ability( $name );

		$this->assertNotNull( $ability, $name . ' is registered.' );

		return null === $input ? $ability->execute() : $ability->execute( $input );
	}

	/**
	 * Test every ability is registered in the theme's category, shown in REST and offered over MCP with annotations.
	 */
	public function test_abilities_are_registered(): void {
		$this->assertTrue( wp_has_ability_category( Ability::CATEGORY ) );

		foreach ( Registrar::ABILITIES as $class_name ) {
			$name    = ( new $class_name() )->name();
			$ability = wp_get_ability( $name );

			$this->assertNotNull( $ability, $name );
			$this->assertSame( Ability::CATEGORY, $ability->get_category(), $name );

			$meta = $ability->get_meta();
			$this->assertTrue( $meta['show_in_rest'], $name );
			$this->assertTrue( $meta['mcp']['public'], $name );
			$this->assertIsBool( $meta['annotations']['readonly'], $name );
		}
	}

	/**
	 * Test the abilities refuse a user without the capability.
	 */
	public function test_subscriber_is_refused(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		foreach ( [ 'aktheme/get-site-design', 'aktheme/list-menus', 'aktheme/get-theme-settings' ] as $name ) {
			$this->assertWPError( $this->run_ability( $name ), $name );
		}

		$this->assertWPError(
			$this->run_ability(
				'aktheme/update-template-part',
				[
					'slug'    => 'header',
					'content' => '<!-- wp:paragraph --><p>x</p><!-- /wp:paragraph -->',
				]
			)
		);
	}

	/**
	 * Test a palette colour changes in Global Styles (not in the theme's files), and restoring undoes it.
	 */
	public function test_update_and_restore_site_design(): void {
		$palette = $this->run_ability( 'aktheme/get-site-design' )['palette'];
		$this->assertNotEmpty( $palette );

		$slug = (string) $palette[0]['slug'];

		$this->assertIsArray(
			$this->run_ability(
				'aktheme/update-site-design',
				[
					'palette' => [
						[
							'slug'  => $slug,
							'color' => '#123456',
						],
					],
					'styles'  => [ 'color' => [ 'background' => '#fafafa' ] ],
				]
			)
		);

		wp_clean_theme_json_cache();
		$colors = wp_list_pluck( wp_get_global_settings( [ 'color', 'palette' ] )['theme'], 'color', 'slug' );
		$this->assertSame( '#123456', $colors[ $slug ] );
		$this->assertSame( '#fafafa', wp_get_global_styles( [ 'color', 'background' ] ) );

		// A second change, then undo it: the revision before the current one is the first change.
		$this->run_ability( 'aktheme/update-site-design', [ 'styles' => [ 'color' => [ 'background' => '#eeeeee' ] ] ] );
		$this->assertIsArray( $this->run_ability( 'aktheme/restore-site-design', [] ) );

		wp_clean_theme_json_cache();
		$this->assertSame( '#fafafa', wp_get_global_styles( [ 'color', 'background' ] ) );
	}

	/**
	 * Test a colour that isn't one is refused by the input schema, and unsafe CSS never reaches the styles.
	 */
	public function test_unsafe_design_is_refused(): void {
		$this->assertWPError(
			$this->run_ability(
				'aktheme/update-site-design',
				[
					'palette' => [
						[
							'slug'  => 'navy',
							'color' => 'red;}body{display:none',
						],
					],
				]
			)
		);

		$this->run_ability( 'aktheme/update-site-design', [ 'styles' => [ 'color' => [ 'background' => 'url(javascript:alert(1))' ] ] ] );

		wp_clean_theme_json_cache();
		$this->assertStringNotContainsString( 'javascript', (string) wp_get_global_styles( [ 'color', 'background' ] ) );
	}

	/**
	 * Test the header can be read with its pattern expanded, changed, and reset to the theme's version.
	 */
	public function test_update_and_reset_template_part(): void {
		$header = $this->run_ability( 'aktheme/get-template-part', [ 'slug' => 'header' ] );

		$this->assertSame( 'theme', $header['source'] );
		$this->assertStringContainsString( 'wp:pattern', $header['raw'] );
		$this->assertStringNotContainsString( 'wp:pattern {"slug":"aktheme/header"}', $header['content'] );

		$this->assertWPError(
			$this->run_ability(
				'aktheme/update-template-part',
				[
					'slug'    => 'header',
					'content' => '<p>Not blocks</p>',
				]
			)
		);

		$this->run_ability(
			'aktheme/update-template-part',
			[
				'slug'    => 'header',
				'content' => '<!-- wp:paragraph --><p>New header</p><!-- /wp:paragraph -->',
			]
		);

		$changed = $this->run_ability( 'aktheme/get-template-part', [ 'slug' => 'header' ] );
		$this->assertSame( 'custom', $changed['source'] );
		$this->assertStringContainsString( 'New header', $changed['content'] );

		$this->run_ability( 'aktheme/reset-template-part', [ 'slug' => 'header' ] );
		$this->assertSame( 'theme', $this->run_ability( 'aktheme/get-template-part', [ 'slug' => 'header' ] )['source'] );
	}

	/**
	 * Test a menu location gets the given links, with a site path made absolute.
	 */
	public function test_update_menu(): void {
		$result = $this->run_ability(
			'aktheme/update-menu',
			[
				'location' => 'primary',
				'items'    => [
					[
						'title'    => 'About',
						'url'      => '/about/',
						'children' => [
							[
								'title' => 'Team',
								'url'   => 'https://example.org/team/',
							],
						],
					],
				],
			]
		);

		$this->assertIsArray( $result );

		$menus   = wp_list_pluck( $this->run_ability( 'aktheme/list-menus' ), 'items', 'location' );
		$primary = $menus['primary'];

		$this->assertSame( home_url( '/about/' ), $primary[0]['url'] );
		$this->assertSame( 'Team', $primary[0]['children'][0]['title'] );
	}

	/**
	 * Test a page is created from a theme pattern as a draft, and can be changed.
	 */
	public function test_create_and_update_page(): void {
		$created = $this->run_ability(
			'aktheme/create-page',
			[
				'title'   => 'Privacy',
				'pattern' => 'aktheme/text-page',
			]
		);

		$this->assertIsArray( $created );
		$this->assertSame( 'draft', get_post_status( $created['id'] ) );
		$this->assertStringContainsString( 'wp:ak/section', get_post_field( 'post_content', $created['id'] ) );

		$this->run_ability(
			'aktheme/update-page',
			[
				'id'      => $created['id'],
				'content' => '<!-- wp:paragraph --><p>Updated</p><!-- /wp:paragraph -->',
			]
		);

		$page = $this->run_ability( 'aktheme/get-pages', [ 'id' => $created['id'] ] );
		$this->assertStringContainsString( 'Updated', $page[0]['content'] );
	}

	/**
	 * Test the settings change one at a time and keep the others.
	 */
	public function test_update_theme_settings(): void {
		$settings = $this->run_ability( 'aktheme/update-theme-settings', [ 'blog_article_inserts' => true ] );

		$this->assertTrue( $settings['blog_article_inserts'] );
		$this->assertTrue( $settings['youtube_consent'] );
		$this->assertTrue( Settings::get( 'blog_article_inserts' ) );
	}

	/**
	 * Test the MCP filter can hide abilities that change things.
	 */
	public function test_mcp_exposure_is_filterable(): void {
		add_filter( 'aktheme_abilities_mcp_public', static fn ( bool $is_public, string $name, bool $is_readonly ): bool => $is_readonly, 10, 3 );

		$meta = ( new AK\Theme\Modules\Abilities\Design\UpdateDesign() )->args()['meta'];
		$this->assertFalse( $meta['mcp']['public'] );

		$meta = ( new AK\Theme\Modules\Abilities\Design\GetDesign() )->args()['meta'];
		$this->assertTrue( $meta['mcp']['public'] );
	}
}
