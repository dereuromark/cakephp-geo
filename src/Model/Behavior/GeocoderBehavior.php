<?php
namespace Geo\Model\Behavior;

use Cake\ORM\Behavior;
use Geo\Geocode\Geocode;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\Event\Event;
use \ArrayObject;

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

	protected $_defaultConfig = array(
		'real' => false, 'address' => array('street', 'postal_code', 'city', 'country'),
		'require' => false, 'allowEmpty' => true, 'invalidate' => array(), 'expect' => array(),
		'lat' => 'lat', 'lng' => 'lng', 'formatted_address' => 'formatted_address',
		'host' => null, 'language' => 'de', 'region' => '', 'bounds' => '',
		'overwrite' => false, 'update' => array(), 'before' => 'save',
		'min_accuracy' => Geocode::ACC_COUNTRY, 'allow_inconclusive' => true, 'unit' => Geocode::UNIT_KM,
		'log' => true, // log successfull results to geocode.log (errors will be logged to error.log in either case)
		'implementedFinders' => [
			'distance' => 'findDistance',
		]
	);

	public $Geocode;

	/**
	 * Initiate behavior for the model using specified settings. Available settings:
	 *
	 * - address: (array | string, optional) set to the field name that contains the
	 * 			string from where to generate the slug, or a set of field names to
	 * 			concatenate for generating the slug.
	 *
	 * - real: (boolean, optional) if set to true then field names defined in
	 * 			label must exist in the database table. DEFAULTS TO: false
	 *
	 * - expect: (array)postal_code, locality, sublocality, ...
	 *
	 * - accuracy: see above
	 *
	 * - override: lat/lng override on changes?
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
		$this->config($config);

		$this->_table = $table;
	}

/**
 * @param \Cake\Event\Event $event The beforeSave event that was fired
 * @param \Cake\ORM\Entity $entity The entity that is going to be saved
 * @param \ArrayObject $options the options passed to the save method
 * @return void
 */
	public function beforeValidate(Event $event, Entity $entity, ArrayObject $options) {
		if ($this->_config['before'] === 'validate') {
			return $this->geocode();
		}

		return true;
	}

/**
 * @param \Cake\Event\Event $event The beforeSave event that was fired
 * @param \Cake\ORM\Entity $entity The entity that is going to be saved
 * @param \ArrayObject $options the options passed to the save method
 * @return void
 */
	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		if ($this->_config['before'] === 'save') {
			return $this->geocode($entity);
		}

		return true;
	}

	/**
	 * Run before a model is saved, used to set up slug for model.
	 *
	 * @param bool $return Value it should return as default (fallback).
	 * @return bool True if save should proceed, false otherwise
	 */
	public function geocode($entity) {
		// Make address fields an array
		if (!is_array($this->_config['address'])) {
			$addressfields = array($this->_config['address']);
		} else {
			$addressfields = $this->_config['address'];
		}
		$addressfields = array_unique($addressfields);

		// Make sure all address fields are available
		if ($this->_config['real']) {
			foreach ($addressfields as $field) {
				if (!$this->_table->hasField($field)) {
					return $entity;
				}
			}
		}

		$addressData = array();
		foreach ($addressfields as $field) {
			$fieldData = $entity->get($field);
			if (!empty($fieldData)) {
				$addressData[] = $fieldData;
			}
		}

		$entityData['geocoder_result'] = array();

		if ((!$this->_config['real'] || ($this->_table->hasField($this->_config['lat']) && $this->_table->hasField($this->_config['lng']))) &&
			($this->_config['overwrite'] || !$entity->get($this->_config['lat']) || ((int)$entity->get($this->_config['lat']) === 0 && (int)$entity->get($this->_config['lng']) === 0))
		) {
			/*
			//FIXME: whitelist in 3.x?
			if (!empty($this->_table->whitelist) && (!in_array($this->_config['lat'], $this->_table->whitelist) || !in_array($this->_config['lng'], $this->_table->whitelist))) {
				return $entity;
			}
			*/
		}

		$geocode = $this->_geocode($addressData);

		if (empty($geocode) && !empty($this->_config['allowEmpty'])) {
			return true;
		}
		if (empty($geocode)) {
			return false;
		}

		// If both are 0, thats not valid, otherwise continue
		if (empty($geocode['lat']) && empty($geocode['lng'])) {
			/*
			// Prevent 0 inserts of incorrect runs
			if (isset($this->_table->data[$this->_table->alias][$this->_config['lat']])) {
				unset($this->_table->data[$this->_table->alias][$this->_config['lat']]);
			}
			if (isset($this->_table->data[$this->_table->alias][$this->_config['lng']])) {
				unset($this->_table->data[$this->_table->alias][$this->_config['lng']]);
			}
			*/
			if ($this->_config['require']) {
				if ($fields = $this->_config['invalidate']) {
					//FIXME
					//$this->_table->invalidate($fields[0], $fields[1], isset($fields[2]) ? $fields[2] : true);
				}
				//return false;
			}
			return $geocode;
		}

		// Valid lat/lng found
		$entityData[$this->_config['lat']] = $geocode['lat'];
		$entityData[$this->_config['lng']] = $geocode['lng'];

		if (!empty($this->_config['formatted_address'])) {
			$entityData[$this->_config['formatted_address']] = $geocode['formatted_address'];
		}

		$entityData['geocoder_result'] = $geocode;
		$entityData['geocoder_result']['address_data'] = implode(' ', $addressData);

		if (!empty($this->_config['update'])) {
			foreach ($this->_config['update'] as $key => $field) {
				if (!empty($geocode[$key])) {
					$entityData[$field] = $geocode[$key];
				}
			}
		}

		$entity->set($entityData);

		return $entity;
	}

	/**
	 * Custom finder for distance.
	 *
	 * Used to be a virtual field in 2.x via setDistanceAsVirtualField()
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
		$options += array('tableName' => null);
		$sql = $this->distance($options['lat'], $options['lng'], null, null, $options['tableName']);
		$query->select(['distance' => $q->newExpr($sql)]);
		if (isset($options['distance'])) {
			$query->where(['distance <' => $options['distance']]);
		}
		return $query->order(['distance' => 'ASC']);
	}

	/**
	 * Adds the distance to this point as a virtual field.
	 * Make sure you have set configs lat/lng field names.
	 *
	 * @param string|float $lat Fieldname (Model.lat) or float value
	 * @param string|float $lng Fieldname (Model.lng) or float value
	 * @return void
	 * @deprecated Use custom finder / findDistance instead.
	 */
	public function setDistanceAsVirtualField($lat, $lng, $tableName = null) {
		$this->_table->virtualFields['distance'] = $this->distance($lat, $lng, null, null, $tableName);
	}

	/**
	 * Forms a sql snippet for distance calculation on db level using two lat/lng points.
	 *
	 * @param string|float $lat Fieldname (Model.lat) or float value
	 * @param string|float $lng Fieldname (Model.lng) or float value
	 * @return string
	 */
	public function distance($lat, $lng, $fieldLat = null, $fieldLng = null, $tableName = null) {
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

		return $value . ' * ACOS(COS(PI()/2 - RADIANS(90 - ' . $tableName . '.' . $fieldLat . ')) * ' .
			'COS(PI()/2 - RADIANS(90 - ' . $lat . ')) * ' .
			'COS(RADIANS(' . $tableName . '.' . $fieldLng . ') - RADIANS(' . $lng . ')) + ' .
			'SIN(PI()/2 - RADIANS(90 - ' . $tableName . '.' . $fieldLat . ')) * ' .
			'SIN(PI()/2 - RADIANS(90 - ' . $lat . ')))';
	}

	/**
	 * Snippet for custom pagination
	 *
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
		$conditions = array(
			$tableName . '.' . $fieldLat . ' <> 0',
			$tableName . '.' . $fieldLng . ' <> 0',
		);
		$fieldName = !empty($fieldName) ? $fieldName : 'distance';
		if ($distance !== null) {
			$conditions[] = '1=1 HAVING ' . $tableName . '.' . $fieldName . ' < ' . intval($distance);
		}
		return $conditions;
	}

	/**
	 * Snippet for custom pagination
	 *
	 * @return string
	 */
	public function distanceField($lat, $lng, $fieldName = null, $tableName = null) {
		if ($tableName === null) {
			$tableName = $this->_table->alias();
		}
		$fieldName = (!empty($fieldName) ? $fieldName : 'distance');
		return $this->distance($lat, $lng, null, null, $tableName) . ' AS ' . $tableName . '.' . $fieldName;
	}

	/**
	 * Snippet for custom pagination
	 * still useful?
	 *
	 * @return string
	 */
	public function distanceByField($lat, $lng, $byFieldName = null, $fieldName = null, $tableName = null) {
		if ($tableName === null) {
			$tableName = $this->_table->alias();
		}
		if ($fieldName === null) {
			$fieldName = 'distance';
		}
		if ($byFieldName === null) {
			$byFieldName = 'radius';
		}

		return $this->distance($lat, $lng, null, null, $tableName) . ' ' . $byFieldName;
	}

	/**
	 * Snippet for custom pagination
	 *
	 * @return int count
	 */
	public function paginateDistanceCount($conditions = null, $recursive = -1, $extra = array()) {
		if (!empty($extra['radius'])) {
			$conditions[] = $extra['distance'] . ' < ' . $extra['radius'] .
				(!empty($extra['startRadius']) ? ' AND ' . $extra['distance'] . ' > ' . $extra['startRadius'] : '') .
				(!empty($extra['endRadius']) ? ' AND ' . $extra['distance'] . ' < ' . $extra['endRadius'] : '');
		}
		if (!empty($extra['group'])) {
			unset($extra['group']);
		}
		$extra['behavior'] = true;
		return $this->_table->paginateCount($conditions, $recursive, $extra);
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
	 * @return array
	 */
	protected function _geocode($addressFields) {
		$address = implode(' ', $addressFields);
		if (empty($address)) {
			return array();
		}

		$geocodeOptions = array(
			'log' => $this->_config['log'], 'min_accuracy' => $this->_config['min_accuracy'],
			'expect' => $this->_config['expect'], 'allow_inconclusive' => $this->_config['allow_inconclusive'],
			'host' => $this->_config['host']
		);
		$this->Geocode = new Geocode($geocodeOptions);

		$config = array('language' => $this->_config['language']);
		if (!$this->Geocode->geocode($address, $config)) {
			return array('lat' => null, 'lng' => null, 'formatted_address' => '');
		}

		return $this->Geocode->getResult();
	}

	/**
	 * Get the current unit factor
	 *
	 * @param int $unit Unit constant
	 * @return float Value
	 */
	protected function _calculationValue($unit) {
		if (!isset($this->Geocode)) {
			$this->Geocode = new Geocode();
		}
		return $this->Geocode->convert(6371.04, Geocode::UNIT_KM, $unit);
	}

}
