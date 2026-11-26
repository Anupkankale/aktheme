<?php
/**
 * Test the built-in MCP server.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

use AK\Theme\Core\Settings;
use AK\Theme\Modules\AI\McpServer;
use AK\Theme\Tests\TestCase;

/**
 * Class McpServerTest
 *
 * Sends JSON-RPC messages to /wp-json/aktheme/v1/mcp through the REST server, the way an MCP client does.
 *
 * @since 1.3.0
 */
class McpServerTest extends TestCase {

	/**
	 * Fresh REST server with the theme's routes, and an administrator.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_abilities' ) ) {
			$this->markTestSkipped( 'The Abilities API needs WordPress 6.9.' );
		}

		$this->reset_server();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * Clean up the REST server.
	 */
	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test isolation.

		parent::tearDown();
	}

	/**
	 * Rebuild the REST server so route registration re-reads the settings.
	 */
	private function reset_server(): void {
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test isolation.
		do_action( 'rest_api_init', $wp_rest_server );
	}

	/**
	 * Send a JSON-RPC message (or raw body).
	 *
	 * @param array<string, mixed>|string $message Message or raw body.
	 */
	private function send( array|string $message ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/aktheme/v1/mcp' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( is_string( $message ) ? $message : (string) wp_json_encode( $message ) );

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * A request message.
	 *
	 * @param string               $method Method.
	 * @param array<string, mixed> $params Params.
	 *
	 * @return array<string, mixed> Message.
	 */
	private function call( string $method, array $params = [] ): array {
		return [
			'jsonrpc' => '2.0',
			'id'      => 7,
			'method'  => $method,
			'params'  => $params,
		];
	}

	/**
	 * Test the server refuses anonymous requests.
	 */
	public function test_requires_login(): void {
		wp_set_current_user( 0 );

		$this->assertSame( 401, $this->send( $this->call( 'ping' ) )->get_status() );
	}

	/**
	 * Test initialize echoes a supported protocol version and names the server.
	 */
	public function test_initialize(): void {
		$data = $this->send( $this->call( 'initialize', [ 'protocolVersion' => '2025-06-18' ] ) )->get_data();

		$this->assertSame( '2.0', $data['jsonrpc'] );
		$this->assertSame( 7, $data['id'] );
		$this->assertSame( '2025-06-18', $data['result']['protocolVersion'] );
		$this->assertSame( 'aktheme', $data['result']['serverInfo']['name'] );
		$this->assertFalse( $data['result']['capabilities']['tools']['listChanged'] );
	}

	/**
	 * Test an unknown protocol version gets the newest one.
	 */
	public function test_initialize_unknown_version(): void {
		$data = $this->send( $this->call( 'initialize', [ 'protocolVersion' => '1999-01-01' ] ) )->get_data();

		$this->assertSame( McpServer::PROTOCOL_VERSIONS[0], $data['result']['protocolVersion'] );
	}

	/**
	 * Test notifications get 202 and no body.
	 */
	public function test_notification(): void {
		$response = $this->send(
			[
				'jsonrpc' => '2.0',
				'method'  => 'notifications/initialized',
			]
		);

		$this->assertSame( 202, $response->get_status() );
		$this->assertNull( $response->get_data() );
	}

	/**
	 * Test tools/list offers the theme abilities with MCP names, schemas and hints.
	 */
	public function test_tools_list(): void {
		$tools = $this->send( $this->call( 'tools/list' ) )->get_data()['result']['tools'];
		$names = wp_list_pluck( $tools, 'name' );

		$this->assertContains( 'aktheme__get-site-design', $names );
		$this->assertContains( 'aktheme__update-page', $names );

		$by_name  = array_combine( $names, $tools );
		$settings = $by_name['aktheme__get-theme-settings'];

		$this->assertTrue( $settings['annotations']['readOnlyHint'] );
		$this->assertFalse( $settings['annotations']['destructiveHint'] );
		$this->assertSame( 'object', $settings['inputSchema']['type'] );
		// Empty properties must serialise as a JSON object.
		$this->assertStringContainsString( '"properties":{}', (string) wp_json_encode( $settings['inputSchema'] ) );

		$update = $by_name['aktheme__update-page'];
		$this->assertTrue( $update['annotations']['destructiveHint'] );
		$this->assertContains( 'id', $update['inputSchema']['required'] );
	}

	/**
	 * Test tools/call runs the ability and returns text plus structured content.
	 */
	public function test_tools_call(): void {
		$result = $this->send( $this->call( 'tools/call', [ 'name' => 'aktheme__get-theme-settings' ] ) )->get_data()['result'];

		$this->assertFalse( $result['isError'] );
		$this->assertSame( 'text', $result['content'][0]['type'] );
		$this->assertSame( Settings::all()['ai_model'], $result['structuredContent']['ai_model'] );
	}

	/**
	 * Test a user without the capability gets a tool error, not a protocol error.
	 */
	public function test_tools_call_refuses_subscriber(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$data = $this->send( $this->call( 'tools/call', [ 'name' => 'aktheme__get-theme-settings' ] ) )->get_data();

		$this->assertArrayNotHasKey( 'error', $data );
		$this->assertTrue( $data['result']['isError'] );
	}

	/**
	 * Test invalid tool input is reported as a tool error the agent can read.
	 */
	public function test_tools_call_invalid_input(): void {
		$result = $this->send(
			$this->call(
				'tools/call',
				[
					'name'      => 'aktheme__update-page',
					'arguments' => [ 'id' => 'not-a-number' ],
				]
			)
		)->get_data()['result'];

		$this->assertTrue( $result['isError'] );
	}

	/**
	 * Test protocol errors: unknown method, unknown tool, bad JSON and batches.
	 */
	public function test_protocol_errors(): void {
		$this->assertSame( -32601, $this->send( $this->call( 'resources/list' ) )->get_data()['error']['code'] );
		$this->assertSame( -32602, $this->send( $this->call( 'tools/call', [ 'name' => 'aktheme__nope' ] ) )->get_data()['error']['code'] );

		$parse = $this->send( '{not json' );
		$this->assertSame( 400, $parse->get_status() );
		$this->assertSame( -32700, $parse->get_data()['error']['code'] );

		$batch = $this->send( (string) wp_json_encode( [ $this->call( 'ping' ) ] ) );
		$this->assertSame( -32600, $batch->get_data()['error']['code'] );
	}

	/**
	 * Test the setting switches the endpoint off.
	 */
	public function test_can_be_disabled(): void {
		update_option( Settings::OPTION, array_merge( Settings::all(), [ 'mcp_enabled' => false ] ) );
		$this->reset_server();

		$this->assertSame( 404, $this->send( $this->call( 'ping' ) )->get_status() );
	}

	/**
	 * Test tool and ability names map both ways.
	 */
	public function test_name_mapping(): void {
		$this->assertSame( 'aktheme__update-page', McpServer::tool_name( 'aktheme/update-page' ) );
		$this->assertSame( 'aktheme/update-page', McpServer::ability_name( 'aktheme__update-page' ) );
	}
}
