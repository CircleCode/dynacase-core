<?php
/**
 * Export Document from Folder
 *
 * @author Anakeen 2003
 * @version $Id: exportfld.php,v 1.44 2009/01/12 13:23:11 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */



include_once("FDL/Lib.Dir.php");
include_once("FDL/Lib.Util.php");
include_once("FDL/Class.DocAttr.php");
include_once("VAULT/Class.VaultFile.php");
include_once("FDL/import_file.php");
include_once("FDL/Class.SearchDoc.php");
/**
 * Exportation as xml of documents from folder or searches
 * @param Action &$action current action
 * @global fldid Http var : folder identificator to export
 * @global wfile Http var : (Y|N) if Y export attached file export format will be tgz
 * @global flat Http var : (Y|N) if Y specid column is set with identificator of document
 * @global eformat Http var :  (X|Y) I:  Y: only one xml, X: zip by document with files
 * @global log Http var :  log file output
 * @global selection Http var :  JSON document selection object
 */
function exportxmlfld(Action &$action, $aflid="0", $famid="") {
    if (ini_get("max_execution_time") < 3600) ini_set("max_execution_time",3600); // 60 minutes

    $dbaccess = $action->GetParam("FREEDOM_DB");
    $fldid = $action->getArgument("id",$aflid);
    $wprof =  false; // no profil
    $wfile = (substr(strtolower($action->getArgument("wfile","N")),0,1)=="y"); // with files
    $wident = (substr(strtolower($action->getArgument("wident","Y")),0,1)=="y"); // with numeric identificator

    $flat = (substr(strtolower($action->getArgument("flat")),0,1)=="y"); // flat xml
    $eformat = strtoupper($action->getArgument("eformat","X")); // export format
    $selection = $action->getArgument("selection"); // export selection  object (JSON)
    $log = $action->getArgument("log"); // export selection  object (JSON)
    $configxml = $action->getArgument("config");
    $flog=false;
    if ($log) {
        $flog=fopen($log, "w");
        if (! $flog) {
            exportExit($action,sprintf(_("cannot write log in %s"),$log));
        }
    }

    // constitution options for filter attributes
    $exportAttribute=array();
    if ($configxml) {
        if (! file_exists($configxml)) exportExit($action,sprintf(_("config file %s not found"),$configxml));
         
        $xml = @simplexml_load_file($configxml);

        if( $xml === false ) {
            exportExit($action,sprintf(_("parse error config file %s : %s"),$configxml, print_r(libxml_get_last_error(),true)));
        }
        foreach( $xml->family as $family ) {
            $afamid=@current($family->attributes()->name);
            if (! $afamid) exportExit($action,sprintf(_("Config file %s : family name not set"),$configxml));
            $fam=new_doc($dbaccess,  $afamid);
            if ( (! $fam->isAlive()) || ($fam->doctype!='C')) exportExit($action,sprintf(_("Config file %s : family name [%s] not match a know family"),$configxml,$afamid));
            $exportAttribute[$fam->id] = array();
            foreach( $family->attribute as $attribute ) {
                $aid=@current($attribute->attributes()->name);

                if (! $aid) exportExit($action,sprintf(_("Config file %s : attribute name not set"),$configxml));
                $oa=$fam->getAttribute($aid);
                if (! $oa) exportExit($action,sprintf(_("Config file %s : unknow attribute name %s"),$configxml,$aid));
                $exportAttribute[$fam->id][$oa->id]=$oa->id;
                $exportAttribute[$fam->id][$oa->fieldSet->id]=$oa->fieldSet->id;
            }
        }
    }
    // set the export's search
    if ((! $fldid) && $selection) {
        $selection=json_decode($selection);
        include_once("DATA/Class.DocumentSelection.php");
        $os=new Fdl_DocumentSelection($selection);
        $ids=$os->getIdentificators();
        $s=new SearchDoc($dbaccess);

        $s->addFilter(getSqlCond($ids,"id",true));
        $s->setObjectReturn();
        $exportname="selection";
    } else {
        if (! $fldid) exportExit($action,_("no export folder specified"));

        $fld = new_Doc($dbaccess, $fldid);
        if ($fldid && (! $fld->isAlive()))  exportExit($action,sprintf(_("folder/search %s not found"),$fldid));
        if ($famid=="") $famid=$action->getArgument("famid");
        $exportname=str_replace(array(" ","'",'/'),array("_","","-"),$fld->title);
        //$tdoc = getChildDoc($dbaccess, $fldid,"0","ALL",array(),$action->user->id,"TABLE",$famid);

        $s=new SearchDoc($dbaccess,$famid);
        $s->setObjectReturn();

        $s->dirid=$fld->id;


    }
    $s->search();
    $err=$s->searchError();
    if ($err) exportExit($action,$err);



    $foutdir = uniqid("/var/tmp/exportxml");
    if (! mkdir($foutdir)) exportExit($action,sprintf("cannot create directory %s",$foutdir));
    //$fname=sprintf("%s/FDL/Layout/fdl.xsd",DEFAULT_PUBDIR);
    //copy($fname,"$foutdir/fdl.xsd");
    $xsd=array();
    $count=0;
     if ($flog) {
        fputs($flog,"==========\n");
        fputs($flog,sprintf("BEGIN DATE : %s\n",Doc::getTimeDate(0,true)));
        fputs($flog,"==========\n");
     }
    
    while ($doc=$s->nextDoc()) {
        //print $doc->exportXml();
        if ($doc->doctype != 'C') {
            $ftitle= str_replace(array('/','\\','?','*',':'),'-',$doc->getTitle());
            $fname=sprintf("%s/%s{%d}.xml",$foutdir,$ftitle,$doc->id);
            $err=$doc->exportXml($xml,$wfile,$fname,$wident,$flat,$exportAttribute);
            // file_put_contents($fname,$doc->exportXml($wfile));
             
            if ($err) exportExit($action,$err);
            $count++;
            if ($flog) fputs($flog,sprintf(_("%4d) Document <%s> [%d] exported")."\n", $count, $doc->getTitle(), $doc->id));
            if (! isset($xsd[$doc->fromid])) {
                $fam=new_doc($dbaccess,$doc->fromid);
                $fname=sprintf("%s/%s.xsd",$foutdir,strtolower($fam->name));
                file_put_contents($fname,$fam->getXmlSchema());
                $xsd[$doc->fromid]=true;
            }
        }
    }

    if ($flog) {
        fputs($flog,"==========\n");
        fputs($flog,sprintf(_("%d documents exported")."\n",$count));
        fputs($flog,"==========\n");
        fputs($flog,sprintf("END DATE : %s\n",Doc::getTimeDate(0,true)));
        fputs($flog,"==========\n");
        fclose($flog);
    }
     

    if ($eformat=="X") {
        $zipfile = uniqid("/var/tmp/xml").".zip";
        system("cd $foutdir && zip -r $zipfile * > /dev/null",$ret);
        if (is_file($zipfile)) {
            system("rm -fr $foutdir");
            Http_DownloadFile($zipfile, "$exportname.zip", "application/x-zip",false,false,true);
        } else {
            exportExit($action,_("Zip Archive cannot be created"));
        }
    } elseif ($eformat=="Y") {
        $xmlfile = uniqid("/var/tmp/xml").".xml";
        $cmde=array();
        $cmde[]="cd $foutdir";
        $cmde[]=sprintf("echo '<?xml version=\"1.0\" encoding=\"UTF-8\"?>' > %s",$xmlfile);
        $cmde[]=sprintf("echo '<documents date=\"%s\" author=\"%s\" name=\"%s\">' >> %s",
        strftime("%FT%T"),User::getDisplayName($action->user->id),$exportname,$xmlfile);
        $cmde[]="cat *xml | grep -v '<?xml version=\"1.0\" encoding=\"UTF-8\"?>' >> $xmlfile";
        $cmde[]="echo '</documents>' >> $xmlfile";
        system(implode(" && ",$cmde),$ret);
        if (is_file($xmlfile)) {
            system("rm -fr $foutdir");
            Http_DownloadFile($xmlfile, "$exportname.xml", "text/xml",false,false,true);
        } else {
            exportExit($action,_("Xml file cannot be created"));
        }
    }
}
function exportExit(Action &$action,$err) {
    $log=$action->getArgument("log");
    if ($log) {
        if (file_put_contents($log, _("ERROR :").$err) === false) {
            $err=sprintf(_("Cannot write to log %s"),$log)."\n".$err;
        }
    }
    $action->exitError($err);
}

?>
