<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

include_once ("FDL/Class.Dir.php");
include_once ("FDL/editcard.php");
/**
 * Compose family parameter edit field
 * @param Action $action
 * @return bool
 */
function editfamilyparameter(Action & $action)
{
    $usage = new ActionUsage($action);
    $famid = $usage->addNeededParameter("famid", _("family id"));
    $attrid = $usage->addNeededParameter("attrid", _("attribute id"));
    $default = $usage->addOptionnalParameter("emptyValue", _("value for empty field"));
    $value = $usage->addOptionnalParameter("value", _("value in field"));
    $onChange = $usage->addOptionnalParameter("submitOnChange", _("Sending input on change?"));
    $localSubmit = $usage->addOptionnalParameter("localSubmit", _("Adding button to submit")) == "yes" ? true : false;
    $submitLabel = $usage->addOptionnalParameter("submitLabel", _("Label of submit button") , array() , _("Submit"));
    $usage->setStrictMode();
    $usage->verify();
    
    editmode($action);
    
    $action->lay->set("famid", $famid);
    $action->lay->set("attrid", strtolower($attrid));
    /**
     * @var DocFam $doc
     */
    $doc = new_Doc($action->dbaccess, $famid, true);
    if ($doc->isAlive()) {
        /**
         * @var NormalAttribute $attr
         */
        $attr = $doc->getAttribute($attrid);
        if (!$attr) {
            $action->AddWarningMsg(sprintf(_("Attribute [%s] is not found") , $attrid));
            $action->lay->template = sprintf(_("Attribute [%s] is not found") , $attrid);
            return false;
        }
        $action->lay->set("label", $attr->getLabel());
        
        if ($onChange == "no") {
            $onChange = "";
        } elseif ($onChange == "yes" || (!$onChange && !$localSubmit)) {
            $onChange = "yes";
        }
        $action->lay->set("local_submit", $localSubmit);
        $action->lay->set("submit_label", $submitLabel);
        
        if (!$value) {
            if ($default !== null) {
                $value = $default;
            } else {
                $value = $doc->getParameterRawValue($attrid, $doc->GetValueMethod($attrid));
            }
        }
        $d = createTmpDoc($action->dbaccess, $doc->id);
        $fdoc = $d->getFamilyDocument();
        $d->setDefaultValues($fdoc->getParams() , false);
        useOwnParamters($d);
        $input_field = getHtmlInput($d, $attr, $value, "", "", true);
        $action->lay->set("input_field", $input_field);
        $action->lay->set("change", ($onChange != ""));
    } else {
        $action->AddWarningMsg(sprintf(_("Family [%s] not found") , $famid));
        $action->lay->template = sprintf(_("Family [%s] not found") , $famid);
        return false;
    }
    
    $action->parent->addJsRef("FDL/Layout/editparameter.js");
    return true;
}
