<?php
/**
 * Ability: go back to the theme's version of a template part.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Templates;

use AK\Theme\Modules\Abilities\Ability;

/**
 * Class ResetTemplatePart
 *
 * `aktheme/reset-template-part`: remove this site's changes to a part the theme ships, so the theme's version shows
 * again (Site Editor: "Reset"). A part only this site has is never deleted here.
 *
 * @since 1.3.0
 */
class ResetTemplatePart extends Ability {

	use TemplateParts;

	/**
	 * {@inheritDoc}
	 */
	protected function slug(): string {
		return 'reset-template-part';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function destructive(): bool {
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function idempotent(): bool {
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function label(): string {
		return __( 'Reset template part', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function description(): string {
		return __( 'Removes this site\'s changes to a template part the theme ships (e.g. the header), so the theme\'s version shows again. The changes are deleted, not kept as a revision: read them with aktheme/get-template-part first if they may be needed.', 'aktheme' );
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
		return $this->changed_schema();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $input Input.
	 */
	public function execute( mixed $input ): mixed {
		$slug = (string) ( is_array( $input ) ? ( $input['slug'] ?? '' ) : '' );
		$part = $this->part( $slug );

		if ( ! $part || ! $part->has_theme_file ) {
			return new \WP_Error( 'aktheme_not_a_theme_part', __( 'Only a template part the theme ships can be reset.', 'aktheme' ), [ 'status' => 400 ] );
		}

		if ( 'custom' !== $part->source ) {
			return [
				'id'      => $this->part_id( $slug ),
				'message' => __( 'The template part already is the theme\'s version.', 'aktheme' ),
			];
		}

		$deleted = $this->rest( 'DELETE', '/wp/v2/template-parts/' . $this->part_id( $slug ), [ 'force' => true ] );

		if ( is_wp_error( $deleted ) ) {
			return $deleted;
		}

		return [
			'id'      => $this->part_id( $slug ),
			'message' => __( 'Template part reset to the theme\'s version.', 'aktheme' ),
		];
	}
}
