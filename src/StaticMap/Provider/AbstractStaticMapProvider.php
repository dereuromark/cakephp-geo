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

}
