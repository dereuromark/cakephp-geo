<?php

namespace Geo\Test\Geocoder;

use Cake\TestSuite\TestCase;
use Geo\Geocoder\Provider\GeoIpAddress;
use Geo\Geocoder\Provider\GeoIpLookup;

class GeoIpLookupTest extends TestCase {

	/**
	 * @var \Geo\Geocoder\Provider\GeoIpLookup
	 */
	protected $GeoIpLookup;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$client = new HttpClient();
		$this->GeoIpLookup = new GeoIpLookup($client, 'User Agent');
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->GeoIpLookup);
	}

	/**
	 * @return void
	 */
	public function testLocal() {
		$ip = '127.0.0.1';

		$is = $this->GeoIpLookup->geocode($ip);

		$this->assertSame(1, $is->count());
		/** @var \Geo\Geocoder\Provider\GeoIpAddress $address */
		$address = $is->first();
		$this->assertInstanceOf(GeoIpAddress::class, $address);
		$this->assertSame('localhost', $address->getLocality());
		$this->assertSame('localhost', $address->getHost());
	}

}
