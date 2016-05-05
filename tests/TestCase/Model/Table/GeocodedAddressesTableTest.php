<?php
namespace Geo\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Geo\Model\Table\GeocodedAddressesTable Test Case
 */
class GeocodedAddressesTableTest extends TestCase {

	/**
	 * Test subject
	 *
	 * @var \Geo\Model\Table\GeocodedAddressesTable
	 */
	public $GeocodedAddresses;

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = [
		'plugin.geo.geocoded_addresses'
	];

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$config = TableRegistry::exists('GeocodedAddresses') ? [] : ['className' => 'Geo\Model\Table\GeocodedAddressesTable'];
		$this->GeocodedAddresses = TableRegistry::get('GeocodedAddresses', $config);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->GeocodedAddresses);

		parent::tearDown();
	}

	/**
	 * Test initialize method
	 *
	 * @return void
	 */
	public function testInitialize() {
		$this->markTestIncomplete('Not implemented yet.');
	}

	/**
	 * Test validationDefault method
	 *
	 * @return void
	 */
	public function testValidationDefault() {
		$this->markTestIncomplete('Not implemented yet.');
	}

	/**
	 * Test buildRules method
	 *
	 * @return void
	 */
	public function testBuildRules() {
		$this->markTestIncomplete('Not implemented yet.');
	}

}
