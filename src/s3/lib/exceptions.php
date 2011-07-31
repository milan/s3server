<?php
/**
 * Exceptions
 * 
 * Maps S3 exceptions via an abstract exception so all common S3 exceptions
 * can be caught and handled easily.
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


/**
 * A Abstration of Excecption to include the __toString function
 *
 */
class AbstractException extends Exception
{
    private $_soapFault;
    
    /**
     * @param Error description $message
     * @param HTTP Error code $code
     */
    public function __construct($message = false, $code = false)
    {
        parent::__construct($message, $code);
    }    

    /**
     * @return string
     */
    public function getSoapFault()
    {
        return $this->_soapFault;
    }
    
    /**
     * @param string $soapFault
     */
    public function setSoapFault($soapFault)
    {
        $this->_soapFault = $soapFault;
    }
        
    /**
     * Returns a formatted string of the error code and message
     *
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}

/**
 * Thrown when user does not have permission to access a resource
 */
class AccessDeniedException extends AbstractException
{
    public function __construct($message = false, $code = 403)
    {
        if (!$message) {
            $message = "Access Denied";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

/**
 * Thrown when there is a problem with your AWS account
 * that prevents the operation from completing successfully.
 */
class AccountProblemException extends AbstractException
{
    public function __construct($message = false, $code = 403)
    {
        if (!$message) {
            $message = "There is a problem with your AWS account that prevents the operation from completing successfully.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

/**
 * The e-mail address you provided is
 * associated with more than one account.
 */
class AmbiguousGrantByEmailAddressException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "The e-mail address you provided is associated with more than one account";
        }        
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class BadDigestException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "The Content-MD5 you specified did not match what we received.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class BucketAlreadyExistsException extends AbstractException
{
    public function __construct($message = false, $code = 409)
    {
        if (!$message) {
            $message = "The requested bucket name is not available. The bucket namespace is shared by all users of the system. Please select a different name and try again.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class BucketAlreadyOwnedByYouException extends AbstractException
{
    public function __construct($message = false, $code = 409)
    {
        if (!$message) {
            $message = "Your previous request to create the named bucket succeeded and you already own it.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class BucketNotEmptyException extends AbstractException
{
    public function __construct($message = false, $code = 409)
    {
        if (!$message) {
            $message = "The bucket you tried to delete is not empty.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class CredentialsNotSupportedException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "This request does not support credentials.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class CrossLocationLoggingProhibittedException extends AbstractException
{
    public function __construct($message = false, $code = 403)
    {
        if (!$message) {
            $message = "Cross location logging not allowed. Buckets in one geographic location cannot log information to a bucket in another location.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class EntityTooSmallException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "Your proposed upload is smaller than the minimum allowed object size.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class EntityTooLargeException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "Your proposed upload exceeds the maximum allowed object size.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class ExpiredTokenException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "The provided token has expired.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class IncompleteBodyException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "You did not provide the number of bytes specified by the Content-Length HTTP header";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class InlineDataTooLargeException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "Inline data exceeds the maximum allowed size.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class InternalErrorException extends AbstractException
{
    public function __construct($message = false, $code = 500)
    {
        if (!$message) {
            $message = "We encountered an internal error. Please try again.";
        }
        $this->setSoapFault('Server');
        parent::__construct($message, $code);
    }
}

class InvalidAccessKeyIdException extends AbstractException
{
    public function __construct($message = false, $code = 403)
    {
        if (!$message) {
            $message = "The AWS Access Key Id you provided does not exist in our records.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class InvalidAddressingHeaderException extends AbstractException
{
    public function __construct($message = false, $code = false)
    {
        if (!$message) {
            $message = "You must specify the Anonymous role.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class InvalidArgument extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "Invalid Argument";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class InvalidBucketNameException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "The specified bucket is not valid.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class InvalidDigestException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "The Content-MD5 you specified was an invalid.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class InvalidLocationConstraintException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "The specified location constraint is not valid.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class InvalidPayerException extends AbstractException
{
    public function __construct($message = false, $code = 403)
    {
        if (!$message) {
            $message = "All access to this object has been disabled.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class InvalidPolicyDocumentException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "The content of the form does not meet the conditions specified in the policy document.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class InvalidRangeException extends AbstractException
{
    public function __construct($message = false, $code = 416)
    {
        if (!$message) {
            $message = "The requested range cannot be satisfied.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class InvalidSecurityException extends AbstractException
{
    public function __construct($message = false, $code = 403)
    {
        if (!$message) {
            $message = "The provided security credentials are not valid.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class InvalidSOAPRequestException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "The SOAP request body is invalid.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class InvalidStorageClassException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "The storage class you specified is not valid.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class InvalidTargetBucketForLoggingException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "The target bucket for logging does not exist or is not owned by you.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class InvalidTokenException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "The provided token is malformed or otherwise invalid.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class InvalidURIException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "Couldn't parse the specified URI.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class KeyTooLongException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "Your key is too long.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class MalformedACLErrorException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "The XML you provided was not well-formed or did not validate against our published schema.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class MalformedXMLException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "The XML you provided was not well-formed or did not validate against our published schema.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class MaxMessageLengthExceededException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "Your request was too big.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class MaxPostPreDataLengthExceededErrorException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "Your POST request fields preceeding the upload file were too large.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class MetadataTooLargeException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "Your metadata headers exceed the maximum allowed metadata size.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class MethodNotAllowedException extends AbstractException
{
    public function __construct($message = false, $code = 405)
    {
        if (!$message) {
            $message = "The specified method is not allowed against this resource.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class MissingAttachmentException extends AbstractException
{
    public function __construct($message = false, $code = false)
    {
        if (!$message) {
            $message = "A SOAP attachment was expected, but none were found.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class MissingContentLengthException extends AbstractException
{
    public function __construct($message = false, $code = 411)
    {
        if (!$message) {
            $message = "You must provide the Content-Length HTTP header.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class MissingSecurityElementException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "The SOAP 1.1 request is missing a security element.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class MissingSecurityHeaderException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "Your request was missing a required header.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class NoLoggingStatusForKeyException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "There is no such thing as a logging status sub-resource for a key.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class NoSuchBucketException extends AbstractException
{
    public function __construct($message = false, $code = 404)
    {
        if (!$message) {
            $message = "The specified bucket does not exist.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class NoSuchKeyException extends AbstractException
{
    public function __construct($message = false, $code = 404)
    {
        if (!$message) {
            $message = "The specified key does not exist.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class NotImplementedException extends AbstractException
{
    public function __construct($message = false, $code = 501)
    {
        if (!$message) {
            $message = "A header you provided implies functionality that is not implemented.";
        }
        $this->setSoapFault('Server');
        parent::__construct($message, $code);
    }
}

class NotSignedUpException extends AbstractException
{
    public function __construct($message = false, $code = 403)
    {
        if (!$message) {
            $message = "Your account is not signed up for the Amazon S3 service. You must sign up before you can use Amazon S3. You can sign up at the following URL: http://aws.amazon.com/s3";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class OperationAbortedException extends AbstractException
{
    public function __construct($message = false, $code = 409)
    {
        if (!$message) {
            $message = "A conflicting conditional operation is currently in progress against this resource. Please try again.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class PermanentRedirectException extends AbstractException
{
    public function __construct($message = false, $code = 301)
    {
        if (!$message) {
            $message = "The bucket you are attempting to access must be addressed using the specified endpoint. Please send all future requests to this endpoint.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class PreconditionFailedException extends AbstractException
{
    public function __construct($message = false, $code = 412)
    {
        if (!$message) {
            $message = "At least one of the pre-conditions you specified did not hold.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class RedirectException extends AbstractException
{
    public function __construct($message = false, $code = 307)
    {
        if (!$message) {
            $message = "Temporary redirect.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class RequestTimeoutException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "Your socket connection to the server was not read from or written to within the timeout period.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class RequestTimeTooSkewedException extends AbstractException
{
    public function __construct($message = false, $code = 403)
    {
        if (!$message) {
            $message = "The difference between the request time and the server's time is too large.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class RequestTorrentOfBucketErrorException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "Requesting the torrent file of a bucket is not permitted.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class SignatureDoesNotMatchException extends AbstractException
{
    public function __construct($message = false, $code = 403)
    {
        if (!$message) {
            $message = "The request signature we calculated does not match the signature you provided. Check your AWS Secret Access Key and signing method. Consult the documentation under Authenticating REST Requests and Authenticating SOAP Requests for details.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class SlowDownException extends AbstractException
{
    public function __construct($message = false, $code = 503)
    {
        if (!$message) {
            $message = "Please reduce your request rate.";
        }
        $this->setSoapFault('Server');
        parent::__construct($message, $code);
    }
}

class TemporaryRedirectException extends AbstractException
{
    public function __construct($message = false, $code = 307)
    {
        if (!$message) {
            $message = "You are being redirected to the bucket while DNS updates.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class TokenRefreshRequiredException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "The provided token must be refreshed.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class TooManyBucketsException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "You have attempted to create more buckets than allowed.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class UnexpectedContentException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "This request does not support content.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

class UnresolvableGrantByEmailAddressException extends AbstractException
{
    public function __construct($message = false, $code = 400)
    {
        if (!$message) {
            $message = "The e-mail address you provided does not match any account on record.";
        }
        $this->setSoapFault('Client');
        parent::__construct($message, $code);
    }
}

?>