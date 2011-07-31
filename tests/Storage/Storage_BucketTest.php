<?php
if (!DIR_SOURCE) {
    define( 'DIR_SOURCE', realpath( dirname( __FILE__ ) . '/../../src' ) );
}
require_once dirname( __FILE__ ) . '/../AbstractTest.php';
require_once DIR_SOURCE . '/s3/lib/storage.php';

/**
 *  test case.
 */
class Storage_BucketTest extends SimpleStorageServiceAbstractTest  {

	private $storage = null;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp ();
		$this->storage = new Storage ( );
		$this->connectDB();
		$this->cleanDB ();
		$this->createUser ();
		$this->storage->connect ( $this->dbh );
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		$this->dbh = null;
		$this->storage = null;
		parent::tearDown ();
	}
	
	/**
	 * Constructs the test case.
	 */
	public function __construct() {
	}
	
	public function testListAllMyBuckets() {
	    $this->createBucket();
	    $buckets = $this->storage->listAllMyBuckets($this->accessId1, $this->signature1, $this->stringToSign1);
	    $this->assertEquals($this->bucket1, $buckets['buckets'][0]['name']);
	    $this->assertEquals($this->userid1, $buckets['owner']['id']);
	    
	}
	
	public function testListAllMyBucketsBadAccessId() {
	    $this->setExpectedException('InvalidAccessKeyIdException');
		$this->storage->listAllMyBuckets ( $this->badAccessId, $this->signature3, $this->stringToSign3 );    
	}
	
	public function testGetBucketsLocation() {
        $this->createBucket();
		$result = $this->storage->getBucketLocation( $this->bucket1, $this->accessId1, $this->signature1, $this->stringToSign1 );    
		$this->assertEquals($this->location1, $result);
	}	
	
	public function testGetBucketsLocationNotOwner() {
        $this->createBucket();
        $this->setExpectedException('AccessDeniedException');
		$this->storage->getBucketLocation( $this->bucket1, $this->accessId2, $this->signature2, $this->stringToSign2 );    
	}

	public function testGetBucketsLocationAuthenticatedUser() {
        // setup
	    $this->createBucket();
        $acp = $this->createACPAllUsers('FULL_CONTROL');
        $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
        
        // test
        $this->setExpectedException('AccessDeniedException');
		$result = $this->storage->getBucketLocation( $this->bucket1, $this->accessId3, $this->signature3, $this->stringToSign3 );    
	}		

	public function testGetBucketsLocationAllUsers() {
        // setup
	    $this->createBucket();
        $acp = $this->createACPAuthenticatedUsers('FULL_CONTROL');
        $this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
        
        // test
        $this->setExpectedException('AccessDeniedException');
		$result = $this->storage->getBucketLocation( $this->bucket1, false, false, false );    
	}			
	
	public function testCreateBucket() {
		$result = $this->storage->createBucket ( $this->bucket1, $this->location1, $this->accessId1, $this->signature1, $this->stringToSign1 );
		$this->assertNotNull($result, 'Bucket was not created' );
	}
	
	public function testCreateBucketMissingSecurityHeader() {
	    $this->setExpectedException('MissingSecurityHeaderException');
		$result = $this->storage->createBucket ( $this->bucket1, $this->location1, false, false, $this->stringToSign1 );
	}	
	
	public function testCreateBucketEmail() {
		$result = $this->storage->createBucket ( $this->bucket1, $this->location1, $this->email1, $this->signature1, $this->stringToSign1 );
		$this->assertNotNull($result, 'Bucket was not created' );
	}
	
	public function testCreateBucketDuplicate() {
	    $this->setExpectedException('BucketAlreadyOwnedByYouException');
		$this->storage->createBucket ( $this->bucket1, $this->location1, $this->email1, $this->signature1, $this->stringToSign1 );
		$this->storage->createBucket ( $this->bucket1, $this->location1, $this->email1, $this->signature1, $this->stringToSign1 );
	}
	
	public function testCreateBucketAlreadyExists() {
	    $this->setExpectedException('BucketAlreadyExistsException');
		$this->storage->createBucket ( $this->bucket1, $this->location1, $this->email1, $this->signature1, $this->stringToSign1 );
		$this->storage->createBucket ( $this->bucket1, $this->location1, $this->email2, $this->signature2, $this->stringToSign2 );
	}	
	
	public function testCreateBucketBadName() {
	    $this->setExpectedException('InvalidBucketNameException');
		$this->storage->createBucket ( $this->badBucket, $this->location1, $this->email1, $this->signature1, $this->stringToSign1 );
	}
	
	public function testCreateBucketInvalidUser() {
	    $this->setExpectedException('InvalidAccessKeyIdException');
		$this->storage->createBucket ( $this->bucket1, $this->location1, $this->badAccessId, $this->signature1, $this->stringToSign1 );
	}
	
	public function testDeleteBucketNonExistant() {
	    $this->setExpectedException('NoSuchBucketException');
		$this->storage->deleteBucket ( $this->bucket1, $this->accessId1, $this->signature1, $this->stringToSign1 );
	}
	
	public function testDeleteBucketNotEmpty() {
		$this->createBucket();
		$this->createObject();
		$this->setExpectedException('BucketNotEmptyException');
		$this->storage->deleteBucket ( $this->bucket1, $this->accessId1, $this->signature1, $this->stringToSign1 );
	}	
	
	public function testDeleteBucketMissingAuthentication() {
		$this->createBucket();
		$this->createObject();
		$this->setExpectedException('MissingSecurityHeaderException');
		$this->storage->deleteBucket ( $this->bucket1, false, false, false );
	}	
	
	public function testDeleteBucket() {
		$this->createBucket();
		$result = $this->storage->deleteBucket ( $this->bucket1, $this->accessId1, $this->signature1, $this->stringToSign1 );
        $this->assertNotNull($result, "Delete bucket failed");
	}
	
	public function testDeleteBucketInvalidUser() {
	    $this->setExpectedException('InvalidAccessKeyIdException');
		$this->createBucket();
		$this->storage->deleteBucket ( $this->bucket1, $this->badAccessId, $this->signature1, $this->stringToSign1 );
	}
	
	public function testListBucketByOwner() {
		$this->createBucket ();
		$this->createObject ();
		
		$prefix = "k";
		$marker = "k";
		$maxKeys = 40;
		$delimiter = false;
		
		$result = $this->storage->listBucket ( $this->bucket1, $prefix, $marker, $delimiter, $maxKeys, $this->accessId1, $this->signature1, $this->stringToSign1 );
		$this->assertEquals ( $this->bucket1, $result ['Name'] );
	}
	
	public function testListBucketAllUsersFullControl() {
		$this->createBucket ();
		$this->createObject ();
		$prefix = "k";
		$marker = "k";
		$maxKeys = 40;
		$delimiter = false;
		$acp = $this->createACPAllUsers('FULL_CONTROL');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
		$result = $this->storage->listBucket ( $this->bucket1, $prefix, $marker, $delimiter, $maxKeys, false, false, false );
		$this->assertEquals ( $this->bucket1, $result ['Name'] );
	}	
	
	public function testListBucketAllUsersRead() {
		$this->createBucket ();
		$this->createObject ();
		$prefix = "k";
		$marker = "k";
		$maxKeys = 40;
		$delimiter = false;
		$acp = $this->createACPAllUsers('READ');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
		$result = $this->storage->listBucket ( $this->bucket1, $prefix, $marker, $delimiter, $maxKeys, false, false, false );
		$this->assertEquals ( $this->bucket1, $result ['Name'] );
	}	

	public function testListBucketAllUsersWrite() {
		$this->createBucket ();
		$this->createObject ();
		$prefix = "k";
		$marker = "k";
		$maxKeys = 40;
		$delimiter = false;
		$acp = $this->createACPAllUsers('WRITE');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
		
		$this->setExpectedException('AccessDeniedException');
		$this->storage->listBucket ( $this->bucket1, $prefix, $marker, $delimiter, $maxKeys, false, false, false );
	}

	public function testListBucketAuthenticatedUserFullControl() {
		$this->createBucket ();
		$this->createObject ();
		$prefix = "k";
		$marker = "k";
		$maxKeys = 40;
		$delimiter = false;
		$acp = $this->createACPAuthenticatedUsers('FULL_CONTROL');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
		$result = $this->storage->listBucket ( $this->bucket1, $prefix, $marker, $delimiter, $maxKeys, $this->accessId3, $this->signature3, $this->stringToSign3 );
		$this->assertEquals ( $this->bucket1, $result ['Name'] );
	}

	public function testListBucketAuthenticatedUserRead() {
		$this->createBucket ();
		$this->createObject ();
		$prefix = "k";
		$marker = "k";
		$maxKeys = 40;
		$delimiter = false;
		$acp = $this->createACPAuthenticatedUsers('READ');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
		$result = $this->storage->listBucket ( $this->bucket1, $prefix, $marker, $delimiter, $maxKeys, $this->accessId3, $this->signature3, $this->stringToSign3 );
		$this->assertEquals ( $this->bucket1, $result ['Name'] );
	}		
	
	public function testListBucketAuthenticatedUserWrite() {
		$this->createBucket ();
		$this->createObject ();
		$prefix = "k";
		$marker = "k";
		$maxKeys = 40;
		$delimiter = false;
		$acp = $this->createACPAuthenticatedUsers('WRITE');
		$this->storage->setBucketAccessControlPolicy($this->bucket1, $acp, $this->accessId1, $this->signature1, $this->stringToSign1);
		
		$this->setExpectedException('AccessDeniedException');
		$this->storage->listBucket ( $this->bucket1, $prefix, $marker, $delimiter, $maxKeys, $this->accessId3, $this->signature3, $this->stringToSign3 );
	}		
	
	public function testListBucketByReadUser() {
		$this->createBucket ();
		$this->createObject ();
		
		$prefix = "k";
		$marker = "k";
		$maxKeys = 40;
		$delimiter = false;
		
		$result = $this->storage->listBucket ( $this->bucket1, $prefix, $marker, $delimiter, $maxKeys, $this->accessId2, $this->signature2, $this->stringToSign2 );
		$this->assertEquals ( $this->bucket1, $result ['Name'] );
	}
	
	public function testListBucketNotReadUser() {
		$this->createBucket ();
		$this->createObject ();
		
		$prefix = "k";
		$marker = "k";
		$maxKeys = 40;
		$delimiter = false;
		
		$this->setExpectedException('AccessDeniedException');
		$this->storage->listBucket ( $this->bucket1, $prefix, $marker, $delimiter, $maxKeys, $this->accessId3, $this->signature3, $this->stringToSign3 );
	}
	
	public function testListBucketInvalidUser() {
		$this->createBucket ();
		$this->createObject ();
		
		$prefix = "k";
		$marker = "k";
		$maxKeys = 40;
		$delimiter = false;
		
		$this->setExpectedException('InvalidAccessKeyIdException');
		$this->storage->listBucket ( $this->bucket1, $prefix, $marker, $delimiter, $maxKeys, $this->badAccessId, $this->signature3, $this->stringToSign3 );
	}
	
	
	
}
?>