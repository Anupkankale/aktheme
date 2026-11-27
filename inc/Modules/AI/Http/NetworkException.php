<?php
/**
 * PSR-18 network error for WpHttpClient.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\AI\Http;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Class NetworkException
 *
 * Thrown when WordPress could not complete a request (DNS, TLS, timeout). The SDK turns it into an
 * APIConnectionException and retries it.
 *
 * @since 1.3.0
 */
final class NetworkException extends \RuntimeException implements NetworkExceptionInterface {

	/**
	 * The request that failed.
	 *
	 * @var RequestInterface
	 */
	private RequestInterface $request;

	/**
	 * Constructor.
	 *
	 * @param RequestInterface $request The request.
	 * @param string           $message Message.
	 */
	public function __construct( RequestInterface $request, string $message ) {
		parent::__construct( $message );

		$this->request = $request;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRequest(): RequestInterface { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- PSR-18 method name.
		return $this->request;
	}
}
