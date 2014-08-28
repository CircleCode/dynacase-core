<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Change default folder for family
 *
 * @author Anakeen
 * @version $Id: moddfld.php,v 1.8 2005/06/28 08:37:46 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage GED
 */
/**
 */

include_once ("FDL/Class.Doc.php");
include_once ("FDL/Class.Dir.php");
include_once ("FDL/Class.DocAttr.php");
include_once ("FDL/freedom_util.php");
// -----------------------------------
function moddfld(Action & $action)
{
    // -----------------------------------
    // Get all the params
    $docid = GetHttpVars("docid");
    $current = (GetHttpVars("current", "N") == "Y");
    $newfolder = (GetHttpVars("autofolder", "N") == "Y");
    $fldid = GetHttpVars("dfldid");
    
    if ($docid == 0) $action->exitError(_("the document is not referenced: cannot apply defaut folder"));
    
    $dbaccess = $action->GetParam("FREEDOM_DB");
    // initialise object
    $doc = new_Doc($dbaccess, $docid);
    // create folder if auto
    if ($newfolder) {
        $fldid = createAutoFolder($doc);
        if ($fldid === false) {
            $action->exitError(_("Error creating new default folder."));
        }
    } else {
        if ($fldid === "0") {
            $fldid = "";
        } else {
            $fld = new_Doc($dbaccess, $fldid);
            if ($fld === null || !$fld->isAlive()) {
                $action->exitError(sprintf(_("Folder with id '%s' not found.") , $fldid));
            }
            if ($fld->defDoctype != 'D') {
                $action->exitError(sprintf(_("Folder with id '%s' is not a folder.") , $fld->id));
            }
            $fldid = $fld->id;
        }
    }
    
    if ($current) $doc->cfldid = $fldid;
    else $doc->dfldid = $fldid; // new default folder
    // test object permission before modify values (no access control on values yet)
    $doc->lock(true); // enabled autolock
    $err = $doc->canEdit();
    if ($err != "") $action->ExitError($err);
    
    $doc->Modify();
    
    $doc->unlock(true); // disabled autolock
    redirect($action, "FDL", "FDL_CARD&id=$docid", $action->GetParam("CORE_STANDURL"));
}
