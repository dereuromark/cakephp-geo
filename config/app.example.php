<?php
/**
 * Example configuration for CakePHP Geo plugin.
 *
 * Copy the relevant parts to your config/app_local.php file.
 */

use Geo\Geocoder\Geocoder;
use Geo\View\Helper\GoogleMapHelper;
use Geo\View\Helper\StaticMapHelper;

return [
	/**
	 * Geocoder configuration.
	 *
	 * Available providers:
	 * - Geocoder::PROVIDER_GOOGLE (default) - requires API key
	 * - Geocoder::PROVIDER_NOMINATIM - free, OpenStreetMap-based
	 * - Geocoder::PROVIDER_GEOAPIFY - requires API key (has free tier)
	 * - Geocoder::PROVIDER_NULL - for testing (returns empty results)
	 */
	'Geocoder' => [
		// Provider selection (use constants or callable)
		'provider' => Geocoder::PROVIDER_GOOGLE,

		// Global settings (used as fallbacks for provider-specific config)
		'apiKey' => env('GEOCODER_API_KEY', ''),
		'locale' => null, // e.g., 'en', 'de', or true to auto-detect from I18n
		'region' => null, // e.g., 'us', 'de', or true to auto-detect from I18n

		// Result handling
		'allowInconclusive' => true, // Allow multiple results
		'minAccuracy' => Geocoder::TYPE_COUNTRY, // Minimum accuracy level, or null to disable
		'expect' => [], // Expected address types, e.g., [Geocoder::TYPE_POSTAL, Geocoder::TYPE_LOC]

		// Google Maps provider settings
		'google' => [
			'apiKey' => env('GOOGLE_MAPS_API_KEY', ''),
			'locale' => 'en',
			'region' => null, // e.g., 'us' for US biasing
		],

		// Nominatim (OpenStreetMap) provider settings
		'nominatim' => [
			'userAgent' => 'MyApp/1.0', // Required by OSM usage policy
			'rootUrl' => 'https://nominatim.openstreetmap.org', // Or your own Nominatim instance
			'locale' => 'en',
		],

		// Geoapify provider settings
		'geoapify' => [
			'apiKey' => env('GEOAPIFY_API_KEY', ''),
			'locale' => 'en',
		],
	],

	/**
	 * GoogleMapHelper configuration.
	 */
	'GoogleMap' => [
		'key' => env('GOOGLE_MAPS_API_KEY', ''), // Required for Google Maps JavaScript API
		'zoom' => null,
		'lat' => null,
		'lng' => null,
		'type' => GoogleMapHelper::TYPE_ROADMAP, // ROADMAP, HYBRID, SATELLITE, TERRAIN
		'map' => [
			'streetViewControl' => false,
			'navigationControl' => true,
			'mapTypeControl' => true,
			'scaleControl' => true,
			'scrollwheel' => false,
			'keyboardShortcuts' => true,
			'defaultLat' => 51,
			'defaultLng' => 11,
			'defaultZoom' => 5,
		],
		'staticMap' => [
			'size' => '300x300',
			'format' => 'png',
		],
		'marker' => [
			'animation' => null, // 'BOUNCE' or 'DROP'
			'draggable' => false,
		],
		'infoWindow' => [
			'maxWidth' => 300,
		],
		'div' => [
			'id' => 'map_canvas',
			'width' => '100%',
			'height' => '400px',
			'class' => 'map',
		],
		'autoScript' => false, // Let helper include JS script links
		'autoCenter' => false, // Fit all markers in view
		'libraries' => null, // e.g., 'places' or ['places', 'geometry']
	],

	/**
	 * LeafletHelper configuration.
	 */
	'Leaflet' => [
		'zoom' => null,
		'lat' => null,
		'lng' => null,
		'map' => [
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
		],
		'popup' => [
			'maxWidth' => 300,
			'autoClose' => true,
		],
		'polyline' => [
			'color' => '#3388ff',
			'weight' => 3,
			'opacity' => 1.0,
		],
		'div' => [
			'id' => 'map',
			'width' => '100%',
			'height' => '400px',
			'class' => 'leaflet-map',
		],
		'autoScript' => false,
		'autoCenter' => false,
	],

	/**
	 * StaticMapHelper configuration.
	 *
	 * Available providers:
	 * - StaticMapHelper::PROVIDER_GEOAPIFY (default)
	 * - StaticMapHelper::PROVIDER_MAPBOX
	 * - StaticMapHelper::PROVIDER_STADIA
	 * - StaticMapHelper::PROVIDER_GOOGLE
	 */
	'StaticMap' => [
		'provider' => StaticMapHelper::PROVIDER_GEOAPIFY,
		'size' => '400x300',
		'format' => 'png',
		'scale' => 1,

		// Geoapify provider settings
		StaticMapHelper::PROVIDER_GEOAPIFY => [
			'apiKey' => env('GEOAPIFY_API_KEY', ''),
			'style' => 'osm-bright',
		],

		// Mapbox provider settings
		StaticMapHelper::PROVIDER_MAPBOX => [
			'apiKey' => env('MAPBOX_API_KEY', ''),
			'style' => 'streets-v12',
			'username' => 'mapbox',
		],

		// Stadia provider settings
		StaticMapHelper::PROVIDER_STADIA => [
			'apiKey' => env('STADIA_API_KEY', ''),
			'style' => 'alidade_smooth',
		],

		// Google provider settings
		StaticMapHelper::PROVIDER_GOOGLE => [
			'apiKey' => env('GOOGLE_MAPS_API_KEY', ''),
			'style' => 'roadmap',
		],
	],

	/**
	 * Geo Calculator configuration.
	 *
	 * Add custom distance units. Built-in units:
	 * - Calculator::UNIT_KM (K)
	 * - Calculator::UNIT_MILES (M)
	 * - Calculator::UNIT_NAUTICAL (N)
	 * - Calculator::UNIT_FEET (F)
	 * - Calculator::UNIT_INCHES (I)
	 */
	'Geo' => [
		'Calculator' => [
			'units' => [
				// Add custom units as 'KEY' => multiplier (relative to miles)
				// 'Y' => 1760, // yards
			],
		],
	],
];
