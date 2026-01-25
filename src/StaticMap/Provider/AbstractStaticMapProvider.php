<?php
declare(strict_types=1);

namespace Geo\StaticMap\Provider;

use Cake\Core\InstanceConfigTrait;

/**
 * Abstract base class for static map providers.
 *
 * Provides common functionality for building static map URLs.
 */
abstract class AbstractStaticMapProvider implements StaticMapProviderInterface {

	use InstanceConfigTrait;

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'apiKey' => null,
		'size' => '400x300',
		'format' => 'png',
		'scale' => 1,
		'style' => null,
	];

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config = []) {
		$this->setConfig($config);
	}

	/**
	 * @inheritDoc
	 */
	public function requiresApiKey(): bool {
		return true;
	}

	/**
	 * Parse size string into width and height.
	 *
	 * @param string $size Size string in format "WIDTHxHEIGHT"
	 * @return array{width: int, height: int}
	 */
	protected function parseSize(string $size): array {
		$parts = explode('x', strtolower($size));
		if (count($parts) !== 2) {
			return ['width' => 400, 'height' => 300];
		}

		return [
			'width' => (int)$parts[0],
			'height' => (int)$parts[1],
		];
	}

	/**
	 * Format a color value for the provider's URL format.
	 *
	 * @param string $color Color value (e.g., "red", "#FF0000", "0xFF0000")
	 * @return string Provider-specific color format
	 */
	abstract protected function formatColor(string $color): string;

	/**
	 * Normalize a color value by removing common prefixes.
	 *
	 * @param string $color Color value
	 * @return string Normalized color (6-char hex without prefix)
	 */
	protected function normalizeColor(string $color): string {
		$named = [
			'red' => 'FF0000',
			'green' => '00FF00',
			'blue' => '0000FF',
			'yellow' => 'FFFF00',
			'orange' => 'FFA500',
			'purple' => '800080',
			'pink' => 'FFC0CB',
			'black' => '000000',
			'white' => 'FFFFFF',
			'gray' => '808080',
			'grey' => '808080',
		];

		$lower = strtolower($color);
		if (isset($named[$lower])) {
			return $named[$lower];
		}

		$color = ltrim($color, '#');
		if (str_starts_with(strtolower($color), '0x')) {
			$color = substr($color, 2);
		}

		return strtoupper($color);
	}

	/**
	 * Build query string from parameters, filtering out null values.
	 *
	 * @param array<string, mixed> $params
	 * @return string
	 */
	protected function buildQueryString(array $params): string {
		$filtered = array_filter($params, fn ($value) => $value !== null);

		return http_build_query($filtered);
	}

	/**
	 * Calculate bounding box from markers and paths.
	 *
	 * @param array<array<string, mixed>> $markers
	 * @param array<array<string, mixed>> $paths
	 * @return array{minLat: float, maxLat: float, minLng: float, maxLng: float}|null
	 */
	protected function calculateBounds(array $markers, array $paths): ?array {
		$points = [];

		foreach ($markers as $marker) {
			if (isset($marker['lat'], $marker['lng'])) {
				$points[] = ['lat' => (float)$marker['lat'], 'lng' => (float)$marker['lng']];
			}
		}

		foreach ($paths as $path) {
			if (!isset($path['points']) || !is_array($path['points'])) {
				continue;
			}
			foreach ($path['points'] as $point) {
				if (isset($point['lat'], $point['lng'])) {
					$points[] = ['lat' => (float)$point['lat'], 'lng' => (float)$point['lng']];
				}
			}
		}

		if (empty($points)) {
			return null;
		}

		$lats = array_column($points, 'lat');
		$lngs = array_column($points, 'lng');

		return [
			'minLat' => min($lats),
			'maxLat' => max($lats),
			'minLng' => min($lngs),
			'maxLng' => max($lngs),
		];
	}

	/**
	 * Calculate center point from bounding box.
	 *
	 * @param array{minLat: float, maxLat: float, minLng: float, maxLng: float}|array $bounds
	 * @return array{lat: float, lng: float}
	 */
	protected function calculateCenter(array $bounds): array {
		return [
			'lat' => ($bounds['minLat'] + $bounds['maxLat']) / 2,
			'lng' => ($bounds['minLng'] + $bounds['maxLng']) / 2,
		];
	}

	/**
	 * Calculate appropriate zoom level for given bounds and map size.
	 *
	 * Uses Mercator projection math to determine the zoom level that will
	 * fit all points within the map viewport.
	 *
	 * @param array{minLat: float, maxLat: float, minLng: float, maxLng: float}|array $bounds
	 * @param int $mapWidth Map width in pixels
	 * @param int $mapHeight Map height in pixels
	 * @param int $padding Padding in pixels around the bounds
	 * @return int Zoom level (0-21)
	 */
	protected function calculateZoom(array $bounds, int $mapWidth, int $mapHeight, int $padding = 40): int {
		$latSpan = $bounds['maxLat'] - $bounds['minLat'];
		$lngSpan = $bounds['maxLng'] - $bounds['minLng'];

		// Handle single point case
		if ($latSpan === 0.0 && $lngSpan === 0.0) {
			return 14;
		}

		// Apply padding for markers/path endpoints visibility
		$effectiveWidth = max(1, $mapWidth - 2 * $padding);
		$effectiveHeight = max(1, $mapHeight - 2 * $padding);

		// World dimensions at zoom 0 (in pixels)
		$worldDim = 256;

		// Calculate zoom for longitude (simple linear relationship)
		$lngZoom = 21;
		if ($lngSpan > 0) {
			$lngZoom = log($effectiveWidth * 360 / ($lngSpan * $worldDim)) / log(2);
		}

		// Calculate zoom for latitude (Mercator projection)
		$latZoom = 21;
		if ($latSpan > 0) {
			// Mercator projection: lat degrees don't map linearly to pixels
			// Using simplified calculation for typical lat ranges
			$latZoom = log($effectiveHeight * 180 / ($latSpan * $worldDim)) / log(2);
		}

		// Use the more restrictive zoom level, subtract 1 to be conservative
		$zoom = min($lngZoom, $latZoom) - 1;

		// Clamp to valid range
		return max(0, min(21, (int)floor($zoom)));
	}

	/**
	 * Auto-calculate center and zoom from markers/paths if not explicitly set.
	 *
	 * @param array<string, mixed> $options
	 * @param array<array<string, mixed>> $markers
	 * @param array<array<string, mixed>> $paths
	 * @return array<string, mixed> Options with calculated lat/lng/zoom
	 */
	protected function autoCalculateBounds(array $options, array $markers, array $paths): array {
		$needsCenter = $options['lat'] === null || $options['lng'] === null;
		$needsZoom = !isset($options['zoom']) || $options['zoom'] === 'auto';

		if (!$needsCenter && !$needsZoom) {
			return $options;
		}

		$bounds = $this->calculateBounds($markers, $paths);
		if ($bounds === null) {
			return $options;
		}

		if ($needsCenter) {
			$center = $this->calculateCenter($bounds);
			$options['lat'] = $center['lat'];
			$options['lng'] = $center['lng'];
		}

		if ($needsZoom) {
			$size = $this->parseSize($options['size'] ?? $this->getConfig('size') ?? '400x300');
			$options['zoom'] = $this->calculateZoom($bounds, $size['width'], $size['height']);
		}

		return $options;
	}

}
