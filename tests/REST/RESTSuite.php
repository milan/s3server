<?php
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'RESTObjectTest.php';
require_once 'RESTBucketTest.php';
require_once 'RESTServerTest.php';
require_once 'RESTAccessControlPolicy.php';

/**
 * Static test suite.
 */
class RESTSuite extends PHPUnit_Framework_TestSuite
{
    
    /**
     * Constructs the test suite handler.
     */
    public function __construct()
    {
        $this->setName('RESTSuite');
        $this->addTestSuite('RESTAccessControlPolicy');
        $this->addTestSuite('RESTObjectTest');
        $this->addTestSuite('RESTBucketTest');
        //$this->addTestSuite('RESTServerTest');
    }
    
    /**
     * Creates the suite.
     */
    public static function suite()
    {
        return new self();
    }

}
?>