<?php
/**
 * Group test - Tools
 */namespace Tools\Test\Case;

class AllHelperTestsTest extends PHPUnit_Framework_TestSuite {

	/**
	 * Suite method, defines tests for this suite.
	 *
	 * @return void
	 */
	public static function suite() {
		$Suite = new CakeTestSuite('All Helper tests');
		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'View' . DS . 'Helper');
		return $Suite;
	}
}
