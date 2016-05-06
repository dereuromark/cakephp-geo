<?php
namespace TestApp\Model\Behavior;

use Cake\Utility\Inflector;
use Exception;
use Geo\Geocoder\Geocoder;
use Geo\Model\Behavior\GeocoderBehavior as GeoGeocoderBehavior;

/**
 * Mocked version to avoid real API hits. Also auto-updates mock files when they cannot be found.
 */
class GeocoderBehavior extends GeoGeocoderBehavior {

	/**
	 * Uses the Geocode class to query
	 *
	 * @param string $address
	 * @return \Geocoder\Model\Address|null
	 * @throws \Exception
	 */
	protected function _execute($address) {
		$this->_Geocoder = new Geocoder($this->_config);

		$file = Inflector::slug($address) . '.txt';

		$testFiles = ROOT . DS . 'tests' . DS . 'test_files' . DS . 'Behavior' . DS;
		$testFile = $testFiles . $file;

		if (!file_exists($testFile)) {
			if (getenv('CI')) {
				throw new Exception('Should not happen on CI: ' . $testFile);
			}

			$address = parent::_execute($address);
			file_put_contents($testFile, serialize($address));
			return $address;
		}

		$address = file_get_contents($testFile);
		$address = unserialize($address);

		return $address;
	}

}
