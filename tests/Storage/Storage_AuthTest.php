<?php
if (!DIR_SOURCE) {
    define( 'DIR_SOURCE', realpath( dirname( __FILE__ ) . '/../../src' ) );
}
require_once dirname( __FILE__ ) . '/../AbstractTest.php';
require_once DIR_SOURCE.'/s3/lib/storage.php';

/**
 *  test case.
 */
class Storage_AuthTest extends SimpleStorageServiceAbstractTest  {
        
    private $storage  = null;
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp() {
        parent::setUp ();
        $this->storage = new Storage();
        $this->connectDB();
        $this->cleanDB();
        $this->createUser();
        $this->storage->connect($this->dbh);
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown() {
        $this->dbh     = null;
        $this->storage = null;
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
    public function testAuthenticateUser ()
    {
        $result = $this->storage->authenticateUser($this->accessId1, $this->signature1, $this->stringToSign1);
        $this->assertEquals($this->accessId1, $result['access_key']);
    }

    /**
     * process should return false on false input
     * 
     * @return void
     */
    public function testAuthenticateUserFalse ()
    {
        $this->setExpectedException('InvalidAccessKeyIdException');
        $result = $this->storage->authenticateUser($this->badAccessId, $this->signature1, $this->stringToSign1);
        $this->assertNull($result);
    }

    /**
     * process should return false on false input
     * 
     * @return void
     */
    public function testAuthenticateEmailUser ()
    {
        $result = $this->storage->authenticateUser($this->email1, $this->signature1, $this->stringToSign1);
        $this->assertEquals($this->email1, $result['email_address']);
    }

    /**
     * process should return false on false input
     * 
     * @return void
     */
    public function testAuthenticateUserEmailFalse ()
    {
        try {
            $this->storage->authenticateUser('milan@magudia', $this->signature1, $this->stringToSign1);
        } catch (InvalidArgumentException $e) {
            $this->assertEquals('$email address is not valid', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * process should return false on false input
     * 
     * @return void
     */
    public function testAuthenticateUserFalseId ()
    {
        try {
            $this->storage->authenticateUser(false, $this->signature1, $this->stringToSign1);
        } catch (InvalidAccessKeyIdException  $e) {
            $this->assertEquals('$accessId cannot be empty: must be 20 alphanumeric characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * process should return false on false input
     * 
     * @return void
     */
    public function testAuthenticateUserFalseSignature ()
    {
        $this->setExpectedException('SignatureDoesNotMatchException');
        $this->storage->authenticateUser($this->email1, false, $this->stringToSign1);
    }

    /**
     * process should return false on false input
     * 
     * @return void
     */
    public function testAuthenticateUserFalseStringToSign ()
    {
        $this->setExpectedException('SignatureDoesNotMatchException');
        $this->storage->authenticateUser($this->email1, $this->signature1, false);
    }    

}
?>