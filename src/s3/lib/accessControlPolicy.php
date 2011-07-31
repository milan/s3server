<?php
/**
 * AccessControlPolicy
 * 
 * A class wrapper to handle AccessControlPolicy's internally to this
 * application, but could be used in a external client to this service.
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
class AccessControlPolicy
{
    
    private $owner;
    private $accessControlList = array ( );
    
    public function __construct()
    {
        $this->owner = new Owner();
    }
    
    /**
     * @return Owner
     */
    public function getOwner()
    {
        return $this->owner;
    }
    
    /**
     * @param Owner $owner
     */
    public function setOwner(Owner $owner)
    {
        $this->owner = $owner;
    }
    
    public function addGrant(Grant $grant)
    {
        array_push($this->accessControlList, $grant);
    }
    
    public function getGrantList()
    {
        return $this->accessControlList;
    }
    
    public function processXML($content)
    {
        $xml = new XMLReader();
        $owner = new Owner();
        try {
            $xml->XML($content);
            while ($xml->read()) {
                if ($xml->nodeType == XMLReader::ELEMENT) {
                    if ($xml->name == 'Owner') {
                        while ($xml->read()) {
                            if ($xml->nodeType == XMLReader::ELEMENT) {
                                if ($xml->name == 'ID') {
                                    $xml->read();
                                    if ($xml->hasValue) {
                                        $owner->setId($xml->value);
                                    }
                                }
                                if ($xml->name == 'DisplayName') {
                                    $xml->read();
                                    if ($xml->hasValue) {
                                        $owner->setDisplayName($xml->value);
                                    }
                                }
                            }
                            if ($xml->name == 'Owner') {
                                break;
                            }
                        }
                    }
                    
                    if ($xml->name == 'Grant') {
                        $grant = new Grant();
                        while ($xml->read()) {
                            if ($xml->nodeType == XMLReader::ELEMENT) {
                                if ($xml->name == 'Permission') {
                                    $xml->read();
                                    if ($xml->hasValue) {
                                        $grant->setProtection($xml->value);
                                    }
                                }
                                if ($xml->name == 'Grantee') {
                                    $grantee = new Grantee();
                                    if ($xml->hasAttributes) {
                                        $type = $xml->getAttribute('xsi:type');
                                        $grantee->setType($type);
                                        
                                        switch (strtolower($type)) {
                                        case strtolower('CanonicalUser') :
                                            while ($xml->read()) {
                                                if ($xml->nodeType == XMLReader::ELEMENT) {
                                                    if ($xml->name == 'ID') {
                                                        $xml->read();
                                                        if ($xml->hasValue) {
                                                            $grantee->setId($xml->value);
                                                        }
                                                    }
                                                    if ($xml->name == 'DisplayName') {
                                                        $xml->read();
                                                        if ($xml->hasValue) {
                                                            $grantee->setDisplayName($xml->value);
                                                        }
                                                    }
                                                }
                                                if ($xml->name == 'Grantee') {
                                                    break;
                                                }
                                            }
                                            break;
                                        case strtolower('AmazonCustomerByEmail') :
                                            while ($xml->read()) {
                                                if ($xml->nodeType == XMLReader::ELEMENT) {
                                                    if ($xml->name == 'EmailAddress') {
                                                        $xml->read();
                                                        if ($xml->hasValue) {
                                                            $grantee->setEmail($xml->value);
                                                        }
                                                    }
                                                }
                                                if ($xml->name == 'Grantee') {
                                                    break;
                                                }
                                            }
                                            break;
                                        case strtolower('Group') :
                                            while ($xml->read()) {
                                                if ($xml->nodeType == XMLReader::ELEMENT) {
                                                    if ($xml->name == 'URI') {
                                                        $xml->read();
                                                        if ($xml->hasValue) {
                                                            $grantee->setUri($xml->value);
                                                        }
                                                    }
                                                }
                                                if ($xml->name == 'Grantee') {
                                                    break;
                                                }
                                            }
                                            break;
                                        }
                                    }
                                    $grant->setGrantee($grantee);
                                
                                }
                            }
                            if ($xml->name == 'Grant') {
                                break;
                            }
                        }
                        $this->addGrant($grant);
                    }
                }
            }
            $xml->close();
            $this->setOwner($owner);
            //$this->
        } catch (Exception $e) {
            throw new MalformedACLErrorException();
        }
        $this->_validatePolicyDocument();
    }
    
    private function _validatePolicyDocument()
    {
        $owner = $this->getOwner();
        $grants = $this->getGrantList();
        
        if (!$owner->getID()) {
            throw new InvalidPolicyDocumentException();
        }
        
        foreach ($grants as $grant) {
            
            if (!$grant->getProtection()) {
                throw new InvalidPolicyDocumentException();
            }
            
            $grantee = $grant->getGrantee();
            $type = $grantee->getType();
            
            switch (strtolower($type)) {
            case strtolower('CanonicalUser') :
                if (!$grantee->getId()) {
                    throw new InvalidPolicyDocumentException();
                }
                break;
            case strtolower('AmazonCustomerByEmail') :
                if (!$grantee->getEmail()) {
                    throw new InvalidPolicyDocumentException();
                }
                break;
            case strtolower('Group') :
                if (!$grantee->getUri()) {
                    throw new InvalidPolicyDocumentException();
                }
                break;
            default :
                throw new InvalidPolicyDocumentException();
                break;
            }
        }
    }
    
    public function generateXML()
    {
        $this->_validatePolicyDocument();
        $owner = $this->getOwner();
        $xml = new XMLWriter();
        $xml->openMemory();
        //$xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('AccessControlPolicy');
        $xml->startElement('Owner');
        $xml->writeElement("ID", $owner->getId());
        if ($owner->getDisplayName()) {
            $xml->writeElement("DisplayName", $owner->getDisplayName());
        }
        $xml->endElement();
        $xml->startElement('AccessControlList');
        $grants = $this->getGrantList();
        foreach ($grants as $grant) {
            $xml->startElement('Grant');
            $grantee = $grant->getGrantee();
            $type = $grantee->getType();
            $xml->startElement('Grantee');
            $xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            switch (strtolower($type)) {
            case strtolower('CanonicalUser') :
                $xml->writeAttribute('xsi:type', 'CanonicalUser');
                $xml->writeElement("ID", $grantee->getId());
                if ($grantee->getDisplayName()) {
                    $xml->writeElement("DisplayName", $grantee->getDisplayName());
                }
                break;
            case strtolower('AmazonCustomerByEmail') :
                $xml->writeAttribute('xsi:type', 'AmazonCustomerByEmail');
                $xml->writeElement("EmailAddress", $grantee->getEmail());
                break;
            case strtolower('Group') :
                $xml->writeAttribute('xsi:type', 'Group');
                $xml->writeElement("URI", $grantee->getUri());
                break;
            }
            $xml->endElement();
            $xml->writeElement("Permission", strtoupper($grant->getProtection()));
            $xml->endElement();
        }
        $xml->endElement();
        $xml->endElement();
        //$xml->endDocument();
        

        return $xml->outputMemory(true);
    }

}

class Owner
{
    private $id;
    
    public function __construct($id = null, $displayName = null)
    {
        $this->setDisplayName($displayName);
        $this->setId($id);
    }
    
    /**
     * @return unknown
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }
    
    /**
     * @return unknown
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * @param unknown_type $displayName
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }
    
    /**
     * @param unknown_type $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    private $displayName;
}

class Grant
{
    private $grantee;
    private $protection;
    
    public function __construct(Grantee $grantee = null, $protection = null)
    {
        if ($grantee) {
            $this->setGrantee($grantee);
        } else {
            $this->grantee = new Grantee();
        }
        $this->protection = $protection;
    }
    
    /**
     * @return unknown
     */
    public function getGrantee()
    {
        return $this->grantee;
    }
    
    /**
     * @param unknown_type $grantee
     */
    public function setGrantee(Grantee $grantee)
    {
        $this->grantee = $grantee;
    }
    
    /**
     * @return unknown
     */
    public function getProtection()
    {
        return $this->protection;
    }
    
    /**
     * @param unknown_type $protection
     */
    public function setProtection($protection)
    {
        $this->protection = strtoupper($protection);
    }
}

class Grantee
{
    private $uri;
    private $email;
    private $displayName;
    private $type;
    private $id;
    
    public function __construct($type = null, $displayName = null, $id = null, $email = null, $uri = null)
    {
        $this->setDisplayName($displayName);
        $this->setType($type);
        $this->setEmail($email);
        $this->setId($id);
        $this->setUri($uri);
    }
    
    /**
     * @return unknown
     */
    public function getEmail()
    {
        return $this->email;
    }
    
    /**
     * @param unknown_type $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }
    
    /**
     * @return unknown
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }
    
    /**
     * @return unknown
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * @return unknown
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * @return unknown
     */
    public function getUri()
    {
        return $this->uri;
    }
    
    /**
     * @param unknown_type $displayName
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }
    
    /**
     * @param unknown_type $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    
    /**
     * @param unknown_type $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
    
    /**
     * @param unknown_type $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

}

?>
