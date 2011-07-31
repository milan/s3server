<?php
/**
 * Storage
 * 
 * The guts of the application this class provides database access via
 * PDO and will be used by the REST and SOAP services. Probably needs
 * a bit of refactoring!
 *
 * PHP versions 5 or more
 *
 *  SimpleStorageService is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with SimpleStorageService; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category   PHP
 * @package    SimpleStorageService
 * @author     Milan Magudia <resume@magudia.com>
 * @license    http://gnu.org/copyleft/gpl.html GNU GPL
 * @version    0.2.0
 * @link       http://blog.magudia.com/category/simplestorageservice/
 */

require_once 'validator.php';
require_once 'accessControlPolicy.php';
require_once 'exceptions.php';

class Storage extends PDO
{
    private static $_dbh;
    private $_username = "s3server";
    private $_password = "password";
    private $_server = "mysql";
    private $_dbname = "s3";
    private $_host = "localhost";
    public $locationConstraint = array ('US', 'EU' );
    
    public function __construct()
    {
    }
    
    public function connect($dbh = false)
    {
        if (empty($dbh)) {
            self::$_dbh = new PDO($this->_server . ':host=' . $this->_host . ';dbname=' . $this->_dbname, $this->_username, $this->_password);
        } else {
            self::$_dbh = $dbh;
        }
    }
    
    private function _runQuery($sql, $data = false)
    {
        $result = false;
        $dbh = self::$_dbh;
        if (!empty($data)) {
            try {
                $sth = $dbh->prepare($sql, array (PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY ));
                $sth->execute($data);
                $err = $sth->errorInfo();
                if (count($err) >= 2) {
                    throw new Exception("Query Exception: " . $err [2]);
                }
                $result = $sth->fetchAll();
            } catch (PDOException $e) {
                echo ("Query Exception: " . $e->getMessage());
            }
        }
        return $result;
    }
    
    private function _getUserFromAccessID($accessId)
    {
        $validator = new Validator();
        $validator->validateAccessId($accessId);
        $query = 'select id, display_name, email_address, access_key, secret_key from user where access_key = :accessId';
        $data = array (':accessId' => $accessId );
        $result = $this->_runQuery($query, $data);
        return $result [0];
    }
    
    private function _getUserFromEmail($email)
    {
        $validator = new Validator();
        $validator->validateEmail($email);
        $query = 'select id, display_name, email_address, access_key, secret_key from user where email_address = :email';
        $data = array (':email' => $email );
        $result = $this->_runQuery($query, $data);
        if (!$result) {
            throw new UnresolvableGrantByEmailAddressException();
        }
        if (count($result) > 1) {
            throw new AmbiguousGrantByEmailAddressException();
        }
        return $result [0];
    }
    
    private function _getUserFromCanonicalId($canonicalUser)
    {
        $validator = new Validator();
        $validator->validateCanonicalUser($canonicalUser);
        $query = 'select id, display_name, email_address, access_key, secret_key from user where id = :id';
        $data = array (':id' => $canonicalUser );
        $result = $this->_runQuery($query, $data);
        return $result [0];
    }
    
    private function _calculateSignature($key, $message)
    {
        include_once 'Crypt/HMAC.php';
        $sha1 = new Crypt_HMAC($key, "sha1");
        $hash = $sha1->hash($message);
        $signature = false;
        // borrowed from: http://neurofuzzy.net/2006/08/26/amazon-s3-php-class-update/
        for ($i = 0; $i < strlen($hash); $i += 2) {
            $signature .= chr(hexdec(substr($hash, $i, 2)));
        }
        return base64_encode($signature);
    }
    
    private function _getBucket($bucket)
    {
        $validator = new Validator();
        $validator->validateBucketName($bucket);
        $sql = "SELECT b.id, b.name, b.ownerid, b.creation_date, u.display_name FROM `bucket` b, `user` u WHERE u.id = b.ownerid AND name = :name";
        $values = array (':name' => $bucket );
        $result = $this->_runQuery($sql, $values);
        if (!$result) {
            throw new NoSuchBucketException();
        }
        return $result [0];
    }
    
    private function _checkBucketExists($bucket)
    {
        $validator = new Validator();
        $validator->validateBucketName($bucket);
        $sql = "SELECT id, ownerid FROM `bucket` WHERE name = :name";
        $values = array (':name' => $bucket );
        $result = $this->_runQuery($sql, $values);
        if ($result) {
            return $result [0];
        } else {
            return false;
        }
    }
    
    private function _getObjectDetails($bucketId, $object)
    {
        $return = false;
        
        if ($bucketId and $object) {
            $sql = "SELECT `id`, `bucketid`, `key`, `ownerid`, `metadata`, `content_type`, `etag`, `last_updated`, length(value) as `length` FROM `object` WHERE `bucketid` = :bucketid AND `key` = :key";
            $values = array (':bucketid' => $bucketId, ':key' => $object );
            $result = $this->_runQuery($sql, $values);
            
            //var_dump($result);
            
            if ($result) {
                // bit of a hack to keep the SQL simple
                $sql = "SELECT `display_name` FROM `user` WHERE `id` = :userid";
                $values = array (':userid' => $result[0] ['ownerid'] );
                $user = $this->_runQuery($sql, $values);
                if ($user) {
                    $result [0] ['display_name'] = $user [0] ['display_name'];
                }
                $return = $result [0];
            } else {
                throw new NoSuchKeyException();
            }
        }
        return $return;
    }
    
    private function _getObjectId($bucketId, $object)
    {
        $return = false;
        
        if ($bucketId and $object) {
            $sql = "SELECT `id` FROM `object` WHERE `bucketid` = :bucketid AND `key` = :key";
            $values = array (':bucketid' => $bucketId, ':key' => $object );
            $result = $this->_runQuery($sql, $values);
            if ($result) {
                $return = $result [0] ['id'];
            } else {
                return false;
            }
        }
        return $return;
    }
    
    private function _getObjectValue($objectId, $content_length, $rangeStart, $rangeEnd, $ifModifiedSince, $ifUnmodifiedSince, $ifMatch, $ifNoneMatch)
    {
        $return = null;
        
        if ($objectId and $content_length) {
            
            if (isset($rangeStart) and $rangeEnd) {
                if ((($rangeEnd - $rangeStart) <= 0) or ($rangeEnd > $content_length) or ($rangeStart > $rangeEnd) or !is_numeric($rangeStart) or !is_numeric($rangeEnd)) {
                    throw new InvalidRangeException();
                }
                $rangeStart++;
                $selectValue = "substring(value, $rangeStart, $rangeEnd) as value ";
            } else {
                $selectValue = "value";
            }
            
            $sql = "SELECT $selectValue FROM `object` WHERE `id` = :objectid ";
            $values = array (':objectid' => $objectId );
            
            if ($ifMatch) {
                $sql .= "AND `etag` = :etag ";
                $values [':etag'] = $ifMatch;
            } elseif ($ifNoneMatch) {
                $sql .= "AND `etag` != :etag ";
                $values [':etag'] = $ifNoneMatch;
            }
            
            if ($ifModifiedSince) {
                $time = strtotime($ifModifiedSince);
                $timestamp = date('Y-m-d H:i:s', $time);
                $sql .= "AND UNIX_TIMESTAMP(`last_updated`) > UNIX_TIMESTAMP(:timestamp) ";
                $values [':timestamp'] = $timestamp;
            } elseif ($ifUnmodifiedSince) {
                $time = strtotime($ifUnmodifiedSince);
                $timestamp = date('Y-m-d H:i:s', $time);
                $sql .= "AND UNIX_TIMESTAMP(`last_updated`) <= UNIX_TIMESTAMP(:timestamp) ";
                $values [':timestamp'] = $timestamp;
            }
            
            $result = $this->_runQuery($sql, $values);
            if ($result) {
                $return = $result [0] ['value'];
            }
        }
        return $return;
    }
    
    private function _getUser($accessId)
    {
        $result = false;
        if (preg_match("/@/", $accessId)) {
            $result = $this->_getUserFromEmail($accessId);
        } else {
            $result = $this->_getUserFromAccessID($accessId);
        }
        if (!$result) {
            throw new InvalidAccessKeyIdException();
        }
        return $result;
    }
    
    private function _generateID($key)
    {
        // until I find something better this will do
        $key = $key . time();
        
        return hash('sha256', $key);
    }
    
    private function _getACL($type)
    {
        $sql = "SELECT `id` FROM `acl` WHERE `type` = :type";
        $value = array (':type' => strtoupper($type) );
        $result = $this->_runQuery($sql, $value);
        $aclid = $result [0] ['id'];
        return $aclid;
    }
    
    private function _getGroup($type)
    {
        $sql = "SELECT `id` FROM `group` WHERE `type` = :type";
        $value = array (':type' => strtoupper($type) );
        $result = $this->_runQuery($sql, $value);
        $aclid = $result [0] ['id'];
        return $aclid;
    }
    
    private function _getBucketACL($bucketId)
    {
        $result = false;
        if ($bucketId) {
            $sql = "SELECT ub.userid, a.type, u.display_name FROM user_bucket as ub, acl as a, user u WHERE u.id = ub.userid AND a.id = ub.aclid AND ub.bucketid = :id";
            $values = array (':id' => $bucketId );
            $result1 = $this->_runQuery($sql, $values);
            $sql = "SELECT g.type as `group`, a.type FROM `group` as g, user_bucket as ub, acl as a WHERE g.id = ub.groupid AND a.id = ub.aclid AND ub.bucketid = :id";
            $values = array (':id' => $bucketId );
            $result2 = $this->_runQuery($sql, $values);
            $result = array_merge($result1, $result2);
        }
        return $result;
    }
    
    private function _getObjectACL($objectId)
    {
        $result = false;
        if ($objectId) {
            $sql = "SELECT uo.userid, a.type, u.display_name FROM user_object as uo, acl as a, user u WHERE u.id = uo.userid AND a.id = uo.aclid AND uo.objectid = :id";
            $values = array (':id' => $objectId );
            $result1 = $this->_runQuery($sql, $values);
            $sql = "SELECT g.type as `group`, a.type FROM `group` as g, user_object as uo, acl as a WHERE g.id = uo.groupid AND a.id = uo.aclid AND uo.objectid = :id";
            $values = array (':id' => $objectId );
            $result2 = $this->_runQuery($sql, $values);
            $result = array_merge($result1, $result2);
        }
        return $result;
    }
    
    private function _getUserObjectsAcl($userId)
    {
        $result = false;
        if ($userId) {
            $sql = "SELECT uo.objectid, a.type FROM user_object as uo, acl as a WHERE a.id = uo.aclid AND uo.userid = :id";
            $values = array (':id' => $userId );
            $result = $this->_runQuery($sql, $values);
        }
        return $result;
    }
    
    private function _checkACLPermission($acl, $id, $type)
    {
        $result = null;
        if ($acl) {
            foreach ($acl as $row) {
                //var_dump($row);
                if (($id == $row ['userid']) or ($id == $row ['objectid'])) {
                    if (($row ['type'] == 'FULL_CONTROL') or ($row ['type'] == strtoupper($type))) {
                        $result = true;
                    }
                }
                if ($row ['group'] == 'AllUsers') {
                    if ($id == 'anonymous') {
                        if (($row ['type'] == 'FULL_CONTROL') or ($row ['type'] == strtoupper($type))) {
                            $result = true;
                        }
                    }
                }
                if ($row ['group'] == 'AuthenticatedUsers') {
                    if (($row ['type'] == 'FULL_CONTROL') or ($row ['type'] == strtoupper($type))) {
                        $result = true;
                    }
                }
            }
        }
        return $result;
    }
    
    private function _getBucketDetails($bucketId, $prefix = false, $marker = false, $delimiter = false, $maxKeys = false)
    {
        
        //$result = false;
        if ($bucketId) {
            $sql = "SELECT o.id, o.key, o.last_updated, md5(o.value) as etag, length(o.value) as size, o.ownerid, u.display_name FROM `object` as o, `user` as u, `bucket` b WHERE b.id = o.bucketid AND u.id = o.ownerid AND o.bucketid = :bucketid ";
            
            $values = array (':bucketid' => $bucketId );
            
            if ($prefix) {
                $sql .= "AND o.key LIKE :prefix ";
                $values [':prefix'] = $prefix . '%';
            }
            
            if ($marker) {
                $sql .= "AND o.key > :marker ";
                $values [':marker'] = $marker;
            }
            
            if ($delimiter) {
                $sql .= " ";
            }
            
            $sql .= " ORDER BY o.key ";
            
            if ($maxKeys) {
                if (is_numeric($maxKeys) and $maxKeys >= 0) {
                    $sql .= "LIMIT 0, $maxKeys ";
                } else {
                    throw new InvalidArgument();
                }
            }
            
            $result = $this->_runQuery($sql, $values);
        }
        return $result;
    }
    
    private function _isBucketEmpty($bucketid)
    {
        $sql = "select count(bucketid) from object where bucketid = :bucketid";
        $values = array (':bucketid' => $bucketid );
        $result = $this->_runQuery($sql, $values);
        if ($result [0] [0]) {
            $bucketEmpty = false;
        } else {
            $bucketEmpty = true;
        }
        
        return $bucketEmpty;
    }
    
    // user admin functions
    public function createUser($displayName, $email, $accessId, $secretKey)
    {
        $validate = new Validator();
        //$validate->validateCanonicalUser ( $canonicalUser );
        $validate->validateDisplayName($displayName);
        $validate->validateEmail($email);
        $validate->validateAccessId($accessId);
        $validate->validateSecretKey($secretKey);
        $canonicalUser = $this->_generateID($email);
        
        $query = "INSERT INTO `user` (`id`, `display_name`, `email_address`, `access_key`, `secret_key`) VALUES
                     (:access_id, :display_name, :email_address, :access_key, :secret_key)";
        $data = array (':access_id' => $canonicalUser, ':display_name' => $displayName, ':email_address' => $email, ':access_key' => $accessId, ':secret_key' => $secretKey );
        $this->_runQuery($query, $data);
        return true;
    }
    
    public function deleteUser()
    {
    }
    
    // should this return user?
    public function authenticateUser($accessId, $signature, $stringToSign, $requireAuthentication = false)
    {
        $result = null;
        
        if ($accessId or $signature) {
            $secretKey = null;
            $user = $this->_getUser($accessId);
            if ($user) {
                $secretKey = $user ['secret_key'];
            }
            
            if ($secretKey) {
                $calculatedSignature = $this->_calculateSignature($secretKey, $stringToSign);
                if ($calculatedSignature == $signature) {
                    $result = $user;
                } else {
                    throw new SignatureDoesNotMatchException();
                }
            }
        }
        
        if ($requireAuthentication and !$result) {
            throw new MissingSecurityHeaderException();
        } elseif (!$requireAuthentication and !$result) {
            $result = array ('id' => 'anonymous' );
        }
        
        return $result;
    }
    
    // general s3 functions
    public function createBucket($bucket, $location, $accessId, $signature, $stringToSign)
    {
        $result = null;
        
        $validator = new Validator();
        $validator->validateBucketName($bucket);
        $validUser = $this->authenticateUser($accessId, $signature, $stringToSign, true);
        $check = $this->_checkBucketExists($bucket);
        
        if ($check) {
            if ($validUser ['id'] == $check ['ownerid']) {
                throw new BucketAlreadyOwnedByYouException();
            } else {
                throw new BucketAlreadyExistsException();
            }
        } else {
            $ownerId = $validUser ['id'];
            $bucketId = $this->_generateID($bucket);
            $bucket_sql = "INSERT INTO bucket (`id`, `location`, `name`, `ownerid`) VALUES (:id, :location, :name, :ownerid)";
            $bucket_values = array (':ownerid' => $ownerId, ':name' => $bucket, ':id' => $bucketId, ':location' => $location );
            $this->_runQuery($bucket_sql, $bucket_values);
            
            $acl_id = $this->_getACL('FULL_CONTROL');
            $acl_sql = "INSERT INTO `user_bucket` (`userid`, `bucketid`, `aclid`) VALUES (:userid, :bucketid, :aclid)";
            $acl_values = array (':userid' => $ownerId, ':bucketid' => $bucketId, ':aclid' => $acl_id );
            $this->_runQuery($acl_sql, $acl_values);
            
            $result = $bucketId;
        }
        
        return $result;
    }
    
    public function deleteBucket($bucket, $accessId, $signature, $stringToSign)
    {
        $result = null;
        $validUser = $this->authenticateUser($accessId, $signature, $stringToSign, true);
        $check = $this->_getBucket($bucket);
        
        $userid = $validUser ['id'];
        $ownerid = $check ['ownerid'];
        
        // Only owner can delete bucket
        if ($userid == $ownerid) {
            $bucketid = $check ['id'];
            $emptyBucket = $this->_isBucketEmpty($bucketid);
            
            if ($emptyBucket) {
                $this->_runQuery("DELETE FROM `bucket` WHERE `id` = :id", array (':id' => $bucketid ));
                $this->_runQuery("DELETE FROM `user_bucket` WHERE bucketid = :id", array (':id' => $bucketid ));
                $result = $bucketid;
            } else {
                throw new BucketNotEmptyException();
            }
        } else {
            throw new AccessDeniedException();
        }
        
        return $result;
    }
    
    public function listBucket($bucket, $prefix, $marker, $maxKeys, $delimiter, $accessId, $signature, $stringToSign)
    {
        $result = null;
        $validUser = $this->authenticateUser($accessId, $signature, $stringToSign);
        $bucketDetails = $this->_getBucket($bucket);
        $bucketId = $bucketDetails ['id'];
        $buckerOwnerId = $bucketDetails ['ownerid'];
        $userId = $validUser ['id'];
        $acl = $this->_getBucketACL($bucketId);
        $allowedUser = $this->_checkACLPermission($acl, $userId, "READ"); // check userId is in ACL as READ then ...
        

        if ($allowedUser) {
            $objects = $this->_getBucketDetails($bucketId, $prefix, $marker, $delimiter, $maxKeys);
            $user_acl = $this->_getUserObjectsAcl($userId);
            
            $result = array ( );
            $result ['Name'] = $bucket;
            if ($prefix) {
                $result ['Prefix'] = $prefix;
            }
            if ($marker) {
                $result ['Marker'] = $marker;
            }
            if ($maxKeys) {
                $result ['MaxKeys'] = $maxKeys;
            }
            if ($delimiter) {
                $result ['Delimiter'] = $delimiter;
            }
            $result ['IsTruncated'] = false;
            $result ['bucketId'] = $bucketId;
            $result ['Contents'] = array ( );
            
            if ($objects) {
                foreach ($objects as $object) {
                    $contents = array ( );
                    $contents ['Key'] = $object ['key'];
                    $contents ['LastModified'] = $object ['last_updated'];
                    $contents ['ETag'] = '"' . $object ['etag'] . '"';
                    $contents ['Size'] = $object ['size'];
                    
                    $accessObjectForUser = $this->_checkACLPermission($user_acl, $object ['id'], 'READ_ACP');
                    if ($accessObjectForUser or ($userId == $buckerOwnerId)) {
                        $contents ['Owner'] = array ( );
                        $contents ['Owner'] ['ID'] = $object ['ownerid'];
                        if ($object ['display_name']) {
                            $contents ['Owner'] ['DisplayName'] = $object ['display_name'];
                        }
                    }
                    array_push($result ['Contents'], $contents);
                }
            }
        } else {
            throw new AccessDeniedException();
        }
        
        return $result;
    }
    
    public function getBucketAccessControlPolicy($bucket, $accessId, $signature, $stringToSign)
    {
        $validUser = $this->authenticateUser($accessId, $signature, $stringToSign);
        $acp = new AccessControlPolicy();
        $bucketDetails = $this->_getBucket($bucket);
        $acl = $this->_getBucketACL($bucketDetails ['id']);
        $validACL = $this->_checkACLPermission($acl, $validUser ['id'], 'READ_ACP');
        
        if ($validACL) {
            $owner = new Owner();
            $owner->setDisplayName($bucketDetails ['display_name']);
            $owner->setId($bucketDetails ['ownerid']);
            
            $acp->setOwner($owner);
            
            foreach ($acl as $permission) {
                $grant = new Grant();
                $grantee = new Grantee();
                if ($permission ['userid']) {
                    $grantee->setDisplayName($permission ['display_name']);
                    $grantee->setId($permission ['userid']);
                    $grantee->setType('CanonicalUser');
                } elseif ($permission ['group']) {
                    $grantee->setUri('http://acs.amazonaws.com/groups/global/' . $permission ['group']);
                    $grantee->setType('Group');
                }
                $grant->setGrantee($grantee);
                $grant->setProtection($permission ['type']);
                
                $acp->addGrant($grant);
            }
        } else {
            throw new AccessDeniedException();
        }
        
        return $acp;
    
    }
    
    public function setBucketAccessControlPolicy($bucket, AccessControlPolicy $acp, $accessId, $signature, $stringToSign)
    {
        $result = null;
        $validUser = $this->authenticateUser($accessId, $signature, $stringToSign);
        $bucketDetails = $this->_getBucket($bucket);
        $bucketId = $bucketDetails ['id'];
        
        // owner always have permission to write acl
        if ($bucketDetails ['ownerid'] == $validUser ['id']) {
            $validACL = true;
        } else {
            $access = $this->_getBucketACL($bucketId);
            $validACL = $this->_checkACLPermission($access, $validUser ['id'], "WRITE_ACP");
        }
        
        if ($validACL) {
            
            // need to check the grant list first for errors - i.e. check before commit
            $grants = $acp->getGrantList();
            foreach ($grants as $grant) {
                $aclid = $this->_getACL($grant->getProtection());
                if ($aclid) {
                    $grantee = $grant->getGrantee();
                    $type = $grantee->getType();
                    switch (strtolower($type)) {
                    case strtolower('CanonicalUser') :
                        if (!$this->_getUserFromCanonicalId($grantee->getId())) {
                            throw new InvalidPolicyDocumentException();
                        }
                        break;
                    case strtolower('AmazonCustomerByEmail') :
                        if (!$this->_getUserFromEmail($grantee->getEmail())) {
                            throw new InvalidPolicyDocumentException();
                        }
                        break;
                    case strtolower('Group') :
                        $uri = $grantee->getUri();
                        switch (strtolower($uri)) {
                        case strtolower('http://acs.amazonaws.com/groups/global/AuthenticatedUsers') :
                            break;
                        case strtolower('http://acs.amazonaws.com/groups/global/AllUsers') :
                            break;
                        default :
                            throw new InvalidPolicyDocumentException();
                            break;
                        }
                        break;
                    default :
                        throw new InvalidPolicyDocumentException();
                        break;
                    }
                
                } else {
                    throw new InvalidPolicyDocumentException();
                }
            }
            
            // should be ok to clear bucket acl by this point
            $sql = "DELETE FROM user_bucket WHERE bucketid = :bucketid";
            $values = array (':bucketid' => $bucketId );
            $this->_runQuery($sql, $values);
            
            foreach ($grants as $grant) {
                $aclid = $this->_getACL($grant->getProtection());
                $grantee = $grant->getGrantee();
                $type = $grantee->getType();
                switch (strtolower($type)) {
                case strtolower('CanonicalUser') :
                    $sql = "INSERT INTO `user_bucket` (`userid`,`bucketid`,`aclid`) VALUES (:userid, :bucketid, :aclid)";
                    $values = array (':userid' => $grantee->getId(), ':bucketid' => $bucketId, ':aclid' => $aclid );
                    $this->_runQuery($sql, $values);
                    break;
                case strtolower('AmazonCustomerByEmail') :
                    $user = $this->_getUserFromEmail($grantee->getEmail());
                    $id = $user ['id'];
                    $sql = "INSERT INTO `user_bucket` (`userid`,`bucketid`,`aclid`) VALUES (:userid, :bucketid, :aclid)";
                    $values = array (':userid' => $id, ':bucketid' => $bucketId, ':aclid' => $aclid );
                    $this->_runQuery($sql, $values);
                    break;
                case strtolower('Group') :
                    $uri = $grantee->getUri();
                    switch (strtolower($uri)) {
                    case strtolower('http://acs.amazonaws.com/groups/global/AuthenticatedUsers') :
                        $groupid = $this->_getGroup('AuthenticatedUsers');
                        $sql = "INSERT INTO `user_bucket` (`groupid`,`bucketid`,`aclid`) VALUES (:groupid, :bucketid, :aclid)";
                        $values = array (':groupid' => $groupid, ':bucketid' => $bucketId, ':aclid' => $aclid );
                        $this->_runQuery($sql, $values);
                        break;
                    case strtolower('http://acs.amazonaws.com/groups/global/AllUsers') :
                        $groupid = $this->_getGroup('AllUsers');
                        $sql = "INSERT INTO `user_bucket` (`groupid`,`bucketid`,`aclid`) VALUES (:groupid, :bucketid, :aclid)";
                        $values = array (':groupid' => $groupid, ':bucketid' => $bucketId, ':aclid' => $aclid );
                        $this->_runQuery($sql, $values);
                        break;
                    }
                    break;
                }
            }
            
            $result = true;
        } else {
            throw new AccessDeniedException();
        }
        
        return $result;
    
    }
    
    //public function getBucketLoggingStatus($bucket, $accessId, $signature, $stringToSign) {
    //}
    

    //public function setBucketLoggingStatus($bucket, $targetBucket, $targetPrefix, $accessId, $signature, $stringToSign) {
    //}
    

    public function getBucketLocation($bucket, $accessId, $signature, $stringToSign)
    {
        
        $result = null;
        
        $validUser = $this->authenticateUser($accessId, $signature, $stringToSign);
        $bucketDetails = $this->_getBucket($bucket);
        $userid = $validUser ['id'];
        $ownerid = $bucketDetails ['ownerid'];
        
        // Only owner can delete bucket
        if ($userid == $ownerid) {
            $bucketid = $bucketDetails ['id'];
            $location = $this->_runQuery("SELECT `location` FROM `bucket` WHERE `id` = :id", array (':id' => $bucketid ));
            $result = $location [0] ['location'];
        } else {
            throw new AccessDeniedException();
        }
        
        return $result;
    }
    
    public function listAllMyBuckets($accessId, $signature, $stringToSign)
    {
        
        $buckets = null;
        $validUser = $this->authenticateUser($accessId, $signature, $stringToSign, true);
        
        if ($validUser) {
            $ownerid = $validUser ['id'];
            $sql = "SELECT name, creation_date FROM bucket WHERE ownerid = :ownerid";
            $values = array (':ownerid' => $ownerid );
            $results = $this->_runQuery($sql, $values);
            
            $buckets = array ( );
            $buckets ['owner'] ['id'] = $ownerid;
            $buckets ['owner'] ['displayName'] = $validUser ['display_name'];
            $buckets ['buckets'] = $results;
        
        } else {
            throw new AccessDeniedException();
        }
        return $buckets;
    }
    
    public function putObject($bucket, $object, $value = '', $metadata = '', $content_type = '', $accessId = false, $signature = false, $stringToSign = false)
    {
        $result = null;
        
        $validUser = $this->authenticateUser($accessId, $signature, $stringToSign);
        $bucketDetails = $this->_getBucket($bucket);
        $bucketId = $bucketDetails ['id'];
        $acl = $this->_getBucketACL($bucketDetails ['id']);
        $validACL = $this->_checkACLPermission($acl, $validUser ['id'], 'WRITE');
        
        if ($validACL) {
            $objectId = $this->_getObjectId($bucketId, $object);
            
            if ($objectId) {
                $sql1 = "DELETE FROM object WHERE `id` = :id";
                $values1 = array (':id' => $objectId );
                $this->_runQuery($sql1, $values1);
                
                $sql2 = "DELETE FROM `user_object` WHERE objectid = :objectid";
                $values2 = array (':objectid' => $objectId );
                $this->_runQuery($sql2, $values2);
            }
            
            $objectId = $this->_generateID($object);
            
            if (!$content_type) {
                $content_type = 'binary/octet-stream';
            }
            
            $sql = "INSERT INTO `object` (`id`, `key`, `bucketid`, `ownerid`, `value`, `metadata`, `content_type`, `etag`) VALUES (:id, :key, :bucketid, :ownerid, :value, :metadata, :content_type, :etag)";
            $values = array (':id' => $objectId, ':key' => $object, ':bucketid' => $bucketDetails ['id'], ':ownerid' => $validUser ['id'], ':value' => $value, ':metadata' => serialize($metadata), ':content_type' => $content_type, ':etag' => md5($value) );
            $this->_runQuery($sql, $values);
            
            $sql_acl = "INSERT INTO `user_object` (userid, objectid, aclid) VALUES (:userid, :objectid, :aclid)";
            $values_acl = array (':userid' => $validUser ['id'], ':objectid' => $objectId, ':aclid' => $this->_getACL('FULL_CONTROL') );
            $this->_runQuery($sql_acl, $values_acl);
            
            $result = $objectId;
        } else {
            throw new AccessDeniedException();
        }
        
        return $result;
    }
    
    public function getObject($bucket, $object, $accessId, $signature, $stringToSign)
    {
        return $this->getObjectExtended($bucket, $object, true, false, false, false, false, false, false, $accessId, $signature, $stringToSign);
    }
    
    public function getObjectExtended($bucket, $object, $returnObjectContents, $rangeStart, $rangeEnd, $ifModifiedSince, $ifUnmodifiedSince, $ifMatch, $ifNoneMatch, $accessId, $signature, $stringToSign)
    {
        $result = null;
        
        $validUser = $this->authenticateUser($accessId, $signature, $stringToSign);
        $bucketDetails = $this->_getBucket($bucket);
        $userId = $validUser ['id'];
        $bucketId = $bucketDetails ['id'];
        $objectDetails = $this->_getObjectDetails($bucketId, $object);
        
        if ($objectDetails) {
            $objectId = $objectDetails ['id'];
            $acl = $this->_getObjectACL($objectId);
            $validACL = $this->_checkACLPermission($acl, $userId, "READ");
            
            if ($validACL and $returnObjectContents) {
                $content_length = $objectDetails ['length'];
                $objectDetails ['value'] = $this->_getObjectValue($objectId, $content_length, $rangeStart, $rangeEnd, $ifModifiedSince, $ifUnmodifiedSince, $ifMatch, $ifNoneMatch);
                $result = $objectDetails;
            } else if ($validACL) {
                $result = $objectDetails;
            } else {
                throw new AccessDeniedException();
            }
        }
        
        return $result;
    }
    
    public function deleteObject($bucket, $object, $accessId, $signature, $stringToSign)
    {
        $result = null;
        
        $validUser = $this->authenticateUser($accessId, $signature, $stringToSign);
        $bucketDetails = $this->_getBucket($bucket);
        $bucketId = $bucketDetails ['id'];
        $acl = $this->_getBucketACL($bucketId);
        $validACL = $this->_checkACLPermission($acl, $validUser ['id'], 'WRITE');
        
        if ($validACL) {
            $objectId = $this->_getObjectId($bucketId, $object);
            
            if ($objectId) {
                $sql1 = "DELETE FROM object WHERE `id` = :id";
                $values1 = array (':id' => $objectId );
                $this->_runQuery($sql1, $values1);
                
                $sql2 = "DELETE FROM `user_object` WHERE objectid = :objectid";
                $values2 = array (':objectid' => $objectId );
                $this->_runQuery($sql2, $values2);
                
                $result = $objectId;
            }
        } else {
            throw new AccessDeniedException();
        }
        
        return $result;
    }
    
    
    // add no such key exception
    public function getObjectAccessControlPolicy($bucket, $object, $accessId, $signature, $stringToSign)
    {
        $acp = new AccessControlPolicy();
        $validUser = $this->authenticateUser($accessId, $signature, $stringToSign);
        $bucketDetails = $this->_getBucket($bucket);
        $userId = $validUser ['id'];
        $bucketId = $bucketDetails ['id'];
        $objectDetails = $this->_getObjectDetails($bucketId, $object);
        $objectId = $objectDetails ['id'];
        $ownerId = $objectDetails ['ownerid'];
        $acl = $this->_getObjectACL($objectId);
        
        // owner always have permission to read acl
        if ($ownerId == $userId) {
            $validACL = true;
        } else {
            $validACL = $this->_checkACLPermission($acl, $userId, "READ_ACP");
        }        
        
        if ($validACL) {
            $owner = new Owner();
            $owner->setDisplayName($bucketDetails ['display_name']);
            $owner->setId($ownerId);
            
            $acp->setOwner($owner);
            
            foreach ($acl as $permission) {
                $grant = new Grant();
                $grantee = new Grantee();
                if ($permission ['userid']) {
                    $grantee->setDisplayName($permission ['display_name']);
                    $grantee->setId($permission ['userid']);
                    $grantee->setType('CanonicalUser');
                } elseif ($permission ['group']) {
                    $grantee->setUri('http://acs.amazonaws.com/groups/global/' . $permission ['group']);
                    $grantee->setType('Group');
                }
                $grant->setGrantee($grantee);
                $grant->setProtection($permission ['type']);
                
                $acp->addGrant($grant);
            }
        } else {
            throw new AccessDeniedException();
        }

        return $acp;
    }
    
    // note objects do not have WRITE acl
    public function setObjectAccessControlPolicy($bucket, $object, AccessControlPolicy $acp, $accessId, $signature, $stringToSign)
    {
        $validUser = $this->authenticateUser($accessId, $signature, $stringToSign);
        $result = null;
        
        if ($validUser) {
            $bucketDetails = $this->_getBucket($bucket);
            $userId = $validUser ['id'];
            $bucketId = $bucketDetails ['id'];
            $objectDetails = $this->_getObjectDetails($bucketId, $object);
            $objectId = $objectDetails ['id'];
            $ownerId = $objectDetails ['ownerid'];
            
            // owner always have permission to write acl
            if ($ownerId == $userId) {
                $validACL = true;
            } else {
                $access = $this->_getObjectACL($objectId);
                $validACL = $this->_checkACLPermission($access, $userId, "WRITE_ACP");
            }
            
            if ($validACL) {
                // need to check the grant list first for errors - i.e. check before commit
                $grants = $acp->getGrantList();
                foreach ($grants as $grant) {
                    
                    // objects do not have WRITE acl
                    if ($grant->getProtection() == 'WRITE') {
                        throw new InvalidPolicyDocumentException();
                    }
                    
                    $aclid = $this->_getACL($grant->getProtection());
                    if ($aclid) {
                        $grantee = $grant->getGrantee();
                        $type = $grantee->getType();
                        switch (strtolower($type)) {
                        case strtolower('CanonicalUser') :
                            if (!$this->_getUserFromCanonicalId($grantee->getId())) {
                                throw new InvalidPolicyDocumentException();
                            }
                            break;
                        case strtolower('AmazonCustomerByEmail') :
                            if (!$this->_getUserFromEmail($grantee->getEmail())) {
                                throw new InvalidPolicyDocumentException();
                            }
                            break;
                        case strtolower('Group') :
                            $uri = $grantee->getUri();
                            switch (strtolower($uri)) {
                            case strtolower('http://acs.amazonaws.com/groups/global/AuthenticatedUsers') :
                                break;
                            case strtolower('http://acs.amazonaws.com/groups/global/AllUsers') :
                                break;
                            default :
                                throw new InvalidPolicyDocumentException();
                                break;
                            }
                            break;
                        default :
                            throw new InvalidPolicyDocumentException();
                            break;
                        }
                    } else {
                        throw new InvalidPolicyDocumentException();
                    }
                }
                
                // clear bucket acl
                $sql = "DELETE FROM user_object WHERE objectid = :objectid";
                $values = array (':objectid' => $objectId );
                $this->_runQuery($sql, $values);
                
                foreach ($grants as $grant) {
                    $aclid = $this->_getACL($grant->getProtection());
                    $grantee = $grant->getGrantee();
                    $type = $grantee->getType();
                    switch (strtolower($type)) {
                    case strtolower('CanonicalUser') :
                        $sql = "INSERT INTO `user_object` (`userid`,`objectid`,`aclid`) VALUES (:userid, :objectid, :aclid)";
                        $values = array (':userid' => $grantee->getId(), ':objectid' => $objectId, ':aclid' => $aclid );
                        $this->_runQuery($sql, $values);
                        break;
                    case strtolower('AmazonCustomerByEmail') :
                        $user = $this->_getUserFromEmail($grantee->getEmail());
                        $id = $user ['id'];
                        $sql = "INSERT INTO `user_object` (`userid`,`objectid`,`aclid`) VALUES (:userid, :objectid, :aclid)";
                        $values = array (':userid' => $id, ':objectid' => $objectId, ':aclid' => $aclid );
                        $this->_runQuery($sql, $values);
                        break;
                    case strtolower('Group') :
                        $uri = $grantee->getUri();
                        switch (strtolower($uri)) {
                        case strtolower('http://acs.amazonaws.com/groups/global/AuthenticatedUsers') :
                            $groupid = $this->_getGroup('AuthenticatedUsers');
                            $sql = "INSERT INTO `user_object` (`groupid`,`objectid`,`aclid`) VALUES (:groupid, :objectid, :aclid)";
                            $values = array (':groupid' => $groupid, ':objectid' => $objectId, ':aclid' => $aclid );
                            $this->_runQuery($sql, $values);
                            break;
                        case strtolower('http://acs.amazonaws.com/groups/global/AllUsers') :
                            $groupid = $this->_getGroup('AllUsers');
                            $sql = "INSERT INTO `user_object` (`groupid`,`objectid`,`aclid`) VALUES (:groupid, :objectid, :aclid)";
                            $values = array (':groupid' => $groupid, ':objectid' => $objectId, ':aclid' => $aclid );
                            $this->_runQuery($sql, $values);
                            break;
                        }
                        break;
                    }
                }
                
                $result = true;
            
            } else {
                throw new AccessDeniedException();
            }
        } else {
            throw new InternalErrorException();
        }
        
        return $result;
    }
}
?>