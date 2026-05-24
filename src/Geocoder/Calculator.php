<?php

namespace Geo\Geocoder;

use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use DateTimeZone;
use Geo\Exception\CalculatorException;

/**
 * Used by Geo.GeocoderBehavior
 *
 * @author Mark Scherer
 * @license MIT
 */
class Calculator {

	use InstanceConfigTrait;

	/**
	 * @var string
	 */
	public const UNIT_KM = 'K';

	/**
	 * @var string
	 */
	public const UNIT_NAUTICAL = 'N';

	/**
	 * @var string
	 */
	public const UNIT_FEET = 'F';

	/**
	 * @var string
	 */
	public const UNIT_INCHES = 'I';

	/**
	 * @var string
	 */
	public const UNIT_MILES = 'M';

	/**
	 * @var array
	 */
	protected array $_units = [
		self::UNIT_KM => 1.609344,
		self::UNIT_NAUTICAL => 0.868976242,
		self::UNIT_FEET => 5280,
		self::UNIT_INCHES => 63360,
		self::UNIT_MILES => 1,
	];

	/**
	 * Validation and retrieval options
	 * - use:
	 * - log: false logs only real errors, true all activities
	 * - pause: timeout to prevent blocking
	 * - ...
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'units' => [],
	];

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config = []) {
		$this->setConfig($config);

		$additionalUnits = (array)Configure::read('Geo.Calculator.units');
		foreach ($additionalUnits as $additionalUnit => $value) {
			$this->_units[$additionalUnit] = $value;
		}
	}

	/**
	 * Convert between units
	 *
	 * @param float $value
	 * @param string $fromUnit (using class constants)
	 * @param string $toUnit (using class constants)
	 * @throws \Exception
	 * @return float convertedValue
	 */
	public function convert(float $value, string $fromUnit, string $toUnit): float {
		$fromUnit = strtoupper($fromUnit);
		$toUnit = strtoupper($toUnit);

		if (!isset($this->_units[$fromUnit]) || !isset($this->_units[$toUnit])) {
			throw new CalculatorException('Invalid Unit');
		}

		if ($fromUnit === static::UNIT_MILES) {
			$value *= $this->_units[$toUnit];
		} elseif ($toUnit === static::UNIT_MILES) {
			$value /= $this->_units[$fromUnit];
		} else {
			$value /= $this->_units[$fromUnit];
			$value *= $this->_units[$toUnit];
		}

		return $value;
	}

	/**
	 * Calculates Distance between two points - each: array('lat'=>x,'lng'=>y)
	 * DB:
	 * '6371.04 * ACOS( COS( PI()/2 - RADIANS(90 - Retailer.lat)) * ' .
	 * 'COS( PI()/2 - RADIANS(90 - '. $data['Location']['lat'] .')) * ' .
	 * 'COS( RADIANS(Retailer.lng) - RADIANS('. $data['Location']['lng'] .')) + ' .
	 * 'SIN( PI()/2 - RADIANS(90 - Retailer.lat)) * ' .
	 * 'SIN( PI()/2 - RADIANS(90 - '. $data['Location']['lat'] . '))) ' .
	 * 'AS distance'
	 *
	 * @param array<string, mixed> $pointX
	 * @param array<string, mixed> $pointY
	 * @param string|null $unit Unit char or constant (M=miles, K=kilometers, N=nautical miles, I=inches, F=feet)
	 * @return int Distance in the requested unit, kilometers by default
	 */
	public function distance(array $pointX, array $pointY, ?string $unit = null): int {
		if (empty($unit)) {
			$unit = array_keys($this->_units);
			$unit = $unit[0];
		}
		$unit = strtoupper((string) $unit);
		if (!isset($this->_units[$unit])) {
			throw new CalculatorException(sprintf('Invalid Unit: %s', $unit));
		}

		$res = static::calculateDistance($pointX, $pointY);
		if (isset($this->_units[$unit])) {
			$res *= $this->_units[$unit];
		}

		return (int)ceil($res);
	}

	/**
	 * Calculate the great-circle distance using the haversine formula.
	 *
	 * The raw return unit remains miles for backward compatibility with the
	 * existing conversion map consumed by {@see distance()}.
	 *
	 * @param \Geo\Geocoder\GeoCoordinate|array<string, mixed> $pointX
	 * @param \Geo\Geocoder\GeoCoordinate|array<string, mixed> $pointY
	 * @return float Distance in miles
	 */
	public static function calculateDistance($pointX, $pointY) {
		if ($pointX instanceof GeoCoordinate) {
			$pointX = $pointX->toArray(true);
		}
		if ($pointY instanceof GeoCoordinate) {
			$pointY = $pointY->toArray(true);
		}

		$latX = deg2rad((float)$pointX['lat']);
		$latY = deg2rad((float)$pointY['lat']);
		$deltaLat = $latY - $latX;
		$deltaLng = deg2rad(static::normalizeLongitudeDelta((float)$pointY['lng'] - (float)$pointX['lng']));

		$sinLat = sin($deltaLat / 2);
		$sinLng = sin($deltaLng / 2);
		$haversine = $sinLat * $sinLat
			+ cos($latX) * cos($latY) * $sinLng * $sinLng;

		// Keep the return unit in miles for BC with the existing converter map.
		$res = 3958.7613 * 2 * asin(min(1.0, sqrt($haversine)));

		return $res;
	}

	/**
	 * Normalize a longitude delta to the shortest path around the globe.
	 *
	 * @param float $deltaLongitude
	 * @return float
	 */
	protected static function normalizeLongitudeDelta(float $deltaLongitude): float {
		$deltaLongitude = fmod($deltaLongitude + 180.0, 360.0);
		if ($deltaLongitude < 0) {
			$deltaLongitude += 360.0;
		}

		return $deltaLongitude - 180.0;
	}

	/**
	 * Fuzziness filter for coordinates (lat or lng).
	 * Useful if you store other users' locations and want to grant some
	 * privacy protection. This way the coordinates will be slightly modified.
	 *
	 * @param float $coordinate Coordinate
	 * @param int $level The Level of blurriness (0...n), 0 means no blur
	 * @throws \Exception
	 * @return float Coordinates
	 */
	public static function blur(float $coordinate, int $level = 0): float {
		if (!$level) {
			return $coordinate;
		}

		$scrambleVal = 0.000001 * mt_rand(10, 200) * 2 ** $level * (mt_rand(0, 1) === 0 ? 1 : -1);

		return $coordinate + $scrambleVal;
	}

	/**
	 * Gets the timezone that is closest to the given coordinates.
	 *
	 * @param float $lat Latitude
	 * @param float $lng Longitude
	 * @return string|null Timezone identifier or null if none found
	 */
	public static function timezoneByCoordinates(float $lat, float $lng): ?string {
		$current = ['timezone' => null, 'distance' => 0.0];
		$identifiers = DateTimeZone::listIdentifiers();

		foreach ($identifiers as $identifier) {
			$timezone = new DateTimeZone($identifier);
			$location = $timezone->getLocation();
			if ($location === false) {
				continue;
			}

			$point = ['lat' => $location['latitude'], 'lng' => $location['longitude']];
			$distance = static::calculateDistance(['lat' => $lat, 'lng' => $lng], $point);

			if ($current['timezone'] === null || $distance < $current['distance']) {
				$current = ['timezone' => $identifier, 'distance' => $distance];
			}
		}

		return $current['timezone'];
	}

}
