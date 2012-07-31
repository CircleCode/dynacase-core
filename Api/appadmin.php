<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Add, modify or delete WHAT application
 *
 *
 * @param string $appname internal name of the application
 * @param string $method may be "init","reinit","update","delete"
 * @author Anakeen 2003
 * @version $Id: appadmin.php,v 1.8 2008/05/21 07:27:02 marc Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage WSH
 */
/**
 */
include_once ("Class.Application.php");
global $action;

$usage = new ApiUsage();
$usage->setText("Manage application");
$appname = $usage->addNeeded("appname", "application name");
$method = $usage->addOption("method", "action to do", array(
    "init",
    "update",
    "reinit",
    "delete"
) , "init");

$usage->verify();

echo " $appname...$method\n";

$app = new Application();

$Null = "";
if ($method != "delete") {
    $app->Set($appname, $Null, null, true);
    if ($method == "reinit") {
        $ret = $app->InitApp($appname, false, null, true);
        if ($ret === false) {
            $action->exitError(sprintf("Error initializing application '%s'.", $appname));
        }
    }
    if ($method == "update") {
        $ret = $app->InitApp($appname, true);
        if ($ret === false) {
            $action->exitError(sprintf("Error updating application '%s'.", $appname));
        }
    }
}
if ($method == "delete") {
    $app->Set($appname, $Null, null, false);
    if ($app->isAffected()) {
        $err = $app->DeleteApp();
        if ($err != '') {
            $action->exitError($err);
        }
    } else {
        echo "already deleted";
    }
}
