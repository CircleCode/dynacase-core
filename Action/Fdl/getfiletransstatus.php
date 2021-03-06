<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * retrieve task status
 *
 * @author Anakeen
 * @version $Id: getfiletransstatus.php,v 1.1 2008/01/03 09:05:13 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage
 */
/**
 */

include_once ("FDL/Class.Doc.php");
/**
 * retrieve task status
 * @param Action &$action current action
 * @global string $tid Http var : task identifier
 */
function getfiletransstatus(Action & $action)
{
    $tid = GetHttpVars("tid");
    
    header('Content-type: text/xml; charset=utf-8');
    
    $action->lay->set("CODE", "KO");
    $tea = getParam("TE_ACTIVATE");
    if ($tea != "yes") return;
    
    global $action;
    include_once ("FDL/Class.TaskRequest.php");
    
    $ot = new TransformationEngine(getParam("TE_HOST") , getParam("TE_PORT"));
    $err = $ot->getInfo($tid, $info);
    if ($err == "") {
        $action->lay->set("tid", $info["tid"]);
        $action->lay->set("status", $info["status"]);
        $action->lay->set("engine", $info["engine"]);
        switch ($info["status"]) {
            case 'P':
                $statusmsg = _("File:: Processing");
                break;

            case 'W':
                $statusmsg = _("File:: Waiting");
                break;

            case 'D':
                $statusmsg = _("File:: converted");
                break;

            case 'K':
                $statusmsg = _("File:: failed");
                break;

            default:
                $statusmsg = $info["status"];
        }
        
        $action->lay->set("statusmsg", $statusmsg);
        $action->lay->set("message", $info["comment"]);
        $action->lay->set("CODE", "OK");
    }
    
    $action->lay->set("warning", $err);
}
?>