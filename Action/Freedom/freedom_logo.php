<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * View logo
 *
 * @author Anakeen
 * @version $Id: freedom_logo.php,v 1.6 2007/03/12 17:35:46 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage GED
 */
/**
 */

function freedom_logo(Action &$action)
{
    $action->parent->AddJsRef($action->GetParam("CORE_JSURL") . "/geometry.js");
    $action->parent->AddJsRef($action->GetParam("CORE_JSURL") . "/resizeimg.js");
    $action->lay->Set("appicon", $action->parent->getImageLink($action->parent->icon));
    $action->lay->Set("apptitle", $action->parent->description);
}
?>
