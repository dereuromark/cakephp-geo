<?php

namespace Geo\Test\TestCase\Geocoder;

use Cake\TestSuite\TestCase;
use Geo\Geocoder\CachedLocation;

class CachedLocationTest extends TestCase {

	/**
	 * @return void
	 */
	public function testCreateEmpty(): void {
		$location = new CachedLocation();

		$this->assertNull($location->getCoordinates());
		$this->assertNull($location->getBounds());
		$this->assertNull($location->getStreetNumber());
		$this->assertNull($location->getStreetName());
		$this->assertNull($location->getLocality());
		$this->assertNull($location->getPostalCode());
		$this->assertNull($location->getSubLocality());
		$this->assertNull($location->getCountry());
		$this->assertNull($location->getTimezone());
		$this->assertSame('cached', $location->getProvidedBy());
		$this->assertCount(0, $location->getAdminLevels());
	}

	/**
	 * @return void
	 */
	public function testCreateFromArray(): void {
		$data = [
			'providedBy' => 'google_maps',
			'latitude' => 48.8566,
			'longitude' => 2.3522,
			'streetNumber' => '123',
			'streetName' => 'Main Street',
			'locality' => 'Paris',
			'postalCode' => '75001',
			'subLocality' => 'District 1',
			'country' => 'France',
			'countryCode' => 'FR',
			'timezone' => 'Europe/Paris',
			'bounds' => [
				'south' => 48.8,
				'west' => 2.3,
				'north' => 48.9,
				'east' => 2.4,
			],
			'adminLevels' => [
				['level' => 1, 'name' => 'Île-de-France', 'code' => 'IDF'],
				['level' => 2, 'name' => 'Paris', 'code' => '75'],
			],
		];

		$location = CachedLocation::createFromArray($data);

		$this->assertSame('google_maps', $location->getProvidedBy());
		$this->assertNotNull($location->getCoordinates());
		$this->assertSame(48.8566, $location->getCoordinates()->getLatitude());
		$this->assertSame(2.3522, $location->getCoordinates()->getLongitude());
		$this->assertSame('123', $location->getStreetNumber());
		$this->assertSame('Main Street', $location->getStreetName());
		$this->assertSame('Paris', $location->getLocality());
		$this->assertSame('75001', $location->getPostalCode());
		$this->assertSame('District 1', $location->getSubLocality());
		$this->assertSame('Europe/Paris', $location->getTimezone());

		$this->assertNotNull($location->getCountry());
		$this->assertSame('France', $location->getCountry()->getName());
		$this->assertSame('FR', $location->getCountry()->getCode());

		$this->assertNotNull($location->getBounds());
		$this->assertSame(48.8, $location->getBounds()->getSouth());
		$this->assertSame(2.3, $location->getBounds()->getWest());
		$this->assertSame(48.9, $location->getBounds()->getNorth());
		$this->assertSame(2.4, $location->getBounds()->getEast());

		$this->assertCount(2, $location->getAdminLevels());
	}

	/**
	 * @return void
	 */
	public function testToArray(): void {
		$data = [
			'providedBy' => 'nominatim',
			'latitude' => 52.52,
			'longitude' => 13.405,
			'streetNumber' => '1',
			'streetName' => 'Unter den Linden',
			'locality' => 'Berlin',
			'postalCode' => '10117',
			'subLocality' => 'Mitte',
			'country' => 'Germany',
			'countryCode' => 'DE',
			'timezone' => 'Europe/Berlin',
			'bounds' => [
				'south' => 52.0,
				'west' => 13.0,
				'north' => 53.0,
				'east' => 14.0,
			],
			'adminLevels' => [
				['level' => 1, 'name' => 'Berlin', 'code' => 'BE'],
			],
		];

		$location = new CachedLocation($data);
		$result = $location->toArray();

		$this->assertSame('nominatim', $result['providedBy']);
		$this->assertSame(52.52, $result['latitude']);
		$this->assertSame(13.405, $result['longitude']);
		$this->assertSame('1', $result['streetNumber']);
		$this->assertSame('Unter den Linden', $result['streetName']);
		$this->assertSame('Berlin', $result['locality']);
		$this->assertSame('10117', $result['postalCode']);
		$this->assertSame('Mitte', $result['subLocality']);
		$this->assertSame('Germany', $result['country']);
		$this->assertSame('DE', $result['countryCode']);
		$this->assertSame('Europe/Berlin', $result['timezone']);
		$this->assertSame(52.0, $result['bounds']['south']);
		$this->assertCount(1, $result['adminLevels']);
		$this->assertSame(1, $result['adminLevels'][0]['level']);
		$this->assertSame('Berlin', $result['adminLevels'][0]['name']);
		$this->assertSame('BE', $result['adminLevels'][0]['code']);
	}

	/**
	 * @return void
	 */
	public function testRoundTrip(): void {
		$originalData = [
			'providedBy' => 'geoapify',
			'latitude' => 40.7128,
			'longitude' => -74.0060,
			'streetNumber' => '350',
			'streetName' => '5th Avenue',
			'locality' => 'New York',
			'postalCode' => '10118',
			'subLocality' => 'Manhattan',
			'country' => 'United States',
			'countryCode' => 'US',
			'timezone' => 'America/New_York',
			'bounds' => [
				'south' => 40.5,
				'west' => -74.5,
				'north' => 41.0,
				'east' => -73.5,
			],
			'adminLevels' => [
				['level' => 1, 'name' => 'New York', 'code' => 'NY'],
				['level' => 2, 'name' => 'New York County', 'code' => null],
			],
		];

		$location = CachedLocation::createFromArray($originalData);
		$exportedData = $location->toArray();

		$this->assertSame($originalData['providedBy'], $exportedData['providedBy']);
		$this->assertSame($originalData['latitude'], $exportedData['latitude']);
		$this->assertSame($originalData['longitude'], $exportedData['longitude']);
		$this->assertSame($originalData['streetNumber'], $exportedData['streetNumber']);
		$this->assertSame($originalData['locality'], $exportedData['locality']);
		$this->assertSame($originalData['country'], $exportedData['country']);
		$this->assertSame($originalData['countryCode'], $exportedData['countryCode']);
	}

	/**
	 * @return void
	 */
	public function testPartialData(): void {
		$data = [
			'latitude' => 51.5074,
			'longitude' => -0.1278,
			'locality' => 'London',
		];

		$location = new CachedLocation($data);

		$this->assertNotNull($location->getCoordinates());
		$this->assertSame(51.5074, $location->getCoordinates()->getLatitude());
		$this->assertSame('London', $location->getLocality());
		$this->assertNull($location->getBounds());
		$this->assertNull($location->getCountry());
		$this->assertNull($location->getStreetName());
		$this->assertSame('cached', $location->getProvidedBy());
	}

	/**
	 * @return void
	 */
	public function testCountryWithoutCode(): void {
		$data = [
			'country' => 'Japan',
		];

		$location = new CachedLocation($data);

		$this->assertNotNull($location->getCountry());
		$this->assertSame('Japan', $location->getCountry()->getName());
		$this->assertNull($location->getCountry()->getCode());
	}

	/**
	 * @return void
	 */
	public function testCountryCodeOnly(): void {
		$data = [
			'countryCode' => 'JP',
		];

		$location = new CachedLocation($data);

		$this->assertNotNull($location->getCountry());
		$this->assertNull($location->getCountry()->getName());
		$this->assertSame('JP', $location->getCountry()->getCode());
	}

	/**
	 * @return void
	 */
	public function testAdminLevelsWithMissingLevel(): void {
		$data = [
			'adminLevels' => [
				['name' => 'Missing level key', 'code' => 'XX'],
				['level' => 1, 'name' => 'Valid', 'code' => 'V'],
			],
		];

		$location = new CachedLocation($data);

		$this->assertCount(1, $location->getAdminLevels());
	}

	/**
	 * @return void
	 */
	public function testNumericStreetNumber(): void {
		$data = [
			'streetNumber' => 42,
		];

		$location = new CachedLocation($data);

		$this->assertSame(42, $location->getStreetNumber());
	}

}
