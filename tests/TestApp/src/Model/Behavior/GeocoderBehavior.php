<?php
namespace TestApp\Model\Behavior;

use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Geo\Geocoder\Geocoder;
use Geo\Model\Behavior\GeocoderBehavior as GeoGeocoderBehavior;

/**
 * Mocked version to avoid real API hits. Also auto-updates mock files when they cannot be found.
 */
class GeocoderBehavior extends GeoGeocoderBehavior {

	/**
	 * Uses the Geocode class to query
	 *
	 * @param array $addressFields (simple array of address pieces)
	 * @return \Geocoder\Model\AddressCollection|null
	 */
	protected function _geocode($addressFields) {
		$address = implode(' ', $addressFields);
		if (empty($address)) {
			return [];
		}

		$this->_Geocoder = new Geocoder($this->_config);

		$file = Inflector::slug($address) . '.php';

		$testFiles = ROOT . DS . 'tests' . DS . 'test_files' . DS . 'Behavior' . DS;
		$testFile = $testFiles . $file;

		if (!file_exists($testFile)) {
			$addresses = parent::_geocode($addressFields);
			file_put_contents($testFile, serialize($addresses));
		}

		$addresses = file_get_contents($testFile);
		$addresses = unserialize($addresses);

		return $addresses;
	}

}
