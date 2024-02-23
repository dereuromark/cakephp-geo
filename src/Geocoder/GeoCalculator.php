<?php

namespace Geo\Geocoder;

use RuntimeException;

class GeoCalculator {

	/**
	 * @param array<\Geo\Geocoder\GeoCoordinate> $geoCoordinates
	 *
	 * @return \Geo\Geocoder\GeoCoordinate
	 */
	public static function getCentralGeoCoordinate(array $geoCoordinates): GeoCoordinate {
		if (!$geoCoordinates) {
			throw new RuntimeException('No geo coordinates provided');
		}
		if (count($geoCoordinates) === 1) {
			return array_shift($geoCoordinates);
		}

		$x = 0;
		$y = 0;
		$z = 0;

		foreach ($geoCoordinates as $geoCoordinate) {
			$latitude = $geoCoordinate->getLatitude() * M_PI / 180;
			$longitude = $geoCoordinate->getLongitude() * M_PI / 180;

			$x += cos($latitude) * cos($longitude);
			$y += cos($latitude) * sin($longitude);
			$z += sin($latitude);
		}

		$total = count($geoCoordinates);

		$x /= $total;
		$y /= $total;
		$z /= $total;

		$centralLongitude = atan2($y, $x);
		$centralSquareRoot = sqrt($x * $x + $y * $y);
		$centralLatitude = atan2($z, $centralSquareRoot);

		return new GeoCoordinate($centralLatitude * 180 / M_PI, $centralLongitude * 180 / M_PI);
	}

}
