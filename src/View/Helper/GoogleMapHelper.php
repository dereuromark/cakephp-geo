<?php
namespace Geo\View\Helper;

use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\View\Helper;
use Cake\Utility\Hash;
use Geo\View\Helper\JsBaseEngineTrait;
use Cake\Core\Exception\Exception;

/**
 * This is a CakePHP helper that helps users to integrate GoogleMap v3
 * into their application by only writing PHP code. This helper depends on jQuery.
 *
 * Capable of resetting itself (full or partly) for multiple maps on a single view.
 *
 * CodeAPI: http://code.google.com/intl/de-DE/apis/maps/documentation/javascript/basics.html
 * Icons/Images: http://gmapicons.googlepages.com/home
 *
 * @author Rajib Ahmed
 * @author Mark Scherer
 * @link http://www.dereuromark.de/2010/12/21/googlemapsv3-cakephp-helper/
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version 1.5
 *
 * Changelog:
 *
 * v1.2: Cake2.x ready
 *
 * v1.3: E_STRICT compliant methods (url now mapUrl, link now mapLink)
 *
 * v1.4: Better handling of script output and directions added
 * You can now either keep map() + script(), or you can now write the script to the buffer with
 * map() + finalize(). You can then decide wether the JS should be in the head or the footer of your layout.
 * Don't forget to put `echo $this->Html->block('script');` somewhere in your layout then, though.
 * You can now also add directions using addDirections().
 *
 * v1.5: "open" for markers
 * Markers can be open now by default on page load. Works for both single and multi window mode.
 * Lots of cleanup and CS corrections.
 *
 * v1.6 CakePHP3.x compatible
 */
class GoogleMapHelper extends Helper {

	use JsBaseEngineTrait;

	public static $mapCount = 0;

	public static $markerCount = 0;

	public static $iconCount = 0;

	public static $infoWindowCount = 0;

	public static $infoContentCount = 0;

	const API = 'maps.google.com/maps/api/js?';

	const STATIC_API = 'maps.google.com/maps/api/staticmap?';

	const TYPE_ROADMAP = 'R';

	const TYPE_HYBRID = 'H';

	const TYPE_SATELLITE = 'S';

	const TYPE_TERRAIN = 'T';

	public $types = [
		self::TYPE_ROADMAP => 'ROADMAP',
		self::TYPE_HYBRID => 'HYBRID',
		self::TYPE_SATELLITE => 'SATELLITE',
		self::TYPE_TERRAIN => 'TERRAIN'
	];

	const TRAVEL_MODE_DRIVING = 'D';

	const TRAVEL_MODE_BICYCLING = 'B';

	const TRAVEL_MODE_TRANSIT = 'T';

	const TRAVEL_MODE_WALKING = 'W';

	public $travelModes = [
		self::TRAVEL_MODE_DRIVING => 'DRIVING',
		self::TRAVEL_MODE_BICYCLING => 'BICYCLING',
		self::TRAVEL_MODE_TRANSIT => 'TRANSIT',
		self::TRAVEL_MODE_WALKING => 'WALKING'
	];

	/**
	 * Needed helpers
	 *
	 * @var array
	 */
	public $helpers = ['Html'];

	/**
	 * Google maker config instance variable
	 *
	 * @var array
	 */
	public $markers = [];

	public $infoWindows = [];

	public $infoContents = [];

	public $icons = [];

	public $matching = [];

	public $map = '';

	protected $_mapIds = []; // Remember already used ones (valid xhtml contains ids not more than once)

	/**
	 * Default settings
	 *
	 * @var array
	 */
	protected $_defaultOptions = [
		'zoom' => null, // global, both map and staticMap
		'lat' => null, // global, both map and staticMap
		'lng' => null, // global, both map and staticMap
		'type' => self::TYPE_ROADMAP,
		'map' => [
			'api' => null,
			'streetViewControl' => false,
			'navigationControl' => true,
			'mapTypeControl' => true,
			'scaleControl' => true,
			'scrollwheel' => false,
			'keyboardShortcuts' => true,
			'typeOptions' => [],
			'navOptions' => [],
			'scaleOptions' => [],
			'defaultLat' => 51, // only last fallback, use Configure::write('Google.lat', ...); to define own one
			'defaultLng' => 11, // only last fallback, use Configure::write('Google.lng', ...); to define own one
			'defaultZoom' => 5,
		],
		'staticMap' => [
			'size' => '300x300',
			'format' => 'png',
			'mobile' => false,
			//'shadow' => true // for icons
		],
		'geolocate' => false,
		'sensor' => false,
		'language' => null,
		'region' => null,
		'showMarker' => true,
		//'showInfoWindow' => true,
		'infoWindow' => [
			'content' => '',
			'useMultiple' => false, // Using single infowindow object for all
			'maxWidth' => 300,
			'lat' => null,
			'lng' => null,
			'pixelOffset' => 0,
			'zIndex' => 200,
			'disableAutoPan' => false
		],
		'marker' => [
			//'autoCenter' => true,
			'animation' => null, // BOUNCE or DROP  https://developers.google.com/maps/documentation/javascript/3.exp/reference#Animation
			'icon' => null, // => default (red marker) //http://google-maps-icons.googlecode.com/files/home.png
			'title' => null,
			'shadow' => null,
			'shape' => null,
			'zIndex' => null,
			'draggable' => false,
			'cursor' => null,
			'directions' => false, // add form with directions
			'open' => false, // New in 1.5
		],
		'div' => [
			'id' => 'map_canvas',
			'width' => '100%',
			'height' => '400px',
			'class' => 'map',
			'escape' => true
		],
		'event' => [
		],
		'animation' => [
			//TODO
		],
		'polyline' => [
			'color' => '#FF0000',
			'opacity' => 1.0,
			'weight' => 2,
		],
		'directions' => [
			'travelMode' => self::TRAVEL_MODE_DRIVING,
			'unitSystem' => 'METRIC',
			'directionsDiv' => null,
		],
		'callbacks' => [
			'geolocate' => null //TODO
		],
		'plugins' => [
			'keydragzoom' => false, // http://google-maps-utility-library-v3.googlecode.com/svn/tags/keydragzoom/
			'markermanager' => false, // http://google-maps-utility-library-v3.googlecode.com/svn/tags/markermanager/
			'markercluster' => false, // http://google-maps-utility-library-v3.googlecode.com/svn/tags/markerclusterer/
		],
		'autoCenter' => false, // try to fit all markers in (careful, all zooms values are omitted)
		'autoScript' => false, // let the helper include the necessary js script links
		'inline' => false, // for scripts
		'localImages' => false,
		'https' => null // auto detect
	];

	protected $_apiIncluded = false;

	protected $_gearsIncluded = false;

	protected $_located = false;

	public function __construct($View = null, $config = []) {
		$google = (array)Configure::read('GoogleMap');
		$defaults = $this->_defaultOptions;
		if (!empty($google['api'])) {
			$defaults['map']['api'] = $google['api'];
		}
		if (!empty($google['zoom'])) {
			$defaults['map']['zoom'] = $google['zoom'];
		}
		if (!empty($google['lat'])) {
			$defaults['map']['lat'] = $google['lat'];
		}
		if (!empty($google['lng'])) {
			$defaults['map']['lng'] = $google['lng'];
		}
		if (!empty($google['type'])) {
			$defaults['map']['type'] = $google['type'];
		}
		if (!empty($google['size'])) {
			$defaults['div']['width'] = $google['size']['width'];
			$defaults['div']['height'] = $google['size']['height'];
		}
		if (!empty($google['staticSize'])) {
			$defaults['staticMap']['size'] = $google['staticSize'];
		}
		// the following are convenience defaults - if not available the map lat/lng/zoom defaults will be used
		if (!empty($google['staticZoom'])) {
			$defaults['staticMap']['zoom'] = $google['staticZoom'];
		}
		if (!empty($google['staticLat'])) {
			$defaults['staticMap']['lat'] = $google['staticLat'];
		}
		if (!empty($google['staticLng'])) {
			$defaults['staticMap']['lng'] = $google['staticLng'];
		}
		if (isset($google['localImages'])) {
			if ($google['localImages'] === true) {
				$google['localImages'] = Router::url('/img/google_map/', true);
			}
			$defaults['localImages'] = $google['localImages'];
		}

		$config = Hash::merge($defaults, $config);
		parent::__construct($View, $config);
	}

/** Google Maps JS **/

	/**
	 * JS maps.google API url
	 * Like:
	 * http://maps.google.com/maps/api/js?sensor=true
	 * Adds Key - more variables could be added after it with "&key=value&..."
	 * - region
	 *
	 * @param bool $sensor
	 * @param string $language (iso2: en, de, ja, ...)
	 * @param string $append (more key-value-pairs to append)
	 * @return string Full URL
	 */
	public function apiUrl($sensor = false, $api = null, $language = null, $append = null) {
		$url = $this->_protocol() . static::API;

		$url .= 'sensor=' . ($sensor ? 'true' : 'false');
		if (!empty($language)) {
			$url .= '&language=' . $language;
		}
		if (!empty($api)) {
			$this->_config['map']['api'] = $api;
		}
		if (!empty($this->_config['map']['api'])) {
			$url .= '&v=' . $this->_config['map']['api'];
		}
		if (!empty($append)) {
			$url .= $append;
		}
		$this->_apiIncluded = true;
		return $url;
	}

	/**
	 * @deprecated
	 */
	public function gearsUrl() {
		$this->_gearsIncluded = true;
		$url = $this->_protocol() . 'code.google.com/apis/gears/gears_init.js';
		return $url;
	}

	/**
	 * @return string currentMapObject
	 */
	public function name() {
		return 'map' . static::$mapCount;
	}

	/**
	 * @return string currentContainerId
	 */
	public function id() {
		return $this->_config['div']['id'];
	}

	/**
	 * Make it possible to include multiple maps per page
	 * resets markers, infoWindows etc
	 * @param full: true=optionsAsWell
	 * @return void
	 */
	public function reset($full = true) {
		static::$markerCount = static::$infoWindowCount = 0;
		$this->markers = $this->infoWindows = [];
		if ($full) {
			$this->_config = $this->_defaultOptions;
		}
	}

	/**
	 * Set the controls of current map
	 *
	 * Control options
	 * - zoom, scale, overview: TRUE/FALSE
	 *
	 * - map: FALSE, small, large
	 * - type: FALSE, normal, menu, hierarchical
	 * TIP: faster/shorter by using only the first character (e.g. "H" for "hierarchical")
	 *
	 * @param array $options
	 * @return void
	 */
	public function setControls($options = []) {
		if (isset($options['streetView'])) {
			$this->_config['map']['streetViewControl'] = $options['streetView'];
		}
		if (isset($options['zoom'])) {
			$this->_config['map']['scaleControl'] = $options['zoom'];
		}
		if (isset($options['scrollwheel'])) {
			$this->_config['map']['scrollwheel'] = $options['scrollwheel'];
		}
		if (isset($options['keyboardShortcuts'])) {
			$this->_config['map']['keyboardShortcuts'] = $options['keyboardShortcuts'];
		}
		if (isset($options['type'])) {
			$this->_config['map']['type'] = $options['type'];
		}
	}

	/**
	 * This the initialization point of the script
	 * Returns the div container you can echo on the website
	 *
	 * @param array $options associative array of settings are passed
	 * @return string divContainer
	 */
	public function map($options = []) {
		$this->reset();
		$this->_config = Hash::merge($this->_config, $options);
		$this->_config['map'] = array_merge($this->_config['map'], ['zoom' => $this->_config['zoom'], 'lat' => $this->_config['lat'], 'lng' => $this->_config['lng'], 'type' => $this->_config['type']], $options);
		if (!$this->_config['map']['lat'] || !$this->_config['map']['lng']) {
			$this->_config['map']['lat'] = $this->_config['map']['defaultLat'];
			$this->_config['map']['lng'] = $this->_config['map']['defaultLng'];
			$this->_config['map']['zoom'] = $this->_config['map']['defaultZoom'];
		} elseif (!$this->_config['map']['zoom']) {
			$this->_config['map']['zoom'] = $this->_config['map']['defaultZoom'];
		}

		$result = '';

		// autoinclude js?
		if (!empty($this->_config['autoScript']) && !$this->_apiIncluded) {
			$res = $this->Html->script($this->apiUrl(), ['inline' => $this->_config['inline']]);

			if ($this->_config['inline']) {
				$result .= $res . PHP_EOL;
			}
			// usually already included
			//http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js
		}
		// still not very common: http://code.google.com/intl/de-DE/apis/maps/documentation/javascript/basics.html
		if (false && !empty($this->_config['autoScript']) && !$this->_gearsIncluded) {
			$res = $this->Html->script($this->gearsUrl(), ['inline' => $this->_config['inline']]);
			if ($this->_config['inline']) {
				$result .= $res . PHP_EOL;
			}
		}

		$map = "
			var initialLocation = " . $this->_initialLocation() . ";
			var browserSupportFlag = new Boolean();
			var myOptions = " . $this->_mapOptions() . ";

			// deprecated
			gMarkers" . static::$mapCount . " = new Array();
			gInfoWindows" . static::$mapCount . " = new Array();
			gWindowContents" . static::$mapCount . " = new Array();
		";

		#rename "map_canvas" to "map_canvas1", ... if multiple maps on one page
		while (in_array($this->_config['div']['id'], $this->_mapIds)) {
			$this->_config['div']['id'] .= '-1'; //TODO: improve
		}
		$this->_mapIds[] = $this->_config['div']['id'];

		$map .= "
			var " . $this->name() . " = new google.maps.Map(document.getElementById(\"" . $this->_config['div']['id'] . "\"), myOptions);
			";
		$this->map = $map;

		$this->_config['div']['style'] = '';
		if (is_numeric($this->_config['div']['width'])) {
			$this->_config['div']['width'] .= 'px';
		}
		if (is_numeric($this->_config['div']['height'])) {
			$this->_config['div']['height'] .= 'px';
		}

		$this->_config['div']['style'] .= 'width: ' . $this->_config['div']['width'] . ';';
		$this->_config['div']['style'] .= 'height: ' . $this->_config['div']['height'] . ';';
		unset($this->_config['div']['width']); unset($this->_config['div']['height']);

		$defaultText = isset($this->_config['content']) ? $this->_config['content'] : __('Map cannot be displayed!');
		$result .= $this->Html->tag('div', $defaultText, $this->_config['div']);

		return $result;
	}

	/**
	 * Generate a new LatLng object with the current lat and lng.
	 *
	 * @return string
	 */
	protected function _initialLocation() {
		if ($this->_config['map']['lat'] && $this->_config['map']['lng']) {
			return "new google.maps.LatLng(" . $this->_config['map']['lat'] . ", " . $this->_config['map']['lng'] . ")";
		}
		$this->_config['autoCenter'] = true;
		return 'false';
	}

	/**
	 * Add a marker to the map.
	 *
	 * Options:
	 * - lat and lng or address (to geocode on demand, not recommended, though)
	 * - title, content, icon, directions, maxWidth, open (optional)
	 *
	 * Note, that you can only set one marker to "open" for single window mode.
	 * If you declare multiple ones, the last one will be the one shown as open.
	 *
	 * @param array $options
	 * @return mixed Integer marker count or boolean false on failure
	 * @throws Exception
	 */
	public function addMarker($options) {
		$defaults = $this->_config['marker'];
		if (isset($options['icon']) && is_array($options['icon'])) {
			$defaults = array_merge($defaults, $options['icon']);
			unset($options['icon']);
		}
		$options += $defaults;

		$params = [];
		$params['map'] = $this->name();

		if (isset($options['title'])) {
			$params['title'] = json_encode($options['title']);
		}
		if (isset($options['icon'])) {
			$params['icon'] = $options['icon'];
			if (is_int($params['icon'])) {
				$params['icon'] = 'gIcons' . static::$mapCount . '[' . $params['icon'] . ']';
			} else {
				$params['icon'] = json_encode($params['icon']);
			}
		}
		if (isset($options['shadow'])) {
			$params['shadow'] = $options['shadow'];
			if (is_int($params['shadow'])) {
				$params['shadow'] = 'gIcons' . static::$mapCount . '[' . $params['shadow'] . ']';
			} else {
				$params['shadow'] = json_encode($params['shadow']);
			}
		}
		if (isset($options['shape'])) {
			$params['shape'] = $options['shape'];
		}
		if (isset($options['zIndex'])) {
			$params['zIndex'] = $options['zIndex'];
		}
		if (isset($options['animation'])) {
			$params['animation'] = 'google.maps.Animation.' . strtoupper($options['animation']);
		}

		// geocode if necessary
		if (!isset($options['lat']) || !isset($options['lng'])) {
			$this->map .= "
var geocoder = new google.maps.Geocoder();

function geocodeAddress(address) {
	geocoder.geocode({'address': address}, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK) {

			x" . static::$markerCount . " = new google.maps.Marker({
				position: results[0].geometry.location,
				" . $this->_toObjectParams($params, false, false) . "
			});
			gMarkers" . static::$mapCount . " .push(
				x" . static::$markerCount . "
			);
			return results[0].geometry.location;
		} else {
			//alert('Geocoding was not successful for the following reason: ' + status);
			return null;
		}
	});
}";
			if (!isset($options['address'])) {
				throw new Exception('Either use lat/lng or address to add a marker');
			}
			$position = 'geocodeAddress(\'' . h($options['address']) . '\')';
		} else {
			$position = "new google.maps.LatLng(" . $options['lat'] . "," . $options['lng'] . ")";
		}

		$marker = "
			var x" . static::$markerCount . " = new google.maps.Marker({
				position: " . $position . ",
				" . $this->_toObjectParams($params, false, false) . "
			});
			gMarkers" . static::$mapCount . " .push(
				x" . static::$markerCount . "
			);
		";
		$this->map .= $marker;

		if (!empty($options['directions'])) {
			$options['content'] .= $this->_directions($options['directions'], $options);
		}

		// Fill popup windows
		if (!empty($options['content']) && $this->_config['infoWindow']['useMultiple']) {
			$x = $this->addInfoWindow(['content' => $options['content']]);
			$this->addEvent(static::$markerCount, $x, $options['open']);

		} elseif (!empty($options['content'])) {
			if (!isset($this->_config['marker']['infoWindow'])) {
				$this->_config['marker']['infoWindow'] = $this->addInfoWindow();
			}

			$x = $this->addInfoContent($options['content']);
			$event = "
			gInfoWindows" . static::$mapCount . "[" . $this->_config['marker']['infoWindow'] . "]. setContent(gWindowContents" . static::$mapCount . "[" . $x . "]);
			gInfoWindows" . static::$mapCount . "[" . $this->_config['marker']['infoWindow'] . "].open(" . $this->name() . ", gMarkers" . static::$mapCount . "[" . $x . "]);
			";
			$this->addCustomEvent(static::$markerCount, $event);

			if (!empty($options['open'])) {
				$this->addCustom($event);
			}
		}

		// Custom matching event?
		if (isset($options['id'])) {
			$this->matching[$options['id']] = static::$markerCount;
		}

		return static::$markerCount++;
	}

	/**
	 * Build directions form (type get) for directions inside infoWindows
	 *
	 * @param mixed $directions
	 * - bool TRUE for autoDirections (using lat/lng)
	 * @param array $options
	 * - options array of marker for autoDirections etc (optional)
	 * @return HTML
	 */
	protected function _directions($directions, $markerOptions = []) {
		$options = [
			'from' => null,
			'to' => null,
			'label' => __('Enter your address'),
			'submit' => __('Get directions'),
			'escape' => true,
			'zoom' => null, // auto
		];
		if ($directions === true) {
			$options['to'] = $markerOptions['lat'] . ',' . $markerOptions['lng'];
		} elseif (is_array($directions)) {
			$options = array_merge($options, $directions);
		}
		if (empty($options['to']) && empty($options['from'])) {
			return '';
		}
		$form = '<form action="http://maps.google.com/maps" method="get" target="_blank">';
		$form .= $options['escape'] ? h($options['label']) : $options['label'];
		if (!empty($options['from'])) {
			$form .= '<input type="hidden" name="saddr" value="' . $options['from'] . '" />';
		} else {
			$form .= '<input type="text" name="saddr" />';
		}
		if (!empty($options['to'])) {
			$form .= '<input type="hidden" name="daddr" value="' . $options['to'] . '" />';
		} else {
			$form .= '<input type="text" name="daddr" />';
		}
		if (isset($options['zoom'])) {
			$form .= '<input type="hidden" name="z" value="' . $options['zoom'] . '" />';
		}
		$form .= '<input type="submit" value="' . $options['submit'] . '" />';
		$form .= '</form>';

		return '<div class="directions">' . $form . '</div>';
	}

	/**
	 * @param string $content
	 * @return int Current marker counter
	 */
	public function addInfoContent($content) {
		$this->infoContents[static::$markerCount] = $this->escapeString($content);
		$event = "
			gWindowContents" . static::$mapCount . ".push(" . $this->escapeString($content) . ");
			";
		$this->addCustom($event);

		//TODO: own count?
		return static::$markerCount;
	}

	public $setIcons = [
		'color' => 'http://www.google.com/mapfiles/marker%s.png',
		'alpha' => 'http://www.google.com/mapfiles/marker%s%s.png',
		'numeric' => 'http://google-maps-icons.googlecode.com/files/%s%s.png',
		'special' => 'http://google-maps-icons.googlecode.com/files/%s.png'
	];

	/**
	 * Get a custom icon set
	 *
	 * @param color: green, red, purple, ... or some special ones like "home", ...
	 * @param char: A...Z or 0...20/100 (defaults to none)
	 * @param size: s, m, l (defaults to medium)
	 * NOTE: for special ones only first parameter counts!
	 * @return array: array(icon, shadow, shape, ...)
	 */
	public function iconSet($color, $char = null, $size = 'm') {
		$colors = ['red', 'green', 'yellow', 'blue', 'purple', 'white', 'black'];
		if (!in_array($color, $colors)) {
			$color = 'red';
		}

		if (!empty($this->_config['localImages'])) {
			$this->setIcons['color'] = $this->_config['localImages'] . 'marker%s.png';
			$this->setIcons['alpha'] = $this->_config['localImages'] . 'marker%s%s.png';
			$this->setIcons['numeric'] = $this->_config['localImages'] . '%s%s.png';
			$this->setIcons['special'] = $this->_config['localImages'] . '%s.png';
		}

		if (!empty($char)) {
			if ($color === 'red') {
				$color = '';
			} else {
				$color = '_' . $color;
			}
			$url = sprintf($this->setIcons['alpha'], $color, $char);
		} else {
			if ($color === 'red') {
				$color = '';
			} else {
				$color = '_' . $color;
			}
			$url = sprintf($this->setIcons['color'], $color);
		}

/*
var iconImage = new google.maps.MarkerImage('images/' + images[0] + ' .png',
	new google.maps.Size(iconData[images[0]].width, iconData[images[0]].height),
	new google.maps.Point(0,0),
	new google.maps.Point(0, 32)
);

var iconShadow = new google.maps.MarkerImage('images/' + images[1] + ' .png',
	new google.maps.Size(iconData[images[1]].width, iconData[images[1]].height),
	new google.maps.Point(0,0),
	new google.maps.Point(0, 32)
);

var iconShape = {
	coord: [1, 1, 1, 32, 32, 32, 32, 1],
	type: 'poly'
};
*/

		$shadow = 'http://www.google.com/mapfiles/shadow50.png';
		$res = [
			'url' => $url,
			'icon' => $this->icon($url, ['size' => ['width' => 20, 'height' => 34]]),
			'shadow' => $this->icon($shadow, ['size' => ['width' => 37, 'height' => 34], 'shadow' => ['width' => 10, 'height' => 34]])
		];
		return $res;
	}

	/**
	 * Generate icon array.
	 *
	 * custom icon: http://thydzik.com/thydzikGoogleMap/markerlink.php?text=?&color=FFFFFF
	 * custom icons: http://code.google.com/p/google-maps-icons/wiki/NumericIcons#Lettered_Balloons_from_A_to_Z,_in_10_Colors
	 * custom shadows: http://www.cycloloco.com/shadowmaker/shadowmaker.htm
	 *
	 * @param string $imageUrl (http://...)
	 * @param string $shadowImageUrl (http://...)
	 * @param array $imageOptions
	 * @param array $shadowImageOptions
	 * @return array Resulting array
	 */
	public function addIcon($image, $shadow = null, $imageOptions = [], $shadowOptions = []) {
		$res = ['url' => $image];
		$res['icon'] = $this->icon($image, $imageOptions);
		if ($shadow) {
			$last = $this->_iconRemember[$res['icon']];
			if (!isset($shadowOptions['anchor'])) {
				$shadowOptions['anchor'] = [];
			}
			$shadowOptions['anchor'] = array_merge($shadowOptions['anchor'], $last['options']['anchor']);

			$res['shadow'] = $this->icon($shadow, $shadowOptions);
		}
		return $res;
	}

	protected $_iconRemember = [];

	/**
	 * Generate icon object
	 *
	 * @param string $url (required)
	 * @param array $options (optional):
	 * - size: array(width=>x, height=>y)
	 * - origin: array(width=>x, height=>y)
	 * - anchor: array(width=>x, height=>y)
	 * @return int Icon count
	 */
	public function icon($url, $options = []) {
		// The shadow image is larger in the horizontal dimension
		// while the position and offset are the same as for the main image.
		if (empty($options['size'])) {
			if ($data = @getimagesize($url)) {
				$options['size']['width'] = $data[0];
				$options['size']['height'] = $data[1];
			} else {
				$options['size']['width'] = $options['size']['height'] = 0;
			}
		}
		if (empty($options['anchor'])) {
			$options['anchor']['width'] = intval($options['size']['width'] / 2);
			$options['anchor']['height'] = $options['size']['height'];
		}
		if (empty($options['origin'])) {
			$options['origin']['width'] = $options['origin']['height'] = 0;
		}
		if (isset($options['shadow'])) {
			$options['anchor'] = $options['shadow'];
		}

		$icon = 'new google.maps.MarkerImage(\'' . $url . '\',
	new google.maps.Size(' . $options['size']['width'] . ', ' . $options['size']['height'] . '),
	new google.maps.Point(' . $options['origin']['width'] . ', ' . $options['origin']['height'] . '),
	new google.maps.Point(' . $options['anchor']['width'] . ', ' . $options['anchor']['height'] . ')
)';
		$this->icons[static::$iconCount] = $icon;
		$this->_iconRemember[static::$iconCount] = ['url' => $url, 'options' => $options, 'id' => static::$iconCount];
		return static::$iconCount++;
	}

	/**
	 * Creates a new InfoWindow.
	 *
	 * @param array $options
	 * - lat, lng, content, maxWidth, pixelOffset, zIndex
	 * @return int windowCount
	 */
	public function addInfoWindow($options = []) {
		$defaults = $this->_config['infoWindow'];
		$options += $defaults;

		if (!empty($options['lat']) && !empty($options['lng'])) {
			$position = "new google.maps.LatLng(" . $options['lat'] . ", " . $options['lng'] . ")";
		} else {
			$position = " " . $this->name() . " .getCenter()";
		}

		$windows = "
			gInfoWindows" . static::$mapCount . ".push(new google.maps.InfoWindow({
					position: {$position},
					content: " . $this->escapeString($options['content']) . ",
					maxWidth: {$options['maxWidth']},
					pixelOffset: {$options['pixelOffset']}
					/*zIndex: {$options['zIndex']},*/
			}));
			";
		$this->map .= $windows;
		return static::$infoWindowCount++;
	}

	/**
	 * Add event to open marker on click.
	 *
	 * @param int $marker
	 * @param int $infoWindow
	 * @param bool $open Also open it right away.
	 * @return void
	 */
	public function addEvent($marker, $infoWindow, $open = false) {
		$this->map .= "
			google.maps.event.addListener(gMarkers" . static::$mapCount . "[{$marker}], 'click', function() {
				gInfoWindows" . static::$mapCount . "[$infoWindow].open(" . $this->name() . ", this);
			});
		";
		if ($open) {
			$event = 'gInfoWindows' . static::$mapCount . "[$infoWindow].open(" . $this->name() .
				", gMarkers" . static::$mapCount . "[" . $marker . "]);";
			$this->addCustom($event);
		}
	}

	/**
	 * Add a custom event for a marker on click.
	 *
	 * @param int $marker
	 * @param string $event (js)
	 * @return void
	 */
	public function addCustomEvent($marker, $event) {
		$this->map .= "
			google.maps.event.addListener(gMarkers" . static::$mapCount . "[{$marker}], 'click', function() {
				$event
			});
		";
	}

	/**
	 * Add custom JS.
	 *
	 * @param string $js Custom JS
	 * @return void
	 */
	public function addCustom($js) {
		$this->map .= $js;
	}

	/**
	 * Add directions to the map.
	 *
	 * @param array|string $from Location as array(fixed lat/lng pair) or string (to be geocoded at runtime)
	 * @param array|string $to Location as array(fixed lat/lng pair) or string (to be geocoded at runtime)
	 * @param array $options
	 * - directionsDiv: Div to place directions in text form
	 * - travelMode: TravelMode,
	 * - transitOptions: TransitOptions,
	 * - unitSystem: UnitSystem (IMPERIAL, METRIC, AUTO),
	 * - waypoints[]: DirectionsWaypoint,
	 * - optimizeWaypoints: Boolean,
	 * - provideRouteAlternatives: Boolean,
	 * - avoidHighways: Boolean,
	 * - avoidTolls: Boolean
	 * - region: String
	 * @see https://developers.google.com/maps/documentation/javascript/3.exp/reference#DirectionsRequest
	 * @return void
	 */
	public function addDirections($from, $to, $options = []) {
		$id = 'd' . static::$markerCount++;
		$defaults = $this->_config['directions'];
		$options += $defaults;
		$travelMode = $this->travelModes[$options['travelMode']];

		$directions = "
			var {$id}Service = new google.maps.DirectionsService();
			var {$id}Display;
			{$id}Display = new google.maps.DirectionsRenderer();
			{$id}Display. setMap(" . $this->name() . ");
			";

		if (!empty($options['directionsDiv'])) {
			$directions .= "{$id}Display. setPanel(document.getElementById('" . $options['directionsDiv'] . "'));";
		}

		if (is_array($from)) {
			$from = 'new google.maps.LatLng(' . (float)$from['lat'] . ', ' . (float)$from['lng'] . ')';
		} else {
			$from = '\'' . h($from) . '\'';
		}
		if (is_array($to)) {
			$to = 'new google.maps.LatLng(' . (float)$to['lat'] . ', ' . (float)$to['lng'] . ')';
		} else {
			$to = '\'' . h($to) . '\'';
		}

		$directions .= "
			var request = {
				origin: $from,
				destination: $to,
				unitSystem: google.maps.UnitSystem." . $options['unitSystem'] . ",
				travelMode: google.maps.TravelMode. $travelMode
			};
			{$id}Service.route(request, function(result, status) {
				if (status == google.maps.DirectionsStatus.OK) {
					{$id}Display. setDirections(result);
				}
			});
		";
		$this->map .= $directions;
	}

	/**
	 * Add a polyline
	 *
	 * This method adds a line between 2 points
	 *
	 * @param array|string $from Location as array(fixed lat/lng pair) or string (to be geocoded at runtime)
	 * @param array|string $to Location as array(fixed lat/lng pair) or string (to be geocoded at runtime)
	 * @param array $options
	 * - color (#FFFFFF ... #000000)
	 * - opacity (0.1 ... 1, defaults to 1)
	 * - weight in pixels (defaults to 2)
	 * @see https://developers.google.com/maps/documentation/javascript/3.exp/reference#Polyline
	 * @return void
	 */
	public function addPolyline($from, $to, $options = []) {
		if (is_array($from)) {
			$from = 'new google.maps.LatLng(' . (float)$from['lat'] . ', ' . (float)$from['lng'] . ')';
		} else {
			throw new Exception('not implemented yet, use array of lat/lng');
			$from = '\'' . h($from) . '\'';
		}
		if (is_array($to)) {
			$to = 'new google.maps.LatLng(' . (float)$to['lat'] . ', ' . (float)$to['lng'] . ')';
		} else {
			throw new Exception('not implemented yet, use array of lat/lng');
			$to = '\'' . h($to) . '\'';
		}

		$defaults = $this->_config['polyline'];
		$options += $defaults;

		$id = 'p' . static::$markerCount++;

		$polyline = "var start = $from;";
		$polyline .= "var end = $to;";
		$polyline .= "
				var poly = [
					start,
					end
				];
				var {$id}Polyline = new google.maps.Polyline({
					path: poly,
					strokeColor: '" . $options['color'] . "',
					strokeOpacity: " . $options['opacity'] . ",
					strokeWeight: " . $options['weight'] . "
				});
				{$id}Polyline.setMap(" . $this->name() . ");
			";
		$this->map .= $polyline;
	}

	/**
	 * @param string $content (html/text)
	 * @param int $infoWindowCount
	 * @return void
	 */
	public function setContentInfoWindow($con, $index) {
		$this->map .= "
			gInfoWindows" . static::$mapCount . "[$index]. setContent(" . $this->escapeString($con) . ");";
	}

	/**
	 * Json encode string
	 *
	 * @param mixed $content
	 * @return json
	 */
	public function escapeString($content) {
		return json_encode($content);
	}

	/**
	 * This method returns the javascript for the current map container.
	 * Including script tags.
	 * Just echo it below the map container. New: Alternativly, use finalize() directly.
	 *
	 * @return string
	 */
	public function script() {
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
	 * @return null|string Javascript if $return is true
	 */
	public function finalize($return = false) {
		$script = $this->_arrayToObject('matching', $this->matching, false, true) . '
		' . $this->_arrayToObject('gIcons' . static::$mapCount, $this->icons, false, false) . '

	jQuery(document).ready(function() {
		';

		$script .= $this->map;
		if ($this->_config['geolocate']) {
			$script .= $this->_geolocate();
		}

		if ($this->_config['showMarker'] && !empty($this->markers) && is_array($this->markers)) {
			$script .= implode($this->markers, " ");
		}

		if ($this->_config['autoCenter']) {
			$script .= $this->_autoCenter();
		}
		$script .= '

	});';
		static::$mapCount++;
		if ($return) {
			return $script;
		}
		$this->Html->scriptBlock($script, ['block' => true]);
	}

	/**
	 * Set a custom geolocate callback
	 *
	 * @param string|bool $customJs
	 * false: no callback at all
	 * @return void
	 */
	public function geolocateCallback($js) {
		if ($js === false) {
			$this->_config['callbacks']['geolocate'] = false;
			return;
		}
		$this->_config['callbacks']['geolocate'] = $js;
	}

	/**
	 * Experimental - works in cutting edge browsers like chrome10
	 */
	protected function _geolocate() {
		return '
	// Try W3C Geolocation (Preferred)
	if (navigator.geolocation) {
		browserSupportFlag = true;
		navigator.geolocation.getCurrentPosition(function(position) {
			geolocationCallback(position.coords.latitude, position.coords.longitude);
		}, function() {
			handleNoGeolocation(browserSupportFlag);
		});
		// Try Google Gears Geolocation
	} else if (google.gears) {
		browserSupportFlag = true;
		var geo = google.gears.factory.create(\'beta.geolocation\');
		geo.getCurrentPosition(function(position) {
			geolocationCallback(position.latitude, position.longitude);
		}, function() {
			handleNoGeoLocation(browserSupportFlag);
		});
		// Browser doesn\'t support Geolocation
	} else {
		browserSupportFlag = false;
		handleNoGeolocation(browserSupportFlag);
	}

	function geolocationCallback(lat, lng) {
		' . $this->_geolocationCallback() . '
	}

	function handleNoGeolocation(errorFlag) {
	if (errorFlag == true) {
		//alert("Geolocation service failed.");
	} else {
		//alert("Your browser doesn\'t support geolocation. We\'ve placed you in Siberia.");
	}
	//' . $this->name() . ' . setCenter(initialLocation);
	}
	';
	}

	protected function _geolocationCallback() {
		if (($js = $this->_config['callbacks']['geolocate']) === false) {
			return '';
		}
		if ($js === null) {
			$js = 'initialLocation = new google.maps.LatLng(lat, lng);
		' . $this->name() . ' . setCenter(initialLocation);
';
		}
		return $js;
	}

	/**
	 * Auto center map
	 * careful: with only one marker this can result in too high zoom values!
	 * @return string autoCenterCommands
	 */
	protected function _autoCenter() {
		return '
		var bounds = new google.maps.LatLngBounds();
		$.each(gMarkers' . static::$mapCount . ',function (index, marker) { bounds.extend(marker.position);});
		' . $this->name() . ' .fitBounds(bounds);
		';
	}

	/**
	 * @return string JSON like js string
	 */
	protected function _mapOptions() {
		$options = array_merge($this->_config, $this->_config['map']);

		$mapOptions = array_intersect_key($options, [
			'streetViewControl' => null,
			'navigationControl' => null,
			'mapTypeControl' => null,
			'scaleControl' => null,
			'scrollwheel' => null,
			'zoom' => null,
			'keyboardShortcuts' => null
		]);
		$res = [];
		foreach ($mapOptions as $key => $mapOption) {
			$res[] = $key . ': ' . $this->value($mapOption);
		}
		if (empty($options['autoCenter'])) {
			$res[] = 'center: initialLocation';
		}
		if (!empty($options['navOptions'])) {
			$res[] = 'navigationControlOptions: ' . $this->_controlOptions('nav', $options['navOptions']);
		}
		if (!empty($options['typeOptions'])) {
			$res[] = 'mapTypeControlOptions: ' . $this->_controlOptions('type', $options['typeOptions']);
		}
		if (!empty($options['scaleOptions'])) {
			$res[] = 'scaleControlOptions: ' . $this->_controlOptions('scale', $options['scaleOptions']);
		}

		if (array_key_exists($options['type'], $this->types)) {
			$type = $this->types[$options['type']];
		} else {
			$type = $options['type'];
		}
		$res[] = 'mapTypeId: google.maps.MapTypeId.' . $type;

		return '{' . implode(', ', $res) . '}';
	}

	/**
	 * @param string $type
	 * @param array $options
	 * @return string JSON like js string
	 */
	protected function _controlOptions($type, $options) {
		$mapping = [
			'nav' => 'NavigationControlStyle',
			'type' => 'MapTypeControlStyle',
			'scale' => ''
		];
		$res = [];
		if (!empty($options['style']) && ($m = $mapping[$type])) {
			$res[] = 'style: google.maps.' . $m . '.' . $options['style'];
		}
		if (!empty($options['pos'])) {
			$res[] = 'position: google.maps.ControlPosition.' . $options['pos'];
		}

		return '{' . implode(', ', $res) . '}';
	}

/** Google Maps Link **/

	/**
	 * Returns a maps.google link
	 *
	 * @param string $linkTitle
	 * @param array $mapOptions
	 * @param array $linkOptions
	 * @return string Html link
	 */
	public function mapLink($title, $mapOptions = [], $linkOptions = []) {
		return $this->Html->link($title, $this->mapUrl($mapOptions), $linkOptions);
	}

	/**
	 * Returns a maps.google url
	 *
	 * @param array $options:
	 * - from: necessary (address or lat,lng)
	 * - to: 1x necessary (address or lat,lng - can be an array of multiple destinations: array('dest1', 'dest2'))
	 * - zoom: optional (defaults to none)
	 * @return string link: http://...
	 */
	public function mapUrl($options = []) {
		$url = $this->_protocol() . 'maps.google.com/maps?';

		$urlArray = [];
		if (!empty($options['from'])) {
			$urlArray[] = 'saddr=' . urlencode($options['from']);
		}

		if (!empty($options['to']) && is_array($options['to'])) {
			$to = array_shift($options['to']);
			foreach ($options['to'] as $key => $value) {
				$to .= '+to:' . $value;
			}
			$urlArray[] = 'daddr=' . urlencode($to);
		} elseif (!empty($options['to'])) {
			$urlArray[] = 'daddr=' . urlencode($options['to']);
		}

		if (!empty($options['zoom'])) {
			$urlArray[] = 'z=' . (int)$options['zoom'];
		}
		//$urlArray[] = 'f=d';
		//$urlArray[] = 'hl=de';
		//$urlArray[] = 'ie=UTF8';
		return $url . implode('&', $urlArray);
	}

/** STATIC MAP **/

/** http://maps.google.com/staticmap?center=40.714728,-73.998672&zoom=14&size=512x512&maptype=mobile&markers=40.702147,-74.015794,blues%7C40.711614,-74.012318,greeng%7C40.718217,-73.998284,redc&mobile=true&sensor=false **/

	/**
	 * Create a plain image map
	 *
	 * @link http://code.google.com/intl/de-DE/apis/maps/documentation/staticmaps
	 * @param options:
	 * - string $size [necessary: VALxVAL, e.g. 500x400 - max 640x640]
	 * - string $center: x,y or address [necessary, if no markers are given; else tries to take defaults if available] or TRUE/FALSE
	 * - int $zoom [optional; if no markers are given, default value is used; if set to "auto" and ]*
	 * - array $markers [optional, @see staticPaths() method]
	 * - string $type [optional: roadmap/hybrid, ...; default:roadmap]
	 * - string $mobile TRUE/FALSE
	 * - string $visible: $area (x|y|...)
	 * - array $paths [optional, @see staticPaths() method]
	 * - string $language [optional]
	 * @param array $attributes: html attributes for the image
	 * - title
	 * - alt (defaults to 'Map')
	 * - url (tip: you can pass $this->link(...) and it will create a link to maps.google.com)
	 * @return string imageTag
	 */
	public function staticMap($options = [], $attributes = []) {
		$defaultAttributes = ['alt' => __d('tools', 'Map')];

		return $this->Html->image($this->staticMapUrl($options), array_merge($defaultAttributes, $attributes));
	}

	/**
	 * Create a link to a plain image map
	 *
	 * @param string $linkTitle
	 * @param array $mapOptions
	 * @param array $linkOptions
	 * @return string Html link
	 */
	public function staticMapLink($title, $mapOptions = [], $linkOptions = []) {
		return $this->Html->link($title, $this->staticMapUrl($mapOptions), $linkOptions);
	}

	/**
	 * Create an url to a plain image map
	 *
	 * @param options
	 * - see staticMap() for details
	 * @return string urlOfImage: http://...
	 */
	public function staticMapUrl($options = []) {
		$map = $this->_protocol() . static::STATIC_API;
		/*
		$params = array(
			'sensor' => 'false',
			'mobile' => 'false',
			'format' => 'png',
			//'center' => false
		);

		if (!empty($options['sensor'])) {
			$params['sensor'] = 'true';
		}
		if (!empty($options['mobile'])) {
			$params['mobile'] = 'true';
		}
		*/

		$defaults = array_merge($this->_defaultOptions, $this->_defaultOptions['staticMap']);
		$mapOptions = $options + $defaults;

		$params = array_intersect_key($mapOptions, [
			'sensor' => null,
			'mobile' => null,
			'format' => null,
			'size' => null,
			//'zoom' => null,
			//'lat' => null,
			//'lng' => null,
			//'visible' => null,
			//'type' => null,
		]);
		// do we want zoom to auto-correct itself?
		if (!isset($options['zoom']) && !empty($mapOptions['markers'])|| !empty($mapOptions['paths']) || !empty($mapOptions['visible'])) {
			$options['zoom'] = 'auto';
		}

		// a position on the map that is supposed to stay visible at all cost
		if (!empty($mapOptions['visible'])) {
			$params['visible'] = urlencode($mapOptions['visible']);
		}

		// center and zoom are not necccessary if path, visible or markers are given
		if (!isset($options['center']) || $options['center'] === false) {
			// dont use it
		} elseif ($options['center'] === true && $mapOptions['lat'] !== null && $mapOptions['lng'] !== null) {
			$params['center'] = urlencode((string)$mapOptions['lat'] . ',' . (string)$mapOptions['lng']);
		} elseif (!empty($options['center'])) {
			$params['center'] = urlencode($options['center']);
		} /*else {
			// try to read from markers array???
			if (isset($options['markers']) && count($options['markers']) == 1) {
				//pr ($options['markers']);
			}
		}*/

		if (!isset($options['zoom']) || $options['zoom'] === false) {
			// dont use it
		} else {
			if ($options['zoom'] === 'auto') {
				if (!empty($options['markers']) && strpos($options['zoom'], '|') !== false) {
					// let google find the best zoom value itself
				} else {
					// do something here?
				}
			} else {
				$params['zoom'] = $options['zoom'];
			}
		}

		if (array_key_exists($mapOptions['type'], $this->types)) {
			$params['maptype'] = $this->types[$mapOptions['type']];
		} else {
			$params['maptype'] = $mapOptions['type'];
		}
		$params['maptype'] = strtolower($params['maptype']);

		// old: {latitude},{longitude},{color}{alpha-character}
		// new: @see staticMarkers()
		if (!empty($options['markers'])) {
			$params['markers'] = $options['markers'];
		}

		if (!empty($options['paths'])) {
			$params['path'] = $options['paths'];
		}

		// valXval
		if (!empty($options['size'])) {
			$params['size'] = $options['size'];
		}

		$pieces = [];
		foreach ($params as $key => $value) {
			if (is_array($value)) {
				$value = implode('&' . $key . '=', $value);
			} elseif ($value === true) {
				$value = 'true';
			} elseif ($value === false) {
				$value = 'false';
			} elseif ($value === null) {
				continue;
			}
			$pieces[] = $key . '=' . $value;
		}
		return $map . implode('&', $pieces);
	}

	/**
	 * Prepare paths for staticMap
	 *
	 * @param array $pathElementArrays
	 * - elements: [required] (multiple array(lat=>x, lng=>y) or just a address strings)
	 * - color: red/blue/green (optional, default blue)
	 * - weight: numeric (optional, default: 5)
	 * @return string paths: e.g: color:0x0000FF80|weight:5|37.40303,-122.08334|37.39471,-122.07201|37.40589,-122.06171{|...}
	 */
	public function staticPaths($pos = []) {
		$defaults = [
			'color' => 'blue',
			'weight' => 5 // pixel
		];

		// not a 2-level array? make it one
		if (!isset($pos[0])) {
			$pos = [$pos];
		}

		$res = [];
		foreach ($pos as $p) {
			$options = array_merge($defaults, $p);

			$markers = $options['path'];
			unset($options['path']);

			// prepare color
			if (!empty($options['color'])) {
				$options['color'] = $this->_prepColor($options['color']);
			}

			$path = [];
			foreach ($options as $key => $value) {
				$path[] = $key . ':' . urlencode($value);
			}
			foreach ($markers as $key => $pos) {
				if (is_array($pos)) {
					// lat/lng?
					$pos = $pos['lat'] . ',' . $pos['lng'];
				}
				$path[] = $pos;
			}
			$res[] = implode('|', $path);
		}
		return $res;
	}

	/**
	 * Prepare markers for staticMap
	 *
	 * @param array $markerArrays
	 * - lat: xx.xxxxxx (necessary)
	 * - lng: xx.xxxxxx (necessary)
	 * - address: (instead of lat/lng)
	 * - color: red/blue/green (optional, default blue)
	 * - label: a-z or numbers (optional, default: s)
	 * - icon: custom icon (png, gif, jpg - max 64x64 - max 5 different icons per image)
	 * - shadow: TRUE/FALSE
	 * @param style (global) (overridden by custom marker styles)
	 * - color
	 * - label
	 * - icon
	 * - shadow
	 * @return array markers: color:green|label:Z|48,11|Berlin
	 *
	 * NEW: size:mid|color:red|label:E|37.400465,-122.073003|37.437328,-122.159928&markers=size:small|color:blue|37.369110,-122.096034
	 * OLD: 40.702147,-74.015794,blueS|40.711614,-74.012318,greenG{|...}
	 */
	public function staticMarkers($pos = [], $style = []) {
		$markers = [];
		$verbose = false;

		$defaults = [
			'shadow' => 'true',
			'color' => 'blue',
			'label' => '',
			'address' => '',
			'size' => ''
		];

		// not a 2-level array? make it one
		if (!isset($pos[0])) {
			$pos = [$pos];
		}

		// new in staticV2: separate styles! right now just merged
		foreach ($pos as $p) {
			$p = array_merge($defaults, $style, $p);

			// adress or lat/lng?
			if (!empty($p['lat']) && !empty($p['lng'])) {
				$p['address'] = $p['lat'] . ',' . $p['lng'];
			} else {
				$p['address'] = $p['address'];
			}
			$p['address'] = urlencode($p['address']);

			$values = [];

			// prepare color
			if (!empty($p['color'])) {
				$p['color'] = $this->_prepColor($p['color']);
				$values[] = 'color:' . $p['color'];
			}
			// label? A-Z0-9
			if (!empty($p['label'])) {
				$values[] = 'label:' . strtoupper($p['label']);
			}
			if (!empty($p['size'])) {
				$values[] = 'size:' . $p['size'];
			}
			if (!empty($p['shadow'])) {
				$values[] = 'shadow:' . $p['shadow'];
			}
			if (!empty($p['icon'])) {
				$values[] = 'icon:' . urlencode($p['icon']);
			}
			$values[] = $p['address'];

			//TODO: icons
			$markers[] = implode('|', $values);
		}

		//TODO: shortcut? only possible if no custom params!
		if ($verbose) {

		}
		// long: markers=styles1|address1&markers=styles2|address2&...
		// short: markers=styles,address1|address2|address3|...

		return $markers;
	}

	/**
	 * Ensure that we stay on the appropriate protocol
	 *
	 * @return string protocol base (including ://)
	 */
	protected function _protocol() {
		if (($https = $this->_config['https']) === null) {
			$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
		}
		return ($https ? 'https' : 'http') . '://';
	}

	/**
	 * // to 0x
	 * or // added
	 *
	 * @param string $color: FFFFFF, #FFFFFF, 0xFFFFFF or blue
	 * @return string color
	 */
	protected function _prepColor($color) {
		if (strpos($color, '#') !== false) {
			return str_replace('#', '0x', $color);
		} elseif (is_numeric($color)) {
			return '0x' . $color;
		}
		return $color;
	}

/** TODOS/EXP **/

/*
TODOS:

- geocoding (+ reverse)

- directions

- overlays

- fluster (for clustering?)
or
- markerManager (many markers)

- infoBox
http://google-maps-utility-library-v3.googlecode.com/svn/tags/infobox/

- ...

*/

	public function geocoder() {
		$js = 'var geocoder = new google.maps.Geocoder();';
		//TODO

	}

	/**
	 * Managing lots of markers!
	 * @link http://google-maps-utility-library-v3.googlecode.com/svn/tags/markermanager/1.0/docs/examples.html
	 * @param options
	 * -
	 * @return void
	 */
	public function setManager() {
		$js .= '
		var mgr' . static::$mapCount . ' = new MarkerManager(' . $this->name() . ');
		';
	}

	public function addManagerMarker($marker, $options) {
		$js = 'mgr' . static::$mapCount . ' .addMarker(' . $marker . ');';
	}

	/**
	 * Clustering for lots of markers!
	 * @link ?
	 * @param options
	 * -
	 * based on Fluster2 0.1.1
	 * @return void
	 */
	public function setCluster($options) {
		$js = static::$flusterScript;
		$js .= '
		var fluster' . static::$mapCount . ' = new Fluster2(' . $this->name() . ');
		';

		// styles
		'fluster' . static::$mapCount . '.styles = {}';

		$this->map .= $js;
	}

	public function addClusterMarker($marker, $options) {
		$js = 'fluster' . static::$mapCount . '.addMarker(' . $marker . ');';
	}

	public function initCluster() {
		$this->map .= 'fluster' . static::$mapCount . '.initialize();';
	}

	public static $flusterScript = '
function Fluster2(_map,_debug) {var map=_map;var projection=new Fluster2ProjectionOverlay(map);var me=this;var clusters=new Object();var markersLeft=new Object();this.debugEnabled=_debug;this.gridSize=60;this.markers=new Array();this.currentZoomLevel=-1;this.styles={0:{image:\'http://gmaps-utility-library.googlecode.com/svn/trunk/markerclusterer/1.0/images/m1.png\',textColor:\'#FFFFFF\',width:53,height:52},10:{image:\'http://gmaps-utility-library.googlecode.com/svn/trunk/markerclusterer/1.0/images/m2.png\',textColor:\'#FFFFFF\',width:56,height:55},20:{image:\'http://gmaps-utility-library.googlecode.com/svn/trunk/markerclusterer/1.0/images/m3.png\',textColor:\'#FFFFFF\',width:66,height:65}};var zoomChangedTimeout=null;function createClusters() {var zoom=map.getZoom();if (clusters[zoom]) {me.debug(\'Clusters for zoom level \'+zoom+\' already initialized.\')} else {var clustersThisZoomLevel=new Array();var clusterCount=0;var markerCount=me.markers.length;for (var i=0;i<markerCount;i++) {var marker=me.markers[i];var markerPosition=marker.getPosition();var done=false;for (var j=clusterCount-1;j>=0;j--) {var cluster=clustersThisZoomLevel[j];if (cluster.contains(markerPosition)) {cluster.addMarker(marker);done=true;break}}if (!done) {var cluster=new Fluster2Cluster(me,marker);clustersThisZoomLevel.push(cluster);clusterCount++}}clusters[zoom]=clustersThisZoomLevel;me.debug(\'Initialized \'+clusters[zoom].length+\' clusters for zoom level \'+zoom+\' .\')}if (clusters[me.currentZoomLevel]) {for (var i=0;i<clusters[me.currentZoomLevel].length;i++) {clusters[me.currentZoomLevel][i].hide()}}me.currentZoomLevel=zoom;showClustersInBounds()}function showClustersInBounds() {var mapBounds=map.getBounds();for (var i=0;i<clusters[me.currentZoomLevel].length;i++) {var cluster=clusters[me.currentZoomLevel][i];if (mapBounds.contains(cluster.getPosition())) {cluster.show()}}}this.zoomChanged=function() {window.clearInterval(zoomChangedTimeout);zoomChangedTimeout=window.setTimeout(createClusters,500)};this.getMap=function() {return map};this.getProjection=function() {return projection.getP()};this.debug=function(message) {if (me.debugEnabled) {console.log(\'Fluster2: \'+message)}};this.addMarker=function(_marker) {me.markers.push(_marker)};this.getStyles=function() {return me.styles};this.initialize=function() {google.maps.event.addListener(map,\'zoom_changed\',this.zoomChanged);google.maps.event.addListener(map,\'dragend\',showClustersInBounds);window.setTimeout(createClusters,1000)}}
function Fluster2Cluster(_fluster,_marker) {var markerPosition=_marker.getPosition();this.fluster=_fluster;this.markers=[];this.bounds=null;this.marker=null;this.lngSum=0;this.latSum=0;this.center=markerPosition;this.map=this.fluster.getMap();var me=this;var projection=_fluster.getProjection();var gridSize=_fluster.gridSize;var position=projection.fromLatLngToDivPixel(markerPosition);var positionSW=new google.maps.Point(position.x-gridSize,position.y+gridSize);var positionNE=new google.maps.Point(position.x+gridSize,position.y-gridSize);this.bounds=new google.maps.LatLngBounds(projection.fromDivPixelToLatLng(positionSW),projection.fromDivPixelToLatLng(positionNE));this.addMarker=function(_marker) {this.markers.push(_marker)};this.show=function() {if (this.markers.length==1) {this.markers[0].setMap(me.map)} elseif (this.markers.length>1) {for (var i=0;i<this.markers.length;i++) {this.markers[i].setMap(null)}if (this.marker==null) {this.marker=new Fluster2ClusterMarker(this.fluster,this);if (this.fluster.debugEnabled) {google.maps.event.addListener(this.marker,\'mouseover\',me.debugShowMarkers);google.maps.event.addListener(this.marker,\'mouseout\',me.debugHideMarkers)}}this.marker.show()}};this.hide=function() {if (this.marker!=null) {this.marker.hide()}};this.debugShowMarkers=function() {for (var i=0;i<me.markers.length;i++) {me.markers[i].setVisible(true)}};this.debugHideMarkers=function() {for (var i=0;i<me.markers.length;i++) {me.markers[i].setVisible(false)}};this.getMarkerCount=function() {return this.markers.length};this.contains=function(_position) {return me.bounds.contains(_position)};this.getPosition=function() {return this.center};this.getBounds=function() {return this.bounds};this.getMarkerBounds=function() {var bounds=new google.maps.LatLngBounds(me.markers[0].getPosition(),me.markers[0].getPosition());for (var i=1;i<me.markers.length;i++) {bounds.extend(me.markers[i].getPosition())}return bounds};this.addMarker(_marker)}
function Fluster2ClusterMarker(_fluster,_cluster) {this.fluster=_fluster;this.cluster=_cluster;this.position=this.cluster.getPosition();this.markerCount=this.cluster.getMarkerCount();this.map=this.fluster.getMap();this.style=null;this.div=null;var styles=this.fluster.getStyles();for (var i in styles) {if (this.markerCount>i) {this.style=styles[i]} else {break}}google.maps.OverlayView.call(this);this.setMap(this.map);this.draw()};Fluster2ClusterMarker.prototype=new google.maps.OverlayView();Fluster2ClusterMarker.prototype.draw=function() {if (this.div==null) {var me=this;this.div=document.createElement(\'div\');this.div.style.position=\'absolute\';this.div.style.width=this.style.width+\'px\';this.div.style.height=this.style.height+\'px\';this.div.style.lineHeight=this.style.height+\'px\';this.div.style.background=\'transparent url("\'+this.style.image+\'") 50% 50% no-repeat\';this.div.style.color=this.style.textColor;this.div.style.textAlign=\'center\';this.div.style.fontFamily=\'Arial, Helvetica\';this.div.style.fontSize=\'11px\';this.div.style.fontWeight=\'bold\';this.div.innerHTML=this.markerCount;this.div.style.cursor=\'pointer\';google.maps.event.addDomListener(this.div,\'click\',function() {me.map.fitBounds(me.cluster.getMarkerBounds())});this.getPanes().overlayLayer.appendChild(this.div)}var position=this.getProjection().fromLatLngToDivPixel(this.position);this.div.style.left=(position.x-parseInt(this.style.width/2))+\'px\';this.div.style.top=(position.y-parseInt(this.style.height/2))+\'px\'};Fluster2ClusterMarker.prototype.hide=function() {this.div.style.display=\'none\'};Fluster2ClusterMarker.prototype.show=function() {this.div.style.display=\'block\'};
function Fluster2ProjectionOverlay(map) {google.maps.OverlayView.call(this);this.setMap(map);this.getP=function() {return this.getProjection()}}Fluster2ProjectionOverlay.prototype=new google.maps.OverlayView();Fluster2ProjectionOverlay.prototype.draw=function() {};
\'';

	/**
	 * GoogleMapHelper::_arrayToObject()
	 *
	 * @param string $name
	 * @param array $array
	 * @param bool $asString
	 * @param bool $keyAsString
	 * @return string
	 */
	protected function _arrayToObject($name, $array, $asString = true, $keyAsString = false) {
		$res = 'var ' . $name . ' = {' . PHP_EOL;
		$res .= $this->_toObjectParams($array, $asString, $keyAsString);
		$res .= '};';
		return $res;
	}

	/**
	 * GoogleMapHelper::_toObjectParams()
	 *
	 * @param array $array
	 * @param bool $asString
	 * @param bool $keyAsString
	 * @return string
	 */
	protected function _toObjectParams($array, $asString = true, $keyAsString = false) {
		$pieces = [];
		foreach ($array as $key => $value) {
			$e = ($asString && strpos($value, 'new ') !== 0 ? '\'' : '');
			$ke = ($keyAsString ? '\'' : '');
			$pieces[] = $ke . $key . $ke . ': ' . $e . $value . $e;
		}
		return implode(',' . PHP_EOL, $pieces);
	}

}
