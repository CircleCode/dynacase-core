<?php

/**
 * Authenticator class
 *
 * Top-level class to authenticate and authorize users
 *
 * @author Anakeen 2009
 * @version $Id: Class.Authenticator.php,v 1.6 2009/01/16 13:33:00 jerome Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage
 */
 /**
 */

Class Authenticator {
  public $parms = array();
  private $authenticator = null;
  
  public function __construct($parms) {
    $this->parms = $parms;
    if( ! array_key_exists('type', $this->parms) ) {
      throw new Exception(__CLASS__."::".__FUNCTION__." "."Error: undefined type in constructor");
    }
    $ret = @include_once('WHAT/Class.'.$this->parms{'type'}."Authenticator.php");
    if( $ret === FALSE ) {
      throw new Exception(__CLASS__."::".__FUNCTION__." "."Error: WHAT/Class.".$this->parms{'type'}."Authenticator.php not found");
    }
    if( ! class_exists($this->parms{'type'}."Authenticator") ) {
      throw new Exception(__CLASS__."::".__FUNCTION__." "."Error: ".$this->parms{'type'}."Authenticator class not found");
    }
    $authclass = $this->parms{'type'}."Authenticator";
    $this->authenticator = new $authclass($this->parms);
  }
  
  public function checkAuthentication() {
    return $this->authenticator->checkAuthentication();
  }

  public function checkAuthorization($opt=array()) {
    return $this->authenticator->checkAuthorization($opt);
  }
  
  public function validateCredential($username, $password) {
    return $this->authenticator->validateCredential($username, $password);
  }
  
  public function askAuthentication() {
    return $this->authenticator->askAuthentication();
  }
  
  public function getAuthUser() {
    return $this->authenticator->getAuthUser();
  }
  
  public function getAuthPw() {
    return $this->authenticator->getAuthPw();
  }
  
  public function logout($redir_uri="") {
    return $this->authenticator->logout($redir_uri);
  }

  public function setSessionVar($name, $value) {
    if( is_callable(array($this->authenticator, 'setSessionVar')) ) {
      return $this->authenticator->setSessionVar($name, $value);
    }
    return Null;
  }
  
  public function getSessionVar($name) {
    if( is_callable(array($this->authenticator, 'getSessionVar')) ) {
      return $this->authenticator->getSessionVar($name);
    }
    return Null;
  }

}

?>
