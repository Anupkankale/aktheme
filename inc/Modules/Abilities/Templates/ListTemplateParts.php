<?php
/**
 * Ability: list the template parts.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Templates;

use AK\Theme\Modules\Abilities\Ability;

/**
 * Class ListTemplateParts
 *
 * `aktheme/list-template-parts`: the header, footer and other template parts, and whether each is the theme's version
 * or has been changed on this site.
 *
 * @since 1.3.0
 */
class ListTemplateParts extends Ability {

	use TemplateParts;

	/**
	 * {@inheritDoc}
	 */
	protected function slug(): string {
		return 'list-template-parts';
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
		return __( 'List template parts', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function description(): string {
		return __( 'Lists the template parts (header, footer, promo bar, ...): slug, title, area, and source ("theme" for the theme\'s version, "custom" when changed on this site).', 'aktheme' );
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
		return [
			'type'  => 'array',
			'items' => [
				'type'       => 'object',
				'properties' => [
					'slug'   => [ 'type' => 'string' ],
					'title'  => [ 'type' => 'string' ],
					'area'   => [ 'type' => 'string' ],
					'source' => [ 'type' => 'string' ],
				],
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

		$parts = array_filter( get_block_templates( [], 'wp_template_part' ), static fn ( $part ) => get_stylesheet() === $part->theme );

		return array_values( array_map( [ $this, 'summary' ], $parts ) );
	}
}
