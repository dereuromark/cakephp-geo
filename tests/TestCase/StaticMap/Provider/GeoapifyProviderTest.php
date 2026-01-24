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
		$this->assertStringContainsString('color:#ff0000', $url);
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

		$this->assertStringContainsString('|', $url);
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
		$this->assertStringContainsString('polyline:', $url);
		$this->assertStringContainsString('linecolor:#0000ff', $url);
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

}
