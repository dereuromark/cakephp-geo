<?php
declare(strict_types=1);

namespace Geo\Test\TestCase\Geocoder\Provider;

use Cake\TestSuite\TestCase;
use Geo\Geocoder\Provider\GeoapifyProvider;
use Geocoder\Exception\UnsupportedOperation;

class GeoapifyProviderTest extends TestCase {

	/**
	 * @return void
	 */
	public function testGetName(): void {
		$provider = new GeoapifyProvider();

		$this->assertSame('geoapify', $provider->getName());
	}

	/**
	 * @return void
	 */
	public function testRequiresApiKey(): void {
		$provider = new GeoapifyProvider();

		$this->assertTrue($provider->requiresApiKey());
	}

	/**
	 * @return void
	 */
	public function testConfig(): void {
		$provider = new GeoapifyProvider([
			'apiKey' => 'test-key',
			'locale' => 'de',
		]);

		$this->assertSame('test-key', $provider->getConfig('apiKey'));
		$this->assertSame('de', $provider->getConfig('locale'));
	}

	/**
	 * @return void
	 */
	public function testDefaultConfig(): void {
		$provider = new GeoapifyProvider();

		$this->assertNull($provider->getConfig('apiKey'));
		$this->assertSame('en', $provider->getConfig('locale'));
	}

	/**
	 * @return void
	 */
	public function testGeocodeWithoutApiKeyThrows(): void {
		$provider = new GeoapifyProvider();

		$this->expectException(UnsupportedOperation::class);
		$this->expectExceptionMessage('Geoapify requires an API key');

		$provider->geocode('Berlin, Germany');
	}

	/**
	 * @return void
	 */
	public function testReverseWithoutApiKeyThrows(): void {
		$provider = new GeoapifyProvider();

		$this->expectException(UnsupportedOperation::class);
		$this->expectExceptionMessage('Geoapify requires an API key');

		$provider->reverse(52.52, 13.405);
	}

}
