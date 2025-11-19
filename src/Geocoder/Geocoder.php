<?php

namespace Geo\Geocoder;

use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Http\Client;
use Cake\I18n\I18n;
use Geo\Exception\InconclusiveException;
use Geo\Exception\NotAccurateEnoughException;
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
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'locale' => null, // For GoogleMaps provider
		'region' => null, // For GoogleMaps provider
		'ssl' => true, // For GoogleMaps provider
		'apiKey' => '', // For GoogleMaps provider,
		'provider' => GoogleMaps::class, // Or use own callable
		'adapter' => Client::class, // Only for default provider
		'allowInconclusive' => true,
		'minAccuracy' => self::TYPE_COUNTRY, // deprecated?
		'expect' => [], # see $_types for details, one hit is enough to be valid
	];

	/**
	 * This mainly does not work with the GoogleMap provider class as it loses information.
	 * Will need an own implementation
	 *
	 * @var array
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
	 * @var \Geocoder\Provider\Provider
	 */
	protected $geocoder;

	/**
	 * @var \Cake\Http\Client
	 */
	protected $adapter;

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
			/** @var \Geocoder\Model\AddressCollection $result */
			$result = $this->geocoder->geocodeQuery(GeocodeQuery::create($address));
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

		/** @var \Geocoder\Model\AddressCollection $result */
		$result = $this->geocoder->reverseQuery(ReverseQuery::fromCoordinates($lat, $lng));
		if (!$this->_config['allowInconclusive'] && !$this->isConclusive($result)) {
			throw new InconclusiveException(sprintf('Inconclusive result (total of %s)', $result->count()));
		}
		if ($this->_config['minAccuracy'] && !$this->containsAccurateEnough($result)) {
			throw new NotAccurateEnoughException('Result is not accurate enough');
		}

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
	 * @return void
	 */
	protected function _buildGeocoder() {
		$geocoderClass = $this->getConfig('provider');
		if (is_callable($geocoderClass)) {
			$this->geocoder = $geocoderClass();

			return;
		}

		/** @var \Cake\Http\Client $adapterClass */
		$adapterClass = $this->getConfig('adapter');
		$this->adapter = new $adapterClass();

		$provider = new GoogleMaps($this->adapter, $this->getConfig('region'), $this->getConfig('apiKey'));
		$geocoder = new StatefulGeocoder($provider, $this->getConfig('locale') ?: 'en');

		$this->geocoder = $geocoder;
	}

}
