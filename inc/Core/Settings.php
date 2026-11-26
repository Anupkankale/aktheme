<?php
/**
 * Theme settings: the per-site switches and values that differ between clients.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Core;

use rtCamp\WPPrimitives\Contracts\Interfaces\Registrable;

/**
 * Class Settings
 *
 * One option (`aktheme_settings`) registered with the Settings API and a JSON schema, so it is validated the same way
 * whether it is saved by the REST API (`/wp/v2/settings`), by an ability (aktheme/update-theme-settings) or by PHP.
 *
 * Everything that used to be hard-coded for one client lives here and is off or empty by default, so a new site
 * starts neutral:
 * - `blog_article_inserts`: add the program card, skills banner and "Recommended reading" to every blog post.
 * - `company_linkedin_url`: the author box's LinkedIn link when the author has no LinkedIn profile of their own.
 * - `youtube_consent`: ask before any YouTube player loads (gallery blocks and Embed blocks).
 *
 * @since 1.3.0
 */
class Settings implements Registrable {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	public const OPTION = 'aktheme_settings';

	/**
	 * Register hooks.
	 *
	 * @since 1.3.0
	 */
	public function register_hooks(): void {
		add_action( 'init', [ $this, 'register' ] );
		add_action( 'admin_init', [ $this, 'migrate' ] );
	}

	/**
	 * Sites that ran the theme before these settings existed keep their article inserts: the theme added them to every
	 * post then. Detected by the synced sections the theme sets up on first use. Runs once.
	 *
	 * @since 1.3.0
	 */
	public function migrate(): void {
		// An explicit default skips the registered one, so a missing option reads as false.
		if ( false !== get_option( self::OPTION, false ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$existing_site = false !== get_option( 'aktheme_synced_sections_version' );

		add_option( self::OPTION, array_merge( self::defaults(), [ 'blog_article_inserts' => $existing_site ] ) );
	}

	/**
	 * Default values.
	 *
	 * @since 1.3.0
	 *
	 * @return array{blog_article_inserts: bool, company_linkedin_url: string, youtube_consent: bool}
	 */
	public static function defaults(): array {
		return [
			'blog_article_inserts' => false,
			'company_linkedin_url' => '',
			'youtube_consent'      => true,
		];
	}

	/**
	 * JSON schema of the option (also the input schema of the settings ability).
	 *
	 * @since 1.3.0
	 *
	 * @return array<string, mixed> Schema.
	 */
	public static function schema(): array {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => [
				'blog_article_inserts' => [
					'type'        => 'boolean',
					'description' => __( 'Add the program card, the skills banner and "Recommended reading" inside every blog post.', 'aktheme' ),
				],
				'company_linkedin_url' => [
					'type'        => 'string',
					'pattern'     => '^(https://\S+)?$', // An https address, or empty (a `uri` format would refuse empty).
					'maxLength'   => 500,
					'description' => __( 'Company LinkedIn page, linked from the author box when the author has no LinkedIn profile. Empty: no link.', 'aktheme' ),
				],
				'youtube_consent'      => [
					'type'        => 'boolean',
					'description' => __( 'Ask visitors before a YouTube player loads (privacy, GDPR).', 'aktheme' ),
				],
			],
		];
	}

	/**
	 * Register the option.
	 *
	 * @since 1.3.0
	 */
	public function register(): void {
		register_setting(
			'aktheme',
			self::OPTION,
			[
				'type'              => 'object',
				'label'             => __( 'AK Theme settings', 'aktheme' ),
				'default'           => self::defaults(),
				'sanitize_callback' => [ self::class, 'sanitize' ],
				'show_in_rest'      => [ 'schema' => self::schema() ],
			]
		);
	}

	/**
	 * Keep known keys with the right types; anything else falls back to the stored or default value.
	 *
	 * @since 1.3.0
	 *
	 * @param mixed $value Submitted value.
	 *
	 * @return array{blog_article_inserts: bool, company_linkedin_url: string, youtube_consent: bool} Clean value.
	 */
	public static function sanitize( $value ): array {
		$value = is_array( $value ) ? $value : [];
		$clean = self::all();

		foreach ( [ 'blog_article_inserts', 'youtube_consent' ] as $key ) {
			if ( array_key_exists( $key, $value ) ) {
				$clean[ $key ] = wp_validate_boolean( $value[ $key ] );
			}
		}

		if ( array_key_exists( 'company_linkedin_url', $value ) ) {
			$clean['company_linkedin_url'] = esc_url_raw( (string) $value['company_linkedin_url'], [ 'https' ] );
		}

		return $clean;
	}

	/**
	 * Every setting, with defaults for the missing ones.
	 *
	 * @since 1.3.0
	 *
	 * @return array{blog_article_inserts: bool, company_linkedin_url: string, youtube_consent: bool} Settings.
	 */
	public static function all(): array {
		$stored   = get_option( self::OPTION, [] );
		$stored   = is_array( $stored ) ? $stored : [];
		$defaults = self::defaults();

		return [
			'blog_article_inserts' => (bool) ( $stored['blog_article_inserts'] ?? $defaults['blog_article_inserts'] ),
			'company_linkedin_url' => (string) ( $stored['company_linkedin_url'] ?? $defaults['company_linkedin_url'] ),
			'youtube_consent'      => (bool) ( $stored['youtube_consent'] ?? $defaults['youtube_consent'] ),
		];
	}

	/**
	 * One setting.
	 *
	 * @since 1.3.0
	 *
	 * @param string $key Setting key.
	 *
	 * @return bool|string|null The value, or null for an unknown key.
	 */
	public static function get( string $key ) {
		$all = self::all();

		/**
		 * Filters one theme setting, e.g. to lock it per environment from a must-use plugin.
		 *
		 * @since 1.3.0
		 *
		 * @param bool|string|null $value The value.
		 * @param string           $key   Setting key.
		 */
		return apply_filters( 'aktheme_setting', $all[ $key ] ?? null, $key );
	}
}
