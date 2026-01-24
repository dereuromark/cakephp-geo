<?php
declare(strict_types=1);

namespace Geo\Test\TestCase\StaticMap\Provider;

use Cake\TestSuite\TestCase;
use Geo\StaticMap\Provider\GoogleProvider;

class GoogleProviderTest extends TestCase {

	/**
	 * @var \Geo\StaticMap\Provider\GoogleProvider
	 */
	protected GoogleProvider $provider;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->provider = new GoogleProvider([
			'apiKey' => 'test-api-key',
		]);
	}

	/**
	 * @return void
	 */
	public function testGetName(): void {
		$this->assertSame('google', $this->provider->getName());
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
		$this->assertContains('roadmap', $styles);
		$this->assertContains('satellite', $styles);
		$this->assertContains('hybrid', $styles);
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

		$this->assertStringContainsString('maps.googleapis.com/maps/api/staticmap', $url);
		$this->assertStringContainsString('key=test-api-key', $url);
		$this->assertStringContainsString('center=48.2082%2C16.3738', $url);
		$this->assertStringContainsString('zoom=12', $url);
		$this->assertStringContainsString('size=400x300', $url);
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

		$this->assertStringContainsString('size=800x600', $url);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlCustomStyle(): void {
		$url = $this->provider->buildUrl([
			'lat' => 48.2082,
			'lng' => 16.3738,
			'style' => 'satellite',
		]);

		$this->assertStringContainsString('maptype=satellite', $url);
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

		$this->assertStringContainsString('markers=', $url);
		$this->assertStringContainsString('color:0xff0000', $url);
		$this->assertStringContainsString('label:A', $url);
		$this->assertStringContainsString('48.2082,16.3738', $url);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlWithMultipleMarkersGrouped(): void {
		$url = $this->provider->buildUrl(
			[],
			[
				['lat' => 48.2082, 'lng' => 16.3738, 'color' => 'red'],
				['lat' => 47.0707, 'lng' => 15.4395, 'color' => 'red'],
			],
		);

		$this->assertStringContainsString('48.2082,16.3738|47.0707,15.4395', $url);
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
					'weight' => 5,
				],
			],
		);

		$this->assertStringContainsString('path=', $url);
		$this->assertStringContainsString('color:0x0000ff', $url);
		$this->assertStringContainsString('weight:5', $url);
		$this->assertStringContainsString('48.2082,16.3738|47.0707,15.4395', $url);
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

		$this->assertStringContainsString('scale=2', $url);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlWithMarkerSize(): void {
		$url = $this->provider->buildUrl(
			[],
			[
				['lat' => 48.2082, 'lng' => 16.3738, 'size' => 'small'],
			],
		);

		$this->assertStringContainsString('size:small', $url);
	}

}
