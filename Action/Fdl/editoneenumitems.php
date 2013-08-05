<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * @param Action $action
 */
function editoneenumitems(Action & $action)
{
    $usage = new ActionUsage($action);
    $famid = $usage->addRequiredParameter("famid", "Family id");
    $enumid = $usage->addRequiredParameter("enumid", "Enum id");
    $viewoldinterface = $usage->addOptionalParameter("viewoldinterface", "Yes to view old interface", array(
        "yes",
        "no"
    ) , "no");
    $usage->setStrictMode(false);
    //Maybe use exception and try vatch to send info in json for datatable
    $usage->verify();
    
    if ($viewoldinterface == "yes") {
        //@TODO: Exit to old interface
        
    }
    
    $action->lay->set("famid", $famid);
    $action->lay->set("enumid", $enumid);
    
    $action->parent->addCssRef("css/dcp/jquery-ui.css");
    $action->parent->addCssRef("lib/jquery-dataTables/css/jquery.dataTables.css");
    $action->parent->addCssRef("ACCESS:user_access.css");
    $action->parent->addCssRef("FDL:editoneenumitems.css");
    
    $action->parent->addJsRef("lib/jquery/jquery.js");
    $action->parent->addJsRef("lib/jquery-ui/js/jquery-ui.js");
    $action->parent->addJsRef("lib/jquery-dataTables/js/jquery.dataTables.js");
    $action->parent->addJsRef("FDL:editenumitemswidget.js");
    $action->parent->addJsRef("FDL:editoneenumitems.js");
}
