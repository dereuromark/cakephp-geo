<?php
declare(strict_types=1);

namespace Geo\Test\TestCase\StaticMap\Provider;

use Cake\TestSuite\TestCase;
use Geo\StaticMap\Provider\GeoapifyProvider;

class GeoapifyProviderTest extends TestCase {

	/**
	 * @var \Geo\StaticMap\Provider\GeoapifyProvider
	 */
	protected GeoapifyProvider $provider;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->provider = new GeoapifyProvider([
			'apiKey' => 'test-api-key',
		]);
	}

	/**
	 * @return void
	 */
	public function testGetName(): void {
		$this->assertSame('geoapify', $this->provider->getName());
	}

	/**
	 * @return void
	 */
	public function testRequiresApiKey(): void {
		$this->assertTrue($this->provider->requiresApiKey());
	}

	/**
	 * @return void
	 */
	public function testGetSupportedStyles(): void {
		$styles = $this->provider->getSupportedStyles();
		$this->assertContains('osm-bright', $styles);
		$this->assertContains('dark-matter', $styles);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlBasic(): void {
		$url = $this->provider->buildUrl([
			'lat' => 48.2082,
			'lng' => 16.3738,
			'zoom' => 12,
		]);

		$this->assertStringContainsString('maps.geoapify.com/v1/staticmap', $url);
		$this->assertStringContainsString('apiKey=test-api-key', $url);
		$this->assertStringContainsString('center=lonlat%3A16.3738%2C48.2082', $url);
		$this->assertStringContainsString('zoom=12', $url);
		$this->assertStringContainsString('width=400', $url);
		$this->assertStringContainsString('height=300', $url);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlWithMarkersExplicitZoom(): void {
		$url = $this->provider->buildUrl(
			['lat' => 48.2082, 'lng' => 16.3738, 'zoom' => 15],
			[
				['lat' => 48.2082, 'lng' => 16.3738, 'color' => 'red'],
			],
		);

		$this->assertStringContainsString('center=lonlat%3A16.3738%2C48.2082', $url);
		$this->assertStringContainsString('zoom=15', $url);
		$this->assertStringContainsString('marker=', $url);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlAutoCalculatesBoundsForMarkers(): void {
		$url = $this->provider->buildUrl(
			[],
			[
				['lat' => 48.2082, 'lng' => 16.3738, 'color' => 'red'],
				['lat' => 48.1951, 'lng' => 16.3715, 'color' => 'blue'],
			],
		);

		$this->assertStringContainsString('center=', $url);
		$this->assertStringContainsString('zoom=', $url);
		$this->assertStringContainsString('marker=', $url);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlAutoCalculatesForSingleMarker(): void {
		$url = $this->provider->buildUrl(
			[],
			[
				['lat' => 48.2082, 'lng' => 16.3738, 'color' => 'red'],
			],
		);

		$this->assertStringContainsString('center=', $url);
		$this->assertStringContainsString('zoom=', $url);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlCustomSize(): void {
		$url = $this->provider->buildUrl([
			'lat' => 48.2082,
			'lng' => 16.3738,
			'size' => '800x600',
		]);

		$this->assertStringContainsString('width=800', $url);
		$this->assertStringContainsString('height=600', $url);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlCustomStyle(): void {
		$url = $this->provider->buildUrl([
			'lat' => 48.2082,
			'lng' => 16.3738,
			'style' => 'dark-matter',
		]);

		$this->assertStringContainsString('style=dark-matter', $url);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlWithMarkers(): void {
		$url = $this->provider->buildUrl(
			['lat' => 48.2082, 'lng' => 16.3738],
			[
				['lat' => 48.2082, 'lng' => 16.3738, 'color' => 'red', 'label' => 'A'],
			],
		);

		$this->assertStringContainsString('marker=', $url);
		$this->assertStringContainsString('lonlat:16.3738,48.2082', $url);
		$this->assertStringContainsString('color:%23ff0000', $url); // URL-encoded #
		$this->assertStringContainsString('text:A', $url);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlWithMultipleMarkers(): void {
		$url = $this->provider->buildUrl(
			[],
			[
				['lat' => 48.2082, 'lng' => 16.3738, 'color' => 'red'],
				['lat' => 47.0707, 'lng' => 15.4395, 'color' => 'blue'],
			],
		);

		$this->assertStringContainsString('%7C', $url); // URL-encoded |
		$this->assertStringContainsString('lonlat:16.3738,48.2082', $url);
		$this->assertStringContainsString('lonlat:15.4395,47.0707', $url);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlWithPaths(): void {
		$url = $this->provider->buildUrl(
			[],
			[],
			[
				[
					'points' => [
						['lat' => 48.2082, 'lng' => 16.3738],
						['lat' => 47.0707, 'lng' => 15.4395],
					],
					'color' => 'blue',
					'weight' => 3,
				],
			],
		);

		$this->assertStringContainsString('geometry=', $url);
		$this->assertStringContainsString('polyline:16.3738,48.2082,15.4395,47.0707', $url);
		$this->assertStringContainsString('linecolor:%230000ff', $url);
		$this->assertStringContainsString('linewidth:3', $url);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlScale(): void {
		$url = $this->provider->buildUrl([
			'lat' => 48.2082,
			'lng' => 16.3738,
			'scale' => 2,
		]);

		$this->assertStringContainsString('scaleFactor=2', $url);
	}

	/**
	 * Test auto-fit behavior when paths are provided without center/zoom.
	 *
	 * @return void
	 */
	public function testBuildUrlAutoFitWithPaths(): void {
		$url = $this->provider->buildUrl(
			[],
			[],
			[
				[
					'points' => [
						['lat' => 48.2082, 'lng' => 16.3738],
						['lat' => 48.215, 'lng' => 16.36],
						['lat' => 48.22, 'lng' => 16.38],
						['lat' => 48.2082, 'lng' => 16.3738],
					],
					'color' => 'blue',
					'weight' => 2,
					'fillColor' => 'yellow',
				],
			],
		);

		$this->assertStringContainsString('center=', $url);
		$this->assertStringContainsString('zoom=', $url);
		$this->assertStringContainsString('geometry=polyline:16.3738,48.2082,16.36,48.215,16.38,48.22,16.3738,48.2082', $url);
		$this->assertStringContainsString('linecolor:%230000ff', $url);
		$this->assertStringContainsString('linewidth:2', $url);
		$this->assertStringContainsString('fillcolor:%23ffff00', $url);
	}

	/**
	 * Test auto-calculated bounds when markers are provided without explicit center/zoom.
	 *
	 * @return void
	 */
	public function testBuildUrlAutoCalculatesBoundsWithMarkers(): void {
		$url = $this->provider->buildUrl(
			[],
			[
				['lat' => 48.2082, 'lng' => 16.3738, 'color' => 'red'],
				['lat' => 47.0707, 'lng' => 15.4395, 'color' => 'blue'],
			],
		);

		$this->assertStringContainsString('center=', $url);
		$this->assertStringContainsString('zoom=', $url);
		$this->assertStringContainsString('marker=', $url);
	}

}
