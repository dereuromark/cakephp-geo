<?php

namespace Geo\Geometry;

use JsonSerializable;

interface GeoJsonInterface extends JsonSerializable {

	/**
	 * @return array<string, mixed>
	 */
	public function toGeoJsonArray(): array;

}
