<?php
declare(strict_types=1);

namespace Geo\Geocoder\Provider;

use Cake\Core\InstanceConfigTrait;
use Cake\Http\Client;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Geocoder\StatefulGeocoder;

/**
 * Abstract base class for geocoding providers.
 *
 * Provides common functionality for geocoding operations using
 * the willdurand/geocoder library providers.
 */
abstract class AbstractGeocodingProvider implements GeocodingProviderInterface {

	use InstanceConfigTrait;

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'apiKey' => null,
		'locale' => 'en',
		'region' => null,
	];

	/**
	 * @var \Cake\Http\Client|null
	 */
	protected ?Client $httpClient = null;

	/**
	 * @var \Geocoder\Provider\Provider|null
	 */
	protected ?Provider $provider = null;

	/**
	 * @var \Geocoder\StatefulGeocoder|null
	 */
	protected ?StatefulGeocoder $geocoder = null;

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config = []) {
		$this->setConfig($config);
	}

	/**
	 * @inheritDoc
	 */
	public function geocode(string $address): AddressCollection {
		$geocoder = $this->getGeocoder();

		/** @var \Geocoder\Model\AddressCollection $result */
		$result = $geocoder->geocodeQuery(GeocodeQuery::create($address));

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function reverse(float $lat, float $lng): AddressCollection {
		$geocoder = $this->getGeocoder();

		/** @var \Geocoder\Model\AddressCollection $result */
		$result = $geocoder->reverseQuery(ReverseQuery::fromCoordinates($lat, $lng));

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function requiresApiKey(): bool {
		return true;
	}

	/**
	 * Get the HTTP client instance.
	 *
	 * @return \Cake\Http\Client
	 */
	protected function getHttpClient(): Client {
		if ($this->httpClient === null) {
			$this->httpClient = new Client();
		}

		return $this->httpClient;
	}

	/**
	 * Get the stateful geocoder instance.
	 *
	 * @return \Geocoder\StatefulGeocoder
	 */
	protected function getGeocoder(): StatefulGeocoder {
		if ($this->geocoder === null) {
			$provider = $this->buildProvider();
			$locale = $this->getConfig('locale') ?: 'en';
			$this->geocoder = new StatefulGeocoder($provider, $locale);
		}

		return $this->geocoder;
	}

	/**
	 * Build the underlying geocoder-php provider.
	 *
	 * @return \Geocoder\Provider\Provider
	 */
	abstract protected function buildProvider(): Provider;

}
