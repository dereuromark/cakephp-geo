<?php

namespace Geo\Geometry;

class Feature implements GeoJsonInterface {

	protected GeoJsonInterface $geometry;

	/**
	 * @var array<string, mixed>
	 */
	protected array $properties;

	/**
	 * @param \Geo\Geometry\GeoJsonInterface $geometry
	 * @param array<string, mixed> $properties
	 */
	public function __construct(GeoJsonInterface $geometry, array $properties = []) {
		$this->geometry = $geometry;
		$this->properties = $properties;
	}

	public function getGeometry(): GeoJsonInterface {
		return $this->geometry;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getProperties(): array {
		return $this->properties;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toGeoJsonArray(): array {
		return [
			'type' => 'Feature',
			'geometry' => $this->geometry->toGeoJsonArray(),
			'properties' => $this->properties,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array {
		return $this->toGeoJsonArray();
	}

}
