<?php
if (!DIR_SOURCE) {
    define( 'DIR_SOURCE', realpath( dirname( __FILE__ ) . '/../../src' ) );
}
require_once dirname( __FILE__ ) . '/../AbstractTest.php';
require_once DIR_SOURCE.'/s3/server.php';

/**
 *  test case.
 */
class Server_Test extends SimpleStorageServiceAbstractTest  {
	
    public static $server = false;
    
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp ();
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		parent::tearDown ();
	}
	
	/**
	 * Constructs the test case.
	 */
	public function __construct() {
	}
	
    /**
     * process should return false on false input
     * 
     * @return void
     */
    public function testProcessFalseInput()
    {
        $server = new SimpleStorageService();
        $result = $server->process(false);
        $this->assertFalse($result);
    }

    /**
     * process should return false on true input
     * 
     * @return void
     */
    public function testProcessTrueInput()
    {
        $server = new SimpleStorageService();
        $result = $server->process(true);
        $this->assertFalse($result);
    }    
    
}
?>