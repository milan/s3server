<?php
if (!DIR_SOURCE) {
    define( 'DIR_SOURCE', realpath( dirname( __FILE__ ) . '/../../src' ) );
}
require_once dirname( __FILE__ ) . '/../AbstractTest.php';
require_once DIR_SOURCE . '/s3/lib/storage.php';

/**
 *  test case.
 */
class Storage_UserTest extends SimpleStorageServiceAbstractTest 
{
    //private $dbh = null;
    private $storage = null;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();
        $this->storage = new Storage();
        $this->connectDB();
        $this->cleanDB();
        $this->storage->connect($this->dbh);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->dbh     = null;
        $this->storage = null;
        parent::tearDown();
    }

    /**
     * Constructs the test case.
     */
    public function __construct ()
    {
    }

    public function testCreateUserNameException ()
    {
        try {
            $this->storage->createUser(false, $this->email1, $this->accessId1, $this->secretKey1);
        } catch (InvalidArgumentException $e) {
            $this->assertEquals('$displayName cannot be empty: must be >=3 and <=64 characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    public function testCreateUserEmailException ()
    {
        try {
            $this->storage->createUser($this->displayName1, false, $this->accessId1, $this->secretKey1);
        } catch (InvalidArgumentException $e) {
            $this->assertEquals('$email address cannot be empty', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    public function testCreateUserAccessIdException ()
    {
        try {
            $this->storage->createUser($this->displayName1, $this->email1, false, $this->secretKey1);
        } catch (InvalidAccessKeyIdException $e) {
            $this->assertEquals('$accessId cannot be empty: must be 20 alphanumeric characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    public function testCreateUserSecretKeyException ()
    {
        try {
            $this->storage->createUser($this->displayName1, $this->email1, $this->accessId1, false);
        } catch (InvalidArgumentException $e) {
            $this->assertEquals('$secretKey cannot be empty: must be 40 characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    public function testCreateUserDuplicate ()
    {
        try {
            $this->storage->createUser($this->displayName1, $this->email1, $this->accessId1, $this->secretKey1);
            $this->storage->createUser($this->displayName1, $this->email1, $this->accessId1, $this->secretKey1);
        } catch (Exception $e) {
            return $e;
        }
        $this->fail('An expected exception has not been raised.');
    }

    public function testCreateUser ()
    {
        $result = $this->storage->createUser($this->displayName1, $this->email1, $this->accessId1, $this->secretKey1);
        $this->assertTrue($result);
    }
}
?>