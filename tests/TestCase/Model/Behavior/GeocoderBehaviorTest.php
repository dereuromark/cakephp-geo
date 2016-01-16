<?php
namespace Geo\Test\Model\Behavior;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use Cake\Controller\Controller;
use Cake\TestSuite\TestCase;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Entity;
use Cake\Database\Driver\Mysql;
use Geo\Geocode\Geocode;
use Geo\Geocoder\Calculator;
use Geo\Geocoder\Geocoder;
use Geo\Model\Behavior\GeocoderBehavior;

class GeocoderBehaviorTest extends TestCase {

	public $fixtures = [
		'plugin.Geo.Addresses'
	];

	/**
	 * @var \Cake\ORM\Table;
	 */
	public $Addresses;

	/**
	 * setUp
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('Geocoder', [
			'locale' => 'DE'
		]);

		$this->Addresses = TableRegistry::get('Geo.Addresses');
		$this->Addresses->addBehavior('Geo.Geocoder');

		$this->db = ConnectionManager::get('test');
	}

	/**
	 * teardown
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Addresses, $this->Addresses);
		TableRegistry::clear();
	}

	/**
	 * GeocoderBehaviorTest::testDistance()
	 *
	 * @return void
	 */
	public function testDistance() {
		$res = $this->Addresses->distanceSql(12, 14);
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Addresses.lat)) * COS(PI()/2 - RADIANS(90 - 12)) * COS(RADIANS(Addresses.lng) - RADIANS(14)) + SIN(PI()/2 - RADIANS(90 - Addresses.lat)) * SIN(PI()/2 - RADIANS(90 - 12)))';
		$this->assertEquals($expected, $res);

		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geo.Geocoder', ['lat' => 'x', 'lng' => 'y']);
		$res = $this->Addresses->distanceSql(12.1, 14.2);
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Addresses.x)) * COS(PI()/2 - RADIANS(90 - 12.1)) * COS(RADIANS(Addresses.y) - RADIANS(14.2)) + SIN(PI()/2 - RADIANS(90 - Addresses.x)) * SIN(PI()/2 - RADIANS(90 - 12.1)))';
		$this->assertEquals($expected, $res);

		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geo.Geocoder', ['lat' => 'x', 'lng' => 'y']);
		$res = $this->Addresses->distanceSql('User.lat', 'User.lng');
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Addresses.x)) * COS(PI()/2 - RADIANS(90 - User.lat)) * COS(RADIANS(Addresses.y) - RADIANS(User.lng)) + SIN(PI()/2 - RADIANS(90 - Addresses.x)) * SIN(PI()/2 - RADIANS(90 - User.lat)))';
		$this->assertEquals($expected, $res);
	}

	/**
	 * GeocoderBehaviorTest::testDistanceField()
	 *
	 * @return void
	 */
	public function testDistanceField() {
		$res = $this->Addresses->distanceField(12, 14);
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Addresses.lat)) * COS(PI()/2 - RADIANS(90 - 12)) * COS(RADIANS(Addresses.lng) - RADIANS(14)) + SIN(PI()/2 - RADIANS(90 - Addresses.lat)) * SIN(PI()/2 - RADIANS(90 - 12))) AS Addresses.distance';
		$this->assertEquals($expected, $res);
	}

	/**
	 * GeocoderBehaviorTest::testSetDistanceAsVirtualField()
	 *
	 * @return void
	 */
	public function testSetDistanceAsVirtualField() {
		$driver = $this->db->driver();
		$this->skipIf(!($driver instanceof Mysql), 'The virtualFields test is only compatible with Mysql.');

		$options = ['lat' => 13.3, 'lng' => 19.2]; //array('order' => array('Address.distance' => 'ASC'));
		$res = $this->Addresses->find()->find('distance', $options)->find('all')->toArray();

		$this->assertTrue($res[0]['distance'] < $res[1]['distance']);
		$this->assertTrue($res[1]['distance'] < $res[2]['distance']);
		$this->assertTrue($res[0]['distance'] > 620 && $res[0]['distance'] < 640);
	}

	/**
	 * GeocoderBehaviorTest::testSetDistanceAsVirtualFieldInMiles()
	 *
	 * @return void
	 */
	public function testSetDistanceAsVirtualFieldInMiles() {
		$driver = $this->db->driver();
		$this->skipIf(!($driver instanceof Mysql), 'The virtualFields test is only compatible with Mysql.');

		$this->Addresses->removeBehavior('Geocoder'); //FIXME: Shouldnt be necessary ideally
		$this->Addresses->addBehavior('Geo.Geocoder', ['unit' => Calculator::UNIT_MILES]);

		$options = ['lat' => 13.3, 'lng' => 19.2]; //$options = array('order' => array('Address.distance' => 'ASC'));
		$res = $this->Addresses->find()->find('distance', $options)->find('all')->toArray();

		$this->assertTrue($res[0]['distance'] < $res[1]['distance']);
		$this->assertTrue($res[1]['distance'] < $res[2]['distance']);
		$this->assertTrue($res[0]['distance'] > 390 && $res[0]['distance'] < 410);
	}

	/**
	 * GeocoderBehaviorTest::testPagination()
	 *
	 * @return void
	 */
	public function testPagination() {
		$driver = $this->db->driver();
		$this->skipIf(!($driver instanceof Mysql), 'The virtualFields test is only compatible with Mysql.');

		$this->Controller = new TestController();
		$this->Controller->Addresses->addBehavior('Geo.Geocoder');
		//$this->Controller->Addresses->setDistanceAsVirtualField(13.3, 19.2);
		$options = ['lat' => 13.3, 'lng' => 19.2, 'distance' => 3000];
		// find()->find('distance', $options)->find('all')->toArray()


		$query = $this->Controller->Addresses->find('distance', $options);
		$query->order(['distance' => 'ASC']);

		$res = $this->Controller->paginate($query)->toArray();

		$this->assertEquals(2, count($res));
		$this->assertTrue($res[0]['distance'] < $res[1]['distance']);
	}

	/**
	 * GeocoderBehaviorTest::testValidate()
	 *
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

		$this->Addresses->validator()->add('lat', 'validateLatitude', ['provider' => 'table', 'rule' => 'validateLatitude', 'message' => 'validateLatitudeError']);
		$this->Addresses->validator()->add('lng', 'validateLongitude', ['provider' => 'table', 'rule' => 'validateLongitude', 'message' => 'validateLongitudeError']);
		$data = [
			'lat' => 44,
			'lng' => 190,
		];
		$entity = $this->Addresses->newEntity($data);

		$expectedErrors = [
			'lng' => [
				'validateLongitude' => __('validateLongitudeError')
			]
		];
		$this->assertEquals($expectedErrors, $entity->errors());
	}

	/**
	 * Geocoding tests using the google webservice
	 *
	 * @return void
	 */
	public function testBasic() {
		$driver = $this->db->driver();
		$this->skipIf(!($driver instanceof Mysql), 'The virtualFields test is only compatible with Mysql.');

		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geo.Geocoder', ['address' => ['street', 'zip', 'city']]);

		$data = [
			'street' => 'Krebenweg 22',
			'zip' => '74523',
			'city' => 'Bibersfeld'
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertTrue(!empty($res['lat']) && !empty($res['lng']));
		$this->assertTrue(round($res['lat']) === 49.0 && round($res['lng']) === 10.0);


		// inconclusive
		$data = [
			//'street' => 'Leopoldstraße',
			'city' => 'München'
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertTrue(!empty($res['lat']) && !empty($res['lng']));
		//FIXME
		$this->assertContains('München', $res['formatted_address']);
		//$this->assertEquals('München, Deutschland', $res['formatted_address']);

		$data = [
			'city' => 'Bibersfeld'
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertTrue(!empty($res));
	}

	/**
	 * GeocoderBehaviorTest::testMinAccLow()
	 *
	 * @return void
	 */
	public function testMinAccLow() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geo.Geocoder', ['real' => false, 'minAccuracy' => Geocoder::TYPE_COUNTRY]);
		$data = [
			'city' => 'Deutschland'
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertEquals('Deutschland', $res['city']);
		$this->assertTrue((int)$res['lat'] && (int)$res['lng']);
	}

	/**
	 * GeocoderBehaviorTest::testMinAccHigh()
	 *
	 * @return void
	 */
	public function testMinAccHigh() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geo.Geocoder', ['real' => false, 'minAccuracy' => Geocoder::TYPE_POSTAL]);
		$data = [
			'city' => 'Deutschland'
		];
		$entity = $this->_getEntity($data);

		$res = $this->Addresses->save($entity);
		$this->assertEquals('Deutschland', $res['city']);
		//FIXME
		//$this->assertTrue(!isset($res['lat']) && !isset($res['lng']));
	}

	/**
	 * GeocoderBehaviorTest::testMinInc()
	 *
	 * @return void
	 */
	public function testMinInc() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geo.Geocoder', ['real' => false, 'minAccuracy' => Geocoder::TYPE_SUBLOC]);

		$this->assertEquals(Geocoder::TYPE_SUBLOC, $this->Addresses->behaviors()->Geocoder->config('minAccuracy'));

		$data = [
			//'street' => 'Leopoldstraße',
			'city' => 'Neustadt'
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertEquals('Neustadt', $res['city']);
		//FIXME
		//$this->assertTrue(!isset($res['lat']) && !isset($res['lng']));
	}

	/**
	 * GeocoderBehaviorTest::testMinIncAllowed()
	 *
	 * @return void
	 */
	public function testMinIncAllowed() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geo.Geocoder', ['real' => false, 'allow_inconclusive' => true]);

		$data = [
			'city' => 'Neustadt'
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertEquals('Neustadt', $res['city']);
		$this->assertTrue(!empty($res['lat']) && !empty($res['lng']));
	}

	/**
	 * GeocoderBehaviorTest::testExpect()
	 *
	 * @return void
	 */
	public function testExpect() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geo.Geocoder', ['real' => false, 'expect' => [Geocoder::TYPE_POSTAL]]);

		$data = [
			'city' => 'Berlin, Deutschland'
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);
		$this->assertTrue(empty($res['lat']) && empty($res['lng']));

		$data = [
			'city' => '74523, Deutschland'
		];
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);
		$this->assertContains('74523 Schwäbisch Hall', $res['formatted_address']);
		//$this->assertEquals('74523 Schwäbisch Hall, Deutschland', $res['formatted_address']);
		$this->assertTrue(!empty($res['lat']) && !empty($res['lng']));
	}

	/**
	 * Gets a new Entity
	 *
	 * @return Entity
	 */
	protected function _getEntity($data) {
		return new Entity($data);
	}

}

class TestController extends Controller {

	public $modelClass = 'Geo.Addresses';

}
