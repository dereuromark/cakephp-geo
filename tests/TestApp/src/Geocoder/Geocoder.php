<?php
namespace TestApp\Geocoder;

use Cake\Utility\Inflector;
use Exception;
use Geo\Geocoder\Geocoder as GeoGeocoder;

/**
 * Geocode via google (UPDATE: api3)
 *
 * @see https://developers.google.com/maps/documentation/geocoding/
 *
 * Used by Geo.GeocoderBehavior
 *
 * @author Mark Scherer
 * @license MIT
 */
class Geocoder extends GeoGeocoder {

	/**
	 * @param mixed $geocoder
	 * @return void
	 */
	public function setGeocoderAndResult($geocoder) {
		$this->geocoder = $geocoder;
	}

	/**
	 * @param string $address
	 * @param array $params
	 * @return \Geocoder\Model\AddressCollection
	 */
	public function geocode($address, array $params = []) {
		$file = Inflector::slug($address) . '.txt';

		$testFiles = ROOT . DS . 'tests' . DS . 'test_files' . DS . 'Geocoder' . DS;
		$testFile = $testFiles . $file;

		if (!file_exists($testFile)) {
			if (getenv('CI')) {
				throw new Exception('Should not happen on CI.');
			}

			$addresses = parent::geocode($address, $params);
			file_put_contents($testFile, serialize($addresses));
		}

		$addresses = file_get_contents($testFile);
		$addresses = unserialize($addresses);

		return $addresses;
	}

}
