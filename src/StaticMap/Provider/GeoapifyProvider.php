<?php
declare(strict_types=1);

namespace Geo\StaticMap\Provider;

/**
 * Geoapify static map provider.
 *
 * @link https://apidocs.geoapify.com/docs/maps/static/
 */
class GeoapifyProvider extends AbstractStaticMapProvider {

	/**
	 * @var string
	 */
	protected const BASE_URL = 'https://maps.geoapify.com/v1/staticmap';

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'apiKey' => null,
		'size' => '400x300',
		'format' => 'png',
		'scale' => 1,
		'style' => 'osm-bright',
	];

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'geoapify';
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
		];

		// Auto-calculate center and zoom if not provided
		$options = $this->autoCalculateBounds($options, $markers, $paths);
		$options['zoom'] = $options['zoom'] ?? 12;

		$size = $this->parseSize($options['size']);

		$params = [
			'apiKey' => $config['apiKey'],
			'style' => $options['style'],
			'width' => $size['width'],
			'height' => $size['height'],
			'format' => $options['format'],
		];

		if ($options['scale'] > 1) {
			$params['scaleFactor'] = $options['scale'];
		}

		if ($options['lat'] !== null && $options['lng'] !== null) {
			$params['center'] = 'lonlat:' . $options['lng'] . ',' . $options['lat'];
			$params['zoom'] = $options['zoom'];
		}

		$url = static::BASE_URL . '?' . $this->buildQueryString($params);

		if ($markers) {
			$url .= '&' . $this->formatMarkers($markers);
		}

		if ($paths) {
			$url .= '&' . $this->formatPaths($paths);
		}

		return $url;
	}

	/**
	 * @inheritDoc
	 */
	public function getSupportedStyles(): array {
		return [
			'osm-bright',
			'osm-bright-grey',
			'osm-bright-smooth',
			'klokantech-basic',
			'osm-liberty',
			'maptiler-3d',
			'toner',
			'toner-grey',
			'positron',
			'positron-blue',
			'positron-red',
			'dark-matter',
			'dark-matter-brown',
			'dark-matter-dark-grey',
			'dark-matter-dark-purple',
			'dark-matter-purple-roads',
			'dark-matter-yellow-roads',
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function formatColor(string $color): string {
		return strtolower($this->normalizeColor($color));
	}

	/**
	 * Format markers for Geoapify URL.
	 *
	 * @param array<array<string, mixed>> $markers
	 * @return string
	 */
	protected function formatMarkers(array $markers): string {
		$formatted = [];
		foreach ($markers as $marker) {
			$parts = ['lonlat:' . $marker['lng'] . ',' . $marker['lat']];

			if (!empty($marker['color'])) {
				$parts[] = 'color:%23' . $this->formatColor($marker['color']);
			}

			if (!empty($marker['size'])) {
				$parts[] = 'size:' . $marker['size'];
			} else {
				$parts[] = 'size:medium';
			}

			if (!empty($marker['icon'])) {
				$parts[] = 'type:' . $marker['icon'];
			} else {
				$parts[] = 'type:material';
			}

			if (!empty($marker['label'])) {
				$parts[] = 'text:' . urlencode($marker['label']);
			}

			$formatted[] = implode(';', $parts);
		}

		return 'marker=' . implode('%7C', $formatted);
	}

	/**
	 * Format paths for Geoapify URL.
	 *
	 * @param array<array<string, mixed>> $paths
	 * @return string
	 */
	protected function formatPaths(array $paths): string {
		$formatted = [];
		foreach ($paths as $path) {
			$points = [];
			foreach ($path['points'] as $point) {
				$points[] = $point['lng'] . ',' . $point['lat'];
			}

			$pathStr = 'polyline:' . implode(',', $points);

			if (!empty($path['color'])) {
				$pathStr .= ';linecolor:%23' . $this->formatColor($path['color']);
			}

			if (!empty($path['weight'])) {
				$pathStr .= ';linewidth:' . $path['weight'];
			}

			if (!empty($path['fillColor'])) {
				$pathStr .= ';fillcolor:%23' . $this->formatColor($path['fillColor']);
			}

			$formatted[] = $pathStr;
		}

		return 'geometry=' . implode('%7C', $formatted);
	}

}
