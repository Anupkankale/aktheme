<?php
/**
 * Built-in MCP server: the theme's abilities as MCP tools, no plugin needed.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\AI;

use AK\Theme\Core\Settings;
use WP_REST_Request;
use WP_REST_Response;
use rtCamp\WPPrimitives\Contracts\Interfaces\Registrable;

/**
 * Class McpServer
 *
 * A Model Context Protocol server at `/wp-json/aktheme/v1/mcp` (Streamable HTTP transport, JSON responses, stateless).
 * Claude Code, Claude Desktop or any MCP client connects with a WordPress application password:
 *
 *     claude mcp add --transport http aktheme https://example.com/wp-json/aktheme/v1/mcp \
 *       --header "Authorization: Basic <base64 of username:application-password>"
 *
 * Every `aktheme/*` ability with `meta.mcp.public` becomes a tool (`aktheme/update-page` -> `aktheme__update-page`).
 * Calling a tool runs WP_Ability::execute(), which validates the input against the ability's schema and checks the
 * user's capability, so an Editor's password can change pages but not the design.
 *
 * Security:
 * - Only logged-in requests reach it. Over REST, a browser cookie counts only with a REST nonce, so a web page cannot
 *   drive it through a visitor's session (no CSRF); agents use application passwords (HTTPS, or a local site).
 * - Turn it off with the `mcp_enabled` setting or the `aktheme_mcp_enabled` filter; limit it to read-only tools with the
 *   `aktheme_abilities_mcp_public` filter (see docs/abilities.md).
 *
 * @since 1.3.0
 */
final class McpServer implements Registrable {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	public const NAMESPACE = 'aktheme/v1';

	/**
	 * REST route.
	 *
	 * @var string
	 */
	public const ROUTE = '/mcp';

	/**
	 * Protocol versions this server speaks, newest first.
	 *
	 * @var list<string>
	 */
	public const PROTOCOL_VERSIONS = [ '2025-11-25', '2025-06-18', '2025-03-26' ];

	/**
	 * Separator replacing `/` in tool names (MCP tool names allow letters, digits, `_`, `-` and `.`).
	 *
	 * @var string
	 */
	public const NAME_SEPARATOR = '__';

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Whether the endpoint is on.
	 *
	 * @since 1.3.0
	 */
	public static function enabled(): bool {
		/**
		 * Filters whether the theme's MCP endpoint is served.
		 *
		 * @since 1.3.0
		 *
		 * @param bool $enabled Default: the `mcp_enabled` theme setting.
		 */
		return (bool) apply_filters( 'aktheme_mcp_enabled', (bool) Settings::get( 'mcp_enabled' ) );
	}

	/**
	 * The endpoint URL.
	 *
	 * @since 1.3.0
	 */
	public static function url(): string {
		return rest_url( self::NAMESPACE . self::ROUTE );
	}

	/**
	 * Register the route.
	 *
	 * @since 1.3.0
	 */
	public function register_routes(): void {
		if ( ! self::enabled() ) {
			return;
		}

		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'handle' ],
					'permission_callback' => [ $this, 'permission' ],
				],
				[
					// Streamable HTTP: a server that offers no SSE stream answers GET with 405.
					'methods'             => 'GET, DELETE',
					'callback'            => static fn () => new WP_REST_Response( null, 405, [ 'Allow' => 'POST' ] ),
					'permission_callback' => '__return_true',
				],
			]
		);
	}

	/**
	 * Only logged-in users (application passwords) may talk to the server; each tool then checks its own capability.
	 *
	 * @since 1.3.0
	 */
	public function permission(): bool|\WP_Error {
		if ( is_user_logged_in() ) {
			return true;
		}

		return new \WP_Error(
			'aktheme_mcp_unauthorized',
			__( 'Authenticate with a WordPress application password (Users > Profile > Application Passwords).', 'aktheme' ),
			[ 'status' => 401 ]
		);
	}

	/**
	 * Handle one JSON-RPC message.
	 *
	 * @since 1.3.0
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response {
		$message = json_decode( $request->get_body(), true );

		if ( ! is_array( $message ) ) {
			return $this->error_response( null, -32700, 'Parse error: the body must be a JSON-RPC message.', 400 );
		}
		if ( array_is_list( $message ) ) {
			return $this->error_response( null, -32600, 'Batches are not supported: send one JSON-RPC message per request.', 400 );
		}

		$id     = $message['id'] ?? null;
		$method = $message['method'] ?? null;

		if ( '2.0' !== ( $message['jsonrpc'] ?? null ) || ! is_string( $method ) ) {
			// A response or a malformed message from the client: nothing to answer.
			if ( ! array_key_exists( 'method', $message ) && array_key_exists( 'id', $message ) ) {
				return new WP_REST_Response( null, 202 );
			}
			return $this->error_response( is_scalar( $id ) ? $id : null, -32600, 'Invalid request: expected a JSON-RPC 2.0 message with a method.', 400 );
		}

		// Notifications (no id) get no reply.
		if ( ! array_key_exists( 'id', $message ) ) {
			return new WP_REST_Response( null, 202 );
		}

		if ( ! is_string( $id ) && ! is_int( $id ) ) {
			return $this->error_response( null, -32600, 'Invalid request: id must be a string or an integer.', 400 );
		}

		$params = is_array( $message['params'] ?? null ) ? $message['params'] : [];

		$result = match ( $method ) {
			'initialize' => $this->initialize( $params ),
			'ping'       => new \stdClass(),
			'tools/list' => $this->tools_list(),
			'tools/call' => $this->tools_call( $params ),
			default      => new \WP_Error( 'method_not_found', sprintf( 'Method not found: %s', $method ), [ 'code' => -32601 ] ),
		};

		if ( is_wp_error( $result ) ) {
			$data = $result->get_error_data();
			$code = is_array( $data ) && isset( $data['code'] ) ? (int) $data['code'] : -32603;

			return $this->error_response( $id, $code, $result->get_error_message() );
		}

		return new WP_REST_Response(
			[
				'jsonrpc' => '2.0',
				'id'      => $id,
				'result'  => $result,
			]
		);
	}

	/**
	 * `initialize`.
	 *
	 * @param array<string, mixed> $params Params.
	 *
	 * @return array<string, mixed> Result.
	 */
	private function initialize( array $params ): array {
		$requested = is_string( $params['protocolVersion'] ?? null ) ? $params['protocolVersion'] : '';
		$version   = in_array( $requested, self::PROTOCOL_VERSIONS, true ) ? $requested : self::PROTOCOL_VERSIONS[0];

		return [
			'protocolVersion' => $version,
			'capabilities'    => [ 'tools' => [ 'listChanged' => false ] ],
			'serverInfo'      => [
				'name'    => 'aktheme',
				'title'   => sprintf( '%s (AK Theme)', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ),
				'version' => defined( 'AKTHEME_VERSION' ) ? (string) AKTHEME_VERSION : '1.3.0',
			],
			'instructions'    => sprintf(
				'This is the WordPress site %1$s (%2$s), built on the AK Theme block theme. Start with aktheme__get-site-design and aktheme__list-building-blocks to learn its design and sections. Changes are saved like Site Editor changes, with revisions. Before changing anything, describe the change and confirm it with the user.',
				wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
				home_url( '/' )
			),
		];
	}

	/**
	 * The theme abilities offered as tools.
	 *
	 * @return array<string, \WP_Ability> Abilities by name.
	 */
	private function abilities(): array {
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return [];
		}

		$abilities = [];
		foreach ( wp_get_abilities() as $ability ) {
			$meta = $ability->get_meta();
			if ( str_starts_with( $ability->get_name(), 'aktheme/' ) && ! empty( $meta['mcp']['public'] ) ) {
				$abilities[ $ability->get_name() ] = $ability;
			}
		}

		/**
		 * Filters the abilities the theme's MCP server offers as tools, e.g. to add a client's own abilities from another
		 * namespace.
		 *
		 * @since 1.3.0
		 *
		 * @param array<string, \WP_Ability> $abilities Abilities by name.
		 */
		return (array) apply_filters( 'aktheme_mcp_abilities', $abilities );
	}

	/**
	 * `tools/list`.
	 *
	 * @return array<string, mixed>|\WP_Error Result.
	 */
	private function tools_list(): array|\WP_Error {
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return new \WP_Error( 'abilities_unavailable', 'The Abilities API needs WordPress 6.9 or later.', [ 'code' => -32603 ] );
		}

		$tools = [];
		foreach ( $this->abilities() as $name => $ability ) {
			$annotations = (array) ( $ability->get_meta()['annotations'] ?? [] );
			$schema      = $ability->get_input_schema();

			$tools[] = [
				'name'        => self::tool_name( $name ),
				'title'       => $ability->get_label(),
				'description' => $ability->get_description(),
				'inputSchema' => self::object_schema( $schema ),
				'annotations' => [
					'title'           => $ability->get_label(),
					'readOnlyHint'    => ! empty( $annotations['readonly'] ),
					'destructiveHint' => ! empty( $annotations['destructive'] ),
					'idempotentHint'  => ! empty( $annotations['idempotent'] ),
					'openWorldHint'   => false,
				],
			];
		}

		return [ 'tools' => $tools ];
	}

	/**
	 * `tools/call`.
	 *
	 * @param array<string, mixed> $params Params.
	 *
	 * @return array<string, mixed>|\WP_Error Result, or a protocol error.
	 */
	private function tools_call( array $params ): array|\WP_Error {
		$tool = is_string( $params['name'] ?? null ) ? $params['name'] : '';
		$name = self::ability_name( $tool );

		$abilities = $this->abilities();
		if ( '' === $tool || ! isset( $abilities[ $name ] ) ) {
			return new \WP_Error( 'unknown_tool', sprintf( 'Unknown tool: %s', $tool ), [ 'code' => -32602 ] );
		}

		$ability   = $abilities[ $name ];
		$arguments = $params['arguments'] ?? null;
		if ( null !== $arguments && ! is_array( $arguments ) ) {
			return new \WP_Error( 'invalid_arguments', 'Tool arguments must be an object.', [ 'code' => -32602 ] );
		}

		// Abilities without an input schema take no input at all.
		$input  = [] === $ability->get_input_schema() ? null : ( $arguments ?? [] );
		$result = $ability->execute( $input );

		// Tool errors go back as results, so the agent can read them and correct itself.
		if ( is_wp_error( $result ) ) {
			return [
				'content' => [
					[
						'type' => 'text',
						'text' => $result->get_error_message(),
					],
				],
				'isError' => true,
			];
		}

		$response = [
			'content' => [
				[
					'type' => 'text',
					'text' => (string) wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
				],
			],
			'isError' => false,
		];

		if ( is_array( $result ) && ! array_is_list( $result ) ) {
			$response['structuredContent'] = $result;
		}

		return $response;
	}

	/**
	 * `aktheme/update-page` -> `aktheme__update-page`.
	 *
	 * @since 1.3.0
	 *
	 * @param string $ability_name Ability name.
	 */
	public static function tool_name( string $ability_name ): string {
		return str_replace( '/', self::NAME_SEPARATOR, $ability_name );
	}

	/**
	 * `aktheme__update-page` -> `aktheme/update-page`.
	 *
	 * @since 1.3.0
	 *
	 * @param string $tool_name Tool name.
	 */
	public static function ability_name( string $tool_name ): string {
		$position = strpos( $tool_name, self::NAME_SEPARATOR );

		return false === $position ? $tool_name : substr_replace( $tool_name, '/', $position, strlen( self::NAME_SEPARATOR ) );
	}

	/**
	 * MCP tools need an object input schema; empty `properties` must encode as `{}`, not `[]`.
	 *
	 * @param array<string, mixed> $schema JSON schema.
	 *
	 * @return array<string, mixed> Schema.
	 */
	private static function object_schema( array $schema ): array {
		if ( [] === $schema || ( $schema['type'] ?? 'object' ) !== 'object' ) {
			return [
				'type'       => 'object',
				'properties' => new \stdClass(),
			];
		}

		return self::fix_empty_objects( $schema );
	}

	/**
	 * Turn empty `properties` arrays into objects, recursively.
	 *
	 * @param array<mixed> $schema Schema.
	 *
	 * @return array<mixed> Schema.
	 */
	private static function fix_empty_objects( array $schema ): array {
		foreach ( $schema as $key => $value ) {
			if ( 'properties' === $key && [] === $value ) {
				$schema[ $key ] = new \stdClass();
			} elseif ( is_array( $value ) ) {
				$schema[ $key ] = self::fix_empty_objects( $value );
			}
		}

		return $schema;
	}

	/**
	 * A JSON-RPC error response.
	 *
	 * @param int|string|float|bool|null $id      Request ID.
	 * @param int                        $code    JSON-RPC error code.
	 * @param string                     $message Message.
	 * @param int                        $status  HTTP status.
	 */
	private function error_response( int|string|float|bool|null $id, int $code, string $message, int $status = 200 ): WP_REST_Response {
		return new WP_REST_Response(
			[
				'jsonrpc' => '2.0',
				'id'      => $id,
				'error'   => [
					'code'    => $code,
					'message' => $message,
				],
			],
			$status
		);
	}
}
