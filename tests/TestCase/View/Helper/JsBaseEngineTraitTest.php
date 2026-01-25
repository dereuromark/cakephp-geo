<?php

namespace Geo\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Geo\View\Helper\JsBaseEngineTrait;

class JsBaseEngineTraitTest extends TestCase {

	/**
	 * @var object
	 */
	protected object $helper;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->helper = new class {
			use JsBaseEngineTrait;
		};
	}

	/**
	 * @return void
	 */
	public function testObject(): void {
		$result = $this->helper->object(['foo' => 'bar']);
		$this->assertSame('{"foo":"bar"}', $result);
	}

	/**
	 * @return void
	 */
	public function testObjectWithPrefixPostfix(): void {
		$result = $this->helper->object(['foo' => 'bar'], ['prefix' => 'var x = ', 'postfix' => ';']);
		$this->assertSame('var x = {"foo":"bar"};', $result);
	}

	/**
	 * @return void
	 */
	public function testValueWithString(): void {
		$result = $this->helper->value('hello');
		$this->assertSame('"hello"', $result);
	}

	/**
	 * @return void
	 */
	public function testValueWithStringUnquoted(): void {
		$result = $this->helper->value('hello', false);
		$this->assertSame('hello', $result);
	}

	/**
	 * @return void
	 */
	public function testValueWithNull(): void {
		$result = $this->helper->value(null);
		$this->assertSame('null', $result);
	}

	/**
	 * @return void
	 */
	public function testValueWithBool(): void {
		$this->assertSame('true', $this->helper->value(true));
		$this->assertSame('false', $this->helper->value(false));
	}

	/**
	 * @return void
	 */
	public function testValueWithInt(): void {
		$result = $this->helper->value(42);
		$this->assertSame('42', $result);
	}

	/**
	 * @return void
	 */
	public function testValueWithFloat(): void {
		$result = $this->helper->value(3.14);
		$this->assertStringContainsString('3.14', $result);
	}

	/**
	 * @return void
	 */
	public function testValueWithArray(): void {
		$result = $this->helper->value(['a', 'b']);
		$this->assertSame('["a","b"]', $result);
	}

	/**
	 * @return void
	 */
	public function testEscape(): void {
		$result = $this->helper->escape('Hello World');
		$this->assertSame('Hello World', $result);
	}

	/**
	 * @return void
	 */
	public function testEscapeWithQuotes(): void {
		$result = $this->helper->escape('Say "Hello"');
		$this->assertSame('Say \"Hello\"', $result);
	}

	/**
	 * @return void
	 */
	public function testEscapeWithNewlines(): void {
		$result = $this->helper->escape("Line1\nLine2");
		$this->assertSame('Line1\nLine2', $result);
	}

	/**
	 * @return void
	 */
	public function testEscapeWithBackslash(): void {
		$result = $this->helper->escape('path\\to\\file');
		$this->assertSame('path\\\\to\\\\file', $result);
	}

	/**
	 * @return void
	 */
	public function testEscapeWithUnicode(): void {
		$result = $this->helper->escape('Héllo Wörld');
		// Unicode characters are escaped to \uXXXX format for JavaScript safety
		$this->assertSame('H\u00e9llo W\u00f6rld', $result);
	}

	/**
	 * @return void
	 */
	public function testEscapeWithTab(): void {
		$result = $this->helper->escape("Hello\tWorld");
		$this->assertSame('Hello\tWorld', $result);
	}

}
