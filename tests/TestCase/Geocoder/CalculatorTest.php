<?php

namespace Geo\Test\TestCase\Geocoder;

use Cake\TestSuite\TestCase;
use Geo\Exception\CalculatorException;
use Geo\Geocoder\Calculator;
use Geo\Geocoder\GeoCoordinate;

class CalculatorTest extends TestCase {

	/**
	 * @var \Geo\Geocoder\Calculator
	 */
	protected $Calculator;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Calculator = new Calculator();
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->Calculator);
	}

	/**
	 * @return void
	 */
	public function testDistance() {
		$coords = [
			['name' => 'MUC/Pforzheim (269km road, 2:33h)', 'x' => ['lat' => 48.1391, 'lng' => 11.5802], 'y' => ['lat' => 48.8934, 'lng' => 8.70492], 'd' => 228],
			['name' => 'MUC/London (1142km road, 11:20h)', 'x' => ['lat' => 48.1391, 'lng' => 11.5802], 'y' => ['lat' => 51.508, 'lng' => -0.124688], 'd' => 919],
			['name' => 'MUC/NewYork (--- road, ---h)', 'x' => ['lat' => 48.1391, 'lng' => 11.5802], 'y' => ['lat' => 40.700943, 'lng' => -73.853531], 'd' => 6479],
		];

		foreach ($coords as $coord) {
			$is = $this->Calculator->distance($coord['x'], $coord['y']);
			$this->assertEquals($coord['d'], $is);
		}

		$is = $this->Calculator->distance($coords[0]['x'], $coords[0]['y'], Calculator::UNIT_MILES);
		$this->assertEquals(142, $is);

		// String directly
		$is = $this->Calculator->distance($coords[0]['x'], $coords[0]['y'], 'F');
		$this->assertEquals(747236, $is);
	}

	/**
	 * GeocodeTest::testBlur()
	 *
	 * @return void
	 */
	public function testBlur() {
		$coords = [
			[48.1391, 1, 0.003],
			[11.5802, 1, 0.003],
			[48.1391, 5, 0.08],
			[11.5802, 5, 0.08],
			[48.1391, 10, 0.5],
			[11.5802, 10, 0.5],
		];
		for ($i = 0; $i < 100; $i++) {
			foreach ($coords as $coord) {
				$is = $this->Calculator->blur($coord[0], $coord[1]);
				$this->assertWithinRange($coord[0], $is, $coord[2], $is . ' instead of ' . $coord[0] . ' (' . $coord[2] . ')');
				$this->assertNotWithinRange($coord[0], $is, $coord[2] / 1000, $is . ' NOT instead of ' . $coord[0] . ' (' . $coord[2] . ')');
			}
		}
	}

	/**
	 * GeocodeTest::testConvert()
	 *
	 * @return void
	 */
	public function testConvert() {
		$values = [
			[3, 'M', 'K', 4.828032],
			[3, 'K', 'M', 1.86411358],
			[100000, 'I', 'K', 2.54],
		];
		foreach ($values as $value) {
			$is = $this->Calculator->convert($value[0], $value[1], $value[2]);
			$this->assertEquals($value[3], round($is, 8));
		}
	}

	/**
	 * @return void
	 */
	public function testBlurWithZeroLevel(): void {
		$coordinate = 48.1391;
		$result = Calculator::blur($coordinate, 0);
		$this->assertSame($coordinate, $result);
	}

	/**
	 * @return void
	 */
	public function testConvertInvalidFromUnit(): void {
		$this->expectException(CalculatorException::class);
		$this->expectExceptionMessage('Invalid Unit');

		$this->Calculator->convert(100, 'INVALID', 'K');
	}

	/**
	 * @return void
	 */
	public function testConvertInvalidToUnit(): void {
		$this->expectException(CalculatorException::class);
		$this->expectExceptionMessage('Invalid Unit');

		$this->Calculator->convert(100, 'K', 'INVALID');
	}

	/**
	 * @return void
	 */
	public function testDistanceInvalidUnit(): void {
		$this->expectException(CalculatorException::class);
		$this->expectExceptionMessage('Invalid Unit: INVALID');

		$pointX = ['lat' => 48.1391, 'lng' => 11.5802];
		$pointY = ['lat' => 48.8934, 'lng' => 8.70492];
		$this->Calculator->distance($pointX, $pointY, 'INVALID');
	}

	/**
	 * @return void
	 */
	public function testCalculateDistanceWithGeoCoordinates(): void {
		$pointX = new GeoCoordinate(48.1391, 11.5802);
		$pointY = new GeoCoordinate(48.8934, 8.70492);

		$result = Calculator::calculateDistance($pointX, $pointY);

		$this->assertGreaterThan(140, $result);
		$this->assertLessThan(150, $result);
	}

	/**
	 * @return void
	 */
	public function testTimezoneByCoordinates(): void {
		// Berlin, Germany (using Berlin's reference coordinates)
		$result = Calculator::timezoneByCoordinates(52.5, 13.4);
		$this->assertSame('Europe/Berlin', $result);

		// New York, USA
		$result = Calculator::timezoneByCoordinates(40.7128, -74.0060);
		$this->assertSame('America/New_York', $result);

		// Tokyo, Japan
		$result = Calculator::timezoneByCoordinates(35.6762, 139.6503);
		$this->assertSame('Asia/Tokyo', $result);

		// Sydney, Australia
		$result = Calculator::timezoneByCoordinates(-33.8688, 151.2093);
		$this->assertSame('Australia/Sydney', $result);

		// London, UK
		$result = Calculator::timezoneByCoordinates(51.5074, -0.1278);
		$this->assertSame('Europe/London', $result);
	}

}
