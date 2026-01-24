<?php
declare(strict_types=1);

namespace Geo\StaticMap\Provider;

/**
 * Stadia Maps static map provider.
 *
 * @link https://docs.stadiamaps.com/static-maps/
 */
class StadiaProvider extends AbstractStaticMapProvider {

	/**
	 * @var string
	 */
	protected const BASE_URL = 'https://tiles.stadiamaps.com/static';

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'apiKey' => null,
		'size' => '400x300',
		'format' => 'png',
		'scale' => 1,
		'style' => 'alidade_smooth',
	];

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'stadia';
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

		$size = $this->parseSize($options['size']);
		$style = $options['style'];

		$params = [];

		if ($config['apiKey']) {
			$params['api_key'] = $config['apiKey'];
		}

		if ($options['lat'] !== null && $options['lng'] !== null) {
			$center = $options['lng'] . ',' . $options['lat'] . ',' . $options['zoom'];
		} else {
			$center = 'auto';
		}

		$sizeStr = $size['width'] . 'x' . $size['height'];
		if ($options['scale'] > 1) {
			$sizeStr .= '@2x';
		}

		$url = static::BASE_URL . '/' . $style . '/' . $center . '/' . $sizeStr . '.' . $options['format'];

		if ($params) {
			$url .= '?' . $this->buildQueryString($params);
		}

		if ($markers) {
			$url .= ($params ? '&' : '?') . $this->formatMarkers($markers);
		}

		if ($paths) {
			$separator = (str_contains($url, '?')) ? '&' : '?';
			$url .= $separator . $this->formatPaths($paths);
		}

		return $url;
	}

	/**
	 * @inheritDoc
	 */
	public function getSupportedStyles(): array {
		return [
			'alidade_smooth',
			'alidade_smooth_dark',
			'alidade_satellite',
			'outdoors',
			'stamen_toner',
			'stamen_toner_lite',
			'stamen_terrain',
			'stamen_watercolor',
			'osm_bright',
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function formatColor(string $color): string {
		return strtolower($this->normalizeColor($color));
	}

	/**
	 * Format markers for Stadia URL.
	 *
	 * Format: markers=lng,lat,marker_style|lng2,lat2,marker_style
	 *
	 * @param array<array<string, mixed>> $markers
	 * @return string
	 */
	protected function formatMarkers(array $markers): string {
		$formatted = [];
		foreach ($markers as $marker) {
			$parts = [$marker['lng'], $marker['lat']];

			$markerStyle = 'marker';
			if (!empty($marker['icon'])) {
				$markerStyle = $marker['icon'];
			}

			if (!empty($marker['color'])) {
				$markerStyle .= '-' . $this->formatColor($marker['color']);
			}

			if (!empty($marker['size'])) {
				$sizeMap = ['small' => 'sm', 'medium' => 'md', 'large' => 'lg'];
				$markerStyle .= '-' . ($sizeMap[strtolower($marker['size'])] ?? 'md');
			}

			$parts[] = $markerStyle;

			$formatted[] = implode(',', $parts);
		}

		return 'markers=' . implode('|', $formatted);
	}

	/**
	 * Format paths for Stadia URL.
	 *
	 * Format: path=lng1,lat1|lng2,lat2&path_color=color&path_width=width
	 *
	 * @param array<array<string, mixed>> $paths
	 * @return string
	 */
	protected function formatPaths(array $paths): string {
		$parts = [];
		foreach ($paths as $index => $path) {
			$points = [];
			foreach ($path['points'] as $point) {
				$points[] = $point['lng'] . ',' . $point['lat'];
			}

			$prefix = $index > 0 ? "path{$index}" : 'path';
			$parts[] = $prefix . '=' . implode('|', $points);

			if (!empty($path['color'])) {
				$parts[] = $prefix . '_color=' . $this->formatColor($path['color']);
			}

			if (!empty($path['weight'])) {
				$parts[] = $prefix . '_width=' . $path['weight'];
			}

			if (!empty($path['fillColor'])) {
				$parts[] = $prefix . '_fill=' . $this->formatColor($path['fillColor']);
			}
		}

		return implode('&', $parts);
	}

}
