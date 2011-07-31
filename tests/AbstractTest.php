<?php
define ( 'LIB_SOURCE', realpath ( dirname ( __FILE__ ) . '/../src/s3/lib' ) );
require_once 'PHPUnit/Framework/TestCase.php';
require_once LIB_SOURCE . '/accessControlPolicy.php';

abstract class SimpleStorageServiceAbstractTest extends PHPUnit_Framework_TestCase
{
        
    // database values
	protected $dbh         = null;
	protected $db_username = "root";
	protected $db_password = "";
	protected $db_server   = "mysql";
	protected $db_name     = "test_s3";
	protected $db_host     = "localhost";
	
	// bucket details
	protected $bucketid1 = "LriYPLdmOdAiIfgSm/F1YsViT1LW94/xUQxMsF7xiEb1a0wiIOIxl+zbwZ163pt7";
	protected $bucket1   = "milan";
	protected $location1 = "EU";
	protected $bucketid2 = "bucketid2bucketid2bucketid2bucketid2bucketid2bucketid2bucketid22";
	protected $bucket2   = "bucket2";
	protected $location2 = "US";
	
	// object details
	protected $key1          = "key1";
	protected $key2          = "key2";
	protected $objectid1     = "1111111111111111111111111111111111111111111111111111111111111111";
	protected $objectid2     = "2222222222222222222222222222222222222222222222222222222222222222";
	protected $value1        = "this is the first value";
	protected $value2        = "<h1>this is the second value</h1>";
	protected $content_type1 = "text/plain";
	protected $content_type2 = "text/html";
	protected $metadata1     = array('Family' => 'Magudia');
	protected $metadata2     = array('Drink'  => 'Gurls');
	protected $lastUpdated1  = '1977-06-23 12:00:00';
	protected $lastUpdated2  = '2000-06-15 12:00:00';
	
	// most of these details are taken from s3 documentation
	protected $userid1       = "a9a7b886d6fd24a52fe8ca5bef65f89a64e0193f23000e241bf9b1c61be666e9";
	protected $displayName1  = "Milan";
	protected $email1        = "milan@magudia.com";
	protected $accessId1     = "0PN5J17HBGZHT7JJ3X82";
	protected $secretKey1    = "uV3F3YluFJax1cknvbcGwgjvx4QpvB+leU8dUj2o";
	protected $stringToSign1 = "PUT\n\nimage/jpeg\nTue, 27 Mar 2007 21:15:45 +0000\n/johnsmith/photos/puppy.jpg";
	protected $signature1    = 'hcicpDDvL9SsO6AkvxqmIWkmOuQ=';
	
	// I made these two up
	protected $userid2       = "userid2userid2userid2userid2userid2userid2userid2userid2userid22";
	protected $displayName2  = "Foo";
	protected $email2        = "foo@magudia.com";
	protected $accessId2     = "FOOFOOFOOFOOFOOFOOOO";
	protected $secretKey2    = "foofoofoofoofoofoofoofoofoofoofoofoofooo";
	protected $stringToSign2 = "stringtosign2";
	protected $signature2    = "q/Tuw+SOlRkoyeUeij0nsox4QuE=";
	
	protected $userid3       = "userid3userid3userid3userid3userid3userid3userid3userid3userid33";
	protected $displayName3  = "Bar";
	protected $email3        = "bar@magudia.com";
	protected $accessId3     = "BARBARBARBARBARBARRR";
	protected $secretKey3    = "barbarbarbarbarbarbarbarbarbarbarbarbarr";
	protected $stringToSign3 = "stringtosign3";
	protected $signature3    = "V9S14Pey4D6NNq9jlLgRplUBeD8=";
	
	// bad values
	protected $badUserId     = "userid4userid4userid4userid4userid4userid4userid4userid4userid44";
	protected $badAccessId   = "INVALIDUSERBADBADBAD";
	protected $badBucket     = "_badname";
	protected $blankBucket   = "bucketnotexists";    
	protected $blankKey      = "keynotexists";
	protected $blankAccessId = "DOESNTEXISTACCESSID0";	
	protected $sampleACP     = "<AccessControlPolicy><Owner><ID>a9a7b886d6fd24a52fe8ca5bef65f89a64e0193f23000e241bf9b1c61be666e9</ID><DisplayName>chriscustomer</DisplayName></Owner><AccessControlList><Grant><Grantee xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:type='CanonicalUser'><ID>a9a7b886d6fd24a52fe8ca5bef65f89a64e0193f23000e241bf9b1c61be666e9</ID><DisplayName>chriscustomer</DisplayName></Grantee><Permission>FULL_CONTROL</Permission></Grant><Grant><Grantee xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:type='CanonicalUser'><ID>79a59df900b949e55d96a1e698fbacedfd6e09d98eacf8f8d5218e7cd47ef2be</ID><DisplayName>Frank</DisplayName></Grantee><Permission>WRITE</Permission></Grant><Grant><Grantee xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:type='CanonicalUser'><ID>79a59df900b949e55d96a1e698fbacedfd6e09d98eacf8f8d5218e7cd47ef2be</ID><DisplayName>Frank</DisplayName></Grantee><Permission>READ_ACP</Permission></Grant><Grant><Grantee xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:type='AmazonCustomerByEmail'><EmailAddress>chriscustomer@email.com</EmailAddress></Grantee><Permission>WRITE</Permission></Grant><Grant><Grantee xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:type='CanonicalUser'><ID>e019164ebb0724ff67188e243eae9ccbebdde523717cc312255d9a82498e394a</ID><DisplayName>Jose</DisplayName></Grantee><Permission>READ_ACP</Permission></Grant><Grant><Grantee xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:type='Group'><URI>http://acs.amazonaws.com/groups/global/AllUsers</URI></Grantee><Permission>READ</Permission></Grant><Grant><Grantee xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:type='Group'><URI>http://acs.amazonaws.com/groups/global/AuthenticatedUsers</URI></Grantee><Permission>WRITE</Permission></Grant></AccessControlList></AccessControlPolicy>";
	
    /**
     * Removes all test contents.
     *
     * @return void
     */
    protected function tearDown()
    {   
        $this->dbh = null;
        parent::tearDown();
    }
    
	protected function calculateSignature($key, $message) {
		require_once 'Crypt/HMAC.php';
		$sha1 = new Crypt_HMAC ( $key, "sha1" );
		$hash = $sha1->hash ( $message );
		$signature = false;
		// borrowed from: http://neurofuzzy.net/2006/08/26/amazon-s3-php-class-update/
		for($i = 0; $i < strlen ( $hash ); $i += 2) {
			$signature .= chr ( hexdec ( substr ( $hash, $i, 2 ) ) );
		}
		return base64_encode ( $signature );
	}    
    
	protected function connectDB() {
		$this->dbh = new PDO ( $this->db_server . ':host=' . $this->db_host . ';dbname=' . $this->db_name, $this->db_username, $this->db_password );
	}
	
	protected function createACP() {
	    $acp = new AccessControlPolicy();
	    
	    $owner = new Owner();
	    $owner->setId($this->userid1);
	    $owner->setDisplayName($this->displayName1);
	    $acp->setOwner($owner);
	    
        $grantee1 = new Grantee('CanonicalUser', $this->displayName1, $this->userid1);
        $grant1   = new Grant($grantee1, 'FULL_CONTROL');
        $acp->addGrant($grant1);
	    
        $grantee2 = new Grantee('CanonicalUser', $this->displayName2, $this->userid2);
        $grant2   = new Grant($grantee2, 'WRITE_ACP');
        $acp->addGrant($grant2);

        $grantee3 = new Grantee('CanonicalUser', $this->displayName2, $this->userid2);
        $grant3   = new Grant($grantee3, 'READ');
        $acp->addGrant($grant3);        
        
	    return $acp;
	}
	
	protected function createBadACP() {
	    $acp = new AccessControlPolicy();
	    
        $grantee1 = new Grantee('CanonicalUser', $this->displayName1, $this->userid1);
        $grant1   = new Grant($grantee1, 'FULL_CONTROL');
        $acp->addGrant($grant1);
	    
        $grantee2 = new Grantee('CanonicalUser', $this->displayName1, $this->badUserId);
        $grant2   = new Grant($grantee2, 'WRITE_ACP');
        $acp->addGrant($grant2);

        $grantee3 = new Grantee('CanonicalUser', $this->displayName2, $this->userid2);
        $grant3   = new Grant($grantee3, 'READ');
        $acp->addGrant($grant3);        
        
	    return $acp;
	}	
	
	protected function createACPEmail() {
	    $acp = new AccessControlPolicy();
	    
        $grantee1 = new Grantee('AmazonCustomerByEmail', null, null, $this->email1);
        $grant1   = new Grant($grantee1, 'FULL_CONTROL');
        $acp->addGrant($grant1);
	    
        $grantee2 = new Grantee('AmazonCustomerByEmail', null, null, $this->email2);
        $grant2   = new Grant($grantee2, 'WRITE_ACP');
        $acp->addGrant($grant2);

        $grantee3 = new Grantee('AmazonCustomerByEmail', null, null, $this->email2);
        $grant3   = new Grant($grantee3, 'READ');
        $acp->addGrant($grant3);        
        
	    return $acp;
	}	
	
	protected function createACPAuthenticatedUsers($permission = 'READ') {
	    $acp = new AccessControlPolicy();

	    $owner = new Owner();
	    $owner->setId($this->userid1);
	    $owner->setDisplayName($this->displayName1);
	    $acp->setOwner($owner);	    
	    
        $grantee1 = new Grantee('AmazonCustomerByEmail', null, null, $this->email1);
        $grant1   = new Grant($grantee1, 'FULL_CONTROL');
        $acp->addGrant($grant1);	    
	    
        $grantee2 = new Grantee('Group', null, null, null, 'http://acs.amazonaws.com/groups/global/AuthenticatedUsers');
        $grant2   = new Grant($grantee2, $permission);
        $acp->addGrant($grant2);

	    return $acp;
	}		
	
	protected function createACPAllUsers($permission = 'READ') {
	    $acp = new AccessControlPolicy();

	    $owner = new Owner();
	    $owner->setId($this->userid1);
	    $owner->setDisplayName($this->displayName1);
	    $acp->setOwner($owner);	    
	    
        $grantee1 = new Grantee('AmazonCustomerByEmail', null, null, $this->email1);
        $grant1   = new Grant($grantee1, 'FULL_CONTROL');
        $acp->addGrant($grant1);	
        	    
        $grantee2 = new Grantee('Group', null, null, null, 'http://acs.amazonaws.com/groups/global/AllUsers');
        $grant2   = new Grant($grantee2, $permission);
        $acp->addGrant($grant2);

	    return $acp;
	}		
	
	protected function createBadACPGroup() {
	    $acp = new AccessControlPolicy();	
        $grantee2 = new Grantee('Group', null, null, null, 'http://acs.amazonaws.com/groups/global/Weird');
        $grant2   = new Grant($grantee2, 'READ');
        $acp->addGrant($grant2);

	    return $acp;
	}	
	
	protected function createBadACPEmail() {
	    $acp = new AccessControlPolicy();

        $grantee1 = new Grantee('AmazonCustomerByEmail', null, null, 'bad@magudia.com');
        $grant1   = new Grant($grantee1, 'FULL_CONTROL');
        $acp->addGrant($grant1);	

	    return $acp;
	}			

	protected function createBadACPType() {
	    $acp = new AccessControlPolicy();	
        	    
        $grantee2 = new Grantee('Type', null, null, null, 'http://acs.amazonaws.com/groups/global/Weird');
        $grant2   = new Grant($grantee2, 'READ');
        $acp->addGrant($grant2);

	    return $acp;
	}			
	
	protected function createUser() {
		// add three users. 
		// user1 as a main test subject as a owner of a bucket and object.
		// user2 with no buckets but could be a owner of a object in bucket1
		// user3 with no buckets and will have no objects in any bucket 
		$this->dbh->exec ( "INSERT INTO `user` (`id`, `display_name`, `email_address`, `access_key`, `secret_key`) VALUES
                     ('$this->userid1', '$this->displayName1', '$this->email1', 
                      '$this->accessId1', '$this->secretKey1')" );
		$this->dbh->exec ( "INSERT INTO `user` (`id`, `display_name`, `email_address`, `access_key`, `secret_key`) VALUES
                     ('$this->userid2', '$this->displayName2', '$this->email2', 
                      '$this->accessId2', '$this->secretKey2')" );
		$this->dbh->exec ( "INSERT INTO `user` (`id`, `display_name`, `email_address`, `access_key`, `secret_key`) VALUES
                     ('$this->userid3', '$this->displayName3', '$this->email3', 
                      '$this->accessId3', '$this->secretKey3')" );
	}
	
	protected function createUserDuplicateEmail() {
		// add three users. 
		// user1 as a main test subject as a owner of a bucket and object.
		// user2 with no buckets but could be a owner of a object in bucket1
		// user3 with no buckets and will have no objects in any bucket 
		$this->dbh->exec ( "DELETE * FROM `user`" );
		$this->dbh->exec ( "INSERT INTO `user` (`id`, `display_name`, `email_address`, `access_key`, `secret_key`) VALUES
                     ('$this->userid1', '$this->displayName1', '$this->email1', 
                      '$this->accessId1', '$this->secretKey1')" );
		$this->dbh->exec ( "INSERT INTO `user` (`id`, `display_name`, `email_address`, `access_key`, `secret_key`) VALUES
                     ('$this->userid2', '$this->displayName2', '$this->email2', 
                      '$this->accessId2', '$this->secretKey2')" );
		$this->dbh->exec ( "INSERT INTO `user` (`id`, `display_name`, `email_address`, `access_key`, `secret_key`) VALUES
                     ('$this->userid3', '$this->displayName3', '$this->email1', 
                      '$this->accessId3', '$this->secretKey3')" );
                      
	}	
	
	protected function cleanDB() {
		// clear all data from database
		$this->dbh->exec ( "TRUNCATE `bucket`" );
		$this->dbh->exec ( "TRUNCATE `user_bucket`" );
		$this->dbh->exec ( "TRUNCATE `user_object" );
		$this->dbh->exec ( "TRUNCATE `user`" );
		$this->dbh->exec ( "TRUNCATE `object`" );
	}
	
	protected function createBucket() {
		// create bucket for user1 and add read permission to the bucket for user2
		$this->dbh->exec ( "INSERT INTO `bucket` (`id`, `location`, `name`, `ownerid`) VALUES ('$this->bucketid1', '$this->location1', '$this->bucket1', '$this->userid1')" );
		$this->dbh->exec ( "INSERT INTO `user_bucket` (`aclid`, `userid`, `bucketid`) VALUES ('1', '$this->userid1', '$this->bucketid1')" );
		$this->dbh->exec ( "INSERT INTO `user_bucket` (`aclid`, `userid`, `bucketid`) VALUES ('2', '$this->userid2', '$this->bucketid1')" );
	}
	
	protected function createObject() {
		// create a object for user1 in the bucket owned by user1
		// create object for user2 in user1's bucket and give read permission to user1 for that object
		$metadata1 = serialize($this->metadata1);
		$metadata2 = serialize($this->metadata2);
		$etag1 = md5($this->value1);
		$etag2 = md5($this->value2);
	    
		$this->dbh->exec ( "INSERT INTO `object` (`id`, `key`, `bucketid`, `ownerid`, `value`, `metadata`, `content_type`, `etag`, `last_updated`) VALUES ('$this->objectid1', '$this->key1', '$this->bucketid1', '$this->userid1', '$this->value1', '$metadata1', '$this->content_type1', '$etag1', '$this->lastUpdated1')" );
		$this->dbh->exec ( "INSERT INTO `object` (`id`, `key`, `bucketid`, `ownerid`, `value`, `metadata`, `content_type`, `etag`, `last_updated`) VALUES ('$this->objectid2', '$this->key2', '$this->bucketid1', '$this->userid2', '$this->value2', '$metadata2', '$this->content_type2', '$etag2', '$this->lastUpdated2')" );
		$this->dbh->exec ( "INSERT INTO `user_object` (`userid`,`objectid`,`aclid`) VALUES ('$this->userid1','$this->objectid1','1')" );
		$this->dbh->exec ( "INSERT INTO `user_object` (`userid`,`objectid`,`aclid`) VALUES ('$this->userid2','$this->objectid2','1')" );
		$this->dbh->exec ( "INSERT INTO `user_object` (`userid`,`objectid`,`aclid`) VALUES ('$this->userid1','$this->objectid2','2')" );
	}	
    
	protected function createObjectExtended() {
		$metadata1 = serialize($this->metadata1);
		$etag1 = md5($this->value1);	   
		 
		$keys = array('A','B','C','D','E');
		
		foreach ($keys as $first) {
            foreach ($keys as $second) {
                $key = $first.$second;
                $objectId =  hash('sha256', $key);
		        $this->dbh->exec ( "INSERT INTO `object` (`id`, `key`, `bucketid`, `ownerid`, `value`, `metadata`, `content_type`, `etag`, `last_updated`) VALUES ('$objectId', '$key', '$this->bucketid1', '$this->userid1', '$this->value1', '$metadata1', '$this->content_type1', '$etag1', '$this->lastUpdated1')" );
		        $this->dbh->exec ( "INSERT INTO `user_object` (`userid`,`objectid`,`aclid`) VALUES ('$this->userid1','$objectId','1')" );		
		    }
		}
	}
    
}