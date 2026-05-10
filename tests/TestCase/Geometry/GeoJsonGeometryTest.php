<?php

namespace Geo\Test\TestCase\Geometry;

use Cake\TestSuite\TestCase;
use Geo\Geometry\Feature;
use Geo\Geometry\FeatureCollection;
use Geo\Geometry\Point;
use Geo\Geometry\Polygon;

class GeoJsonGeometryTest extends TestCase {

	public function testPointToGeoJsonArray(): void {
		$point = new Point(16.3738, 48.2082);

		$expected = [
			'type' => 'Point',
			'coordinates' => [16.3738, 48.2082],
		];
		$this->assertSame($expected, $point->toGeoJsonArray());
		$this->assertSame(['lat' => 48.2082, 'lng' => 16.3738], $point->toLatLngArray());
	}

	public function testPointNamedConstructorsAndAccessors(): void {
		$point = Point::fromLatLng(48.2082, 16.3738);

		$this->assertSame(16.3738, $point->getLongitude());
		$this->assertSame(48.2082, $point->getLatitude());
		$this->assertSame($point->toGeoJsonArray(), $point->jsonSerialize());

		$pointFromCoordinates = Point::fromCoordinates([16.4, 48.3]);
		$this->assertSame(16.4, $pointFromCoordinates->getLongitude());
		$this->assertSame(48.3, $pointFromCoordinates->getLatitude());
	}

	public function testPolygonFromLatLngPointsClosesRing(): void {
		$polygon = Polygon::fromLatLngPoints([
			['lat' => 48.2, 'lng' => 16.3],
			['lat' => 48.3, 'lng' => 16.4],
			['lat' => 48.1, 'lng' => 16.5],
		]);

		$coordinates = $polygon->toGeoJsonArray()['coordinates'];
		$this->assertCount(1, $coordinates);
		$this->assertSame([16.3, 48.2], $coordinates[0][0]);
		$this->assertSame([16.3, 48.2], $coordinates[0][3]);
	}

	public function testFeatureCollectionToGeoJsonArray(): void {
		$collection = new FeatureCollection([
			new Feature(new Point(16.3738, 48.2082), ['name' => 'Vienna']),
			new Feature(Polygon::fromLatLngPoints([
				['lat' => 48.2, 'lng' => 16.3],
				['lat' => 48.3, 'lng' => 16.4],
				['lat' => 48.1, 'lng' => 16.5],
			])),
		]);

		$result = $collection->toGeoJsonArray();

		$this->assertSame('FeatureCollection', $result['type']);
		$this->assertCount(2, $result['features']);
		$this->assertSame('Point', $result['features'][0]['geometry']['type']);
		$this->assertSame('Polygon', $result['features'][1]['geometry']['type']);
	}

	public function testFeatureAndCollectionAccessors(): void {
		$feature = new Feature(new Point(16.3738, 48.2082), ['name' => 'Vienna']);

		$this->assertSame('Vienna', $feature->getProperties()['name']);
		$this->assertInstanceOf(Point::class, $feature->getGeometry());
		$this->assertSame($feature->toGeoJsonArray(), $feature->jsonSerialize());

		$collection = new FeatureCollection([$feature]);
		$this->assertCount(1, $collection->getFeatures());
		$this->assertSame($collection->toGeoJsonArray(), $collection->jsonSerialize());
	}

}
