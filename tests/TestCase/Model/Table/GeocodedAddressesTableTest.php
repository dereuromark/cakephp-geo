<?php

namespace Geo\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Geo\Model\Table\GeocodedAddressesTable Test Case
 */
class GeocodedAddressesTableTest extends TestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Geo.GeocodedAddresses',
	];

	/**
	 * @var \Geo\Model\Table\GeocodedAddressesTable
	 */
	protected $GeocodedAddresses;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$config = TableRegistry::getTableLocator()->exists('GeocodedAddresses') ? [] : ['className' => 'Geo\Model\Table\GeocodedAddressesTable'];
		$this->GeocodedAddresses = TableRegistry::getTableLocator()->get('GeocodedAddresses', $config);
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		unset($this->GeocodedAddresses);

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testSave() {
		$geocodedAddress = $this->GeocodedAddresses->newEntity([
			'address' => 'Berlin',
			'lat' => 12,
			'lng' => 11,
		]);
		$geocodedAddress = $this->GeocodedAddresses->save($geocodedAddress);
		$this->assertNotEmpty($geocodedAddress);
	}

}
