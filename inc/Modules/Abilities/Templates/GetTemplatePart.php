<?php
/**
 * Ability: read a template part's blocks.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Templates;

use AK\Theme\Modules\Abilities\Ability;

/**
 * Class GetTemplatePart
 *
 * `aktheme/get-template-part`: the block markup of the header, footer or another part. The theme's parts only point at
 * a pattern (patterns/header.php, ...), so `content` has those patterns expanded: the blocks a visitor sees, ready to
 * edit and send back to aktheme/update-template-part.
 *
 * @since 1.3.0
 */
class GetTemplatePart extends Ability {

	use TemplateParts;

	/**
	 * {@inheritDoc}
	 */
	protected function slug(): string {
		return 'get-template-part';
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
		return __( 'Get template part', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function description(): string {
		return __( 'Returns the block markup of a template part such as the header or footer. "content" has theme patterns expanded into their blocks (edit it and pass it to aktheme/update-template-part); "raw" is what is stored. Menu links in the header and footer come from menus (aktheme/list-menus), not from this markup, when a block has an "ak-menu-<location>" class.', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function input_schema(): array {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => [ 'slug' ],
			'properties'           => [ 'slug' => $this->slug_schema() ],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function output_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'slug'    => [ 'type' => 'string' ],
				'area'    => [ 'type' => 'string' ],
				'source'  => [ 'type' => 'string' ],
				'content' => [ 'type' => 'string' ],
				'raw'     => [ 'type' => 'string' ],
			],
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $input Input.
	 */
	public function execute( mixed $input ): mixed {
		$slug = (string) ( is_array( $input ) ? ( $input['slug'] ?? '' ) : '' );
		$part = $this->part( $slug );

		if ( ! $part ) {
			return new \WP_Error( 'aktheme_no_template_part', __( 'There is no template part with that slug.', 'aktheme' ), [ 'status' => 404 ] );
		}

		return array_merge(
			$this->summary( $part ),
			[
				'content' => $this->expand_patterns( $part->content ),
				'raw'     => $part->content,
			]
		);
	}
}
