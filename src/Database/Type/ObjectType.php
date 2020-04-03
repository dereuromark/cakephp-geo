<?php

namespace Geo\Database\Type;

use Cake\Database\DriverInterface;
use Cake\Database\Type\BaseType;
use PDO;

/**
 * This can serialize and unserialize objects.
 */
class ObjectType extends BaseType {

	/**
	 * @param string|null $value
	 * @param \Cake\Database\DriverInterface $driver
	 *
	 * @return object|null
	 */
	public function toPHP($value, DriverInterface $driver) {
		if ($value === null) {
			return $value;
		}
		return unserialize($value);
	}

	/**
	 * @param object|string|null $value
	 *
	 * @return object|null
	 */
	public function marshal($value) {
		if ($value === null) {
			return $value;
		}
		if (is_object($value)) {
			return $value;
		}
		return unserialize($value);
	}

	/**
	 * @param object|null $value
	 * @param \Cake\Database\DriverInterface $driver
	 *
	 * @return string|null
	 */
	public function toDatabase($value, DriverInterface $driver) {
		if ($value === null) {
			return $value;
		}
		return serialize($value);
	}

	/**
	 * @param mixed|null $value
	 * @param \Cake\Database\DriverInterface $driver
	 *
	 * @return int
	 */
	public function toStatement($value, DriverInterface $driver) {
		if ($value === null) {
			return PDO::PARAM_NULL;
		}
		return PDO::PARAM_STR;
	}

}
