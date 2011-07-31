<?php
if (!DIR_SOURCE) {
    define( 'DIR_SOURCE', realpath( dirname( __FILE__ ) . '/../../src' ) );
}
require_once DIR_SOURCE.'/s3/lib/rest.php';
require_once dirname( __FILE__ ) . '/RESTAbstractTest.php';

/**
 * RestStorage test case.
 */
class RESTBucketTest extends RESTAbstractTest {
	
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
	
	
	
	
	public function testGet() {
	    $this->createBucket();
	    $message = $this->createHTTPRequest('GET', false, false, false, $this->accessId1, $this->secretKey1, -1);
	    $response = $this->RestStorage->get($message);
	    $this->assertEquals('200', $response->getResponseCode()); 
	    $buckets = $this->readListBucket($response->getBody());
	    $this->assertEquals($this->userid1, $buckets['id']);
	    $this->assertEquals($this->displayName1, $buckets['name']);
	    $this->assertEquals($this->bucket1, $buckets['bucket'][0]['name']);
	}

	public function testGetSignatureDoesNotMatch() {
	    $this->createBucket();    
	    $message = $this->createHTTPRequest('GET', false, false, false, $this->accessId1, $this->secretKey2, -1);
	    $response = $this->RestStorage->get($message);	
		$this->assertEquals('403', $response->getResponseCode());
		$this->assertEquals('SignatureDoesNotMatch', $this->getErrorType($response->getBody()));
	}		
	
	public function testGetUnresolvableGrantByEmailAddress() {
	    $this->createBucket();	    
	    $message = $this->createHTTPRequest('GET', false, false, false, 'resume@magudia.com', $this->secretKey1, -1);
		$response = $this->RestStorage->get($message);	
	    $this->assertEquals('UnresolvableGrantByEmailAddress', $this->getErrorType($response->getBody()));
	    $this->assertEquals('400', $response->getResponseCode());
	}			
	
	public function testGetInvalidAccessKeyId() {
	    $this->createBucket();    
	    $message = $this->createHTTPRequest('GET', false, false, false, $this->blankAccessId, $this->secretKey1, -1);
		$response = $this->RestStorage->get($message);
		$this->assertEquals('InvalidAccessKeyId', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());
	}		

	public function testGetUnexpectedContent() {
	    $this->createBucket();
	    $message = $this->createHTTPRequest('GET', false, false, $this->value1, $this->accessId1, $this->secretKey1, -1);
	    $response = $this->RestStorage->get($message);
		$this->assertEquals('UnexpectedContent', $this->getErrorType($response->getBody()));		
		$this->assertEquals('400', $response->getResponseCode());		    
	}		
	
	public function testGetBucketLocation() {
	    $this->createBucket();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket.'?'.'location' );
	    $response = $this->RestStorage->getBucketLocation($this->bucket1, $message);
	    $this->assertEquals('200', $response->getResponseCode()); 
	}		
	
	public function testGetBucketLocationNotOwner() {
	    $this->createBucket();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId2, $this->secretKey2, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket.'?'.'location' );
	    $response = $this->RestStorage->getBucketLocation($this->bucket1, $message); 
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());	    
	}	

	public function testGetBucketLocationAllUsers() {
	    // setup
	    $this->createBucket();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, false, false, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket.'?'.'location' );
	    $this->_setBucketACL($this->createACPAllUsers('FULL_CONTROL'));
	    
	    // test
	    $response = $this->RestStorage->getBucketLocation($this->bucket1, $message); 
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());	    
	}		

	public function testGetBucketLocationAuthenticatedUsers() {
	    // setup
	    $this->createBucket();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId2, $this->secretKey2, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket.'?'.'location' );
	    $this->_setBucketACL($this->createACPAuthenticatedUsers('FULL_CONTROL'));
	    
	    // test
	    $response = $this->RestStorage->getBucketLocation($this->bucket1, $message); 
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());	    
	}			
	
	public function testGetBucket() {
	    $this->createBucket();
	    $this->createObject();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
	    $this->assertEquals('200', $response->getResponseCode());  
	}	
	
	public function testGetBucketAllUsersFullControl() {
	    $this->createBucket();
	    $this->createObject();
	    $this->_setBucketACL($this->createACPAllUsers('FULL_CONTROL'));
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, false, false, -1);
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
	    $this->assertEquals('200', $response->getResponseCode());  
	}	

	public function testGetBucketAllUsersRead() {
	    $this->createBucket();
	    $this->createObject();
	    $this->_setBucketACL($this->createACPAllUsers('READ'));
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, false, false, -1);
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
	    $this->assertEquals('200', $response->getResponseCode());  
	}		
		
	public function testGetBucketAllUsersWrite() {
	    $this->createBucket();
	    $this->createObject();
	    $this->_setBucketACL($this->createACPAllUsers('WRITE'));
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, false, false, -1);
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());	 
	}	

	public function testGetBucketAuthenticatedUserFullControl() {
	    $this->createBucket();
	    $this->createObject();
	    $this->_setBucketACL($this->createACPAuthenticatedUsers('FULL_CONTROL'));
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId3, $this->secretKey3, -1);
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
	    $this->assertEquals('200', $response->getResponseCode());  
	}	

	public function testGetBucketAuthenticatedUserRead() {
	    $this->createBucket();
	    $this->createObject();
	    $this->_setBucketACL($this->createACPAuthenticatedUsers('READ'));
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId3, $this->secretKey3, -1);
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
	    $this->assertEquals('200', $response->getResponseCode());  
	}		
		
	public function testGetBucketAuthenticatedUserWrite() {
	    $this->createBucket();
	    $this->createObject();
	    $this->_setBucketACL($this->createACPAuthenticatedUsers('WRITE'));
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId3, $this->secretKey3, -1);
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());	 
	}		
	
	public function testGetBucketReadPermission() {
	    $this->createBucket();
	    $this->createObject();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId2, $this->secretKey2, -1);
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
	    $this->assertEquals('200', $response->getResponseCode());  
	}	
	
	public function testGetBucketNoSuchBucket() {	    
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
	    $this->assertEquals('NoSuchBucket', $this->getErrorType($response->getBody()));	    
	    $this->assertEquals('404', $response->getResponseCode());  
	}	
	
	public function testGetBucketSignatureDoesNotMatch() {
	    $this->createBucket();
	    $this->createObject();	    
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey2, -1);
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);	
		$this->assertEquals('403', $response->getResponseCode());
		$this->assertEquals('SignatureDoesNotMatch', $this->getErrorType($response->getBody()));
	}		
	
	public function testGetBucketUnresolvableGrantByEmailAddress() {
	    $this->createBucket();
	    $this->createObject();	    
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, 'resume@magudia.com', $this->secretKey1, -1);
		$response = $this->RestStorage->getBucket($this->bucket1, $message);	
	    $this->assertEquals('UnresolvableGrantByEmailAddress', $this->getErrorType($response->getBody()));
	    $this->assertEquals('400', $response->getResponseCode());
	}			
	
	public function testGetBucketInvalidAccessKeyId() {
	    $this->createBucket();
	    $this->createObject();	    
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->blankAccessId, $this->secretKey1, -1);
		$response = $this->RestStorage->getBucket($this->bucket1, $message);
		$this->assertEquals('InvalidAccessKeyId', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());
	}	

	public function testGetBucketInvalidBucketName() {
	    $this->createBucket();
	    $this->createObject();	    
	    $message = $this->createHTTPRequest('GET', $this->badBucket, false, false, $this->accessId1, $this->secretKey1, -1);
		$response = $this->RestStorage->getBucket($this->badBucket, $message);
		$this->assertEquals('400', $response->getResponseCode());
		$this->assertEquals('InvalidBucketName', $this->getErrorType($response->getBody()));	
	}	

	public function testGetBucketUnexpectedContent() {
	    $this->createBucket();
	    $this->createObject();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, $this->value1, $this->accessId1, $this->secretKey1, -1);
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
		$this->assertEquals('UnexpectedContent', $this->getErrorType($response->getBody()));		
		$this->assertEquals('400', $response->getResponseCode());		    
	}	
	
	public function testGetBucketAccessDeniedNoAccess() {
	    $this->createBucket();
	    $this->createObject();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId3, $this->secretKey3, -1);
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());	    
	}			

	public function testGetBucketMarker1() {
	    $this->createBucket();
	    $this->createObjectExtended();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $marker = "DE";
	    $nextMarker = "EA";
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket.'?'.'marker='.$marker );
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
	    $keys = $this->getListBucketContentKeys($response->getBody());
	    $this->assertEquals($nextMarker, $keys[0]);
	    $this->assertEquals(5, count($keys));  
	}
	
	public function testGetBucketMarkerMaxKeys() {
	    $this->createBucket();
	    $this->createObjectExtended();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $marker = "DE";
	    $nextMarker = "EA";
	    $maxKeys = 1;
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket.'?'.'marker='.$marker.'&max-keys='.$maxKeys );
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
	    $keys = $this->getListBucketContentKeys($response->getBody());
	    $this->assertEquals($nextMarker, $keys[0]);
	    $this->assertEquals(1, count($keys));  
	}	
	
	public function testGetBucketMarker2() {
	    $this->createBucket();
	    $this->createObjectExtended();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $marker = "ZA";
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket.'?'.'marker='.$marker );
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
	    $keys = $this->getListBucketContentKeys($response->getBody());
	    $this->assertEquals(0, count($keys));  
	}	
	
	public function testGetBucketMaxKeys1() {
	    $this->createBucket();
	    $this->createObjectExtended();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $maxkeys = 10;
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket.'?'.'max-keys='.$maxkeys );
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
	    $this->assertEquals($maxkeys, $this->countListBucketContents($response->getBody()));    
	}
	
	public function testGetBucketMaxKeys2() {
	    $this->createBucket();
	    $this->createObjectExtended();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $maxkeys = 1;
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket.'?'.'max-keys='.$maxkeys );
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
	    $this->assertEquals($maxkeys, $this->countListBucketContents($response->getBody()));    
	}	
	
	public function testGetBucketMaxKeysNotANumber() {
	    $this->createBucket();
	    $this->createObjectExtended();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $maxkeys = "NotANumber";
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket.'?'.'max-keys='.$maxkeys );
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
		$this->assertEquals('InvalidArgument', $this->getErrorType($response->getBody()));		
		$this->assertEquals('400', $response->getResponseCode());	    
	}		
	
	public function testGetBucketMaxNegativeNumber() {
	    $this->createBucket();
	    $this->createObjectExtended();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $maxkeys = -1;
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket.'?'.'max-keys='.$maxkeys );
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
		$this->assertEquals('InvalidArgument', $this->getErrorType($response->getBody()));		
		$this->assertEquals('400', $response->getResponseCode());	     
	}		
	
	public function testGetBucketPrefix1() {
	    $this->createBucket();
	    $this->createObjectExtended();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $prefix = 'B';
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket.'?'.'prefix='.$prefix );
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
	    $keys = $this->getListBucketContentKeys($response->getBody());
	    $this->assertEquals(5, count($keys));
	    foreach ($keys as $key) {
	        $this->assertEquals($prefix, substr($key, 0, 1));    
	    }
	}
	
	public function testGetBucketPrefix2() {
	    $this->createBucket();
	    $this->createObjectExtended();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $prefix = 'BA';
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket.'?'.'prefix='.$prefix );
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
	    $keys = $this->getListBucketContentKeys($response->getBody());
	    $this->assertEquals(1, count($keys));
	    foreach ($keys as $key) {
	        $this->assertEquals($prefix, substr($key, 0, 2));    
	    }
	}	
	
	public function testGetBucketPrefixNoContents() {
	    $this->createBucket();
	    $this->createObjectExtended();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $prefix = 'Z';
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket.'?'.'prefix='.$prefix );
	    $response = $this->RestStorage->getBucket($this->bucket1, $message);
	    $keys = $this->getListBucketContentKeys($response->getBody());
	    $this->assertEquals(0, count($keys));
	}	
	
	
	public function testPutBucket() {
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, false, $this->accessId1, $this->secretKey1);
	    $response = $this->RestStorage->putBucket($this->bucket1, $message);
	    $this->assertEquals('200', $response->getResponseCode());
	}

	public function testPutBucketMissingSecurityHeader() {
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, false, false, false);
	    $response = $this->RestStorage->putBucket($this->bucket1, $message);
	    $this->assertEquals('MissingSecurityHeader', $this->getErrorType($response->getBody()));
	    $this->assertEquals('400', $response->getResponseCode());
	}	
	
	public function testPutBucketAlreadyOwnedByYou() {
	    $this->createBucket();
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, false, $this->accessId1, $this->secretKey1);
	    $response = $this->RestStorage->putBucket($this->bucket1, $message);	    
	    $this->assertEquals('BucketAlreadyOwnedByYou', $this->getErrorType($response->getBody()));
	    $this->assertEquals('409', $response->getResponseCode());	    
	}

	public function testPutBucketAlreadyExists() {
	    $this->createBucket();
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, false, $this->accessId2, $this->secretKey2);
	    $response = $this->RestStorage->putBucket($this->bucket1, $message);	
	    $this->assertEquals('BucketAlreadyExists', $this->getErrorType($response->getBody()));
	    $this->assertEquals('409', $response->getResponseCode());	        
	}	
	
	public function testPutBucketSignatureDoesNotMatch() {
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, false, $this->accessId1, $this->secretKey2);
	    $response = $this->RestStorage->putBucket($this->bucket1, $message);	
		$this->assertEquals('403', $response->getResponseCode());
		$this->assertEquals('SignatureDoesNotMatch', $this->getErrorType($response->getBody()));
	}				
			
	public function testPutBucketIncompleteBody() {
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, 1000);
		$response = $this->RestStorage->putBucket($this->bucket1, $message);
		$this->assertEquals('400', $response->getResponseCode());
		$this->assertEquals('IncompleteBody', $this->getErrorType($response->getBody()));	
	}
	
	public function testPutBucketUnresolvableGrantByEmailAddress() {
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, false, 'resume@magudia.com', $this->secretKey1);
		$response = $this->RestStorage->putBucket($this->bucket1, $message);	
	    $this->assertEquals('UnresolvableGrantByEmailAddress', $this->getErrorType($response->getBody()));
	    $this->assertEquals('400', $response->getResponseCode());
	}			
	
	public function testPutBucketInvalidAccessKeyId() {
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, false, $this->blankAccessId, $this->secretKey1);
		$response = $this->RestStorage->putBucket($this->bucket1, $message);
		$this->assertEquals('InvalidAccessKeyId', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());
	}	

	public function testPutBucketInvalidBucketName() {
	    $message = $this->createHTTPRequest('PUT', $this->badBucket, false, false, $this->accessId1, $this->secretKey1);
		$response = $this->RestStorage->putBucket($this->badBucket, $message);
		$this->assertEquals('400', $response->getResponseCode());
		$this->assertEquals('InvalidBucketName', $this->getErrorType($response->getBody()));	
	}		
	
	public function testPutBucketMalformedXML() {
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, $this->malformedXML, $this->accessId1, $this->secretKey1);
		$response = $this->RestStorage->putBucket($this->bucket1, $message);
		$this->assertEquals('MalformedXML', $this->getErrorType($response->getBody()));		
		$this->assertEquals('400', $response->getResponseCode());
	}		

	public function testPutBucketLocation() {
	    //$this->markTestIncomplete ( "location has not implemented" );
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, $this->locationXML1, $this->accessId1, $this->secretKey1);
		$response = $this->RestStorage->putBucket($this->bucket1, $message);	
		$this->assertEquals('200', $response->getResponseCode());
	}	

	public function testPutBucketInvalidLocationConstraint() {
	    //$this->markTestIncomplete ( "location has not implemented" );
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, $this->badLocationXML, $this->accessId1, $this->secretKey1);
		$response = $this->RestStorage->putBucket($this->bucket1, $message);	
		$this->assertEquals('InvalidLocationConstraint', $this->getErrorType($response->getBody()));	
		$this->assertEquals('400', $response->getResponseCode());
	}		
	
	public function testPutBucketEmptyXML() {
	    //$this->markTestIncomplete ( "location has not implemented" );
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, $this->invalidLocationXML, $this->accessId1, $this->secretKey1);
		$response = $this->RestStorage->putBucket($this->bucket1, $message);
		//var_dump($response);
		$this->assertEquals('MalformedXML', $this->getErrorType($response->getBody()));		
		$this->assertEquals('400', $response->getResponseCode());
	}		

	
	public function testDeleteBucket() {
	    $this->createBucket();
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, false, false, $this->accessId1, $this->secretKey1);
	    $response = $this->RestStorage->deleteBucket($this->bucket1, $message);
	    $this->assertEquals('204', $response->getResponseCode());	    
	}
	
	public function testDeleteBucketMissingSecurityHeader() {
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, false, false, false, false);
	    $response = $this->RestStorage->deleteBucket($this->bucket1, $message);
	    $this->assertEquals('MissingSecurityHeader', $this->getErrorType($response->getBody()));
	    $this->assertEquals('400', $response->getResponseCode());
	}		

	public function testDeleteBucketUnexpectedContent() {
	    $this->createBucket();
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, false, $this->value1, $this->accessId1, $this->secretKey1);
	    $response = $this->RestStorage->deleteBucket($this->bucket1, $message);
		$this->assertEquals('UnexpectedContent', $this->getErrorType($response->getBody()));		
		$this->assertEquals('400', $response->getResponseCode());		    
	}	
	
	public function testDeleteBucketAccessDeniedNotOwner() {
	    $this->createBucket();
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, false, false, $this->accessId2, $this->secretKey2);
	    $response = $this->RestStorage->deleteBucket($this->bucket1, $message);
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());	    
	}		

	public function testDeleteBucketNoSuchBucket() {
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, false, false, $this->accessId1, $this->secretKey1);
	    $response = $this->RestStorage->deleteBucket($this->bucket1, $message);
		$this->assertEquals('NoSuchBucket', $this->getErrorType($response->getBody()));		
		$this->assertEquals('404', $response->getResponseCode());	    
	}	
	
	public function testDeleteBucketNotEmpty() {
	    $this->createBucket();
	    $this->createObject();
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, false, false, $this->accessId1, $this->secretKey1);
	    $response = $this->RestStorage->deleteBucket($this->bucket1, $message);
		$this->assertEquals('BucketNotEmpty', $this->getErrorType($response->getBody()));		
		$this->assertEquals('409', $response->getResponseCode());	    
	}		

	public function testDeleteBucketInvalidBucketName() {
	    $this->createBucket();
	    $message = $this->createHTTPRequest('DELETE', $this->badBucket, false, false, $this->accessId1, $this->secretKey1);
		$response = $this->RestStorage->deleteBucket($this->badBucket, $message);
		$this->assertEquals('400', $response->getResponseCode());
		$this->assertEquals('InvalidBucketName', $this->getErrorType($response->getBody()));	
	}		
	
	public function testDeleteBucketSignatureDoesNotMatch() {
	    $this->createBucket();
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, false, false, $this->accessId1, $this->secretKey2);
	    $response = $this->RestStorage->deleteBucket($this->bucket1, $message);	
		$this->assertEquals('403', $response->getResponseCode());
		$this->assertEquals('SignatureDoesNotMatch', $this->getErrorType($response->getBody()));
	}				

	public function testDeleteBucketUnresolvableGrantByEmailAddress() {
	    $this->createBucket();
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, false, false, 'resume@magudia.com', $this->secretKey1);
		$response = $this->RestStorage->deleteBucket($this->bucket1, $message);	
	    $this->assertEquals('UnresolvableGrantByEmailAddress', $this->getErrorType($response->getBody()));
	    $this->assertEquals('400', $response->getResponseCode());
	}			
	
	public function testDeleteBucketInvalidAccessKeyId() {
	    $this->createBucket();
	    $message = $this->createHTTPRequest('DELETE', $this->bucket1, false, false, $this->blankAccessId, $this->secretKey1);
		$response = $this->RestStorage->deleteBucket($this->bucket1, $message);
		$this->assertEquals('InvalidAccessKeyId', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());
	}	

	private function _setBucketACL($acp) {
	    $value = $acp->generateXML();
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, $value, $this->accessId1, $this->secretKey1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'?'.'acl' );
	    $this->RestStorage->putACL($this->bucket1, false, $message);	
	}
		
}
?>