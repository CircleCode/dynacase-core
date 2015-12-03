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
 * @version $Id: generic_editnewcatg.php,v 1.9 2005/06/28 08:37:46 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage
 */
/**
 */
// ---------------------------------------------------------------
// $Id: generic_editnewcatg.php,v 1.9 2005/06/28 08:37:46 eric Exp $
// $Source: /home/cvsroot/anakeen/freedom/freedom/Action/Generic/generic_editnewcatg.php,v $
// ---------------------------------------------------------------
include_once ("FDL/Class.Dir.php");
include_once ("GENERIC/generic_util.php");
// -----------------------------------
function generic_editnewcatg(Action & $action)
{
    // -----------------------------------
    global $dbaccess;
    
    $dbaccess = $action->dbaccess;
    $homefld = new_Doc($dbaccess, getDefFld($action));
    
    $stree = getChildCatg($homefld->id, 1);
    
    reset($stree);
    
    $action->lay->SetBlockData("CATG", $stree);
    $action->lay->Set("topdir", getDefFld($action));
}
