<?php
/**
 * SimpleStorageService
 * 
 * The class reproduces the S3 storage service. You can use it as a mock, 
 * clone or a fail over service. It's almost a full functional version of
 * S3, minus billing and logging.
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
class SimpleStorageService
{

    public function process ($service_type = false)
    {
        $result  = null;
        $service = null;
        
        switch (strtolower($service_type)) {
        case "soap":
            // initialise soap class
            require_once "lib/soap.php";
        	$service = new SoapStorage();
            break;
        case "rest":
            // initialise rest class
            require_once "lib/rest.php";
            $service = new RestStorage();
            break;
        default:
            // todo: report server error
            $result = false;
        }
        
        if ($service)
        {   
        	$result = $service->processRequest();
        }
        
        return $result;
    }
}
?>