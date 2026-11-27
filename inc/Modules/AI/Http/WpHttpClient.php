<?php
/**
 * PSR-18 HTTP client on top of the WordPress HTTP API.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\AI\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class WpHttpClient
 *
 * The Anthropic SDK talks to any PSR-18 client. This one sends its requests with wp_safe_remote_request(), so the theme
 * does not bundle Guzzle (plugins often ship their own, incompatible copy) and requests honour the site's proxy, SSL and
 * `http_request_args` settings like every other WordPress request.
 *
 * Redirects are left to the SDK (`redirection` 0) and nothing is streamed: the assistant's replies are short.
 *
 * @since 1.3.0
 */
final class WpHttpClient implements ClientInterface {

	/**
	 * Request timeout in seconds.
	 *
	 * @var float
	 */
	private float $timeout;

	/**
	 * Constructor.
	 *
	 * @param float $timeout Timeout in seconds.
	 */
	public function __construct( float $timeout = 60.0 ) {
		$this->timeout = $timeout;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param RequestInterface $request Request.
	 *
	 * @throws \AK\Theme\Modules\AI\Http\NetworkException When WordPress could not complete the request.
	 */
	public function sendRequest( RequestInterface $request ): ResponseInterface {
		$headers = [];
		foreach ( $request->getHeaders() as $name => $values ) {
			$headers[ (string) $name ] = implode( ', ', $values );
		}

		$body = (string) $request->getBody();

		$args = [
			'method'      => $request->getMethod(),
			'headers'     => $headers,
			'timeout'     => $this->timeout, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- Model replies take seconds; only called on an editor's click.
			'redirection' => 0,
			'httpversion' => '1.1',
		];
		if ( '' !== $body ) {
			$args['body'] = $body;
		}

		$response = wp_safe_remote_request( (string) $request->getUri(), $args );

		if ( is_wp_error( $response ) ) {
			throw new \AK\Theme\Modules\AI\Http\NetworkException( $request, $response->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Not rendered to the browser.
		}

		$factory = new Psr17Factory();
		$code    = (int) wp_remote_retrieve_response_code( $response );
		$reason  = (string) wp_remote_retrieve_response_message( $response );
		$psr     = $factory->createResponse( $code > 0 ? $code : 500, $reason );

		$response_headers = wp_remote_retrieve_headers( $response );
		if ( $response_headers instanceof \WpOrg\Requests\Utility\CaseInsensitiveDictionary ) {
			$response_headers = $response_headers->getAll();
		}
		foreach ( (array) $response_headers as $name => $value ) {
			$psr = $psr->withHeader( (string) $name, is_array( $value ) ? array_map( 'strval', $value ) : (string) $value );
		}

		return $psr->withBody( $factory->createStream( (string) wp_remote_retrieve_body( $response ) ) );
	}
}
