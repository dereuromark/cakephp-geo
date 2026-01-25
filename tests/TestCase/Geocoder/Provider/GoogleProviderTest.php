<?php
declare(strict_types=1);

namespace Geo\Test\TestCase\Geocoder\Provider;

use Cake\TestSuite\TestCase;
use Geo\Geocoder\Provider\GoogleProvider;

class GoogleProviderTest extends TestCase {

	/**
	 * @return void
	 */
	public function testGetName(): void {
		$provider = new GoogleProvider();

		$this->assertSame('google', $provider->getName());
	}

	/**
	 * @return void
	 */
	public function testRequiresApiKey(): void {
		$provider = new GoogleProvider();

		$this->assertTrue($provider->requiresApiKey());
	}

	/**
	 * @return void
	 */
	public function testConfig(): void {
		$provider = new GoogleProvider([
			'apiKey' => 'test-key',
			'locale' => 'de',
			'region' => 'de',
		]);

		$this->assertSame('test-key', $provider->getConfig('apiKey'));
		$this->assertSame('de', $provider->getConfig('locale'));
		$this->assertSame('de', $provider->getConfig('region'));
	}

	/**
	 * @return void
	 */
	public function testDefaultConfig(): void {
		$provider = new GoogleProvider();

		$this->assertNull($provider->getConfig('apiKey'));
		$this->assertSame('en', $provider->getConfig('locale'));
		$this->assertNull($provider->getConfig('region'));
	}

}
