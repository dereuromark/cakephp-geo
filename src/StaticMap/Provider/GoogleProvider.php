<?php
declare(strict_types=1);

namespace Geo\StaticMap\Provider;

/**
 * Google Maps static map provider.
 *
 * @link https://developers.google.com/maps/documentation/maps-static/overview
 */
class GoogleProvider extends AbstractStaticMapProvider {

	/**
	 * @var string
	 */
	protected const BASE_URL = 'https://maps.googleapis.com/maps/api/staticmap';

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'apiKey' => null,
		'size' => '400x300',
		'format' => 'png',
		'scale' => 1,
		'style' => 'roadmap',
	];

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'google';
	}

	/**
	 * @inheritDoc
	 */
	public function buildUrl(array $options, array $markers = [], array $paths = []): string {
		$config = $this->getConfig();
		$options += [
			'lat' => null,
			'lng' => null,
			'zoom' => 12,
			'size' => $config['size'],
			'format' => $config['format'],
			'style' => $config['style'],
			'scale' => $config['scale'],
		];

		$params = [
			'key' => $config['apiKey'],
			'size' => $options['size'],
			'format' => $options['format'],
			'maptype' => $options['style'],
		];

		if ($options['scale'] > 1) {
			$params['scale'] = $options['scale'];
		}

		if ($options['lat'] !== null && $options['lng'] !== null) {
			$params['center'] = $options['lat'] . ',' . $options['lng'];
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
			'roadmap',
			'satellite',
			'terrain',
			'hybrid',
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function formatColor(string $color): string {
		$normalized = $this->normalizeColor($color);

		return '0x' . strtolower($normalized);
	}

	/**
	 * Format markers for Google Static Maps URL.
	 *
	 * Format: markers=color:red|label:A|lat,lng
	 *
	 * @param array<array<string, mixed>> $markers
	 * @return string
	 */
	protected function formatMarkers(array $markers): string {
		$groups = [];

		foreach ($markers as $marker) {
			$style = [];

			if (!empty($marker['color'])) {
				$style[] = 'color:' . $this->formatColor($marker['color']);
			}

			if (!empty($marker['label'])) {
				$style[] = 'label:' . strtoupper(substr($marker['label'], 0, 1));
			}

			if (!empty($marker['size'])) {
				$sizeMap = ['small' => 'small', 'medium' => 'mid', 'large' => 'small'];
				$style[] = 'size:' . ($sizeMap[strtolower($marker['size'])] ?? 'mid');
			}

			if (!empty($marker['icon'])) {
				$style[] = 'icon:' . urlencode($marker['icon']);
			}

			$position = $marker['lat'] . ',' . $marker['lng'];

			$styleKey = implode('|', $style);
			if (!isset($groups[$styleKey])) {
				$groups[$styleKey] = [];
			}
			$groups[$styleKey][] = $position;
		}

		$formatted = [];
		foreach ($groups as $style => $positions) {
			$markerStr = $style;
			if ($markerStr) {
				$markerStr .= '|';
			}
			$markerStr .= implode('|', $positions);
			$formatted[] = 'markers=' . $markerStr;
		}

		return implode('&', $formatted);
	}

	/**
	 * Format paths for Google Static Maps URL.
	 *
	 * Format: path=color:red|weight:5|lat1,lng1|lat2,lng2
	 *
	 * @param array<array<string, mixed>> $paths
	 * @return string
	 */
	protected function formatPaths(array $paths): string {
		$formatted = [];
		foreach ($paths as $path) {
			$parts = [];

			if (!empty($path['color'])) {
				$parts[] = 'color:' . $this->formatColor($path['color']);
			}

			if (!empty($path['weight'])) {
				$parts[] = 'weight:' . $path['weight'];
			}

			if (!empty($path['fillColor'])) {
				$parts[] = 'fillcolor:' . $this->formatColor($path['fillColor']);
			}

			foreach ($path['points'] as $point) {
				$parts[] = $point['lat'] . ',' . $point['lng'];
			}

			$formatted[] = 'path=' . implode('|', $parts);
		}

		return implode('&', $formatted);
	}

}
