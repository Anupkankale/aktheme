<?php
/**
 * Discovery strategy (php-http) that offers the theme's HTTP client and PSR-17 factory.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\AI\Http;

use Http\Discovery\Strategy\DiscoveryStrategy as DiscoveryStrategyInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * Class DiscoveryStrategy
 *
 * The Anthropic SDK's Client constructor always runs php-http discovery for a PSR-18 client and PSR-17 factories, even
 * when a transporter is passed in. ClaudeClient installs this strategy only while it builds the SDK client and then
 * restores the previous strategies, so other plugins' discovery is left as it was.
 *
 * @since 1.3.0
 */
final class DiscoveryStrategy implements DiscoveryStrategyInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @param string $type Interface being discovered.
	 *
	 * @return list<array{class: class-string, condition: class-string}> Candidates.
	 */
	public static function getCandidates( $type ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Interface method name.
		if ( ClientInterface::class === $type ) {
			return [
				[
					'class'     => WpHttpClient::class,
					'condition' => WpHttpClient::class,
				],
			];
		}

		$factories = [ RequestFactoryInterface::class, ResponseFactoryInterface::class, StreamFactoryInterface::class, UriFactoryInterface::class ];
		if ( in_array( $type, $factories, true ) ) {
			return [
				[
					'class'     => Psr17Factory::class,
					'condition' => Psr17Factory::class,
				],
			];
		}

		return [];
	}
}
