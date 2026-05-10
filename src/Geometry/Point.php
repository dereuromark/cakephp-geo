<?php

namespace Geo\Geometry;

class Point implements GeoJsonInterface {

	protected float $longitude;

	protected float $latitude;

	public function __construct(float $longitude, float $latitude) {
		$this->longitude = $longitude;
		$this->latitude = $latitude;
	}

	public static function fromLatLng(float $latitude, float $longitude): static {
		return new static($longitude, $latitude);
	}

	/**
	 * @param array{0: float|int, 1: float|int} $coordinates
	 * @return static
	 */
	public static function fromCoordinates(array $coordinates): static {
		return new static((float)$coordinates[0], (float)$coordinates[1]);
	}

	public function getLongitude(): float {
		return $this->longitude;
	}

	public function getLatitude(): float {
		return $this->latitude;
	}

	/**
	 * @return array{lat: float, lng: float}
	 */
	public function toLatLngArray(): array {
		return [
			'lat' => $this->latitude,
			'lng' => $this->longitude,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toGeoJsonArray(): array {
		return [
			'type' => 'Point',
			'coordinates' => [$this->longitude, $this->latitude],
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array {
		return $this->toGeoJsonArray();
	}

}
