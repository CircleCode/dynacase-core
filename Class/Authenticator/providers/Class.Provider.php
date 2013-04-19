<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * provider abstract class
 *
 * @author Anakeen
 * @version $Id:  $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 */
/**
 */
/**
 * Class Provider
 * @method initializeUser
 */
abstract class Provider
{
    
    public $parms;
    public $pname;
    public $errno;
    
    const ERRNO_BUG_639 = 1;
    
    public function __construct($authprovider, $parms)
    {
        $this->parms = $parms;
        $this->pname = strtolower($authprovider);
        $this->errno = 0;
    }
    
    abstract function validateCredential($username, $password);
    
    public function validateAuthorization($opt)
    {
        return true;
    }
    
    public function canICreateUser()
    {
        if (array_key_exists('allowAutoFreedomUserCreation', $this->parms) && strtolower($this->parms{'allowAutoFreedomUserCreation'}) == 'yes' && is_callable(array(
            $this,
            'initializeUser'
        ))) {
            $this->errno = 0;
            return TRUE;
        }
        $this->errno = 0;
        return FALSE;
    }
}
