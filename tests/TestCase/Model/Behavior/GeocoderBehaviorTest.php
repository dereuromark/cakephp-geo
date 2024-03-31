<?php

namespace Geo\Test\TestCase\Model\Behavior;

use Cake\Core\Configure;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Geo\Geocoder\Calculator;
use Geo\Geocoder\Geocoder;
use Geocoder\Model\Coordinates;
use InvalidArgumentException;
use TestApp\Controller\TestController;

class GeocoderBehaviorTest extends TestCase {

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.Geo.Addresses',
	];

	/**
	 * @var \Cake\ORM\Table|\Geo\Model\Behavior\GeocoderBehavior;
	 */
	protected $Addresses;

	/**
	 * @var \Cake\Database\Connection
	 */
	protected $db;

	/**
	 * setUp
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Configure::write('Geocoder.locale', 'DE');

		$this->Addresses = TableRegistry::getTableLocator()->get('Geo.Addresses');
		$this->Addresses->addBehavior('Geocoder');

		$this->db = ConnectionManager::get('test');
	}

	/**
	 * teardown
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->Addresses, $this->Addresses);
		TableRegistry::getTableLocator()->clear();
	}

	/**
	 * @return void
	 */
	public function testDistance() {
		$expr = $this->Addresses->distanceExpr(12, 14);
		$expected = '(6371.04 * ACOS(((COS(((PI() / 2) - RADIANS((90 - Addresses.lat)))) * COS(PI()/2 - RADIANS(90 - 12)) * COS((RADIANS(Addresses.lng) - RADIANS(:param0)))) + (SIN(((PI() / 2) - RADIANS((90 - Addresses.lat)))) * SIN(((PI() / 2) - RADIANS(90 - 12)))))))';

		$binder = new ValueBinder();
		$result = $expr->sql($binder);
		$this->assertEquals($expected, $result);

		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geocoder', ['lat' => 'x', 'lng' => 'y']);
		$expr = $this->Addresses->distanceExpr(12.1, 14.2);
		//$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Addresses.x)) * COS(PI()/2 - RADIANS(90 - 12.1)) * COS(RADIANS(Addresses.y) - RADIANS(14.2)) + SIN(PI()/2 - RADIANS(90 - Addresses.x)) * SIN(PI()/2 - RADIANS(90 - 12.1)))';
		$this->assertInstanceOf(QueryExpression::class, $expr);

		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geocoder', ['lat' => 'x', 'lng' => 'y']);
		$expr = $this->Addresses->distanceExpr('User.lat', 'User.lng');
		//$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Addresses.x)) * COS(PI()/2 - RADIANS(90 - User.lat)) * COS(RADIANS(Addresses.y) - RADIANS(User.lng)) + SIN(PI()/2 - RADIANS(90 - Addresses.x)) * SIN(PI()/2 - RADIANS(90 - User.lat)))';
		$this->assertInstanceOf(QueryExpression::class, $expr);
	}

	/**
	 * @return void
	 */
	public function testDistanceField() {
		$condition = $this->Addresses->distanceField(12, 14);
		//$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Addresses.lat)) * COS(PI()/2 - RADIANS(90 - 12)) * COS(RADIANS(Addresses.lng) - RADIANS(14)) + SIN(PI()/2 - RADIANS(90 - Addresses.lat)) * SIN(PI()/2 - RADIANS(90 - 12))) AS Addresses.distance';

		$this->assertInstanceOf(QueryExpression::class, $condition['Addresses.distance']);
	}

	/**
	 * @return void
	 */
	public function testSetDistanceAsVirtualField() {
		$driver = $this->db->getDriver();
		$this->skipIf(!($driver instanceof Mysql || $driver instanceof Postgres), 'The virtualFields test is only compatible with Mysql/Postgres.');

		$options = ['lat' => 13.3, 'lng' => 19.2]; //array('order' => array('Address.distance' => 'ASC'));
		$query = $this->Addresses->find()->find('distance', ...$options);

		$result = $query->toArray();

		$this->assertTrue($result[0]['distance'] < $result[1]['distance']);
		$this->assertTrue($result[1]['distance'] < $result[2]['distance']);
		$this->assertTrue($result[0]['distance'] > 620 && $result[0]['distance'] < 650);
	}

	/**
	 * @return void
	 */
	public function testSetDistanceAsValueObject() {
		$driver = $this->db->getDriver();
		$this->skipIf(!($driver instanceof Mysql || $driver instanceof Postgres), 'The virtualFields test is only compatible with Mysql/Postgres.');

		$coordinates = new Coordinates(13.3, 19.2);
		$options = ['coordinates' => $coordinates];
		$query = $this->Addresses->find()->find('distance', ...$options);

		$result = $query->toArray();

		$this->assertTrue($result[0]['distance'] < $result[1]['distance']);
		$this->assertTrue($result[1]['distance'] < $result[2]['distance']);
		$this->assertTrue($result[0]['distance'] > 620 && $result[0]['distance'] < 650);
	}

	/**
	 * @return void
	 */
	public function testFindDistanceOptionsMissing() {
		$this->expectException(InvalidArgumentException::class);

		$options = [];
		$this->Addresses->find()->find('distance', ...$options);
	}

	/**
	 * @return void
	 */
	public function testSetDistanceAsVirtualFieldInMiles() {
		$driver = $this->db->getDriver();
		$this->skipIf(!($driver instanceof Mysql || $driver instanceof Postgres), 'The virtualFields test is only compatible with Mysql/Postgres.');

		$this->Addresses->removeBehavior('Geocoder'); //FIXME: Shouldnt be necessary ideally
		$this->Addresses->addBehavior('Geocoder', ['unit' => Calculator::UNIT_MILES]);

		$options = ['lat' => 13.3, 'lng' => 19.2]; //$options = array('order' => array('Address.distance' => 'ASC'));
		$res = $this->Addresses->find()->find('distance', ...$options)->toArray();

		$this->assertTrue($res[0]['distance'] < $res[1]['distance']);
		$this->assertTrue($res[1]['distance'] < $res[2]['distance']);
		$this->assertTrue($res[0]['distance'] > 390 && $res[0]['distance'] < 410);
	}

	/**
	 * @return void
	 */
	public function testPagination() {
		$driver = $this->db->getDriver();
		$this->skipIf(!($driver instanceof Mysql || $driver instanceof Postgres), 'The virtualFields test is only compatible with Mysql/Postgres.');

		$controller = new TestController(new ServerRequest());
		$controller->getTableLocator()->get('Addresses')->addBehavior('Geocoder');
		$options = ['lat' => 13.3, 'lng' => 19.2, 'distance' => 3000];

		/** @var \Cake\ORM\Query $query */
		$query = $controller->getTableLocator()->get('Addresses')->find('distance', ...$options);
		$query->orderByAsc('distance');

		$res = $controller->paginate($query);

		$this->assertSame(2, $res->count());

		$items = $res->items()->__serialize();
		$this->assertTrue($items[0]['distance'] < $items[1]['distance']);
	}

	/**
	 * @return void
	 */
	public function testValidate() {
		$is = $this->Addresses->validateLatitude(44);
		$this->assertTrue($is);

		$is = $this->Addresses->validateLatitude(110);
		$this->assertFalse($is);

		$is = $this->Addresses->validateLongitude(150);
		$this->assertTrue($is);

		$is = $this->Addresses->validateLongitude(-190);
		$this->assertFalse($is);

		$this->Addresses->getValidator()->add('lat', 'validateLatitude', ['provider' => 'table', 'rule' => 'validateLatitude', 'message' => 'validateLatitudeError']);
		$this->Addresses->getValidator()->add('lng', 'validateLongitude', ['provider' => 'table', 'rule' => 'validateLongitude', 'message' => 'validateLongitudeError']);
		$data = [
			'lat' => 44,
			'lng' => 190,
		];
		$entity = $this->Addresses->newEntity($data);

		$expectedErrors = [
			'lng' => [
				'validateLongitude' => __('validateLongitudeError'),
			],
		];
		$this->assertEquals($expectedErrors, $entity->getErrors());
	}

	/**
	 * @return void
	 */
	public function testBasic() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geocoder', ['address' => ['street', 'zip', 'city']]);
		$data = [
			'street' => 'Krebenweg 22',
			'zip' => '74523',
			'city' => 'Bibersfeld',
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertTrue(!empty($res['lat']) && !empty($res['lng']));
		$this->assertTrue(round($res['lat']) === 49.0 && round($res['lng']) === 10.0);

		// inconclusive
		$data = [
			//'street' => 'Leopoldstraße',
			'city' => 'München',
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertTrue(!empty($res['lat']) && !empty($res['lng']));
		//FIXME
		$this->assertStringContainsString('München', $res['formatted_address']);
		//$this->assertEquals('München, Deutschland', $res['formatted_address']);

		$data = [
			'city' => 'Bibersfeld',
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertTrue(!empty($res));
		$this->assertSame('Bibersfeld', $res->geocoder_result['address_data']);
	}
	/**
	 * @return void
	 */
	public function testAddressClosure() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geocoder', [
			'address' => [
				'street',
				'zip',
				'city',
				function($entity) {
					if ($entity->country && $entity->country->id && $entity->country_id) {
						return $entity->country->name;
					}
					if ($entity->get('country_name')) {
						return $entity->get('country_name');
					}

					if ($entity->country_id) {
						// One can use this, but we fake it for simplicity of the test
						//$country = TableRegistry::getTableLocator('Countries')->get($entity->country_id);
						return 'Deutschland';
					}

					return null;
				},
			],
		]);

		$data = [
			'street' => 'Krebenweg 22',
			'zip' => '74523',
			'city' => 'Bibersfeld',
			'country_id' => 1,
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertTrue(!empty($res['lat']) && !empty($res['lng']));
		$this->assertTrue(round($res['lat']) === 49.0 && round($res['lng']) === 10.0);

		$this->assertSame('Deutschland', $res->geocoder_result['country']);
		$this->assertSame('DE', $res->geocoder_result['countryCode']);
		$this->assertSame('Krebenweg 22 74523 Bibersfeld Deutschland', $res->geocoder_result['address_data']);

		// inconclusive
		$data = [
			//'street' => 'Leopoldstraße',
			'city' => 'München',
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertTrue(!empty($res['lat']) && !empty($res['lng']));
		//FIXME
		$this->assertStringContainsString('München', $res['formatted_address']);
		//$this->assertEquals('München, Deutschland', $res['formatted_address']);

		$data = [
			'city' => 'Bibersfeld',
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertTrue(!empty($res));
	}

	/**
	 * @return void
	 */
	public function testMinAccLow() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geocoder', ['minAccuracy' => Geocoder::TYPE_COUNTRY]);
		$data = [
			'city' => 'Deutschland',
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertEquals('Deutschland', $res['city']);
		$this->assertTrue((int)$res['lat'] && (int)$res['lng']);
	}

	/**
	 * @return void
	 */
	public function testMinAccHigh() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geocoder', ['minAccuracy' => Geocoder::TYPE_POSTAL]);
		$data = [
			'city' => 'Deutschland',
		];
		$entity = $this->_getEntity($data);

		$res = $this->Addresses->save($entity);
		$this->assertEquals('Deutschland', $res['city']);
		//FIXME
		//$this->assertTrue(!isset($res['lat']) && !isset($res['lng']));
	}

	/**
	 * @return void
	 */
	public function testMinInc() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geocoder', ['minAccuracy' => Geocoder::TYPE_SUBLOC]);

		$this->assertEquals(Geocoder::TYPE_SUBLOC, $this->Addresses->behaviors()->Geocoder->getConfig('minAccuracy'));

		$data = [
			//'street' => 'Leopoldstraße',
			'city' => 'Neustadt',
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertEquals('Neustadt', $res['city']);
		//FIXME
		//$this->assertTrue(!isset($res['lat']) && !isset($res['lng']));
	}

	/**
	 * @return void
	 */
	public function testMinIncAllowed() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geocoder', ['allow_inconclusive' => true]);

		$data = [
			'city' => 'Neustadt',
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertEquals('Neustadt', $res['city']);
		$this->assertTrue(!empty($res['lat']) && !empty($res['lng']));
	}

	/**
	 * @return void
	 */
	public function testExpect() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geocoder', ['expect' => [Geocoder::TYPE_POSTAL]]);

		$data = [
			'city' => 'Berlin, Deutschland',
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);
		$this->assertTrue(empty($res['lat']) && empty($res['lng']));

		$data = [
			'city' => '74523, Deutschland',
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);
		$this->assertStringContainsString('74523 Schwäbisch Hall', $res['formatted_address']);
		//$this->assertEquals('74523 Schwäbisch Hall, Deutschland', $res['formatted_address']);
		$this->assertTrue(!empty($res['lat']) && !empty($res['lng']));
	}

	/**
	 * @return void
	 */
	public function testAllowEmpty() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geocoder', ['expect' => [Geocoder::TYPE_POSTAL]]);

		$data = [
			'city' => '',
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);
		$this->assertTrue((bool)$res);
	}

	/**
	 * @return void
	 */
	public function testAllowEmptyFalse() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geocoder', [
			'allowEmpty' => false,
			'expect' => [Geocoder::TYPE_POSTAL],
			'lat' => 'latitude',
			'lng' => 'longitude',
		]);

		$data = [
			'city' => '',
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);
		$this->assertFalse($res);

		$data = [
			'first_name' => 'ADmad',
			'latitude' => 40,
			'longitude' => 16,
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);
		$this->assertTrue((bool)$res);
	}

	/**
	 * @return void
	 */
	public function testOverwrite() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geocoder', [
			'allowEmpty' => false,
			'address' => ['street', 'zip', 'city'],
		]);
		$data = [
			'street' => 'Krebenweg 22',
			'zip' => '74523',
			'city' => 'Bibersfeld',
			'lat' => 40,
			'lng' => 16,
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);
		// Lat, lng is overwritten since `overwrite` is true by default.
		$this->assertTrue(round($res['lat']) === 49.0 && round($res['lng']) === 10.0);

		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geocoder', [
			'overwrite' => false,
			'allowEmpty' => false,
			'address' => ['street', 'zip', 'city'],
		]);

		$data = [
			'street' => 'Krebenweg 22',
			'zip' => '74523',
			'city' => 'Bibersfeld',
			'lat' => 40,
			'lng' => 16,
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);
		$this->assertTrue((bool)$res);
		$this->assertTrue($res['lat'] === 40 && $res['lng'] === 16);

		// Editing address fields does not modifying lat, lng since they are already set.
		$res->street = 'blah blah';
		$res->zip = 'xxxxxx';
		$res = $this->Addresses->save($entity);
		$this->assertTrue((bool)$res);
		$this->assertTrue($res['lat'] === 40 && $res['lng'] === 16);
	}

	/**
	 * Gets a new Entity
	 *
	 * @param array $data
	 * @return \Cake\ORM\Entity
	 */
	protected function _getEntity($data) {
		return new Entity($data);
	}

}
