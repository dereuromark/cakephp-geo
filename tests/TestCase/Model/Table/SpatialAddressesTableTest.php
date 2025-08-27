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
	public function testFindSpatialExplain() {
		$this->assertNotEmpty($this->SpatialAddresses->getSchema()->getIndex('coordinates_spatial'));

		$this->SpatialAddresses->addBehavior('Geo.Geocoder');

		$sql = <<<SQL
EXPLAIN SELECT (ST_Distance_Sphere(coordinates, ST_GeomFromText('POINT(11.42223 48.110589)')) / 1000) AS `distance`, `SpatialAddresses`.`id` AS `SpatialAddresses__id`, `SpatialAddresses`.`address` AS `SpatialAddresses__address`, `SpatialAddresses`.`lat` AS `SpatialAddresses__lat`, `SpatialAddresses`.`lng` AS `SpatialAddresses__lng`, `SpatialAddresses`.`coordinates` AS `SpatialAddresses__coordinates`, `SpatialAddresses`.`created` AS `SpatialAddresses__created`, `SpatialAddresses`.`modified` AS `SpatialAddresses__modified` FROM `spatial_addresses` `SpatialAddresses` WHERE (ST_Distance_Sphere(coordinates, ST_GeomFromText('POINT(11.42223 48.110589)')) / 1000) <= 100 ORDER BY `distance` ASC
SQL;
		$result = $this->SpatialAddresses->getConnection()->execute($sql)->fetchAssoc();

		// Should be type range or index, not ALL
		debug($result);
	}

}
