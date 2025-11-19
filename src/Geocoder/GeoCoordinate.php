<?php

namespace Geo\Geocoder;

use Geocoder\Model\Coordinates;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

class GeoCoordinate implements JsonSerializable, Stringable {

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
	 * @param \Geocoder\Model\Coordinates $coordinates
	 *
	 * @return static
	 */
	public function fromGeocoderCoordinates(Coordinates $coordinates): static {
		return new static($coordinates->getLatitude(), $coordinates->getLongitude());
	}

	/**
	 * @return \Geocoder\Model\Coordinates
	 */
	public function toGeocoderCoordinates(): Coordinates {
		return new Coordinates($this->latitude, $this->longitude);
	}

	/**
	 * @param string $coordinate
	 *
	 * @return static
	 */
	public static function fromString(string $coordinate): static {
		if (!str_contains($coordinate, ',')) {
			throw new InvalidArgumentException('Coordinate must be in format: lat,lng');
		}

		[$lat, $lng] = explode(',', $coordinate);

		return new static((float)$lat, (float)$lng);
	}

	/**
	 * @return string
	 */
	public function toString(): string {
		return $this->latitude . ',' . $this->longitude;
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->toString();
	}

	/**
	 * @return string
	 */
	public function jsonSerialize(): string {
		return $this->toString();
	}

}
