<?php

namespace Geo\Test\TestCase\Geocoder\Provider;

use Cake\TestSuite\TestCase;
use Geo\Geocoder\Provider\GeoIpAddress;
use Geocoder\Model\AdminLevelCollection;

class GeoIpAddressTest extends TestCase {

	/**
	 * @return void
	 */
	public function testWithHost(): void {
		$address = new GeoIpAddress('geo_ip_lookup', new AdminLevelCollection());
		$this->assertNull($address->getHost());

		$newAddress = $address->withHost('example.com');

		$this->assertNull($address->getHost());
		$this->assertSame('example.com', $newAddress->getHost());
		$this->assertNotSame($address, $newAddress);
	}

	/**
	 * @return void
	 */
	public function testWithHostNull(): void {
		$address = new GeoIpAddress('geo_ip_lookup', new AdminLevelCollection());
		$address = $address->withHost('example.com');

		$newAddress = $address->withHost(null);

		$this->assertSame('example.com', $address->getHost());
		$this->assertNull($newAddress->getHost());
	}

}
