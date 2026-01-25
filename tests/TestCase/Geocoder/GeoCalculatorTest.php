<?php

namespace Geo\Test\TestCase\Geocoder;

use Cake\TestSuite\TestCase;
use Geo\Geocoder\GeoCalculator;
use Geo\Geocoder\GeoCoordinate;
use RuntimeException;

class GeoCalculatorTest extends TestCase {

	/**
	 * @return void
	 */
	public function testGetCentralGeoCoordinateEmpty(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No geo coordinates provided');

		GeoCalculator::getCentralGeoCoordinate([]);
	}

	/**
	 * @return void
	 */
	public function testGetCentralGeoCoordinate(): void {
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
