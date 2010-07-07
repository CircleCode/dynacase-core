<?php

/**
 * LibSystem class
 *
 * This class provides methods for querying system informations
 *
 * @author Anakeen 2009
 * @version $Id: Lib.System.php,v 1.4 2009/01/16 13:33:01 jerome Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package WHAT
 * @subpackage
 */
 /**
 */

class LibSystem {

  function getCommandPath($cmdname) {
    $path_env = getenv("PATH");
    if( $path_env == false ) {
      return false;
    }
    foreach (explode(":", $path_env) as $path) {
      if( file_exists("$path/$cmdname") ) {
	return "$path/$cmdname";
      }
    }
    return false;
  }
  
  function getHostName() {
    return php_uname('n');
  }
  
  function getHostIPAddress($hostname="") {
    if( $hostname == false ) {
      $hostname = LibSystem::getHostName();
    }
    $ip = gethostbyname($hostname);
    if( $ip == $hostname ) {
      return false;
    }
    return $ip;
  }

  function getServerName() {
    return getenv("SERVER_NAME");
  }

  function getServerAddr() {
    return getenv("SERVER_ADDR");
  }

  function runningInHttpd() {
    return LibSystem::getServerAddr();
  }

  function ssystem($args, $opt=null) {
    $pid = pcntl_fork();
    if( $pid == -1 ) {
      return -1;
    }
    if( $pid != 0 ) {
      $ret = pcntl_waitpid($pid, $status);
      if( $ret == -1 ) {
	return -1;
      }
      return pcntl_wexitstatus($status);
    }
    $envs = array();
    if( $opt && array_key_exists('envs', $opt) && is_array($opt['envs']) ) {
      $envs = $opt['envs'];
    }
    if( $opt && array_key_exists('closestdin', $opt) && $opt['closestdin'] === true) {
      fclose(STDIN);
    }
    if( $opt && array_key_exists('closestdout', $opt) && $opt['closestdout'] === true) {
      fclose(STDOUT);
    }
    if( $opt && array_key_exists('closestderr', $opt) && $opt['closestderr'] === true) {
      fclose(STDERR);
    }
    $cmd = array_shift($args);
    pcntl_exec($cmd, $args, $envs);
  }

  function getAbsolutePath($path) {
    if( is_link($path) ) {
      $path = readlink($path);
    }
    return realpath($path);
  }

  function tempnam($dir, $prefix) {
    if( $dir === null || $dir === false ) {
      $dir = null;
      foreach( array('TMP', 'TMPDIR') as $env ) {
	$dir = getenv($env);
	if( $dir !== false && is_dir($dir) && is_writable($dir) ) {
	  break;
	}
      }
    }
    if( $dir === null || $dir === false ) {
      $dir = null;
      foreach( array(getTmpDir(), '/tmp', '/var/tmp') as $tmpdir ) {
	if( is_dir($tmpdir) && is_writable($tmpdir) ) {
	  $dir = $tmpdir;
	  break;
	}
      }
    }
    return tempnam($dir, $prefix);
  }

}

?>