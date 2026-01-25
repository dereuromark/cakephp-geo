<?php
declare(strict_types=1);

namespace Geo\Test\TestCase\Geocoder\Provider;

use Cake\TestSuite\TestCase;
use Geo\Geocoder\Provider\NullProvider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

class NullProviderTest extends TestCase {

	/**
	 * @var \Geo\Geocoder\Provider\NullProvider
	 */
	protected NullProvider $provider;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->provider = new NullProvider();
	}

	/**
	 * @return void
	 */
	public function testGetName(): void {
		$this->assertSame('null', $this->provider->getName());
	}

	/**
	 * @return void
	 */
	public function testRequiresApiKey(): void {
		$this->assertFalse($this->provider->requiresApiKey());
	}

	/**
	 * @return void
	 */
	public function testGeocode(): void {
		$result = $this->provider->geocode('Berlin, Germany');

		$this->assertSame(0, $result->count());
	}

	/**
	 * @return void
	 */
	public function testReverse(): void {
		$result = $this->provider->reverse(52.52, 13.405);

		$this->assertSame(0, $result->count());
	}

	/**
	 * @return void
	 */
	public function testGeocodeQuery(): void {
		$query = GeocodeQuery::create('Berlin, Germany');
		$result = $this->provider->geocodeQuery($query);

		$this->assertSame(0, $result->count());
	}

	/**
	 * @return void
	 */
	public function testReverseQuery(): void {
		$query = ReverseQuery::fromCoordinates(52.52, 13.405);
		$result = $this->provider->reverseQuery($query);

		$this->assertSame(0, $result->count());
	}

}
