<?php

namespace Geo\Test\TestCase\Database\Type;

use Cake\Database\Driver\Sqlite;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Geo\Database\Type\ObjectType;

class ObjectTypeTest extends TestCase {

	/**
	 * @return void
	 */
	public function testToPhp() {
		$objectType = new ObjectType();

		$value = serialize(new DateTime());
		$driver = new Sqlite();

		$result = $objectType->toPHP($value, $driver);
		$this->assertInstanceOf(DateTime::class, $result);

		$result = $objectType->toPHP(null, $driver);
		$this->assertNull($result);
	}

	/**
	 * @return void
	 */
	public function testTo() {
		$objectType = new ObjectType();

		$value = new DateTime();
		$driver = new Sqlite();

		$result = $objectType->toDatabase($value, $driver);
		$this->assertIsString($result);

		$result = $objectType->toDatabase(null, $driver);
		$this->assertNull($result);
	}

	/**
	 * @return void
	 */
	public function testMarshal() {
		$objectType = new ObjectType();

		$value = new DateTime();

		$result = $objectType->marshal($value);

		$this->assertSame($value, $result);
	}

}
