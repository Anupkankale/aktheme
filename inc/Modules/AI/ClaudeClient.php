<?php
/**
 * Claude implementation of the AI writing assistant's client.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\AI;

use AK\Theme\Modules\AI\Http\DiscoveryStrategy;
use AK\Theme\Modules\AI\Http\WpHttpClient;
use Anthropic\Beta\Messages\BetaBase64ImageSource;
use Anthropic\Beta\Messages\BetaImageBlockParam;
use Anthropic\Beta\Messages\BetaTextBlockParam;
use Anthropic\Beta\Messages\BetaUserMessageParam;
use Anthropic\Client;
use Http\Discovery\ClassDiscovery;

/**
 * Class ClaudeClient
 *
 * Calls the Claude Messages API through the official Anthropic PHP SDK, with requests sent by WordPress's HTTP API
 * (WpHttpClient) instead of Guzzle.
 *
 * - Effort `low`: the assistant's tasks are short writing jobs, so low effort keeps them quick and cheap.
 * - Server-side refusal fallbacks (`fallbacks: "default"`) are on for the models that support them, so a request a
 *   safety classifier declines is retried on a fallback model in the same call instead of failing.
 *
 * @since 1.3.0
 */
final class ClaudeClient implements AiClient {

	/**
	 * Models that accept server-side refusal fallbacks. Claude Haiku 5.5 has none.
	 *
	 * @var list<string>
	 */
	private const FALLBACK_MODELS = [ 'claude-opus-5-5', 'claude-sonnet-5-5', 'claude-fable-5-2' ];

	/**
	 * Beta flag for server-side fallbacks.
	 *
	 * @var string
	 */
	private const FALLBACK_BETA = 'server-side-fallback-2026-07-01';

	/**
	 * Constructor.
	 *
	 * @param string $api_key Anthropic API key.
	 * @param string $model   Model ID.
	 */
	public function __construct(
		private string $api_key,
		private string $model,
	) {}

	/**
	 * {@inheritDoc}
	 *
	 * @param string                     $system     System prompt.
	 * @param list<array{type: 'text', text: string}|array{type: 'image', mime: 'image/jpeg'|'image/png'|'image/gif'|'image/webp', data: string}> $content User content.
	 * @param int                        $max_tokens Output cap.
	 */
	public function complete( string $system, array $content, int $max_tokens = 4096 ): string|\WP_Error {
		$args = [
			'maxTokens'    => $max_tokens,
			'model'        => $this->model,
			'system'       => $system,
			'messages'     => [ BetaUserMessageParam::with( content: $this->blocks( $content ) ) ],
			'outputConfig' => [ 'effort' => 'low' ],
		];

		if ( in_array( $this->model, self::FALLBACK_MODELS, true ) ) {
			$args['fallbacks'] = 'default';
			$args['betas']     = [ self::FALLBACK_BETA ];
		}

		try {
			$message = $this->sdk()->beta->messages->create( ...$args );
		} catch ( \Anthropic\Core\Exceptions\AuthenticationException ) {
			return $this->error( 'aktheme_ai_auth', __( 'The Anthropic API key was rejected. Check it in Appearance > AK AI.', 'aktheme' ), 401 );
		} catch ( \Anthropic\Core\Exceptions\PermissionDeniedException ) {
			return $this->error( 'aktheme_ai_permission', __( 'This API key cannot use the selected model. Pick another model in Appearance > AK AI.', 'aktheme' ), 403 );
		} catch ( \Anthropic\Core\Exceptions\RateLimitException ) {
			return $this->error( 'aktheme_ai_rate_limit', __( 'Too many AI requests right now. Wait a minute and try again.', 'aktheme' ), 429 );
		} catch ( \Anthropic\Core\Exceptions\OverloadedException ) {
			return $this->error( 'aktheme_ai_overloaded', __( 'Claude is busy right now. Try again in a moment.', 'aktheme' ), 503 );
		} catch ( \Anthropic\Core\Exceptions\BadRequestException $e ) {
			return $this->error( 'aktheme_ai_bad_request', $e->getMessage(), 400 );
		} catch ( \Anthropic\Core\Exceptions\APIStatusException $e ) {
			/* translators: %s: error message from the API. */
			return $this->error( 'aktheme_ai_api', sprintf( __( 'The AI request failed: %s', 'aktheme' ), $e->getMessage() ), 502 );
		} catch ( \Anthropic\Core\Exceptions\APIConnectionException ) {
			return $this->error( 'aktheme_ai_connection', __( 'Could not reach the Anthropic API. Check the server\'s internet connection.', 'aktheme' ), 504 );
		} catch ( \Anthropic\Core\Exceptions\APIException $e ) {
			/* translators: %s: error message. */
			return $this->error( 'aktheme_ai_api', sprintf( __( 'The AI request failed: %s', 'aktheme' ), $e->getMessage() ), 502 );
		}

		// Always check the stop reason before reading the content.
		if ( 'refusal' === $message->stopReason ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- SDK property.
			return $this->error( 'aktheme_ai_refusal', __( 'Claude declined this request. Try rephrasing it.', 'aktheme' ), 422 );
		}

		$text = '';
		foreach ( $message->content as $block ) {
			if ( isset( $block->type ) && 'text' === $block->type && isset( $block->text ) ) {
				$text .= (string) $block->text;
			}
		}

		if ( 'max_tokens' === $message->stopReason ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- SDK property.
			return $this->error( 'aktheme_ai_too_long', __( 'The reply was cut off because it was too long. Ask for something shorter.', 'aktheme' ), 422 );
		}

		return '' === trim( $text )
			? $this->error( 'aktheme_ai_empty', __( 'Claude returned an empty reply. Try again.', 'aktheme' ), 502 )
			: trim( $text );
	}

	/**
	 * Content blocks as SDK params.
	 *
	 * @param list<array{type: 'text', text: string}|array{type: 'image', mime: 'image/jpeg'|'image/png'|'image/gif'|'image/webp', data: string}> $content Content.
	 *
	 * @return list<BetaTextBlockParam|BetaImageBlockParam> Blocks.
	 */
	private function blocks( array $content ): array {
		return array_map(
			static fn ( array $block ): BetaTextBlockParam|BetaImageBlockParam => 'image' === $block['type']
				? BetaImageBlockParam::with( source: BetaBase64ImageSource::with( data: $block['data'], mediaType: $block['mime'] ) )
				: BetaTextBlockParam::with( text: $block['text'] ),
			$content
		);
	}

	/**
	 * The SDK client. The SDK runs php-http discovery in its constructor, so the theme's discovery strategy is installed
	 * for that call only and the previous strategies (and cache) are restored afterwards.
	 */
	private function sdk(): Client {
		$previous = ClassDiscovery::getStrategies();
		$previous = is_array( $previous ) ? $previous : iterator_to_array( $previous, false );
		ClassDiscovery::setStrategies( [ DiscoveryStrategy::class ] ); // Also clears the discovery cache.

		try {
			return new Client(
				apiKey: $this->api_key,
				requestOptions: [
					'transporter' => new WpHttpClient( (float) apply_filters( 'aktheme_ai_timeout', 60.0 ) ),
					'timeout'     => 60.0, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- Model replies take seconds; only on an editor's click.
					'maxRetries'  => 1,
				],
				openTelemetry: false,
			);
		} finally {
			ClassDiscovery::setStrategies( $previous );
		}
	}

	/**
	 * Error with an HTTP status for the REST response.
	 *
	 * @param string $code    Error code.
	 * @param string $message Message.
	 * @param int    $status  HTTP status.
	 */
	private function error( string $code, string $message, int $status ): \WP_Error {
		return new \WP_Error( $code, $message, [ 'status' => $status ] );
	}
}
