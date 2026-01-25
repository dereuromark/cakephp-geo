<?php

namespace Geo\Test\TestCase\Geocoder;

use Cake\TestSuite\TestCase;
use Geo\Geocoder\GeoCoordinate;
use Geocoder\Model\Coordinates;
use InvalidArgumentException;

class GeoCoordinateTest extends TestCase {

	/**
	 * @return void
	 */
	public function testConstruct(): void {
		$geoCoordinate = new GeoCoordinate(48.2082, 16.3738);

		$this->assertSame(48.2082, $geoCoordinate->getLatitude());
		$this->assertSame(16.3738, $geoCoordinate->getLongitude());
	}

	/**
	 * @return void
	 */
	public function testGetLatitude(): void {
		$geoCoordinate = new GeoCoordinate(51.5074, -0.1278);

		$this->assertSame(51.5074, $geoCoordinate->getLatitude());
	}

	/**
	 * @return void
	 */
	public function testGetLongitude(): void {
		$geoCoordinate = new GeoCoordinate(51.5074, -0.1278);

		$this->assertSame(-0.1278, $geoCoordinate->getLongitude());
	}

	/**
	 * @return void
	 */
	public function testToArray(): void {
		$geoCoordinate = new GeoCoordinate(48.2082, 16.3738);
		$result = $geoCoordinate->toArray();

		$this->assertSame([
			'latitude' => 48.2082,
			'longitude' => 16.3738,
		], $result);
	}

	/**
	 * @return void
	 */
	public function testToArrayAbbreviated(): void {
		$geoCoordinate = new GeoCoordinate(48.2082, 16.3738);
		$result = $geoCoordinate->toArray(true);

		$this->assertSame([
			'lat' => 48.2082,
			'lng' => 16.3738,
		], $result);
	}

	/**
	 * @return void
	 */
	public function testFromArrayWithFullKeys(): void {
		$result = GeoCoordinate::fromArray([
			'latitude' => 48.2082,
			'longitude' => 16.3738,
		]);

		$this->assertSame(48.2082, $result->getLatitude());
		$this->assertSame(16.3738, $result->getLongitude());
	}

	/**
	 * @return void
	 */
	public function testFromArrayWithAbbreviatedKeys(): void {
		$result = GeoCoordinate::fromArray([
			'lat' => 51.5074,
			'lng' => -0.1278,
		]);

		$this->assertSame(51.5074, $result->getLatitude());
		$this->assertSame(-0.1278, $result->getLongitude());
	}

	/**
	 * @return void
	 */
	public function testFromGeocoderCoordinates(): void {
		$coordinates = new Coordinates(40.7128, -74.0060);
		$geoCoordinate = new GeoCoordinate(0, 0);

		$result = $geoCoordinate->fromGeocoderCoordinates($coordinates);

		$this->assertSame(40.7128, $result->getLatitude());
		$this->assertSame(-74.0060, $result->getLongitude());
	}

	/**
	 * @return void
	 */
	public function testToGeocoderCoordinates(): void {
		$geoCoordinate = new GeoCoordinate(40.7128, -74.0060);

		$result = $geoCoordinate->toGeocoderCoordinates();

		$this->assertInstanceOf(Coordinates::class, $result);
		$this->assertSame(40.7128, $result->getLatitude());
		$this->assertSame(-74.0060, $result->getLongitude());
	}

	/**
	 * @return void
	 */
	public function testFromString(): void {
		$result = GeoCoordinate::fromString('48.2082,16.3738');

		$this->assertSame(48.2082, $result->getLatitude());
		$this->assertSame(16.3738, $result->getLongitude());
	}

	/**
	 * @return void
	 */
	public function testFromStringInvalid(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Coordinate must be in format: lat,lng');

		GeoCoordinate::fromString('invalid');
	}

	/**
	 * @return void
	 */
	public function testToString(): void {
		$geoCoordinate = new GeoCoordinate(48.2082, 16.3738);

		$this->assertSame('48.2082,16.3738', $geoCoordinate->toString());
	}

	/**
	 * @return void
	 */
	public function testMagicToString(): void {
		$geoCoordinate = new GeoCoordinate(48.2082, 16.3738);

		$this->assertSame('48.2082,16.3738', (string)$geoCoordinate);
	}

	/**
	 * @return void
	 */
	public function testJsonSerialize(): void {
		$geoCoordinate = new GeoCoordinate(25.43, 12.22);
		$result = json_encode($geoCoordinate);

		$this->assertSame('"25.43,12.22"', $result);
	}

	/**
	 * @return void
	 */
	public function testRoundTrip(): void {
		$original = new GeoCoordinate(48.2082, 16.3738);

		// Convert to JSON and back
		$json = json_encode($original);
		$fromJson = GeoCoordinate::fromString(json_decode($json, true));

		$this->assertSame($original->getLatitude(), $fromJson->getLatitude());
		$this->assertSame($original->getLongitude(), $fromJson->getLongitude());

		// Convert to array and back
		$array = $original->toArray();
		$fromArray = GeoCoordinate::fromArray($array);

		$this->assertSame($original->getLatitude(), $fromArray->getLatitude());
		$this->assertSame($original->getLongitude(), $fromArray->getLongitude());
	}

}
