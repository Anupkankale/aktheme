<?php
/**
 * Ability: list the menu locations and their links.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Menus;

use AK\Theme\Modules\Abilities\Ability;

/**
 * Class ListMenus
 *
 * `aktheme/list-menus`: every menu location (header, footer columns, blog category bar), the menu assigned to it and
 * its links as a tree. The header and footer print these menus (MenuRenderer).
 *
 * @since 1.3.0
 */
class ListMenus extends Ability {

	/**
	 * {@inheritDoc}
	 */
	protected function slug(): string {
		return 'list-menus';
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
		return __( 'List menus', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function description(): string {
		return __( 'Lists the menu locations (header menu, footer columns, blog category bar), the menu assigned to each and its links (title, url, children). The header and footer show these links; change them with aktheme/update-menu.', 'aktheme' );
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
					'location' => [ 'type' => 'string' ],
					'label'    => [ 'type' => 'string' ],
					'menu_id'  => [ 'type' => 'integer' ],
					'items'    => [ 'type' => 'array' ],
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

		$assigned = get_nav_menu_locations();
		$out      = [];

		foreach ( get_registered_nav_menus() as $location => $label ) {
			$menu_id = (int) ( $assigned[ $location ] ?? 0 );
			$items   = $menu_id ? wp_get_nav_menu_items( $menu_id ) : [];

			$out[] = [
				'location' => (string) $location,
				'label'    => (string) $label,
				'menu_id'  => $menu_id,
				'items'    => $this->tree( is_array( $items ) ? $items : [], 0 ),
			];
		}

		return $out;
	}

	/**
	 * Menu items under a parent, with their children.
	 *
	 * @param array<int, object> $items     Menu items.
	 * @param int                $parent_id Parent item ID.
	 *
	 * @return list<array<string, mixed>> Items.
	 */
	private function tree( array $items, int $parent_id ): array {
		$out = [];

		foreach ( $items as $item ) {
			if ( (int) ( $item->menu_item_parent ?? 0 ) !== $parent_id ) {
				continue;
			}

			$out[] = [
				'title'    => (string) ( $item->title ?? '' ),
				'url'      => (string) ( $item->url ?? '' ),
				'classes'  => implode( ' ', array_filter( (array) ( $item->classes ?? [] ) ) ),
				'children' => $this->tree( $items, (int) ( $item->ID ?? 0 ) ),
			];
		}

		return $out;
	}
}
