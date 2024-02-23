<?php

namespace Geo\Geocoder;

class GeoCoordinate implements \Stringable {

	protected float $latitude;

	protected float $longitude;

	/**
	 * @param float $latitude
	 * @param float $longitude
	 */
	public function __construct(float $latitude, float $longitude) {
		$this->latitude = $latitude;
		$this->longitude = $longitude;
	}

	/**
	 * @return float
	 */
	public function getLatitude(): float {
		return $this->latitude;
	}

	/**
	 * @return float
	 */
	public function getLongitude(): float {
		return $this->longitude;
	}

	/**
	 * @param bool $abbr
	 *
	 * @return array<string, float>
	 */
	public function toArray(bool $abbr = false): array {
		return [
			($abbr ? 'lat' : 'latitude') => $this->latitude,
			($abbr ? 'lng' : 'longitude') => $this->longitude,
		];
	}

	/**
	 * @param array<string, float> $data
	 *
	 * @return static
	 */
	public static function fromArray(array $data): static {
		$lat = $data['latitude'] ?? $data['lat'];
		$lng = $data['longitude'] ?? $data['lng'];

		return new static($lat, $lng);
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->latitude . ',' . $this->longitude;
	}

}
