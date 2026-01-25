<?php

namespace Geo\View\Helper;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use Cake\View\Helper;

/**
 * This is a CakePHP helper that helps users to integrate Leaflet.js
 * into their application by only writing PHP code. This helper depends on jQuery.
 *
 * Capable of resetting itself (full or partly) for multiple maps on a single view.
 *
 * @link https://leafletjs.com/
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class LeafletHelper extends Helper {

	use JsBaseEngineTrait;

	/**
	 * OpenStreetMap tiles
	 *
	 * @var string
	 */
	public const TILES_OSM = 'osm';

	/**
	 * CartoDB Light tiles
	 *
	 * @var string
	 */
	public const TILES_CARTO_LIGHT = 'carto_light';

	/**
	 * CartoDB Dark tiles
	 *
	 * @var string
	 */
	public const TILES_CARTO_DARK = 'carto_dark';

	/**
	 * @var int
	 */
	public static int $mapCount = 0;

	/**
	 * @var int
	 */
	public static int $markerCount = 0;

	/**
	 * @var int
	 */
	public static int $popupCount = 0;

	/**
	 * Needed helpers
	 *
	 * @var array
	 */
	protected array $helpers = ['Html'];

	/**
	 * Markers array
	 *
	 * @var array
	 */
	public array $markers = [];

	/**
	 * Popups array
	 *
	 * @var array
	 */
	public array $popups = [];

	/**
	 * Current map JS
	 *
	 * @var string
	 */
	public string $map = '';

	/**
	 * Tile layer presets
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $tilePresets = [
		'osm' => [
			'url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
			'options' => [
				'attribution' => '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors',
				'maxZoom' => 19,
			],
		],
		'carto_light' => [
			'url' => 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
			'options' => [
				'attribution' => '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/">CARTO</a>',
				'maxZoom' => 20,
				'subdomains' => 'abcd',
			],
		],
		'carto_dark' => [
			'url' => 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
			'options' => [
				'attribution' => '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/">CARTO</a>',
				'maxZoom' => 20,
				'subdomains' => 'abcd',
			],
		],
	];

	/**
	 * @var array<string>
	 */
	protected array $_mapIds = [];

	/**
	 * Default settings
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'zoom' => null,
		'lat' => null,
		'lng' => null,
		'map' => [
			'zoom' => null,
			'lat' => null,
			'lng' => null,
			'scrollWheelZoom' => true,
			'zoomControl' => true,
			'dragging' => true,
			'defaultLat' => 51,
			'defaultLng' => 11,
			'defaultZoom' => 5,
		],
		'tileLayer' => [
			'url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
			'options' => [
				'attribution' => '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors',
				'maxZoom' => 19,
			],
		],
		'marker' => [
			'draggable' => false,
			'title' => null,
			'open' => false,
		],
		'popup' => [
			'content' => '',
			'maxWidth' => 300,
			'autoClose' => true,
		],
		'polyline' => [
			'color' => '#3388ff',
			'weight' => 3,
			'opacity' => 1.0,
		],
		'circle' => [
			'color' => '#3388ff',
			'fillColor' => '#3388ff',
			'fillOpacity' => 0.2,
			'radius' => 500,
		],
		'polygon' => [
			'color' => '#3388ff',
			'fillColor' => '#3388ff',
			'fillOpacity' => 0.2,
		],
		'div' => [
			'id' => 'map',
			'width' => '100%',
			'height' => '400px',
			'class' => 'leaflet-map',
		],
		'autoCenter' => false,
		'autoScript' => false,
		'block' => true,
	];

	/**
	 * @var array<string, mixed>
	 */
	protected array $_runtimeConfig = [];

	/**
	 * @var bool
	 */
	protected bool $_apiIncluded = false;

	/**
	 * @var bool
	 */
	protected bool $_clusterIncluded = false;

	/**
	 * @var bool
	 */
	protected bool $_useCluster = false;

	/**
	 * @param array<string, mixed> $config
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$defaultConfig = Hash::merge($this->_defaultConfig, (array)Configure::read('Leaflet'));
		$config = Hash::merge($defaultConfig, $config);

		if (isset($config['zoom']) && !isset($config['map']['zoom'])) {
			$config['map']['zoom'] = $config['zoom'];
		}
		if (isset($config['lat']) && !isset($config['map']['lat'])) {
			$config['map']['lat'] = $config['lat'];
		}
		if (isset($config['lng']) && !isset($config['map']['lng'])) {
			$config['map']['lng'] = $config['lng'];
		}
		if (isset($config['size'])) {
			$config['div']['width'] = $config['size']['width'];
			$config['div']['height'] = $config['size']['height'];
		}

		$this->_config = $config;
		$this->_runtimeConfig = $this->_config;
	}

	/**
	 * @return string currentMapObject
	 */
	public function name(): string {
		return 'map' . static::$mapCount;
	}

	/**
	 * @return string currentContainerId
	 */
	public function id(): string {
		return $this->_runtimeConfig['div']['id'];
	}

	/**
	 * Make it possible to include multiple maps per page
	 * resets markers, popups etc
	 *
	 * @param bool $full true=optionsAsWell
	 * @return void
	 */
	public function reset(bool $full = true): void {
		static::$markerCount = static::$popupCount = 0;
		$this->markers = $this->popups = [];
		$this->_useCluster = false;
		if ($full) {
			$this->_runtimeConfig = $this->_config;
		}
	}

	/**
	 * Use a predefined tile provider preset.
	 *
	 * @param string $preset Preset name (osm, carto_light, carto_dark)
	 * @return void
	 */
	public function useTilePreset(string $preset): void {
		if (!isset($this->tilePresets[$preset])) {
			return;
		}
		$this->_config['tileLayer'] = $this->tilePresets[$preset];
		$this->_runtimeConfig['tileLayer'] = $this->tilePresets[$preset];
	}

	/**
	 * This the initialization point of the script.
	 * Returns the div container you can echo on the website.
	 *
	 * @param array<string, mixed> $options associative array of settings are passed
	 * @return string divContainer
	 */
	public function map(array $options = []): string {
		$this->reset();
		$this->_runtimeConfig = Hash::merge($this->_runtimeConfig, $options);
		$this->_runtimeConfig['map'] = $options + $this->_runtimeConfig['map'];

		if (!isset($this->_runtimeConfig['map']['lat']) || !isset($this->_runtimeConfig['map']['lng'])) {
			$this->_runtimeConfig['map']['lat'] = $this->_runtimeConfig['map']['defaultLat'];
			$this->_runtimeConfig['map']['lng'] = $this->_runtimeConfig['map']['defaultLng'];
		}
		if (!isset($this->_runtimeConfig['map']['zoom'])) {
			$this->_runtimeConfig['map']['zoom'] = $this->_runtimeConfig['map']['defaultZoom'];
		}

		$result = '';

		// autoinclude js/css?
		if ($this->_runtimeConfig['autoScript'] && !$this->_apiIncluded) {
			$cssUrl = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
			$jsUrl = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';

			$css = $this->Html->css($cssUrl, ['block' => $this->_runtimeConfig['block']]);
			$js = $this->Html->script($jsUrl, ['block' => $this->_runtimeConfig['block']]);
			$this->_apiIncluded = true;

			if (!$this->_runtimeConfig['block']) {
				$result .= $css . PHP_EOL . $js . PHP_EOL;
			}
		}

		// Rename div id if already used
		while (in_array($this->_runtimeConfig['div']['id'], $this->_mapIds, true)) {
			$this->_runtimeConfig['div']['id'] .= '-1';
		}
		$this->_mapIds[] = $this->_runtimeConfig['div']['id'];

		$map = '
		var lMarkers' . static::$mapCount . ' = [];
		var lPopups' . static::$mapCount . ' = [];
		';

		$map .= '
		var ' . $this->name() . ' = L.map("' . $this->_runtimeConfig['div']['id'] . '", ' . $this->_mapOptions() . ');
		' . $this->name() . '.setView([' . $this->_runtimeConfig['map']['lat'] . ', ' . $this->_runtimeConfig['map']['lng'] . '], ' . $this->_runtimeConfig['map']['zoom'] . ');
		';

		$map .= $this->_tileLayerJs();

		$this->map = $map;

		// Build div attributes
		$divOptions = $this->_runtimeConfig['div'];
		$divOptions['style'] = '';
		if (is_numeric($divOptions['width'])) {
			$divOptions['width'] .= 'px';
		}
		if (is_numeric($divOptions['height'])) {
			$divOptions['height'] .= 'px';
		}

		$divOptions['style'] .= 'width: ' . $divOptions['width'] . ';';
		$divOptions['style'] .= 'height: ' . $divOptions['height'] . ';';
		unset($divOptions['width'], $divOptions['height']);

		$defaultText = $this->_runtimeConfig['content'] ?? __('Map cannot be displayed!');
		$result .= $this->Html->tag('div', $defaultText, $divOptions);

		return $result;
	}

	/**
	 * Add a marker to the map.
	 *
	 * Options:
	 * - lat and lng (required)
	 * - title, content, icon, draggable, open (optional)
	 *
	 * @param array<string, mixed> $options
	 * @return int marker count
	 */
	public function addMarker(array $options): int {
		$defaults = $this->_runtimeConfig['marker'];
		$options += $defaults;

		$markerOptions = [];
		if (isset($options['title'])) {
			$markerOptions['title'] = $options['title'];
		}
		if (isset($options['draggable']) && $options['draggable']) {
			$markerOptions['draggable'] = true;
		}
		if (isset($options['icon'])) {
			$markerOptions['icon'] = $options['icon'];
		}

		$position = '[' . $options['lat'] . ', ' . $options['lng'] . ']';

		$markerOptionsJs = '';
		if ($markerOptions) {
			$markerOptionsJs = ', ' . $this->_buildJsObject($markerOptions);
		}

		// Add to cluster group if clustering is enabled, otherwise directly to map
		$addTo = $this->_useCluster ? 'clusterGroup' . static::$mapCount : $this->name();

		$marker = '
		var x' . static::$markerCount . ' = L.marker(' . $position . $markerOptionsJs . ').addTo(' . $addTo . ');
		lMarkers' . static::$mapCount . '.push(x' . static::$markerCount . ');
		';

		if (!empty($options['content'])) {
			$marker .= 'x' . static::$markerCount . '.bindPopup(' . $this->escapeString($options['content']) . ');
			';
			if (!empty($options['open'])) {
				$marker .= 'x' . static::$markerCount . '.openPopup();
				';
			}
		}

		$this->map .= $marker;
		$this->markers[static::$markerCount] = $marker;

		return static::$markerCount++;
	}

	/**
	 * Add a standalone popup to the map.
	 *
	 * @param array<string, mixed> $options
	 * @return int popup count
	 */
	public function addPopup(array $options = []): int {
		$defaults = $this->_runtimeConfig['popup'];
		$options += $defaults;

		$popupOptions = [];
		if (isset($options['maxWidth'])) {
			$popupOptions['maxWidth'] = $options['maxWidth'];
		}
		if (isset($options['autoClose'])) {
			$popupOptions['autoClose'] = $options['autoClose'];
		}

		$position = '[' . $options['lat'] . ', ' . $options['lng'] . ']';

		$popupOptionsJs = '';
		if ($popupOptions) {
			$popupOptionsJs = $this->_buildJsObject($popupOptions);
		}

		$popup = '
		var p' . static::$popupCount . ' = L.popup(' . $popupOptionsJs . ')
			.setLatLng(' . $position . ')
			.setContent(' . $this->escapeString($options['content']) . ')
			.openOn(' . $this->name() . ');
		lPopups' . static::$mapCount . '.push(p' . static::$popupCount . ');
		';

		$this->map .= $popup;
		$this->popups[static::$popupCount] = $popup;

		return static::$popupCount++;
	}

	/**
	 * Add a polyline between two or more points.
	 *
	 * @param array|string $from Location as array(lat, lng pair)
	 * @param array|string $to Location as array(lat, lng pair)
	 * @param array<string, mixed> $options
	 * @return void
	 */
	public function addPolyline(array|string $from, array|string $to, array $options = []): void {
		$defaults = $this->_runtimeConfig['polyline'];
		$options += $defaults;

		$points = [];
		if (is_array($from)) {
			$points[] = '[' . (float)$from['lat'] . ', ' . (float)$from['lng'] . ']';
		}
		if (is_array($to)) {
			$points[] = '[' . (float)$to['lat'] . ', ' . (float)$to['lng'] . ']';
		}

		$polylineOptions = [
			'color' => $options['color'],
			'weight' => $options['weight'],
			'opacity' => $options['opacity'],
		];

		$polyline = '
		L.polyline([' . implode(', ', $points) . '], ' . $this->_buildJsObject($polylineOptions) . ').addTo(' . $this->name() . ');
		';

		$this->map .= $polyline;
	}

	/**
	 * Add a polyline from an array of points.
	 *
	 * @param array<array<string, float>> $points Array of lat/lng pairs
	 * @param array<string, mixed> $options
	 * @return void
	 */
	public function addPolylineFromPoints(array $points, array $options = []): void {
		$defaults = $this->_runtimeConfig['polyline'];
		$options += $defaults;

		$jsPoints = [];
		foreach ($points as $point) {
			$jsPoints[] = '[' . (float)$point['lat'] . ', ' . (float)$point['lng'] . ']';
		}

		$polylineOptions = [
			'color' => $options['color'],
			'weight' => $options['weight'],
			'opacity' => $options['opacity'],
		];

		$polyline = '
		L.polyline([' . implode(', ', $jsPoints) . '], ' . $this->_buildJsObject($polylineOptions) . ').addTo(' . $this->name() . ');
		';

		$this->map .= $polyline;
	}

	/**
	 * Add a circle to the map.
	 *
	 * @param array<string, mixed> $options
	 * @return void
	 */
	public function addCircle(array $options): void {
		$defaults = $this->_runtimeConfig['circle'];
		$options += $defaults;

		$position = '[' . (float)$options['lat'] . ', ' . (float)$options['lng'] . ']';

		$circleOptions = [
			'color' => $options['color'],
			'fillColor' => $options['fillColor'],
			'fillOpacity' => $options['fillOpacity'],
			'radius' => $options['radius'],
		];

		$circle = '
		L.circle(' . $position . ', ' . $this->_buildJsObject($circleOptions) . ').addTo(' . $this->name() . ');
		';

		$this->map .= $circle;
	}

	/**
	 * Add a polygon to the map.
	 *
	 * @param array<array<string, float>> $points Array of lat/lng pairs
	 * @param array<string, mixed> $options
	 * @return void
	 */
	public function addPolygon(array $points, array $options = []): void {
		$defaults = $this->_runtimeConfig['polygon'];
		$options += $defaults;

		$jsPoints = [];
		foreach ($points as $point) {
			$jsPoints[] = '[' . (float)$point['lat'] . ', ' . (float)$point['lng'] . ']';
		}

		$polygonOptions = [
			'color' => $options['color'],
			'fillColor' => $options['fillColor'],
			'fillOpacity' => $options['fillOpacity'],
		];

		$polygon = '
		L.polygon([' . implode(', ', $jsPoints) . '], ' . $this->_buildJsObject($polygonOptions) . ').addTo(' . $this->name() . ');
		';

		$this->map .= $polygon;
	}

	/**
	 * Enable marker clustering for this map.
	 *
	 * This will group nearby markers into clusters that expand on click.
	 * Requires the Leaflet.markercluster plugin (auto-included when autoScript is true).
	 *
	 * @link https://github.com/Leaflet/Leaflet.markercluster
	 * @param array<string, mixed> $options Cluster options (showCoverageOnHover, maxClusterRadius, etc.)
	 * @return void
	 */
	public function enableClustering(array $options = []): void {
		$this->_useCluster = true;

		// Include markercluster scripts if autoScript is enabled
		if ($this->_runtimeConfig['autoScript'] && !$this->_clusterIncluded) {
			$cssUrl = 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css';
			$cssDefaultUrl = 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css';
			$jsUrl = 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js';

			$this->Html->css($cssUrl, ['block' => $this->_runtimeConfig['block']]);
			$this->Html->css($cssDefaultUrl, ['block' => $this->_runtimeConfig['block']]);
			$this->Html->script($jsUrl, ['block' => $this->_runtimeConfig['block']]);
			$this->_clusterIncluded = true;
		}

		// Initialize the cluster group
		$optionsJs = $options ? $this->_buildJsObject($options) : '{}';
		$cluster = '
		var clusterGroup' . static::$mapCount . ' = L.markerClusterGroup(' . $optionsJs . ');
		';

		$this->map .= $cluster;
	}

	/**
	 * Add a GeoJSON layer to the map.
	 *
	 * @param array<string, mixed> $data GeoJSON data
	 * @param array<string, mixed> $options
	 * @return void
	 */
	public function addGeoJson(array $data, array $options = []): void {
		$geoJsonJs = json_encode($data);

		$optionsJs = '';
		if ($options) {
			$optionsJs = ', ' . $this->_buildJsObject($options);
		}

		$geoJson = '
		L.geoJSON(' . $geoJsonJs . $optionsJs . ').addTo(' . $this->name() . ');
		';

		$this->map .= $geoJson;
	}

	/**
	 * Add a custom tile layer.
	 *
	 * @param string $url Tile URL template
	 * @param array<string, mixed> $options
	 * @return void
	 */
	public function addTileLayer(string $url, array $options = []): void {
		$optionsJs = '';
		if ($options) {
			$optionsJs = ', ' . $this->_buildJsObject($options);
		}

		$tileLayer = '
		L.tileLayer("' . $url . '"' . $optionsJs . ').addTo(' . $this->name() . ');
		';

		$this->map .= $tileLayer;
	}

	/**
	 * Add custom JS.
	 *
	 * @param string $js Custom JS
	 * @return void
	 */
	public function addCustom(string $js): void {
		$this->map .= $js;
	}

	/**
	 * This method returns the javascript for the current map container.
	 * Including script tags.
	 *
	 * @return string
	 */
	public function script(): string {
		$script = '<script>
		' . $this->finalize(true) . '
</script>';

		return $script;
	}

	/**
	 * Finalize the map and write the javascript to the buffer.
	 * Make sure that your view does also output the buffer at some place!
	 *
	 * @param bool $return If the output should be returned instead
	 * @return string|null Javascript if $return is true
	 */
	public function finalize(bool $return = false): ?string {
		$mapInit = $this->map;

		// Add cluster group to map if clustering was enabled
		if ($this->_useCluster) {
			$mapInit .= '
		' . $this->name() . '.addLayer(clusterGroup' . static::$mapCount . ');
			';
		}

		if ($this->_runtimeConfig['autoCenter']) {
			$mapInit .= $this->_autoCenter();
		}

		// Wrap in appropriate loader based on clustering
		if ($this->_useCluster) {
			// When clustering is enabled, wait for markercluster library to load
			$script = '
(function() {
	function initMap' . static::$mapCount . '() {
		' . $mapInit . '
	}

	function waitForCluster() {
		if (typeof L !== "undefined" && typeof L.markerClusterGroup === "function") {
			jQuery(document).ready(initMap' . static::$mapCount . ');
		} else {
			setTimeout(waitForCluster, 50);
		}
	}
	waitForCluster();
})();';
		} else {
			$script = '
jQuery(document).ready(function() {
		' . $mapInit . '
});';
		}

		static::$mapCount++;
		if (!$return) {
			$this->Html->scriptBlock($script, ['block' => true]);

			return null;
		}

		return $script;
	}

	/**
	 * Json encode string
	 *
	 * @param mixed $content
	 * @return string JSON
	 */
	public function escapeString(mixed $content): string {
		$result = json_encode($content);
		if ($result === false) {
			return '';
		}

		return $result;
	}

	/**
	 * Auto center map to fit all markers
	 *
	 * @return string autoCenterCommands
	 */
	protected function _autoCenter(): string {
		return '
		if (lMarkers' . static::$mapCount . '.length > 0) {
			var group = new L.featureGroup(lMarkers' . static::$mapCount . ');
			' . $this->name() . '.fitBounds(group.getBounds());
		}
		';
	}

	/**
	 * @return string JSON like js string
	 */
	protected function _mapOptions(): string {
		$options = $this->_runtimeConfig['map'];

		$mapOptions = [];
		if (isset($options['scrollWheelZoom'])) {
			$mapOptions['scrollWheelZoom'] = $options['scrollWheelZoom'];
		}
		if (isset($options['zoomControl'])) {
			$mapOptions['zoomControl'] = $options['zoomControl'];
		}
		if (isset($options['dragging'])) {
			$mapOptions['dragging'] = $options['dragging'];
		}

		return $this->_buildJsObject($mapOptions);
	}

	/**
	 * Generate tile layer JavaScript
	 *
	 * @return string
	 */
	protected function _tileLayerJs(): string {
		$tileConfig = $this->_runtimeConfig['tileLayer'];
		$url = $tileConfig['url'];
		$options = $tileConfig['options'] ?? [];

		return '
		L.tileLayer("' . $url . '", ' . $this->_buildJsObject($options) . ').addTo(' . $this->name() . ');
		';
	}

	/**
	 * Build a JavaScript object from PHP array
	 *
	 * @param array<string, mixed> $options
	 * @return string
	 */
	protected function _buildJsObject(array $options): string {
		$result = json_encode($options);
		if ($result === false) {
			return '{}';
		}

		return $result;
	}

}
