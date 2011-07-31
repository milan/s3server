<?php
if (!DIR_SOURCE) {
    define( 'DIR_SOURCE', realpath( dirname( __FILE__ ) . '/../../src' ) );
}
require_once dirname ( __FILE__ ) . '/../AbstractTest.php';
require_once DIR_SOURCE . '/s3/lib/storage.php';

/**
 *  test case.
 */
class Storage_ObjectTest extends SimpleStorageServiceAbstractTest {
	
	private $storage = null;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp ();
		$this->storage = new Storage ( );
		$this->connectDB ();
		$this->cleanDB ();
		$this->createUser ();
		$this->createBucket ();
		$this->storage->connect ( $this->dbh );
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		// TODO Auto-generated Test::tearDown()
		$this->dbh = null;
		$this->storage = null;
		parent::tearDown ();
	}
	
	/**
	 * Constructs the test case.
	 */
	public function __construct() {
		// TODO Auto-generated constructor
	}
	
	public function testGetObject() {
		$this->createObject ();
		$result = $this->storage->getObject ( $this->bucket1, $this->key1, $this->accessId1, $this->signature1, $this->stringToSign1 );
		$this->assertEquals ( $this->value1, $result ['value'] );
	}
	
	public function testGetObjectAllUsersFullControl() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAllUsers('FULL_CONTROL');
		$this->storage->setObjectAccessControlPolicy($this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );
	    
		// test
		$result = $this->storage->getObject ( $this->bucket1, $this->key1, false, false, false);
		$this->assertEquals ( $this->value1, $result ['value'] );
	}
	
	public function testGetObjectAllUsersRead() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAllUsers('READ');
		$this->storage->setObjectAccessControlPolicy($this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );
	    
		// test
		$result = $this->storage->getObject ( $this->bucket1, $this->key1, false, false, false);
		$this->assertEquals ( $this->value1, $result ['value'] );
	}

	public function testGetObjectAllUsersWriteACP() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAllUsers('WRITE_ACP');
		$this->storage->setObjectAccessControlPolicy($this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );
	    
		// test
	    $this->setExpectedException('AccessDeniedException');
		$this->storage->getObject ( $this->bucket1, $this->key1, false, false, false);
	}

	public function testGetObjectAuthenticatedUserFullControl() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAuthenticatedUsers('FULL_CONTROL');
		$this->storage->setObjectAccessControlPolicy($this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );
	    
		// test
		$result = $this->storage->getObject ( $this->bucket1, $this->key1, $this->accessId3, $this->signature3, $this->stringToSign3 );
		$this->assertEquals ( $this->value1, $result ['value'] );
	}

	public function testGetObjectAuthenticatedUserRead() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAuthenticatedUsers('READ');
		$this->storage->setObjectAccessControlPolicy($this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1,  $this->stringToSign1 );
	    
		// test
		$result = $this->storage->getObject ( $this->bucket1, $this->key1, $this->accessId3, $this->signature3, $this->stringToSign3 );
		$this->assertEquals ( $this->value1, $result ['value'] );
	}

	public function testGetObjectAuthenticatedUserWriteACP() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAuthenticatedUsers('WRITE_ACP');
		$this->storage->setObjectAccessControlPolicy($this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );
	    
		// test
		$this->setExpectedException('AccessDeniedException');
		$this->storage->getObject ( $this->bucket1, $this->key1, $this->accessId3, $this->signature3, $this->stringToSign3 );
	}	
	
	public function testGetObjectDoesNotExist() {
		$this->createObject ();
		$this->setExpectedException('NoSuchKeyException');
		$this->storage->getObject ( $this->bucket1, $this->blankKey, $this->accessId1, $this->signature1, $this->stringToSign1 );
	}
	
	public function testGetObjectInvalidUser() {
		$this->createObject ();
		$this->setExpectedException('InvalidAccessKeyIdException');
		$this->storage->getObject ( $this->bucket1, $this->key1, $this->badAccessId, $this->signature1, $this->stringToSign1 );
	}
	
	public function testGetObjectPermissionDenied() {
		$this->createObject ();
		$this->setExpectedException('AccessDeniedException');
		$this->storage->getObject ( $this->bucket1, $this->key1, $this->accessId3, $this->signature3, $this->stringToSign3 );
	}
	
	public function testgetObjectExtended() {
		$this->createObject ();
		$result = $this->storage->getObjectExtended ( $this->bucket1, $this->key1, true, false, false, false, false, false, false, $this->accessId1, $this->signature1, $this->stringToSign1 );
		$this->assertEquals ( $this->value1, $result ['value'] );
	}
	
	public function testgetObjectExtendedRange1() {
		$this->createObject ();
		$ifModifiedSince   = false;
		$ifMatch           = false;
		$ifUnmodifiedSince = false;
		$ifNoneMatch       = false;
		$returnValue       = true;
		$rangeStart        = 0;
		$rangeEnd          = 4;
		$result = $this->storage->getObjectExtended ( $this->bucket1, $this->key1, $returnValue, $rangeStart, $rangeEnd, $ifModifiedSince, $ifUnmodifiedSince, $ifMatch, $ifNoneMatch, $this->accessId1, $this->signature1, $this->stringToSign1 );
		$this->assertEquals ( substr($this->value1,0, 4), $result ['value'] );
	}
	
	public function testgetObjectExtendedRange2() {
		$this->createObject ();
		$ifModifiedSince   = false;
		$ifMatch           = false;
		$ifUnmodifiedSince = false;
		$ifNoneMatch       = false;
		$returnValue       = true;
		$rangeStart        = 5;
		$rangeEnd          = 8;
		$result = $this->storage->getObjectExtended ( $this->bucket1, $this->key1, $returnValue, $rangeStart, $rangeEnd, $ifModifiedSince, $ifUnmodifiedSince, $ifMatch, $ifNoneMatch, $this->accessId1, $this->signature1, $this->stringToSign1 );
		$this->assertEquals ( substr($this->value1, $rangeStart, $rangeEnd), $result ['value'] );
	}
	
	public function testgetObjectExtendedifMatch() {
		$this->createObject ();
		$ifModifiedSince   = false;
		$ifMatch           = md5($this->value1);
		$ifUnmodifiedSince = false;
		$ifNoneMatch       = false;
		$rangeStart        = false;
		$rangeEnd          = false;
		$returnValue       = true;
		$result = $this->storage->getObjectExtended ( $this->bucket1, $this->key1, $returnValue, $rangeStart, $rangeEnd, $ifModifiedSince, $ifUnmodifiedSince, $ifMatch, $ifNoneMatch, $this->accessId1, $this->signature1, $this->stringToSign1 );
		$this->assertEquals ( $this->value1, $result ['value'] );
	}	
	
	public function testgetObjectExtendedifNotMatch() {
		$this->createObject ();
		$ifModifiedSince   = false;
		$ifMatch           = false;
		$ifUnmodifiedSince = false;
		$ifNoneMatch       = md5($this->value2);
		$rangeStart        = false;
		$rangeEnd          = false;
		$returnValue       = true;
		$result = $this->storage->getObjectExtended ( $this->bucket1, $this->key1, $returnValue, $rangeStart, $rangeEnd, $ifModifiedSince, $ifUnmodifiedSince, $ifMatch, $ifNoneMatch, $this->accessId1, $this->signature1, $this->stringToSign1 );
		$this->assertEquals ( $this->value1, $result ['value'] );
	}	

	public function testgetObjectExtendedifUnmodifiedSince1() {
		$this->createObject ();
		$ifModifiedSince   = false;
		$ifMatch           = false;
		$ifUnmodifiedSince = date('Y-m-d H:i:s', time());
		$ifNoneMatch       = false;
		$rangeStart        = false;
		$rangeEnd          = false;
		$returnValue       = true;
		$result = $this->storage->getObjectExtended ( $this->bucket1, $this->key1, $returnValue, $rangeStart, $rangeEnd, $ifModifiedSince, $ifUnmodifiedSince, $ifMatch, $ifNoneMatch, $this->accessId1, $this->signature1, $this->stringToSign1 );
		$this->assertEquals ( $this->value1, $result ['value'] );
	}		

	public function testgetObjectExtendedifUnmodifiedSince2() {
		$this->createObject ();
		$ifModifiedSince   = false;
		$ifMatch           = false;
		$ifUnmodifiedSince = date('Y-m-d H:i:s', time(strtotime($this->lastUpdated1)));
		$ifNoneMatch       = false;
		$rangeStart        = false;
		$rangeEnd          = false;
		$returnValue       = true;
		$result = $this->storage->getObjectExtended ( $this->bucket1, $this->key1, $returnValue, $rangeStart, $rangeEnd, $ifModifiedSince, $ifUnmodifiedSince, $ifMatch, $ifNoneMatch, $this->accessId1, $this->signature1, $this->stringToSign1 );
		$this->assertEquals ( $this->value1, $result ['value'] );
	}			
	
	public function testgetObjectExtendedifModifiedSince() {
		$this->createObject ();
		$ifModifiedSince   = "1977-01-01 12:00:00";
		$ifMatch           = false;
		$ifUnmodifiedSince = false;
		$ifNoneMatch       = false;
		$returnValue       = true;
		$rangeStart        = false;
		$rangeEnd          = false;
		$result = $this->storage->getObjectExtended ( $this->bucket1, $this->key1, $returnValue, $rangeStart, $rangeEnd, $ifModifiedSince, $ifUnmodifiedSince, $ifMatch, $ifNoneMatch, $this->accessId1, $this->signature1, $this->stringToSign1 );
		$this->assertEquals ( $this->value1, $result ['value'] );
	}			
	
	public function testgetObjectExtendedDoNotReturnObject() {
		$this->createObject ();
		$returnObjectContents = false;
		$result = $this->storage->getObjectExtended ( $this->bucket1, $this->key1, $returnObjectContents, false, false, false, false, false, false, $this->accessId1, $this->signature1, $this->stringToSign1 );
		$this->assertEquals ( strlen($this->value1), $result['length']);
		$this->assertTrue( empty($result['value']), "Value contains: $this->value1");
	}
	
	
	public function testDeleteObject() {
		$this->createObject ();
		$this->storage->deleteObject ( $this->bucket1, $this->key1, $this->accessId1, $this->signature1, $this->stringToSign1 );
	}
	
	
	public function testDeleteObjectAuthenticatedUserFullControl() {
	    // setup
		$this->createObject ();
		$acp = $this->createACPAuthenticatedUsers('FULL_CONTROL');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
		
		// test
		$this->storage->deleteObject ( $this->bucket1, $this->key1, $this->accessId3, $this->signature3, $this->stringToSign3 );
	}
	
	public function testDeleteObjectAuthenticatedUserWrite() {
	    // setup
		$this->createObject ();
		$acp = $this->createACPAuthenticatedUsers('WRITE');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
		
		// test
		$this->storage->deleteObject ( $this->bucket1, $this->key1, $this->accessId3, $this->signature3, $this->stringToSign3 );
	}

	public function testDeleteObjectAuthenticatedUserRead() {
	    // setup
		$this->createObject ();
		$acp = $this->createACPAuthenticatedUsers('READ');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
		
		// test
		$this->setExpectedException('AccessDeniedException');
		$this->storage->deleteObject ( $this->bucket1, $this->key1, $this->accessId3, $this->signature3, $this->stringToSign3 );
	}	

	public function testDeleteObjectAllUserFullControl() {
	    // setup
		$this->createObject ();
		$acp = $this->createACPAllUsers('FULL_CONTROL');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
		
		// test
		$this->storage->deleteObject ( $this->bucket1, $this->key1, false, false, false );
	}
	
	public function testDeleteObjectAllUserWrite() {
	    // setup
		$this->createObject ();
		$acp = $this->createACPAllUsers('WRITE');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
		
		// test
		$this->storage->deleteObject ( $this->bucket1, $this->key1,  false, false, false );
	}

	public function testDeleteObjectAllUserRead() {
	    // setup
		$this->createObject ();
		$acp = $this->createACPAllUsers('READ');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
		
		// test
		$this->setExpectedException('AccessDeniedException');
		$this->storage->deleteObject ( $this->bucket1, $this->key1,  false, false, false );
	}		
	
	public function testDeleteObjectDoesNotExist() {
		$this->createObject ();
		$this->storage->deleteObject ( $this->bucket1, $this->blankKey, $this->accessId1, $this->signature1, $this->stringToSign1 );
	}
	
	public function testDeleteObjectInvalidUser() {
		$this->createObject ();
		$this->setExpectedException('InvalidAccessKeyIdException');
		$this->storage->deleteObject ( $this->bucket1, $this->key1, $this->badAccessId, $this->signature1, $this->stringToSign1 );
	}
	
	public function testDeleteObjectNoWriteAccess() {
		$this->createObject ();
		$this->setExpectedException('AccessDeniedException');
		$this->storage->deleteObject ( $this->bucket1, $this->key1, $this->accessId2, $this->signature2, $this->stringToSign2 );
	}
	
	public function testPutObject() {
		$this->storage->putObject ( $this->bucket1, $this->key1, $this->value1, $this->metadata1, $this->content_type1, $this->accessId1, $this->signature1, $this->stringToSign1 );
	}
	
	public function testPutObjectAllUsersFullControl() {
	    // setup 
	    $acp = $this->createACPAllUsers('FULL_CONTROL');
	    $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
	    
	    // test
		$result = $this->storage->putObject ( $this->bucket1, $this->key1, $this->value1, $this->metadata1, $this->content_type1, false, false, false );
	    $this->assertNotNull($result);
	}

	public function testPutObjectAllUsersWrite() {
	    // setup
	    $acp = $this->createACPAllUsers('WRITE');
	    $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
	    
	    // test
		$result = $this->storage->putObject ( $this->bucket1, $this->key1, $this->value1, $this->metadata1, $this->content_type1, false, false, false );
	    $this->assertNotNull($result);
	}
	
	public function testPutObjectAllUsersRead() {
	    // setup
	    $acp = $this->createACPAllUsers('READ');
	    $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
	    
	    // test
	    $this->setExpectedException('AccessDeniedException');
		$this->storage->putObject ( $this->bucket1, $this->key1, $this->value1, $this->metadata1, $this->content_type1, false, false, false );
	}

	public function testPutObjectAuthenticatedUsersFullControl() {
	    // setup
	    $acp = $this->createACPAuthenticatedUsers('FULL_CONTROL');
	    $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
	    
	    // test
		$result = $this->storage->putObject ( $this->bucket1, $this->key1, $this->value1, $this->metadata1, $this->content_type1, $this->accessId3, $this->signature3, $this->stringToSign3 );
	    $this->assertNotNull($result);
	}	
	
	public function testPutObjectAuthenticatedUsersWrite() {
	    // setup
	    $acp = $this->createACPAuthenticatedUsers('WRITE');
	    $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
	    
	    // test
		$result = $this->storage->putObject ( $this->bucket1, $this->key1, $this->value1, $this->metadata1, $this->content_type1, $this->accessId3, $this->signature3, $this->stringToSign3 );
	    $this->assertNotNull($result);
	}	

	public function testPutObjectAuthenticatedUsersRead() {
	    // setup
	    $acp = $this->createACPAuthenticatedUsers('READ');
	    $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
	    
	    // test
	    $this->setExpectedException('AccessDeniedException');
		$this->storage->putObject ( $this->bucket1, $this->key1, $this->value1, $this->metadata1, $this->content_type1, $this->accessId3, $this->signature3, $this->stringToSign3 );
	}		
	
	
	
	public function testPutObjectInvalidUser() {
	    $this->setExpectedException('InvalidAccessKeyIdException');
		$this->storage->putObject ( $this->bucket1, $this->key1, $this->value1, $this->metadata1, $this->content_type1, $this->badAccessId, $this->signature1, $this->stringToSign1 );
	}
	
	public function testPutObjectNoWriteAccess() {
	    $this->setExpectedException('AccessDeniedException');
		$this->storage->putObject ( $this->bucket1, $this->key1, $this->value1, $this->metadata1, $this->content_type1, $this->accessId2, $this->signature2, $this->stringToSign2 );
	}
	
	/**
	 * Tests Storage->getBucketAccessControlPolicy()
	 */
	public function testGetObjectAccessControlPolicy() {
		$this->createObject ();
		$result = $this->storage->getObjectAccessControlPolicy ( $this->bucket1, $this->key1, $this->accessId1, $this->signature1, $this->stringToSign1 );
		$this->assertType ( 'AccessControlPolicy', $result );
		$owner = $result->getOwner ();
		$this->assertEquals ( $this->userid1, $owner->getId () );
	}
	
	public function testGetObjectAccessControlPolicyBadBucket() {
		$this->createObject ();
		$this->setExpectedException('NoSuchBucketException');
        $this->storage->getObjectAccessControlPolicy ( $this->blankBucket, $this->key1, $this->accessId1, $this->signature1, $this->stringToSign1 );
	}
	
	public function testGetObjectAccessControlPolicyAccessDenied() {
		$this->createObject ();
		$this->setExpectedException('InvalidAccessKeyIdException');
		$this->storage->getObjectAccessControlPolicy ( $this->bucket1, $this->key1, $this->badAccessId, $this->signature1, $this->stringToSign1 );
	}
	
	public function testGetObjectAccessControlPolicyNoSuchKey() {
		$this->createObject ();
		$this->setExpectedException('NoSuchKeyException');
        $this->storage->getObjectAccessControlPolicy ( $this->bucket1, $this->blankKey, $this->accessId1, $this->signature1, $this->stringToSign1 );
	}	

	public function testGetObjectAccessControlPolicyAuthenticatedUsersFullControl() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAuthenticatedUsers('FULL_CONTROL');
		$this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );		
		
		//test
        $result = $this->storage->getObjectAccessControlPolicy ( $this->bucket1, $this->key1, $this->accessId3, $this->signature3, $this->stringToSign3 );
		$this->assertType ( 'AccessControlPolicy', $result );
		$owner = $result->getOwner ();
		$this->assertEquals ( $this->userid1, $owner->getId () );
	}		
	
	public function testGetObjectAccessControlPolicyAuthenticatedUsersReadACP() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAuthenticatedUsers('READ_ACP');
		$this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );		
		
		//test
        $result = $this->storage->getObjectAccessControlPolicy ( $this->bucket1, $this->key1, $this->accessId3, $this->signature3, $this->stringToSign3 );
		$this->assertType ( 'AccessControlPolicy', $result );
		$owner = $result->getOwner ();
		$this->assertEquals ( $this->userid1, $owner->getId () );
	}	

	public function testGetObjectAccessControlPolicyAuthenticatedUsersWriteACP() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAuthenticatedUsers('WRITE_ACP');
		$this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );		
		
		//test
		$this->setExpectedException('AccessDeniedException');
        $this->storage->getObjectAccessControlPolicy ( $this->bucket1, $this->key1, $this->accessId3, $this->signature3, $this->stringToSign3 );
	}

	public function testGetObjectAccessControlPolicyAllUsersFullControl() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAllUsers('FULL_CONTROL');
		$this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );		
		
		//test
        $result = $this->storage->getObjectAccessControlPolicy ( $this->bucket1, $this->key1, false, false, false );
		$this->assertType ( 'AccessControlPolicy', $result );
		$owner = $result->getOwner ();
		$this->assertEquals ( $this->userid1, $owner->getId () );
	}		
	
	public function testGetObjectAccessControlPolicyAllUsersReadACP() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAllUsers('READ_ACP');
		$this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );		
		
		//test
        $result = $this->storage->getObjectAccessControlPolicy ( $this->bucket1, $this->key1, false, false, false );
		$this->assertType ( 'AccessControlPolicy', $result );
		$owner = $result->getOwner ();
		$this->assertEquals ( $this->userid1, $owner->getId () );
	}	

	public function testGetObjectAccessControlPolicyAllUsersWriteACP() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAllUsers('WRITE_ACP');
		$this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );		
		
		//test
		$this->setExpectedException('AccessDeniedException');
        $this->storage->getObjectAccessControlPolicy ( $this->bucket1, $this->key1, false, false, false );
	}	
	
	/**
	 * Tests Storage->setBucketAccessControlPolicy()
	 */
	public function testSetObjectAccessControlPolicy() {
		$this->createObject ();
		$acp = $this->createACP ();
		$result = $this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );
		$this->assertTrue ( $result );
		
		$new_acp = $this->storage->getObjectAccessControlPolicy ( $this->bucket1, $this->key1, $this->accessId1, $this->signature1, $this->stringToSign1 );
		$grants = $new_acp->getGrantList ();
		$this->assertEquals ( 3, count ( $grants ) );
		$owner = $new_acp->getOwner ();
		$this->assertEquals ( $this->userid1, $owner->getId () );
	}
	
	public function testSetObjectAccessControlPolicyBadBucket() {
		$this->createObject ();
		$acp = $this->createACP ();
		$this->setExpectedException('NoSuchBucketException');
		$this->storage->setObjectAccessControlPolicy ( $this->blankBucket, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );
	}
	
	public function testSetObjectAccessControlPolicyAccessDenied() {
		$this->createObject ();
		$acp = $this->createACP ();
		$this->setExpectedException('InvalidAccessKeyIdException');
		$this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, $this->badAccessId, $this->signature1, $this->stringToSign1 );
	}

	public function testSetObjectAccessControlPolicyAuthenticatedUsersFullControl() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAuthenticatedUsers('FULL_CONTROL');
		$this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );		
		
		//test
		$acp = $this->createACP();
        $this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, $this->accessId3, $this->signature3, $this->stringToSign3 );
	}		
	
	public function testSetObjectAccessControlPolicyAuthenticatedUsersWriteACP() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAuthenticatedUsers('WRITE_ACP');
		$this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );		
		
		//test
		$acp = $this->createACP();
        $this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, $this->accessId3, $this->signature3, $this->stringToSign3 );
	}	

	public function testSetObjectAccessControlPolicyAuthenticatedUsersReadACP() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAuthenticatedUsers('READ_ACP');
		$this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );		
		
		//test
		$this->setExpectedException('AccessDeniedException');
		$acp = $this->createACP();
        $this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, $this->accessId3, $this->signature3, $this->stringToSign3 );
	}

	public function testSetObjectAccessControlPolicyAllUsersFullControl() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAllUsers('FULL_CONTROL');
		$this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );		
		
		//test
		$acp = $this->createACP();
        $this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, false, false, false );
	}		
	
	public function testSetObjectAccessControlPolicyAllUsersWriteACP() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAllUsers('WRITE_ACP');
		$this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );		
		
		//test
		$acp = $this->createACP();
        $this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, false, false, false );
	}	

	public function testSetObjectAccessControlPolicyAllUsersReadACP() {
		// setup
	    $this->createObject ();
		$acp = $this->createACPAllUsers('READ_ACP');
		$this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1 );		
		
		//test
		$this->setExpectedException('AccessDeniedException');
		$acp = $this->createACP();
        $this->storage->setObjectAccessControlPolicy ( $this->bucket1, $this->key1, $acp, false, false, false );
	}
	
}
?>