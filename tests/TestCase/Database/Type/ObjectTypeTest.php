<?php

namespace Geo\Test\TestCase\Database\Type;

use ArrayObject;
use Cake\Database\Driver\Sqlite;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Geo\Database\Type\ObjectType;
use stdClass;

class ObjectTypeTest extends TestCase {

	/**
	 * @return void
	 */
	public function testToPhp(): void {
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
	public function testToDatabase(): void {
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
	public function testMarshal(): void {
		$objectType = new ObjectType();

		$value = new DateTime();

		$result = $objectType->marshal($value);

		$this->assertSame($value, $result);
	}

	/**
	 * @return void
	 */
	public function testMarshalNull(): void {
		$objectType = new ObjectType();

		$result = $objectType->marshal(null);

		$this->assertNull($result);
	}

	/**
	 * @return void
	 */
	public function testMarshalFromString(): void {
		$objectType = new ObjectType();

		$value = serialize(new DateTime());
		$result = $objectType->marshal($value);

		$this->assertInstanceOf(DateTime::class, $result);
	}

	/**
	 * Test that allowed classes are properly unserialized.
	 *
	 * @return void
	 */
	public function testToPhpAllowedClasses(): void {
		$objectType = new ObjectType();
		$driver = new Sqlite();

		$dateTime = new DateTime();
		$result = $objectType->toPHP(serialize($dateTime), $driver);
		$this->assertInstanceOf(DateTime::class, $result);

		$dateTimeImmutable = new DateTimeImmutable();
		$result = $objectType->toPHP(serialize($dateTimeImmutable), $driver);
		$this->assertInstanceOf(DateTimeImmutable::class, $result);

		$dateTimeZone = new DateTimeZone('UTC');
		$result = $objectType->toPHP(serialize($dateTimeZone), $driver);
		$this->assertInstanceOf(DateTimeZone::class, $result);

		$dateInterval = new DateInterval('P1D');
		$result = $objectType->toPHP(serialize($dateInterval), $driver);
		$this->assertInstanceOf(DateInterval::class, $result);

		$date = new Date();
		$result = $objectType->toPHP(serialize($date), $driver);
		$this->assertInstanceOf(Date::class, $result);

		$stdClass = new stdClass();
		$stdClass->foo = 'bar';
		$result = $objectType->toPHP(serialize($stdClass), $driver);
		$this->assertInstanceOf(stdClass::class, $result);
		$this->assertSame('bar', $result->foo);
	}

	/**
	 * Test that disallowed classes are blocked to prevent object injection attacks.
	 *
	 * @return void
	 */
	public function testToPhpDisallowedClasses(): void {
		$objectType = new ObjectType();
		$driver = new Sqlite();

		// ArrayObject is a valid serializable class but not in the allowed list
		$disallowed = new ArrayObject(['data' => 'test']);
		$serialized = serialize($disallowed);

		$result = $objectType->toPHP($serialized, $driver);

		$this->assertInstanceOf('__PHP_Incomplete_Class', $result);
	}

	/**
	 * Test that marshal blocks disallowed classes.
	 *
	 * @return void
	 */
	public function testMarshalDisallowedClasses(): void {
		$objectType = new ObjectType();

		// ArrayObject is a valid serializable class but not in the allowed list
		$disallowed = new ArrayObject(['data' => 'test']);
		$serialized = serialize($disallowed);

		$result = $objectType->marshal($serialized);

		$this->assertInstanceOf('__PHP_Incomplete_Class', $result);
	}

}
