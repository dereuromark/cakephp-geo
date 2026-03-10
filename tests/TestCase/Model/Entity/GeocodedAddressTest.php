<?php
declare(strict_types=1);

namespace Geo\Test\TestCase\Model\Entity;

use Cake\TestSuite\TestCase;
use Geo\Model\Entity\GeocodedAddress;

/**
 * @uses \Geo\Model\Entity\GeocodedAddress
 */
class GeocodedAddressTest extends TestCase {

	/**
	 * Test entity creation.
	 *
	 * @return void
	 */
	public function testEntityCreation(): void {
		$entity = new GeocodedAddress();

		$this->assertInstanceOf(GeocodedAddress::class, $entity);
	}

	/**
	 * Test entity with data.
	 *
	 * @return void
	 */
	public function testEntityWithData(): void {
		$data = [
			'address' => '123 Main St',
			'formatted_address' => '123 Main Street, City, Country',
			'country' => 'Germany',
			'lat' => 48.8566,
			'lng' => 2.3522,
		];
		$entity = new GeocodedAddress($data);

		$this->assertSame('123 Main St', $entity->address);
		$this->assertSame('123 Main Street, City, Country', $entity->formatted_address);
		$this->assertSame('Germany', $entity->country);
		$this->assertSame(48.8566, $entity->lat);
		$this->assertSame(2.3522, $entity->lng);
	}

	/**
	 * Test that id is not accessible for mass assignment.
	 *
	 * @return void
	 */
	public function testIdNotAccessible(): void {
		$entity = new GeocodedAddress();

		// Check that id is marked as not accessible
		$this->assertFalse($entity->isAccessible('id'));
		// But other fields are accessible
		$this->assertTrue($entity->isAccessible('address'));
		$this->assertTrue($entity->isAccessible('lat'));
	}

	/**
	 * Test that other fields are accessible.
	 *
	 * @return void
	 */
	public function testOtherFieldsAccessible(): void {
		$entity = new GeocodedAddress([
			'address' => 'Test Address',
			'lat' => 50.0,
			'lng' => 10.0,
			'data' => ['key' => 'value'],
		]);

		$this->assertSame('Test Address', $entity->address);
		$this->assertSame(50.0, $entity->lat);
		$this->assertSame(10.0, $entity->lng);
		$this->assertSame(['key' => 'value'], $entity->data);
	}

	/**
	 * Test nullable fields.
	 *
	 * @return void
	 */
	public function testNullableFields(): void {
		$entity = new GeocodedAddress([
			'address' => 'Test',
			'formatted_address' => null,
			'country' => null,
			'lat' => null,
			'lng' => null,
			'data' => null,
		]);

		$this->assertSame('Test', $entity->address);
		$this->assertNull($entity->formatted_address);
		$this->assertNull($entity->country);
		$this->assertNull($entity->lat);
		$this->assertNull($entity->lng);
		$this->assertNull($entity->data);
	}

	/**
	 * Test id can be set directly.
	 *
	 * @return void
	 */
	public function testIdCanBeSetDirectly(): void {
		$entity = new GeocodedAddress();
		$entity->id = 123;

		$this->assertSame(123, $entity->id);
	}

}
