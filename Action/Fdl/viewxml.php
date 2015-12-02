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
 * @version $Id: viewxml.php,v 1.4 2005/06/28 08:37:46 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage
 */
/**
 */
// ---------------------------------------------------------------
// $Id: viewxml.php,v 1.4 2005/06/28 08:37:46 eric Exp $
// $Source: /home/cvsroot/anakeen/freedom/freedom/Action/Fdl/viewxml.php,v $
// ---------------------------------------------------------------

include_once ("FDL/Class.Doc.php");
// -----------------------------------
function viewxml(Action &$action)
{
    // -----------------------------------
    
    // Get all the params
    $docid = GetHttpVars("id"); // dccument to export
    $dbaccess = $action->dbaccess;
    
    $doc = new_Doc($dbaccess, $docid);
    $xml = $doc->toxml(true, $docid);
    //$doc->fromxml($xml);
    //$xml=$doc->viewdtd();
    
    $export_file = uniqid(getTmpDir() . "/xml");
    $export_file.= ".xml";
    $fp = fopen($export_file, "w");
    
    fwrite($fp, $xml);
    fclose($fp);
    //http_DownloadFile($export_file,chop($doc->title).".xml","text/dtd");
    http_DownloadFile($export_file, str_replace(" ", "_", chop($doc->title)) . ".xml", "text/xml");
    
    unlink($export_file);
    exit;
}
