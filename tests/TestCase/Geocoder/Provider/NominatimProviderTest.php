<?php
declare(strict_types=1);

namespace Geo\Test\TestCase\Geocoder\Provider;

use Cake\TestSuite\TestCase;
use Geo\Geocoder\Provider\NominatimProvider;

class NominatimProviderTest extends TestCase {

	/**
	 * @return void
	 */
	public function testGetName(): void {
		$provider = new NominatimProvider();

		$this->assertSame('nominatim', $provider->getName());
	}

	/**
	 * @return void
	 */
	public function testRequiresApiKey(): void {
		$provider = new NominatimProvider();

		$this->assertFalse($provider->requiresApiKey());
	}

	/**
	 * @return void
	 */
	public function testConfig(): void {
		$provider = new NominatimProvider([
			'locale' => 'de',
			'userAgent' => 'MyApp/1.0',
		]);

		$this->assertSame('de', $provider->getConfig('locale'));
		$this->assertSame('MyApp/1.0', $provider->getConfig('userAgent'));
	}

	/**
	 * @return void
	 */
	public function testDefaultConfig(): void {
		$provider = new NominatimProvider();

		$this->assertNull($provider->getConfig('apiKey'));
		$this->assertSame('en', $provider->getConfig('locale'));
		$this->assertSame('CakePHP-Geo-Plugin', $provider->getConfig('userAgent'));
		$this->assertSame('https://nominatim.openstreetmap.org', $provider->getConfig('rootUrl'));
	}

	/**
	 * @return void
	 */
	public function testCustomRootUrl(): void {
		$provider = new NominatimProvider([
			'rootUrl' => 'https://nominatim.example.com',
			'userAgent' => 'CustomApp',
		]);

		$this->assertSame('https://nominatim.example.com', $provider->getConfig('rootUrl'));
	}

}
