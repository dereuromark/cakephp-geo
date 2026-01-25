<?php
declare(strict_types=1);

namespace Geo\StaticMap\Provider;

/**
 * Mapbox static map provider.
 *
 * @link https://docs.mapbox.com/api/maps/static-images/
 */
class MapboxProvider extends AbstractStaticMapProvider {

	/**
	 * @var string
	 */
	protected const BASE_URL = 'https://api.mapbox.com/styles/v1';

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'apiKey' => null,
		'size' => '400x300',
		'format' => 'png',
		'scale' => 1,
		'style' => 'streets-v12',
		'username' => 'mapbox',
	];

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'mapbox';
	}

	/**
	 * @inheritDoc
	 */
	public function buildUrl(array $options, array $markers = [], array $paths = []): string {
		$config = $this->getConfig();
		$options += [
			'lat' => null,
			'lng' => null,
			'zoom' => null,
			'size' => $config['size'],
			'format' => $config['format'],
			'style' => $config['style'],
			'scale' => $config['scale'],
			'username' => $config['username'],
			'bearing' => 0,
			'pitch' => 0,
		];

		// Auto-calculate center and zoom if not provided
		$options = $this->autoCalculateBounds($options, $markers, $paths);
		$options['zoom'] = $options['zoom'] ?? 12;

		$size = $this->parseSize($options['size']);
		$username = $options['username'];
		$style = $options['style'];

		$overlays = [];

		if ($markers) {
			$overlays = array_merge($overlays, $this->formatMarkers($markers));
		}

		if ($paths) {
			$overlays = array_merge($overlays, $this->formatPaths($paths));
		}

		$overlayStr = $overlays ? implode(',', $overlays) . '/' : '';

		if ($options['lat'] !== null && $options['lng'] !== null) {
			$center = $options['lng'] . ',' . $options['lat'] . ',' . $options['zoom'];
			if ($options['bearing'] || $options['pitch']) {
				$center .= ',' . $options['bearing'] . ',' . $options['pitch'];
			}
		} else {
			$center = 'auto';
		}

		$sizeStr = $size['width'] . 'x' . $size['height'];
		if ($options['scale'] > 1) {
			$sizeStr .= '@2x';
		}

		$url = static::BASE_URL . '/' . $username . '/' . $style . '/static/'
			. $overlayStr . $center . '/' . $sizeStr
			. '?access_token=' . $config['apiKey'];

		return $url;
	}

	/**
	 * @inheritDoc
	 */
	public function getSupportedStyles(): array {
		return [
			'streets-v12',
			'outdoors-v12',
			'light-v11',
			'dark-v11',
			'satellite-v9',
			'satellite-streets-v12',
			'navigation-day-v1',
			'navigation-night-v1',
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function formatColor(string $color): string {
		return strtolower($this->normalizeColor($color));
	}

	/**
	 * Format markers for Mapbox URL (as overlay).
	 *
	 * Format: pin-{size}-{label}+{color}({lng},{lat})
	 *
	 * @param array<array<string, mixed>> $markers
	 * @return array<string>
	 */
	protected function formatMarkers(array $markers): array {
		$formatted = [];
		foreach ($markers as $marker) {
			$size = 's';
			if (!empty($marker['size'])) {
				$sizeMap = ['small' => 's', 'medium' => 'm', 'large' => 'l'];
				$size = $sizeMap[strtolower($marker['size'])] ?? 's';
			}

			$pin = 'pin-' . $size;

			if (!empty($marker['label'])) {
				$pin .= '-' . strtolower(substr($marker['label'], 0, 1));
			}

			if (!empty($marker['color'])) {
				$pin .= '+' . $this->formatColor($marker['color']);
			}

			$pin .= '(' . $marker['lng'] . ',' . $marker['lat'] . ')';

			$formatted[] = $pin;
		}

		return $formatted;
	}

	/**
	 * Format paths for Mapbox URL (as overlay).
	 *
	 * Format: path-{strokeWidth}+{strokeColor}-{strokeOpacity}+{fillColor}-{fillOpacity}({polyline})
	 *
	 * @param array<array<string, mixed>> $paths
	 * @return array<string>
	 */
	protected function formatPaths(array $paths): array {
		$formatted = [];
		foreach ($paths as $path) {
			$pathStr = 'path';

			$weight = $path['weight'] ?? 2;
			$pathStr .= '-' . $weight;

			if (!empty($path['color'])) {
				$pathStr .= '+' . $this->formatColor($path['color']);
			}

			if (!empty($path['fillColor'])) {
				$pathStr .= '+' . $this->formatColor($path['fillColor']);
			}

			$points = [];
			foreach ($path['points'] as $point) {
				$points[] = '[' . $point['lng'] . ',' . $point['lat'] . ']';
			}

			$pathStr .= '(' . implode(',', $points) . ')';

			$formatted[] = $pathStr;
		}

		return $formatted;
	}

}
