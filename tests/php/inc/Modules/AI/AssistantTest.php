<?php
/**
 * Test the AI writing assistant endpoint.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

use AK\Theme\Core\Settings;
use AK\Theme\Modules\AI\AiClient;
use AK\Theme\Modules\AI\ApiKey;
use AK\Theme\Modules\AI\Assistant;
use AK\Theme\Tests\TestCase;

/**
 * Class AssistantTest
 *
 * Uses a fake client (the `aktheme_ai_client` filter), so no request ever reaches the Anthropic API.
 *
 * @since 1.3.0
 */
class AssistantTest extends TestCase {

	/**
	 * Fake client: records the last call and returns a preset reply.
	 *
	 * @var AiClient&object{reply: string|WP_Error, calls: list<array<string, mixed>>}
	 */
	private AiClient $fake;

	/**
	 * Install the fake client and a fresh REST server.
	 */
	public function setUp(): void {
		parent::setUp();

		putenv( 'ANTHROPIC_API_KEY' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Isolates the env-var key source.
		ApiKey::delete();

		$this->fake = new class() implements AiClient {

			/**
			 * Reply to return.
			 *
			 * @var string|WP_Error
			 */
			public string|\WP_Error $reply = 'OK';

			/**
			 * Calls received.
			 *
			 * @var list<array<string, mixed>>
			 */
			public array $calls = [];

			/**
			 * {@inheritDoc}
			 *
			 * @param string                     $system     System prompt.
			 * @param list<array<string, mixed>> $content    Content.
			 * @param int                        $max_tokens Output cap.
			 */
			public function complete( string $system, array $content, int $max_tokens = 4096 ): string|\WP_Error {
				$this->calls[] = compact( 'system', 'content', 'max_tokens' );

				return $this->reply;
			}
		};

		add_filter( 'aktheme_ai_client', fn () => $this->fake );

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test isolation.
		do_action( 'rest_api_init', $wp_rest_server );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
	}

	/**
	 * Clean up.
	 */
	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test isolation.
		remove_all_filters( 'aktheme_ai_client' );

		parent::tearDown();
	}

	/**
	 * Run a task.
	 *
	 * @param string               $task  Task.
	 * @param array<string, mixed> $input Input.
	 */
	private function run_task( string $task, array $input ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/aktheme/v1/ai' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			(string) wp_json_encode(
				[
					'task'  => $task,
					'input' => (object) $input,
				]
			)
		);

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Test users who cannot edit posts are refused.
	 */
	public function test_requires_edit_posts(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertSame( 403, $this->run_task( 'meta_description', [ 'text' => 'x' ] )->get_status() );
	}

	/**
	 * Test the meta description: site context in the system prompt, page text in the message, trimmed result.
	 */
	public function test_meta_description(): void {
		update_option( 'blogname', 'Acme Studio' );
		$this->fake->reply = "  We build fast websites.\n";

		$response = $this->run_task(
			'meta_description',
			[
				'title' => 'Web design',
				'text'  => 'We design websites.',
			]
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'We build fast websites.', $response->get_data()['result'] );

		$call = $this->fake->calls[0];
		$this->assertStringContainsString( 'Acme Studio', $call['system'] );
		$this->assertStringContainsString( 'meta description', $call['system'] );
		$this->assertSame( 'text', $call['content'][0]['type'] );
		$this->assertStringContainsString( 'Web design', $call['content'][0]['text'] );
	}

	/**
	 * Test title ideas are cleaned of numbering and quotes and capped at five.
	 */
	public function test_title_ideas(): void {
		$this->fake->reply = "1. \"First title\"\n2) Second title\n- Third title\n* Fourth\nFifth\nSixth";

		$result = $this->run_task( 'title_ideas', [ 'text' => 'Content' ] )->get_data()['result'];

		$this->assertSame( [ 'First title', 'Second title', 'Third title', 'Fourth', 'Fifth' ], $result );
	}

	/**
	 * Test FAQ replies are parsed into question/answer pairs.
	 */
	public function test_faq(): void {
		$this->fake->reply = "Q: How much does it cost?\nA: From <strong>₹24,999</strong>.\n\nQ: How long?\nA: Two weeks.";

		$result = $this->run_task( 'faq', [ 'text' => 'Content' ] )->get_data()['result'];

		$this->assertCount( 2, $result );
		$this->assertSame( 'How much does it cost?', $result[0]['question'] );
		$this->assertSame( 'From <strong>₹24,999</strong>.', $result[0]['answer'] );
		$this->assertSame( 'Two weeks.', $result[1]['answer'] );
	}

	/**
	 * Test rewrites keep inline formatting but lose anything unsafe.
	 */
	public function test_rewrite_is_sanitised(): void {
		$this->fake->reply = 'Better <strong>text</strong><script>alert(1)</script> <a href="https://example.com" onclick="x()">link</a>';

		$result = $this->run_task(
			'rewrite',
			[
				'text' => 'Some text',
				'mode' => 'improve',
			]
		)->get_data()['result'];

		$this->assertStringContainsString( '<strong>text</strong>', $result );
		$this->assertStringNotContainsString( '<script', $result );
		$this->assertStringNotContainsString( 'onclick', $result );
		$this->assertStringContainsString( 'href="https://example.com"', $result );
	}

	/**
	 * Test a new section keeps only the allowed block tags and drops code fences.
	 */
	public function test_write_section(): void {
		$this->fake->reply = "```html\n<h2>Why us</h2><p>Fast.</p><div class=\"x\"><iframe src=\"x\"></iframe></div>\n```";

		$result = $this->run_task( 'write_section', [ 'brief' => 'Why choose us' ] )->get_data()['result'];

		$this->assertStringStartsWith( '<h2>Why us</h2>', $result );
		$this->assertStringNotContainsString( '<iframe', $result );
		$this->assertStringNotContainsString( '```', $result );
	}

	/**
	 * Test bad input is refused before any model call.
	 */
	public function test_invalid_input(): void {
		$this->assertSame(
			400,
			$this->run_task(
				'rewrite',
				[
					'text' => 'x',
					'mode' => 'poem',
				] 
			)->get_status() 
		);
		$this->assertSame( 400, $this->run_task( 'rewrite', [ 'text' => '   ' ] )->get_status() );
		$this->assertSame( 400, $this->run_task( 'write_section', [ 'brief' => '' ] )->get_status() );
		$this->assertSame( 400, $this->run_task( 'meta_description', [ 'text' => str_repeat( 'a', Assistant::MAX_INPUT + 1 ) ] )->get_status() );
		$this->assertSame( 400, $this->run_task( 'alt_text', [ 'attachment_id' => 999999 ] )->get_status() );
		$this->assertSame( [], $this->fake->calls );
	}

	/**
	 * Test model errors are passed back with their status.
	 */
	public function test_client_error(): void {
		$this->fake->reply = new \WP_Error( 'aktheme_ai_rate_limit', 'Slow down', [ 'status' => 429 ] );

		$response = $this->run_task( 'meta_description', [ 'text' => 'x' ] );

		$this->assertSame( 429, $response->get_status() );
	}

	/**
	 * Test that without the fake client and without a key the assistant asks for one.
	 */
	public function test_no_key(): void {
		remove_all_filters( 'aktheme_ai_client' );

		$client = Assistant::client();

		$this->assertWPError( $client );
		$this->assertSame( 'aktheme_ai_no_key', $client->get_error_code() );
	}

	/**
	 * Test the setting switches the endpoint off.
	 */
	public function test_can_be_disabled(): void {
		update_option( Settings::OPTION, array_merge( Settings::all(), [ 'ai_enabled' => false ] ) );

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test isolation.
		do_action( 'rest_api_init', $wp_rest_server );

		$this->assertSame( 404, $this->run_task( 'meta_description', [ 'text' => 'x' ] )->get_status() );
	}
}
