<?php

namespace Geo\Geocoder\Provider;

use Geocoder\Model\Address;

/**
 * @author Mark Scherer
 */
class GeoIpAddress extends Address {

	/**
	 * @var string|null
	 */
	protected $host;

	/**
	 * @return string|null
	 */
	public function getHost(): ?string {
		return $this->host;
	}

	/**
	 * @param string|null $host
	 *
	 * @return static
	 */
	public function withHost(string $host = null) {
		$new = clone $this;
		$new->host = $host;

		return $new;
	}

}
