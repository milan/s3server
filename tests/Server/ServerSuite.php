<?php
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'ServerTest.php';

/**
 * Static test suite.
 */
class ServerSuite extends PHPUnit_Framework_TestSuite {
	
	/**
	 * Constructs the test suite handler.
	 */
	public function __construct() {
		$this->setName ( 'ServerSuite' );
		$this->addTestSuite ( 'Server_Test' );
	}
	
	/**
	 * Creates the suite.
	 */
	public static function suite() {
		return new self ( );
	}
}
?>
