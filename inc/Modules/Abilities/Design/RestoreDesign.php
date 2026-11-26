<?php
/**
 * Ability: undo a design change by restoring a Global Styles revision.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Design;

use AK\Theme\Modules\Abilities\Ability;

/**
 * Class RestoreDesign
 *
 * `aktheme/restore-site-design`: put back the design of an earlier Global Styles revision; without a revision ID, the
 * one before the current design (undo the last change). Restoring is itself a new revision, so it can be undone too.
 *
 * @since 1.3.0
 */
class RestoreDesign extends Ability {

	use GlobalStyles;

	/**
	 * {@inheritDoc}
	 */
	protected function slug(): string {
		return 'restore-site-design';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function label(): string {
		return __( 'Restore site design', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function description(): string {
		return __( 'Undoes design changes: restores the global styles of a Global Styles revision (IDs from aktheme/get-site-design), or, without revision_id, of the revision before the current design. The restore is saved as a new revision.', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function input_schema(): array {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => [
				'revision_id' => [
					'type'    => 'integer',
					'minimum' => 1,
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
	 *
	 * @param mixed $input Input.
	 */
	public function execute( mixed $input ): mixed {
		$wanted    = (int) ( is_array( $input ) ? ( $input['revision_id'] ?? 0 ) : 0 );
		$revisions = $this->rest(
			'GET',
			'/wp/v2/global-styles/' . $this->global_styles_id() . '/revisions',
			[
				'per_page' => 100,
				'context'  => 'edit',
			]
		);

		if ( is_wp_error( $revisions ) ) {
			return $revisions;
		}

		$revisions = array_values( $this->to_array( $revisions ) );
		$revision  = null;

		if ( $wanted ) {
			foreach ( $revisions as $item ) {
				if ( (int) ( $item['id'] ?? 0 ) === $wanted ) {
					$revision = $item;
					break;
				}
			}
		} else {
			// The newest revision is the current design; the one after it in the list is the design before.
			$revision = $revisions[1] ?? null;
		}

		if ( ! is_array( $revision ) ) {
			return new \WP_Error( 'aktheme_no_revision', __( 'There is no such design revision to restore.', 'aktheme' ), [ 'status' => 404 ] );
		}

		$saved = $this->save_user_layer( $this->to_array( $revision['settings'] ?? [] ), $this->to_array( $revision['styles'] ?? [] ) );

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return [
			'id'      => (int) $revision['id'],
			'message' => __( 'Design restored from the revision; the restore is a new revision.', 'aktheme' ),
		];
	}
}
