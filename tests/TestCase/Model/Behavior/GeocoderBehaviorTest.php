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
		'core.comment', 'plugin.geo.address'
	);

	public $Comments;

	public $Addresses;

	/**
	 * setUp
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Comments = TableRegistry::get('Comments');
		$this->Comments->addBehavior('Geo.Geocoder', array('real' => false));
	}

	/**
	 * teardown
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Comments, $this->Addresses);
		TableRegistry::clear();
	}

	/**
	 * GeocoderBehaviorTest::testDistance()
	 *
	 * @return void
	 */
	public function testDistance() {
		$res = $this->Comments->distance(12, 14);
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Comments.lat)) * COS(PI()/2 - RADIANS(90 - 12)) * COS(RADIANS(Comments.lng) - RADIANS(14)) + SIN(PI()/2 - RADIANS(90 - Comments.lat)) * SIN(PI()/2 - RADIANS(90 - 12)))';
		$this->assertEquals($expected, $res);

		$this->Comments->removeBehavior('Geocoder');
		$this->Comments->addBehavior('Geo.Geocoder', array('lat' => 'x', 'lng' => 'y'));
		$res = $this->Comments->distance(12.1, 14.2);
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Comments.x)) * COS(PI()/2 - RADIANS(90 - 12.1)) * COS(RADIANS(Comments.y) - RADIANS(14.2)) + SIN(PI()/2 - RADIANS(90 - Comments.x)) * SIN(PI()/2 - RADIANS(90 - 12.1)))';
		$this->assertEquals($expected, $res);

		$this->Comments->removeBehavior('Geocoder');
		$this->Comments->addBehavior('Geo.Geocoder', array('lat' => 'x', 'lng' => 'y'));
		$res = $this->Comments->distance('User.lat', 'User.lng');
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Comments.x)) * COS(PI()/2 - RADIANS(90 - User.lat)) * COS(RADIANS(Comments.y) - RADIANS(User.lng)) + SIN(PI()/2 - RADIANS(90 - Comments.x)) * SIN(PI()/2 - RADIANS(90 - User.lat)))';
		$this->assertEquals($expected, $res);
	}

	/**
	 * GeocoderBehaviorTest::testDistanceField()
	 *
	 * @return void
	 */
	public function testDistanceField() {
		$res = $this->Comments->distanceField(12, 14);
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Comments.lat)) * COS(PI()/2 - RADIANS(90 - 12)) * COS(RADIANS(Comments.lng) - RADIANS(14)) + SIN(PI()/2 - RADIANS(90 - Comments.lat)) * SIN(PI()/2 - RADIANS(90 - 12))) AS Comments.distance';
		$this->assertEquals($expected, $res);
	}

	/**
	 * GeocoderBehaviorTest::testSetDistanceAsVirtualField()
	 *
	 * @return void
	 */
	public function testSetDistanceAsVirtualField() {
		$this->Addresses = TableRegistry::get('Geo.Addresses');
		die(debug($this->Addresses->schema()));
		$this->Addresses->addBehavior('Geo.Geocoder');
		//$this->Addresses->setDistanceAsVirtualField(13.3, 19.2);

		$options = array('lat' => 13.3, 'lng' => 19.2); //array('order' => array('Address.distance' => 'ASC'));
		$res = $this->Addresses->find()->find('distance', $options)->find('all');
		debug($res);die();

		$this->assertTrue($res[0]['Address']['distance'] < $res[1]['Address']['distance']);
		$this->assertTrue($res[1]['Address']['distance'] < $res[2]['Address']['distance']);
		$this->assertTrue($res[0]['Address']['distance'] > 640 && $res[0]['Address']['distance'] < 650);
	}

	/**
	 * GeocoderBehaviorTest::testSetDistanceAsVirtualFieldInMiles()
	 *
	 * @return void
	 */
	public function testSetDistanceAsVirtualFieldInMiles() {
		$this->Addresses = TableRegistry::get('Geo.Addresses');
		$this->Addresses->addBehavior('Geo.Geocoder', array('unit' => Geocode::UNIT_MILES));
		//$this->Addresses->setDistanceAsVirtualField(13.3, 19.2);

		$options = array('lat' => 13.3, 'lng' => 19.2); //$options = array('order' => array('Address.distance' => 'ASC'));
		$res = $this->Addresses->find()->find('distance', $options)->find('all');
		debug($res);die();

		$this->assertTrue($res[0]['Address']['distance'] < $res[1]['Address']['distance']);
		$this->assertTrue($res[1]['Address']['distance'] < $res[2]['Address']['distance']);
		$this->assertTrue($res[0]['Address']['distance'] > 390 && $res[0]['Address']['distance'] < 410);
	}

	/**
	 * GeocoderBehaviorTest::testPagination()
	 *
	 * @return void
	 */
	public function testPagination() {
		$this->Controller = new TestController();
		$this->Controller->Addresses->addBehavior('Geo.Geocoder');
		$this->Controller->Addresses->setDistanceAsVirtualField(13.3, 19.2);
		$options = array('lat' => 13.3, 'lng' => 19.2, 'distance' => 3000);
		// find()->find('distance', $options)->find('all')

		$this->Controller->paginate = array(
			'conditions' => array('distance <' => 3000),
			'order' => array('distance' => 'ASC')
		);
		$res = $this->Controller->paginate();
		debug($res);die();

		$this->assertEquals(2, count($res));
		$this->assertTrue($res[0]['Address']['distance'] < $res[1]['Address']['distance']);
	}

	/**
	 * GeocoderBehaviorTest::testValidate()
	 *
	 * @return void
	 */
	public function testValidate() {
		$is = $this->Comments->validateLatitude(44);
		$this->assertTrue($is);

		$is = $this->Comments->validateLatitude(110);
		$this->assertFalse($is);

		$is = $this->Comments->validateLongitude(150);
		$this->assertTrue($is);

		$is = $this->Comments->validateLongitude(-190);
		$this->assertFalse($is);

		$this->db = ConnectionManager::get('test');
		$driver = $this->db->driver();
		$this->skipIf(!($driver instanceof Mysql), 'The virtualFields test is only compatible with Mysql.');

		$this->Comments->validator()->add('lat', 'validateLatitude', array('rule' => 'validateLatitude', 'message' => 'validateLatitudeError'));
		$this->Comments->validator()->add('lng', 'validateLongitude', array('rule' => 'validateLongitude', 'message' => 'validateLongitudeError'));
		$data = array(
			'lat' => 44,
			'lng' => 190,
		);
		$this->Comments->set($data);
		$res = $this->Comments->validates();
		$this->assertFalse($res);
		$expectedErrors = array(
			'lng' => array(__('validateLongitudeError'))
		);
		$this->assertEquals($expectedErrors, $this->Comments->validationErrors);
	}

	/**
	 * Geocoding tests using the google webservice
	 *
	 * @return void
	 */
	public function testBasic() {
		$this->db = ConnectionManager::get('test');
		$driver = $this->db->driver();
		$this->skipIf(!($this->db instanceof Mysql), 'The virtualFields test is only compatible with Mysql.');

		$data = array(
			'street' => 'Krebenweg 22',
			'zip' => '74523',
			'city' => 'Bibersfeld'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Comments->save($entity);
		$this->debug($res);
		$this->assertTrue(!empty($res['Comment']['lat']) && !empty($res['Comment']['lng']) && round($res['Comment']['lat']) === 49.0 && round($res['Comment']['lng']) === 10.0);

		// inconclusive
		$data = array(
			//'street' => 'Leopoldstraße',
			'city' => 'München'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertEquals('', $this->Comments->behaviors()->Geocoder->Geocode->error());

		//debug($res);
		$this->assertTrue(!empty($res['Comment']['lat']) && !empty($res['Comment']['lng']));
		$this->assertEquals('München, Deutschland', $res['Comment']['geocoder_result']['formatted_address']);

		$data = array(
			'city' => 'Bibersfeld'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Comments->save($entity);
		$this->debug($res);
		$this->assertTrue(!empty($res));
		$this->assertEquals('', $this->Comments->behaviors()->Geocoder->Geocode->error());
	}

	/**
	 * GeocoderBehaviorTest::testMinAccLow()
	 *
	 * @return void
	 */
	public function testMinAccLow() {
		$this->Comments->removeBehavior('Geocoder');
		$this->Comments->addBehavior('Geo.Geocoder', array('real' => false, 'min_accuracy' => Geocode::ACC_COUNTRY));
		$data = array(
			'city' => 'Deutschland'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((int)$res['Comment']['lat'] && (int)$res['Comment']['lng']);
	}

	/**
	 * GeocoderBehaviorTest::testMinAccHigh()
	 *
	 * @return void
	 */
	public function testMinAccHigh() {
		$this->Comments->removeBehavior('Geocoder');
		$this->Comments->addBehavior('Geo.Geocoder', array('real' => false, 'min_accuracy' => Geocode::ACC_POSTAL));
		$data = array(
			'city' => 'Deutschland'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue(!isset($res['Comment']['lat']) && !isset($res['Comment']['lng']));
	}

	/**
	 * GeocoderBehaviorTest::testMinInc()
	 *
	 * @return void
	 */
	public function testMinInc() {
		$this->Comments->removeBehavior('Geocoder');
		$this->Comments->addBehavior('Geo.Geocoder', array('real' => false, 'min_accuracy' => Geocode::ACC_SUBLOC));

		$this->assertEquals(Geocode::ACC_SUBLOC, $this->Comments->behaviors()->Geocoder->config('min_accuracy'));

		$data = array(
			//'street' => 'Leopoldstraße',
			'city' => 'Neustadt'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Comments->save($entity);

		$this->assertTrue(!isset($res['Comment']['lat']) && !isset($res['Comment']['lng']));
	}

	/**
	 * GeocoderBehaviorTest::testMinIncAllowed()
	 *
	 * @return void
	 */
	public function testMinIncAllowed() {
		$this->Comments->removeBehavior('Geocoder');
		$this->Comments->addBehavior('Geo.Geocoder', array('real' => false, 'allow_inconclusive' => true));

		$data = array(
			'city' => 'Neustadt'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Comments->save($entity);

		$this->assertTrue(!empty($res['Comment']['lat']) && !empty($res['Comment']['lng']));
	}

	/**
	 * GeocoderBehaviorTest::testExpect()
	 *
	 * @return void
	 */
	public function testExpect() {
		$this->Comments->removeBehavior('Geocoder');
		$this->Comments->addBehavior('Geo.Geocoder', array('real' => false, 'expect' => array('postal_code')));

		$data = array(
			'city' => 'Bibersfeld'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue(empty($res['Comment']['lat']) && empty($res['Comment']['lng']));

		$data = array(
			'city' => '74523'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue(!empty($res['Comment']['lat']) && !empty($res['Comment']['lng']));
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
