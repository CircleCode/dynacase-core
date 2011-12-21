#!/usr/bin/env php
<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * WHAT SHELL
 *
 * @author Anakeen 2002
 * @version $Id: wsh.php,v 1.35 2008/05/06 08:43:33 jerome Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 */

require_once 'getopts.php';

ini_set("max_execution_time", "3600");
include_once ("WHAT/Lib.Prefix.php");
include_once ('Class.Action.php');
include_once ('Class.Application.php');
include_once ('Class.Session.php');
include_once ('Class.Log.php');

define('WSH_GETOPTS_LEGACY', 'WSH_GETOPTS_LEGACY');
define('WSH_GETOPTS_NEW', 'WSH_GETOPTS_NEW');

define('APIDIR', 'API');

function print_usage()
{
    print "Usage:\n" .
        "\twsh.php --app <APPLICATION-NAME> --action <ACTION-NAME> [--arg <ARG-NAME>=<ARG-VAL> ...] :\n" .
        "\t\texecute an action\n" .
        "\twsh.php --api <API-NAME> [--arg <ARG-NAME>=<ARG-VAL> ...] :\n" .
        "\t\texecute an api function\n" .
        "\twsh.php --listapi :\n" .
        "\t\tview api list\n";
}

function print_listApi($pretty=true) {
    if($pretty){
        $cmdListApi = sprintf('ls -1 %s | sed -n -e \'s/\(.*\)\.php$/\t\- \1/p\'', escapeshellarg(DEFAULT_PUBDIR . APIDIR));
    } else {
        $cmdListApi = sprintf('ls -1 %s | sed -n -e \'s/\.php$//p\'', escapeshellarg(DEFAULT_PUBDIR.APIDIR));
    }
    print "application list :\n";
    print shell_exec($cmdListApi);
    print "\n";
    exit(0);
}

wbar(1, -1, "initialisation");
$log = new Log("", "index.php");

$CoreNull = "";
global $CORE_LOGLEVEL;
// get param
global $_GET;
global $_SERVER;

if (isset($_SERVER['HTTP_HOST'])) {
    print "<BR><H1>:~(</H1>";
    exit(1);
}
if (count($argv) == 1) {
    print_usage();
    
    exit(1);
}

/*
 * Parse arguments
 */

$wshOpts = array();

// for compatibility, try the old way first
$wshGetOptsMode = WSH_GETOPTS_NEW;
foreach ($argv as $k => $v) {
    if (preg_match("/--([^=]+)=(.+)/", $v, $reg)) {
        $wshOpts[$reg[1]] = $reg[2];
        // at least one of the params was given
        // the old way.
        // consider everything is given the old way
        $wshGetOptsMode = WSH_GETOPTS_LEGACY;
    } else if (preg_match("/--(.+)/", $v, $reg)) {
        if ($reg[1] == "listapi") {
            print_listApi();
        }
        $_GET[$reg[1]] = true;
    }
}

if($wshGetOptsMode === WSH_GETOPTS_NEW){
    $wshOpts = getopts(array(
        'listapi' => array(
            'switch' => array('l', 'listapi'),
            'type'   => GETOPT_SWITCH
        ),
        'api'     => array(
            'switch' => array('s', 'script', 'api'),
            'type'   => GETOPT_VAL
        ),
        'app'     => array(
            'switch' => array('A', 'App', 'app'),
            'type'   => GETOPT_VAL
        ),
        'action'     => array(
            'switch' => array('a', 'action'),
            'type'   => GETOPT_VAL
        ),
        'params'     => array(
            'switch' => array('p', 'param'),
            'type'   => GETOPT_KEYVAL
        ),
        'userid'     => array(
            'switch' => array('u', 'userid'),
            'type'   => GETOPT_VAL
        )
    ), $_SERVER['argv']);

    if($wshOpts['listapi']){
        print_listApi(true);
    } elseif((!$wshOpts["api"])
        && (!$wshOpts["app"]
            || !$wshOpts["action"])){
        print_usage();
        exit(1);
    }

    // unset are required since the code
    // use things like if(isset($_GET['api']))
    // TODO: it would be better to use
    // if( isset($_GET['api']) && !empty($_GET['api']) )
    if ($wshOpts[api] === 0) {
        unset($wshOpts[api]);
    }
    if ($wshOpts[app] === 0) {
        unset($wshOpts[app]);
    }
    if ($wshOpts[action] === 0) {
        unset($wshOpts[action]);
    }
    if ($wshOpts[userid] === 0) {
        unset($wshOpts[userid]);
    }

    // unset are required because of the default strict mode
    // in ApiUsage / ActionUsage
    if (isset($wshOpts[cmdline])) {
        unset($wshOpts[cmdline]);
    }
    if (isset($wshOpts[listapi])) {
        unset($wshOpts[listapi]);
    }

    $wshParams = $wshOpts['params'];
    unset($wshOpts['params']);
    $_GET = array_merge($_GET, $wshOpts, $wshParams);
} else {
    $_GET = array_merge($_GET, $wshOpts);
}
// EO Parse arguments

$core = new Application();
if ($core->dbid < 0) {
    print "Cannot access to main database";
    exit(1);
}

if (isset($_GET["userid"])) $core->user = new User("", $_GET["userid"]); //special user
$core->Set("CORE", $CoreNull);
$core->session = new Session();
if (!isset($_GET["userid"])) $core->user = new User("", 1); //admin
$CORE_LOGLEVEL = $core->GetParam("CORE_LOGLEVEL", "IWEF");

$hostname = LibSystem::getHostName();
$puburl = $core->GetParam("CORE_PUBURL", "http://" . $hostname . "/freedom");

ini_set("memory_limit", $core->GetParam("MEMORY_LIMIT", "32") . "M");

$absindex = $core->GetParam("CORE_URLINDEX");
if ($absindex == '') {
    $absindex = "$puburl/"; // try default
    
}
if ($absindex) $core->SetVolatileParam("CORE_EXTERNURL", $absindex);
else $core->SetVolatileParam("CORE_EXTERNURL", $puburl . "/");

$core->SetVolatileParam("CORE_PUBURL", "."); // relative links
$core->SetVolatileParam("CORE_ABSURL", $puburl . "/"); // absolute links
$core->SetVolatileParam("CORE_JSURL", "WHAT/Layout");
$core->SetVolatileParam("CORE_ROOTURL", "$absindex?sole=R&");
$core->SetVolatileParam("CORE_BASEURL", "$absindex?sole=A&");
$core->SetVolatileParam("CORE_SBASEURL", "$absindex?sole=A&"); // no session
$core->SetVolatileParam("CORE_STANDURL", "$absindex?sole=Y&");
$core->SetVolatileParam("CORE_SSTANDURL", "$absindex?sole=Y&"); // no session
$core->SetVolatileParam("CORE_ASTANDURL", "$absindex?sole=Y&"); // absolute links
$core->SetVolatileParam("ISIE", false);

if (isset($_GET["app"])) {
    $appl = new Application();
    $appl->Set($_GET["app"], $core);
} else {
    $appl = $core;
}

$action = new Action();
if (isset($_GET["action"])) {
    $action->Set($_GET["action"], $appl);
} else {
    $action->Set("", $appl);
}
// init for gettext
setLanguage($action->Getparam("CORE_LANG"));

if (isset($_GET["api"])) {
    $apifile = trim($_GET["api"]);
    if (!file_exists(sprintf("%s/API/%s.php", DEFAULT_PUBDIR, $apifile))) {
        echo sprintf(_("API file %s not found\n") , "API/" . $apifile . ".php");
    } else {
        try {
            include ("API/" . $apifile . ".php");
        }
        catch(Exception $e) {
            switch ($e->getCode()) {
                case THROW_EXITERROR:
                    echo sprintf(_("Error : %s\n") , $e->getMessage());
                    exit(1);
                    break;

                default:
                    echo sprintf(_("Caught Exception : %s\n") , $e->getMessage());
                    exit(1);
            }
        }
    }
} else {
    if (!isset($_GET["wshfldid"])) {
        try {
            echo ($action->execute());
        }
        catch(Exception $e) {
            switch ($e->getCode()) {
                case THROW_EXITERROR:
                    echo sprintf(_("Error : %s\n") , $e->getMessage());
                    exit(1);
                    break;

                default:
                    echo sprintf(_("Caught Exception : %s\n") , $e->getMessage());
                    exit(1);
            }
        }
    } else {
        // REPEAT EXECUTION FOR FREEDOM FOLDERS
        $dbaccess = $appl->GetParam("FREEDOM_DB");
        if ($dbaccess == "") {
            print "Database not found : param FREEDOM_DB";
            exit;
        }
        include_once ("FDL/Class.Doc.php");
        $http_iddoc = "id"; // default correspondance
        if (isset($_GET["wshfldhttpdocid"])) $http_iddoc = $_GET["wshfldhttpdocid"];
        $fld = new_Doc($dbaccess, $_GET["wshfldid"]);
        $ld = $fld->getContent();
        foreach ($ld as $k => $v) {
            $_GET[$http_iddoc] = $v["id"];
            try {
                echo ($action->execute());
            }
            catch(Exception $e) {
                switch ($e->getCode()) {
                    case THROW_EXITERROR:
                        echo sprintf(_("Error : %s\n") , $e->getMessage());
                        break;

                    default:
                        echo sprintf(_("Caught Exception : %s\n") , $e->getMessage());
                }
            }
            echo "<hr>";
        }
    }
}

wbar(-1, -1, "completed");

return (0);
?>