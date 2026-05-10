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

}
