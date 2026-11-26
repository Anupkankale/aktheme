<?php
/**
 * Reading and saving the site's Global Styles (the user layer of theme.json).
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Design;

use WP_Theme_JSON;
use WP_Theme_JSON_Resolver;

/**
 * Trait GlobalStyles
 *
 * The Site Editor keeps the site's design changes in one `wp_global_styles` post per theme, on top of theme.json.
 * The design abilities read and write that post through the core REST route `/wp/v2/global-styles/<id>`, so every
 * change gets a revision (Site Editor > Styles > Revisions) and the same permission checks as the editor.
 *
 * @since 1.3.0
 */
trait GlobalStyles {

	/**
	 * ID of the active theme's user Global Styles post (created when missing).
	 *
	 * @return int Post ID.
	 */
	protected function global_styles_id(): int {
		return (int) WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
	}

	/**
	 * The user layer as saved: settings and styles.
	 *
	 * @return array{settings: array<string, mixed>, styles: array<string, mixed>}|\WP_Error The data, or the error.
	 */
	protected function user_layer(): array|\WP_Error {
		$data = $this->rest( 'GET', '/wp/v2/global-styles/' . $this->global_styles_id(), [ 'context' => 'edit' ] );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$data = $this->to_array( $data );

		return [
			'settings' => $this->to_array( $data['settings'] ?? [] ),
			'styles'   => $this->to_array( $data['styles'] ?? [] ),
		];
	}

	/**
	 * Save settings and styles as the user layer.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param array<string, mixed> $styles   Styles.
	 *
	 * @return true|\WP_Error True when saved.
	 */
	protected function save_user_layer( array $settings, array $styles ): bool|\WP_Error {
		$saved = $this->rest(
			'POST',
			'/wp/v2/global-styles/' . $this->global_styles_id(),
			[
				'settings' => (object) $settings,
				'styles'   => (object) $styles,
			]
		);

		return is_wp_error( $saved ) ? $saved : true;
	}

	/**
	 * A change without unsafe CSS or keys theme.json doesn't know: the filter core applies to users without
	 * `unfiltered_html`, applied here to everyone (an agent's input is never trusted CSS). Only the change is filtered;
	 * what is saved already came through the editor.
	 *
	 * @param array<string, mixed> $settings Settings to add.
	 * @param array<string, mixed> $styles   Styles to add.
	 *
	 * @return array{settings: array<string, mixed>, styles: array<string, mixed>} The safe part.
	 */
	protected function clean_change( array $settings, array $styles ): array {
		$clean = WP_Theme_JSON::remove_insecure_properties(
			[
				'version'  => WP_Theme_JSON::LATEST_SCHEMA,
				'settings' => $settings,
				'styles'   => $styles,
			],
			'custom'
		);

		return [
			'settings' => $this->to_array( $clean['settings'] ?? [] ),
			'styles'   => $this->to_array( $clean['styles'] ?? [] ),
		];
	}

	/**
	 * Decode objects into arrays.
	 *
	 * @param mixed $value Value.
	 *
	 * @return array<string, mixed> Array.
	 */
	protected function to_array( mixed $value ): array {
		$decoded = json_decode( (string) wp_json_encode( $value ), true );

		return is_array( $decoded ) ? $decoded : [];
	}
}
