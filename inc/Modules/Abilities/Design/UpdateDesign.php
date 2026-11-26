<?php
/**
 * Ability: change the site's design tokens and global styles.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Design;

use AK\Theme\Modules\Abilities\Ability;

/**
 * Class UpdateDesign
 *
 * `aktheme/update-site-design`: change palette colours, font sizes and global styles (typography, colours, spacing,
 * elements, blocks) the way the Site Editor's Styles panel does. The change is merged into what is saved, filtered
 * for unsafe CSS and stored as a new Global Styles revision, so aktheme/restore-site-design (or Site Editor > Styles >
 * Revisions) can undo it.
 *
 * @since 1.3.0
 */
class UpdateDesign extends Ability {

	use GlobalStyles;

	/**
	 * A CSS colour: hex, rgb(a), hsl(a) or a palette variable.
	 */
	public const COLOR_PATTERN = '^(#([0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})|(rgb|rgba|hsl|hsla)\\([0-9.,%\\s/]+\\)|var\\(--wp--preset--color--[a-z0-9-]+\\))$';

	/**
	 * A CSS length or a clamp()/min()/max()/calc() of lengths.
	 */
	public const SIZE_PATTERN = '^[0-9a-z.,%()+*/\\s-]+$';

	/**
	 * A preset slug.
	 */
	public const SLUG_PATTERN = '^[a-z0-9-]+$';

	/**
	 * {@inheritDoc}
	 */
	protected function slug(): string {
		return 'update-site-design';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function label(): string {
		return __( 'Update site design', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function description(): string {
		return __( 'Changes the site-wide design, like Site Editor > Styles: palette colours (change a colour by its slug, or add one), font sizes, and theme.json "styles" (e.g. {"color":{"background":"var:preset|color|white"},"typography":{"fontFamily":"var:preset|font-family|inter"},"elements":{"button":{"color":{"background":"var:preset|color|gold"}},"h1":{"typography":{"fontSize":"var:preset|font-size|xx-large"}}},"blocks":{"core/navigation":{"typography":{"fontSize":"1rem"}}}}). Styles are merged into the current ones unless replace_styles is true. Unsafe CSS is removed. Every call is a revision; aktheme/restore-site-design undoes it. Call aktheme/get-site-design first for the slugs.', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function input_schema(): array {
		$name = [
			'type'      => 'string',
			'maxLength' => 60,
		];
		$slug = [
			'type'    => 'string',
			'pattern' => self::SLUG_PATTERN,
		];

		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => [
				'palette'        => [
					'type'        => 'array',
					'maxItems'    => 40,
					'description' => __( 'Colours to change (by slug) or add.', 'aktheme' ),
					'items'       => [
						'type'                 => 'object',
						'additionalProperties' => false,
						'required'             => [ 'slug', 'color' ],
						'properties'           => [
							'slug'  => $slug,
							'name'  => $name,
							'color' => [
								'type'    => 'string',
								'pattern' => self::COLOR_PATTERN,
							],
						],
					],
				],
				'font_sizes'     => [
					'type'        => 'array',
					'maxItems'    => 20,
					'description' => __( 'Font sizes to change (by slug) or add, e.g. {"slug":"large","size":"clamp(1.25rem, 2vw, 1.5rem)"}.', 'aktheme' ),
					'items'       => [
						'type'                 => 'object',
						'additionalProperties' => false,
						'required'             => [ 'slug', 'size' ],
						'properties'           => [
							'slug' => $slug,
							'name' => $name,
							'size' => [
								'type'      => 'string',
								'pattern'   => self::SIZE_PATTERN,
								'maxLength' => 80,
							],
						],
					],
				],
				'styles'         => [
					'type'        => 'object',
					'description' => __( 'theme.json "styles" to merge in: color, typography, spacing, border, elements, blocks.', 'aktheme' ),
				],
				'replace_styles' => [
					'type'        => 'boolean',
					'default'     => false,
					'description' => __( 'Replace every global style changed in the Site Editor instead of merging.', 'aktheme' ),
				],
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function output_schema(): array {
		return $this->changed_schema();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function destructive(): bool {
		return true; // It overwrites values; the revision keeps the old ones.
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $input Input.
	 */
	public function execute( mixed $input ): mixed {
		$input = is_array( $input ) ? $input : [];

		if ( empty( $input['palette'] ) && empty( $input['font_sizes'] ) && empty( $input['styles'] ) && empty( $input['replace_styles'] ) ) {
			return new \WP_Error( 'aktheme_nothing_to_change', __( 'Give palette, font_sizes or styles.', 'aktheme' ), [ 'status' => 400 ] );
		}

		$user = $this->user_layer();

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$settings_change = [];

		if ( ! empty( $input['palette'] ) ) {
			$settings_change['color']['palette']['theme'] = $this->merge_presets( [ 'color', 'palette' ], (array) $input['palette'], 'color' );
		}

		if ( ! empty( $input['font_sizes'] ) ) {
			$settings_change['typography']['fontSizes']['theme'] = $this->merge_presets( [ 'typography', 'fontSizes' ], (array) $input['font_sizes'], 'size' );
		}

		$change = $this->clean_change( $settings_change, $this->to_array( $input['styles'] ?? [] ) );

		if ( [] !== $settings_change && [] === $change['settings'] ) {
			return new \WP_Error( 'aktheme_unsafe_design', __( 'The colours or sizes were not accepted as safe CSS.', 'aktheme' ), [ 'status' => 400 ] );
		}

		$settings = array_replace_recursive( $user['settings'], $change['settings'] );

		// Preset lists replace the saved list as a whole (a merge by index would mix two lists).
		foreach ( [ [ 'color', 'palette', 'theme' ], [ 'typography', 'fontSizes', 'theme' ] ] as $path ) {
			$list = _wp_array_get( $change['settings'], $path, null );

			if ( null !== $list ) {
				_wp_array_set( $settings, $path, $list );
			}
		}

		$styles = ! empty( $input['replace_styles'] ) ? $change['styles'] : array_replace_recursive( $user['styles'], $change['styles'] );
		$saved  = $this->save_user_layer( $settings, $styles );

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return [
			'id'      => $this->global_styles_id(),
			'message' => __( 'Design saved as a new Global Styles revision.', 'aktheme' ),
		];
	}

	/**
	 * The theme's presets at a path (with the Site Editor's changes) with the given ones changed or added by slug.
	 *
	 * @param string[]                         $path   Settings path.
	 * @param array<int, array<string, mixed>> $change Presets to change or add.
	 * @param string                           $value  The preset's value key (`color`, `size`).
	 *
	 * @return list<array<string, mixed>> Presets.
	 */
	private function merge_presets( array $path, array $change, string $value ): array {
		$current = $this->to_array( wp_get_global_settings( $path ) );
		$by_slug = [];

		foreach ( $this->to_array( $current['theme'] ?? [] ) as $preset ) {
			if ( is_array( $preset ) && isset( $preset['slug'] ) ) {
				$by_slug[ (string) $preset['slug'] ] = $preset;
			}
		}

		foreach ( $change as $preset ) {
			$slug = sanitize_key( (string) ( $preset['slug'] ?? '' ) );

			if ( '' === $slug ) {
				continue;
			}

			$by_slug[ $slug ] = array_merge(
				$by_slug[ $slug ] ?? [ 'name' => ucwords( str_replace( '-', ' ', $slug ) ) ],
				array_filter(
					[
						'slug'  => $slug,
						'name'  => isset( $preset['name'] ) ? sanitize_text_field( (string) $preset['name'] ) : null,
						$value  => (string) $preset[ $value ],
					],
					static fn ( $item ) => null !== $item
				)
			);
		}

		return array_values( $by_slug );
	}
}
