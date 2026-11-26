<?php
/**
 * Ability: change a template part's blocks.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Templates;

use AK\Theme\Modules\Abilities\Ability;

/**
 * Class UpdateTemplatePart
 *
 * `aktheme/update-template-part`: save new block markup for the header, footer or another part, through the core
 * template parts REST route (as the Site Editor saves it: a `wp_template_part` post with revisions, kses for users
 * without `unfiltered_html`). A slug the theme doesn't have creates a new part in the given area.
 *
 * @since 1.3.0
 */
class UpdateTemplatePart extends Ability {

	use TemplateParts;

	/**
	 * {@inheritDoc}
	 */
	protected function slug(): string {
		return 'update-template-part';
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
		return __( 'Update template part', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function description(): string {
		return __( 'Replaces the block markup of a template part (e.g. the header or footer) with "content": complete block markup, usually the "content" of aktheme/get-template-part with your changes. Saved like a Site Editor change (with revisions); aktheme/reset-template-part goes back to the theme\'s version. A new slug creates a part in "area".', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function input_schema(): array {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => [ 'slug', 'content' ],
			'properties'           => [
				'slug'    => $this->slug_schema(),
				'content' => [
					'type'      => 'string',
					'minLength' => 1,
					'maxLength' => 500000,
				],
				'title'   => [
					'type'      => 'string',
					'maxLength' => 80,
				],
				'area'    => [
					'type'    => 'string',
					'enum'    => [ 'header', 'footer', 'uncategorized' ],
					'default' => 'uncategorized',
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
	 *
	 * @param mixed $input Input.
	 */
	public function execute( mixed $input ): mixed {
		$input   = is_array( $input ) ? $input : [];
		$slug    = (string) ( $input['slug'] ?? '' );
		$content = (string) ( $input['content'] ?? '' );
		$checked = $this->check_blocks( $content );

		if ( is_wp_error( $checked ) ) {
			return $checked;
		}

		$params = [ 'content' => $content ];

		if ( isset( $input['title'] ) ) {
			$params['title'] = sanitize_text_field( (string) $input['title'] );
		}

		if ( $this->part( $slug ) ) {
			$saved = $this->rest( 'POST', '/wp/v2/template-parts/' . $this->part_id( $slug ), $params );
		} else {
			$saved = $this->rest(
				'POST',
				'/wp/v2/template-parts',
				$params + [
					'slug'  => $slug,
					'title' => $params['title'] ?? ucwords( str_replace( '-', ' ', $slug ) ),
					'area'  => (string) ( $input['area'] ?? 'uncategorized' ),
				]
			);
		}

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return [
			'id'      => $this->part_id( $slug ),
			'message' => __( 'Template part saved.', 'aktheme' ),
		];
	}
}
