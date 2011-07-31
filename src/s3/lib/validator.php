<?php
/**
 * Validator
 * 
 * Simple set of tests used internally to check various inputs to the 
 * service.
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

include_once 'exceptions.php';
class Validator
{
    
    public function validateBucketId($bucketId)
    {
        return $this->validateId($bucketId, '$bucketId');
    }
    
    public function validateUserId($userId)
    {
        return $this->validateId($userId, '$userId');
    }
    
    public function validateCanonicalUser($canonicalUser)
    {
        $result = $this->validateId($canonicalUser, '$canonicalUser');
        if (!preg_match('/^[0-9a-z]+$/', $canonicalUser)) {
            throw new InvalidArgumentException('$canonicalUser string is not valid: must be 64 alphanumeric characters');
        }
        return $result;
    }
    
    private function validateId($id, $name)
    {
        if (empty($id)) {
            throw new InvalidArgumentException("$name cannot be empty: must be 64 alphanumeric characters");
        }
        if (strlen($id) < 64) {
            throw new InvalidArgumentException("$name string is too short: must be 64 alphanumeric characters");
        }
        if (strlen($id) > 64) {
            throw new InvalidArgumentException("$name string is too long: must be 64 alphanumeric characters");
        }
        if (!preg_match('/^[0-9A-Za-z\/\+]+$/', $id)) {
            throw new InvalidArgumentException("$name string is not valid: must be 64 alphanumeric characters");
        }
        return true;
    }
    
    public function validateDisplayName($displayName)
    {
        $displayName = trim($displayName);
        if (empty($displayName)) {
            throw new InvalidArgumentException('$displayName cannot be empty: must be >=3 and <=64 characters');
        }
        if (strlen($displayName) < 3) {
            throw new InvalidArgumentException('$displayName string is too short: must be >=3 and <=64 characters');
        }
        if (strlen($displayName) > 64) {
            throw new InvalidArgumentException('$displayName string is too long: must be >=3 and <=64 characters');
        }
        return true;
    }
    
    public function validateEmail($email)
    {
        if (empty($email)) {
            throw new InvalidArgumentException('$email address cannot be empty');
        }
        if (strlen($email) > 64) {
            throw new InvalidArgumentException('$email address is too long: must be <=64 characters');
        }
        if (!preg_match('/^[\w-]+(\.[\w-]+)*@([a-z0-9-]+(\.[a-z0-9-]+)*?\.[a-z]{2,6}|(\d{1,3}\.){3}\d{1,3})(:\d{4})?$/', $email)) {
            throw new InvalidArgumentException('$email address is not valid');
        }
        return true;
    }
    
    public function validateAccessId($accessId)
    {
        if (empty($accessId)) {
            throw new InvalidAccessKeyIdException('$accessId cannot be empty: must be 20 alphanumeric characters');
        }
        if (strlen($accessId) < 20) {
            throw new InvalidAccessKeyIdException('$accessId is too short: must be 20 alphanumeric characters');
        }
        if (strlen($accessId) > 20) {
            throw new InvalidAccessKeyIdException('$accessId is too long: must be 20 alphanumeric characters');
        }
        if (!preg_match('/^[0-9A-Z]+$/', $accessId)) {
            throw new InvalidAccessKeyIdException('$accessId is not valid: must be 20 alphanumeric characters');
        }
        return true;
    }
    
    public function validateSecretKey($secretKey)
    {
        if (empty($secretKey)) {
            throw new InvalidArgumentException('$secretKey cannot be empty: must be 40 characters');
        }
        if (strlen($secretKey) > 40) {
            throw new InvalidArgumentException('$secretKey is too long: must be 40 characters');
        }
        if (strlen($secretKey) < 40) {
            throw new InvalidArgumentException('$secretKey is too short: must be 40 characters');
        }
        if (!preg_match('/^[\S]+$/', $secretKey)) {
            throw new InvalidArgumentException('$secretKey is not valid: must be 40 characters');
        }
        return true;
    }
    
    public function validateBucketName($bucket)
    {
        //$bucket = trim($bucket);
        if (empty($bucket)) {
            throw new InvalidBucketNameException();
        }
        if (strlen($bucket) < 3) {
            throw new InvalidBucketNameException();
        }
        if (strlen($bucket) > 256) {
            throw new InvalidBucketNameException();
        }
        if (preg_match('/\d{1,3}(\.\d{1,3}){3}/', $bucket)) {
            throw new InvalidBucketNameException();
        }
        if (!preg_match('/^[0-9a-z]/', $bucket)) {
            throw new InvalidBucketNameException();
        }
        if (!preg_match('/^[0-9a-z\-\.\_]+$/', $bucket)) {
            throw new InvalidBucketNameException();
        }
        
        return true;
    }
}
?>