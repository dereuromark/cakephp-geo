<?php

namespace Geo\Test\Geocoder;

use Cake\TestSuite\TestCase;
use Geo\Geocoder\GeoCalculator;
use Geo\Geocoder\GeoCoordinate;

class GeoCalculatorTest extends TestCase {

	/**
	 * @var \Geocoder\Provider\Provider
	 */
	protected $Geocoder;

	/**
	 * @return void
	 */
	public function testGetCentralGeoCoordinate() {
		$coordinates = [
			new GeoCoordinate(48.1, 17.2),
		];
		$result = GeoCalculator::getCentralGeoCoordinate($coordinates);
		$this->assertEquals($coordinates[0], $result);

		$coordinates = [
			new GeoCoordinate(48.1, 17.2),
			new GeoCoordinate(48.5, 17.1),
			new GeoCoordinate(48.8, 16.8),
		];
		$result = GeoCalculator::getCentralGeoCoordinate($coordinates);
		$this->assertWithinRange(48.46, $result->getLatitude(), 0.01);
		$this->assertWithinRange(17.03, $result->getLongitude(), 0.01);
	}

}
