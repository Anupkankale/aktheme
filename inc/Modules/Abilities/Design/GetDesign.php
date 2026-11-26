<?php
/**
 * Ability: read the site's design tokens and global styles.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Design;

use AK\Theme\Modules\Abilities\Ability;

/**
 * Class GetDesign
 *
 * `aktheme/get-site-design`: the colours, fonts, font sizes and spacing sizes the site can use (theme.json merged with
 * the Site Editor's changes), the global styles in effect, what the Site Editor changed on top of the theme, and the
 * recent design revisions (for aktheme/restore-site-design).
 *
 * @since 1.3.0
 */
class GetDesign extends Ability {

	use GlobalStyles;

	/**
	 * {@inheritDoc}
	 */
	protected function slug(): string {
		return 'get-site-design';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function is_readonly(): bool {
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function label(): string {
		return __( 'Get site design', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function description(): string {
		return __( 'Returns the design tokens (colour palette, font families, font sizes, spacing sizes, each with its slug), the global styles in effect (theme.json "styles": colours, typography, spacing, elements such as links, buttons and headings, and per-block styles), the changes made on top of the theme, and recent design revisions. Call it before aktheme/update-site-design, and refer to tokens in styles as "var:preset|color|<slug>".', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function input_schema(): array {
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function output_schema(): array {
		$presets = [
			'type'  => 'array',
			'items' => [ 'type' => 'object' ],
		];

		return [
			'type'       => 'object',
			'properties' => [
				'palette'       => $presets,
				'font_families' => $presets,
				'font_sizes'    => $presets,
				'spacing_sizes' => $presets,
				'styles'        => [ 'type' => 'object' ],
				'customized'    => [ 'type' => 'object' ],
				'revisions'     => $presets,
			],
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $input Unused.
	 */
	public function execute( mixed $input ): mixed {
		unset( $input );

		$user = $this->user_layer();

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$revisions = $this->rest(
			'GET',
			'/wp/v2/global-styles/' . $this->global_styles_id() . '/revisions',
			[
				'per_page' => 10,
				'_fields'  => 'id,date,author',
			]
		);

		return [
			'palette'       => $this->preset( [ 'color', 'palette' ] ),
			'font_families' => $this->preset( [ 'typography', 'fontFamilies' ] ),
			'font_sizes'    => $this->preset( [ 'typography', 'fontSizes' ] ),
			'spacing_sizes' => $this->preset( [ 'spacing', 'spacingSizes' ] ),
			'styles'        => (object) $this->to_array( wp_get_global_styles() ),
			'customized'    => (object) $user,
			'revisions'     => is_wp_error( $revisions ) ? [] : $this->to_array( $revisions ),
		];
	}

	/**
	 * The theme's presets at a path, with the Site Editor's changes.
	 *
	 * @param string[] $path Settings path.
	 *
	 * @return array<int, array<string, mixed>> Presets.
	 */
	private function preset( array $path ): array {
		$by_origin = $this->to_array( wp_get_global_settings( $path ) );

		return array_values( $this->to_array( $by_origin['theme'] ?? [] ) );
	}
}
