<?php

/**
 * basicAuthenticator class
 *
 * This class provides methods for HTTP Basic authentication
 *
 * @author Anakeen 2009
 * @version $Id: Class.basicAuthenticator.php,v 1.3 2009/01/16 13:33:00 jerome Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage
 */
 /**
 */

Class openAuthenticator {

  private $privatelogin=false;
  
  /**
   * no need to ask authentication
   */
  public function checkAuthentication() {    
    include_once('WHAT/Lib.Http.php');
    $privatekey=getHttpVars("privateid");
    if (! $privatekey) return false;
    return $this->getLoginFromPrivateKey($privatekey);
  }

  public function getLoginFromPrivateKey($privatekey) {
    include_once('WHAT/Class.UserToken.php');
    include_once('WHAT/Class.QueryDb.php');
    include_once('WHAT/Class.User.php');
    $q=new QueryDb("","UserToken");
    $q->addQuery("token='".pg_escape_string($privatekey)."'");
    $tu=$q->Query(0,1,"TABLE");
    if ($q->nb > 0) {
      $uid=$tu[0]["userid"];
      $u=new User("",$uid);
      $this->privatelogin=$u->login;
    }
    
    return  $this->privatelogin;
  }

  public function checkAuthorization($opt) {
    return TRUE;
  }
  
  /**
   * no ask
   */
  public function askAuthentication() {    
    return TRUE;
  }
  
  public function getAuthUser() {
    return $this->privatelogin;
  }
  
  /**
   * no password needed
   */
  public function getAuthPw() {
    return false;
  }
  
  /**
   * no logout
   */
  public function logout($redir_uri) {           
    header("HTTP/1.0 401 Authorization Required ");
    print _("private key is not valid");
    return true;  
  }

  /**
   **
   **
   **/
  public function setSessionVar($name, $value) {
    include_once('WHAT/Class.Session.php');
    $session_auth = new Session($this->parms{'cookie'});
    if( array_key_exists($this->parms{'cookie'}, $_COOKIE) ) {
      $session_auth->Set($_COOKIE[$this->parms{'cookie'}]);
    } else {
      $session_auth->Set();
    }
    
    $session_auth->register($name, $value);
    
    return $session_auth->read($name);
  }
  
  /**
   **
   **
   **/
  public function getSessionVar($name) {
    include_once('WHAT/Class.Session.php');
    $session_auth = new Session($this->parms{'cookie'});
    if( array_key_exists($this->parms{'cookie'}, $_COOKIE) ) {
      $session_auth->Set($_COOKIE[$this->parms{'cookie'}]);
    } else {
      $session_auth->Set();
    }
    
    return $session_auth->read($name);
  }
}

?>
