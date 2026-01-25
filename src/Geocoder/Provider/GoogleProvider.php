<?php
declare(strict_types=1);

namespace Geo\Geocoder\Provider;

use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\Provider\Provider;

/**
 * Google Maps geocoding provider.
 *
 * @link https://developers.google.com/maps/documentation/geocoding/
 */
class GoogleProvider extends AbstractGeocodingProvider {

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'apiKey' => null,
		'locale' => 'en',
		'region' => null,
	];

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'google';
	}

	/**
	 * @inheritDoc
	 */
	public function requiresApiKey(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function buildProvider(): Provider {
		return new GoogleMaps(
			$this->getHttpClient(),
			$this->getConfig('region'),
			$this->getConfig('apiKey'),
		);
	}

}
