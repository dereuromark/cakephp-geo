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

	public function setUp() {
		parent::setUp();

		$this->Comment = TableRegistry::get('Comments');

		$this->Comment->addBehavior('Geo.Geocoder', array('real' => false));
	}

	/**
	 * GeocoderBehaviorTest::testDistance()
	 *
	 * @return void
	 */
	public function testDistance() {
		$res = $this->Comment->distance(12, 14);
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Comment.lat)) * COS(PI()/2 - RADIANS(90 - 12)) * COS(RADIANS(Comment.lng) - RADIANS(14)) + SIN(PI()/2 - RADIANS(90 - Comment.lat)) * SIN(PI()/2 - RADIANS(90 - 12)))';
		$this->assertEquals($expected, $res);

		$this->Comment->removeBehavior('Geocoder');
		$this->Comment->addBehavior('Geo.Geocoder', array('lat' => 'x', 'lng' => 'y'));
		$res = $this->Comment->distance(12.1, 14.2);
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Comment.x)) * COS(PI()/2 - RADIANS(90 - 12.1)) * COS(RADIANS(Comment.y) - RADIANS(14.2)) + SIN(PI()/2 - RADIANS(90 - Comment.x)) * SIN(PI()/2 - RADIANS(90 - 12.1)))';
		$this->assertEquals($expected, $res);

		$this->Comment->removeBehavior('Geocoder');
		$this->Comment->addBehavior('Geo.Geocoder', array('lat' => 'x', 'lng' => 'y'));
		$res = $this->Comment->distance('User.lat', 'User.lng');
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Comment.x)) * COS(PI()/2 - RADIANS(90 - User.lat)) * COS(RADIANS(Comment.y) - RADIANS(User.lng)) + SIN(PI()/2 - RADIANS(90 - Comment.x)) * SIN(PI()/2 - RADIANS(90 - User.lat)))';
		$this->assertEquals($expected, $res);
	}

	/**
	 * GeocoderBehaviorTest::testDistanceField()
	 *
	 * @return void
	 */
	public function testDistanceField() {
		$res = $this->Comment->distanceField(12, 14);
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Comment.lat)) * COS(PI()/2 - RADIANS(90 - 12)) * COS(RADIANS(Comment.lng) - RADIANS(14)) + SIN(PI()/2 - RADIANS(90 - Comment.lat)) * SIN(PI()/2 - RADIANS(90 - 12))) AS Comment.distance';
		$this->assertEquals($expected, $res);
	}

	/**
	 * GeocoderBehaviorTest::testSetDistanceAsVirtualField()
	 *
	 * @return void
	 */
	public function testSetDistanceAsVirtualField() {
		$this->Address = TableRegistry::get('Addresses');
		$this->Address->addBehavior('Geo.Geocoder');
		$this->Address->setDistanceAsVirtualField(13.3, 19.2);
		$options = array('order' => array('Address.distance' => 'ASC'));
		$res = $this->Address->find('all', $options);
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
		$this->Address = TableRegistry::get('Addresses');
		$this->Address->addBehavior('Geo.Geocoder', array('unit' => Geocode::UNIT_MILES));
		$this->Address->setDistanceAsVirtualField(13.3, 19.2);
		$options = array('order' => array('Address.distance' => 'ASC'));
		$res = $this->Address->find('all', $options);
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
		$this->Controller->Address->addBehavior('Geo.Geocoder');
		$this->Controller->Address->setDistanceAsVirtualField(13.3, 19.2);
		$this->Controller->paginate = array(
			'conditions' => array('distance <' => 3000),
			'order' => array('distance' => 'ASC')
		);
		$res = $this->Controller->paginate();
		$this->assertEquals(2, count($res));
		$this->assertTrue($res[0]['Address']['distance'] < $res[1]['Address']['distance']);
	}

	/**
	 * GeocoderBehaviorTest::testValidate()
	 *
	 * @return void
	 */
	public function testValidate() {
		$is = $this->Comment->validateLatitude(44);
		$this->assertTrue($is);

		$is = $this->Comment->validateLatitude(110);
		$this->assertFalse($is);

		$is = $this->Comment->validateLongitude(150);
		$this->assertTrue($is);

		$is = $this->Comment->validateLongitude(-190);
		$this->assertFalse($is);

		$this->db = ConnectionManager::get('test');
		$driver = $this->db->driver();
		$this->skipIf(!($driver instanceof Mysql), 'The virtualFields test is only compatible with Mysql.');

		$this->Comment->validator()->add('lat', 'validateLatitude', array('rule' => 'validateLatitude', 'message' => 'validateLatitudeError'));
		$this->Comment->validator()->add('lng', 'validateLongitude', array('rule' => 'validateLongitude', 'message' => 'validateLongitudeError'));
		$data = array(
			'lat' => 44,
			'lng' => 190,
		);
		$this->Comment->set($data);
		$res = $this->Comment->validates();
		$this->assertFalse($res);
		$expectedErrors = array(
			'lng' => array(__('validateLongitudeError'))
		);
		$this->assertEquals($expectedErrors, $this->Comment->validationErrors);
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
		$res = $this->Comment->save($entity);
		$this->debug($res);
		$this->assertTrue(!empty($res['Comment']['lat']) && !empty($res['Comment']['lng']) && round($res['Comment']['lat']) === 49.0 && round($res['Comment']['lng']) === 10.0);

		// inconclusive
		$data = array(
			//'street' => 'Leopoldstraße',
			'city' => 'München'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Comment->save($entity);
		$this->assertEquals('', $this->Comment->behaviors()->Geocoder->Geocode->error());

		//debug($res);
		$this->assertTrue(!empty($res['Comment']['lat']) && !empty($res['Comment']['lng']));
		$this->assertEquals('München, Deutschland', $res['Comment']['geocoder_result']['formatted_address']);

		$data = array(
			'city' => 'Bibersfeld'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Comment->save($entity);
		$this->debug($res);
		$this->assertTrue(!empty($res));
		$this->assertEquals('', $this->Comment->behaviors()->Geocoder->Geocode->error());
	}

	/**
	 * GeocoderBehaviorTest::testMinAccLow()
	 *
	 * @return void
	 */
	public function testMinAccLow() {
		$this->Comment->removeBehavior('Geocoder');
		$this->Comment->addBehavior('Geo.Geocoder', array('real' => false, 'min_accuracy' => Geocode::ACC_COUNTRY));
		$data = array(
			'city' => 'Deutschland'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Comment->save($entity);
		$this->assertTrue((int)$res['Comment']['lat'] && (int)$res['Comment']['lng']);
	}

	/**
	 * GeocoderBehaviorTest::testMinAccHigh()
	 *
	 * @return void
	 */
	public function testMinAccHigh() {
		$this->Comment->removeBehavior('Geocoder');
		$this->Comment->addBehavior('Geo.Geocoder', array('real' => false, 'min_accuracy' => Geocode::ACC_POSTAL));
		$data = array(
			'city' => 'Deutschland'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Comment->save($entity);
		$this->assertTrue(!isset($res['Comment']['lat']) && !isset($res['Comment']['lng']));
	}

	/**
	 * GeocoderBehaviorTest::testMinInc()
	 *
	 * @return void
	 */
	public function testMinInc() {
		$this->Comment->removeBehavior('Geocoder');
		$this->Comment->addBehavior('Geo.Geocoder', array('real' => false, 'min_accuracy' => Geocode::ACC_SUBLOC));

		$this->assertEquals(Geocode::ACC_SUBLOC, $this->Comment->behaviors()->Geocoder->config('min_accuracy'));

		$data = array(
			//'street' => 'Leopoldstraße',
			'city' => 'Neustadt'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Comment->save($entity);

		$this->assertTrue(!isset($res['Comment']['lat']) && !isset($res['Comment']['lng']));
	}

	/**
	 * GeocoderBehaviorTest::testMinIncAllowed()
	 *
	 * @return void
	 */
	public function testMinIncAllowed() {
		$this->Comment->removeBehavior('Geocoder');
		$this->Comment->addBehavior('Geo.Geocoder', array('real' => false, 'allow_inconclusive' => true));

		$data = array(
			'city' => 'Neustadt'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Comment->save($entity);

		$this->assertTrue(!empty($res['Comment']['lat']) && !empty($res['Comment']['lng']));
	}

	/**
	 * GeocoderBehaviorTest::testExpect()
	 *
	 * @return void
	 */
	public function testExpect() {
		$this->Comment->removeBehavior('Geocoder');
		$this->Comment->addBehavior('Geo.Geocoder', array('real' => false, 'expect' => array('postal_code')));

		$data = array(
			'city' => 'Bibersfeld'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Comment->save($entity);
		$this->assertTrue(empty($res['Comment']['lat']) && empty($res['Comment']['lng']));

		$data = array(
			'city' => '74523'
		);
		$entity = $this->_getEntity($data);
		$res = $this->Comment->save($entity);
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

	public $modelClass = 'Address';

}
