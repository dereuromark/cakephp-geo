<?php
declare(strict_types=1);

namespace Geo\Test\TestCase\StaticMap\Provider;

use Cake\TestSuite\TestCase;
use Geo\StaticMap\Provider\MapboxProvider;

class MapboxProviderTest extends TestCase {

	/**
	 * @var \Geo\StaticMap\Provider\MapboxProvider
	 */
	protected MapboxProvider $provider;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->provider = new MapboxProvider([
			'apiKey' => 'test-access-token',
		]);
	}

	/**
	 * @return void
	 */
	public function testGetName(): void {
		$this->assertSame('mapbox', $this->provider->getName());
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
		$this->assertContains('streets-v12', $styles);
		$this->assertContains('satellite-v9', $styles);
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

		$this->assertStringContainsString('api.mapbox.com/styles/v1', $url);
		$this->assertStringContainsString('mapbox/streets-v12', $url);
		$this->assertStringContainsString('/static/', $url);
		$this->assertStringContainsString('16.3738,48.2082,12', $url);
		$this->assertStringContainsString('400x300', $url);
		$this->assertStringContainsString('access_token=test-access-token', $url);
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

		$this->assertStringContainsString('800x600', $url);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlCustomStyle(): void {
		$url = $this->provider->buildUrl([
			'lat' => 48.2082,
			'lng' => 16.3738,
			'style' => 'outdoors-v12',
		]);

		$this->assertStringContainsString('mapbox/outdoors-v12', $url);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlCustomUsername(): void {
		$url = $this->provider->buildUrl([
			'lat' => 48.2082,
			'lng' => 16.3738,
			'username' => 'myuser',
			'style' => 'custom-style',
		]);

		$this->assertStringContainsString('myuser/custom-style', $url);
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

		$this->assertStringContainsString('pin-s-a+ff0000(16.3738,48.2082)', $url);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlWithMarkerSizes(): void {
		$url = $this->provider->buildUrl(
			[],
			[
				['lat' => 48.2082, 'lng' => 16.3738, 'size' => 'large'],
				['lat' => 47.0707, 'lng' => 15.4395, 'size' => 'small'],
			],
		);

		$this->assertStringContainsString('pin-l', $url);
		$this->assertStringContainsString('pin-s', $url);
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

		$this->assertStringContainsString('path-3+0000ff', $url);
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

		$this->assertStringContainsString('@2x', $url);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlAuto(): void {
		$url = $this->provider->buildUrl(
			[],
			[
				['lat' => 48.2082, 'lng' => 16.3738],
				['lat' => 47.0707, 'lng' => 15.4395],
			],
		);

		$this->assertStringContainsString('/auto/', $url);
	}

}
