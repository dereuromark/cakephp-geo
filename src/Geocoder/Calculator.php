<?php

namespace Geo\Geocoder;

use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
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
		'units' => [
		],
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
	 * @return int Distance in km
	 */
	public function distance(array $pointX, array $pointY, ?string $unit = null): int {
		if (empty($unit)) {
			$unit = array_keys($this->_units);
			$unit = $unit[0];
		}
		$unit = strtoupper($unit);
		if (!isset($this->_units[$unit])) {
			throw new CalculatorException(sprintf('Invalid Unit: %s', $unit));
		}

		$res = $this->calculateDistance($pointX, $pointY);
		if (isset($this->_units[$unit])) {
			$res *= $this->_units[$unit];
		}

		return (int)ceil($res);
	}

	/**
	 * @param \Geo\Geocoder\GeoCoordinate|array<string, mixed> $pointX
	 * @param \Geo\Geocoder\GeoCoordinate|array<string, mixed> $pointY
	 * @return float
	 */
	public static function calculateDistance($pointX, $pointY) {
		if ($pointX instanceof GeoCoordinate) {
			$pointX = $pointX->toArray(true);
		}
		if ($pointY instanceof GeoCoordinate) {
			$pointY = $pointY->toArray(true);
		}

		$res = 69.09 * rad2deg(acos(sin(deg2rad($pointX['lat'])) * sin(deg2rad($pointY['lat']))
			+ cos(deg2rad($pointX['lat'])) * cos(deg2rad($pointY['lat'])) * cos(deg2rad($pointX['lng']
			- $pointY['lng']))));

		return $res;
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

		$scrambleVal = 0.000001 * mt_rand(10, 200) * pow(2, $level) * (mt_rand(0, 1) === 0 ? 1 : -1);

		return $coordinate + $scrambleVal;
	}

}
