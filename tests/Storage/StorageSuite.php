<?php
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'Storage_AuthTest.php';
require_once 'Storage_BucketTest.php';
require_once 'Storage_UserTest.php';
require_once 'Storage_BucketACLTest.php';
require_once 'Storage_ObjectTest.php';


/**
 * Static test suite.
 */
class StorageSuite extends PHPUnit_Framework_TestSuite {
	
	/**
	 * Constructs the test suite handler.
	 */
	public function __construct() {
		$this->setName ( 'StorageSuite' );
		$this->addTestSuite ( 'Storage_AuthTest' );
		$this->addTestSuite ( 'Storage_BucketTest' );
		$this->addTestSuite ( 'Storage_UserTest' );
		$this->addTestSuite ( 'Storage_BucketACLTest' );
		$this->addTestSuite ( 'Storage_ObjectTest' );
	}
	
	/**
	 * Creates the suite.
	 */
	public static function suite() {
		return new self ( );
	}
			

}
?>