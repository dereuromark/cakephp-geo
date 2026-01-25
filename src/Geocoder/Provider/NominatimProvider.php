<?php
declare(strict_types=1);

namespace Geo\Geocoder\Provider;

use Geocoder\Provider\Nominatim\Nominatim;
use Geocoder\Provider\Provider;

/**
 * Nominatim (OpenStreetMap) geocoding provider.
 *
 * This provider uses the free OpenStreetMap Nominatim service.
 * No API key required, but a user agent string is mandatory
 * per OpenStreetMap's usage policy.
 *
 * @link https://nominatim.org/release-docs/develop/api/Overview/
 * @link https://operations.osmfoundation.org/policies/nominatim/
 */
class NominatimProvider extends AbstractGeocodingProvider {

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'apiKey' => null,
		'locale' => 'en',
		'region' => null,
		'userAgent' => 'CakePHP-Geo-Plugin',
		'rootUrl' => 'https://nominatim.openstreetmap.org',
	];

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'nominatim';
	}

	/**
	 * @inheritDoc
	 */
	public function requiresApiKey(): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	protected function buildProvider(): Provider {
		$rootUrl = $this->getConfig('rootUrl');
		$userAgent = $this->getConfig('userAgent');

		if ($rootUrl === 'https://nominatim.openstreetmap.org') {
			return Nominatim::withOpenStreetMapServer(
				$this->getHttpClient(),
				$userAgent,
			);
		}

		return new Nominatim(
			$this->getHttpClient(),
			$rootUrl,
			$userAgent,
		);
	}

}
