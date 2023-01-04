<?php

namespace Geo\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Type\BaseType;
use PDO;

/**
 * This can serialize and unserialize objects.
 */
class ObjectType extends BaseType {

	/**
	 * @param string|null $value
	 * @param \Cake\Database\Driver $driver
	 *
	 * @return object|null
	 */
	public function toPHP(mixed $value, Driver $driver): mixed {
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
	public function marshal(mixed $value): mixed {
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
	 * @param \Cake\Database\Driver $driver
	 *
	 * @return string|null
	 */
	public function toDatabase(mixed $value, Driver $driver): mixed {
		if ($value === null) {
			return $value;
		}

		return serialize($value);
	}

	/**
	 * @param mixed|null $value
	 * @param \Cake\Database\Driver $driver
	 *
	 * @return int
	 */
	public function toStatement(mixed $value, Driver $driver): int {
		if ($value === null) {
			return PDO::PARAM_NULL;
		}

		return PDO::PARAM_STR;
	}

}
