<?php
declare(strict_types=1);

namespace Geo\View\Helper;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use Cake\View\Helper;
use Geo\StaticMap\Provider\GeoapifyProvider;
use Geo\StaticMap\Provider\GoogleProvider;
use Geo\StaticMap\Provider\MapboxProvider;
use Geo\StaticMap\Provider\StadiaProvider;
use Geo\StaticMap\Provider\StaticMapProviderInterface;
use InvalidArgumentException;

/**
 * StaticMapHelper for generating static map images from various providers.
 *
 * Supports multiple providers: Geoapify, Mapbox, Stadia, Google.
 * Configuration can be set globally via Configure::write('StaticMap', [...])
 * or per-instance via helper options.
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class StaticMapHelper extends Helper {

	/**
	 * Geoapify provider
	 *
	 * @var string
	 */
	public const PROVIDER_GEOAPIFY = 'geoapify';

	/**
	 * Mapbox provider
	 *
	 * @var string
	 */
	public const PROVIDER_MAPBOX = 'mapbox';

	/**
	 * Stadia Maps provider
	 *
	 * @var string
	 */
	public const PROVIDER_STADIA = 'stadia';

	/**
	 * Google Maps provider
	 *
	 * @var string
	 */
	public const PROVIDER_GOOGLE = 'google';

	/**
	 * @var array
	 */
	protected array $helpers = ['Html'];

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'provider' => self::PROVIDER_GEOAPIFY,
		'size' => '400x300',
		'format' => 'png',
		'scale' => 1,
		self::PROVIDER_GEOAPIFY => [
			'apiKey' => null,
			'style' => 'osm-bright',
		],
		self::PROVIDER_MAPBOX => [
			'apiKey' => null,
			'style' => 'streets-v12',
			'username' => 'mapbox',
		],
		self::PROVIDER_STADIA => [
			'apiKey' => null,
			'style' => 'alidade_smooth',
		],
		self::PROVIDER_GOOGLE => [
			'apiKey' => null,
			'style' => 'roadmap',
		],
	];

	/**
	 * @var array<string, class-string<StaticMapProviderInterface>>
	 */
	protected array $providerClasses = [
		self::PROVIDER_GEOAPIFY => GeoapifyProvider::class,
		self::PROVIDER_MAPBOX => MapboxProvider::class,
		self::PROVIDER_STADIA => StadiaProvider::class,
		self::PROVIDER_GOOGLE => GoogleProvider::class,
	];

	/**
	 * @var array<string, StaticMapProviderInterface>
	 */
	protected array $providers = [];

	/**
	 * @param array<string, mixed> $config
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$defaultConfig = Hash::merge($this->_defaultConfig, (array)Configure::read('StaticMap'));
		$config = Hash::merge($defaultConfig, $config);

		$this->_config = $config;
	}

	/**
	 * Generate an image tag with the static map.
	 *
	 * @param array<string, mixed> $options Map options (lat, lng, zoom, size, style, provider, markers, paths)
	 * @param array<string, mixed> $attributes HTML attributes for the img tag
	 * @return string
	 */
	public function image(array $options = [], array $attributes = []): string {
		$defaultAttributes = ['alt' => __d('geo', 'Map')];
		$attributes += $defaultAttributes;

		return $this->Html->image($this->url($options), $attributes);
	}

	/**
	 * Generate the URL for a static map.
	 *
	 * @param array<string, mixed> $options Map options (lat, lng, zoom, size, style, provider, markers, paths)
	 * @return string
	 */
	public function url(array $options = []): string {
		$providerName = $options['provider'] ?? $this->_config['provider'];
		unset($options['provider']);

		$markers = $options['markers'] ?? [];
		unset($options['markers']);

		$paths = $options['paths'] ?? [];
		unset($options['paths']);

		$options += [
			'size' => $this->_config['size'],
			'format' => $this->_config['format'],
			'scale' => $this->_config['scale'],
		];

		$provider = $this->provider($providerName);

		return $provider->buildUrl($options, $markers, $paths);
	}

	/**
	 * Generate a link containing a static map.
	 *
	 * @param string $title Link title
	 * @param array<string, mixed> $options Map options
	 * @param array<string, mixed> $linkOptions HTML attributes for the link
	 * @return string
	 */
	public function link(string $title, array $options = [], array $linkOptions = []): string {
		return $this->Html->link($title, $this->url($options), $linkOptions);
	}

	/**
	 * Prepare markers array from positions with optional styling.
	 *
	 * @param array<array<string, mixed>> $positions Array of position arrays with lat/lng
	 * @param array<string, mixed> $style Default style options (color, size, label, icon)
	 * @return array<array<string, mixed>>
	 */
	public function markers(array $positions, array $style = []): array {
		$markers = [];
		foreach ($positions as $index => $position) {
			$marker = $position + $style;

			if (!isset($marker['lat']) || !isset($marker['lng'])) {
				continue;
			}

			if (!isset($marker['label']) && isset($style['autoLabel']) && $style['autoLabel']) {
				$marker['label'] = chr(65 + $index);
			}

			$markers[] = $marker;
		}

		return $markers;
	}

	/**
	 * Prepare paths array from path data with optional styling.
	 *
	 * @param array<array<string, mixed>> $pathData Array of path definitions with 'points' key
	 * @param array<string, mixed> $style Default style options (color, weight, fillColor)
	 * @return array<array<string, mixed>>
	 */
	public function paths(array $pathData, array $style = []): array {
		$paths = [];
		foreach ($pathData as $path) {
			if (!isset($path['points']) || !is_array($path['points'])) {
				continue;
			}

			$paths[] = $path + $style;
		}

		return $paths;
	}

	/**
	 * Get or create a provider instance.
	 *
	 * @param string|null $name Provider name (null for default)
	 * @throws \InvalidArgumentException If provider is not supported
	 * @return \Geo\StaticMap\Provider\StaticMapProviderInterface
	 */
	public function provider(?string $name = null): StaticMapProviderInterface {
		$name = $name ?? $this->_config['provider'];

		if (!isset($this->providerClasses[$name])) {
			throw new InvalidArgumentException(sprintf('Unknown static map provider: %s', $name));
		}

		if (!isset($this->providers[$name])) {
			$providerConfig = $this->_config[$name] ?? [];
			$providerConfig += [
				'size' => $this->_config['size'],
				'format' => $this->_config['format'],
				'scale' => $this->_config['scale'],
			];

			$class = $this->providerClasses[$name];
			$this->providers[$name] = new $class($providerConfig);
		}

		return $this->providers[$name];
	}

	/**
	 * Get list of available provider names.
	 *
	 * @return array<string>
	 */
	public function availableProviders(): array {
		return array_keys($this->providerClasses);
	}

	/**
	 * Get supported styles for a provider.
	 *
	 * @param string|null $providerName Provider name (null for default)
	 * @return array<string>
	 */
	public function supportedStyles(?string $providerName = null): array {
		return $this->provider($providerName)->getSupportedStyles();
	}

}
