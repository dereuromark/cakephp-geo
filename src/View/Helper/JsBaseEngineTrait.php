<?php

namespace Geo\View\Helper;

use Cake\Routing\Router;

/**
 * Basic Js Base Engine Trait derived from 2.x JsBaseEngineClass.
 *
 * Provides generic methods.
 */
trait JsBaseEngineTrait {

	/**
	 * The js snippet for the current selection.
	 *
	 * @var string
	 */
	public $selection;

	/**
	 * Collection of option maps. Option maps allow other helpers to use generic names for engine
	 * callbacks and options. Allowing uniform code access for all engine types. Their use is optional
	 * for end user use though.
	 *
	 * @var array
	 */
	protected array $_optionMap = [];

	/**
	 * An array of lowercase method names in the Engine that are buffered unless otherwise disabled.
	 * This allows specific 'end point' methods to be automatically buffered by the JsHelper.
	 *
	 * @var array
	 */
	public $bufferedMethods = ['event', 'sortable', 'drag', 'drop', 'slider'];

	/**
	 * Contains a list of callback names -> default arguments.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $_callbackArguments = [];

	/**
	 * Create an `alert()` message in JavaScript
	 *
	 * @param string $message Message you want to alter.
	 * @return string completed alert()
	 */
	public function alert($message) {
		return 'alert("' . $this->escape($message) . '");';
	}

	/**
	 * Redirects to a URL. Creates a window.location modification snippet
	 * that can be used to trigger 'redirects' from JavaScript.
	 *
	 * @param array|string|null $url URL
	 * @return string completed redirect in javascript
	 */
	public function redirect($url = null) {
		return 'window.location = "' . Router::url($url) . '";';
	}

	/**
	 * Create a `confirm()` message
	 *
	 * @param string $message Message you want confirmed.
	 * @return string completed confirm()
	 */
	public function confirm($message) {
		return 'confirm("' . $this->escape($message) . '");';
	}

	/**
	 * Generate a confirm snippet that returns false from the current
	 * function scope.
	 *
	 * @param string $message Message to use in the confirm dialog.
	 * @return string completed confirm with return script
	 */
	public function confirmReturn($message) {
		$out = 'var _confirm = ' . $this->confirm($message);
		$out .= "if (!_confirm) {\n\treturn false;\n}";

		return $out;
	}

	/**
	 * Create a `prompt()` JavaScript function
	 *
	 * @param string $message Message you want to prompt.
	 * @param string $default Default message
	 * @return string completed prompt()
	 */
	public function prompt($message, $default = '') {
		return 'prompt("' . $this->escape($message) . '", "' . $this->escape($default) . '");';
	}

	/**
	 * Generates a JavaScript object in JavaScript Object Notation (JSON)
	 * from an array. Will use native JSON encode method if available, and $useNative == true
	 *
	 * ### Options:
	 *
	 * - `prefix` - String prepended to the returned data.
	 * - `postfix` - String appended to the returned data.
	 *
	 * @param object|array $data Data to be converted.
	 * @param array<string, mixed> $options Set of options, see above.
	 * @return string A JSON code block
	 */
	public function object($data = [], $options = []) {
		$defaultOptions = [
			'prefix' => '',
			'postfix' => '',
		];
		$options += $defaultOptions;

		return $options['prefix'] . json_encode($data) . $options['postfix'];
	}

	/**
	 * Converts a PHP-native variable of any type to a JSON-equivalent representation
	 *
	 * @param mixed $val A PHP variable to be converted to JSON
	 * @param bool|null $quoteString If false, leaves string values unquoted
	 * @param string $key Key name.
	 * @return string a JavaScript-safe/JSON representation of $val
	 */
	public function value($val = [], $quoteString = null, $key = 'value') {
		if ($quoteString === null) {
			$quoteString = true;
		}
		switch (true) {
			case (is_array($val) || is_object($val)):
				$val = $this->object($val);

				break;
			case ($val === null):
				$val = 'null';

				break;
			case (is_bool($val)):
				$val = ($val === true) ? 'true' : 'false';

				break;
			case (is_int($val)):
				break;
			case (is_float($val)):
				$val = sprintf('%.11f', $val);

				break;
			default:
				$val = $this->escape($val);
				if ($quoteString) {
					$val = '"' . $val . '"';
				}
		}

		return (string)$val;
	}

	/**
	 * Escape a string to be JSON friendly.
	 *
	 * Uses json_encode() internally for proper UTF-8 handling.
	 *
	 * @param string $string String that needs to get escaped.
	 * @return string Escaped string.
	 */
	public function escape($string) {
		$encoded = json_encode($string, JSON_UNESCAPED_SLASHES);
		if ($encoded === false) {
			return '';
		}

		// json_encode wraps string in quotes, strip them
		return substr($encoded, 1, -1);
	}

	/**
	 * Parse an options assoc array into a JavaScript object literal.
	 * Similar to object() but treats any non-integer value as a string,
	 * does not include `{ }`
	 *
	 * @param array<string, mixed> $options Options to be converted
	 * @param array<string> $safeKeys Keys that should not be escaped.
	 * @return string Parsed JSON options without enclosing { }.
	 */
	protected function _parseOptions($options, $safeKeys = []) {
		$out = [];
		$safeKeys = array_flip($safeKeys);
		foreach ($options as $key => $value) {
			if (!is_int($value) && !isset($safeKeys[$key])) {
				$value = $this->value($value);
			}
			$out[] = $key . ':' . $value;
		}
		sort($out);

		return implode(', ', $out);
	}

	/**
	 * Prepare callbacks and wrap them with function ([args]) { } as defined in
	 * _callbackArgs array.
	 *
	 * @param string $method Name of the method you are preparing callbacks for.
	 * @param array<string, mixed> $options Array of options being parsed
	 * @param array<string> $callbacks Additional Keys that contain callbacks
	 * @return array Array of options with callbacks added.
	 */
	protected function _prepareCallbacks($method, $options, array $callbacks = []) {
		$wrapCallbacks = true;
		if (isset($options['wrapCallbacks'])) {
			$wrapCallbacks = $options['wrapCallbacks'];
		}
		unset($options['wrapCallbacks']);
		if (!$wrapCallbacks) {
			return $options;
		}
		$callbackOptions = [];
		if (isset($this->_callbackArguments[$method])) {
			$callbackOptions = $this->_callbackArguments[$method];
		}
		$callbacks = array_unique(array_merge(array_keys($callbackOptions), $callbacks));

		foreach ($callbacks as $callback) {
			if (empty($options[$callback])) {
				continue;
			}
			$args = null;
			if (!empty($callbackOptions[$callback])) {
				$args = $callbackOptions[$callback];
			}
			$options[$callback] = 'function (' . $args . ') {' . $options[$callback] . '}';
		}

		return $options;
	}

	/**
	 * Convenience wrapper method for all common option processing steps.
	 * Runs _mapOptions, _prepareCallbacks, and _parseOptions in order.
	 *
	 * @param string $method Name of method processing options for.
	 * @param array<string, mixed> $options Array of options to process.
	 * @return string Parsed options string.
	 */
	protected function _processOptions($method, $options) {
		//$mapOptions = $this->_mapOptions();
		$options = $this->_prepareCallbacks($method, $options);
		$options = $this->_parseOptions($options, array_keys($this->_callbackArguments[$method]));

		return $options;
	}

}
