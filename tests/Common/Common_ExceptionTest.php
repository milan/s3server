<?php

define( 'DIR_SOURCE', realpath( dirname( __FILE__ ) . '/../../src' ) );
require_once 'PHPUnit/Framework/TestCase.php';
require_once DIR_SOURCE.'/s3/lib/exceptions.php';

/**
 *  test case.
 */
class Common_ExceptionTest extends PHPUnit_Framework_TestCase
{
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        parent::tearDown();
    }
    
    /**
     * Constructs the test case.
     */
    public function __construct()
    {
    }

    public function testAccessDeniedException() {
        $this->setExpectedException('AccessDeniedException', "Access Denied", 403);
        throw new AccessDeniedException();
    }

    public function testAccessDeniedException2() {
        $this->setExpectedException('AccessDeniedException', "Access Not Allowed", 666);
        throw new AccessDeniedException("Access Not Allowed", 666);
    }   

    public function testAccessDeniedException3() {
        try {
            throw new AccessDeniedException();
        } catch (AccessDeniedException $e) {
            $this->assertEquals('Client', $e->getSoapFault());
            //$this->assertEquals("AccessDeniedException: [403]: Access Denied\n", $e->__toString());
            return;
        }
        $this->fail("Expected exception not thrown");
    }       
    
}

