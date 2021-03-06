<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Interface to choose preferences
 *
 * @author Anakeen
 * @version $Id: generic_prefs.php,v 1.2 2007/05/04 16:11:40 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage
 */
/**
 */

include_once ("FDL/Class.Doc.php");
include_once ("GENERIC/generic_util.php");
function generic_prefs(Action & $action)
{
    $famid = $action->getArgument("famid"); // family id
    $dirid = $action->getArgument("dirid"); // last searched
    $onefamOrigin = $action->getArgument("onefam");
    $action->parent->AddJsRef($action->GetParam("CORE_JSURL") . "/resizeimg.js");
    
    $dbaccess = $action->getParam("FREEDOM_DB");
    $fdoc = new_doc($dbaccess, $famid);
    if (!$fdoc->isAlive()) {
        $action->addWarningMsg(sprintf(_("Family (#%s) not exists") , $famid));
        redirect($action, "GENERIC", "GENERIC_LOGO", $action->GetParam("CORE_STANDURL"));
    }
    
    $action->lay->set("famtitle", $fdoc->title);
    $action->lay->set("famid", $famid);
    $action->lay->set("dirid", $dirid);
    $action->lay->set("onefam", $onefamOrigin);
    $tabletters = getTabLetter($action, $famid);
    if ($tabletters == 'Y') $action->lay->set("lettercheck", "checked");
    else $action->lay->set("lettercheck", "");
    
    $inh = getInherit($action, $famid);
    if ($inh == 'N') $action->lay->set("inhcheck", "");
    else $action->lay->set("inhcheck", "checked");
    
    $fchild = $fdoc->GetChildFam();
    $action->lay->set("viewinh", (count($fchild) > 0));
    if (count($fchild) > 0) {
        $tftitle = array();
        foreach ($fchild as $k => $v) {
            $tftitle[] = $v["title"];
        }
        $action->lay->set("ftitle", implode(", ", $tftitle));
    }
    
    $viewmode = getViewMode($action, $famid);
    $splitmode = getSplitMode($action, $famid);
    
    $action->lay->set("iconsrc", $fdoc->getIcon());
    $action->lay->set("dispocheck1", "");
    $action->lay->set("dispocheck2", "");
    $action->lay->set("dispocheck3", "");
    $action->lay->set("dispocheck4", "");
    if (($viewmode == 'abstract') && ($splitmode == 'V')) $action->lay->set("dispocheck1", "checked");
    if (($viewmode == 'column') && ($splitmode == 'H')) $action->lay->set("dispocheck2", "checked");
    if (($viewmode == 'column') && ($splitmode == 'V')) $action->lay->set("dispocheck3", "checked");
    if (($viewmode == 'abstract') && ($splitmode == 'H')) $action->lay->set("dispocheck4", "checked");
}
?>