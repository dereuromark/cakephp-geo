<?php
declare(strict_types=1);

namespace Geo\Test\TestCase\Exception;

use Cake\Core\Exception\CakeException;
use Cake\TestSuite\TestCase;
use Geo\Exception\CalculatorException;
use Geo\Exception\InconclusiveException;
use Geo\Exception\NotAccurateEnoughException;
use InvalidArgumentException;

/**
 * @uses \Geo\Exception\CalculatorException
 * @uses \Geo\Exception\InconclusiveException
 * @uses \Geo\Exception\NotAccurateEnoughException
 */
class ExceptionTest extends TestCase {

	/**
	 * Test CalculatorException is an InvalidArgumentException.
	 *
	 * @return void
	 */
	public function testCalculatorExceptionInheritance(): void {
		$exception = new CalculatorException('Test message');

		$this->assertInstanceOf(InvalidArgumentException::class, $exception);
		$this->assertSame('Test message', $exception->getMessage());
	}

	/**
	 * Test CalculatorException can be thrown and caught.
	 *
	 * @return void
	 */
	public function testCalculatorExceptionThrow(): void {
		$this->expectException(CalculatorException::class);
		$this->expectExceptionMessage('Calculator error');

		throw new CalculatorException('Calculator error');
	}

	/**
	 * Test InconclusiveException is a CakeException.
	 *
	 * @return void
	 */
	public function testInconclusiveExceptionInheritance(): void {
		$exception = new InconclusiveException('Inconclusive result');

		$this->assertInstanceOf(CakeException::class, $exception);
		$this->assertSame('Inconclusive result', $exception->getMessage());
	}

	/**
	 * Test InconclusiveException can be thrown and caught.
	 *
	 * @return void
	 */
	public function testInconclusiveExceptionThrow(): void {
		$this->expectException(InconclusiveException::class);
		$this->expectExceptionMessage('Geocoding result was inconclusive');

		throw new InconclusiveException('Geocoding result was inconclusive');
	}

	/**
	 * Test NotAccurateEnoughException is a CakeException.
	 *
	 * @return void
	 */
	public function testNotAccurateEnoughExceptionInheritance(): void {
		$exception = new NotAccurateEnoughException('Not accurate');

		$this->assertInstanceOf(CakeException::class, $exception);
		$this->assertSame('Not accurate', $exception->getMessage());
	}

	/**
	 * Test NotAccurateEnoughException can be thrown and caught.
	 *
	 * @return void
	 */
	public function testNotAccurateEnoughExceptionThrow(): void {
		$this->expectException(NotAccurateEnoughException::class);
		$this->expectExceptionMessage('Geocoding result not accurate enough');

		throw new NotAccurateEnoughException('Geocoding result not accurate enough');
	}

	/**
	 * Test CalculatorException with code.
	 *
	 * @return void
	 */
	public function testCalculatorExceptionWithCode(): void {
		$exception = new CalculatorException('Error', 500);

		$this->assertSame('Error', $exception->getMessage());
		$this->assertSame(500, $exception->getCode());
	}

	/**
	 * Test InconclusiveException with code.
	 *
	 * @return void
	 */
	public function testInconclusiveExceptionWithCode(): void {
		$exception = new InconclusiveException('Test message', 500);

		$this->assertSame('Test message', $exception->getMessage());
		$this->assertSame(500, $exception->getCode());
	}

	/**
	 * Test NotAccurateEnoughException with code.
	 *
	 * @return void
	 */
	public function testNotAccurateEnoughExceptionWithCode(): void {
		$exception = new NotAccurateEnoughException('Accuracy error', 422);

		$this->assertSame('Accuracy error', $exception->getMessage());
		$this->assertSame(422, $exception->getCode());
	}

}
