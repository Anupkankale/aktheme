<?php
/**
 * Ability: change a shared section everywhere it shows.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Content;

use AK\Theme\Modules\Abilities\Ability;
use AK\Theme\Modules\Patterns\SyncedSections;

/**
 * Class UpdateSection
 *
 * `aktheme/update-section`: new blocks for a shared section (offer banner, testimonials, closing call to action, ...).
 * It is a synced pattern, so every page that shows it changes. Saved through the core `/wp/v2/blocks` route, with
 * revisions.
 *
 * @since 1.3.0
 */
class UpdateSection extends Ability {

	/**
	 * {@inheritDoc}
	 */
	protected function slug(): string {
		return 'update-section';
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
		return __( 'Update shared section', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function description(): string {
		return __( 'Replaces the blocks of a shared section (keys from aktheme/list-building-blocks). The change shows on every page that uses the section. Returns the content saved before, so the change can be reverted.', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function input_schema(): array {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => [ 'key', 'content' ],
			'properties'           => [
				'key'     => [
					'type' => 'string',
					'enum' => array_map( 'strval', array_keys( SyncedSections::SECTIONS ) ),
				],
				'content' => [
					'type'      => 'string',
					'minLength' => 1,
					'maxLength' => 500000,
				],
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function output_schema(): array {
		$schema                                   = $this->changed_schema();
		$schema['properties']['previous_content'] = [ 'type' => 'string' ];

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
		$id      = SyncedSections::id( (string) ( $input['key'] ?? '' ) );
		$checked = $this->check_blocks( $content );

		if ( is_wp_error( $checked ) ) {
			return $checked;
		}

		if ( ! $id ) {
			return new \WP_Error( 'aktheme_no_section', __( 'That shared section does not exist on this site (yet): an administrator opening wp-admin creates it.', 'aktheme' ), [ 'status' => 404 ] );
		}

		$section  = get_post( $id );
		$previous = $section ? $section->post_content : '';
		$saved    = $this->rest( 'POST', '/wp/v2/blocks/' . $id, [ 'content' => $content ] );

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return [
			'id'               => $id,
			'message'          => __( 'Shared section saved; it changed on every page that shows it.', 'aktheme' ),
			'previous_content' => $previous,
		];
	}
}
