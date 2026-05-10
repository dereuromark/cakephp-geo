<?php

namespace Geo\Geometry;

class Polygon implements GeoJsonInterface {

	/**
	 * @var array<int, array<int, array{0: float, 1: float}>>
	 */
	protected array $rings;

	/**
	 * @param array<int, array<int, array{0: float|int, 1: float|int}>> $rings
	 */
	public function __construct(array $rings) {
		$this->rings = [];
		foreach ($rings as $ring) {
			$this->rings[] = $this->normalizeRing($ring);
		}
	}

	/**
	 * @param array<int, array<string, float|int>> $points
	 * @return static
	 */
	public static function fromLatLngPoints(array $points): static {
		$ring = [];
		foreach ($points as $point) {
			$ring[] = [(float)$point['lng'], (float)$point['lat']];
		}

		if ($ring !== [] && $ring[0] !== $ring[count($ring) - 1]) {
			$ring[] = $ring[0];
		}

		return new static([$ring]);
	}

	/**
	 * @return array<int, array<int, array{0: float, 1: float}>>
	 */
	public function getRings(): array {
		return $this->rings;
	}

	/**
	 * Leaflet expects [lat, lng] pairs, GeoJSON stores [lng, lat].
	 *
	 * @return array<int, array<int, array{lat: float, lng: float}>>
	 */
	public function toLeafletRings(): array {
		$result = [];
		foreach ($this->rings as $ring) {
			$result[] = array_map(function (array $coordinates): array {
				return [
					'lat' => $coordinates[1],
					'lng' => $coordinates[0],
				];
			}, $ring);
		}

		return $result;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toGeoJsonArray(): array {
		return [
			'type' => 'Polygon',
			'coordinates' => $this->rings,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array {
		return $this->toGeoJsonArray();
	}

	/**
	 * @param array<int, array{0: float|int, 1: float|int}> $ring
	 * @return array<int, array{0: float, 1: float}>
	 */
	protected function normalizeRing(array $ring): array {
		$result = [];
		foreach ($ring as $coordinates) {
			$result[] = [(float)$coordinates[0], (float)$coordinates[1]];
		}

		return $result;
	}

}
