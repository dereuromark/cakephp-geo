<?php
/**
 * Example configuration for CakePHP Geo plugin.
 *
 * Copy the relevant parts to your config/app_local.php file.
 */

use Geo\Geocoder\Geocoder;

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
];
