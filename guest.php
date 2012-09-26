<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Main program to activate action in WHAT software in guest mode
 *
 * @author Anakeen
 * @version $Id: guest.php,v 1.24 2008/12/16 15:51:53 jerome Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage
 */
/**
 */

if (file_exists('maintenance.lock')) {
    include_once ('TOOLBOX/stop.php');
    exit(0);
}

include_once ('WHAT/Lib.Main.php');

$authtype = getAuthType();
if ($authtype != 'basic') {
    unset($_SERVER['PHP_AUTH_USER']);
}
#
# This is the main body of App manager
# It is used to launch application and
# function giving them all necessary environment
# element
#
#

/**
 * @var Action $action
 */
$action = null;
getmainAction($auth = null, $action);
/**
 * @var Action $action
 */
if ($action->user->id != ANONYMOUS_ID) {
    // reopen a new anonymous session
    setcookie(Session::PARAMNAME, $action->session->id, 0);
    unset($_SERVER['PHP_AUTH_USER']); // cause IE send systematicaly AUTH_USER & AUTH_PASSWD
    $action->session->Set("");
    $action->parent->SetSession($action->session);
}
if ($action->user->id != ANONYMOUS_ID) {
    // reverify
    print "<B>:~((</B>";
    exit;
}

executeAction($action);
?>
