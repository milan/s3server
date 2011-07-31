<?php
if (!DIR_SOURCE) {
    define( 'DIR_SOURCE', realpath( dirname( __FILE__ ) . '/../../src' ) );
}
require_once DIR_SOURCE.'/s3/lib/rest.php';
require_once dirname( __FILE__ ) . '/RESTAbstractTest.php';

/**
 * RestStorage test case.
 */
class RESTObjectTest extends RESTAbstractTest {
	
	/**
	 * @var RestStorage
	 */
	private $RestStorage;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp ();
		
		$this->connectDB();
		$this->cleanDB();
		$this->createBucket();
		$this->createUser();
		$this->createObject();
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
	 * Tests RestStorage->deleteObject()
	 */
	public function testDeleteObject() {
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, $this->key1, false, $this->accessId1, $this->secretKey1);
		$response = $this->RestStorage->deleteObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('204', $response->getResponseCode());
	}
	
	public function testDeleteObjectAuthenticatedUserFull() {
	    // setup 
	    $this->_setBucketACL($this->createACPAuthenticatedUsers('FULL_CONTROL'));
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, $this->key1, false, $this->accessId3, $this->secretKey3);
	    
	    // test
		$response = $this->RestStorage->deleteObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('204', $response->getResponseCode());
	}	
	
	public function testDeleteObjectAuthenticatedUserWrite() {
	    // setup 
	    $this->_setBucketACL($this->createACPAuthenticatedUsers('WRITE'));
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, $this->key1, false, $this->accessId3, $this->secretKey3);
	    
	    // test
		$response = $this->RestStorage->deleteObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('204', $response->getResponseCode());
	}	

	public function testDeleteObjectAuthenticatedUserRead() {
	    // setup 
	    $this->_setBucketACL($this->createACPAuthenticatedUsers('READ'));
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, $this->key1, false, $this->accessId3, $this->secretKey3);
	    
	    // test
		$response = $this->RestStorage->deleteObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));
		$this->assertEquals('403', $response->getResponseCode());		
	}		
	
	
	public function testDeleteObjectAllUserFull() {
	    // setup 
	    $this->_setBucketACL($this->createACPAllUsers('FULL_CONTROL'));
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, $this->key1, false, false, false);
	    
	    // test
		$response = $this->RestStorage->deleteObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('204', $response->getResponseCode());
	}	
	
	public function testDeleteObjectAllUserWrite() {
	    // setup 
	    $this->_setBucketACL($this->createACPAllUsers('WRITE'));
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, $this->key1, false, false, false);
	    
	    // test
		$response = $this->RestStorage->deleteObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('204', $response->getResponseCode());
	}	

	public function testDeleteObjectAllUserRead() {
	    // setup 
	    $this->_setBucketACL($this->createACPAllUsers('READ'));
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, $this->key1, false, false, false);
	    
	    // test
		$response = $this->RestStorage->deleteObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));
		$this->assertEquals('403', $response->getResponseCode());		
	}	
	
	public function testDeleteObjectUnexpectedContent() {
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1);
		$response = $this->RestStorage->deleteObject($this->bucket1, $this->key1, $message);
	    $this->assertEquals('UnexpectedContent', $this->getErrorType($response->getBody()));
	    $this->assertEquals('400', $response->getResponseCode());
	}	
	
	public function testDeleteObjectTwice() {
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, $this->key1, false, $this->accessId1, $this->secretKey1, -1);
		$response = $this->RestStorage->deleteObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('204', $response->getResponseCode());
		
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, $this->key1, false, $this->accessId1, $this->secretKey1, -1);
		$response = $this->RestStorage->deleteObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('204', $response->getResponseCode());		
	}	
	
	public function testDeleteObjectSignatureDoesNotMatch() {
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, $this->key1, false, $this->accessId1, $this->secretKey2);
	    //$this->markTestIncomplete ( "putObject test not implemented" );
		$response = $this->RestStorage->deleteObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('SignatureDoesNotMatch', $this->getErrorType($response->getBody()));
		$this->assertEquals('403', $response->getResponseCode());
	}	
	
	public function testDeleteObjectPermissionDenied() {
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, $this->key1, false, $this->accessId3, $this->secretKey3);
		$response = $this->RestStorage->deleteObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));
		$this->assertEquals('403', $response->getResponseCode());
	}			
	
	public function testDeleteObjectUnresolvableGrantByEmailAddress() {
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, $this->key1, false, 'resume@magudia.com', $this->secretKey1);
		$response = $this->RestStorage->deleteObject($this->bucket1, $this->key1, $message);		
	    $this->assertEquals('UnresolvableGrantByEmailAddress', $this->getErrorType($response->getBody()));
	    $this->assertEquals('400', $response->getResponseCode());
	}		

	public function testDeleteObjectEmail() {
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, $this->key1, false, $this->email1, $this->secretKey1);
		$response = $this->RestStorage->deleteObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('204', $response->getResponseCode());
	}		
	
	public function testDeleteObjectInvalidAccessKeyId() {
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, $this->key1, false, $this->blankAccessId, $this->secretKey1);
		$response = $this->RestStorage->deleteObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('InvalidAccessKeyId', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());
	}		
	
	public function testDeleteObjectNoSuchBucket() {
	    $message = $this->createHTTPRequest('DELETE', $this->blankBucket, $this->key1, false, $this->accessId1, $this->secretKey1);
		$response = $this->RestStorage->deleteObject($this->blankBucket, $this->key1, $message);
		$this->assertEquals('NoSuchBucket', $this->getErrorType($response->getBody()));		
		$this->assertEquals('404', $response->getResponseCode());
	}	

	
	/**
	 * Tests RestStorage->getObject()
	 */
	public function testGetObject() {
		$message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1);
        $response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);   
        $this->assertEquals($this->value1, $response->getBody());     
	}
	
	/**
	 * Tests RestStorage->getObject()
	 */

	
	public function testGetObjectAllUsersFullControl() {
	    // setup
	    $this->_setObjectACL($this->createACPAllUsers('FULL_CONTROL'));
		$message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, false, false, false);
		
		// test
        $response =$this->RestStorage->getObject($this->bucket1, $this->key1, $message);   
        $this->assertEquals($this->value1, $response->getBody());     
	}	
	
	public function testGetObjectAllUsers() {
	    // setup
	    $this->_setObjectACL($this->createACPAllUsers('READ'));
		$message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, false, false, false);
		
		// test
        $response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);   
        $this->assertEquals($this->value1, $response->getBody());     
	}

	public function testGetObjectAllUsersFail() {
	    // setup
	    $this->_setObjectACL($this->createACPAllUsers('WRITE_ACP'));
		$message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, false, false, false);
		
		// test
        $response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);    
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));
		$this->assertEquals('403', $response->getResponseCode());          
	}

	public function testGetObjectAuthenticatedUserFullControl() {
	    // setup
	    $this->_setObjectACL($this->createACPAuthenticatedUsers('FULL_CONTROL'));
		$message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value3, $this->accessId3, $this->secretKey3);
		
		// test
        $response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);   
        $this->assertEquals($this->value1, $response->getBody());     
	}

	public function testGetObjectAuthenticatedUserRead() {
	    // setup
	    $this->_setObjectACL($this->createACPAuthenticatedUsers('READ'));
		$message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value3, $this->accessId3, $this->secretKey3);
		
		// test
        $response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);   
        $this->assertEquals($this->value1, $response->getBody());     
	}	
	
	public function testGetObjectAuthenticatedUserFail() {
	    // setup
	    $this->_setObjectACL($this->createACPAuthenticatedUsers('WRITE_ACP'));
		$message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value3, $this->accessId3, $this->secretKey3);
		
		// test
        $response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);    
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));
		$this->assertEquals('403', $response->getResponseCode());  
	}
			
	
	public function testGetObjectSignatureDoesNotMatch() {
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey2);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('SignatureDoesNotMatch', $this->getErrorType($response->getBody()));
		$this->assertEquals('403', $response->getResponseCode());
	}	
	
	public function testGetObjectPermissionDenied() {
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->accessId3, $this->secretKey3);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));
		$this->assertEquals('403', $response->getResponseCode());
	}		
	
	public function testGetObjectUnresolvableGrantByEmailAddress() {
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, 'resume@magudia.com', $this->secretKey1);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);		
	    $this->assertEquals('UnresolvableGrantByEmailAddress', $this->getErrorType($response->getBody()));
	    $this->assertEquals('400', $response->getResponseCode());
	}		

	public function testGetObjectEmail() {
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->email1, $this->secretKey1);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('200', $response->getResponseCode());
	}		
	
	public function testGetObjectInvalidAccessKeyId() {
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->blankAccessId, $this->secretKey1);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('InvalidAccessKeyId', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());
	}		
	
	public function testGetObjectNoSuchBucket() {
	    $message = $this->createHTTPRequest('GET', $this->blankBucket, $this->key1, $this->value1, $this->accessId1, $this->secretKey1);
		$response = $this->RestStorage->getObject($this->blankBucket, $this->key1, $message);
		$this->assertEquals('NoSuchBucket', $this->getErrorType($response->getBody()));		
		$this->assertEquals('404', $response->getResponseCode());
	}			
	
	public function testGetObjectRange1() {
	    $rangeStart = 0;
	    $rangeEnd   = 4;
	    $headers = array('Range' => $rangeStart."-".$rangeEnd);
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, -1, $headers);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);
	    $this->assertEquals ( substr($this->value1,$rangeStart, $rangeEnd), $response->getBody() );
	    $this->assertEquals('200', $response->getResponseCode());
	    $this->assertEquals($rangeStart."-".$rangeEnd."/".strlen($this->value1), $response->getHeader('Content-Length'));
	}				
	
	public function testGetObjectRange2() {
	    $rangeStart = 5;
	    $rangeEnd   = 12;
	    $headers = array('Range' => $rangeStart."-".$rangeEnd);
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, -1, $headers);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);
	    $this->assertEquals ( substr($this->value1,$rangeStart, $rangeEnd), $response->getBody() );
	    $this->assertEquals('200', $response->getResponseCode());
	    $this->assertEquals($rangeStart."-".$rangeEnd."/".strlen($this->value1), $response->getHeader('Content-Length'));
	}		
	
	public function testGetObjectInvalidRange1() {
	    $rangeStart = -1;
	    $rangeEnd   = 8;
	    $headers = array('Range' => "$rangeStart-$rangeEnd");
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, -1, $headers);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('InvalidRange', $this->getErrorType($response->getBody()));		
		$this->assertEquals('416', $response->getResponseCode());		
	}	

	public function testGetObjectInvalidRange2() {
	    $rangeStart = 8;
	    $rangeEnd   = 5;
	    $headers = array('Range' => "$rangeStart-$rangeEnd");
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, -1, $headers);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('InvalidRange', $this->getErrorType($response->getBody()));		
		$this->assertEquals('416', $response->getResponseCode());		
	}		

	public function testGetObjectInvalidRange3() {
	    $rangeStart = 8;
	    $rangeEnd   = strlen($this->value1) + 5;
	    $headers = array('Range' => "$rangeStart-$rangeEnd");
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, -1, $headers);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('InvalidRange', $this->getErrorType($response->getBody()));		
		$this->assertEquals('416', $response->getResponseCode());		
	}	
	
	public function testGetObjectIfMatch() {
	    $headers = array('If-Match' => md5($this->value1));
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, -1, $headers);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);	    
	    $this->assertEquals ( $this->value1, $response->getBody() );
	    $this->assertEquals('200', $response->getResponseCode());
	}
	
	public function testGetObjectIfMatchNegative() {
	    $headers = array('If-Match' => md5($this->value2));
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, -1, $headers);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);	    
	    $this->assertEquals('412', $response->getResponseCode());
	    $body = $response->getBody();
	    $this->assertTrue(empty($body));
	}	
	
	public function testGetObjectIfNoneMatch() {
	    $headers = array('If-None-Match' => md5($this->value2));
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, -1, $headers);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);	    
	    $this->assertEquals ( $this->value1, $response->getBody() );
	    $this->assertEquals('200', $response->getResponseCode());
	}
	
	public function testGetObjectIfNoneMatchNegative() {
	    $headers = array('If-None-Match' => md5($this->value1));
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, -1, $headers);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);	    
	    $this->assertEquals('304', $response->getResponseCode());
	    $body = $response->getBody();
	    $this->assertTrue(empty($body));
	}		
	
	public function testGetObjectIfModifiedSince() {
	    $headers = array('If-Modified-Since' => time(strtotime($this->lastUpdated1) - 5));
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, -1, $headers);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);	    
	    $this->assertEquals ( $this->value1, $response->getBody() );
	    $this->assertEquals('200', $response->getResponseCode());
	}
	
	public function testGetObjectIfModifiedSinceNegative() {
	    $headers = array('If-Modified-Since' => date('Y-m-d H:i:s', time()));
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, -1, $headers);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);	    
	    $this->assertEquals('304', $response->getResponseCode());
	    $body = $response->getBody();
	    $this->assertTrue(empty($body));
	}	
	
	public function testGetObjectIfUnmodifiedSince() {
	    $headers = array('If-Unmodified-Since' => date('Y-m-d H:i:s', time()));
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, -1, $headers);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);	    
	    $this->assertEquals ( $this->value1, $response->getBody() );
	    $this->assertEquals('200', $response->getResponseCode());
	}
	
	public function testGetObjectIfUnmodifiedSinceNegative() {
	    $headers = array('If-Unmodified-Since' => time(strtotime($this->lastUpdated1) - 5));
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, -1, $headers);
		$response = $this->RestStorage->getObject($this->bucket1, $this->key1, $message);	    
	    $this->assertEquals('412', $response->getResponseCode());
	    $body = $response->getBody();
	    $this->assertTrue(empty($body));
	}	
		
	/**
	 * Tests RestStorage->headObject()
	 */
	public function testHeadObject() {
		$message = $this->createHTTPRequest('HEAD', $this->bucket1, $this->key1, false, $this->accessId1, $this->secretKey1);
        $response = $this->RestStorage->headObject($this->bucket1, $this->key1, $message);
	    $this->assertEquals('200', $response->getResponseCode());           
	    $body = $response->getBody();
	    $this->assertTrue(empty($body));
	}

	public function testHeadObjectUnexpectedContent() {
		$message = $this->createHTTPRequest('HEAD', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1);
        $response = $this->RestStorage->headObject($this->bucket1, $this->key1, $message);
	    $this->assertEquals('UnexpectedContent', $this->getErrorType($response->getBody()));
	    $this->assertEquals('400', $response->getResponseCode());
	}	
	
	public function testHeadObjectSignatureDoesNotMatch() {
	    $message = $this->createHTTPRequest('HEAD', $this->bucket1, $this->key1, false, $this->accessId1, $this->secretKey2);
		$response = $this->RestStorage->headObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('SignatureDoesNotMatch', $this->getErrorType($response->getBody()));
		$this->assertEquals('403', $response->getResponseCode());
	}	
	
	public function testHeadObjectPermissionDenied() {
	    $message = $this->createHTTPRequest('HEAD', $this->bucket1, $this->key1, false, $this->accessId3, $this->secretKey3);
		$response = $this->RestStorage->headObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));
		$this->assertEquals('403', $response->getResponseCode());
	}		
	
	public function testHeadObjectUnresolvableGrantByEmailAddress() {
	    $message = $this->createHTTPRequest('HEAD', $this->bucket1, $this->key1, false, 'resume@magudia.com', $this->secretKey1);
		$response = $this->RestStorage->headObject($this->bucket1, $this->key1, $message);		
	    $this->assertEquals('UnresolvableGrantByEmailAddress', $this->getErrorType($response->getBody()));
	    $this->assertEquals('400', $response->getResponseCode());
	}		

	public function testHeadObjectEmail() {
	    $message = $this->createHTTPRequest('HEAD', $this->bucket1, $this->key1, false, $this->email1, $this->secretKey1);
		$response = $this->RestStorage->headObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('200', $response->getResponseCode());
	    $body = $response->getBody();
	    $this->assertTrue(empty($body));		
	}		
	
	public function testHeadObjectInvalidAccessKeyId() {
	    $message = $this->createHTTPRequest('HEAD', $this->bucket1, $this->key1, false, $this->blankAccessId, $this->secretKey1);
		$response = $this->RestStorage->headObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('InvalidAccessKeyId', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());
	}		
	
	public function testHeadObjectNoSuchBucket() {
	    $message = $this->createHTTPRequest('HEAD', $this->blankBucket, $this->key1, false, $this->accessId1, $this->secretKey1);
		$response = $this->RestStorage->headObject($this->blankBucket, $this->key1, $message);
		$this->assertEquals('NoSuchBucket', $this->getErrorType($response->getBody()));		
		$this->assertEquals('404', $response->getResponseCode());
	}		
	
	/**
	 * Tests RestStorage->putObject()
	 */
	public function testPutObject() {
	    $headers = array('Content-Md5' => $this->_setMD5($this->value1), 'Content-Type' => $this->content_type1);
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, false, $headers, $this->metadata1);
		$response = $this->RestStorage->putObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('', $response->getBody());
		$this->assertEquals('200', $response->getResponseCode());
	}
	
	public function testPutObjectAllUsersFullControl() {
	    $this->_setBucketACL($this->createACPAllUsers('FULL_CONTROL'));
	    $headers = array('Content-Md5' => $this->_setMD5($this->value1), 'Content-Type' => $this->content_type1);
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $this->value1, false, false, false, $headers, $this->metadata1);
		$response = $this->RestStorage->putObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('', $response->getBody());
		$this->assertEquals('200', $response->getResponseCode());
	}	
	
	public function testPutObjectAuthenticatedUserFullControl() {
	    $this->_setBucketACL($this->createACPAuthenticatedUsers('FULL_CONTROL'));
	    $headers = array('Content-Md5' => $this->_setMD5($this->value1), 'Content-Type' => $this->content_type1);
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $this->value1, $this->accessId3, $this->secretKey3, false, $headers, $this->metadata1);
		$response = $this->RestStorage->putObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('', $response->getBody());
		$this->assertEquals('200', $response->getResponseCode());
	}		
	
	public function testPutObjectAllUsersWrite() {
	    $this->_setBucketACL($this->createACPAllUsers('WRITE'));
	    $headers = array('Content-Md5' => $this->_setMD5($this->value1), 'Content-Type' => $this->content_type1);
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $this->value1, false, false, false, $headers, $this->metadata1);
		$response = $this->RestStorage->putObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('', $response->getBody());
		$this->assertEquals('200', $response->getResponseCode());
	}	
	
	public function testPutObjectAuthenticatedUserWrite() {
	    $this->_setBucketACL($this->createACPAuthenticatedUsers('WRITE'));
	    $headers = array('Content-Md5' => $this->_setMD5($this->value1), 'Content-Type' => $this->content_type1);
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $this->value1, $this->accessId3, $this->secretKey3, false, $headers, $this->metadata1);
		$response = $this->RestStorage->putObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('', $response->getBody());
		$this->assertEquals('200', $response->getResponseCode());
	}

	public function testPutObjectAllUsersRead() {
	    $this->_setBucketACL($this->createACPAllUsers('READ'));
	    $headers = array('Content-Md5' => $this->_setMD5($this->value1), 'Content-Type' => $this->content_type1);
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $this->value1, false, false, false, $headers, $this->metadata1);
		$response = $this->RestStorage->putObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));
		$this->assertEquals('403', $response->getResponseCode());
	}	
	
	public function testPutObjectAuthenticatedUserRead() {
	    $this->_setBucketACL($this->createACPAuthenticatedUsers('READ'));
	    $headers = array('Content-Md5' => $this->_setMD5($this->value1), 'Content-Type' => $this->content_type1);
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $this->value1, $this->accessId3, $this->secretKey3, false, $headers, $this->metadata1);
		$response = $this->RestStorage->putObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));
		$this->assertEquals('403', $response->getResponseCode());
	}		
	
	public function testPutObjectEmptyMD5() {
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1);
		$response = $this->RestStorage->putObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('200', $response->getResponseCode());
	}	
	
	public function testPutObjectBadDigest() {
	    $headers = array('Content-Md5' => $this->_setMD5($this->value2));
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, false, $headers);
		$response = $this->RestStorage->putObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('400', $response->getResponseCode());
		$this->assertEquals('BadDigest', $this->getErrorType($response->getBody()));
	}		
	
	public function testPutObjectSignatureDoesNotMatch() {
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey2);
	    //$this->markTestIncomplete ( "putObject test not implemented" );
		$response = $this->RestStorage->putObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('403', $response->getResponseCode());
		$this->assertEquals('SignatureDoesNotMatch', $this->getErrorType($response->getBody()));
	}	
	
	public function testPutObjectPermissionDenied() {
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $this->value1, $this->accessId3, $this->secretKey3);
	    //$this->markTestIncomplete ( "putObject test not implemented" );
		$response = $this->RestStorage->putObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('403', $response->getResponseCode());
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));
	}		
	
	public function testPutObjectMissingContentLength() {
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, -1);
		$response = $this->RestStorage->putObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('411', $response->getResponseCode());
		$this->assertEquals('MissingContentLength', $this->getErrorType($response->getBody()));
	}
			
	public function testPutObjectIncompleteBody() {
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, 1000);
		$response = $this->RestStorage->putObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('400', $response->getResponseCode());
		$this->assertEquals('IncompleteBody', $this->getErrorType($response->getBody()));
	}
	
	public function testPutObjectUnresolvableGrantByEmailAddress() {
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $this->value1, 'resume@magudia.com', $this->secretKey1);
		$response = $this->RestStorage->putObject($this->bucket1, $this->key1, $message);		
	    $this->assertEquals('UnresolvableGrantByEmailAddress', $this->getErrorType($response->getBody()));
	    $this->assertEquals('400', $response->getResponseCode());
	}		

	public function testPutObjectEmail() {
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $this->value1, $this->email1, $this->secretKey1);
		$response = $this->RestStorage->putObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('200', $response->getResponseCode());
	}		
	
	public function testPutObjectInvalidAccessKeyId() {
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $this->value1, $this->blankAccessId, $this->secretKey1);
		$response = $this->RestStorage->putObject($this->bucket1, $this->key1, $message);
		$this->assertEquals('InvalidAccessKeyId', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());
	}		
	
	public function testPutObjectNoSuchBucket() {
	    $message = $this->createHTTPRequest('PUT', $this->blankBucket, $this->key1, $this->value1, $this->accessId1, $this->secretKey1);
		$response = $this->RestStorage->putObject($this->blankBucket, $this->key1, $message);
		$this->assertEquals('NoSuchBucket', $this->getErrorType($response->getBody()));		
		$this->assertEquals('404', $response->getResponseCode());
	}			

	private function _setBucketACL($acp) {
	    $value = $acp->generateXML();
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, $value, $this->accessId1, $this->secretKey1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'?'.'acl' );
	    $this->RestStorage->putACL($this->bucket1, false, $message);	
	}
	
	private function _setObjectACL($acp) {
	    $value = $acp->generateXML();
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $value, $this->accessId1, $this->secretKey1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'/'.$this->key1.'?acl' );
	    $this->RestStorage->putACL($this->bucket1, $this->key1, $message);	
	}		

}

?>