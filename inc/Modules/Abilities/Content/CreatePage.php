<?php
/**
 * Ability: create a page from a theme pattern or from blocks.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Content;

use AK\Theme\Modules\Abilities\Ability;
use WP_Block_Patterns_Registry;

/**
 * Class CreatePage
 *
 * `aktheme/create-page`: a new page, as a draft unless asked otherwise, filled with one of the theme's page patterns
 * or with given block markup. Created through the core `/wp/v2/pages` route, so publishing needs `publish_pages`.
 *
 * @since 1.3.0
 */
class CreatePage extends Ability {

	/**
	 * {@inheritDoc}
	 */
	protected function slug(): string {
		return 'create-page';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function capability(): string {
		return 'edit_pages';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function label(): string {
		return __( 'Create page', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function description(): string {
		return __( 'Creates a page, as a draft by default, from a theme page pattern ("pattern": a slug from aktheme/list-building-blocks) or from block markup ("content"). With both, the content is used. Returns the new page ID and its edit link.', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function input_schema(): array {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => [ 'title' ],
			'properties'           => [
				'title'   => [
					'type'      => 'string',
					'minLength' => 1,
					'maxLength' => 200,
				],
				'pattern' => [
					'type'    => 'string',
					'pattern' => '^aktheme/[a-z0-9-]+$',
				],
				'content' => [
					'type'      => 'string',
					'maxLength' => 1000000,
				],
				'status'  => [
					'type'    => 'string',
					'enum'    => [ 'draft', 'pending', 'publish' ],
					'default' => 'draft',
				],
				'parent'  => [
					'type'    => 'integer',
					'minimum' => 0,
				],
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function output_schema(): array {
		$schema                            = $this->changed_schema();
		$schema['properties']['edit_link'] = [ 'type' => 'string' ];
		$schema['properties']['link']      = [ 'type' => 'string' ];

		return $schema;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $input Input.
	 */
	public function execute( mixed $input ): mixed {
		$input   = is_array( $input ) ? $input : [];
		$content = (string) ( $input['content'] ?? '' );

		if ( '' === trim( $content ) && ! empty( $input['pattern'] ) ) {
			$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( (string) $input['pattern'] );

			if ( ! is_array( $pattern ) || empty( $pattern['content'] ) ) {
				return new \WP_Error( 'aktheme_no_pattern', __( 'There is no theme pattern with that slug.', 'aktheme' ), [ 'status' => 404 ] );
			}

			$content = (string) $pattern['content'];
		}

		if ( '' !== trim( $content ) ) {
			$checked = $this->check_blocks( $content );

			if ( is_wp_error( $checked ) ) {
				return $checked;
			}
		}

		$page = $this->rest(
			'POST',
			'/wp/v2/pages',
			[
				'title'   => sanitize_text_field( (string) ( $input['title'] ?? '' ) ),
				'content' => $content,
				'status'  => (string) ( $input['status'] ?? 'draft' ),
				'parent'  => (int) ( $input['parent'] ?? 0 ),
			]
		);

		if ( is_wp_error( $page ) ) {
			return $page;
		}

		$id = (int) ( (array) $page )['id'];

		return [
			'id'        => $id,
			'message'   => __( 'Page created.', 'aktheme' ),
			'edit_link' => (string) get_edit_post_link( $id, 'raw' ),
			'link'      => (string) get_permalink( $id ),
		];
	}
}
