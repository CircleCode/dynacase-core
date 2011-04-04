<?php
/**
 * Common util functions
 *
 * @author Anakeen 2002
 * @version $Id: Lib.Common.php,v 1.50 2008/09/11 14:50:04 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package WHAT
 * @subpackage CORE
 */
/**
 */
include_once("Lib.Prefix.php");

function N_($s) {return ($s);} // to tag gettext without change text immediatly
// library of utilies functions

function print_r2($z,$ret=false) {
	print "<PRE>";
	print_r($z, $ret);
	print "</PRE>";
	flush();
}

function AddLogMsg($msg,$cut=80) {
	global $action;
	if (isset($action->parent))
	$action->parent->AddLogMsg($msg,$cut);
}

function AddWarningMsg($msg) {
	global $action;
	if (isset($action->parent))
	$action->parent->AddWarningMsg($msg);
}

function getMailAddr($userid, $full=false) {
	$user = new User("",$userid);

	if ($user->isAffected()) {
		$pren = $postn = "";
		if ($full) {
			//	$pren = ucfirst(strtolower($user->getTitle()))." <";
			// $postn = ">";
		}
		return $pren.$user->getMail().$postn;
	}
	return false;
}

function getTmpDir($def='/tmp') {
	static $tmp;
	if(isset($tmp) && !empty($tmp)) {
		return $tmp;
	}
	$tmp = getParam('CORE_TMPDIR', $def);
	if(empty($tmp)) {
		return $def;
	}
	return $tmp;
}

function getParam($name, $def="") {
	global $action;
	if ($action)  return $action->getParam($name,$def);

	// case of without what context
	include_once("Class.Action.php");
	$core = new Application();
	$core->Set("CORE",$CoreNull);
	$act = new Action();
	$act->Set("",$core);
	return  $act->getParam($name, $def);
}
/**
 *
 * @param string $name the variable
 * @param string $def default value if variable is not defined
 * @return unknown_type
 */
function getSessionValue($name, $def="") {
	global $action;
	if ($action)  return $action->read($name,$def);
	return  null;
}

function getLayoutFile($app, $layfile) {
  if (strstr($layfile,'..')) {
        return ""; // not authorized
    }

	$socStyle = Getparam("CORE_SOCSTYLE");
	if ($socStyle != "") {
		$root = Getparam("CORE_PUBDIR");
		$file = $root."/$app/Layout/$socStyle/$layfile";

		if (file_exists($file))  return($file);

	}
	return $app."/Layout/".$layfile;
}

function microtime_diff($a,$b) {
	list($a_micro, $a_int)=explode(' ',$a);
	list($b_micro, $b_int)=explode(' ',$b);
	if ($a_int>$b_int) {
		return ($a_int-$b_int)+($a_micro-$b_micro);
	} elseif ($a_int==$b_int) {
		if ($a_micro>$b_micro) {
			return ($a_int-$b_int)+($a_micro-$b_micro);
		} elseif ($a_micro<$b_micro) {
			return ($b_int-$a_int)+($b_micro-$a_micro);
		} else {
			return 0;
		}
	} else { // $a_int<$b_int
		return ($b_int-$a_int)+($b_micro-$a_micro);
	}
}
function getDebugStack($slice=1) {
	$td=@debug_backtrace(false);
	if (! is_array($td)) return;
	$t=array_slice($td,$slice);
	foreach ($t as $k=>$s) {
		unset($t[$k]["args"]); // no set arg
	}
	return $t;
}
function getDbid($dbaccess) {
	global $CORE_DBID;
	if (!isset($CORE_DBID) || !($CORE_DBID[$dbaccess])) {
		$CORE_DBID[$dbaccess] = pg_connect($dbaccess);
	}
	return $CORE_DBID[$dbaccess];
}

function getDbAccess() {
	return getDbAccessCore();
}

function getDbAccessCore() {
	return "service='".getServiceCore()."'";
}

function getDbAccessFreedom() {
	return "service='".getServiceFreedom()."'";
}

function getDbEnv() {
	error_log("Deprecated call to getDbEnv() : use getFreedomContext()");
	return getFreedomContext();
}

function getFreedomContext() {
	$freedomctx=getenv("freedom_context");
	if( $freedomctx == false || $freedomctx == "" ) {
		return "default";
	}
	return $freedomctx;
}

function getServiceCore() {
	static $pg_service=null;
	global $pubdir;

	if ($pg_service) return $pg_service;

	$freedomctx = getFreedomContext();
	if ($freedomctx != "") {
		$filename = "$pubdir/context/$freedomctx/dbaccess.php";
		if (file_exists($filename)) {
			include($filename);
		}
	}
	if ($pgservice_core == "") {
		include("dbaccess.php");
	}
	if ($pgservice_core == "") {
		error_log("Undefined pgservice_core in dbaccess.php");
		exit(1);
	}
	$pg_service = $pgservice_core;
	return $pg_service;
}

function getServiceFreedom() {
	static $pg_service=null;
	global $pubdir;

	if ($pg_service) return $pg_service;

	$freedomctx = getFreedomContext();
	if ($freedomctx != "") {
		$filename = "$pubdir/context/$freedomctx/dbaccess.php";
		if (file_exists($filename)) {
			include($filename);
		}
	}
	if ($pgservice_freedom == "") {
		include("dbaccess.php");
	}
	if ($pgservice_freedom == "") {
		error_log("Undefined pgservice_freedom in dbaccess.php");
		exit(1);
	}
	$pg_service = $pgservice_freedom;
	return $pg_service;
}

function getDbName($dbaccess) {
	error_log("Deprecated call to getDbName(dbaccess) : use getServiceName(dbaccess)");
	return getServiceName($dbaccess);
}

function getServiceName($dbaccess) {
	if (preg_match("/service='?([a-zA-Z0-9_.-]+)/", $dbaccess, $reg)) {
		return $reg[1];
	}
}

function getAuthType($freedomctx="") {
	global $pubdir;

	if( array_key_exists('authtype', $_GET) ) {
		return $_GET['authtype'];
	}

	if( $freedomctx == "" ) {
		$freedomctx = getFreedomContext();
	}
	if( $freedomctx != "" ) {
		$filename = "$pubdir/context/$freedomctx/dbaccess.php";
		if( file_exists($filename) ) {
			include($filename);
		}
	}

	if( $freedom_authtype == "" ) {
		$freedom_authtype = "apache";
	}

	return trim($freedom_authtype);
}

function getAuthProvider($freedomctx="") {
	global $pubdir;

	if( $freedomctx == "" ) {
		$freedomctx = getFreedomContext();
	}
	if( $freedomctx != "" ) {
		$filename = "$pubdir/context/$freedomctx/dbaccess.php";
		if( file_exists($filename) ) {
			include($filename);
		}
	}

	if( $freedom_authprovider == "" ) {
		$freedom_authprovider = "apache";
	}

	return trim($freedom_authprovider);
}

function getAuthProviderList($freedomctx="") {
  global $pubdir;

  return preg_split("/\s*,\s*/", getAuthProvider($freedomctx));
}

function getAuthTypeParams($freedomctx="") {
	global $pubdir;

	if( $freedomctx == "" ) {
		$freedomctx = getFreedomContext();
	}
	if( $freedomctx != "" ) {
		$filename = "$pubdir/context/$freedomctx/dbaccess.php";
		if( file_exists($filename) ) {
			include($filename);
		}
	}
	if( ! is_array($freedom_authtypeparams) ) {
		printf(_("filename %s does not contain freedom_authtypeparams variable. May be old syntax for configuration file"),$filename);
		exit;
	}

	if( ! array_key_exists(getAuthType(), $freedom_authtypeparams) ) {
		error_log(__FUNCTION__.":".__LINE__."> authtype ".getAuthType()." does not exists in freedom_authtypeparams");
		return array();
	}

	return $freedom_authtypeparams[getAuthType()];
}

function getAuthParam($freedomctx="", $provider="") {
	global $pubdir;

	if ($provider=="") return array();

	if( $freedomctx == "" ) {
		$freedomctx = getFreedomContext();
	}
	if( $freedomctx != "" ) {
		$filename = "$pubdir/context/$freedomctx/dbaccess.php";
		if( file_exists($filename) ) {
			include($filename);
		}
	}

	if( ! is_array($freedom_providers) ) {
		return array();
	}

	if( ! array_key_exists($provider, $freedom_providers) ) {
		error_log(__FUNCTION__.":".__LINE__."provider ".$provider." does not exists in freedom_providers");
		return array();
	}

	return $freedom_providers[$provider];
}

/**
 * return shell commande for wsh
 * depending of database (in case of several instances)
 * @param bool $nice set to true if want nice mode
 * @param int $userid the user identificator to send command (if 0 send like admin without specific user parameter)
 * @param bool $sudo set to true if want to be send with sudo (need /etc/sudoers correctly configured)
 * @return string the command
 */
function getWshCmd($nice=false,$userid=0,$sudo=false) {
	$freedomctx=getFreedomContext(); // choose when several databases
	$wsh="export freedom_context=\"$freedomctx\";";
	if ($nice) $wsh.= "nice -n +10 ";
	if ($sudo) $wsh.= "sudo ";
	$wsh.=GetParam("CORE_PUBDIR")."/wsh.php  ";
	$userid=intval($userid);
	if ($userid>0) $wsh.="--userid=$userid ";
	return $wsh;
}

/**
 * get the system user id
 * @return int
 */
function getUserId() {
	global $action;
	if ($action)  return $action->user->id;

	return 0;
}
/**
 * exec list of unix command in background
 * @param array $tcmd unix command strings
 */
function bgexec($tcmd,&$result,&$err) {
	$foutname = uniqid(getTmpDir()."/bgexec");
	$fout = fopen($foutname,"w+");
	fwrite($fout,"#!/bin/bash\n");
	foreach ($tcmd as $v) {
		fwrite($fout,"$v\n");
	}
	fclose($fout);
	chmod($foutname,0700);

	//  if (session_id()) session_write_close(); // necessary to close if not background cmd
	exec("exec nohup $foutname > /dev/null 2>&1 &",$result,$err);
	//if (session_id()) @session_start();
}

function wbartext($text) {
	wbar('-','-',$text);
}

function wbar($reste,$total,$text="",$fbar=false) {
	static $preste,$ptotal;
	if (!$fbar) $fbar = GetHttpVars("bar"); // for progress bar
	if ($fbar) {
		if ($reste==='-') $reste=$preste;
		else $preste=$reste;
		if ($total==='-') $total=$ptotal;
		else $ptotal=$total;
		if (file_exists("$fbar.lck")) {
			$wmode="w";
			unlink("$fbar.lck");
		} else {
			$wmode="a";
		}
		$ffbar=fopen($fbar,$wmode);
		fputs($ffbar,"$reste/$total/$text\n");
		fclose($ffbar);
	}
}

function getJsVersion() {
	include_once("Class.QueryDb.php");
	$q=new QueryDb("","param");
	$q->AddQuery("name='VERSION'");
	$l=$q->Query(0,0,"TABLE");
	$nv=0;
	foreach ($l as $k=>$v) {
		$nv+=intval(str_replace('.','',$v["val"]));
	}

	return $nv;
}

/**
 * produce an anchor mailto '<a ...>'
 * @param string to a valid mail address or list separated by comma -supported by client-
 * @param string anchor content <a...>anchor content</a>
 * @param string subject
 * @param string cc
 * @param string bcc
 * @param array treated as html anchor attribute : key is attribute name and value.. value
 * @param string force link to be produced according the value
 * @return string like user admin dbname anakeen
 */
function setMailtoAnchor($to, $acontent="", $subject="", $cc="", $bcc="", $from="", $anchorattr=array(), $forcelink="" ) {

	global $action;

	if ($to=="") return '';

	if ($forcelink=="mailto"||$forcelink=="squirrel") {
		$target = $forcelink;
	} else {
		$target = strtolower(GetParam("CORE_MAIL_LINK", "optimal"));
		if ($target=="optimal") {
			$target = "mailto";
			if ($action->user->iddomain>9) {
				$query=new QueryDb($action->dbaccess,"Application");
				$query->basic_elem->sup_where=array("name='MAIL'","available='Y'", "displayable='Y'");
				$list = $query->Query(0,0,"TABLE");
				if ($query->nb>0)  {
					$queryact=new QueryDb($action->dbaccess,"Action");
					$queryact->AddQuery("id_application=".$list[0]["id"]);
					$queryact->AddQuery("root='Y'");
					$listact = $queryact->Query(0,0,"TABLE");
					$root_acl_name=$listact[0]["acl"];
					if ($action->HasPermission($root_acl_name,$list[0]["id"])) {
						$target = "squirrel";
					}
				}
			}
		}
	}
	$prot = ($_SERVER["HTTPS"]=="on" ? "https" : "http" );
	$host = $_SERVER["SERVER_NAME"];
	$port = $_SERVER["SERVER_PORT"];

	$attrcode = "";
	if (is_array($anchorattr)) {
		foreach ($anchorattr as $k => $v) $attrcode .= ' '.$k.'="'.$v.'"';
	}

	$subject = str_replace(" ", "%20", $subject);

	switch ($target) {

		case "squirrel" :
			$link  = ' <a ';
			$link .= 'href="'.$prot."://".$host.":".$port."/".GetParam("CORE_MAIL_SQUIRRELBASE", "squirrel")."/src/compose.php?";
			$link .= "&send_to=".$to;
			$link .= ($subject!="" ? '&subject='.$subject : '');
			$link .= ($cc!="" ? '&cc='.$cc : '');
			$link .= ($bcc!="" ? '&bcc='.$bcc : '');
			$link .= '"';
			$link .= $attrcode;
			$link .= '>';
			$link .= $acontent;
			$link .= '</a>';
			break;

		case "mailto":
			$link  = '<a ';
			$link .= 'href="mailto:'.$to.'"';
			$link .= ($subject!="" ? '&Subject='.$subject : '');
			$link .= ($cc!="" ? '&cc='.$cc : '');
			$link .= ($bcc!="" ? '&bcc='.$bcc : '');
			$link .= '"';
			$link .= $attrcode;
			$link .= '>';
			$link .= $acontent;
			$link .= '</a>';
			break;

		default:
			$link = '<span '.$classcode.'>'.$acontent.'</span>';
	}
	return $link;
}


/**
 * Returns <kbd>true</kbd> if the string or array of string is encoded in UTF8.
 *
 * Example of use. If you want to know if a file is saved in UTF8 format :
 * <code> $array = file('one file.txt');
 * $isUTF8 = isUTF8($array);
 * if (!$isUTF8) --> we need to apply utf8_encode() to be in UTF8
 * else --> we are in UTF8 :)
 * </code>
 * @param mixed A string, or an array from a file() function.
 * @return boolean
 */
function isUTF8($string)
{
	if (is_array($string))   return seems_utf8(implode('', $string));
	else return seems_utf8($string);
}

/**
 * Returns <kbd>true</kbd> if the string  is encoded in UTF8.
 *
 * @param mixed $Str string
 * @return boolean
 */
function seems_utf8($Str) {
	for ($i=0; $i<strlen($Str); $i++) {
		if (ord($Str[$i]) < 0x80) $n=0; # 0bbbbbbb
		elseif ((ord($Str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
		elseif ((ord($Str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
		elseif ((ord($Str[$i]) & 0xF0) == 0xF0) $n=3; # 1111bbbb
		else return false; # Does not match any model
		for ($j=0; $j<$n; $j++) { # n octets that match 10bbbbbb follow ?
			if ((++$i == strlen($Str)) || ((ord($Str[$i]) & 0xC0) != 0x80)) return false;
		}
	}
	return true;
}

/**
 * return true if it is possible to manage USER by FREEDOM
 *
 */
function usefreedomuser() {
	if (@include_once('FDL/Lib.Usercard.php')) {
		$usefreedom=(GetParam("USE_FREEDOM_USER")!="no");
		return $usefreedom;
	}
	return false;
}

/**
 * Initialise WHAT : set global $action whithout an authorized user
 *
 */
function WhatInitialisation() {
	global $action;
	include_once('Class.User.php');
	include_once('Class.Session.php');

	$CoreNull="";
	$core = new Application();
	$core->Set("CORE",$CoreNull);
	$core->session=new Session();
	$action=new Action();
	$action->Set("",$core);

	// i18n
	$lang=$action->Getparam("CORE_LANG");
	setLanguage($lang);
}

function setSystemLogin($login) {
	global $action;
	include_once('Class.User.php');
	include_once('Class.Session.php');

	if ($login!="") {
		$action->user=new User(); //create user
		$action->user->setLoginName($login);
	}
}
/**
 * Returns a random password of specified length composed
 * with chars from the given charspace string or pattern
 */

function mkpasswd($length=8, $charspace="") {
	if( $charspace == "" ) {
		$charspace = "[:alnum:]";
	}

	// Repeat a pattern e.g. [:a:3] -> [:a:][:a:][:a:]
	$charspace = preg_replace(
			    "/(\[:[a-z]+:)(\d+)(\])/e",
			    "str_repeat('\\1\\3',\\2)",
	$charspace
	);

	// Expand [:patterns:]
	$charspace = preg_replace(
	array(
				  "/\[:alnum:\]/",
				  "/\[:extrastrong:\]/",
				  "/\[:hex:\]/",
				  "/\[:lower:\]/",
				  "/\[:upper:\]/",
				  "/\[:digit:\]/",
				  "/\[:extra:\]/",
	),
	array(
				  "[:lower:][:upper:][:digit:]",
				  "[:extra:],;:=+*/(){}[]&@#!?\"'<>",
				  "[:digit:]abcdef",
				  "abcdefghijklmnopqrstuvwxyz",
				  "ABCDEFGHIJKLMNOPQRSTUVWXYZ",
				  "0123456789",
				  "-_.",
	),
	$charspace
	);

	$passwd = "";
	for($i = 0; $i<$length; $i++) {
		$passwd .= substr($charspace, rand(0, strlen($charspace)-1), 1);
	}

	return $passwd;
}
/**
 *
 * @param string $core_lang
 */
function getLocaleConfig($core_lang='') {

	if(empty($core_lang)) {
		$core_lang = getParam("CORE_LANG", "fr_FR");
	}
	$lng = substr($core_lang, 0, 2);
	if(preg_match('#^[a-z0-9_\.-]+$#i', $core_lang) && file_exists("locale/".$lng."/lang.php")) {
		include("locale/".$lng."/lang.php");
	}
	else {
		include("locale/fr/lang.php");
	}
	if(!isset($lang) || !isset($lang[$core_lang]) || !is_array($lang[$core_lang])) {
		return false;
	}
	return $lang[$core_lang];
}

/**
 *
 * @param string $lang
 */
function setLanguage($lang) {
	global $pubdir;
	//  print "<h1>setLanguage:$lang</H1>";
	$lang.=".UTF-8";
	setlocale(LC_MESSAGES,$lang);
	setlocale(LC_CTYPE,$lang);
	setlocale(LC_MONETARY, $lang);
	setlocale(LC_TIME, $lang);
	//print $action->Getparam("CORE_LANG");
	$number = 0;
	if( is_file("$pubdir/locale/.gettextnumber") ) {
		$number = trim(@file_get_contents("$pubdir/locale/.gettextnumber"));
		if( $number == "" ) {
			$number = 0;
		}
	}

	$td="freedom-catalog$number";

	putenv ("LANG=".$lang); // needed for old Linux kernel < 2.4
	bindtextdomain ($td, "$pubdir/locale");
	bind_textdomain_codeset($td, 'utf-8');
	textdomain ($td);
	mb_internal_encoding('UTF-8');
}


?>
