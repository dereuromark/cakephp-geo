<?php

namespace Geo\Model\Behavior;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Geo\Exception\InconclusiveException;
use Geo\Exception\NotAccurateEnoughException;
use Geo\Geocoder\Calculator;
use Geo\Geocoder\Geocoder;
use Geocoder\Formatter\StringFormatter;
use Geocoder\Model\Coordinates;
use InvalidArgumentException;
use RuntimeException;

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
 * @license MIT
 * @link https://www.dereuromark.de/2012/06/12/geocoding-with-cakephp/
 */
class GeocoderBehavior extends Behavior {

	/**
	 * @var string
	 */
	public const OPTION_COORDINATES = 'coordinates';

	/**
	 * @var string
	 */
	public const OPTION_LAT = 'lat';

	/**
	 * @var string
	 */
	public const OPTION_LNG = 'lng';

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'address' => null,
		'allowEmpty' => true,
		'expect' => [],
		'lat' => self::OPTION_LAT, // Field name in your entity
		'lng' => self::OPTION_LNG, // Field name in your entity
		'formattedAddress' => 'formatted_address', // Field name in your entity
		'addressFormat' => '%S %n, %z %L', // For class StringFormatter
		'locale' => null, // For GoogleMaps provider
		'region' => null, // For GoogleMaps provider
		'ssl' => true, // For GoogleMaps provider
		'overwrite' => true, // Overwrite existing
		'update' => [],
		'on' => 'beforeSave', // Use beforeMarshal or afterMarshal if you need it for validation
		'minAccuracy' => Geocoder::TYPE_COUNTRY,
		'allowInconclusive' => true,
		'unit' => Calculator::UNIT_KM,
		'implementedFinders' => [
			'distance' => 'findDistance',
			'spatial' => 'findSpatial',
		],
		'validationError' => null,
		'cache' => false, // Enable only if you got a GeocodedAddresses table running
	];

	/**
	 * @var \Geo\Geocoder\Geocoder
	 */
	protected $_Geocoder;

	/**
	 * @var \Geo\Geocoder\Calculator|null
	 */
	protected $_Calculator;

	/**
	 * Initiate behavior for the model using specified settings. Available settings:
	 *
	 * - address: (array|string, optional) set to the field name that contains the
	 *             string from where to generate the slug, or a set of field names to
	 *             concatenate for generating the slug.
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
	 *             set to false if you only want to use the validation rules etc
	 *
	 * Merges config with the default and store in the config property
	 *
	 * Does not retain a reference to the Table object. If you need this
	 * you should override the constructor.
	 *
	 * @param \Cake\ORM\Table $table The table this behavior is attached to.
	 * @param array<string, mixed> $config The config for this behavior.
	 */
	public function __construct(Table $table, array $config = []) {
		$defaults = (array)Configure::read('Geocoder');
		parent::__construct($table, $config + $defaults);

		// BC shim, will be removed in next major
		if (!empty($this->_config['formatted_address'])) {
			$this->_config['formattedAddress'] = $this->_config['formatted_address'];
		}

		// Bug in core about merging keys of array values
		if ($this->_config['address'] === null) {
			$this->_config['address'] = ['street', 'postal_code', 'city', 'country'];
		}
		$this->_table = $table;

		$this->_Geocoder = new Geocoder($this->_config);
	}

	/**
	 * Using pre-patching to populate the entity with the lat/lng etc before
	 * the validation kicks in.
	 * This has the downside that it has to run every time. The other events trigger
	 * geocoding only if the address data has been modified (fields marked as dirty).
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void {
		if ($this->_config['on'] === 'beforeMarshal') {
			$addressFields = (array)$this->_config['address'];

			$addressData = [];
			foreach ($addressFields as $field) {
				if (!empty($data[$field])) {
					$addressData[] = $data[$field];
				}
			}

			if (!$this->_geocode($data, $addressData)) {
				$event->setResult(false);
				$event->stopPropagation();
			}
		}
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Geo\Model\Entity\GeocodedAddress $entity
	 *
	 * @return void
	 */
	public function afterMarshal(EventInterface $event, EntityInterface $entity): void {
		if ($this->_config['on'] === 'afterMarshal') {
			if (!$this->geocode($entity)) {
				$event->setResult(false);
				$event->stopPropagation();
			}
		}
	}

	/**
	 * @param \Cake\Event\EventInterface $event The beforeSave event that was fired
	 * @param \Geo\Model\Entity\GeocodedAddress $entity The entity that is going to be saved
	 * @param \ArrayObject $options the options passed to the save method
	 * @return void
	 */
	public function beforeRules(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if ($this->_config['on'] === 'beforeRules') {
			if (!$this->geocode($entity)) {
				$event->setResult(false);
				$event->stopPropagation();
			}
		}
	}

	/**
	 * @param \Cake\Event\EventInterface $event The beforeSave event that was fired
	 * @param \Geo\Model\Entity\GeocodedAddress $entity The entity that is going to be saved
	 * @param \ArrayObject $options the options passed to the save method
	 * @return void
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if ($this->_config['on'] === 'beforeSave') {
			if (!$this->geocode($entity)) {
				$event->setResult(false);
				$event->stopPropagation();
			}
		}
	}

	/**
	 * Run before a model is saved, used to set up slug for model.
	 *
	 * @param \Geo\Model\Entity\GeocodedAddress $entity The entity that is going to be saved
	 * @throws \RuntimeException
	 * @return \Geo\Model\Entity\GeocodedAddress|null
	 */
	public function geocode(EntityInterface $entity) {
		$latField = $this->_config[static::OPTION_LAT];
		$lngField = $this->_config[static::OPTION_LNG];

		if (
			!$this->_config['overwrite'] &&
			$entity->{$latField} && $entity->{$lngField}
		) {
			return $entity;
		}

		$addressFields = (array)$this->_config['address'];

		$addressData = [];
		$dirty = false;
		foreach ($addressFields as $field) {
			$isClosure = $field instanceof \Closure;
			if ($isClosure) {
				$fieldData = $field($entity);
			} else {
				$fieldData = $entity->get($field);
			}
			if ($fieldData) {
				$addressData[] = $fieldData;
			}

			if ($isClosure) {
				if ($fieldData) {
					$dirty = true;
				}

				continue;
			}
			if ($entity->isDirty($field)) {
				$dirty = true;
			}
		}

		if (!$dirty) {
			if (
				$this->_config['allowEmpty'] ||
				($entity->{$latField} && $entity->{$lngField})
			) {
				return $entity;
			}

			$this->invalidate($entity);

			return null;
		}

		/** @var \Geo\Model\Entity\GeocodedAddress|null $result */
		$result = $this->_geocode($entity, $addressData);

		return $result;
	}

	/**
	 * @param \Geo\Model\Entity\GeocodedAddress|\ArrayObject $entity
	 * @param array<string> $addressData
	 *
	 * @return \Geo\Model\Entity\GeocodedAddress|\ArrayObject|null
	 */
	protected function _geocode($entity, array $addressData) {
		$entityData = [
			'geocoder_result' => [],
		];

		$search = implode(' ', $addressData);
		if ($search === '') {
			if (!$this->_config['allowEmpty']) {
				return null;
			}

			return $entity;
		}

		$address = $this->_execute($search);
		if (!$address) {
			if ($this->_config['allowEmpty']) {
				return $entity;
			}
			if ($entity instanceof Entity) {
				$this->invalidate($entity);
			}

			return null;
		}

		if (!$this->_Geocoder->isExpectedType($address)) {
			if ($this->_config['allowEmpty']) {
				return $entity;
			}
			if ($entity instanceof Entity) {
				$this->invalidate($entity);
			}

			return null;
		}

		$coordinates = $address->getCoordinates();
		if ($coordinates) {
			// Valid lat/lng found
			$entityData[$this->_config['lat']] = $coordinates->getLatitude();
			$entityData[$this->_config['lng']] = $coordinates->getLongitude();
		}

		if (!empty($this->_config['formattedAddress'])) {
			// Unfortunately, the formatted address of google is lost
			$formatter = new StringFormatter();
			$entityData[$this->_config['formattedAddress']] = $formatter->format($address, $this->_config['addressFormat']);
		}

		$geocodedData = $address->toArray();
		$entityData['geocoder_result'] = $geocodedData;
		$entityData['geocoder_result']['address_data'] = implode(' ', $addressData);

		if (!empty($this->_config['update'])) {
			foreach ($this->_config['update'] as $key => $field) {
				if (!empty($geocodedData[$key])) {
					$entityData[$field] = $geocodedData[$key];
				}
			}
		}

		foreach ($entityData as $key => $value) {
			$entity[$key] = $value;
		}

		return $entity;
	}

	/**
	 * Custom finder for distance.
	 *
	 * Options:
	 * - lat (required)
	 * - lng (required)
	 * - coordinates (replaces lat/lng as value object alternative)
	 * - tableName
	 * - distance
	 * - sort
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query Query.
	 * @param float|null $lat
	 * @param float|null $lng
	 * @param \Geocoder\Model\Coordinates|null $coordinates
	 * @param int|null $distance
	 * @param string|null $tableName
	 * @param bool $sort
	 *
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findDistance(SelectQuery $query, ?float $lat = null, ?float $lng = null, ?Coordinates $coordinates = null, ?int $distance = null, ?string $tableName = null, bool $sort = true): SelectQuery {
		$options = [
			'tableName' => $tableName,
			'sort' => $sort,
			'lat' => $lat,
			'lng' => $lng,
			'distance' => $distance,
			'coordinates' => $coordinates,
		];
		$options = $this->assertCoordinates($options);
		$sql = $this->distanceExpr($options[static::OPTION_LAT], $options[static::OPTION_LNG], null, null, $options['tableName']);

		if ($query->isAutoFieldsEnabled() === null) {
			$query->enableAutoFields(true);
		}

		$query->select(['distance' => $query->newExpr($sql)]);
		if (isset($options['distance'])) {
			// Some SQL versions cannot reuse the select() distance field, so we better reuse the $sql snippet
			$query->where(function (QueryExpression $exp) use ($sql, $options) {
				return $exp->lt($sql, $options['distance']);
			});
		}

		if ($options['sort']) {
			$sort = $options['sort'] === true ? 'ASC' : $options['sort'];
			$query->orderBy(['distance' => $sort]);
		}

		return $query;
	}

	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param float|null $lat
	 * @param float|null $lng
	 * @param \Geocoder\Model\Coordinates|null $coordinates
	 * @param int|null $distance
	 * @param string|null $tableName
	 * @param bool $sort
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findSpatial(SelectQuery $query, ?float $lat = null, ?float $lng = null, ?Coordinates $coordinates = null, ?int $distance = null, ?string $tableName = null, bool $sort = true): SelectQuery {
		$options = [
			'tableName' => $tableName,
			'sort' => $sort,
			'lat' => $lat,
			'lng' => $lng,
			'distance' => $distance,
			'coordinates' => $coordinates,
		];
		$options = $this->assertCoordinates($options);

		if ($query->isAutoFieldsEnabled() === null) {
			$query->enableAutoFields(true);
		}

		$lat = $options[static::OPTION_LAT];
		$lng = $options[static::OPTION_LNG];

		// Add distance calculation as a virtual field
		$query->select([
			'distance' => new QueryExpression(
				"ST_Distance_Sphere(coordinates, ST_GeomFromText('POINT($lng $lat)')) / 1000",
			),
		]);

		// Filter by max distance if limit is provided
		if (isset($options['distance'])) {
			$distance = (float)$options['distance'];
			$query->where(function (QueryExpression $exp) use ($lat, $lng, $distance) {
				return $exp->lte(
					new QueryExpression("ST_Distance_Sphere(coordinates, ST_GeomFromText('POINT($lng $lat)')) / 1000"),
					$distance,
				);
			});
		}

		if ($options['sort']) {
			$sort = $options['sort'] === true ? 'ASC' : $options['sort'];
			$query->orderBy(['distance' => $sort]);
		}

		return $query;
	}

	/**
	 * Forms a sql snippet for distance calculation on db level using two lat/lng points.
	 *
	 * @param string|float $lat Latitude field (Model.lat) or float value
	 * @param string|float $lng Longitude field (Model.lng) or float value
	 * @param string|null $fieldLat Comparison field
	 * @param string|null $fieldLng Comparison field
	 * @param string|null $tableName
	 * @return \Cake\Database\ExpressionInterface
	 */
	public function distanceExpr($lat, $lng, $fieldLat = null, $fieldLng = null, $tableName = null) {
		if ($fieldLat === null) {
			$fieldLat = $this->_config['lat'];
		}
		if ($fieldLng === null) {
			$fieldLng = $this->_config['lng'];
		}
		if ($tableName === null) {
			$tableName = $this->_table->getAlias();
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
			$func('ACOS', $op('+', [$mult, $mult2])),
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
			$tableName = $this->_table->getAlias();
		}
		$conditions = [
			$tableName . '.' . $fieldLat . ' <> 0',
			$tableName . '.' . $fieldLng . ' <> 0',
		];
		$fieldName = !empty($fieldName) ? $fieldName : 'distance';
		if ($distance !== null) {
			$conditions[] = '1=1 HAVING ' . $tableName . '.' . $fieldName . ' < ' . (int)$distance;
		}

		return $conditions;
	}

	/**
	 * Snippet for custom pagination
	 *
	 * @param string|float $lat
	 * @param string|float $lng
	 * @param string|null $fieldName
	 * @param string|null $tableName
	 * @return array
	 */
	public function distanceField($lat, $lng, $fieldName = null, $tableName = null) {
		if ($tableName === null) {
			$tableName = $this->_table->getAlias();
		}
		$fieldName = (!empty($fieldName) ? $fieldName : 'distance');

		return [$tableName . '.' . $fieldName => $this->distanceExpr($lat, $lng, null, null, $tableName)];
	}

	/**
	 * Returns if a latitude is valid or not.
	 * validation rule for models
	 *
	 * @param array<float>|float $latitude
	 * @return bool
	 */
	public function validateLatitude($latitude) {
		if (is_array($latitude)) {
			$latitude = array_shift($latitude);
		}

		return $latitude <= 90 && $latitude >= -90;
	}

	/**
	 * Returns if a longitude is valid or not.
	 * validation rule for models
	 *
	 * @param array<float>|float $longitude
	 * @return bool
	 */
	public function validateLongitude($longitude) {
		if (is_array($longitude)) {
			$longitude = array_shift($longitude);
		}

		return $longitude <= 180 && $longitude >= -180;
	}

	/**
	 * Uses the Geocode class to query
	 *
	 * @param string $address
	 * @throws \RuntimeException
	 * @return \Geocoder\Location|null
	 */
	protected function _execute(string $address) {
		/** @var \Geo\Model\Table\GeocodedAddressesTable $GeocodedAddresses */
		$GeocodedAddresses = TableRegistry::getTableLocator()->get('Geo.GeocodedAddresses');
		if ($this->getConfig('cache')) {
			/** @var \Geo\Model\Entity\GeocodedAddress|null $result */
			$result = $GeocodedAddresses->find()->where(['address' => $address])->first();
			if ($result) {
				/** @var \Geocoder\Model\Address|null $data */
				$data = $result->data;

				return $data ?: null;
			}
		}

		try {
			$addresses = $this->_Geocoder->geocode($address);
		} catch (InconclusiveException $e) {
			$addresses = null;
		} catch (NotAccurateEnoughException $e) {
			$addresses = null;
		}
		$result = null;
		if ($addresses && $addresses->count() > 0) {
			$result = $addresses->first();
		}

		if ($this->getConfig('cache')) {
			$addressEntity = $GeocodedAddresses->newEntity([
				'address' => $address,
			]);

			if ($result) {
				$formatter = new StringFormatter();
				$addressEntity->formatted_address = $formatter->format($result, $this->_config['addressFormat']);
				$coordinates = $result->getCoordinates();
				if ($coordinates) {
					$addressEntity->lat = $coordinates->getLatitude();
					$addressEntity->lng = $coordinates->getLongitude();
				}
				$country = $result->getCountry();
				if ($country) {
					$addressEntity->country = $country->getCode();
				}
				$addressEntity->data = $result;
			}

			if (!$GeocodedAddresses->save($addressEntity, ['atomic' => false])) {
				throw new RuntimeException('Could not store geocoding cache data');
			}
		}

		return $result;
	}

	/**
	 * Get the current unit factor
	 *
	 * @param string $unit Unit constant/string.
	 * @return float Value
	 */
	protected function _calculationValue($unit) {
		if (!isset($this->_Calculator)) {
			$this->_Calculator = new Calculator();
		}

		return $this->_Calculator->convert(6371.04, Calculator::UNIT_KM, $unit);
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 */
	protected function invalidate($entity) {
		$errorMessage = $this->_config['validationError'] ?? __('Could not geocode this address. Please refine.');
		if ($errorMessage === false) {
			return;
		}

		$fields = (array)$this->_config['address'];
		foreach ($fields as $field) {
			if (!is_array($errorMessage)) {
				$entity->setError($field, $errorMessage);
			}

			$message = !empty($errorMessage[$field]) ? $errorMessage[$field] : null;
			if (!$message) {
				continue;
			}
			$entity->setError($field, $message);
		}
	}

	/**
	 * @param array<string, mixed> $options
	 *
	 * @return array
	 */
	protected function assertCoordinates(array $options): array {
		if (!empty($options[static::OPTION_COORDINATES])) {
			/** @var \Geocoder\Model\Coordinates $coordinates */
			$coordinates = $options[static::OPTION_COORDINATES];
			$options[static::OPTION_LAT] = $coordinates->getLatitude();
			$options[static::OPTION_LNG] = $coordinates->getLongitude();
		}

		if (empty($options[static::OPTION_LAT]) || empty($options[static::OPTION_LNG])) {
			$error = sprintf('Fields %s or %s value object are missing.', static::OPTION_LNG . '/' . static::OPTION_LNG, static::OPTION_COORDINATES);

			throw new InvalidArgumentException($error);
		}

		if ($options[static::OPTION_LAT] < -90 || $options[static::OPTION_LAT] > 90 || $options[static::OPTION_LNG] < -180 || $options[static::OPTION_LNG] > 180) {
			throw new InvalidArgumentException('Invalid latitude or longitude in (' . $options[static::OPTION_LAT] . '/' . $options[static::OPTION_LNG] . ').');
		}

		return $options;
	}

}
