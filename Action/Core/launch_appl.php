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
 * @version $Id: launch_appl.php,v 1.3 2004/03/22 15:21:40 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage CORE
 */
/**
 */
// $Id: launch_appl.php,v 1.3 2004/03/22 15:21:40 eric Exp $
// $Log: launch_appl.php,v $
// Revision 1.3  2004/03/22 15:21:40  eric
// change HTTP variable name to put register_globals = Off
//
// Revision 1.2  2003/08/18 15:46:41  eric
// phpdoc
//
// Revision 1.1  2002/01/08 12:41:33  eric
// first
//
// Revision 1.4  2000/10/18 08:48:24  yannick
// Traitement du JS et passage par référence
//
// Revision 1.3  2000/10/11 12:27:38  yannick
// Gestion de l'authentification
//
// Revision 1.2  2000/10/06 13:59:16  yannick
// Ajout des règles de codage
//
// Revision 1.1.1.1  2000/10/05 17:29:10  yannick
// Importation
//
include_once ('Class.Application.php');

function launch_appl(&$action)
{
    // This function is used to launch a function in an application
    // get the app and function name
    global $_GET;
    $appl = new Application();
    $appl->Set(GetHttpVars("app") , $action->parent);
    
    $called = new Action();
    $called->Set(GetHttpVars("action") , $appl, $action->session);
    
    $action->lay->set("OUT", $called->execute());
}

function app_title(&$action)
{
    
    global $_GET;
    $appl = new Application();
    $appl->Set(GetHttpVars("app") , $action->parent);
    
    $action->lay->set("OUT", $appl->description);
}
?>
