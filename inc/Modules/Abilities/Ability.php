<?php
/**
 * Base class of the theme's abilities.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities;

use WP_REST_Request;
use rtCamp\WPPrimitives\Contracts\Abstracts\AbstractAbility;

/**
 * Class Ability
 *
 * What every AK ability shares:
 * - The `aktheme` category and the `aktheme/` name prefix.
 * - REST and MCP exposure. Every ability is listed under `/wp-abilities/v1` and, with the MCP Adapter plugin, on its
 *   default server (`meta.mcp.public`). Listing is not running: each ability still checks the caller's capability, and
 *   the `aktheme_abilities_mcp_public` filter can hide any of them from MCP.
 * - Annotations that tell an agent what a call does: read-only, destructive (replaces or deletes something) and
 *   idempotent (calling it twice with the same input changes nothing more).
 * - rest(): changes go through the core REST controllers where one exists (global styles, template parts, synced
 *   patterns, pages), so an ability gets the same permission checks, sanitising (kses for users without
 *   `unfiltered_html`) and revisions as the Site Editor. Nothing an ability does writes to the theme's files.
 *
 * @since 1.3.0
 */
abstract class Ability extends AbstractAbility {

	/**
	 * Category slug.
	 *
	 * @var string
	 */
	public const CATEGORY = 'aktheme';

	/**
	 * The ability slug after `aktheme/`.
	 *
	 * @return string Slug.
	 */
	abstract protected function slug(): string;

	/**
	 * Whether the ability only reads.
	 */
	protected function is_readonly(): bool {
		return false;
	}

	/**
	 * Whether the ability replaces or deletes something (a revision may still keep the old version).
	 */
	protected function destructive(): bool {
		return false;
	}

	/**
	 * Whether a second call with the same input changes nothing more.
	 */
	protected function idempotent(): bool {
		return $this->is_readonly();
	}

	/**
	 * Capability the caller needs.
	 *
	 * @return string Capability.
	 */
	protected function capability(): string {
		return 'edit_theme_options';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return lowercase-string&non-falsy-string Ability name.
	 */
	public function name(): string {
		/**
		 * The prefixed name.
		 *
		 * @var lowercase-string&non-falsy-string $name
		 */
		$name = 'aktheme/' . $this->slug();

		return $name;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function category(): string {
		return self::CATEGORY;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $input Input.
	 */
	protected function permission( mixed $input = null ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Part of the parent signature.
		return current_user_can( $this->capability() ); // phpcs:ignore WordPress.WP.Capabilities.Undetermined -- Each ability names a core capability in capability().
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array<string, mixed> Meta.
	 */
	protected function meta(): array {
		/**
		 * Filters whether an AK ability is offered on the MCP Adapter's default server. It still checks capabilities
		 * when it runs; return false to hide it from AI agents connected over MCP, e.g. every ability that changes
		 * something on a production site.
		 *
		 * @since 1.3.0
		 *
		 * @param bool   $public   Whether to offer it (default true).
		 * @param string $name     Ability name.
		 * @param bool   $is_readonly Whether the ability only reads.
		 */
		$public = (bool) apply_filters( 'aktheme_abilities_mcp_public', true, $this->name(), $this->is_readonly() );

		return [
			'show_in_rest' => true,
			'annotations'  => [
				'readonly'    => $this->is_readonly(),
				'destructive' => $this->destructive(),
				'idempotent'  => $this->idempotent(),
			],
			'mcp'          => [ 'public' => $public ],
		];
	}

	/**
	 * Run a core REST route as the current user.
	 *
	 * @param string               $method HTTP method.
	 * @param string               $route  Route, e.g. `/wp/v2/pages/12`.
	 * @param array<string, mixed> $params Body (or query, for GET and DELETE) parameters.
	 *
	 * @return mixed|\WP_Error The response data, or the error the route returned.
	 */
	protected function rest( string $method, string $route, array $params = [] ): mixed {
		$request = new WP_REST_Request( $method, $route );

		if ( in_array( $method, [ 'GET', 'DELETE' ], true ) ) {
			$request->set_query_params( $params );
		} else {
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( (string) wp_json_encode( $params ) );
		}

		$response = rest_do_request( $request );
		$server   = rest_get_server();

		if ( $response->is_error() ) {
			return $response->as_error();
		}

		return $server->response_to_data( $response, false );
	}

	/**
	 * Reject block markup that holds no blocks (plain HTML would be wrapped in a Classic block the Site Editor can't edit).
	 *
	 * @param string $content Block markup.
	 *
	 * @return true|\WP_Error True when it holds blocks.
	 */
	protected function check_blocks( string $content ): bool|\WP_Error {
		if ( '' === trim( $content ) || ! has_blocks( $content ) ) {
			return new \WP_Error(
				'aktheme_no_blocks',
				__( 'The content must be block markup (<!-- wp:... --> comments), as the editor saves it.', 'aktheme' ),
				[ 'status' => 400 ]
			);
		}

		return true;
	}

	/**
	 * Replace Pattern blocks that point at registered patterns with the patterns' markup, so the caller sees (and can
	 * edit) the blocks a visitor sees. Nested references are expanded up to three levels.
	 *
	 * @param string $content Block markup.
	 * @param int    $depth   Levels left.
	 *
	 * @return string Block markup.
	 */
	protected function expand_patterns( string $content, int $depth = 3 ): string {
		if ( $depth < 1 || ! str_contains( $content, 'wp:pattern' ) ) {
			return $content;
		}

		$registry = \WP_Block_Patterns_Registry::get_instance();
		$out      = '';

		foreach ( parse_blocks( $content ) as $block ) {
			$slug    = (string) ( $block['attrs']['slug'] ?? '' );
			$pattern = 'core/pattern' === $block['blockName'] && '' !== $slug ? $registry->get_registered( $slug ) : null;

			$out .= is_array( $pattern ) && isset( $pattern['content'] )
				? $this->expand_patterns( (string) $pattern['content'], $depth - 1 )
				: serialize_block( $block );
		}

		return $out;
	}

	/**
	 * The shared output schema of an ability that changed one post: its ID and a short message.
	 *
	 * @return array<string, mixed> Schema.
	 */
	protected function changed_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'id'      => [ 'type' => [ 'integer', 'string' ] ],
				'message' => [ 'type' => 'string' ],
			],
		];
	}
}
