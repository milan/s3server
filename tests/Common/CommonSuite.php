<?php
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'Common_ValidatorTest.php';

/**
 * Static test suite.
 */
class CommonSuite extends PHPUnit_Framework_TestSuite {
	
	/**
	 * Constructs the test suite handler.
	 */
	public function __construct() {
		$this->setName ( 'CommonSuite' );
		
		$this->addTestSuite ( 'Common_ValidatorTest' );
	
	}
	
	/**
	 * Creates the suite.
	 */
	public static function suite() {
		return new self ( );
	}
}
?>