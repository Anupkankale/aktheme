<?php
/**
 * Ability: change a page's title, blocks or status.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Content;

use AK\Theme\Modules\Abilities\Ability;

/**
 * Class UpdatePage
 *
 * `aktheme/update-page`: save a page's new title, block markup or status through the core `/wp/v2/pages` route (the
 * caller must be allowed to edit that page; every save is a revision in the editor's Revisions panel).
 *
 * @since 1.3.0
 */
class UpdatePage extends Ability {

	/**
	 * {@inheritDoc}
	 */
	protected function slug(): string {
		return 'update-page';
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
		return __( 'Update page', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function description(): string {
		return __( 'Changes a page: "title", "content" (complete block markup, usually from aktheme/get-pages with your changes) and/or "status". The previous version stays in the page\'s revisions.', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function input_schema(): array {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => [ 'id' ],
			'properties'           => [
				'id'      => [
					'type'    => 'integer',
					'minimum' => 1,
				],
				'title'   => [
					'type'      => 'string',
					'minLength' => 1,
					'maxLength' => 200,
				],
				'content' => [
					'type'      => 'string',
					'minLength' => 1,
					'maxLength' => 1000000,
				],
				'status'  => [
					'type' => 'string',
					'enum' => [ 'draft', 'pending', 'publish', 'private' ],
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
		$input  = is_array( $input ) ? $input : [];
		$id     = (int) ( $input['id'] ?? 0 );
		$params = [];

		if ( 'page' !== get_post_type( $id ) ) {
			return new \WP_Error( 'aktheme_no_page', __( 'There is no page with that ID.', 'aktheme' ), [ 'status' => 404 ] );
		}

		if ( isset( $input['content'] ) ) {
			$checked = $this->check_blocks( (string) $input['content'] );

			if ( is_wp_error( $checked ) ) {
				return $checked;
			}

			$params['content'] = (string) $input['content'];
		}

		if ( isset( $input['title'] ) ) {
			$params['title'] = sanitize_text_field( (string) $input['title'] );
		}

		if ( isset( $input['status'] ) ) {
			$params['status'] = (string) $input['status'];
		}

		if ( [] === $params ) {
			return new \WP_Error( 'aktheme_nothing_to_change', __( 'Give title, content or status.', 'aktheme' ), [ 'status' => 400 ] );
		}

		$saved = $this->rest( 'POST', '/wp/v2/pages/' . $id, $params );

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return [
			'id'      => $id,
			'message' => __( 'Page saved; the previous version is in its revisions.', 'aktheme' ),
		];
	}
}
