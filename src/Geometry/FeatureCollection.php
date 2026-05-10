<?php

namespace Geo\Geometry;

class FeatureCollection implements GeoJsonInterface {

	/**
	 * @var array<int, \Geo\Geometry\Feature>
	 */
	protected array $features;

	/**
	 * @param array<int, \Geo\Geometry\Feature> $features
	 */
	public function __construct(array $features) {
		$this->features = $features;
	}

	/**
	 * @return array<int, \Geo\Geometry\Feature>
	 */
	public function getFeatures(): array {
		return $this->features;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toGeoJsonArray(): array {
		return [
			'type' => 'FeatureCollection',
			'features' => array_map(static fn (Feature $feature): array => $feature->toGeoJsonArray(), $this->features),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array {
		return $this->toGeoJsonArray();
	}

}
