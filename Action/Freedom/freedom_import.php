<?php
/**
 * Import document descriptions
 *
 * @author Anakeen 2000 
 * @version $Id: freedom_import.php,v 1.13 2008/02/27 11:43:08 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FREEDOM
 * @subpackage GED
 */
 /**
 */


include_once("FDL/import_file.php");





// -----------------------------------
function freedom_import(&$action) {
    // -----------------------------------
    global $_FILES;
    if (ini_get("max_execution_time") < 3600) ini_set("max_execution_time",3600); // 60 minutes

    if (isset($_FILES["file"])) {
        $filename=$_FILES["file"]['name'];
        $csvfile=$_FILES["file"]['tmp_name'];
        $ext=substr($filename,strrpos($filename,'.')+1);
        rename($csvfile,$csvfile.".$ext");
        $csvfile.=".$ext";
    } else {
        $filename=GetHttpVars("file");
        $csvfile=$filename;
    }
    $ext=substr($csvfile,strrpos($csvfile,'.')+1);

    if ($ext=="xml") {
        include_once("FREEDOM/freedom_import_xml.php");
        $cr= freedom_import_xml($action,   $csvfile);
    } else if ($ext=="zip") {
        include_once("FREEDOM/freedom_import_xml.php");
        $cr= freedom_import_xmlzip($action,   $csvfile);
    } else {
        $cr=add_import_file($action,$csvfile);
    }
    $action->parent->AddJsRef($action->GetParam("CORE_JSURL")."/subwindow.js");
    foreach ($cr as $k=>$v) {
        $cr[$k]["taction"]=_($v["action"]); // translate action
        $cr[$k]["order"]=$k; // translate action
        $cr[$k]["svalues"]="";
        $cr[$k]["msg"]=nl2br($v["msg"]);
        foreach ($v["values"] as $ka=>$va) {
            $cr[$k]["svalues"].= "<LI>[$ka:$va]</LI>"; //
        }
    }
    $nbdoc=count(array_filter($cr,"isdoc"));
    $action->lay->SetBlockData("ADDEDDOC",$cr);
    $action->lay->Set("nbdoc",$nbdoc);
    $action->lay->Set("nbprof",count(array_filter($cr,"isprof")));
    if (isset($_FILES["file"])) @unlink($csvfile); //tmp file
}

function isdoc($var) {
  return (($var["action"]=="added") ||  ($var["action"]=="updated"));
}
function isprof($var) {
  return (($var["action"]=="modprofil") );
}


?>
