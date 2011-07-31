<?php

define( 'DIR_SOURCE', realpath( dirname( __FILE__ ) . '/../../src' ) );
require_once 'PHPUnit/Framework/TestCase.php';
require_once DIR_SOURCE.'/s3/lib/validator.php';

/**
 *  test case.
 */
class Common_ValidatorTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp ();
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		parent::tearDown ();
	}
	
	/**
	 * Constructs the test case.
	 */
	public function __construct() {
	}

	public function testValidatorBucketNameTooShort()
	{
        $validate = new Validator();
        $bucket = "12";
        $this->setExpectedException('InvalidBucketNameException');
        $validate->validateBucketName($bucket);
    }
	
	public function testValidatorBucketNameTooLong()
	{
		$validate = new Validator();
        $bucket = "thisisaveryveryveryverylongstring";
        $bucket = $bucket.$bucket.$bucket.$bucket.$bucket.$bucket.$bucket;
        $bucket = $bucket.$bucket.$bucket.$bucket.$bucket.$bucket.$bucket;
	    $this->setExpectedException('InvalidBucketNameException');
        $validate->validateBucketName($bucket);
	}
	
    public function testValidatorBucketNameIPAddress()
    {
        $this->setExpectedException('InvalidBucketNameException');
        $validate = new Validator();
        $bucket = "192.168.0.1";
        $validate->validateBucketName($bucket);
    }	

    public function testValidatorBucketNameStartsWithNonNumberOrLetter()
    {
        $validate = new Validator();
        $bucket = "goodname";
        $result = $validate->validateBucketName($bucket);
        $this->assertTrue($result);
        
        $bucket = "0zerogood";
        $result = $validate->validateBucketName($bucket);
        $this->assertTrue($result);
        
        $this->setExpectedException('InvalidBucketNameException');
        $bucket = '$notgood';
        $validate->validateBucketName($bucket);
    }    

    public function testValidatorBucketNameSpaces()
    {
        $validate = new Validator();
        $bucket = "buck  et";
        $this->setExpectedException('InvalidBucketNameException');
        $validate->validateBucketName($bucket);
    }       
    
    public function testValidatorBucketName()
    {
        $validate = new Validator();
        $bucket = "b.u_c-ket";
        $result = $validate->validateBucketName($bucket);
        $this->assertTrue($result);
    }       
    
    public function testInvalidateBucketName()
    {
        $validate = new Validator();
        $bucket = "this!is&not*a^good%displayName";
	    $this->setExpectedException('InvalidBucketNameException');        
        $validate->validateBucketName($bucket);
    }      
    
    public function testValidatorCanonicalUserEmpty()
    {
        $validate = new Validator();
        $canonicalUser = false;
        try {
            $validate->validateCanonicalUser($canonicalUser);
        } catch (InvalidArgumentException $e) {
                    $this->assertEquals('$canonicalUser cannot be empty: must be 64 alphanumeric characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }
    
    public function testValidatorCanonicalUserTooShort()
    {
        $validate = new Validator();
        $canonicalUser = "ABCD";
        try {
            $validate->validateCanonicalUser($canonicalUser);
        } catch (InvalidArgumentException $e) {
                    $this->assertEquals('$canonicalUser string is too short: must be 64 alphanumeric characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }    
           
    public function testValidatorCanonicalUserTooLong()
    {
        $validate = new Validator();
        $canonicalUser = "ABCABCABCABCABCABCABCABCABCABCABCABCABCABCABCABCABCABCABCABCABCABCABCABC";
        try {
            $validate->validateCanonicalUser($canonicalUser);
        } catch (InvalidArgumentException $e) {
                    $this->assertEquals('$canonicalUser string is too long: must be 64 alphanumeric characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }    
    
    public function testValidatorCanonicalUserInvalid()
    {
        $validate = new Validator();
        $canonicalUser = "a9!7b886d6fd24a52fe8ca5bef65f89a64e0193f23000e241bf9b1c61be666e9";
        try {
            $validate->validateCanonicalUser($canonicalUser);
        } catch (InvalidArgumentException $e) {
                    $this->assertEquals('$canonicalUser string is not valid: must be 64 alphanumeric characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }          
    
    public function testValidatorCanonicalUser()
    {
        $validate = new Validator();
        $canonicalUser = "a9a7b886d6fd24a52fe8ca5bef65f89a64e0193f23000e241bf9b1c61be666e9";
        $result = $validate->validateCanonicalUser($canonicalUser);
        $this->assertTrue($result);
    }    

    public function testValidatorDisplayNameEmpty()
    {
        $validate = new Validator();
        $displayName = false;
        try {
            $validate->validateDisplayName($displayName);    
        } catch (InvalidArgumentException $e) {
                    $this->assertEquals('$displayName cannot be empty: must be >=3 and <=64 characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }
    
    public function testValidatorDisplayNameTooShort()
    {
        $validate = new Validator();
        $displayName = "MM";
        try {
            $validate->validateDisplayName($displayName);    
        } catch (InvalidArgumentException $e) {
                    $this->assertEquals('$displayName string is too short: must be >=3 and <=64 characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }    
    
    public function testValidatorDisplayNameTooLong()
    {
        $validate = new Validator();
        $displayName = "1234567890123456789012345678901234567890123456789012345678901234567890";
        try {
            $validate->validateDisplayName($displayName);    
        } catch (InvalidArgumentException $e) {
                    $this->assertEquals('$displayName string is too long: must be >=3 and <=64 characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }        
    
    public function testValidatorDisplayName()
    {
        $validate = new Validator();
        $displayName = "MilanMagudia";
        $result = $validate->validateDisplayName($displayName);    
        $this->assertTrue($result);
    } 
    
    public function testValidatorEmailEmpty()
    {
        $validate = new Validator();
        $email = false;
        try {
            $validate->validateEmail($email);    
        } catch (InvalidArgumentException $e) {
                    $this->assertEquals('$email address cannot be empty', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }        

    public function testValidatorEmailInvalid()
    {
        $validate = new Validator();
        $email = "milan.magudia.com";
        try {
            $validate->validateEmail($email);    
        } catch (InvalidArgumentException $e) {
                    $this->assertEquals('$email address is not valid', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }        

    public function testValidatorEmailTooLong()
    {
        $validate = new Validator();
        $email = "1234567890123456789012345678901234567890123456789012345678901234567890";
        try {
            $validate->validateEmail($email);    
        } catch (InvalidArgumentException $e) {
                    $this->assertEquals('$email address is too long: must be <=64 characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }         

    public function testValidatorEmail()
    {
        $validate = new Validator();
        $email = "milan@magudia.com";
        $result = $validate->validateEmail($email);    
        $this->assertTrue($result);
    } 
    
    public function testValidatorAccessIdTooLong()
    {
        $validate = new Validator();
        $accessId = "1234567890123456789012345678901234567890123456789012345678901234567890";
        try {
            $validate->validateAccessId($accessId);
        } catch (InvalidAccessKeyIdException $e) {
                    $this->assertEquals('$accessId is too long: must be 20 alphanumeric characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    public function testValidatorAccessIdTooShort()
    {
        $validate = new Validator();
        $accessId = "1234567890";
        try {
            $validate->validateAccessId($accessId);
        } catch (InvalidAccessKeyIdException $e) {
                    $this->assertEquals('$accessId is too short: must be 20 alphanumeric characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }    

    public function testValidatorAccessIdEmpty()
    {
        $validate = new Validator();
        $accessId = false;
        try {
            $validate->validateAccessId($accessId); 
        } catch (InvalidAccessKeyIdException $e) {
                    $this->assertEquals('$accessId cannot be empty: must be 20 alphanumeric characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }   

    public function testValidatorAccessIdInvalid()
    {
        $validate = new Validator();
        $accessId = "*2!Q%0^E7MXBSH9DHM02";
        try {
            $validate->validateAccessId($accessId);
        } catch (InvalidAccessKeyIdException $e) {
                    $this->assertEquals('$accessId is not valid: must be 20 alphanumeric characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }       
    
    public function testValidatorAccessId() 
    {
        $validate = new Validator();
        $accessId = "022QF06E7MXBSH9DHM02";
        $result = $validate->validateAccessId($accessId);
        $this->assertTrue($result);        
    }
    
    public function testValidatorSecretKeyTooLong()
    {
        $validate = new Validator();
        $secretKey = "1234567890123456789012345678901234567890123456789012345678901234567890";
        try {
            $validate->validateSecretKey($secretKey);
        } catch (InvalidArgumentException $e) {
            $this->assertEquals('$secretKey is too long: must be 40 characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    public function testValidatorSecretKeyTooShort()
    {
        $validate = new Validator();
        $secretKey = "1234567890";
        try {
            $validate->validateSecretKey($secretKey);
        } catch (InvalidArgumentException $e) {
            $this->assertEquals('$secretKey is too short: must be 40 characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }    

    public function testValidatorSecretKeyEmpty()
    {
        $validate = new Validator();
        $secretKey = false;
        try {
            $validate->validateSecretKey($secretKey); 
        } catch (InvalidArgumentException $e) {
            $this->assertEquals('$secretKey cannot be empty: must be 40 characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }   

    public function testValidatorSecretKeyInvalid()
    {
        $validate = new Validator();
        $secretKey = 'kWcrlUX5JEDGM/LtmE NI/aVmYvHNif5zB+d9+ct';
        try {
            $validate->validateSecretKey($secretKey); 
        } catch (InvalidArgumentException $e) {
            $this->assertEquals('$secretKey is not valid: must be 40 characters', $e->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }   
    
    public function testValidatorSecretKey() 
    {
        $validate = new Validator();
        $secretKey = 'kWcrlUX5JEDGM/LtmEENI/aVmYvHNif5zB+d9+ct';
        $result = $validate->validateSecretKey($secretKey);
        $this->assertTrue($result);        
    }    
}
?>