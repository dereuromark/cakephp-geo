<?php
namespace Geo\View\Helper;

use Geo\View\Helper\GoogleMapHelper;

/**
 * This is a CakePHP helper that helps users to integrate Google Places
 * into their application by only writing PHP code. This helper depends on jQuery.
 *
 * CodeAPI: http://code.google.com/intl/de-DE/apis/maps/documentation/javascript/basics.html
 * Icons/Images: http://gmapicons.googlepages.com/home
 *
 * @author Michael Heit
 * @link http://www.dereuromark.de/2010/12/21/googlemapsv3-cakephp-helper/
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class GooglePlacesHelper extends GoogleMapHelper {

	/**
	 * Needed helpers
	 *
	 * @var array
	 */
	public $helpers = [ 'Form', 'Html' ];


	/**
	 * Wrapper function for control like Form->input
	 *
	 * @param string $fieldName name of input field
	 * @param array $fieldOptions associative array of settings are passed. Should be the same as uses on Form->control
	 * @param array $googleOptions associative array of settings for places.Autocomplete
	 *
	 * @return string divContainer
	 */
	public function input( $fieldName, array $fieldOptions = [], $googleOptions = [] ) {
		return $this->control( $fieldName, $fieldOptions, $googleOptions );
	}

	/**
	 * This the initialization point of the script
	 * Returns the div container you can echo on the website
	 *
	 * @param string $fieldName name of input field
	 * @param array $fieldOptions associative array of settings are passed. Should be the same as uses on Form->control
	 * @param array $googleOptions associative array of settings for places.Autocomplete
	 *
	 * @return string divContainer
	 */
	public function control( $fieldName, $fieldOptions = [], $googleOptions = [] ) {

		$id = isset( $fieldOptions['id'] ) && $fieldOptions[ 'id' ] != '' ? $fieldOptions[ 'id' ] : $fieldName;

		$html = $this->Form->control( $fieldName, $fieldOptions );
		$html .= $this->Form->hidden( "{$fieldName}_lat", [ 'id' => "{$id}_lat" ] );
		$html .= $this->Form->hidden( "{$fieldName}_lon", [ 'id' => "{$id}_lon" ] );

		$this->_script( $id, $googleOptions );

		return $html;
	}

	/**
	 * Inserts the required javascript code
	 *
	 * @param $id string the id of the input field
	 * @param array $options associative array of settings for places.Autocomplete
	 */
	private function _script( $id, $options = [] ) {
		// autoinclude js?
		if ($this->_runtimeConfig['autoScript'] && !$this->_apiIncluded) {
			$res = $this->Html->script($this->apiUrl( ), ['block' => $this->_runtimeConfig['block']]);
			$this->_apiIncluded = true;

			if (!$this->_runtimeConfig['block']) {
				$result .= $res . PHP_EOL;
			}
			// usually already included
			//http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js
		}
		// still not very common: http://code.google.com/intl/de-DE/apis/maps/documentation/javascript/basics.html
		if (false && !empty($this->_runtimeConfig['autoScript']) && !$this->_gearsIncluded) {
			$res = $this->Html->script($this->gearsUrl(), ['block' => $this->_runtimeConfig['block']]);
			if (!$this->_runtimeConfig['block']) {
				$result .= $res . PHP_EOL;
			}
		}

		$js = "
			function initialize() {
				var options = " . json_encode( $options ) . ";
		        var input = document.getElementById('" . $id . "');
		        var hidden_lat = document.getElementById('" . $id . "_lat');
		        var hidden_lon = document.getElementById('" . $id . "_lon');
		        var autocomplete = new google.maps.places.Autocomplete(input, options);
		        
				google.maps.event.addDomListener(input, 'keydown', function(event) { 
				    if (event.keyCode === 13 && $('.pac-container:visible').length ) { 
				        event.preventDefault(); 
				    }
				}); 
		        autocomplete.addListener('place_changed', function() {
		            var place = autocomplete.getPlace();
		            hidden_lat.value = place.geometry.location.lat()
		            hidden_lon.value = place.geometry.location.lng()
		        });
		
		    }
		    initialize();
		";

		$script = 'jQuery(document).ready(function() {' . $js . '});';

		$this->Html->scriptBlock($script, ['block' => true]);
	}
}