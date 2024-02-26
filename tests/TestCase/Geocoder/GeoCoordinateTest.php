<?php

namespace Geo\Test\Geocoder;

use Cake\TestSuite\TestCase;
use Geo\Geocoder\GeoCoordinate;

class GeoCoordinateTest extends TestCase {

	/**
	 * @return void
	 */
	public function testSerialize() {
		$geoCoordinate = new GeoCoordinate(25.43, 12.22);
		$result = json_encode($geoCoordinate);

		$this->assertSame('"25.43,12.22"', $result);
	}

	/**
	 * @return void
	 */
	public function testDeserialize() {
		$coordinate = json_decode('"25.43,12.22"', true);
		$result = GeoCoordinate::fromString($coordinate);

		$this->assertSame('25.43,12.22', (string)$result);
	}

}
