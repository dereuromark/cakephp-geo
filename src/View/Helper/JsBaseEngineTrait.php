<?php

namespace Geo\View\Helper;

/**
 * Basic Js Base Engine Trait derived from 2.x JsBaseEngineClass.
 *
 * Provides generic methods.
 */
trait JsBaseEngineTrait {

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

}
