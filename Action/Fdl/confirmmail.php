<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Generated Header (not documented yet)
 *
 * @author Anakeen
 * @version $Id: confirmmail.php,v 1.3 2008/02/28 17:50:36 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage
 */
/**
 */
// ---------------------------------------------------------------
// $Id: confirmmail.php,v 1.3 2008/02/28 17:50:36 eric Exp $
// $Source: /home/cvsroot/anakeen/freedom/freedom/Action/Fdl/confirmmail.php,v $
// ---------------------------------------------------------------
include_once ("FDL/editmail.php");
// -----------------------------------
// -----------------------------------
function confirmmail(&$action)
{
    
    $nextstate = GetHttpVars("state");
    $ulink = GetHttpVars("ulink");
    editmail($action);
    
    $action->lay->eSet("ulink", ($ulink ? $ulink : "Y"));
    $action->lay->eSet("state", $nextstate);
    $action->lay->eSet("tstate", _($nextstate));
}
