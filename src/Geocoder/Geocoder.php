<?php

namespace Geo\Geocoder;

use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Http\Client;
use Cake\I18n\I18n;
use Geo\Exception\InconclusiveException;
use Geo\Exception\NotAccurateEnoughException;
use Geo\Geocoder\Provider\ChainProvider;
use Geo\Geocoder\Provider\GeoapifyProvider;
use Geo\Geocoder\Provider\GeocodingProviderInterface;
use Geo\Geocoder\Provider\GoogleProvider;
use Geo\Geocoder\Provider\NominatimProvider;
use Geo\Geocoder\Provider\NullProvider;
use Geocoder\Exception\CollectionIsEmpty;
use Geocoder\Exception\InvalidServerResponse;
use Geocoder\Location;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Geocoder\StatefulGeocoder;
use Locale;
use RuntimeException;

/**
 * Geocode via google (UPDATE: api3)
 *
 * @see https://developers.google.com/maps/documentation/geocoding/
 *
 * Used by Geo.GeocoderBehavior
 *
 * @author Mark Scherer
 * @license MIT
 */
class Geocoder {

	use InstanceConfigTrait;

	/**
	 * Provider constant for Google Maps.
	 *
	 * @var string
	 */
	public const PROVIDER_GOOGLE = 'google';

	/**
	 * Provider constant for Nominatim (OpenStreetMap).
	 *
	 * @var string
	 */
	public const PROVIDER_NOMINATIM = 'nominatim';

	/**
	 * Provider constant for Geoapify.
	 *
	 * @var string
	 */
	public const PROVIDER_GEOAPIFY = 'geoapify';

	/**
	 * Provider constant for NullProvider (testing).
	 *
	 * @var string
	 */
	public const PROVIDER_NULL = 'null';

	/**
	 * @var string
	 */
	public const TYPE_COUNTRY = 'country';

	/**
	 * @var string
	 */
	public const TYPE_AAL1 = 'administrative_area_level_1';

	/**
	 * @var string
	 */
	public const TYPE_AAL2 = 'administrative_area_level_2';

	/**
	 * @var string
	 */
	public const TYPE_AAL3 = 'administrative_area_level_3';

	/**
	 * @var string
	 */
	public const TYPE_AAL4 = 'administrative_area_level_4';

	/**
	 * @var string
	 */
	public const TYPE_AAL5 = 'administrative_area_level_5';

	/**
	 * @var string
	 */
	public const TYPE_LOC = 'locality';

	/**
	 * @var string
	 */
	public const TYPE_SUBLOC = 'sublocality';

	/**
	 * @var string
	 */
	public const TYPE_POSTAL = 'postal_code';

	//const TYPE_ROUTE = 'route'; // not available with GoogleMapsAPI
	//const TYPE_INTERSEC = 'intersection';
	/**
	 * @var string
	 */
	public const TYPE_ADDRESS = 'street_address';

	/**
	 * @var string
	 */
	public const TYPE_NUMBER = 'street_number';

	/**
	 * Registry of provider name to class mappings.
	 *
	 * @var array<string, class-string<\Geo\Geocoder\Provider\GeocodingProviderInterface>>
	 */
	protected static array $providerClasses = [
		self::PROVIDER_GOOGLE => GoogleProvider::class,
		self::PROVIDER_NOMINATIM => NominatimProvider::class,
		self::PROVIDER_GEOAPIFY => GeoapifyProvider::class,
		self::PROVIDER_NULL => NullProvider::class,
	];

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'locale' => null, // For GoogleMaps provider
		'region' => null, // For GoogleMaps provider
		'ssl' => true, // For GoogleMaps provider
		'apiKey' => '', // For GoogleMaps provider,
		'provider' => GoogleMaps::class, // Or use own callable, or provider name string
		'providers' => [], // Array of provider names for fallback chain
		'adapter' => Client::class, // Only for default provider
		'allowInconclusive' => true,
		'minAccuracy' => self::TYPE_COUNTRY, // deprecated?
		'expect' => [], # see $_types for details, one hit is enough to be valid
		// Provider-specific config
		'google' => [],
		'nominatim' => [],
		'geoapify' => [],
	];

	/**
	 * This mainly does not work with the GoogleMap provider class as it loses information.
	 * Will need an own implementation
	 *
	 * @var array<string>
	 */
	protected array $_types = [
		self::TYPE_COUNTRY,
		self::TYPE_AAL1,
		self::TYPE_AAL3,
		self::TYPE_AAL4,
		self::TYPE_AAL5,
		self::TYPE_LOC,
		self::TYPE_SUBLOC,
		self::TYPE_POSTAL,
		self::TYPE_ADDRESS,
		self::TYPE_NUMBER,
	];

	/**
	 * Legacy geocoder instance (Provider or StatefulGeocoder).
	 *
	 * @var \Geocoder\Provider\Provider|null
	 */
	protected $geocoder;

	/**
	 * @var \Cake\Http\Client|null
	 */
	protected $adapter;

	/**
	 * @var \Geo\Geocoder\Provider\GeocodingProviderInterface|null
	 */
	protected ?GeocodingProviderInterface $providerInstance = null;

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config = []) {
		$defaults = (array)Configure::read('Geocoder');
		$this->setConfig($config + $defaults);

		if ($this->getConfig('locale') === true) {
			$this->setConfig('locale', strtolower((string)Locale::getPrimaryLanguage(I18n::getLocale())));
		}

		if ($this->getConfig('region') === true) {
			$this->setConfig('region', strtolower((string)Locale::getRegion(I18n::getLocale())));
		}
	}

	/**
	 * Register a custom provider class.
	 *
	 * @param string $name Provider name
	 * @param class-string<\Geo\Geocoder\Provider\GeocodingProviderInterface> $className Provider class name
	 * @return void
	 */
	public static function registerProvider(string $name, string $className): void {
		static::$providerClasses[$name] = $className;
	}

	/**
	 * Get all registered provider classes.
	 *
	 * @return array<string, class-string<\Geo\Geocoder\Provider\GeocodingProviderInterface>>
	 */
	public static function getProviders(): array {
		return static::$providerClasses;
	}

	/**
	 * @return array<string, string>
	 */
	public function accuracyTypes() {
		$array = [
			static::TYPE_COUNTRY => __('Country'),
			static::TYPE_AAL1 => __('Province'),
			static::TYPE_AAL3 => __('Sub Province'),
			static::TYPE_AAL4 => __('Region'),
			static::TYPE_AAL5 => __('Sub Region'),
			static::TYPE_LOC => __('Locality'),
			static::TYPE_SUBLOC => __('Sub Locality'),
			static::TYPE_POSTAL => __('Postal Code'),
			static::TYPE_ADDRESS => __('Street Address'),
			static::TYPE_NUMBER => __('Street Number'),
		];

		return $array;
	}

	/**
	 * Actual querying.
	 * The query will be flatted, and if multiple results are fetched, they will be found
	 * in $result['all'].
	 *
	 * @param string $address
	 * @param array $params
	 *
	 * @throws \Geo\Exception\InconclusiveException
	 * @throws \Geo\Exception\NotAccurateEnoughException
	 *
	 * @return \Geocoder\Model\AddressCollection
	 */
	public function geocode($address, array $params = []) {
		$this->_buildGeocoder();

		try {
			$result = $this->executeGeocode($address);
		} catch (CollectionIsEmpty $e) {
			throw new InconclusiveException(sprintf('Inconclusive result (total of %s)', 0), 0, $e);
		} catch (InvalidServerResponse $e) {
			throw new RuntimeException(sprintf('Problem with API key `%s`', $this->getConfig('apiKey')) . ': ' . $e->getMessage(), 0, $e);
		}

		if (!$this->_config['allowInconclusive'] && !$this->isConclusive($result)) {
			throw new InconclusiveException(sprintf('Inconclusive result (total of %s)', $result->count()));
		}
		if ($this->_config['minAccuracy'] && !$this->containsAccurateEnough($result)) {
			throw new NotAccurateEnoughException('Result is not accurate enough');
		}

		return $result;
	}

	/**
	 * Results usually from most accurate to least accurate result (street_address, ..., country)
	 *
	 * @param float $lat
	 * @param float $lng
	 * @param array $params
	 *
	 * @throws \Geo\Exception\InconclusiveException
	 * @throws \Geo\Exception\NotAccurateEnoughException
	 *
	 * @return \Geocoder\Model\AddressCollection Result
	 */
	public function reverse($lat, $lng, array $params = []) {
		$this->_buildGeocoder();

		$result = $this->executeReverse($lat, $lng);

		if (!$this->_config['allowInconclusive'] && !$this->isConclusive($result)) {
			throw new InconclusiveException(sprintf('Inconclusive result (total of %s)', $result->count()));
		}
		if ($this->_config['minAccuracy'] && !$this->containsAccurateEnough($result)) {
			throw new NotAccurateEnoughException('Result is not accurate enough');
		}

		return $result;
	}

	/**
	 * Execute geocode using the configured provider.
	 *
	 * @param string $address
	 * @return \Geocoder\Model\AddressCollection
	 */
	protected function executeGeocode(string $address): AddressCollection {
		if ($this->providerInstance !== null) {
			return $this->providerInstance->geocode($address);
		}

		assert($this->geocoder !== null);

		/** @var \Geocoder\Model\AddressCollection $result */
		$result = $this->geocoder->geocodeQuery(GeocodeQuery::create($address));

		return $result;
	}

	/**
	 * Execute reverse geocode using the configured provider.
	 *
	 * @param float $lat
	 * @param float $lng
	 * @return \Geocoder\Model\AddressCollection
	 */
	protected function executeReverse(float $lat, float $lng): AddressCollection {
		if ($this->providerInstance !== null) {
			return $this->providerInstance->reverse($lat, $lng);
		}

		assert($this->geocoder !== null);

		/** @var \Geocoder\Model\AddressCollection $result */
		$result = $this->geocoder->reverseQuery(ReverseQuery::fromCoordinates($lat, $lng));

		return $result;
	}

	/**
	 * Seems like there are no details info anymore, or the provider does not forward it
	 *
	 * @param \Geocoder\Model\AddressCollection $addresses
	 * @return bool True if inconclusive
	 */
	public function isConclusive(AddressCollection $addresses) {
		return $addresses->count() === 1;
	}

	/**
	 * Expects certain address types to be present in the given address.
	 *
	 * @param \Geocoder\Location $address
	 * @return bool
	 */
	public function isExpectedType(Location $address) {
		$expected = $this->_config['expect'];
		if (!$expected) {
			return true;
		}

		$adminLevels = $address->getAdminLevels();
		$map = [
			static::TYPE_AAL1 => 1,
			static::TYPE_AAL2 => 2,
			static::TYPE_AAL3 => 3,
			static::TYPE_AAL4 => 4,
			static::TYPE_AAL5 => 5,
		];

		foreach ($expected as $expect) {
			switch ($expect) {
				case static::TYPE_COUNTRY:
					if ($address->getCountry() !== null) {
						return true;
					}

					break;
				case static::TYPE_AAL1:
				case static::TYPE_AAL2:
				case static::TYPE_AAL3:
				case static::TYPE_AAL4:
				case static::TYPE_AAL5:
					if ($adminLevels->has($map[$expect])) {
						return true;
					}

					break;
				case static::TYPE_LOC:
					if ($address->getLocality() !== null) {
						return true;
					}

					break;
				case static::TYPE_SUBLOC:
					if ($address->getSubLocality() !== null) {
						return true;
					}

					break;
				case static::TYPE_POSTAL:
					if ($address->getPostalCode() !== null) {
						return true;
					}

					break;
				case static::TYPE_ADDRESS:
					if ($address->getStreetName() !== null) {
						return true;
					}

					break;
				case static::TYPE_NUMBER:
					if ($address->getStreetNumber() !== null) {
						return true;
					}

					break;
			}
		}

		return false;
	}

	/**
	 * @param \Geocoder\Model\AddressCollection $addresses
	 * @return bool
	 */
	public function containsAccurateEnough(AddressCollection $addresses) {
		foreach ($addresses as $address) {
			if ($this->isAccurateEnough($address)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param \Geocoder\Location $address
	 * @return bool
	 */
	public function isAccurateEnough(Location $address) {
		$expected = $this->_config['minAccuracy'];
		if (!$expected) {
			return true;
		}

		$minAccuracyIndex = array_search($expected, $this->_types, true);
		if ($minAccuracyIndex === false) {
			return true;
		}

		$adminLevels = $address->getAdminLevels();
		$adminLevelMap = [
			static::TYPE_AAL1 => 1,
			static::TYPE_AAL2 => 2,
			static::TYPE_AAL3 => 3,
			static::TYPE_AAL4 => 4,
			static::TYPE_AAL5 => 5,
		];

		// Check all accuracy levels from minimum required to most accurate
		for ($i = $minAccuracyIndex; $i < count($this->_types); $i++) {
			$type = $this->_types[$i];
			switch ($type) {
				case static::TYPE_COUNTRY:
					if ($address->getCountry() !== null) {
						return true;
					}

					break;
				case static::TYPE_AAL1:
				case static::TYPE_AAL2:
				case static::TYPE_AAL3:
				case static::TYPE_AAL4:
				case static::TYPE_AAL5:
					if ($adminLevels->has($adminLevelMap[$type])) {
						return true;
					}

					break;
				case static::TYPE_LOC:
					if ($address->getLocality() !== null) {
						return true;
					}

					break;
				case static::TYPE_SUBLOC:
					if ($address->getSubLocality() !== null) {
						return true;
					}

					break;
				case static::TYPE_POSTAL:
					if ($address->getPostalCode() !== null) {
						return true;
					}

					break;
				case static::TYPE_ADDRESS:
					if ($address->getStreetName() !== null) {
						return true;
					}

					break;
				case static::TYPE_NUMBER:
					if ($address->getStreetNumber() !== null) {
						return true;
					}

					break;
			}
		}

		return false;
	}

	/**
	 * @throws \RuntimeException When both 'provider' and 'providers' are configured
	 * @return void
	 */
	protected function _buildGeocoder() {
		$providers = $this->getConfig('providers');
		$provider = $this->getConfig('provider');

		// Check for conflicting configuration
		$hasProvidersArray = $providers && is_array($providers);
		$hasCustomProvider = is_string($provider) && isset(static::$providerClasses[$provider]);
		$hasCallableProvider = is_callable($provider);
		$hasInstanceProvider = $provider instanceof GeocodingProviderInterface;

		if ($hasProvidersArray && ($hasCustomProvider || $hasCallableProvider || $hasInstanceProvider)) {
			throw new RuntimeException(
				'Cannot configure both \'provider\' and \'providers\'. Use only one.',
			);
		}

		// Handle providers array (fallback chain)
		if ($hasProvidersArray) {
			$this->buildChainProvider($providers);

			return;
		}

		// Handle callable provider (legacy support and advanced usage)
		if (is_callable($provider)) {
			$this->geocoder = $provider();

			return;
		}

		// Handle string provider name (new provider registry)
		if (is_string($provider) && isset(static::$providerClasses[$provider])) {
			$this->buildFromRegistry($provider);

			return;
		}

		// Handle GeocodingProviderInterface instance
		if ($provider instanceof GeocodingProviderInterface) {
			$this->providerInstance = $provider;

			return;
		}

		// Legacy: Handle class name (e.g., GoogleMaps::class)
		/** @var \Cake\Http\Client $adapterClass */
		$adapterClass = $this->getConfig('adapter');
		$this->adapter = new $adapterClass();

		$geocoderProvider = new GoogleMaps($this->adapter, $this->getConfig('region'), $this->getConfig('apiKey'));
		$geocoder = new StatefulGeocoder($geocoderProvider, $this->getConfig('locale') ?: 'en');

		$this->geocoder = $geocoder;
	}

	/**
	 * Build provider from the registry.
	 *
	 * @param string $providerName The provider name
	 * @return void
	 */
	protected function buildFromRegistry(string $providerName): void {
		$className = static::$providerClasses[$providerName];

		// Get provider-specific config and merge with global settings
		$providerConfig = (array)$this->getConfig($providerName);

		// Add global config fallbacks
		if (!isset($providerConfig['apiKey']) && $this->getConfig('apiKey')) {
			$providerConfig['apiKey'] = $this->getConfig('apiKey');
		}
		if (!isset($providerConfig['locale']) && $this->getConfig('locale')) {
			$providerConfig['locale'] = $this->getConfig('locale');
		}
		if (!isset($providerConfig['region']) && $this->getConfig('region')) {
			$providerConfig['region'] = $this->getConfig('region');
		}

		$this->providerInstance = new $className($providerConfig);
	}

	/**
	 * Build a chain provider from an array of provider names.
	 *
	 * @param array<string> $providerNames Array of provider names
	 * @return void
	 */
	protected function buildChainProvider(array $providerNames): void {
		$chain = new ChainProvider();

		foreach ($providerNames as $providerName) {
			if (!isset(static::$providerClasses[$providerName])) {
				continue;
			}

			$className = static::$providerClasses[$providerName];

			// Get provider-specific config and merge with global settings
			$providerConfig = (array)$this->getConfig($providerName);

			// Add global config fallbacks
			if (!isset($providerConfig['apiKey']) && $this->getConfig('apiKey')) {
				$providerConfig['apiKey'] = $this->getConfig('apiKey');
			}
			if (!isset($providerConfig['locale']) && $this->getConfig('locale')) {
				$providerConfig['locale'] = $this->getConfig('locale');
			}
			if (!isset($providerConfig['region']) && $this->getConfig('region')) {
				$providerConfig['region'] = $this->getConfig('region');
			}

			$chain->addProvider(new $className($providerConfig));
		}

		$this->providerInstance = $chain;
	}

}
