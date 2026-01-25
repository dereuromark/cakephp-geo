<?php
declare(strict_types=1);

namespace Geo\Geocoder\Provider;

use Geocoder\Exception\InvalidServerResponse;
use Geocoder\Exception\QuotaExceeded;
use Geocoder\Model\AddressCollection;
use RuntimeException;
use Throwable;

/**
 * Chain provider that tries multiple providers in sequence.
 *
 * If a provider fails due to rate limiting (QuotaExceeded) or server errors,
 * the next provider in the chain is tried. This provides resilience against
 * API outages and rate limits.
 */
class ChainProvider implements GeocodingProviderInterface {

	/**
	 * @var array<\Geo\Geocoder\Provider\GeocodingProviderInterface>
	 */
	protected array $providers = [];

	/**
	 * Exception classes that trigger fallback to next provider.
	 *
	 * @var array<class-string<\Throwable>>
	 */
	protected array $fallbackExceptions = [
		QuotaExceeded::class,
		InvalidServerResponse::class,
	];

	/**
	 * @param array<\Geo\Geocoder\Provider\GeocodingProviderInterface> $providers
	 */
	public function __construct(array $providers = []) {
		$this->providers = $providers;
	}

	/**
	 * Add a provider to the chain.
	 *
	 * @param \Geo\Geocoder\Provider\GeocodingProviderInterface $provider
	 * @return $this
	 */
	public function addProvider(GeocodingProviderInterface $provider) {
		$this->providers[] = $provider;

		return $this;
	}

	/**
	 * Get all providers in the chain.
	 *
	 * @return array<\Geo\Geocoder\Provider\GeocodingProviderInterface>
	 */
	public function getProviders(): array {
		return $this->providers;
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'chain';
	}

	/**
	 * @inheritDoc
	 */
	public function geocode(string $address): AddressCollection {
		return $this->executeWithFallback(
			fn (GeocodingProviderInterface $provider) => $provider->geocode($address),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function reverse(float $lat, float $lng): AddressCollection {
		return $this->executeWithFallback(
			fn (GeocodingProviderInterface $provider) => $provider->reverse($lat, $lng),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function requiresApiKey(): bool {
		foreach ($this->providers as $provider) {
			if (!$provider->requiresApiKey()) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Execute a callback with fallback to next provider on failure.
	 *
     * @param callable(\Geo\Geocoder\Provider\GeocodingProviderInterface): \Geocoder\Model\AddressCollection $callback
     * @throws \RuntimeException When all providers fail
     * @return \Geocoder\Model\AddressCollection
	 */
	protected function executeWithFallback(callable $callback): AddressCollection {
		if (!$this->providers) {
			throw new RuntimeException('No providers configured in chain.');
		}

		$lastException = new RuntimeException('No providers returned results');

		foreach ($this->providers as $provider) {
			try {
				return $callback($provider);
			} catch (Throwable $e) {
				$lastException = $e;

				if (!$this->shouldFallback($e)) {
					throw $e;
				}
			}
		}

		throw new RuntimeException(
			'All providers in chain failed. Last error: ' . $lastException->getMessage(),
			0,
			$lastException,
		);
	}

	/**
	 * Check if an exception should trigger fallback to next provider.
	 *
	 * @param \Throwable $exception
	 * @return bool
	 */
	protected function shouldFallback(Throwable $exception): bool {
		foreach ($this->fallbackExceptions as $exceptionClass) {
			if ($exception instanceof $exceptionClass) {
				return true;
			}
		}

		return false;
	}

}
