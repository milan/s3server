<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once 'PHPUnit/Framework/TestSuite.php';
require_once dirname(__FILE__) . '/Common/CommonSuite.php';
require_once dirname(__FILE__) . '/REST/RESTSuite.php';
require_once dirname(__FILE__) . '/Server/ServerSuite.php';
require_once dirname(__FILE__) . '/Storage/StorageSuite.php';

/**
 * Static test suite.
 */
class AllTests extends PHPUnit_Framework_TestSuite {
	
	/**
	 * Constructs the test suite handler.
	 */
	public function __construct() {
		$this->setName ( 'AllTests' );
		$this->addTestSuite ( 'CommonSuite' );
		$this->addTestSuite ( 'RESTSuite' );
		$this->addTestSuite ( 'ServerSuite' );
		$this->addTestSuite ( 'StorageSuite' );
	}
	
	/**
	 * Creates the suite.
	 */
	public static function suite() {
		return new self ( );
	}
		
}
?>