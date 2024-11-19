<?php

namespace Geo\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Geo\Exception\InconclusiveException;
use Geo\Exception\NotAccurateEnoughException;
use Geo\Geocoder\Geocoder;
use Geocoder\Formatter\StringFormatter;

/**
 * GeocodedAddresses Model
 *
 * @method \Geo\Model\Entity\GeocodedAddress get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Geo\Model\Entity\GeocodedAddress newEntity(array $data, array $options = [])
 * @method array<\Geo\Model\Entity\GeocodedAddress> newEntities(array $data, array $options = [])
 * @method \Geo\Model\Entity\GeocodedAddress|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Geo\Model\Entity\GeocodedAddress patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\Geo\Model\Entity\GeocodedAddress> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Geo\Model\Entity\GeocodedAddress findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \Geo\Model\Entity\GeocodedAddress saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Geo\Model\Entity\GeocodedAddress>|false saveMany(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @method \Geo\Model\Entity\GeocodedAddress newEmptyEntity()
 * @method \Cake\Datasource\ResultSetInterface<\Geo\Model\Entity\GeocodedAddress> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Geo\Model\Entity\GeocodedAddress>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Geo\Model\Entity\GeocodedAddress> deleteManyOrFail(iterable $entities, array $options = [])
 */
class GeocodedAddressesTable extends Table {

	/**
	 * @var \Geo\Geocoder\Geocoder
	 */
	protected $_Geocoder;

	/**
	 * Initialize method
	 *
	 * @param array<string, mixed> $config The configuration for the Table.
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->setTable('geocoded_addresses');
		$this->setDisplayField('address');
		$this->setPrimaryKey('id');

		$this->getSchema()->setColumnType('data', 'object');

		$this->addBehavior('Timestamp');
	}

	/**
	 * @return int
	 */
	public function clearEmpty() {
		return $this->deleteAll(['formatted_address IS' => null]);
	}

	/**
	 * @return int
	 */
	public function clearAll() {
		return $this->deleteAll('1=1');
	}

	/**
	 * @param string $address
	 *
	 * @return \Geo\Model\Entity\GeocodedAddress|false
	 */
	public function retrieve($address) {
		/** @var \Geo\Model\Entity\GeocodedAddress|null $entity */
		$entity = $this->find()->where(['address' => $address])->first();
		if ($entity) {
			return $entity;
		}

		$result = $this->_execute($address);
		$geocodedAddress = $this->newEntity([
			'address' => $address,
		]);
		if ($result) {
			$coordinates = $result->getCoordinates();
			if ($coordinates) {
				$geocodedAddress->lat = $coordinates->getLatitude();
				$geocodedAddress->lng = $coordinates->getLongitude();
			}
			$country = $result->getCountry();
			if ($country) {
				$geocodedAddress->country = $country->getCode();
			}
			$formatter = new StringFormatter();
			$geocodedAddress->formatted_address = $formatter->format($result, '%S %n, %z %L');
			$geocodedAddress->data = $result;
		}

		return $this->save($geocodedAddress);
	}

	/**
	 * Default validation rules.
	 *
	 * @param \Cake\Validation\Validator $validator Validator instance.
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $validator): Validator {
		$validator
			->integer('id')
			->allowEmptyString('id', 'create');

		$validator
			->requirePresence('address', 'create')
			->notEmptyString('address')
			->add('address', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

		$validator
			->allowEmptyString('formatted_address');

		$validator
			->allowEmptyString('country');

		$validator
			->decimal('lat')
			->allowEmptyString('lat');

		$validator
			->decimal('lng')
			->allowEmptyString('lng');

		$validator
			->allowEmptyArray('data');

		return $validator;
	}

	/**
	 * Returns a rules checker object that will be used for validating
	 * application integrity.
	 *
	 * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Cake\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker $rules): RulesChecker {
		return $rules;
	}

	/**
	 * @param string $address
	 *
	 * @return \Geocoder\Location|null
	 */
	protected function _execute($address) {
		$this->_Geocoder = new Geocoder();
		try {
			$addresses = $this->_Geocoder->geocode($address);
		} catch (InconclusiveException $e) {
			return null;
		} catch (NotAccurateEnoughException $e) {
			return null;
		}

		if ($addresses->count() < 1) {
			return null;
		}

		return $addresses->first();
	}

}
