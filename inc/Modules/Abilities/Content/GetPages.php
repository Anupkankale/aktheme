<?php
/**
 * Ability: find pages, or read one page's blocks.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Content;

use AK\Theme\Modules\Abilities\Ability;

/**
 * Class GetPages
 *
 * `aktheme/get-pages`: without an ID, the pages (optionally matching a search); with an ID, that page and its block
 * markup, for aktheme/update-page. Reads through the core `/wp/v2/pages` route, so it shows what the caller may edit.
 *
 * @since 1.3.0
 */
class GetPages extends Ability {

	/**
	 * {@inheritDoc}
	 */
	protected function slug(): string {
		return 'get-pages';
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
		return __( 'Get pages', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function description(): string {
		return __( 'Without "id": lists pages (id, title, status, link), optionally those matching "search". With "id": returns that page with its block markup ("content"), to edit and pass to aktheme/update-page.', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function input_schema(): array {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => [
				'id'     => [
					'type'    => 'integer',
					'minimum' => 1,
				],
				'search' => [
					'type'      => 'string',
					'maxLength' => 100,
				],
			],
		];
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
					'id'      => [ 'type' => 'integer' ],
					'title'   => [ 'type' => 'string' ],
					'status'  => [ 'type' => 'string' ],
					'link'    => [ 'type' => 'string' ],
					'content' => [ 'type' => 'string' ],
				],
			],
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $input Input.
	 */
	public function execute( mixed $input ): mixed {
		$input = is_array( $input ) ? $input : [];
		$id    = (int) ( $input['id'] ?? 0 );

		if ( $id ) {
			$page = $this->rest( 'GET', '/wp/v2/pages/' . $id, [ 'context' => 'edit' ] );

			return is_wp_error( $page ) ? $page : [ $this->page( (array) $page, true ) ];
		}

		$pages = $this->rest(
			'GET',
			'/wp/v2/pages',
			array_filter(
				[
					'context'  => 'edit',
					'per_page' => 100,
					'status'   => [ 'publish', 'draft', 'pending', 'private', 'future' ],
					'search'   => (string) ( $input['search'] ?? '' ),
					'orderby'  => 'title',
					'order'    => 'asc',
				]
			)
		);

		if ( is_wp_error( $pages ) ) {
			return $pages;
		}

		return array_values( array_map( fn ( $page ) => $this->page( (array) $page, false ), (array) $pages ) );
	}

	/**
	 * What the ability returns about a page.
	 *
	 * @param array<string, mixed> $page         REST page data.
	 * @param bool                 $with_content Include the block markup.
	 *
	 * @return array<string, mixed> Page.
	 */
	private function page( array $page, bool $with_content ): array {
		$out = [
			'id'     => (int) ( $page['id'] ?? 0 ),
			'title'  => (string) ( $page['title']['raw'] ?? '' ),
			'status' => (string) ( $page['status'] ?? '' ),
			'link'   => (string) ( $page['link'] ?? '' ),
		];

		if ( $with_content ) {
			$out['content'] = (string) ( $page['content']['raw'] ?? '' );
		}

		return $out;
	}
}
