<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * View ask for a document
 *
 * @author Anakeen
 * @version $Id: fdl_card.php,v 1.42 2008/12/02 15:20:52 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage
 */
/**
 */

include_once ("FDL/Class.Dir.php");
/**
 * View a document
 * @param Action &$action current action
 * @global docid Http var : document identifier where use the ask
 * @global waskid Http var : document identifier of the ask
 */
function viewwask(&$action)
{
    $docid = GetHttpVars("docid");
    $answers = GetHttpVars("answer");
    $dbaccess = $action->GetParam("FREEDOM_DB");
    if ($docid == "") $action->exitError(_("no document reference"));
    $doc = new_Doc($dbaccess, $docid);
    if (!$doc->isAlive()) $action->exitError(sprintf(_("unknow document reference '%s'") , GetHttpVars("docid")));
    $err = $doc->control("view");
    if ($err) $action->exitError($err);
    
    $answers = $doc->getWasks();
    $action->lay->setBlockData("WASK", $answers);
    $action->lay->set("docid", $doc->id);
}
?>