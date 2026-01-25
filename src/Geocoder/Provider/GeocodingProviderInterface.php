<?php
declare(strict_types=1);

namespace Geo\Geocoder\Provider;

use Geocoder\Model\AddressCollection;

/**
 * Interface for geocoding providers.
 *
 * Each provider implements geocoding and reverse geocoding using their specific API.
 */
interface GeocodingProviderInterface {

	/**
	 * Get the provider name.
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Geocode an address string to coordinates.
	 *
	 * @param string $address The address to geocode
	 * @return \Geocoder\Model\AddressCollection
	 */
	public function geocode(string $address): AddressCollection;

	/**
	 * Reverse geocode coordinates to an address.
	 *
	 * @param float $lat Latitude
	 * @param float $lng Longitude
	 * @return \Geocoder\Model\AddressCollection
	 */
	public function reverse(float $lat, float $lng): AddressCollection;

	/**
	 * Check if this provider requires an API key.
	 *
	 * @return bool
	 */
	public function requiresApiKey(): bool;

}
