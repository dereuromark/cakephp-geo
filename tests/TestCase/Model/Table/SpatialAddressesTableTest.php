<?php

namespace Geo\Test\TestCase\Model\Table;

use Cake\Database\Driver\Mysql;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;

class SpatialAddressesTableTest extends TestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Geo.SpatialAddresses',
	];

	/**
	 * @var \TestApp\Model\Table\SpatialAddressesTable
	 */
	protected $SpatialAddresses;

	/**
	 * @return void
	 */
	public function setUp(): void {
		$db = ConnectionManager::get('test');
		$driver = $db->getDriver();
		$this->skipIf(!($driver instanceof Mysql), 'The functionality/test is only compatible with Mysql right now.');

		parent::setUp();

		$config = TableRegistry::getTableLocator()->exists('SpatialAddresses') ? [] : ['className' => 'TestApp\Model\Table\SpatialAddressesTable'];
		$this->SpatialAddresses = TableRegistry::getTableLocator()->get('SpatialAddresses', $config);

		$entries = [
			[
				'address' => 'Langstrasse 10, 101010 München',
				'lat' => '48.150589',
				'lng' => '11.472230',
				'created' => '2011-04-21 16:50:05',
				'modified' => '2011-10-07 17:42:27',
			],
			[
				'address' => 'Leckermannstrasse 10, 10101 München',
				'lat' => '48.133942',
				'lng' => '11.490000',
				'created' => '2011-04-21 16:51:01',
				'modified' => '2011-10-07 17:44:02',
			],
			[
				'address' => 'Krebenweg 11, 12523 Schwäbisch Hall',
				'lat' => '19.081490',
				'lng' => '19.690800',
				'created' => '2011-11-17 13:47:36',
				'modified' => '2011-11-17 13:47:36',
			],
			[
				'address' => 'hjsf',
				'lat' => '52.52',
				'lng' => '13.40',
				'created' => '2011-11-17 14:34:14',
				'modified' => '2011-11-17 14:49:21',
			],
		];
		foreach ($entries as $entry) {
			$entity = $this->SpatialAddresses->newEntity($entry);
			$this->SpatialAddresses->saveOrFail($entity);
		}
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		unset($this->SpatialAddresses);

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testSave() {
		$address = $this->SpatialAddresses->newEntity([
			'address' => 'Berlin',
			'lat' => 12,
			'lng' => 11,
		]);
		$address = $this->SpatialAddresses->save($address);
		$this->assertNotEmpty($address);

		$address = $this->SpatialAddresses->get($address->id);
		$this->assertNotEmpty($address->coordinates);
		$this->assertEquals(11, $address->coordinates['x']);
		$this->assertEquals(12, $address->coordinates['y']);
	}

	/**
	 * @return void
	 */
	public function testFindSpatial() {
		$this->SpatialAddresses->addBehavior('Geo.Geocoder');
		$addresses = $this->SpatialAddresses->find('spatial', ...[
			'lat' => 48.110589,
			'lng' => 11.422230,
			'distance' => 100,
		])
			->all()
			->toArray();

		$distances = Hash::extract($addresses, '{n}.distance');
		$expected = [
			5.66,
			5.79,
		];
		foreach ($distances as $key => $distance) {
			$this->assertSame($expected[$key], round($distance, 2));
		}
	}

	/**
	 * @return void
	 */
	public function testFindSpatialExplain(): void {
		$this->assertNotEmpty($this->SpatialAddresses->getSchema()->getIndex('coordinates_spatial'));

		$this->SpatialAddresses->addBehavior('Geo.Geocoder');

		$lat = 48.110589;
		$lng = 11.422230;
		$distance = 100;

		// Calculate bounding box (same as in findSpatial)
		$latDelta = $distance / 111.0;
		$lngDelta = $distance / (111.0 * abs(cos(deg2rad($lat))));
		$minLat = $lat - $latDelta;
		$maxLat = $lat + $latDelta;
		$minLng = $lng - $lngDelta;
		$maxLng = $lng + $lngDelta;

		$sql = <<<SQL
EXPLAIN SELECT (ST_Distance_Sphere(coordinates, ST_GeomFromText('POINT($lng $lat)')) / 1000) AS `distance`,
	`SpatialAddresses`.`id` AS `SpatialAddresses__id`
FROM `spatial_addresses` `SpatialAddresses`
WHERE ST_Within(coordinates, ST_GeomFromText('POLYGON(($minLng $minLat, $maxLng $minLat, $maxLng $maxLat, $minLng $maxLat, $minLng $minLat))'))
	AND (ST_Distance_Sphere(coordinates, ST_GeomFromText('POINT($lng $lat)')) / 1000) <= $distance
ORDER BY `distance` ASC
SQL;
		$result = $this->SpatialAddresses->getConnection()->execute($sql)->fetchAssoc();

		// With small test data, optimizer may choose full scan, but the query structure must allow index usage
		// The key check is that possible_keys contains our spatial index
		$this->assertStringContainsString('coordinates_spatial', $result['possible_keys'] ?? '', 'Spatial index should be a possible key for the query');
	}

}
