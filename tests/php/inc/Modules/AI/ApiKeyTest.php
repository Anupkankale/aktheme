<?php
/**
 * Test the Anthropic API key storage.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

use AK\Theme\Core\Settings;
use AK\Theme\Modules\AI\ApiKey;
use AK\Theme\Tests\TestCase;

/**
 * Class ApiKeyTest
 *
 * @since 1.3.0
 */
class ApiKeyTest extends TestCase {

	/**
	 * A fake key.
	 *
	 * @var string
	 */
	private const KEY = 'sk-ant-api03-test-0000000000000000-WXYZ';

	/**
	 * Start without a key.
	 */
	public function setUp(): void {
		parent::setUp();

		putenv( 'ANTHROPIC_API_KEY' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Isolates the env-var key source.
		ApiKey::delete();
	}

	/**
	 * Test a saved key round-trips, is encrypted at rest and only its last four characters are shown.
	 */
	public function test_save_and_read(): void {
		$this->assertFalse( ApiKey::has() );
		$this->assertSame( '', ApiKey::source() );

		$this->assertTrue( ApiKey::save( self::KEY ) );

		$this->assertSame( self::KEY, ApiKey::get() );
		$this->assertSame( 'option', ApiKey::source() );
		$this->assertSame( 'WXYZ', ApiKey::last4() );

		$stored = (string) get_option( ApiKey::OPTION );
		$this->assertNotSame( '', $stored );
		$this->assertStringNotContainsString( self::KEY, $stored );
		$this->assertStringNotContainsString( 'sk-ant', $stored );
	}

	/**
	 * Test the key option is not autoloaded.
	 */
	public function test_not_autoloaded(): void {
		global $wpdb;

		ApiKey::save( self::KEY );

		$autoload = $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", ApiKey::OPTION ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Test.
		$this->assertContains( $autoload, [ 'no', 'off' ] );
	}

	/**
	 * Test saving an empty value removes the key.
	 */
	public function test_empty_value_deletes(): void {
		ApiKey::save( self::KEY );
		ApiKey::save( '   ' );

		$this->assertFalse( ApiKey::has() );
		$this->assertFalse( get_option( ApiKey::OPTION ) );
	}

	/**
	 * Test the environment variable wins over the saved key.
	 */
	public function test_env_wins(): void {
		ApiKey::save( self::KEY );
		putenv( 'ANTHROPIC_API_KEY=sk-ant-from-env-1234' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Isolates the env-var key source.

		$this->assertSame( 'sk-ant-from-env-1234', ApiKey::get() );
		$this->assertSame( 'env', ApiKey::source() );

		putenv( 'ANTHROPIC_API_KEY' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Isolates the env-var key source.
	}

	/**
	 * Test the key never shows up in the theme settings, over REST or through the settings ability.
	 */
	public function test_key_is_not_exposed(): void {
		ApiKey::save( self::KEY );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertStringNotContainsString( self::KEY, (string) wp_json_encode( Settings::all() ) );

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test isolation.
		do_action( 'rest_api_init', $wp_rest_server );

		$settings = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/wp/v2/settings' ) )->get_data();
		$this->assertStringNotContainsString( self::KEY, (string) wp_json_encode( $settings ) );
		$this->assertArrayNotHasKey( ApiKey::OPTION, $settings );

		if ( function_exists( 'wp_get_ability' ) ) {
			$ability = wp_get_ability( 'aktheme/get-theme-settings' );
			$this->assertNotNull( $ability );
			$this->assertStringNotContainsString( self::KEY, (string) wp_json_encode( $ability->execute() ) );
		}

		$wp_rest_server = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test isolation.
	}
}
