<?php
if (!DIR_SOURCE) {
    define( 'DIR_SOURCE', realpath( dirname( __FILE__ ) . '/../../src' ) );
}
require_once DIR_SOURCE . '/s3/lib/storage.php';
require_once DIR_SOURCE . '/s3/lib/accessControlPolicy.php';
require_once dirname( __FILE__ ) . '/../AbstractTest.php';

/**
 * Storage test case.
 */
class Storage_BucketACLTest extends SimpleStorageServiceAbstractTest  {
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp ();
		$this->storage = new Storage ( );
		$this->connectDB();
		$this->cleanDB ();
		$this->createUser();
		$this->createBucket();
		$this->createObject();
		$this->storage->connect ( $this->dbh );
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		$this->storage = null;
		$this->dbh     = null;
		parent::tearDown ();
	}
	
	/**
	 * Constructs the test case.
	 */
	public function __construct() {
	}
	
	/**
	 * Tests Storage->getBucketAccessControlPolicy()
	 */
	public function testGetBucketAccessControlPolicy() {
		$result = $this->storage->getBucketAccessControlPolicy($this->bucket1, $this->accessId1, $this->signature1, $this->stringToSign1);
		$this->assertType('AccessControlPolicy', $result);
		$owner = $result->getOwner();
		$this->assertEquals($this->userid1, $owner->getId());
	}
	
	public function testGetBucketAccessControlPolicyAllUsersFullControl() {
	    $acp = $this->createACPAllUsers('FULL_CONTROL');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
		
		$result = $this->storage->getBucketAccessControlPolicy($this->bucket1, false, false, false);
		$this->assertType('AccessControlPolicy', $result);
		$owner = $result->getOwner();
		$this->assertEquals($this->userid1, $owner->getId());
	}	
	
	public function testGetBucketAccessControlPolicyAllUsersReadACP() {
	    $acp = $this->createACPAllUsers('READ_ACP');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
			    
		$result = $this->storage->getBucketAccessControlPolicy($this->bucket1, false, false, false);
		$this->assertType('AccessControlPolicy', $result);
		$owner = $result->getOwner();
		$this->assertEquals($this->userid1, $owner->getId());
	}		
	
	public function testGetBucketAccessControlPolicyAllUsersWrite() {
	    $acp = $this->createACPAllUsers('WRITE');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);

		$this->setExpectedException('AccessDeniedException');
		$this->storage->getBucketAccessControlPolicy($this->bucket1, false, false, false);
	}	

	public function testGetBucketAccessControlPolicyAuthenticatedUsersFullControl() {
	    $acp = $this->createACPAuthenticatedUsers('FULL_CONTROL');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
		
		$result = $this->storage->getBucketAccessControlPolicy($this->bucket1, $this->accessId3, $this->signature3, $this->stringToSign3);
		$this->assertType('AccessControlPolicy', $result);
		$owner = $result->getOwner();
		$this->assertEquals($this->userid1, $owner->getId());
	}	
	
	public function testGetBucketAccessControlPolicyAuthenticatedUsersReadACP() {
	    $acp = $this->createACPAuthenticatedUsers('READ_ACP');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
			    
		$result = $this->storage->getBucketAccessControlPolicy($this->bucket1, $this->accessId3, $this->signature3, $this->stringToSign3);
		$this->assertType('AccessControlPolicy', $result);
		$owner = $result->getOwner();
		$this->assertEquals($this->userid1, $owner->getId());
	}		
	
	public function testGetBucketAccessControlPolicyAuthenticatedUsersWrite() {
	    $acp = $this->createACPAuthenticatedUsers('WRITE');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);

		$this->setExpectedException('AccessDeniedException');
		$this->storage->getBucketAccessControlPolicy($this->bucket1, $this->accessId3, $this->signature3, $this->stringToSign3);
	}	
		
	
	public function testGetBucketAccessControlPolicyBadBucket() {
	    $this->setExpectedException('NoSuchBucketException');
        $this->storage->getBucketAccessControlPolicy($this->blankBucket, $this->accessId1, $this->signature1, $this->stringToSign1);
	}	
	
	public function testGetBucketAccessControlPolicyAccessDenied() {
	    $this->setExpectedException('InvalidAccessKeyIdException');
	    $this->storage->getBucketAccessControlPolicy($this->bucket1, $this->badAccessId, $this->signature1, $this->stringToSign1);
	}		
	
	/**
	 * Tests Storage->setBucketAccessControlPolicy()
	 */
	public function testSetBucketAccessControlPolicy(){
	    $acp = $this->createACP();
	    $result = $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
	    $this->assertTrue($result);
	    
	    $new_acp = $this->storage->getBucketAccessControlPolicy($this->bucket1, $this->accessId1, $this->signature1, $this->stringToSign1);
	    $grants = $new_acp->getGrantList();
	    $this->assertEquals(3, count($grants));
	    $owner = $new_acp->getOwner();
	    $this->assertEquals($this->userid1, $owner->getId());
	}
	
	public function testSetBucketAccessControlPolicyEmail(){
	    $acp = $this->createACPEmail();
	    $result = $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
	    $this->assertTrue($result);
	    //exit;
	    $new_acp = $this->storage->getBucketAccessControlPolicy($this->bucket1, $this->accessId1, $this->signature1, $this->stringToSign1);
	    $grants = $new_acp->getGrantList();
	    $this->assertEquals(3, count($grants));
	    $owner = $new_acp->getOwner();
	    $this->assertEquals($this->userid1, $owner->getId());
	}	

	public function testSetBucketAccessControlPolicyAllUsers(){
	    $acp = $this->createACPAllUsers();
	    $result = $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
	    $this->assertTrue($result);
	    //exit;
	    $new_acp = $this->storage->getBucketAccessControlPolicy($this->bucket1, $this->accessId1, $this->signature1, $this->stringToSign1);
	    $grants = $new_acp->getGrantList();
	    $this->assertEquals(2, count($grants));
        $grant = $grants[1];
	    $grantee = $grant->getGrantee();
	    $this->assertEquals('READ',$grant->getProtection());
	    $this->assertEquals('http://acs.amazonaws.com/groups/global/AllUsers',$grantee->getUri());          
	    $owner = $new_acp->getOwner();
	    $this->assertEquals($this->userid1, $owner->getId());
	}		
	
	public function testSetBucketAccessControlPolicyAuthenticatedUsers(){
	    $acp = $this->createACPAuthenticatedUsers();
	    $result = $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
	    $this->assertTrue($result);
	    //exit;
	    $new_acp = $this->storage->getBucketAccessControlPolicy($this->bucket1, $this->accessId1, $this->signature1, $this->stringToSign1);
	    $grants = $new_acp->getGrantList();
	    $this->assertEquals(2, count($grants));
	    $grant = $grants[1];
	    $grantee = $grant->getGrantee();
	    $this->assertEquals('READ',$grant->getProtection());  
	    $this->assertEquals('http://acs.amazonaws.com/groups/global/AuthenticatedUsers',$grantee->getUri());   
	    $owner = $new_acp->getOwner();
	    $this->assertEquals($this->userid1, $owner->getId());
	}		
	
	public function testSetBucketAccessControlPolicyBadACP(){
	    $this->setExpectedException('InvalidPolicyDocumentException');
	    $acp = $this->createBadACP();
	    $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
	}		
	
	public function testSetBucketAccessControlPolicyBadACPEmail(){
	    $this->setExpectedException('UnresolvableGrantByEmailAddressException');
	    $acp = $this->createBadACPEmail();
	    $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
	}	

	public function testSetBucketAccessControlPolicyBadACPGroup(){
	    $this->setExpectedException('InvalidPolicyDocumentException');
	    $acp = $this->createBadACPGroup();
	    $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
	}	

	public function testSetBucketAccessControlPolicyBadACPType(){
	    $this->setExpectedException('InvalidPolicyDocumentException');
	    $acp = $this->createBadACPType();
	    $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
	}		
	
	public function testSetBucketAccessControlPolicyBadBucket() {
	    $acp = $this->createACP();
	    $this->setExpectedException('NoSuchBucketException');
        $this->storage->setBucketAccessControlPolicy($this->blankBucket, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
	}	
	
	public function testSetBucketAccessControlPolicyAccessDenied() {
	    $acp = $this->createACP();
	    $this->setExpectedException('InvalidAccessKeyIdException');
        $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->badAccessId, $this->signature1, $this->stringToSign1);
	}		

	public function testSetBucketAccessControlPolicyAllUsersFullControl() {
	    $acp = $this->createACPAllUsers('FULL_CONTROL');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
		
		$result = $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, false, false, false);
        $this->assertNotNull($result);
	}	
	
	public function testSetBucketAccessControlPolicyAllUsersWriteACP() {
	    $acp = $this->createACPAllUsers('WRITE_ACP');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
			    
		$result = $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, false, false, false);
        $this->assertNotNull($result);
	}		
	
	public function testSetBucketAccessControlPolicyAllUsersWrite() {
	    $acp = $this->createACPAllUsers('WRITE');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);

		$this->setExpectedException('AccessDeniedException');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, false, false, false);
	}	

	public function testSetBucketAccessControlPolicyAuthenticatedUsersFullControl() {
	    $acp = $this->createACPAuthenticatedUsers('FULL_CONTROL');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
		
		$result = $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId3, $this->signature3, $this->stringToSign3);
		$this->assertNotNull($result);
	}	
	
	public function testSetBucketAccessControlPolicyAuthenticatedUsersWriteACP() {
	    $acp = $this->createACPAuthenticatedUsers('WRITE_ACP');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
			    
		$result = $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId3, $this->signature3, $this->stringToSign3);
        $this->assertNotNull($result);
	}		
	
	public function testSetBucketAccessControlPolicyAuthenticatedUsersWrite() {
	    $acp = $this->createACPAuthenticatedUsers('WRITE');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);

		$this->setExpectedException('AccessDeniedException');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId3, $this->signature3, $this->stringToSign3);
	}		
	
}

