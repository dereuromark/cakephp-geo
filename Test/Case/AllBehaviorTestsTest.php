<?php
/**
 * Group test - Tools
 */namespace Tools\Test\Case;

class AllBehaviorTestsTest extends PHPUnit_Framework_TestSuite {

	/**
	 * Suite method, defines tests for this suite.
	 *
	 * @return void
	 */
	public static function suite() {
		$Suite = new CakeTestSuite('All Behavior tests');
		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Model' . DS . 'Behavior');
		return $Suite;
	}
}
