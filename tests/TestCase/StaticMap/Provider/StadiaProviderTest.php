<?php
declare(strict_types=1);

namespace Geo\Test\TestCase\StaticMap\Provider;

use Cake\TestSuite\TestCase;
use Geo\StaticMap\Provider\StadiaProvider;

class StadiaProviderTest extends TestCase {

	/**
	 * @var \Geo\StaticMap\Provider\StadiaProvider
	 */
	protected StadiaProvider $provider;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->provider = new StadiaProvider([
			'apiKey' => 'test-api-key',
		]);
	}

	/**
	 * @return void
	 */
	public function testGetName(): void {
		$this->assertSame('stadia', $this->provider->getName());
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
		$this->assertContains('alidade_smooth', $styles);
		$this->assertContains('stamen_toner', $styles);
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

		$this->assertStringContainsString('tiles.stadiamaps.com/static', $url);
		$this->assertStringContainsString('alidade_smooth', $url);
		$this->assertStringContainsString('16.3738,48.2082,12', $url);
		$this->assertStringContainsString('400x300', $url);
		$this->assertStringContainsString('api_key=test-api-key', $url);
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
			'style' => 'stamen_toner',
		]);

		$this->assertStringContainsString('stamen_toner', $url);
	}

	/**
	 * @return void
	 */
	public function testBuildUrlWithMarkers(): void {
		$url = $this->provider->buildUrl(
			['lat' => 48.2082, 'lng' => 16.3738],
			[
				['lat' => 48.2082, 'lng' => 16.3738, 'color' => 'red'],
			],
		);

		// Stadia uses m=lat,lng,style format for each marker
		$this->assertStringContainsString('m=', $url);
		$this->assertStringContainsString('48.2082,16.3738', $url);
		$this->assertStringContainsString('ff0000', $url);
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

		// Stadia uses multiple m= parameters (not pipe-separated)
		$this->assertStringContainsString('m=48.2082,16.3738', $url);
		$this->assertStringContainsString('m=47.0707,15.4395', $url);
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

		$this->assertStringContainsString('path=', $url);
		$this->assertStringContainsString('path_color=', $url);
		$this->assertStringContainsString('path_width=3', $url);
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

	/**
	 * @return void
	 */
	public function testBuildUrlWithoutApiKey(): void {
		$provider = new StadiaProvider([]);
		$url = $provider->buildUrl([
			'lat' => 48.2082,
			'lng' => 16.3738,
		]);

		$this->assertStringNotContainsString('api_key=', $url);
	}

}
