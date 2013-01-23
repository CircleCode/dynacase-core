<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Export edition
 *
 * @author Anakeen
 * @version $Id: editexport.php,v 1.4 2008/11/13 17:25:29 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage GED
 */
/**
 */

include_once ("FDL/Class.Doc.php");
include_once ("FDL/Lib.Dir.php");
include_once ("GENERIC/generic_util.php");

function editexport(Action & $action)
{
    $dbaccess = $action->GetParam("FREEDOM_DB");
    $docid = GetHttpVars("id", 0);
    $action->parent->addJsRef("lib/jquery/jquery.js");
    $doc = new_Doc($dbaccess, $docid);
    $exportId = uniqid("export");
    $action->lay->Set("dirid", $docid);
    $action->lay->Set("title", $doc->title);
    $action->lay->Set("exportid", $exportId);
    $famid = 0;
    $tclassdoc = GetClassesDoc($dbaccess, $action->user->id, $famid, "TABLE");
    $selectclass = array();
    $pref = getFamilyParameters($action, "FREEDOM_EXPORTCOLS");
    $tfam = array_keys($pref);
    foreach ($tclassdoc as $k => $cdoc) {
        $selectclass[$k]["idcdoc"] = $cdoc["id"];
        $selectclass[$k]["classname"] = getFamTitle($cdoc);
        $selectclass[$k]["pref"] = in_array($cdoc["id"], $tfam);
    }
    $action->lay->setBlockData("coptions", $selectclass);
}
?>