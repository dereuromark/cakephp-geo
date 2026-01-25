<?php
declare(strict_types=1);

namespace Geo\Test\TestCase\Geocoder\Provider;

use Cake\TestSuite\TestCase;
use Geo\Geocoder\Provider\ChainProvider;
use Geo\Geocoder\Provider\GeocodingProviderInterface;
use Geo\Geocoder\Provider\NullProvider;
use Geocoder\Exception\InvalidServerResponse;
use Geocoder\Exception\QuotaExceeded;
use Geocoder\Model\AddressBuilder;
use Geocoder\Model\AddressCollection;
use InvalidArgumentException;
use RuntimeException;

class ChainProviderTest extends TestCase {

	/**
	 * @return void
	 */
	public function testGetName(): void {
		$chain = new ChainProvider();

		$this->assertSame('chain', $chain->getName());
	}

	/**
	 * @return void
	 */
	public function testAddProvider(): void {
		$chain = new ChainProvider();
		$chain->addProvider(new NullProvider());

		$this->assertCount(1, $chain->getProviders());
	}

	/**
	 * @return void
	 */
	public function testConstructorWithProviders(): void {
		$providers = [new NullProvider(), new NullProvider()];
		$chain = new ChainProvider($providers);

		$this->assertCount(2, $chain->getProviders());
	}

	/**
	 * @return void
	 */
	public function testRequiresApiKeyAllRequire(): void {
		$provider1 = $this->createMock(GeocodingProviderInterface::class);
		$provider1->method('requiresApiKey')->willReturn(true);

		$provider2 = $this->createMock(GeocodingProviderInterface::class);
		$provider2->method('requiresApiKey')->willReturn(true);

		$chain = new ChainProvider([$provider1, $provider2]);

		$this->assertTrue($chain->requiresApiKey());
	}

	/**
	 * @return void
	 */
	public function testRequiresApiKeyOneDoesNot(): void {
		$provider1 = $this->createMock(GeocodingProviderInterface::class);
		$provider1->method('requiresApiKey')->willReturn(true);

		$provider2 = $this->createMock(GeocodingProviderInterface::class);
		$provider2->method('requiresApiKey')->willReturn(false);

		$chain = new ChainProvider([$provider1, $provider2]);

		$this->assertFalse($chain->requiresApiKey());
	}

	/**
	 * @return void
	 */
	public function testGeocodeReturnsFirstSuccessfulResult(): void {
		$expectedResult = new AddressCollection([
			(new AddressBuilder('test'))->build(),
		]);

		$provider1 = $this->createMock(GeocodingProviderInterface::class);
		$provider1->method('geocode')->willReturn($expectedResult);

		$provider2 = $this->createMock(GeocodingProviderInterface::class);
		$provider2->expects($this->never())->method('geocode');

		$chain = new ChainProvider([$provider1, $provider2]);
		$result = $chain->geocode('Berlin, Germany');

		$this->assertSame($expectedResult, $result);
	}

	/**
	 * @return void
	 */
	public function testGeocodeFallsBackOnQuotaExceeded(): void {
		$expectedResult = new AddressCollection([
			(new AddressBuilder('test'))->build(),
		]);

		$provider1 = $this->createMock(GeocodingProviderInterface::class);
		$provider1->method('geocode')->willThrowException(new QuotaExceeded('Rate limited'));

		$provider2 = $this->createMock(GeocodingProviderInterface::class);
		$provider2->method('geocode')->willReturn($expectedResult);

		$chain = new ChainProvider([$provider1, $provider2]);
		$result = $chain->geocode('Berlin, Germany');

		$this->assertSame($expectedResult, $result);
	}

	/**
	 * @return void
	 */
	public function testGeocodeFallsBackOnInvalidServerResponse(): void {
		$expectedResult = new AddressCollection([
			(new AddressBuilder('test'))->build(),
		]);

		$provider1 = $this->createMock(GeocodingProviderInterface::class);
		$provider1->method('geocode')->willThrowException(new InvalidServerResponse('Server error'));

		$provider2 = $this->createMock(GeocodingProviderInterface::class);
		$provider2->method('geocode')->willReturn($expectedResult);

		$chain = new ChainProvider([$provider1, $provider2]);
		$result = $chain->geocode('Berlin, Germany');

		$this->assertSame($expectedResult, $result);
	}

	/**
	 * @return void
	 */
	public function testGeocodeThrowsNonFallbackException(): void {
		$provider1 = $this->createMock(GeocodingProviderInterface::class);
		$provider1->method('geocode')->willThrowException(new InvalidArgumentException('Bad input'));

		$provider2 = $this->createMock(GeocodingProviderInterface::class);
		$provider2->expects($this->never())->method('geocode');

		$chain = new ChainProvider([$provider1, $provider2]);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Bad input');

		$chain->geocode('Berlin, Germany');
	}

	/**
	 * @return void
	 */
	public function testGeocodeThrowsWhenAllProvidersFail(): void {
		$provider1 = $this->createMock(GeocodingProviderInterface::class);
		$provider1->method('geocode')->willThrowException(new QuotaExceeded('Rate limited'));

		$provider2 = $this->createMock(GeocodingProviderInterface::class);
		$provider2->method('geocode')->willThrowException(new QuotaExceeded('Also rate limited'));

		$chain = new ChainProvider([$provider1, $provider2]);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('All providers in chain failed');

		$chain->geocode('Berlin, Germany');
	}

	/**
	 * @return void
	 */
	public function testGeocodeThrowsWhenNoProviders(): void {
		$chain = new ChainProvider();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No providers configured');

		$chain->geocode('Berlin, Germany');
	}

	/**
	 * @return void
	 */
	public function testReverseReturnsFirstSuccessfulResult(): void {
		$expectedResult = new AddressCollection([
			(new AddressBuilder('test'))->build(),
		]);

		$provider1 = $this->createMock(GeocodingProviderInterface::class);
		$provider1->method('reverse')->willReturn($expectedResult);

		$chain = new ChainProvider([$provider1]);
		$result = $chain->reverse(52.52, 13.405);

		$this->assertSame($expectedResult, $result);
	}

	/**
	 * @return void
	 */
	public function testReverseFallsBackOnQuotaExceeded(): void {
		$expectedResult = new AddressCollection([
			(new AddressBuilder('test'))->build(),
		]);

		$provider1 = $this->createMock(GeocodingProviderInterface::class);
		$provider1->method('reverse')->willThrowException(new QuotaExceeded('Rate limited'));

		$provider2 = $this->createMock(GeocodingProviderInterface::class);
		$provider2->method('reverse')->willReturn($expectedResult);

		$chain = new ChainProvider([$provider1, $provider2]);
		$result = $chain->reverse(52.52, 13.405);

		$this->assertSame($expectedResult, $result);
	}

	/**
	 * @return void
	 */
	public function testReverseThrowsWhenNoProviders(): void {
		$chain = new ChainProvider();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No providers configured');

		$chain->reverse(52.52, 13.405);
	}

	/**
	 * @return void
	 */
	public function testAddProviderReturnsFluent(): void {
		$chain = new ChainProvider();
		$result = $chain->addProvider(new NullProvider());

		$this->assertSame($chain, $result);
	}

	/**
	 * @return void
	 */
	public function testRequiresApiKeyEmptyProviders(): void {
		$chain = new ChainProvider();

		$this->assertTrue($chain->requiresApiKey());
	}

	/**
	 * @return void
	 */
	public function testGeocodeFallsBackOnMixedExceptions(): void {
		$expectedResult = new AddressCollection([
			(new AddressBuilder('test'))->build(),
		]);

		$provider1 = $this->createMock(GeocodingProviderInterface::class);
		$provider1->method('geocode')->willThrowException(new QuotaExceeded('Rate limited'));

		$provider2 = $this->createMock(GeocodingProviderInterface::class);
		$provider2->method('geocode')->willThrowException(new InvalidServerResponse('Server error'));

		$provider3 = $this->createMock(GeocodingProviderInterface::class);
		$provider3->method('geocode')->willReturn($expectedResult);

		$chain = new ChainProvider([$provider1, $provider2, $provider3]);
		$result = $chain->geocode('Berlin, Germany');

		$this->assertSame($expectedResult, $result);
	}

	/**
	 * @return void
	 */
	public function testGeocodeReturnsEmptyCollectionWithoutError(): void {
		$emptyResult = new AddressCollection([]);

		$provider = $this->createMock(GeocodingProviderInterface::class);
		$provider->method('geocode')->willReturn($emptyResult);

		$chain = new ChainProvider([$provider]);
		$result = $chain->geocode('Nonexistent Place');

		$this->assertSame(0, $result->count());
	}

}
