<?php
declare(strict_types=1);

namespace Geo\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Geo\View\Helper\StaticMapHelper;
use InvalidArgumentException;

class StaticMapHelperTest extends TestCase {

	/**
	 * @var \Geo\View\Helper\StaticMapHelper
	 */
	protected StaticMapHelper $StaticMap;

	/**
	 * @var \Cake\View\View
	 */
	protected View $View;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Configure::delete('StaticMap');

		$this->View = new View(null);
		$this->StaticMap = new StaticMapHelper($this->View, [
			StaticMapHelper::PROVIDER_GEOAPIFY => ['apiKey' => 'test-geoapify-key'],
			StaticMapHelper::PROVIDER_MAPBOX => ['apiKey' => 'test-mapbox-key'],
			StaticMapHelper::PROVIDER_STADIA => ['apiKey' => 'test-stadia-key'],
			StaticMapHelper::PROVIDER_GOOGLE => ['apiKey' => 'test-google-key'],
		]);
	}

	/**
	 * @return void
	 */
	public function testConfigMergeDefaults(): void {
		$config = [
			'provider' => StaticMapHelper::PROVIDER_MAPBOX,
			'size' => '600x400',
		];
		$this->StaticMap = new StaticMapHelper($this->View, $config);

		$result = $this->StaticMap->getConfig();
		$this->assertSame(StaticMapHelper::PROVIDER_MAPBOX, $result['provider']);
		$this->assertSame('600x400', $result['size']);
	}

	/**
	 * @return void
	 */
	public function testConfigFromConfigure(): void {
		Configure::write('StaticMap.provider', StaticMapHelper::PROVIDER_STADIA);
		Configure::write('StaticMap.size', '800x600');

		$this->StaticMap = new StaticMapHelper($this->View);

		$result = $this->StaticMap->getConfig();
		$this->assertSame(StaticMapHelper::PROVIDER_STADIA, $result['provider']);
		$this->assertSame('800x600', $result['size']);
	}

	/**
	 * @return void
	 */
	public function testUrl(): void {
		$url = $this->StaticMap->url([
			'lat' => 48.2082,
			'lng' => 16.3738,
			'zoom' => 12,
		]);

		$this->assertStringContainsString('maps.geoapify.com', $url);
		$this->assertStringContainsString('apiKey=test-geoapify-key', $url);
	}

	/**
	 * @return void
	 */
	public function testUrlWithProvider(): void {
		$url = $this->StaticMap->url([
			'provider' => StaticMapHelper::PROVIDER_MAPBOX,
			'lat' => 48.2082,
			'lng' => 16.3738,
		]);

		$this->assertStringContainsString('api.mapbox.com', $url);
		$this->assertStringContainsString('access_token=test-mapbox-key', $url);
	}

	/**
	 * @return void
	 */
	public function testUrlWithMarkers(): void {
		$url = $this->StaticMap->url([
			'lat' => 48.2082,
			'lng' => 16.3738,
			'markers' => [
				['lat' => 48.2082, 'lng' => 16.3738, 'color' => 'red'],
			],
		]);

		$this->assertStringContainsString('marker=', $url);
	}

	/**
	 * @return void
	 */
	public function testUrlWithPaths(): void {
		$url = $this->StaticMap->url([
			'paths' => [
				[
					'points' => [
						['lat' => 48.2082, 'lng' => 16.3738],
						['lat' => 47.0707, 'lng' => 15.4395],
					],
					'color' => 'blue',
				],
			],
		]);

		$this->assertStringContainsString('geometry=', $url);
	}

	/**
	 * @return void
	 */
	public function testImage(): void {
		$result = $this->StaticMap->image([
			'lat' => 48.2082,
			'lng' => 16.3738,
		]);

		$this->assertStringContainsString('<img', $result);
		$this->assertStringContainsString('src="', $result);
		$this->assertStringContainsString('maps.geoapify.com', $result);
		$this->assertStringContainsString('alt="Map"', $result);
	}

	/**
	 * @return void
	 */
	public function testImageWithAttributes(): void {
		$result = $this->StaticMap->image(
			['lat' => 48.2082, 'lng' => 16.3738],
			['class' => 'map-image', 'alt' => 'Vienna Map'],
		);

		$this->assertStringContainsString('class="map-image"', $result);
		$this->assertStringContainsString('alt="Vienna Map"', $result);
	}

	/**
	 * @return void
	 */
	public function testLink(): void {
		$result = $this->StaticMap->link(
			'View Map',
			['lat' => 48.2082, 'lng' => 16.3738],
		);

		$this->assertStringContainsString('<a', $result);
		$this->assertStringContainsString('href="', $result);
		$this->assertStringContainsString('>View Map</a>', $result);
	}

	/**
	 * @return void
	 */
	public function testLinkWithOptions(): void {
		$result = $this->StaticMap->link(
			'Map',
			['lat' => 48.2082, 'lng' => 16.3738],
			['class' => 'map-link', 'target' => '_blank'],
		);

		$this->assertStringContainsString('class="map-link"', $result);
		$this->assertStringContainsString('target="_blank"', $result);
	}

	/**
	 * @return void
	 */
	public function testMarkers(): void {
		$positions = [
			['lat' => 48.2082, 'lng' => 16.3738],
			['lat' => 47.0707, 'lng' => 15.4395],
		];

		$result = $this->StaticMap->markers($positions, ['color' => 'red']);

		$this->assertCount(2, $result);
		$this->assertSame('red', $result[0]['color']);
		$this->assertSame('red', $result[1]['color']);
	}

	/**
	 * @return void
	 */
	public function testMarkersWithAutoLabel(): void {
		$positions = [
			['lat' => 48.2082, 'lng' => 16.3738],
			['lat' => 47.0707, 'lng' => 15.4395],
		];

		$result = $this->StaticMap->markers($positions, ['autoLabel' => true]);

		$this->assertSame('A', $result[0]['label']);
		$this->assertSame('B', $result[1]['label']);
	}

	/**
	 * @return void
	 */
	public function testMarkersSkipsInvalidPositions(): void {
		$positions = [
			['lat' => 48.2082, 'lng' => 16.3738],
			['invalid' => 'data'],
			['lat' => 47.0707, 'lng' => 15.4395],
		];

		$result = $this->StaticMap->markers($positions);

		$this->assertCount(2, $result);
	}

	/**
	 * @return void
	 */
	public function testPaths(): void {
		$pathData = [
			[
				'points' => [
					['lat' => 48.2082, 'lng' => 16.3738],
					['lat' => 47.0707, 'lng' => 15.4395],
				],
			],
		];

		$result = $this->StaticMap->paths($pathData, ['color' => 'blue', 'weight' => 3]);

		$this->assertCount(1, $result);
		$this->assertSame('blue', $result[0]['color']);
		$this->assertSame(3, $result[0]['weight']);
	}

	/**
	 * @return void
	 */
	public function testPathsSkipsInvalidPaths(): void {
		$pathData = [
			[
				'points' => [
					['lat' => 48.2082, 'lng' => 16.3738],
				],
			],
			['invalid' => 'data'],
		];

		$result = $this->StaticMap->paths($pathData);

		$this->assertCount(1, $result);
	}

	/**
	 * @return void
	 */
	public function testProvider(): void {
		$provider = $this->StaticMap->provider(StaticMapHelper::PROVIDER_GEOAPIFY);

		$this->assertSame(StaticMapHelper::PROVIDER_GEOAPIFY, $provider->getName());
	}

	/**
	 * @return void
	 */
	public function testProviderDefault(): void {
		$provider = $this->StaticMap->provider();

		$this->assertSame(StaticMapHelper::PROVIDER_GEOAPIFY, $provider->getName());
	}

	/**
	 * @return void
	 */
	public function testProviderUnknown(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown static map provider: unknown');

		$this->StaticMap->provider('unknown');
	}

	/**
	 * @return void
	 */
	public function testAvailableProviders(): void {
		$providers = $this->StaticMap->availableProviders();

		$this->assertContains(StaticMapHelper::PROVIDER_GEOAPIFY, $providers);
		$this->assertContains(StaticMapHelper::PROVIDER_MAPBOX, $providers);
		$this->assertContains(StaticMapHelper::PROVIDER_STADIA, $providers);
		$this->assertContains(StaticMapHelper::PROVIDER_GOOGLE, $providers);
	}

	/**
	 * @return void
	 */
	public function testSupportedStyles(): void {
		$styles = $this->StaticMap->supportedStyles(StaticMapHelper::PROVIDER_GEOAPIFY);

		$this->assertContains('osm-bright', $styles);
	}

	/**
	 * @return void
	 */
	public function testSupportedStylesDefault(): void {
		$styles = $this->StaticMap->supportedStyles();

		$this->assertContains('osm-bright', $styles);
	}

	/**
	 * @return void
	 */
	public function testProviderReuse(): void {
		$provider1 = $this->StaticMap->provider(StaticMapHelper::PROVIDER_GEOAPIFY);
		$provider2 = $this->StaticMap->provider(StaticMapHelper::PROVIDER_GEOAPIFY);

		$this->assertSame($provider1, $provider2);
	}

	/**
	 * @return void
	 */
	public function testGoogleProvider(): void {
		$url = $this->StaticMap->url([
			'provider' => StaticMapHelper::PROVIDER_GOOGLE,
			'lat' => 48.2082,
			'lng' => 16.3738,
		]);

		$this->assertStringContainsString('maps.googleapis.com', $url);
		$this->assertStringContainsString('key=test-google-key', $url);
	}

	/**
	 * @return void
	 */
	public function testStadiaProvider(): void {
		$url = $this->StaticMap->url([
			'provider' => StaticMapHelper::PROVIDER_STADIA,
			'lat' => 48.2082,
			'lng' => 16.3738,
		]);

		$this->assertStringContainsString('tiles.stadiamaps.com', $url);
		$this->assertStringContainsString('api_key=test-stadia-key', $url);
	}

}
