<?php

namespace TestApp\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;

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
class SpatialAddressesTable extends Table {

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

		$this->setTable('spatial_addresses');
		$this->setDisplayField('address');
		$this->setPrimaryKey('id');

		//$this->getSchema()->setColumnType('data', 'object');

		$this->addBehavior('Timestamp');
	}

	/**
	 * Add formatter aka afterFind parsing geospatial values
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options): void {
		$columns = ['coordinates' => 'point'];

		// Go through each result and unpack() the binary data
		$query->formatResults(function($results) use($columns) {
			return $results->map(function($entity) use($columns) {
				foreach ($columns as $column => $type) {
					if (!isset($entity->{$column})) {
						continue;
					}
					// [TypeError] unpack(): Argument #2 ($string) must be of type string
					if (!is_string($entity->{$column})) {
						continue;
					}
					switch ($type) {
						// TODO support other types, not only POINT
						case 'point':
							$entity->{$column} = unpack('x/x/x/x/corder/Ltype/dx/dy', $entity->{$column});

							break;
					}
				}

				return $entity;
			});
		});
	}

	/**
	 * Once entity is marshalled, prepare geospatial values to be saved into database
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function afterMarshal(EventInterface $event, EntityInterface $entity, ArrayObject $data, ArrayObject $options): void {
		$columns = ['coordinates' => 'point'];

		foreach ($columns as $column => $type) {
			// Skip if the column is not present in $data
			if (!isset($data[$column])) {
				if (!empty($data['lat']) && !empty($data['lng'])) {
					$data[$column] = [$data['lng'], $data['lat']]; // lng, lat order is important!
				} else {
					continue;
				}
			}

			// We expect an array like [12, 34] otherwise skip
			if (!is_array($data[$column])) {
				continue;
			}
			switch ($type) {
				// TODO support other types, not only POINT
				case 'point':
					$value = sprintf('\'%s(%s)\'', strtoupper($type), implode(' ', $data[$column]));

					break;
			}
			// Set $value on $entity using ST_GeomFromText()
			$entity->{$column} = $this->query()->func()->ST_GeomFromText([
				$value => 'literal',
			]);
		}
	}

}
