<?php
namespace Geo\Geocoder;

use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Geocoder\Exception\NoResult;
use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;
use Geo\Exception\InconclusiveException;
use Geo\Exception\NotAccurateEnoughException;

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

	const TYPE_COUNTRY = 'country';
	const TYPE_AAL1 = 'administrative_area_level_1';
	const TYPE_AAL2 = 'administrative_area_level_2';
	const TYPE_AAL3 = 'administrative_area_level_3';
	const TYPE_AAL4 = 'administrative_area_level_4';
	const TYPE_AAL5 = 'administrative_area_level_5';
	const TYPE_LOC = 'locality';
	const TYPE_SUBLOC = 'sublocality';
	const TYPE_POSTAL = 'postal_code';
	//const TYPE_ROUTE = 'route'; // not available with GoogleMapsAPI
	//const TYPE_INTERSEC = 'intersection';
	const TYPE_ADDRESS = 'street_address';
	const TYPE_NUMBER = 'street_number';

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'locale' => null, // For GoogleMaps provider
		'region' => null, // For GoogleMaps provider
		'ssl' => false, // For GoogleMaps provider
		'apiKey' => '', // For GoogleMaps provider,
		'provider' => '\Geocoder\Provider\GoogleMaps', // Or use own callable
		'adapter' => '\Ivory\HttpAdapter\CakeHttpAdapter', // Only for default provider
		//'log' => false,
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
	protected $_types = [
		self::TYPE_COUNTRY,
		self::TYPE_AAL1,
		self::TYPE_AAL3,
		self::TYPE_AAL4,
		self::TYPE_AAL5,
		self::TYPE_LOC,
		self::TYPE_SUBLOC,
		self::TYPE_POSTAL,
		self::TYPE_ADDRESS,
		self::TYPE_NUMBER
	];

	/**
	 * @var \Geocoder\Provider\Provider
	 */
	protected $geocoder;

	/**
	 * @var \Ivory\HttpAdapter\HttpAdapterInterface
	 */
	protected $adapter;

	public function __construct(array $config = []) {
		$defaults = (array)Configure::read('Geocoder');
		$this->config($config + $defaults);
	}

	/**
	 * Actual querying.
	 * The query will be flatted, and if multiple results are fetched, they will be found
	 * in $result['all'].
	 *
	 * @param string $address
	 * @param array $params
	 * @return \Geocoder\Model\AddressCollection Result
	 */
	public function geocode($address, array $params = []) {
		$this->_buildGeocoder();

		try {
			$result = $this->geocoder->geocode($address);
		} catch (NoResult $e) {
			throw new InconclusiveException(sprintf('Inconclusive result (total of %s)', 0));
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
	 * @return \Geocoder\Model\AddressCollection Result
	 */
	public function reverse($lat, $lng, array $params = []) {
		$this->_buildGeocoder();

		$result = $this->geocoder->reverse($lat, $lng);
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
	 * @param \Geocoder\Model\Address $address
	 * @return bool
	 */
	public function isExpectedType(Address $address) {
		$expected = $this->_config['expect'];
		if (!$expected) {
			return true;
		}

		$adminLevels = $address->getAdminLevels();
		$map = [
			self::TYPE_AAL1 => 1,
			self::TYPE_AAL2 => 2,
			self::TYPE_AAL3 => 3,
			self::TYPE_AAL4 => 4,
			self::TYPE_AAL5 => 5,
		];

		foreach ($expected as $expect) {
			switch ($expect) {
				case self::TYPE_COUNTRY:
					if ($address->getCountry() !== null) {
						return true;
					}
					break;
				case (self::TYPE_AAL1):
				case (self::TYPE_AAL2):
				case (self::TYPE_AAL3):
				case (self::TYPE_AAL4):
				case (self::TYPE_AAL5):
					if ($adminLevels->has($map[$expect])) {
						return true;
					}
					break;
				case self::TYPE_LOC:
					if ($address->getLocality() !== null) {
						return true;
					}
					break;
				case self::TYPE_SUBLOC:
					if ($address->getSubLocality() !== null) {
						return true;
					}
					break;
				case self::TYPE_POSTAL:
					if ($address->getPostalCode() !== null) {
						return true;
					}
					break;
				case self::TYPE_ADDRESS:
					if ($address->getStreetName() !== null) {
						return true;
					}
					break;
				case self::TYPE_NUMBER:
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
	 * @param \Geocoder\Model\Address $address
	 * @return bool
	 */
	public function isAccurateEnough(Address $address) {
		$levels = array_keys($this->_types);
		$values = array_values($this->_types);
		$map = array_combine($levels, $values);

		$expected = $this->_config['minAccuracy'];

		//TODO
		return true;
	}

	/**
	 * @return void
	 */
	protected function _buildGeocoder() {
		if (isset($this->geocoder)) {
			return $this->geocoder;
		}

		$geocoderClass = $this->config('provider');
		if (is_callable($geocoderClass)) {
			$this->geocoder = $geocoderClass();
			return;
		}

		$adapterClass = $this->config('adapter');
		$this->adapter = new $adapterClass();
		$this->geocoder = new $geocoderClass($this->adapter, $this->config('locale'), $this->config('region'), $this->config('ssl'), $this->config('apiKey'));
	}

}
