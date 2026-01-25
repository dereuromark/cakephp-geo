<?php
declare(strict_types=1);

namespace Geo\Test\TestCase\Geocoder;

use Cake\TestSuite\TestCase;
use Geo\Geocoder\Geocoder;
use Geo\Geocoder\Provider\GeoapifyProvider;
use Geo\Geocoder\Provider\GoogleProvider;
use Geo\Geocoder\Provider\NominatimProvider;
use Geo\Geocoder\Provider\NullProvider;
use RuntimeException;

class GeocoderProviderTest extends TestCase {

	/**
	 * @return void
	 */
	public function testProviderConstants(): void {
		$this->assertSame('google', Geocoder::PROVIDER_GOOGLE);
		$this->assertSame('nominatim', Geocoder::PROVIDER_NOMINATIM);
		$this->assertSame('geoapify', Geocoder::PROVIDER_GEOAPIFY);
		$this->assertSame('null', Geocoder::PROVIDER_NULL);
	}

	/**
	 * @return void
	 */
	public function testGetProviders(): void {
		$providers = Geocoder::getProviders();

		$this->assertArrayHasKey('google', $providers);
		$this->assertArrayHasKey('nominatim', $providers);
		$this->assertArrayHasKey('geoapify', $providers);
		$this->assertArrayHasKey('null', $providers);

		$this->assertSame(GoogleProvider::class, $providers['google']);
		$this->assertSame(NominatimProvider::class, $providers['nominatim']);
		$this->assertSame(GeoapifyProvider::class, $providers['geoapify']);
		$this->assertSame(NullProvider::class, $providers['null']);
	}

	/**
	 * @return void
	 */
	public function testRegisterCustomProvider(): void {
		$customProviderClass = NullProvider::class;

		Geocoder::registerProvider('custom', $customProviderClass);
		$providers = Geocoder::getProviders();

		$this->assertArrayHasKey('custom', $providers);
		$this->assertSame($customProviderClass, $providers['custom']);
	}

	/**
	 * @return void
	 */
	public function testNullProviderReturnsEmptyResults(): void {
		$geocoder = new Geocoder([
			'provider' => Geocoder::PROVIDER_NULL,
			'minAccuracy' => null,
		]);

		$result = $geocoder->geocode('Berlin, Germany');

		$this->assertSame(0, $result->count());
	}

	/**
	 * @return void
	 */
	public function testNullProviderReverseReturnsEmptyResults(): void {
		$geocoder = new Geocoder([
			'provider' => Geocoder::PROVIDER_NULL,
			'minAccuracy' => null,
		]);

		$result = $geocoder->reverse(52.52, 13.405);

		$this->assertSame(0, $result->count());
	}

	/**
	 * @return void
	 */
	public function testProviderInstanceConfig(): void {
		$provider = new NullProvider();

		$geocoder = new Geocoder([
			'provider' => $provider,
			'minAccuracy' => null,
		]);

		$result = $geocoder->geocode('Berlin, Germany');

		$this->assertSame(0, $result->count());
	}

	/**
	 * @return void
	 */
	public function testCallableProviderBackwardCompatibility(): void {
		$geocoder = new Geocoder([
			'provider' => fn () => new NullProvider(),
			'minAccuracy' => null,
		]);

		$result = $geocoder->geocode('Berlin, Germany');

		$this->assertSame(0, $result->count());
	}

	/**
	 * @return void
	 */
	public function testProviderSpecificConfigMerge(): void {
		$geocoder = new Geocoder([
			'provider' => Geocoder::PROVIDER_NOMINATIM,
			'locale' => 'de',
			'nominatim' => [
				'userAgent' => 'CustomApp/2.0',
			],
		]);

		$this->assertSame('de', $geocoder->getConfig('locale'));
		$this->assertSame('CustomApp/2.0', $geocoder->getConfig('nominatim.userAgent'));
	}

	/**
	 * @return void
	 */
	public function testGlobalApiKeyFallback(): void {
		$geocoder = new Geocoder([
			'provider' => Geocoder::PROVIDER_GOOGLE,
			'apiKey' => 'global-key',
		]);

		$this->assertSame('global-key', $geocoder->getConfig('apiKey'));
	}

	/**
	 * @return void
	 */
	public function testProvidersArrayCreatesChain(): void {
		$geocoder = new Geocoder([
			'providers' => [
				Geocoder::PROVIDER_NULL,
				Geocoder::PROVIDER_NULL,
			],
			'minAccuracy' => null,
		]);

		$result = $geocoder->geocode('Berlin, Germany');

		$this->assertSame(0, $result->count());
	}

	/**
	 * @return void
	 */
	public function testProvidersArrayWithConfigMerge(): void {
		$geocoder = new Geocoder([
			'providers' => [
				Geocoder::PROVIDER_NOMINATIM,
				Geocoder::PROVIDER_GEOAPIFY,
			],
			'locale' => 'de',
			'nominatim' => [
				'userAgent' => 'TestApp/1.0',
			],
		]);

		$this->assertSame('de', $geocoder->getConfig('locale'));
		$this->assertSame('TestApp/1.0', $geocoder->getConfig('nominatim.userAgent'));
	}

	/**
	 * @return void
	 */
	public function testConflictingProviderAndProvidersThrowsException(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Cannot configure both \'provider\' and \'providers\'');

		$geocoder = new Geocoder([
			'provider' => Geocoder::PROVIDER_GOOGLE,
			'providers' => [
				Geocoder::PROVIDER_NOMINATIM,
				Geocoder::PROVIDER_GEOAPIFY,
			],
			'minAccuracy' => null,
		]);

		// Trigger geocoder build
		$geocoder->geocode('Berlin, Germany');
	}

}
