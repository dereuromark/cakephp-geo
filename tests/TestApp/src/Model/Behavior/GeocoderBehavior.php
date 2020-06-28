<?php

namespace TestApp\Model\Behavior;

use Cake\Utility\Text;
use Geo\Geocoder\Geocoder;
use Geo\Model\Behavior\GeocoderBehavior as GeoGeocoderBehavior;
use RuntimeException;
use Shim\TestSuite\TestTrait;

/**
 * Mocked version to avoid real API hits. Also auto-updates mock files when they cannot be found.
 */
class GeocoderBehavior extends GeoGeocoderBehavior {

	use TestTrait;

	/**
	 * Uses the Geocode class to query
	 *
	 * @param string $address
	 * @throws \RuntimeException
	 * @return \Geocoder\Location|null
	 */
	protected function _execute($address) {
		$this->_Geocoder = new Geocoder($this->_config);

		$file = Text::slug($address) . '.txt';

		$testFiles = ROOT . DS . 'tests' . DS . 'test_files' . DS . 'Behavior' . DS;
		$testFile = $testFiles . $file;

		if ($this->isDebug() || !file_exists($testFile)) {
			if (!$this->isDebug() && getenv('CI')) {
				throw new RuntimeException('Should not happen on CI: ' . $testFile);
			}

			$addresses = parent::_execute($address);
			file_put_contents($testFile, serialize($addresses));

			return $addresses;
		}

		$addresses = file_get_contents($testFile);
		$addresses = unserialize($addresses);

		return $addresses;
	}

}
