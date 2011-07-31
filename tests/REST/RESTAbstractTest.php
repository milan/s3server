<?php
define('ABSTRACT_SOURCE', realpath(dirname(__FILE__)));
include ABSTRACT_SOURCE . '/../AbstractTest.php';

abstract class RESTAbstractTest extends SimpleStorageServiceAbstractTest
{
    
    protected $locationXML1 = "<CreateBucketConfiguration><LocationConstraint>EU</LocationConstraint></CreateBucketConfiguration>";
    protected $locationXML2 = "<CreateBucketConfiguration><LocationConstraint>US</LocationConstraint></CreateBucketConfiguration>";
    protected $malformedXML = "<LocationConstraint>EU<LocationConstraint>";
    protected $invalidLocationXML = "<CreateBucketConfiguration><LocoConstraint>EU</LocoConstraint></CreateBucketConfiguration>";
    protected $badLocationXML = "<CreateBucketConfiguration><LocationConstraint>OO</LocationConstraint></CreateBucketConfiguration>";
    
    /**
     * Removes all test contents.
     *
     * @return void
     */
    protected function tearDown()
    {
        parent::tearDown();
    }
    
    protected function countListBucketContents($body) {
        $count = 0;
        try {
            $xr = new XMLReader();
            $xr->XML($body);
            while($xr->read()) {
                if($xr->nodeType == XMLReader::ELEMENT) {
                    if ($xr->name == 'Contents') {
                        //print "\nContents";
                        $count++;
                    }
                }
                
            }
            $xr->close();
        } catch (Exception $e) {
            return $e;
        }
        return $count;
    }
    
    protected function getListBucketContentKeys($body) {
        $keys = array();
        try {
            $xr = new XMLReader();
            $xr->XML($body);
            while($xr->read()) {
                if($xr->nodeType == XMLReader::ELEMENT) {
                    if ($xr->name == 'Key') {
                        $xr->read();
                        if ($xr->hasValue) {
                            array_push($keys, $xr->value);
                        }
                    }
                }
                
            }
            $xr->close();
        } catch (Exception $e) {
            return $e;
        }
        return $keys;
    }    
    
    protected function readListBucket($body) {
        $result = array();
        $result['buckets'] = array();
        $bucket = 0;
        if ($body) {
            //print "$body\n";
            try {
                $xr = new XMLReader();
                $xr->XML($body);
                while($xr->read()) {
                    if($xr->nodeType == XMLReader::ELEMENT) {

                        if ($xr->name == 'ID') {
                            $xr->read();
                            if ($xr->hasValue) {
                                $result['id'] = $xr->value;
                            }        
                        }
                        if ($xr->name == 'DisplayName') {
                            $xr->read();
                            if ($xr->hasValue) {
                                $result['name'] = $xr->value;
                            }        
                        }                                                     
                        if ($xr->name == 'Name') {
                            $xr->read();
                            if ($xr->hasValue) {
                                $result['bucket'][$bucket]['name'] = $xr->value;
                            }        
                        }
                        if ($xr->name == 'CreationDate') {
                            $xr->read();
                            if ($xr->hasValue) {
                                $result['bucket'][$bucket]['creation_date'] = $xr->value;
                                $bucket++;
                            }        
                        }                                                   
                    }
                }
                $xr->close();
            } catch (Exception $e) {
                return $e;
            }
        }
        return $result;        
    }
    
    protected function createHTTPRequest($method = '', $bucket = '', $object = '', $body = '', $accessId = '', $secretKey = '', $length = '', $headers = array(), $metadata = array()) {
        $message = new HTTPMessage();
        $message->setType(HTTPMessage::TYPE_REQUEST);
        
        $message->setBody($body);
        
        $message->setRequestMethod($method);
        
        //$headers['Content-Type']   = $this->content_type1;
        //$headers['Content-Md5']    = $this->setMD5($this->value1);
        
        if ($length < 0) {
            // do nothing
        } else if (!$length) {
            $headers['Content-Length'] = strlen($body);
        } else {
            $headers['Content-Length'] = $length;
        }
        //$headers['X-Amz-Acl']      = 'private';
        
        $headers['Date']           = gmdate(DATE_RFC822);
        
        foreach ($metadata as $key => $value) {
             $headers['X-Amz-Meta-'.$key] = $value;
        }

        $message->setHeaders($headers);
        
        if ($accessId or $secretKey) {
            $stringToSign = $this->_generateStringToSign($bucket, $object, $message);
            $signature    = $this->calculateSignature($secretKey, $stringToSign);
            $headers['Authorization'] = 'AWS '.$accessId.':'.$signature;
            $message->setHeaders($headers);
        }
        
        return $message;
    }    
    
    public function getErrorType($error)
    {
        $return = null;
        try {
            $reader = new XMLReader();
            $reader->XML($error);
            
            while ($reader->read()){
                //echo $reader->name;
                if ($reader->hasValue) {
                    return $reader->value;
                    //echo ": " . $reader->value;
                }
                //echo "\n";   
            }
            
            $reader->close();
        } catch (Exception $e) {
            return "WARNING: (getErrorType) No Error Returned!";
        }
        return $return;
    }
    
    private function _generateStringToSign($bucket, $object, HTTPMessage $message)
    {
        
        $headers = $message->getHeaders();
        $verb = $message->getRequestMethod();
        $md5 = $message->getHeader('Content-Md5');
        $type = $message->getHeader('Content-Type');
        $date = $message->getHeader('Date');
        
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
            $resource = "";
        }
        
        $stringToSign = "$verb\n$md5\n$type\n$date\n$aclstring$metadatastring/$resource";
        
        return $stringToSign;
    
    }    
    
    private function hex2b64($str)
    {
        
        $raw = '';
        for ($i = 0; $i < strlen($str); $i += 2) {
            $raw .= chr(hexdec(substr($str, $i, 2)));
        }
        return base64_encode($raw);
    
    }

    public function _setMD5($body) {
        return $this->hex2b64(md5($body));
    }

}