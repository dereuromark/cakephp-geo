<?php
declare(strict_types=1);

namespace Geo\Geocoder\Provider;

use Geocoder\Collection;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

/**
 * Null provider for testing that returns empty results without making API calls.
 */
class NullProvider implements Provider {

	/**
	 * @param \Geocoder\Query\GeocodeQuery $query
	 * @return \Geocoder\Collection
	 */
	public function geocodeQuery(GeocodeQuery $query): Collection {
		return new AddressCollection([]);
	}

	/**
	 * @param \Geocoder\Query\ReverseQuery $query
	 * @return \Geocoder\Collection
	 */
	public function reverseQuery(ReverseQuery $query): Collection {
		return new AddressCollection([]);
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return 'null';
	}

}
