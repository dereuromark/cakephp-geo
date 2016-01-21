<?php
namespace Geo\Model\Behavior;

use Cake\Core\Configure;
use Cake\Database\ExpressionInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use \ArrayObject;
use Geo\Exception\InconclusiveException;
use Geo\Exception\NotAccurateEnoughException;
use Geo\Geocoder\Calculator;
use Geo\Geocoder\Geocoder;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\FunctionExpression;

/**
 * A geocoding behavior for CakePHP to easily geocode addresses.
 * Uses the GeocodeLib for actual geocoding.
 * Also provides some useful geocoding tools like validation and distance conditions.
 *
 * Note that your lat/lng fields should be of type "float(10,6) DEFAULT NULL".
 * NULL as default is important as invalid or not found addresses should result in NULL
 * instead of 0.0 (which is a truthy value!).
 * If you need 0.0, cast it in your beforeSave() callback.
 *
 * @author Mark Scherer
 * @licence MIT
 * @link http://www.dereuromark.de/2012/06/12/geocoding-with-cakephp/
 */
class GeocoderBehavior extends Behavior {

	protected $_defaultConfig = [
		'address' => ['street', 'postal_code', 'city', 'country'],
		'allowEmpty' => true, // deprecated?
		'expect' => [],
		'lat' => 'lat', 'lng' => 'lng', 'formatted_address' => 'formatted_address',
		'locale' => null, // For GoogleMaps provider
		'region' => null, // For GoogleMaps provider
		'ssl' => false, // For GoogleMaps provider
		//'bounds' => '',
		'overwrite' => false,
		'update' => [],
		'on' => 'beforeSave',
		'minAccuracy' => Geocoder::TYPE_COUNTRY,
		'allowInconclusive' => true,
		'unit' => Calculator::UNIT_KM,
		//'log' => true, // logs successful results to geocode.log (errors will be logged to error.log in either case)
		'implementedFinders' => [
			'distance' => 'findDistance',
		]
	];

	/**
	 * @var \Geo\Geocoder\Geocoder
	 */
	public $_Geocoder;

	/**
	 * Initiate behavior for the model using specified settings. Available settings:
	 *
	 * - address: (array | string, optional) set to the field name that contains the
	 * 			string from where to generate the slug, or a set of field names to
	 * 			concatenate for generating the slug.
	 *
	 * - expect: (array)postal_code, locality, sublocality, ...
	 *
	 * - accuracy: see above
	 *
	 * - overwrite: lat/lng overwrite on changes?
	 *
	 * - update: what fields to update (key=>value array pairs)
	 *
	 * - before: validate/save (defaults to save)
	 * 			set to false if you only want to use the validation rules etc
	 *
/**
 * Constructor
 *
 * Merges config with the default and store in the config property
 *
 * Does not retain a reference to the Table object. If you need this
 * you should override the constructor.
 *
 * @param Table $table The table this behavior is attached to.
 * @param array $config The config for this behavior.
 */
	public function __construct(Table $table, array $config = []) {
		$defaults = (array)Configure::read('Geocoder');
		parent::__construct($table, $config + $defaults);

		$this->_table = $table;
	}

/**
 * @param \Cake\Event\Event $event The beforeSave event that was fired
 * @param \Cake\ORM\Entity $entity The entity that is going to be saved
 * @param \ArrayObject $options the options passed to the save method
 * @return void
 */
	public function beforeRules(Event $event, Entity $entity, ArrayObject $options) {
		if ($this->_config['on'] === 'beforeRules') {
			if(!$this->geocode($entity)) {
				$event->stopPropagation();
			}
		}
	}

/**
 * @param \Cake\Event\Event $event The beforeSave event that was fired
 * @param \Cake\ORM\Entity $entity The entity that is going to be saved
 * @param \ArrayObject $options the options passed to the save method
 * @return void
 */
	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		if ($this->_config['on'] === 'beforeSave') {
			if (!$this->geocode($entity)) {
				$event->stopPropagation();
			}
		}
	}

	/**
	 * Run before a model is saved, used to set up slug for model.
	 *
	 * @param \Cake\ORM\Entity $entity The entity that is going to be saved
	 * @return bool True if save should proceed, false otherwise
	 */
	public function geocode(Entity $entity) {
		// Make address fields an array
		if (!is_array($this->_config['address'])) {
			$addressfields = [$this->_config['address']];
		} else {
			$addressfields = $this->_config['address'];
		}
		$addressfields = array_unique($addressfields);

		$addressData = [];
		foreach ($addressfields as $field) {
			$fieldData = $entity->get($field);
			if (!empty($fieldData)) {
				$addressData[] = $fieldData;
			}
		}

		$entityData['geocoder_result'] = [];

		$addresses = $this->_geocode($addressData);

		if ($addresses->count() < 1) {
			return !empty($this->_config['allowEmpty']) ? true : false;
		}
		$address = $addresses->first();

		if (!$this->_Geocoder->isExpectedType($address)) {
			return !empty($this->_config['allowEmpty']) ? true : false;
		}
		// Valid lat/lng found
		$entityData[$this->_config['lat']] = $address->getLatitude();
		$entityData[$this->_config['lng']] = $address->getLongitude();

		//debug($address);die();
		if (!empty($this->_config['formatted_address'])) {
			// Unfortunately, the formatted address of google is lost
			$formatter = new \Geocoder\Formatter\StringFormatter();
			$entityData[$this->_config['formatted_address']] = $formatter->format($address, '%S %n, %z %L');
		}

		$entityData['geocoder_result'] = $address->toArray();
		$entityData['geocoder_result']['address_data'] = implode(' ', $addressData);

		if (!empty($this->_config['update'])) {
			foreach ($this->_config['update'] as $key => $field) {
				//FIXME, not so easy with the new library
				if (!empty($geocode[$key])) {
					$entityData[$field] = $geocode[$key];
				}
			}
		}

		$entity->set($entityData);

		return true;
	}

	/**
	 * Custom finder for distance.
	 *
	 * Options:
	 * - lat (required)
	 * - lng (required)
	 * - tableName
	 * - distance
	 *
	 * @param \Cake\ORM\Query $query Query.
	 * @param array $options Array of options as described above
	 * @return \Cake\ORM\Query
	 */
	public function findDistance(Query $query, array $options) {
		$options += ['tableName' => null];
		$sql = $this->distanceExpr($options['lat'], $options['lng'], null, null, $options['tableName']);
		$query->select(['distance' => $query->newExpr($sql)]);
		if (isset($options['distance'])) {
			// Some SQL versions cannot reuse the select() distance field, so we better reuse the $sql snippet
			$query->where(function ($exp) use ($sql, $options) {
				return $exp->lt($sql, $options['distance']);
			});
		}
		return $query->order(['distance' => 'ASC']);
	}

	/**
	 * Forms a sql snippet for distance calculation on db level using two lat/lng points.
	 *
	 * @param string|float|null $lat Latitude field (Model.lat) or float value
	 * @param string|float|null $lng Longitude field (Model.lng) or float value
	 * @param string|null $fieldLat Comparison field
	 * @param string|null $fieldLng Comparison field
	 * @param string|null $tableName
	 * @return ExpressionInterface
	 */
	public function distanceExpr($lat, $lng, $fieldLat = null, $fieldLng = null, $tableName = null) {
		if ($fieldLat === null) {
			$fieldLat = $this->_config['lat'];
		}
		if ($fieldLng === null) {
			$fieldLng = $this->_config['lng'];
		}
		if ($tableName === null) {
			$tableName = $this->_table->alias();
		}

		$value = $this->_calculationValue($this->_config['unit']);

		$op = function ($type, $params) {
			return new QueryExpression($params, [], $type);
		};
		$func = function ($name, $arg = null) {
			return new FunctionExpression($name, $arg === null ? [] : [$arg]);
		};

		$fieldLat = new IdentifierExpression("$tableName.$fieldLat");
		$fieldLng = new IdentifierExpression("$tableName.$fieldLng");

		$fieldLatRadians = $func('RADIANS', $op('-', ['90', $fieldLat]));
		$fieldLngRadians = $func('RADIANS', $fieldLng);
		$radius = $op('/', [$func('PI'), '2']);

		$mult = $op('*', [
			$func('COS', $op('-', [$radius, $fieldLatRadians])),
			'COS(PI()/2 - RADIANS(90 - ' . $lat . '))',
			$func('COS', $op('-', [$fieldLngRadians, $func('RADIANS', $lng)])),
		]);

		$mult2 = $op('*', [
			$func('SIN', $op('-', [$radius, $fieldLatRadians])),
			$func('SIN', $op('-', [$radius, 'RADIANS(90 - ' . $lat . ')'])),
		]);

		return $op('*', [
			(string)$value,
			$func('ACOS', $op('+', [$mult, $mult2]))
		]);
	}

	/**
	 * Snippet for custom pagination
	 *
	 * @param int|null $distance
	 * @param string|null $fieldName
	 * @param string|null $fieldLat
	 * @param string|null $fieldLng
	 * @param string|null $tableName
	 * @return array
	 */
	public function distanceConditions($distance = null, $fieldName = null, $fieldLat = null, $fieldLng = null, $tableName = null) {
		if ($fieldLat === null) {
			$fieldLat = $this->_config['lat'];
		}
		if ($fieldLng === null) {
			$fieldLng = $this->_config['lng'];
		}
		if ($tableName === null) {
			$tableName = $this->_table->alias();
		}
		$conditions = [
			$tableName . '.' . $fieldLat . ' <> 0',
			$tableName . '.' . $fieldLng . ' <> 0',
		];
		$fieldName = !empty($fieldName) ? $fieldName : 'distance';
		if ($distance !== null) {
			$conditions[] = '1=1 HAVING ' . $tableName . '.' . $fieldName . ' < ' . intval($distance);
		}
		return $conditions;
	}

	/**
	 * Snippet for custom pagination
	 *
	 * @param float|string|null $lat
	 * @param float|string|null $lng
	 * @param string|null $fieldName
	 * @param string|null $tableName
	 * @return array
	 */
	public function distanceField($lat, $lng, $fieldName = null, $tableName = null) {
		if ($tableName === null) {
			$tableName = $this->_table->alias();
		}
		$fieldName = (!empty($fieldName) ? $fieldName : 'distance');
		return [$tableName . '.' . $fieldName => $this->distanceExpr($lat, $lng, null, null, $tableName)];
	}

	/**
	 * Returns if a latitude is valid or not.
	 * validation rule for models
	 *
	 * @param float $latitude
	 * @return bool
	 */
	public function validateLatitude($latitude) {
		if (is_array($latitude)) {
			$latitude = array_shift($latitude);
		}
		return ($latitude <= 90 && $latitude >= -90);
	}

	/**
	 * Returns if a longitude is valid or not.
	 * validation rule for models
	 *
	 * @param float $longitude
	 * @return bool
	 */
	public function validateLongitude($longitude) {
		if (is_array($longitude)) {
			$longitude = array_shift($longitude);
		}
		return ($longitude <= 180 && $longitude >= -180);
	}

	/**
	 * Uses the Geocode class to query
	 *
	 * @param array $addressFields (simple array of address pieces)
	 * @return \Geocoder\Model\AddressCollection|null
	 */
	protected function _geocode($addressFields) {
		$address = implode(' ', $addressFields);
		if (empty($address)) {
			return [];
		}

		$this->_Geocoder = new Geocoder($this->_config);
		try {
			$addresses = $this->_Geocoder->geocode($address);
		} catch (InconclusiveException $e) {
			return null;
		} catch (NotAccurateEnoughException $e) {
			return null;
		}

		return $addresses;
	}

	/**
	 * Get the current unit factor
	 *
	 * @param int $unit Unit constant
	 * @return float Value
	 */
	protected function _calculationValue($unit) {
		if (!isset($this->Calculator)) {
			$this->Calculator = new Calculator();
		}
		return $this->Calculator->convert(6371.04, Calculator::UNIT_KM, $unit);
	}

}
