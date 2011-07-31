<?php
/**
 * RestStorage
 * 
 * This class is the restful interface to the clone SimpleStorageService. 
 * It provides nearly all of the functionality of the real S3 service,
 * mainly virtual hosting is missing (but you can do it yourself if you
 * want) and query string authentication.
 * 
 * TODO: POST Object 
 * TODO: Query string authentication
 * TODO: Implement expires time
 * TODO: Error if request time is 15 minutes out of server time
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
require_once 'storage.php';
class RestStorage
{
    private $storage = false;
    public $headers = array ( );
    public $body = false;
    private $response;
    private $request;
    private $message;
    
    public function __construct($dbh = false)
    {
        $this->_storageConnect($dbh);
        $this->response = new HTTPMessage();
        $this->response->setType(HTTPMessage::TYPE_RESPONSE);
        $this->message = new HTTPMessage();
    }
    
    // TODO: Query string authentication
    // TODO: amz-date convert to Date / authenticate using it?
    public function processRequest(HTTPMessage $message = null)
    {
        $result = null;
        $bucket = null;
        $object = null;       

        if ($message) {
             $this->message = $message;   
        } else {
            // set the http message from external http request
            $this->message = HTTPMessage::fromEnv(HTTPMessage::TYPE_REQUEST);
        }
        
            // parse the url
        $script_location = $_SERVER ['SCRIPT_NAME'];
        $location_split = split('/', $script_location);
        array_pop($location_split);
        $location = join('/', $location_split);
        
        $request_uri = parse_url($this->message->getRequestUrl(), PHP_URL_PATH);
        $resource = str_replace($location, "", $request_uri);
        $endpoint = split('/', $resource);
        
        foreach ($endpoint as $value) {
            if ($value) {
                if ($bucket) {
                    $object = $value;
                } else {
                    $bucket = $value;
                }
            }
        }        
        
        //var_dump($_SERVER);
        //var_dump($this->message);
        
        // add missing headers to message
        $content_type = $_SERVER ['CONTENT_TYPE'];
        $content_length = $_SERVER ['CONTENT_LENGTH'];
        $headers = array ('Content-Type' => $content_type, 'Content-Length' => $content_length );
        $this->message->addHeaders($headers);
        
        // TODO: fix url authentication
        //$arguments = array ( );
        //$url_query = parse_url($this->message->getRequestUrl(), PHP_URL_QUERY);
        //parse_str($url_query, $arguments);
        
        try {
            // check for valid action arguments
            switch (strtolower($_SERVER ["argv"] [0])) {
            case "torrent" : // optimistic
            case "acl" :
                $result = $this->_processACLRequest($bucket, $object, $this->message);
                break;
            case "location" :
                $result = $this->_processLocationRequest($bucket, $this->message);
                break;
            case "xml" :
                $result = $this->_processXMLRequest($bucket, $object, $this->message);
                break;
            default :
                $result = $this->_processXMLRequest($bucket, $object, $this->message);
            }
    
            if (!$result) {
                $result = $this->_sendErrorResponse(new MethodNotAllowedException());
            }
        } catch (Exception $e) {
            $result = $this->_sendErrorResponse(new InternalErrorException($e));
        }

        $this->response->send();
        
        return $result;
    }
    
    private function _generateRequestId($chars = 16)
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        return substr(str_shuffle($letters), 0, $chars);
    }
    
    private function _sendErrorResponse(Exception $error, $resource = "", $requestId = "")
    {
        $errorName = str_replace("Exception", "", get_class($error));
        
        $this->response->setResponseCode($error->getCode());
        $this->response->setHeaders(array ('Content-Type' => 'application/xml' ));
        
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('Error');
        $xml->writeElement("Code", $errorName);
        $xml->writeElement("Message", $error->getMessage());
        $xml->writeElement("Resource", $resource);
        $xml->writeElement("RequestId", $requestId);
        $xml->endElement();
        $xml->endDocument();
        
        $this->response->setBody($xml->outputMemory());
        
        return $this->response;
    }
    
    private function _processXMLRequest($bucket = false, $object = false, HTTPMessage $message)
    {
        // validate bucket or key
        // get bucket or object policy
        // authenticate if needed
        // do something based on the http verb
        switch ($this->message->getRequestMethod()) {
        case "PUT" :
            if ($object) {
                $result = $this->putObject($bucket, $object, $message);
            } else {
                $result = $this->putBucket($bucket, $message);
            }
            break;
        case "POST" :
            if ($object) {
                $result = $this->postObject($bucket, $object);
            }
            break;
        case "DELETE" :
            if ($object) {
                $result = $this->deleteObject($bucket, $object, $message);
            } else {
                $result = $this->deleteBucket($bucket, $message);
            }
            break;
        case "GET" :
            if ($object) {
                $result = $this->getObject($bucket, $object, $message);
            } else if ($bucket) {
                $result = $this->getBucket($bucket, $message);
            } else {
                $result = $this->get($message); // should be ($this->message);
            }
            break;
        case "HEAD" :
            if ($object) {
                $result = $this->headObject($bucket, $object, $message);
            }
            break;
        default :
            $result = $this->_sendErrorResponse(new MethodNotAllowedException());
        }
        
        return $result;
    }
    
    private function _processLocationRequest($bucket = false, HTTPMessage $message)
    {
        switch ($this->message->getRequestMethod()) {
        case "GET" :
            if ($bucket) {
                $result = $this->getBucketLocation($bucket, $message);
            }
            break;
        default :
            $result = $this->_sendErrorResponse(new MethodNotAllowedException());
        }   
        return $result;
    }    
    
    private function _processACLRequest($bucket = false, $object = false, HTTPMessage $message)
    {
        // validate bucket or key
        // get bucket or object policy
        // authenticate if needed
        // do something based on the http verb
        switch ($this->message->getRequestMethod()) {
        case "PUT" :
            $result = $this->putACL($bucket, $object, $message);
            break;
        case "GET" :
            $result = $this->getACL($bucket, $object, $message);
            break;
        default :
            $result = $this->_sendErrorResponse(new MethodNotAllowedException());
        }
        
        return $result;
    
    }
    
    private function _getAuthentication($authorization)
    {
        $result = false;
        
        if ($authorization) {
            $credentials = str_replace('AWS ', '', $authorization);
            $result = split(':', $credentials);
        }
        
        return $result;
    }
    
    private function _generateStringToSign($bucket, $object, HTTPMessage $message)
    {
        
        $headers = $message->getHeaders();
        $verb = $message->getRequestMethod();
        $md5 = $headers ["Content-Md5"];
        $type = $headers ["Content-Type"];
        $date = $headers ["Date"];
        
        $acl = $headers ["X-Amz-Acl"];
        if ($acl) {
            $aclstring = "x-amz-acl:$acl\n";
        }
        
        $metadatastring = "";
        foreach ($headers as $header => $value) {
            if (preg_match('/X-Amz-Meta-/', $header)) {
                $key = str_replace('X-Amz-Meta-', '', $header);
                $key = strtolower($key);
                $metadatastring .= "x-amz-meta-" . $key . ":" . trim($value) . "\n";
            }
        }
        
        if ($object) {
            $resource = $bucket . "/" . $object;
        } else if ($bucket) {
            $resource = $bucket;
        } else {
            $resource = '';
        }
        
        $stringToSign = "$verb\n$md5\n$type\n$date\n$aclstring$metadatastring/$resource";
        
        return $stringToSign;
    
    }
    
    /**
     * Operations on buckets - the PUT request operation with a bucket URI creates a new bucket
     * 
     * @method putBucket
     */
    function putBucket($bucket, HTTPMessage $message)
    {
        $credentials = $this->_getAuthentication($message->getHeader('Authorization'));
        if ($credentials) {
            $accessId = $credentials [0];
            $signature = $credentials [1];
            $stringToSign = $this->_generateStringToSign($bucket, false, $message);
        }
        
        $headers = $message->getHeaders();
        $content_length = $message->getHeader('Content-Length');
        $contentmd5 = $message->getHeader('Content-Md5');
        $body = $message->getBody();
        
        if ((isset($headers ['Content-Length'])) and ($headers ['Content-Length'] != strlen($body))) {
            return $this->_sendErrorResponse(new IncompleteBodyException());
        }
        
        if ($contentmd5 and ($contentmd5 != $this->hex2b64(md5($body)))) {
            return $this->_sendErrorResponse(new BadDigestException());
        }        
        
        if ($content_length) {
            $xml = new XMLReader();
            try {
                if ($xml->XML($body)) {
                    $xml->read();
                    if ($xml->localName == 'CreateBucketConfiguration') {
                        $xml->read();
                        if ($xml->localName == 'LocationConstraint') {
                            $xml->read();
                            if ($xml->hasValue) {
                                if (in_array($xml->value, $this->storage->locationConstraint)) {
                                    $location = $xml->value;
                                } else {
                                    return $this->_sendErrorResponse(new InvalidLocationConstraintException());
                                }
                            }
                        }
                    }
                    
                    // if no location then the XML is garbage
                    if (!$location) {
                        throw new MalformedXMLException();
                    }
                }
            } catch (Exception $e) {
                return $this->_sendErrorResponse(new MalformedXMLException());
            }
            $xml->close();
        } else {
            $location = $this->storage->locationConstraint [0];
        }
        
        try {
            $result = $this->storage->createBucket($bucket, $location, $accessId, $signature, $stringToSign);
            if ($result) {
                $this->_setCannedACL($bucket, null, $message);
                $headers = array ( );
                $headers ['Date'] = gmdate(DATE_RFC822, time());
                $headers ['Content-Length'] = 0;
                $headers ['x-amz-id-2'] = $result;
                $headers ['x-amz-request-id'] = $this->_generateRequestId();
                $headers ['Location'] = '/' . $bucket;
                $this->response->setResponseCode(200);
                $this->response->setHeaders($headers);
            }
        } catch (AbstractException $e) {
            $this->_sendErrorResponse($e, $bucket, $this->_generateRequestId());
        }
        
        return $this->response;
    
    }
    
    /**
     * The DELETE request operation deletes the bucket named in the URI.
     * All objects in the bucket must be deleted before the bucket itself can be deleted.
     * 
     * @method deleteBucket
     */
    function deleteBucket($bucket, HTTPMessage $message)
    {
        $credentials = $this->_getAuthentication($message->getHeader('Authorization'));
        if ($credentials) {
            $accessId = $credentials [0];
            $signature = $credentials [1];
            $stringToSign = $this->_generateStringToSign($bucket, false, $message);
        }
        
        if ($message->getBody()) {
            return $this->_sendErrorResponse(new UnexpectedContentException());
        }
        
        try {
            $result = $this->storage->deleteBucket($bucket, $accessId, $signature, $stringToSign);
            if ($result) {
                $headers = array ( );
                $headers ['Date'] = gmdate(DATE_RFC822, time());
                $headers ['x-amz-id-2'] = $result;
                $headers ['x-amz-request-id'] = $this->_generateRequestId();
                $this->response->setResponseCode(204);
                $this->response->setHeaders($headers);
            }
        } catch (AbstractException $e) {
            $this->_sendErrorResponse($e, $bucket, $this->_generateRequestId());
        }
        
        return $this->response;
    }
    
    /**
     * Sets the name of the default bucket
     * 
     * @method setBucketName
     * @return mixed list of all of the buckets owned by the authenticated sender of the request
     */
    function get(HTTPMessage $message)
    {
        $stringToSign = $this->_generateStringToSign(false, false, $message);
        $credentials = $this->_getAuthentication($message->getHeader('Authorization'));
        if ($credentials) {
            $accessId = $credentials [0];
            $signature = $credentials [1];
        }
        
        if ($message->getBody()) {
            return $this->_sendErrorResponse(new UnexpectedContentException());
        }

        try {
            $result = $this->storage->listAllMyBuckets($accessId, $signature, $stringToSign);
            if ($result) {
                $xml = new XMLWriter();
                $xml->openMemory();
                $xml->startDocument('1.0', 'UTF-8');
                $xml->startElement('ListAllMyBucketsResult');
                $xml->writeAttribute('xmlns', 'http://doc.s3.amazonaws.com/2006-03-01');                
                if ($result ['owner']) {
                    $xml->startElement('Owner');
                    $xml->writeElement('ID', $result ['owner'] ['id']);
                    $xml->writeElement('DisplayName', $result ['owner'] ['displayName']);
                    $xml->endElement();
                }
                $xml->startElement('Buckets');
                if ($result['buckets']) {
                    foreach ($result['buckets'] as $bucket) {
                        $xml->startElement('Bucket');
                        $xml->writeElement('Name', $bucket ['name']);
                        $xml->writeElement('CreationDate', gmdate(DATE_RFC3339, strtotime($bucket ['creation_date'])));
                        $xml->endElement();
                    }
                }
                $xml->endElement();
                $xml->endElement();
                $body = $xml->outputMemory(true);
                
                $headers = array ( );
                $headers ['Date'] = gmdate(DATE_RFC822, time());
                $headers ['Content-Length'] = strlen($body);
                $headers ['Content-Type'] = 'application/xml';
                $headers ['x-amz-request-id'] = $this->_generateRequestId();
                
                $this->response->setBody($body);
                $this->response->setResponseCode(200);
                $this->response->setHeaders($headers);
				
            }
        } catch (AbstractException $e) {
            $this->_sendErrorResponse($e, null, $this->_generateRequestId());
        }

        return $this->response;        
        
    }
    
    // A GET request operation using a bucket URI lists information about the objects in the bucket.
    function getBucket($bucket, HTTPMessage $message)
    {
        $stringToSign = $this->_generateStringToSign($bucket, false, $message);
        $credentials = $this->_getAuthentication($message->getHeader('Authorization'));
        if ($credentials) {
            $accessId = $credentials [0];
            $signature = $credentials [1];
        }
        
        if ($message->getBody()) {
            return $this->_sendErrorResponse(new UnexpectedContentException());
        }
        
        // process requestURI
        $arguments = array ( );
        $requestURL = parse_url($message->getRequestUrl(), PHP_URL_QUERY);
        parse_str($requestURL, $arguments);
        
        $prefix = $arguments ['prefix'];
        $marker = $arguments ['marker'];
        $maxKeys = $arguments ['max-keys'];
        
        // TODO: Implemeny delimiter parameter, CommonPrefixes and NextMarker
        $delimiter = null;
        
        try {
            $result = $this->storage->listBucket($bucket, $prefix, $marker, $maxKeys, $delimiter, $accessId, $signature, $stringToSign);
            if ($result) {
                $xml = new XMLWriter();
                $xml->openMemory();
                $xml->startDocument('1.0', 'UTF-8');
                $xml->startElement('ListBucketResult');
                $xml->writeAttribute('xmlns', 'http://doc.s3.amazonaws.com/2006-03-01');
                $xml->writeElement('Name', $result ['name']);
                $xml->writeElement('Prefix', $result ['Prefix']);
                $xml->writeElement('Marker', $result ['Marker']);
                $xml->writeElement('MaxKeys', $result ['MaxKeys']);
                if ($result ['Delimiter']) {
                    $xml->writeElement('Delimiter', $result ['Delimiter']);
                }
                $xml->writeElement('IsTruncated', ($result ['IsTruncated']) ? 'true' : 'false');
                foreach ($result ['Contents'] as $content) {
                    $xml->startElement('Contents');
                    $xml->writeElement('Key', $content ['Key']);
                    $xml->writeElement('LastModified', $content ['LastModified']);
                    $xml->writeElement('ETag', $content ['ETag']);
                    $xml->writeElement('Size', $content ['Size']);
                    $xml->writeElement('StorageClass', 'STANDARD');
                    if ($content ['Owner']) {
                        $xml->startElement('Owner');
                        $xml->writeElement('ID', $content ['Owner'] ['ID']);
                        if ($content ['Owner'] ['DisplayName']) {
                            $xml->writeElement('DisplayName', $content ['Owner'] ['DisplayName']);
                        }
                        $xml->endElement();
                    }
                    $xml->endElement();
                }
                $xml->endElement();
                $body = $xml->outputMemory(true);
                
                $headers = array ( );
                $headers ['Date'] = gmdate(DATE_RFC822, time());
                $headers ['Content-Length'] = strlen($body);
                $headers ['Content-Type'] = 'application/xml';
                $headers ['x-amz-id-2'] = $result ['bucketId'];
                $headers ['x-amz-request-id'] = $this->_generateRequestId();
                
                $this->response->setBody($body);
                $this->response->setResponseCode(200);
                $this->response->setHeaders($headers);
            }
        } catch (AbstractException $e) {
            $this->_sendErrorResponse($e, $bucket, $this->_generateRequestId());
        }
        
        return $this->response;
    
    }
    
    // A GET location request operation using a bucket URI lists the location constraint of the bucket.
    function getBucketLocation($bucket, HTTPMessage $message)
    {
        
        $credentials = $this->_getAuthentication($message->getHeader('Authorization'));
        if ($credentials) {
            $accessId = $credentials [0];
            $signature = $credentials [1];
            $stringToSign = $this->_generateStringToSign($bucket, false, $message);
        } 

        if ($message->getBody()) {
            return $this->_sendErrorResponse(new UnexpectedContentException());
        }

        
        try {
            $result = $this->storage->getBucketLocation($bucket, $accessId, $signature, $stringToSign);
            if ($result) {
                $xml = new XMLWriter();
                $xml->openMemory();
                $xml->startDocument('1.0', 'UTF-8');
                $xml->startElement('LocationConstraint');
                $xml->writeAttribute('xmlns', 'http://s3.amazonaws.com/doc/2006-03-01/');
                $xml->text($result);
                $xml->endElement();
                $body = $xml->outputMemory(true);
                
                $headers = array ( );
                $headers ['Date'] = gmdate(DATE_RFC822, time());
                $headers ['Content-Length'] = strlen($body);
                $headers ['Content-Type'] = 'application/xml';
                $headers ['x-amz-id-2'] = $result ['bucketId'];
                $headers ['x-amz-request-id'] = $this->_generateRequestId();
                
                $this->response->setBody($body);
                $this->response->setResponseCode(200);
                $this->response->setHeaders($headers);                
            }
        } catch (AbstractException $e) {
            $this->_sendErrorResponse($e, $bucket, $this->_generateRequestId());
        }
        
        return $this->response;
        
    }
    
    /// Operations on objects
    // The PUT request operation adds an object to a bucket.
    function putObject($bucket, $object, HTTPMessage $message)
    {
        // not supporting acl for REST for this release (as well as many other things)
        

        $stringToSign = $this->_generateStringToSign($bucket, $object, $message);
        $credentials = $this->_getAuthentication($message->getHeader('Authorization'));
        if ($credentials) {
            $accessId = $credentials [0];
            $signature = $credentials [1];
        }
        
        $metadata = array ( );
        $headers = $message->getHeaders();
        foreach ($headers as $header => $value) {
            if (preg_match('/X-Amz-Meta-/', $header)) {
                $key = strtolower(str_replace('X-Amz-Meta-', '', $header));
                $metadata [$key] = trim($value);
            }
        }
        
        $contenttype = $message->getHeader('Content-Type');
        $contentmd5 = $message->getHeader('Content-Md5');
        $body = $message->getBody();
        
        // MD5 does not received body value
        if ($contentmd5 and ($contentmd5 != $this->hex2b64(md5($body)))) {
            return $this->_sendErrorResponse(new BadDigestException());
        }
        
        // No content length header was sent
        if (!isset($headers ['Content-Length'])) {
            return $this->_sendErrorResponse(new MissingContentLengthException());
        }
        
        if ($headers ['Content-Length'] != strlen($body)) {
            return $this->_sendErrorResponse(new IncompleteBodyException());
        }
        
        try {
            $result = $this->storage->putObject($bucket, $object, $body, $metadata, $contenttype, $accessId, $signature, $stringToSign);
            if ($result) {
                $this->_setCannedACL($bucket, $object, $message);
                $headers = array ( );
                $headers ['Date'] = gmdate(DATE_RFC822, time());
                $headers ['Content-Length'] = 0;
                foreach ($metadata as $key => $value) {
                    $headers ['x-amz-meta-' . $key] = $value;
                }
                $headers ['x-amz-id-2'] = $result;
                $headers ['x-amz-request-id'] = $this->_generateRequestId();
                $headers ['ETag'] = md5($body);
                $this->response->setResponseCode(200);
                $this->response->setHeaders($headers);
            }
        } catch (AbstractException $e) {
            $resource = $bucket.'/'.$object;
            //$resource = $_SERVER ['SCRIPT_NAME'];
            $this->_sendErrorResponse($e, $resource, $this->_generateRequestId());
        }
        
        return $this->response;
    
    }
    
    // You fetch objects from Amazon S3 using the GET operation.
    function getObject($bucket, $object, HTTPMessage $message)
    {
        $stringToSign = $this->_generateStringToSign($bucket, $object, $message);
        $credentials = $this->_getAuthentication($message->getHeader('Authorization'));
        if ($credentials) {
            $accessId = $credentials [0];
            $signature = $credentials [1];
        }
        
        $returnObjectContents = true; // get the value unless other conditions fail
        

        $ifMatch = $message->getHeader('If-Match');
        $ifNoneMatch = $message->getHeader('If-None-Match');
        $ifModifiedSince = $message->getHeader('If-Modified-Since');
        $ifUnmodifiedSince = $message->getHeader('If-Unmodified-Since');
        $range = $message->getHeader('Range');
        
        if ($range) {
            $rangeArray = split("-", $range, 2);
            $rangeStart = $rangeArray [0];
            $rangeEnd = $rangeArray [1];
        }
        
        try {
            $result = $this->storage->getObjectExtended($bucket, $object, $returnObjectContents, $rangeStart, $rangeEnd, $ifModifiedSince, $ifUnmodifiedSince, $ifMatch, $ifNoneMatch, $accessId, $signature, $stringToSign);
            if ($result) {
                
                if ($result ['metadata']) {
                    $metadata = unserialize($result ['metadata']);
                    if (is_array($metadata)) {
                        foreach ($metadata as $key => $value) {
                            $headers ['x-amz-meta-' . $key] = $value;
                        }
                    }
                }
                $headers ['x-amz-id-2'] = $result ['id'];
                $headers ['x-amz-request-id'] = $this->_generateRequestId();
                $headers ['Last-Modified'] = gmdate(DATE_RFC822, strtotime($result ['last_updated']));
                $headers ['ETag'] = $result ['etag'];
                if ($range) {
                    $headers ['Content-Length'] = $rangeStart . "-" . $rangeEnd . "/" . $result ['length'];
                } else {
                    $headers ['Content-Length'] = $result ['length'];
                }
                $headers ['Content-Type'] = $result ['content_type'];
                
                $body = $result ['value'];
                if ($ifMatch and empty($body)) {
                    $this->response->setResponseCode(412);
                } else if ($ifNoneMatch and empty($body)) {
                    $this->response->setResponseCode(304);
                }
                if ($ifModifiedSince and empty($body)) {
                    $this->response->setResponseCode(304);
                } else if ($ifUnmodifiedSince and empty($body)) {
                    $this->response->setResponseCode(412);
                }
                
                if (isset($body)) {
                    $this->response->setBody($body);
                    $this->response->setResponseCode(200);
                }
                $this->response->setHeaders($headers);
            
            }
        } catch (AbstractException $e) {
            $this->_sendErrorResponse($e, $bucket.'/'.$object, $this->_generateRequestId());
        }
        
        return $this->response;
    }
    
    // The HEAD operation is used to retrieve information about a specific object,
    // without actually fetching the object itself.
    function headObject($bucket, $object, HTTPMessage $message)
    {
        $stringToSign = $this->_generateStringToSign($bucket, $object, $message);
        $credentials = $this->_getAuthentication($message->getHeader('Authorization'));
        if ($credentials) {
            $accessId = $credentials [0];
            $signature = $credentials [1];
        }
        
        if ($message->getBody()) {
            return $this->_sendErrorResponse(new UnexpectedContentException());
        }
        
        $returnObjectContents = false; // get the value unless other conditions fail
        

        try {
            $result = $this->storage->getObjectExtended($bucket, $object, $returnObjectContents, false, false, false, false, false, false, $accessId, $signature, $stringToSign);
            if ($result) {
                //var_dump($result);
                if ($result ['metadata']) {
                    $metadata = unserialize($result ['metadata']);
                    if (is_array($metadata)) {
                        foreach ($metadata as $key => $value) {
                            $headers ['x-amz-meta-' . $key] = $value;
                        }
                    }
                }
                $headers ['x-amz-id-2'] = $result ['id'];
                $headers ['x-amz-request-id'] = $this->_generateRequestId();
                $headers ['Last-Modified'] = gmdate(DATE_RFC822, strtotime($result ['last_updated']));
                $headers ['ETag'] = $result ['etag'];
                $headers ['Content-Length'] = $result ['length'];
                $headers ['Content-Type'] = $result ['content_type'];
                $this->response->setResponseCode(200);
                $this->response->setHeaders($headers);
            }
        } catch (AbstractException $e) {
            $this->_sendErrorResponse($e, $bucket.'/'.$object, $this->_generateRequestId());
        }
        
        return $this->response;
    }
    
    // The DELETE request operation removes the specified object from the storage service
    function deleteObject($bucket, $object, HTTPMessage $message)
    {
        $credentials = $this->_getAuthentication($message->getHeader('Authorization'));
        if ($credentials) {
            $accessId = $credentials [0];
            $signature = $credentials [1];
            $stringToSign = $this->_generateStringToSign($bucket, $object, $message);
        }
        
        if ($message->getBody()) {
            return $this->_sendErrorResponse(new UnexpectedContentException());
        }
        
        try {
            $result = $this->storage->deleteObject($bucket, $object, $accessId, $signature, $stringToSign);
            if ($result) {
                $headers = array ( );
                $headers ['Date'] = gmdate(DATE_RFC822, time());
                $headers ['x-amz-id-2'] = $result;
                $headers ['x-amz-request-id'] = $this->_generateRequestId();
                $this->response->setResponseCode(204);
                $this->response->setHeaders($headers);
            }
        } catch (AbstractException $e) {
            $this->_sendErrorResponse($e, $bucket.'/'.$object, $this->_generateRequestId());
        }
        
        return $this->response;
    }
    
    // The POST request operation adds an object to a bucket using forms.
    function postObject()
    {
    
    }
    
    /// Operations for Access Control
    // You can set the ACL on an existing bucket or object by doing an HTTP PUT to /bucket?acl, or /bucket/key?acl
    public function putACL($bucket, $object = null, HTTPMessage $message)
    {
        $stringToSign = $this->_generateStringToSign($bucket, $object, $message);
        $credentials = $this->_getAuthentication($message->getHeader('Authorization'));
        if ($credentials) {
            $accessId = $credentials [0];
            $signature = $credentials [1];
        }
        
        $headers = $message->getHeaders();
        $contentmd5 = $message->getHeader('Content-Md5');
        $body = $message->getBody();
        
        // MD5 does not received body value
        if ($contentmd5 and ($contentmd5 != $this->hex2b64(md5($body)))) {
            return $this->_sendErrorResponse(new BadDigestException());
        }
        
        // No content length header was sent
        if (isset($headers ['Content-Length'])) {
            if ($headers ['Content-Length'] != strlen($body)) {
                return $this->_sendErrorResponse(new IncompleteBodyException());
            }
        }

        try {
            $acp = new AccessControlPolicy();
            $acp->processXML($body);
            if ($object) {
                $result = $this->storage->setObjectAccessControlPolicy($bucket, $object, $acp, $accessId, $signature, $stringToSign);
            } elseif ($bucket) {
                $result = $this->storage->setBucketAccessControlPolicy($bucket, $acp, $accessId, $signature, $stringToSign);
            }
            if ($result) {
                $headers = array ( );
                $headers ['Date'] = gmdate(DATE_RFC822, time());
                $headers ['Content-Length'] = 0;
                $headers ['x-amz-id-2'] = $result;
                $headers ['x-amz-request-id'] = $this->_generateRequestId();
                $this->response->setResponseCode(200);
                $this->response->setHeaders($headers);
            }
        } catch (AbstractException $e) {
            if ($object) {
                $resource = $bucket.'/'.$object;
            } else {
                $resource = $bucket;
            }
            $this->_sendErrorResponse($e, $resource, $this->_generateRequestId());            
        }
        
        return $this->response;
            
    }
    
    public function getACL($bucket, $object, HTTPMessage $message)
    {
        $stringToSign = $this->_generateStringToSign($bucket, $object, $message);
        $credentials = $this->_getAuthentication($message->getHeader('Authorization'));
        if ($credentials) {
            $accessId = $credentials [0];
            $signature = $credentials [1];
        }
        
        if ($message->getBody()) {
            return $this->_sendErrorResponse(new UnexpectedContentException());
        }
        
        try {
            if ($object) {
                $acp = $this->storage->getObjectAccessControlPolicy($bucket, $object, $accessId, $signature, $stringToSign);
            } elseif ($bucket) {
                $acp = $this->storage->getBucketAccessControlPolicy($bucket, $accessId, $signature, $stringToSign);
            }
            if ($acp) {
                $headers = array ( );
                $body = $acp->generateXML();
                $headers ['Date'] = gmdate(DATE_RFC822, time());
                $headers ['Content-Length'] = strlen($body);
                $headers ['x-amz-request-id'] = $this->_generateRequestId();
                $this->response->setBody($body);
                $this->response->setResponseCode(200);
                $this->response->setHeaders($headers);
            }
        } catch (AbstractException $e) {
            if ($object) {
                $resource = $bucket.'/'.$object;
            } else {
                $resource = $bucket;
            }
            $this->_sendErrorResponse($e, $resource, $this->_generateRequestId());
        }
        
        return $this->response;        
    }
    
    private function _setCannedACL($bucket, $object, HTTPMessage $message)
    {
        $type = $message->getHeader('x-amz-acl');
        if ((strtolower($type) != 'private') and ($type)) {
        
            $stringToSign = $this->_generateStringToSign($bucket, $object, $message);
            $credentials = $this->_getAuthentication($message->getHeader('Authorization'));
            if ($credentials) {
                $accessId = $credentials [0];
                $signature = $credentials [1];
            }
            
            try {
                if ($object) {
                    $acp = $this->storage->getObjectAccessControlPolicy($bucket, $object, $accessId, $signature, $stringToSign);
                } elseif ($bucket) {
                    $acp = $this->storage->getBucketAccessControlPolicy($bucket, $accessId, $signature, $stringToSign);
                }   
                
                $grant = new Grant();
                $grantee = new Grantee();
                $grantee->setType('GROUP');
                
                switch (strtolower($type)) {
                case 'authenticated-read':
                    $grant->setProtection('READ');
                    $grantee->setUri('http://acs.amazonaws.com/groups/global/AuthenticatedUsers');
                    $grant->setGrantee($grantee);
                    $acp->addGrant($grant);
                    if ($object) {
                        $this->storage->setObjectAccessControlPolicy($bucket, $object, $acp, $accessId, $signature, $stringToSign);    
                    } elseif ($bucket) {
                        $this->storage->setBucketAccessControlPolicy($bucket, $acp, $accessId, $signature, $stringToSign); 
                    }                    
                    break;
                case 'public-read':
                    $grant->setProtection('READ');
                    $grantee->setUri('http://acs.amazonaws.com/groups/global/AllUsers');
                    $grant->setGrantee($grantee);
                    $acp->addGrant($grant);
                    if ($object) {
                        $this->storage->setObjectAccessControlPolicy($bucket, $object, $acp, $accessId, $signature, $stringToSign);    
                    } elseif ($bucket) {
                        $this->storage->setBucketAccessControlPolicy($bucket, $acp, $accessId, $signature, $stringToSign); 
                    }   
                    break;
                case 'public-read-write':
                    $grant->setProtection('READ');
                    $grantee->setUri('http://acs.amazonaws.com/groups/global/AllUsers');
                    $grant->setGrantee($grantee);
                    $acp->addGrant($grant);
                    if ($object) {
                        $this->storage->setObjectAccessControlPolicy($bucket, $object, $acp, $accessId, $signature, $stringToSign);    
                    } elseif ($bucket) {
                        $grant2 = new Grant();
                        $grant2->setProtection('WRITE');
                        $grantee2 = new Grantee();
                        $grantee2->setType('GROUP');
                        $grantee2->setUri('http://acs.amazonaws.com/groups/global/AllUsers');
                        $grant2->setGrantee($grantee2);
                        $acp->addGrant($grant2);
                        $this->storage->setBucketAccessControlPolicy($bucket, $acp, $accessId, $signature, $stringToSign); 
                    }   
                    break;
                }
            } catch (AbstractException $e) {
                if ($object) {
                    $resource = $bucket.'/'.$object;
                } else {
                    $resource = $bucket;
                }
                return $this->_sendErrorResponse($e, $resource, $this->_generateRequestId());
            }
        } else {
            return null;
        }
                
    }
    
    // I don't like this, but it's basically for unit tests
    // If only phpAspects was stable!
    private function _storageConnect($dbh = false)
    {
        $this->storage = new Storage();
        $this->storage->connect($dbh);
    }
    
    private function hex2b64($str)
    {
        
        $raw = '';
        for ($i = 0; $i < strlen($str); $i += 2) {
            $raw .= chr(hexdec(substr($str, $i, 2)));
        }
        return base64_encode($raw);
    
    }

}
?>