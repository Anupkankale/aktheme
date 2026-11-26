<?php
/**
 * Ability: list what pages are built from.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Content;

use AK\Theme\Modules\Abilities\Ability;
use AK\Theme\Modules\Patterns\SyncedSections;
use WP_Block_Patterns_Registry;
use WP_Block_Styles_Registry;
use WP_Block_Type_Registry;

/**
 * Class ListBuildingBlocks
 *
 * `aktheme/list-building-blocks`: the theme's page and section patterns, its shared (synced) sections, its `ak/*`
 * section blocks and the block style variations, so an agent composes pages from the design system instead of
 * inventing markup and inline styles.
 *
 * @since 1.3.0
 */
class ListBuildingBlocks extends Ability {

	/**
	 * {@inheritDoc}
	 */
	protected function slug(): string {
		return 'list-building-blocks';
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
	protected function capability(): string {
		return 'edit_pages';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function label(): string {
		return __( 'List building blocks', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function description(): string {
		return __( 'Lists what pages are built from in this theme: page and section patterns (slug, title, categories; use a page pattern with aktheme/create-page), shared sections that show the same content on every page (key, synced pattern ID; change one with aktheme/update-section, place one with <!-- wp:block {"ref":ID} /-->), the AK section blocks, and block style variations (apply one with the class "is-style-<name>"). Prefer these over new markup or inline styles.', 'aktheme' );
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
		$list = [
			'type'  => 'array',
			'items' => [ 'type' => 'object' ],
		];

		return [
			'type'       => 'object',
			'properties' => [
				'patterns'        => $list,
				'shared_sections' => $list,
				'blocks'          => $list,
				'block_styles'    => [ 'type' => 'object' ],
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

		$patterns = [];

		foreach ( WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern ) {
			if ( ! str_starts_with( (string) $pattern['name'], 'aktheme/' ) || false === ( $pattern['inserter'] ?? true ) ) {
				continue;
			}

			$patterns[] = [
				'slug'        => (string) $pattern['name'],
				'title'       => (string) ( $pattern['title'] ?? '' ),
				'description' => (string) ( $pattern['description'] ?? '' ),
				'categories'  => array_values( (array) ( $pattern['categories'] ?? [] ) ),
			];
		}

		$sections = [];

		foreach ( SyncedSections::SECTIONS as $key => $section ) {
			$sections[] = [
				'key'   => (string) $key,
				'title' => (string) $section['title'],
				'id'    => SyncedSections::id( (string) $key ),
			];
		}

		$blocks = [];

		foreach ( WP_Block_Type_Registry::get_instance()->get_all_registered() as $name => $type ) {
			if ( str_starts_with( (string) $name, 'ak/' ) ) {
				$blocks[] = [
					'name'        => (string) $name,
					'title'       => (string) $type->title,
					'description' => (string) $type->description,
				];
			}
		}

		$styles = [];

		foreach ( WP_Block_Styles_Registry::get_instance()->get_all_registered() as $block_name => $variations ) {
			$names = array_values( array_diff( array_keys( (array) $variations ), [ 'default' ] ) );

			if ( [] !== $names ) {
				$styles[ (string) $block_name ] = $names;
			}
		}

		return [
			'patterns'        => $patterns,
			'shared_sections' => $sections,
			'blocks'          => $blocks,
			'block_styles'    => (object) $styles,
		];
	}
}
