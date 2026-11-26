<?php
/**
 * Shared helpers of the template part abilities.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Templates;

use WP_Block_Template;

/**
 * Trait TemplateParts
 *
 * Template parts (header, footer, promo bar, ...) of the active theme. The theme ships them in parts/; a change from
 * the Site Editor or an ability is a `wp_template_part` post that takes their place, kept across theme updates and
 * removable to go back to the theme's version.
 *
 * @since 1.3.0
 */
trait TemplateParts {

	/**
	 * Template part slug schema.
	 *
	 * @return array<string, mixed> Schema.
	 */
	protected function slug_schema(): array {
		return [
			'type'        => 'string',
			'pattern'     => '^[a-z0-9-]+$',
			'maxLength'   => 60,
			'description' => __( 'Template part slug, e.g. "header" or "footer" (see aktheme/list-template-parts).', 'aktheme' ),
		];
	}

	/**
	 * A template part of the active theme.
	 *
	 * @param string $slug Slug.
	 *
	 * @return WP_Block_Template|null The part, or null.
	 */
	protected function part( string $slug ): ?WP_Block_Template {
		return get_block_template( $this->part_id( $slug ), 'wp_template_part' );
	}

	/**
	 * The REST/entity ID of a part: `<theme>//<slug>`.
	 *
	 * @param string $slug Slug.
	 *
	 * @return string ID.
	 */
	protected function part_id( string $slug ): string {
		return get_stylesheet() . '//' . $slug;
	}

	/**
	 * What a list shows about a part.
	 *
	 * @param WP_Block_Template $part Part.
	 *
	 * @return array<string, mixed> Summary.
	 */
	protected function summary( WP_Block_Template $part ): array {
		return [
			'slug'           => $part->slug,
			'title'          => $part->title,
			'area'           => $part->area,
			'source'         => $part->source,
			'has_theme_file' => (bool) $part->has_theme_file,
			'modified'       => $part->modified ? (string) $part->modified : null,
		];
	}
}
