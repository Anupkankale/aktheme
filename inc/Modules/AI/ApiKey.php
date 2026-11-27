<?php
/**
 * Anthropic API key storage.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\AI;

use rtCamp\WPPrimitives\Utils\Encryptor;

/**
 * Class ApiKey
 *
 * Where the AI assistant's key comes from, in order:
 * 1. The `AKTHEME_ANTHROPIC_API_KEY` constant (wp-config.php). Recommended: never in the database.
 * 2. The `ANTHROPIC_API_KEY` environment variable.
 * 3. The key saved in Appearance > AK AI, stored encrypted (AES-256-GCM, keyed by the site's auth salt) in its own
 *    `aktheme_ai_key` option. That option is not autoloaded and not registered with the Settings API, so it never
 *    appears in /wp/v2/settings or in the theme-settings abilities.
 *
 * The key itself is never printed: the admin screen only shows its source and last four characters.
 *
 * @since 1.3.0
 */
final class ApiKey {

	/**
	 * Option holding the encrypted key.
	 *
	 * @var string
	 */
	public const OPTION = 'aktheme_ai_key';

	/**
	 * Constant name for wp-config.php.
	 *
	 * @var string
	 */
	public const CONSTANT = 'AKTHEME_ANTHROPIC_API_KEY';

	/**
	 * The key in use, or '' when none is configured.
	 *
	 * @since 1.3.0
	 */
	public static function get(): string {
		if ( defined( self::CONSTANT ) && is_string( constant( self::CONSTANT ) ) && '' !== constant( self::CONSTANT ) ) {
			return (string) constant( self::CONSTANT );
		}

		$env = getenv( 'ANTHROPIC_API_KEY' );
		if ( is_string( $env ) && '' !== $env ) {
			return $env;
		}

		$stored = get_option( self::OPTION, '' );
		if ( ! is_string( $stored ) || '' === $stored ) {
			return '';
		}

		$key = self::encryptor()->decrypt( $stored );

		return is_string( $key ) ? $key : '';
	}

	/**
	 * Where the key comes from: `constant`, `env`, `option`, or '' for none.
	 *
	 * @since 1.3.0
	 */
	public static function source(): string {
		if ( defined( self::CONSTANT ) && '' !== (string) constant( self::CONSTANT ) ) {
			return 'constant';
		}
		if ( '' !== (string) getenv( 'ANTHROPIC_API_KEY' ) ) {
			return 'env';
		}

		return '' !== self::get() ? 'option' : '';
	}

	/**
	 * Whether a key is configured.
	 *
	 * @since 1.3.0
	 */
	public static function has(): bool {
		return '' !== self::get();
	}

	/**
	 * The last four characters of the key, for display.
	 *
	 * @since 1.3.0
	 */
	public static function last4(): string {
		$key = self::get();

		return strlen( $key ) >= 8 ? substr( $key, -4 ) : '';
	}

	/**
	 * Save a key (encrypted). An empty value removes it.
	 *
	 * @since 1.3.0
	 *
	 * @param string $key The API key.
	 *
	 * @return bool Whether it was saved.
	 */
	public static function save( string $key ): bool {
		$key = trim( $key );
		if ( '' === $key ) {
			return self::delete();
		}

		$encrypted = self::encryptor()->encrypt( $key );
		if ( false === $encrypted ) {
			return false;
		}

		update_option( self::OPTION, $encrypted, false );

		return true;
	}

	/**
	 * Remove the saved key (a constant or environment variable is not affected).
	 *
	 * @since 1.3.0
	 */
	public static function delete(): bool {
		delete_option( self::OPTION );

		return true;
	}

	/**
	 * Encryptor keyed by the site's auth salt (AUTH_KEY . AUTH_SALT). Changing those salts makes a saved key unreadable,
	 * which then shows as "no key" and can simply be saved again.
	 */
	private static function encryptor(): Encryptor {
		return new Encryptor( wp_salt( 'auth' ) );
	}
}
