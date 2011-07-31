<?php
if (!DIR_SOURCE) {
    define( 'DIR_SOURCE', realpath( dirname( __FILE__ ) . '/../../src' ) );
}
require_once DIR_SOURCE.'/s3/lib/rest.php';
require_once DIR_SOURCE.'/s3/lib/accessControlPolicy.php';
require_once dirname( __FILE__ ) . '/RESTAbstractTest.php';

/**
 * RestStorage test case.
 */
class RESTAccessControlPolicy extends RESTAbstractTest {
	
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
	
	public function testPutBucketAccessControlPolicy() {
	    $this->createBucket();
	    $this->createObject();
	    $acp = $this->createACP();
	    $value = $acp->generateXML();
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, $value, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'?'.'acl' );
	    $response = $this->RestStorage->putACL($this->bucket1, false, $message);	
	    $this->assertEquals('200', $response->getResponseCode());       	    
	}
    
	public function testPutBucketAccessControlPolicyNoSuchBucket() {	 
	    $acp = $this->createACP();
	    $value = $acp->generateXML();	       
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, $value, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'?'.'acl' );
	    $response = $this->RestStorage->putACL($this->bucket1, false, $message);
	    $this->assertEquals('NoSuchBucket', $this->getErrorType($response->getBody()));	    
	    $this->assertEquals('404', $response->getResponseCode());  
	}	
	
	public function testPutBucketAccessControlPolicySignatureDoesNotMatch() {
	    $this->createBucket();
	    $this->createObject();
	    $acp = $this->createACP();
	    $value = $acp->generateXML();	    
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, $value, $this->accessId1, $this->secretKey2, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'?'.'acl' );
	    $response = $this->RestStorage->putACL($this->bucket1, false, $message);	
		$this->assertEquals('SignatureDoesNotMatch', $this->getErrorType($response->getBody()));
		$this->assertEquals('403', $response->getResponseCode());
	}		
	
	public function testPutBucketAccessControlPolicyUnresolvableGrantByEmailAddress() {
	    $this->createBucket();
	    $this->createObject();
	    $acp = $this->createACP();
	    $value = $acp->generateXML();    
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, $value, 'resume@magudia.com', $this->secretKey1, -1);
		$message->setRequestUrl  ( '/workspace/'.$this->bucket1.'?'.'acl' );
	    $response = $this->RestStorage->putACL($this->bucket1, false, $message);	
	    $this->assertEquals('UnresolvableGrantByEmailAddress', $this->getErrorType($response->getBody()));
	    $this->assertEquals('400', $response->getResponseCode());
	}			
	
	public function testPutBucketAccessControlPolicyInvalidAccessKeyId() {
	    $this->createBucket();
	    $this->createObject();
	    $acp = $this->createACP();
	    $value = $acp->generateXML();	    
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, $value, $this->blankAccessId, $this->secretKey1, -1);
		$message->setRequestUrl  ( '/workspace/'.$this->bucket1.'?'.'acl' );
	    $response = $this->RestStorage->putACL($this->bucket1, false, $message);
		$this->assertEquals('InvalidAccessKeyId', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());
	}	

	public function testPutBucketAccessControlPolicyInvalidBucketName() {
	    $this->createBucket();
	    $this->createObject();
	    $acp = $this->createACP();
	    $value = $acp->generateXML();    
	    $message = $this->createHTTPRequest('PUT', $this->badBucket, false, $value, $this->accessId1, $this->secretKey1, -1);
		$message->setRequestUrl  ( '/workspace/'.$this->badBucket.'?'.'acl' );
	    $response = $this->RestStorage->putACL($this->badBucket, false, $message);
		$this->assertEquals('InvalidBucketName', $this->getErrorType($response->getBody()));
		$this->assertEquals('400', $response->getResponseCode());	
	}	
	
	public function testPutBucketAccessControlPolicyMalformedACLError() {
	    $this->createBucket();
	    $this->createObject();
	    $value = "<badXML><badXML>";
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, $value, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'?'.'acl' );
	    $response = $this->RestStorage->putACL($this->bucket1, false, $message);
		$this->assertEquals('MalformedACLError', $this->getErrorType($response->getBody()));		
		$this->assertEquals('400', $response->getResponseCode());		    
	}		
	
	public function testPutBucketAccessControlPolicyAccessDenied() {
	    $this->createBucket();
	    $this->createObject();
	    $acp = $this->createACP();
	    $value = $acp->generateXML();
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, false, $value, $this->accessId3, $this->secretKey3, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'?'.'acl' );
	    $response = $this->RestStorage->putACL($this->bucket1, false, $message);
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());	    
	}    
    	
	
	public function testGetBucketAccessControlPolicy() {
	    $this->createBucket();
	    $this->createObject();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'?'.'acl' );
	    $response = $this->RestStorage->getACL($this->bucket1, false, $message);
	    $this->assertEquals('200', $response->getResponseCode()); 
        $body = $response->getBody();
        $acp = new AccessControlPolicy();
        $acp->processXML($body);
        //var_dump($acp);
	}	
	
	public function testGetBucketAccessControlPolicyNoSuchBucket() {	    
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'?'.'acl' );
	    $response = $this->RestStorage->getACL($this->bucket1, false, $message);
	    $this->assertEquals('NoSuchBucket', $this->getErrorType($response->getBody()));	    
	    $this->assertEquals('404', $response->getResponseCode());  
	}	
	
	public function testGetBucketAccessControlPolicySignatureDoesNotMatch() {
	    $this->createBucket();
	    $this->createObject();	    
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId1, $this->secretKey2, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'?'.'acl' );
	    $response = $this->RestStorage->getACL($this->bucket1, false, $message);	
		$this->assertEquals('SignatureDoesNotMatch', $this->getErrorType($response->getBody()));
		$this->assertEquals('403', $response->getResponseCode());
	}		
	
	public function testGetBucketAccessControlPolicyUnresolvableGrantByEmailAddress() {
	    $this->createBucket();
	    $this->createObject();	    
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, 'resume@magudia.com', $this->secretKey1, -1);
		$message->setRequestUrl  ( '/workspace/'.$this->bucket1.'?'.'acl' );
	    $response = $this->RestStorage->getACL($this->bucket1, false, $message);	
	    $this->assertEquals('UnresolvableGrantByEmailAddress', $this->getErrorType($response->getBody()));
	    $this->assertEquals('400', $response->getResponseCode());
	}			
	
	public function testGetBucketAccessControlPolicyInvalidAccessKeyId() {
	    $this->createBucket();
	    $this->createObject();	    
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->blankAccessId, $this->secretKey1, -1);
		$message->setRequestUrl  ( '/workspace/'.$this->bucket1.'?'.'acl' );
	    $response = $this->RestStorage->getACL($this->bucket1, false, $message);
		$this->assertEquals('InvalidAccessKeyId', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());
	}	

	public function testGetBucketAccessControlPolicyInvalidBucketName() {
	    $this->createBucket();
	    $this->createObject();	    
	    $message = $this->createHTTPRequest('GET', $this->badBucket, false, false, $this->accessId1, $this->secretKey1, -1);
		$message->setRequestUrl  ( '/workspace/'.$this->badBucket.'?'.'acl' );
	    $response = $this->RestStorage->getACL($this->badBucket, false, $message);
		$this->assertEquals('InvalidBucketName', $this->getErrorType($response->getBody()));
		$this->assertEquals('400', $response->getResponseCode());	
	}	

	public function testGetBucketAccessControlPolicyUnexpectedContent() {
	    $this->createBucket();
	    $this->createObject();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, $this->value1, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'?'.'acl' );
	    $response = $this->RestStorage->getACL($this->bucket1, false, $message);
		$this->assertEquals('UnexpectedContent', $this->getErrorType($response->getBody()));		
		$this->assertEquals('400', $response->getResponseCode());		    
	}	
	
	public function testGetBucketAccessControlPolicyAccessDenied() {
	    $this->createBucket();
	    $this->createObject();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, false, false, $this->accessId3, $this->secretKey3, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'?'.'acl' );
	    $response = $this->RestStorage->getACL($this->bucket1, false, $message);
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());	    
	}    
    

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	public function testPutObjectAccessControlPolicy() {
	    $this->createBucket();
	    $this->createObject();
	    $acp = $this->createACP();
	    $value = $acp->generateXML();
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $value, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'/'.$this->key1.'?'.'acl' );
	    $response = $this->RestStorage->putACL($this->bucket1, $this->key1, $message);	
	    $this->assertEquals('200', $response->getResponseCode());       	    
	}
    
	public function testPutObjectAccessControlPolicyNoSuchBucket() {	 
	    $acp = $this->createACP();
	    $value = $acp->generateXML();	       
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $value, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'/'.$this->key1.'?'.'acl' );
	    $response = $this->RestStorage->putACL($this->bucket1, $this->key1, $message);
	    $this->assertEquals('NoSuchBucket', $this->getErrorType($response->getBody()));	    
	    $this->assertEquals('404', $response->getResponseCode());  
	}	
	
	public function testPutObjectAccessControlPolicySignatureDoesNotMatch() {
	    $this->createBucket();
	    $this->createObject();
	    $acp = $this->createACP();
	    $value = $acp->generateXML();	    
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $value, $this->accessId1, $this->secretKey2, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'/'.$this->key1.'?'.'acl' );
	    $response = $this->RestStorage->putACL($this->bucket1, $this->key1, $message);	
		$this->assertEquals('SignatureDoesNotMatch', $this->getErrorType($response->getBody()));
		$this->assertEquals('403', $response->getResponseCode());
	}		
	
	public function testPutObjectAccessControlPolicyUnresolvableGrantByEmailAddress() {
	    $this->createBucket();
	    $this->createObject();
	    $acp = $this->createACP();
	    $value = $acp->generateXML();    
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $value, 'resume@magudia.com', $this->secretKey1, -1);
		$message->setRequestUrl  ( '/workspace/'.$this->bucket1.'/'.$this->key1.'?'.'acl' );
	    $response = $this->RestStorage->putACL($this->bucket1, $this->key1, $message);	
	    $this->assertEquals('UnresolvableGrantByEmailAddress', $this->getErrorType($response->getBody()));
	    $this->assertEquals('400', $response->getResponseCode());
	}			
	
	public function testPutObjectAccessControlPolicyInvalidAccessKeyId() {
	    $this->createBucket();
	    $this->createObject();
	    $acp = $this->createACP();
	    $value = $acp->generateXML();	    
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $value, $this->blankAccessId, $this->secretKey1, -1);
		$message->setRequestUrl  ( '/workspace/'.$this->bucket1.'/'.$this->key1.'?'.'acl' );
	    $response = $this->RestStorage->putACL($this->bucket1, $this->key1, $message);
		$this->assertEquals('InvalidAccessKeyId', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());
	}	

	public function testPutObjectAccessControlPolicyInvalidBucketName() {
	    $this->createBucket();
	    $this->createObject();
	    $acp = $this->createACP();
	    $value = $acp->generateXML();    
	    $message = $this->createHTTPRequest('PUT', $this->badBucket, $this->key1, $value, $this->accessId1, $this->secretKey1, -1);
		$message->setRequestUrl  ( '/workspace/'.$this->badBucket.'/'.$this->key1.'?'.'acl' );
	    $response = $this->RestStorage->putACL($this->badBucket, $this->key1, $message);
		$this->assertEquals('InvalidBucketName', $this->getErrorType($response->getBody()));
		$this->assertEquals('400', $response->getResponseCode());	
	}	
	
	public function testPutObjectAccessControlPolicyMalformedACLError() {
	    $this->createBucket();
	    $this->createObject();
	    $value = "<badXML><badXML>";
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $value, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'/'.$this->key1.'?'.'acl' );
	    $response = $this->RestStorage->putACL($this->bucket1, $this->key1, $message);
		$this->assertEquals('MalformedACLError', $this->getErrorType($response->getBody()));		
		$this->assertEquals('400', $response->getResponseCode());		    
	}		
	
	public function testPutObjectAccessControlPolicyAccessDenied() {
	    $this->createBucket();
	    $this->createObject();
	    $acp = $this->createACP();
	    $value = $acp->generateXML();
	    $message = $this->createHTTPRequest('PUT', $this->bucket1, $this->key1, $value, $this->accessId3, $this->secretKey3, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'/'.$this->key1.'?'.'acl' );
	    $response = $this->RestStorage->putACL($this->bucket1, $this->key1, $message);
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());	    
	}    
    	
	
	public function testGetObjectAccessControlPolicy() {
	    $this->createBucket();
	    $this->createObject();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, false, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'/'.$this->key1.'?'.'acl' );
	    $response = $this->RestStorage->getACL($this->bucket1, $this->key1, $message);
	    $this->assertEquals('200', $response->getResponseCode()); 
        $body = $response->getBody();
        $acp = new AccessControlPolicy();
        $acp->processXML($body);
        //var_dump($acp);
	}	
	
	public function testGetObjectAccessControlPolicyNoSuchBucket() {	    
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, false, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'/'.$this->key1.'?'.'acl' );
	    $response = $this->RestStorage->getACL($this->bucket1, $this->key1, $message);
	    $this->assertEquals('NoSuchBucket', $this->getErrorType($response->getBody()));	    
	    $this->assertEquals('404', $response->getResponseCode());  
	}	
	
	public function testGetObjectAccessControlPolicySignatureDoesNotMatch() {
	    $this->createBucket();
	    $this->createObject();	    
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, false, $this->accessId1, $this->secretKey2, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'/'.$this->key1.'?'.'acl' );
	    $response = $this->RestStorage->getACL($this->bucket1, $this->key1, $message);	
		$this->assertEquals('SignatureDoesNotMatch', $this->getErrorType($response->getBody()));
		$this->assertEquals('403', $response->getResponseCode());
	}		
	
	public function testGetObjectAccessControlPolicyUnresolvableGrantByEmailAddress() {
	    $this->createBucket();
	    $this->createObject();	    
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, false, 'resume@magudia.com', $this->secretKey1, -1);
		$message->setRequestUrl  ( '/workspace/'.$this->bucket1.'/'.$this->key1.'?'.'acl' );
	    $response = $this->RestStorage->getACL($this->bucket1, $this->key1, $message);	
	    $this->assertEquals('UnresolvableGrantByEmailAddress', $this->getErrorType($response->getBody()));
	    $this->assertEquals('400', $response->getResponseCode());
	}			
	
	public function testGetObjectAccessControlPolicyInvalidAccessKeyId() {
	    $this->createBucket();
	    $this->createObject();	    
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, false, $this->blankAccessId, $this->secretKey1, -1);
		$message->setRequestUrl  ( '/workspace/'.$this->bucket1.'/'.$this->key1.'?'.'acl' );
	    $response = $this->RestStorage->getACL($this->bucket1, $this->key1, $message);
		$this->assertEquals('InvalidAccessKeyId', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());
	}	

	public function testGetObjectAccessControlPolicyInvalidBucketName() {
	    $this->createBucket();
	    $this->createObject();	    
	    $message = $this->createHTTPRequest('GET', $this->badBucket, $this->key1, false, $this->accessId1, $this->secretKey1, -1);
		$message->setRequestUrl  ( '/workspace/'.$this->badBucket.'/'.$this->key1.'?'.'acl' );
	    $response = $this->RestStorage->getACL($this->badBucket, $this->key1, $message);
		$this->assertEquals('InvalidBucketName', $this->getErrorType($response->getBody()));
		$this->assertEquals('400', $response->getResponseCode());	
	}	

	public function testGetObjectAccessControlPolicyUnexpectedContent() {
	    $this->createBucket();
	    $this->createObject();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, $this->value1, $this->accessId1, $this->secretKey1, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'/'.$this->key1.'?'.'acl' );
	    $response = $this->RestStorage->getACL($this->bucket1, $this->key1, $message);
		$this->assertEquals('UnexpectedContent', $this->getErrorType($response->getBody()));		
		$this->assertEquals('400', $response->getResponseCode());		    
	}	
	
	public function testGetObjectAccessControlPolicyAccessDenied() {
	    $this->createBucket();
	    $this->createObject();
	    $message = $this->createHTTPRequest('GET', $this->bucket1, $this->key1, false, $this->accessId3, $this->secretKey3, -1);
	    $message->setRequestUrl  ( '/workspace/'.$this->bucket1.'/'.$this->key1.'?'.'acl' );
	    $response = $this->RestStorage->getACL($this->bucket1, $this->key1, $message);
		$this->assertEquals('AccessDenied', $this->getErrorType($response->getBody()));		
		$this->assertEquals('403', $response->getResponseCode());	    
	} 	
	
	
	
}
?>