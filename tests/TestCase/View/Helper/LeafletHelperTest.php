<?php

namespace Geo\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Geo\View\Helper\LeafletHelper;

class LeafletHelperTest extends TestCase {

	/**
	 * @var \Geo\View\Helper\LeafletHelper
	 */
	protected LeafletHelper $Leaflet;

	/**
	 * @var \Cake\View\View
	 */
	protected View $View;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Configure::delete('Leaflet');

		$this->View = new View(null);
		$this->Leaflet = new LeafletHelper($this->View);

		LeafletHelper::$mapCount = 0;
		LeafletHelper::$markerCount = 0;
		LeafletHelper::$popupCount = 0;
	}

	/**
	 * @return void
	 */
	public function testConfigMergeDefaults(): void {
		$config = [
			'zoom' => 10,
			'lat' => 48.1,
			'lng' => 11.5,
		];
		$this->Leaflet = new LeafletHelper($this->View, $config);

		$result = $this->Leaflet->getConfig();
		$this->assertSame(10, $result['map']['zoom']);
		$this->assertSame(48.1, $result['map']['lat']);
		$this->assertSame(11.5, $result['map']['lng']);
	}

	/**
	 * @return void
	 */
	public function testConfigMergeDeep(): void {
		$config = [
			'map' => [
				'scrollWheelZoom' => false,
			],
		];
		Configure::write('Leaflet.zoom', 8);
		$this->Leaflet = new LeafletHelper($this->View, $config);

		$result = $this->Leaflet->getConfig();
		$this->assertSame(8, $result['map']['zoom']);
		$this->assertFalse($result['map']['scrollWheelZoom']);
	}

	/**
	 * @return void
	 */
	public function testMap(): void {
		$options = [
			'zoom' => 10,
			'lat' => 48.2082,
			'lng' => 16.3738,
		];

		$result = $this->Leaflet->map($options);
		$result .= $this->Leaflet->script();

		$expected = '<div id="map" class="leaflet-map"';
		$this->assertTextContains($expected, $result);

		$expected = 'var map0 = L.map("map"';
		$this->assertTextContains($expected, $result);

		$expected = 'map0.setView([48.2082, 16.3738], 10)';
		$this->assertTextContains($expected, $result);

		$expected = 'L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"';
		$this->assertTextContains($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testMapCustomId(): void {
		$options = [
			'div' => ['id' => 'custom-map'],
		];

		$result = $this->Leaflet->map($options);
		$result .= $this->Leaflet->script();

		$expected = '<div id="custom-map"';
		$this->assertTextContains($expected, $result);

		$expected = 'L.map("custom-map"';
		$this->assertTextContains($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testMapAutoScript(): void {
		$options = [
			'autoScript' => true,
			'block' => false,
		];

		$result = $this->Leaflet->map($options);

		$expected = 'leaflet.css';
		$this->assertTextContains($expected, $result);

		$expected = 'leaflet.js';
		$this->assertTextContains($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testAddMarker(): void {
		$this->Leaflet->map();
		$markerCount = $this->Leaflet->addMarker([
			'lat' => 48.2082,
			'lng' => 16.3738,
			'title' => 'Vienna',
		]);

		$result = $this->Leaflet->script();

		$this->assertSame(0, $markerCount);
		$this->assertTextContains('L.marker([48.2082, 16.3738]', $result);
		$this->assertTextContains('"title":"Vienna"', $result);
		$this->assertTextContains('.addTo(map0)', $result);
		$this->assertTextContains('lMarkers0.push(x0)', $result);
	}

	/**
	 * @return void
	 */
	public function testAddMarkerWithPopup(): void {
		$this->Leaflet->map();
		$this->Leaflet->addMarker([
			'lat' => 48.2082,
			'lng' => 16.3738,
			'content' => '<b>Vienna</b>',
		]);

		$result = $this->Leaflet->script();

		$this->assertTextContains('x0.bindPopup("<b>Vienna<\\/b>")', $result);
	}

	/**
	 * @return void
	 */
	public function testAddMarkerOpenPopup(): void {
		$this->Leaflet->map();
		$this->Leaflet->addMarker([
			'lat' => 48.2082,
			'lng' => 16.3738,
			'content' => 'Test content',
			'open' => true,
		]);

		$result = $this->Leaflet->script();

		$this->assertTextContains('x0.openPopup()', $result);
	}

	/**
	 * @return void
	 */
	public function testAddPopup(): void {
		$this->Leaflet->map();
		$popupCount = $this->Leaflet->addPopup([
			'lat' => 48.2082,
			'lng' => 16.3738,
			'content' => 'Hello World',
		]);

		$result = $this->Leaflet->script();

		$this->assertSame(0, $popupCount);
		$this->assertTextContains('L.popup(', $result);
		$this->assertTextContains('.setLatLng([48.2082, 16.3738])', $result);
		$this->assertTextContains('.setContent("Hello World")', $result);
		$this->assertTextContains('.openOn(map0)', $result);
	}

	/**
	 * @return void
	 */
	public function testAddPolyline(): void {
		$this->Leaflet->map();
		$this->Leaflet->addPolyline(
			['lat' => 48.2082, 'lng' => 16.3738],
			['lat' => 47.0707, 'lng' => 15.4395],
		);

		$result = $this->Leaflet->script();

		$this->assertTextContains('L.polyline([[48.2082, 16.3738], [47.0707, 15.4395]]', $result);
		$this->assertTextContains('"color":"#3388ff"', $result);
		$this->assertTextContains('"weight":3', $result);
	}

	/**
	 * @return void
	 */
	public function testAddPolylineFromPoints(): void {
		$points = [
			['lat' => 48.2082, 'lng' => 16.3738],
			['lat' => 47.0707, 'lng' => 15.4395],
			['lat' => 46.0569, 'lng' => 14.5058],
		];
		$this->Leaflet->map();
		$this->Leaflet->addPolylineFromPoints($points, ['color' => '#ff0000']);

		$result = $this->Leaflet->script();

		$this->assertTextContains('L.polyline([[48.2082, 16.3738], [47.0707, 15.4395], [46.0569, 14.5058]]', $result);
		$this->assertTextContains('"color":"#ff0000"', $result);
	}

	/**
	 * @return void
	 */
	public function testAddCircle(): void {
		$this->Leaflet->map();
		$this->Leaflet->addCircle([
			'lat' => 48.2082,
			'lng' => 16.3738,
			'radius' => 1000,
			'color' => '#ff0000',
		]);

		$result = $this->Leaflet->script();

		$this->assertTextContains('L.circle([48.2082, 16.3738]', $result);
		$this->assertTextContains('"radius":1000', $result);
		$this->assertTextContains('"color":"#ff0000"', $result);
	}

	/**
	 * @return void
	 */
	public function testAddPolygon(): void {
		$points = [
			['lat' => 48.2, 'lng' => 16.3],
			['lat' => 48.3, 'lng' => 16.4],
			['lat' => 48.1, 'lng' => 16.5],
		];
		$this->Leaflet->map();
		$this->Leaflet->addPolygon($points);

		$result = $this->Leaflet->script();

		$this->assertTextContains('L.polygon([[48.2, 16.3], [48.3, 16.4], [48.1, 16.5]]', $result);
	}

	/**
	 * @return void
	 */
	public function testAddGeoJson(): void {
		$geoJson = [
			'type' => 'Feature',
			'geometry' => [
				'type' => 'Point',
				'coordinates' => [16.3738, 48.2082],
			],
		];
		$this->Leaflet->map();
		$this->Leaflet->addGeoJson($geoJson);

		$result = $this->Leaflet->script();

		$this->assertTextContains('L.geoJSON(', $result);
		$this->assertTextContains('"type":"Feature"', $result);
	}

	/**
	 * @return void
	 */
	public function testUseTilePreset(): void {
		$this->Leaflet->useTilePreset(LeafletHelper::TILES_CARTO_DARK);
		$this->Leaflet->map();

		$result = $this->Leaflet->script();

		$this->assertTextContains('basemaps.cartocdn.com/dark_all', $result);
		$this->assertTextContains('CARTO', $result);
	}

	/**
	 * @return void
	 */
	public function testUseTilePresetInvalid(): void {
		$this->Leaflet->useTilePreset('invalid_preset');
		$this->Leaflet->map();

		$result = $this->Leaflet->script();

		// Should fall back to default OSM tiles
		$this->assertTextContains('tile.openstreetmap.org', $result);
	}

	/**
	 * @return void
	 */
	public function testAddTileLayer(): void {
		$this->Leaflet->map();
		$this->Leaflet->addTileLayer(
			'https://custom.tiles/{z}/{x}/{y}.png',
			['attribution' => 'Custom'],
		);

		$result = $this->Leaflet->script();

		$this->assertTextContains('L.tileLayer("https://custom.tiles/{z}/{x}/{y}.png"', $result);
		$this->assertTextContains('"attribution":"Custom"', $result);
	}

	/**
	 * @return void
	 */
	public function testReset(): void {
		$this->Leaflet->map();
		$this->Leaflet->addMarker(['lat' => 48.2, 'lng' => 16.3]);
		$this->Leaflet->addMarker(['lat' => 47.0, 'lng' => 15.4]);
		$this->Leaflet->finalize();

		// Reset for second map
		$this->Leaflet->reset();

		$this->assertSame(0, LeafletHelper::$markerCount);
		$this->assertEmpty($this->Leaflet->markers);
	}

	/**
	 * @return void
	 */
	public function testMultipleMaps(): void {
		// First map
		$result1 = $this->Leaflet->map(['div' => ['id' => 'map1']]);
		$this->Leaflet->addMarker(['lat' => 48.2, 'lng' => 16.3]);
		$result1 .= $this->Leaflet->script();

		// Second map
		$result2 = $this->Leaflet->map(['div' => ['id' => 'map2']]);
		$this->Leaflet->addMarker(['lat' => 47.0, 'lng' => 15.4]);
		$result2 .= $this->Leaflet->script();

		$this->assertTextContains('id="map1"', $result1);
		$this->assertTextContains('var map0 = L.map("map1"', $result1);
		$this->assertTextContains('lMarkers0.push(x0)', $result1);

		$this->assertTextContains('id="map2"', $result2);
		$this->assertTextContains('var map1 = L.map("map2"', $result2);
		$this->assertTextContains('lMarkers1.push(x0)', $result2);
	}

	/**
	 * @return void
	 */
	public function testFinalize(): void {
		$this->Leaflet->map();
		$this->Leaflet->finalize();

		$scripts = $this->View->fetch('script');
		$this->assertTextContains('jQuery(document).ready', $scripts);
	}

	/**
	 * @return void
	 */
	public function testAutoCenter(): void {
		$options = [
			'autoCenter' => true,
		];
		$this->Leaflet->map($options);
		$this->Leaflet->addMarker(['lat' => 48.2, 'lng' => 16.3]);
		$this->Leaflet->addMarker(['lat' => 47.0, 'lng' => 15.4]);

		$result = $this->Leaflet->script();

		$this->assertTextContains('L.featureGroup(lMarkers0)', $result);
		$this->assertTextContains('fitBounds(group.getBounds())', $result);
	}

	/**
	 * @return void
	 */
	public function testAddCustom(): void {
		$this->Leaflet->map();
		$this->Leaflet->addCustom('console.log("custom js");');

		$result = $this->Leaflet->script();

		$this->assertTextContains('console.log("custom js")', $result);
	}

	/**
	 * @return void
	 */
	public function testName(): void {
		$this->Leaflet->map();

		$this->assertSame('map0', $this->Leaflet->name());
	}

	/**
	 * @return void
	 */
	public function testId(): void {
		$this->Leaflet->map(['div' => ['id' => 'my-map']]);

		$this->assertSame('my-map', $this->Leaflet->id());
	}

	/**
	 * @return void
	 */
	public function testMapOptionsScrollWheelZoom(): void {
		$options = [
			'map' => [
				'scrollWheelZoom' => false,
			],
		];
		$this->Leaflet->map($options);

		$result = $this->Leaflet->script();

		$this->assertTextContains('"scrollWheelZoom":false', $result);
	}

	/**
	 * @return void
	 */
	public function testMarkerDraggable(): void {
		$this->Leaflet->map();
		$this->Leaflet->addMarker([
			'lat' => 48.2,
			'lng' => 16.3,
			'draggable' => true,
		]);

		$result = $this->Leaflet->script();

		$this->assertTextContains('"draggable":true', $result);
	}

	/**
	 * @return void
	 */
	public function testDuplicateMapIdHandling(): void {
		// First map with same id
		$result1 = $this->Leaflet->map(['div' => ['id' => 'map']]);
		$this->Leaflet->finalize();

		// Second map with same id - should be renamed
		$result2 = $this->Leaflet->map(['div' => ['id' => 'map']]);
		$this->Leaflet->finalize();

		$this->assertTextContains('id="map"', $result1);
		$this->assertTextContains('id="map-1"', $result2);
	}

}
