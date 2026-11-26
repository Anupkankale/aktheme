<?php
/**
 * Ability: replace the links of a menu location.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Menus;

use AK\Theme\Modules\Abilities\Ability;

/**
 * Class UpdateMenu
 *
 * `aktheme/update-menu`: give a menu location (header menu, a footer column, ...) a new list of links, two levels
 * deep. The menu assigned to the location is emptied and refilled; a location without a menu gets a new one.
 *
 * @since 1.3.0
 */
class UpdateMenu extends Ability {

	/**
	 * Most links in one menu.
	 */
	private const MAX_ITEMS = 60;

	/**
	 * {@inheritDoc}
	 */
	protected function slug(): string {
		return 'update-menu';
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
		return __( 'Update menu', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function description(): string {
		return __( 'Replaces all links of a menu location (see aktheme/list-menus) with "items": each has a title and a url (a full address, or a path such as "/contact/" on this site) and may have "children" (one level). The old links are deleted.', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function input_schema(): array {
		$item = [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => [ 'title', 'url' ],
			'properties'           => [
				'title'   => [
					'type'      => 'string',
					'minLength' => 1,
					'maxLength' => 100,
				],
				'url'     => [
					'type'      => 'string',
					'minLength' => 1,
					'maxLength' => 2000,
				],
				'classes' => [
					'type'    => 'string',
					'pattern' => '^[A-Za-z0-9_ -]*$',
				],
			],
		];

		$parent                           = $item;
		$parent['properties']['children'] = [
			'type'     => 'array',
			'maxItems' => self::MAX_ITEMS,
			'items'    => $item,
		];

		$location = [ 'type' => 'string' ];
		$names    = array_keys( get_registered_nav_menus() );

		if ( [] !== $names ) {
			$location['enum'] = array_map( 'strval', $names );
		}

		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => [ 'location', 'items' ],
			'properties'           => [
				'location' => $location,
				'items'    => [
					'type'     => 'array',
					'maxItems' => self::MAX_ITEMS,
					'items'    => $parent,
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
		$input     = is_array( $input ) ? $input : [];
		$location  = (string) ( $input['location'] ?? '' );
		$locations = get_registered_nav_menus();

		if ( ! isset( $locations[ $location ] ) ) {
			return new \WP_Error( 'aktheme_no_menu_location', __( 'There is no menu location with that name.', 'aktheme' ), [ 'status' => 404 ] );
		}

		$assigned = get_nav_menu_locations();
		$menu_id  = (int) ( $assigned[ $location ] ?? 0 );

		if ( ! $menu_id || ! is_nav_menu( $menu_id ) ) {
			$menu_id = wp_create_nav_menu( (string) $locations[ $location ] );

			if ( is_wp_error( $menu_id ) ) {
				return $menu_id;
			}

			$assigned[ $location ] = (int) $menu_id;
			set_theme_mod( 'nav_menu_locations', $assigned );
		}

		$old = wp_get_nav_menu_items( (int) $menu_id, [ 'post_status' => 'any' ] );

		foreach ( is_array( $old ) ? $old : [] as $item ) {
			wp_delete_post( (int) $item->ID, true );
		}

		$count = $this->add( (int) $menu_id, (array) ( $input['items'] ?? [] ), 0 );

		return [
			'id'      => (int) $menu_id,
			/* translators: %d: number of links. */
			'message' => sprintf( _n( 'Menu saved with %d link.', 'Menu saved with %d links.', $count, 'aktheme' ), $count ),
		];
	}

	/**
	 * Add links (and their children) to a menu.
	 *
	 * @param int                              $menu_id   Menu ID.
	 * @param array<int, array<string, mixed>> $items     Links.
	 * @param int                              $parent_id Parent item ID.
	 *
	 * @return int Links added.
	 */
	private function add( int $menu_id, array $items, int $parent_id ): int {
		$count = 0;

		foreach ( $items as $item ) {
			$url = (string) ( $item['url'] ?? '' );
			$url = esc_url_raw( str_starts_with( $url, '/' ) && ! str_starts_with( $url, '//' ) ? home_url( $url ) : $url );

			if ( '' === $url ) {
				continue;
			}

			$id = wp_update_nav_menu_item(
				$menu_id,
				0,
				[
					'menu-item-title'     => sanitize_text_field( (string) ( $item['title'] ?? '' ) ),
					'menu-item-url'       => $url,
					'menu-item-type'      => 'custom',
					'menu-item-status'    => 'publish',
					'menu-item-parent-id' => $parent_id,
					'menu-item-classes'   => sanitize_text_field( (string) ( $item['classes'] ?? '' ) ),
				]
			);

			if ( is_wp_error( $id ) ) {
				continue;
			}

			++$count;

			if ( ! empty( $item['children'] ) && 0 === $parent_id ) {
				$count += $this->add( $menu_id, (array) $item['children'], (int) $id );
			}
		}

		return $count;
	}
}
