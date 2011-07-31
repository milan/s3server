<?php
if (!DIR_SOURCE) {
    define( 'DIR_SOURCE', realpath( dirname( __FILE__ ) . '/../../src' ) );
}
require_once DIR_SOURCE.'/s3/lib/rest.php';
require_once dirname( __FILE__ ) . '/RESTAbstractTest.php';

/**
 * RestStorage test case.
 */
class RESTServerTest extends RESTAbstractTest {
	
	/**
	 * @var RestStorage
	 */
	private $RestStorage;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp ();
		
		$_SERVER['SCRIPT_NAME'] = '/workspace/s3server/src/index.php';
		
		$this->connectDB();
		$this->cleanDB();
		//$this->createBucket();
		$this->createUser();
		//$this->createObject();
		$this->RestStorage = new RestStorage($this->dbh);
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		$this->RestStorage = null;
		parent::tearDown ();
	}
	
	/**
	 * Constructs the test case.
	 */
	public function __construct() {
	}
	
	
	/**
	 * Tests RestStorage->putBucket()
	 */
	public function testProcessRequestPutBucket() {
	    // setup
	    $_SERVER['CONTENT_TYPE'] = '';
	    $_SERVER['CONTENT_LENGTH'] = '';
	    $_SERVER ["argv"] [0] = '';
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/s3server/src/'.$this->bucket1);
	    
	    // test
		$response = $this->RestStorage->processRequest($message);
		$this->assertEquals('200', $response->getResponseCode());
	}
	
	/**
	 * Tests RestStorage->deleteBucket()
	 */
	public function testProcessRequestDeleteBucket() {
	    // setup
	    $this->createBucket();
	    $_SERVER['CONTENT_TYPE'] = '';
	    $_SERVER['CONTENT_LENGTH'] = '';
	    $_SERVER ["argv"] [0] = '';
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/s3server/src/'.$this->bucket1);
	    
	    // test
		$response = $this->RestStorage->processRequest($message);
		$this->assertEquals('204', $response->getResponseCode());
	}		

	/**
	 * Tests RestStorage->getBucket()
	 */
	public function testProcessRequestGetBucket() {
	    // setup
	    $this->createBucket();
	    $_SERVER['CONTENT_TYPE'] = '';
	    $_SERVER['CONTENT_LENGTH'] = '';
	    $_SERVER ["argv"] [0] = '';
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/s3server/src/'.$this->bucket1);
	    
	    // test
		$response = $this->RestStorage->processRequest($message);
		$this->assertEquals('200', $response->getResponseCode());
	}		

	/**
	 * Tests RestStorage->get()
	 */
	public function testProcessRequestGet() {
	    // setup
	    $this->createBucket();
	    $_SERVER['CONTENT_TYPE'] = '';
	    $_SERVER['CONTENT_LENGTH'] = '';
	    $_SERVER ["argv"] [0] = '';
	    $message = $this->createHTTPRequest('GET', false, false, false, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/s3server/src/');
	    
	    // test
		$response = $this->RestStorage->processRequest($message);
		$this->assertEquals('200', $response->getResponseCode());
	}		

	/**
	 * Tests RestStorage->getBucketLocation()
	 */
	public function testProcessRequestGetBucketLocation() {
	    // setup
	    $this->createBucket();
	    $_SERVER['CONTENT_TYPE'] = '';
	    $_SERVER['CONTENT_LENGTH'] = '';
	    $_SERVER ["argv"] [0] = 'location';
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/s3server/src/'.$this->bucket1.'?location');
	    
	    // test
		$response = $this->RestStorage->processRequest($message);
		$this->assertEquals('200', $response->getResponseCode());
	}		

	/**
	 * Tests RestStorage->putObject()
	 */
	public function testProcessRequestPutObject() {
	    // setup
	    $this->createBucket();
	    $_SERVER['CONTENT_TYPE'] = '';
	    $_SERVER['CONTENT_LENGTH'] = strlen($this->value1);
	    $_SERVER ["argv"] [0] = '';
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/s3server/src/'.$this->bucket1.'/'.$this->key1);
	    
	    // test
		$response = $this->RestStorage->processRequest($message);
		$this->assertEquals('200', $response->getResponseCode());
	}		
	
	public function testProcessRequestGetObject() {
	    // setup
	    $this->createBucket();
	    $this->createObject();
	    $_SERVER['CONTENT_TYPE'] = '';
	    $_SERVER['CONTENT_LENGTH'] = '';
	    $_SERVER ["argv"] [0] = '';
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, false, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/s3server/src/'.$this->bucket1.'/'.$this->key1);
	    
	    // test
		$response = $this->RestStorage->processRequest($message);
		$this->assertEquals('200', $response->getResponseCode());
	}	

	public function testProcessRequestHeadObject() {
	    // setup
	    $this->createBucket();
	    $this->createObject();
	    $_SERVER['CONTENT_TYPE'] = '';
	    $_SERVER['CONTENT_LENGTH'] = strlen($this->value1);
	    $_SERVER ["argv"] [0] = '';
	    $message = $this->createHTTPRequest('HEAD', $this->bucket1, $this->key1, false, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/s3server/src/'.$this->bucket1.'/'.$this->key1);
	    
	    // test
		$response = $this->RestStorage->processRequest($message);
		$this->assertEquals('200', $response->getResponseCode());
	}	

	public function testProcessRequestDeleteObject() {
	    // setup
	    $this->createBucket();
	    $this->createObject();
	    $_SERVER['CONTENT_TYPE'] = '';
	    $_SERVER['CONTENT_LENGTH'] = '';
	    $_SERVER ["argv"] [0] = '';
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, $this->key1, false, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/s3server/src/'.$this->bucket1.'/'.$this->key1);
	    
	    // test
		$response = $this->RestStorage->processRequest($message);
		$this->assertEquals('204', $response->getResponseCode());
	}	

	public function testProcessRequestPutAcl() {
	    // setup
	    $this->createBucket();
	    $this->createObject();
	    $acp = $this->createACP();
	    $acl = $acp->generateXML();
	    $_SERVER['CONTENT_TYPE'] = '';
	    $_SERVER['CONTENT_LENGTH'] = strlen($acl);
	    $_SERVER ["argv"] [0] = 'acl';
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, null, $acl, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/s3server/src/'.$this->bucket1);
	    
	    // test
		$response = $this->RestStorage->processRequest($message);
		$this->assertEquals('200', $response->getResponseCode());
	}	
	
	public function testProcessRequestGetAcl() {
	    // setup
	    $this->createBucket();
	    $this->createObject();
	    $_SERVER['CONTENT_TYPE'] = '';
	    $_SERVER['CONTENT_LENGTH'] = '';
	    $_SERVER ["argv"] [0] = 'acl';
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, null, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/s3server/src/'.$this->bucket1.'/'.$this->key1);
	    
	    // test
		$response = $this->RestStorage->processRequest($message);
		$this->assertEquals('200', $response->getResponseCode());
	}		
	
	public function testProcessRequestPostBucketNotAllowed() {
	    // setup
	    $_SERVER['CONTENT_TYPE'] = '';
	    $_SERVER['CONTENT_LENGTH'] = '';
	    $_SERVER ["argv"] [0] = '';
	    $message = $this->createHTTPRequest('FOOBAR', $this->bucket1, null, null, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/s3server/src/'.$this->bucket1);
	    
	    // test
		$response = $this->RestStorage->processRequest($message);
		$this->assertEquals('MethodNotAllowed', $this->getErrorType($response->getBody()));		
		$this->assertEquals('405', $response->getResponseCode());		
	}		
		
	public function testProcessRequestPostACLBucketNotAllowed() {
	    // setup
	    $_SERVER['CONTENT_TYPE'] = '';
	    $_SERVER['CONTENT_LENGTH'] = '';
	    $_SERVER ["argv"] [0] = 'acl';
	    $message = $this->createHTTPRequest('POST', $this->bucket1, null, null, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/s3server/src/'.$this->bucket1);
	    
	    // test
		$response = $this->RestStorage->processRequest($message);
		$this->assertEquals('MethodNotAllowed', $this->getErrorType($response->getBody()));		
		$this->assertEquals('405', $response->getResponseCode());		
	}	

	public function testProcessRequestPostLocationBucketNotAllowed() {
	    // setup
	    $_SERVER['CONTENT_TYPE'] = '';
	    $_SERVER['CONTENT_LENGTH'] = '';
	    $_SERVER ["argv"] [0] = 'location';
	    $message = $this->createHTTPRequest('POST', $this->bucket1, null, null, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/s3server/src/'.$this->bucket1);
	    
	    // test
		$response = $this->RestStorage->processRequest($message);
		$this->assertEquals('MethodNotAllowed', $this->getErrorType($response->getBody()));		
		$this->assertEquals('405', $response->getResponseCode());		
	}		
	
}

?>