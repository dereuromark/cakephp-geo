<?php
declare(strict_types=1);

namespace Geo\Geocoder\Provider;

use Geocoder\Exception\InvalidServerResponse;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Model\Address;
use Geocoder\Model\AddressBuilder;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Provider;

/**
 * Geoapify geocoding provider.
 *
 * Uses the Geoapify Geocoding API which has a generous free tier.
 *
 * @link https://www.geoapify.com/geocoding-api
 * @link https://apidocs.geoapify.com/docs/geocoding/
 */
class GeoapifyProvider extends AbstractGeocodingProvider {

	/**
	 * @var string
	 */
	protected const GEOCODE_URL = 'https://api.geoapify.com/v1/geocode/search';

	/**
	 * @var string
	 */
	protected const REVERSE_URL = 'https://api.geoapify.com/v1/geocode/reverse';

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
		return 'geoapify';
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
	public function geocode(string $address): AddressCollection {
		$apiKey = $this->getConfig('apiKey');
		if (!$apiKey) {
			throw new UnsupportedOperation('Geoapify requires an API key.');
		}

		$url = static::GEOCODE_URL . '?' . http_build_query([
			'text' => $address,
			'apiKey' => $apiKey,
			'lang' => $this->getConfig('locale') ?: 'en',
		]);

		return $this->executeQuery($url);
	}

	/**
	 * @inheritDoc
	 */
	public function reverse(float $lat, float $lng): AddressCollection {
		$apiKey = $this->getConfig('apiKey');
		if (!$apiKey) {
			throw new UnsupportedOperation('Geoapify requires an API key.');
		}

		$url = static::REVERSE_URL . '?' . http_build_query([
			'lat' => $lat,
			'lon' => $lng,
			'apiKey' => $apiKey,
			'lang' => $this->getConfig('locale') ?: 'en',
		]);

		return $this->executeQuery($url);
	}

	/**
	 * Execute a query against the Geoapify API.
	 *
     * @param string $url The API URL
     * @throws \Geocoder\Exception\InvalidServerResponse
     * @return \Geocoder\Model\AddressCollection
	 */
	protected function executeQuery(string $url): AddressCollection {
		$response = $this->getHttpClient()->get($url);

		if (!$response->isOk()) {
			throw new InvalidServerResponse(
				sprintf('Geoapify API returned status %d', $response->getStatusCode()),
			);
		}

		$data = $response->getJson();
		if (!is_array($data) || !isset($data['features'])) {
			throw new InvalidServerResponse('Invalid response from Geoapify API');
		}

		$addresses = [];
		foreach ($data['features'] as $feature) {
			$address = $this->parseFeature($feature);
			if ($address !== null) {
				$addresses[] = $address;
			}
		}

		return new AddressCollection($addresses);
	}

	/**
	 * Parse a GeoJSON feature into an Address object.
	 *
	 * @param array<string, mixed> $feature The GeoJSON feature
	 * @return \Geocoder\Model\Address|null
	 */
	protected function parseFeature(array $feature): ?Address {
		if (!isset($feature['properties']) || !isset($feature['geometry'])) {
			return null;
		}

		$props = $feature['properties'];
		$coords = $feature['geometry']['coordinates'] ?? null;

		$builder = new AddressBuilder($this->getName());

		if ($coords && count($coords) >= 2) {
			$builder->setCoordinates($coords[1], $coords[0]);
		}

		if (!empty($props['street'])) {
			$builder->setStreetName($props['street']);
		}

		if (!empty($props['housenumber'])) {
			$builder->setStreetNumber($props['housenumber']);
		}

		if (!empty($props['postcode'])) {
			$builder->setPostalCode($props['postcode']);
		}

		if (!empty($props['city'])) {
			$builder->setLocality($props['city']);
		}

		if (!empty($props['district'])) {
			$builder->setSubLocality($props['district']);
		}

		if (!empty($props['state'])) {
			$builder->addAdminLevel(1, $props['state'], $props['state_code'] ?? null);
		}

		if (!empty($props['county'])) {
			$builder->addAdminLevel(2, $props['county']);
		}

		if (!empty($props['country'])) {
			$builder->setCountry($props['country']);
		}

		if (!empty($props['country_code'])) {
			$builder->setCountryCode(strtoupper($props['country_code']));
		}

		return $builder->build();
	}

	/**
	 * Not used for this custom implementation.
	 *
	 * @return \Geocoder\Provider\Provider
	 */
	protected function buildProvider(): Provider {
		throw new UnsupportedOperation('GeoapifyProvider uses custom HTTP implementation.');
	}

}
