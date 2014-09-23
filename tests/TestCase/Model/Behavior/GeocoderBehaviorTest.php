<?php
namespace Geo\Test\Model\Behavior;

use Cake\Utility\Hash;
use Cake\Controller\Controller;
use Cake\TestSuite\TestCase;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Entity;
use Cake\Database\Driver\Mysql;
use Geo\Geocode\Geocode;
use Geo\Model\Behavior\GeocoderBehavior;

class GeocoderBehaviorTest extends TestCase {

	public $fixtures = array(
		'plugin.geo.addresses'
	);

	public $Addresses;

	/**
	 * setUp
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Addresses = TableRegistry::get('Geo.Addresses');
		$this->Addresses->addBehavior('Geo.Geocoder', array('real' => false));

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
		$res = $this->Addresses->distance(12, 14);
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Addresses.lat)) * COS(PI()/2 - RADIANS(90 - 12)) * COS(RADIANS(Addresses.lng) - RADIANS(14)) + SIN(PI()/2 - RADIANS(90 - Addresses.lat)) * SIN(PI()/2 - RADIANS(90 - 12)))';
		$this->assertEquals($expected, $res);

		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geo.Geocoder', array('lat' => 'x', 'lng' => 'y'));
		$res = $this->Addresses->distance(12.1, 14.2);
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Addresses.x)) * COS(PI()/2 - RADIANS(90 - 12.1)) * COS(RADIANS(Addresses.y) - RADIANS(14.2)) + SIN(PI()/2 - RADIANS(90 - Addresses.x)) * SIN(PI()/2 - RADIANS(90 - 12.1)))';
		$this->assertEquals($expected, $res);

		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geo.Geocoder', array('lat' => 'x', 'lng' => 'y'));
		$res = $this->Addresses->distance('User.lat', 'User.lng');
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

		$options = array('lat' => 13.3, 'lng' => 19.2); //array('order' => array('Address.distance' => 'ASC'));
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
		$this->Addresses->addBehavior('Geo.Geocoder', array('unit' => Geocode::UNIT_MILES));
		//$this->Addresses->setDistanceAsVirtualField(13.3, 19.2);

		$options = array('lat' => 13.3, 'lng' => 19.2); //$options = array('order' => array('Address.distance' => 'ASC'));
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
		$this->skipIf(true, 'FIX pagination');

		$this->Controller = new TestController();
		$this->Controller->Addresses->addBehavior('Geo.Geocoder');
		//$this->Controller->Addresses->setDistanceAsVirtualField(13.3, 19.2);
		$options = array('lat' => 13.3, 'lng' => 19.2, 'distance' => 3000);
		// find()->find('distance', $options)->find('all')->toArray()

		$this->Controller->paginate = array(
			'conditions' => array('distance <' => 3000),
			'order' => array('distance' => 'ASC')
		);
		$res = $this->Controller->paginate();
		//debug($res);die();

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


		$driver = $this->db->driver();
		$this->skipIf(!($driver instanceof Mysql), 'The virtualFields test is only compatible with Mysql.');

		$this->Addresses->validator()->add('lat', 'validateLatitude', array('rule' => 'validateLatitude', 'message' => 'validateLatitudeError'));
		$this->Addresses->validator()->add('lng', 'validateLongitude', array('rule' => 'validateLongitude', 'message' => 'validateLongitudeError'));
		$data = array(
			'lat' => 44,
			'lng' => 190,
		);
		$entity = $this->_getEntity($data);

		//FIXME
		return;

		$res = $this->Addresses->validates();
		$this->assertFalse($res);
		$expectedErrors = array(
			'lng' => array(__('validateLongitudeError'))
		);
		$this->assertEquals($expectedErrors, $this->Addresses->validationErrors);
	}

	/**
	 * Geocoding tests using the google webservice
	 *
	 * @return void
	 */
	public function testBasic() {
		$driver = $this->db->driver();
		$this->skipIf(!($driver instanceof Mysql), 'The virtualFields test is only compatible with Mysql.');

		$data = array(
			'street' => 'Krebenweg 22',
			'zip' => '74523',
			'city' => 'Bibersfeld'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertTrue(!empty($res['lat']) && !empty($res['lng']) && round($res['lat']) === 49.0 && round($res['lng']) === 10.0);

		// inconclusive
		$data = array(
			//'street' => 'Leopoldstraße',
			'city' => 'München'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);
		$this->assertEquals('', $this->Addresses->behaviors()->Geocoder->Geocode->error());

		$this->assertTrue(!empty($res['lat']) && !empty($res['lng']));
		$this->assertEquals('München, Deutschland', $res['geocoder_result']['formatted_address']);

		$data = array(
			'city' => 'Bibersfeld'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertTrue(!empty($res));
		$this->assertEquals('', $this->Addresses->behaviors()->Geocoder->Geocode->error());
	}

	/**
	 * GeocoderBehaviorTest::testMinAccLow()
	 *
	 * @return void
	 */
	public function testMinAccLow() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geo.Geocoder', array('real' => false, 'min_accuracy' => Geocode::ACC_COUNTRY));
		$data = array(
			'city' => 'Deutschland'
		);
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
		$this->Addresses->addBehavior('Geo.Geocoder', array('real' => false, 'min_accuracy' => Geocode::ACC_POSTAL));
		$data = array(
			'city' => 'Deutschland'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertEquals('Deutschland', $res['city']);
		$this->assertTrue(!isset($res['lat']) && !isset($res['lng']));
	}

	/**
	 * GeocoderBehaviorTest::testMinInc()
	 *
	 * @return void
	 */
	public function testMinInc() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geo.Geocoder', array('real' => false, 'min_accuracy' => Geocode::ACC_SUBLOC));

		$this->assertEquals(Geocode::ACC_SUBLOC, $this->Addresses->behaviors()->Geocoder->config('min_accuracy'));

		$data = array(
			//'street' => 'Leopoldstraße',
			'city' => 'Neustadt'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertEquals('Neustadt', $res['city']);
		$this->assertTrue(!isset($res['lat']) && !isset($res['lng']));
	}

	/**
	 * GeocoderBehaviorTest::testMinIncAllowed()
	 *
	 * @return void
	 */
	public function testMinIncAllowed() {
		$this->Addresses->removeBehavior('Geocoder');
		$this->Addresses->addBehavior('Geo.Geocoder', array('real' => false, 'allow_inconclusive' => true));

		$data = array(
			'city' => 'Neustadt'
		);
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
		$this->Addresses->addBehavior('Geo.Geocoder', array('real' => false, 'expect' => array('postal_code')));

		$data = array(
			'city' => 'Bibersfeld, Deutschland'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);
		$this->assertTrue(empty($res['lat']) && empty($res['lng']));

		$data = array(
			'city' => '74523, Deutschland'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Addresses->save($entity);

		$this->assertEquals('74523 Schwäbisch Hall, Deutschland', $res['formatted_address']);
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
