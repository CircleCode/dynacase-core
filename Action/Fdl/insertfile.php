<?php
/**
 * Insert rendering file which comes from transformation engine
 *
 * @author Anakeen 2007
 * @version $Id: insertfile.php,v 1.8 2007/12/10 09:15:03 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */


include_once("FDL/Class.Doc.php");
include_once("FDL/Class.TaskRequest.php");
include_once("WHAT/Class.TEClient.php");
include_once("Lib.FileMime.php");
/**
 * Modify the attrid_txt attribute
 * @param Action &$action current action
 * @global docid Http var : document identificator to modify
 * @global attrid Http var : the id of attribute to modify
 * @global index Http var : the range in case of array
 * @global tid Http var : task identificator
 * 
 */
function insertfile(&$action) {
  $vidin=GetHttpVars("vidin");
  $vidout=GetHttpVars("vidout");
  $tid = GetHttpVars("tid");
  $name = GetHttpVars("name");
  $engine = GetHttpVars("engine");
  $isimage = (GetHttpVars("isimage")!="");
  $dbaccess = $action->GetParam("FREEDOM_DB");

  if (! $tid) $err=_("no task identificator found");
  else {
    $filename= uniqid("/var/tmp/txt-".$vidout.'-');
    $err=getTEFile($tid,$filename,$info);
    if ($err=="") {
     	
	$outfile=$info["outfile"];
	$status=$info["status"];
	
	if (($status=='D') && ($outfile != '')) {
	 
	  $vf = newFreeVaultFile($dbaccess);
	  $err=$vf->Retrieve($vidin, $infoin);
	  $err=$vf->Retrieve($vidout, $infoout);
	  $err=$vf->Save($filename, false , $vidout);
	  $err=$vf->Retrieve($vidout, $infoout); // relaod for mime
	
	  $ext=getExtension($infoout->mime_s);
	  if ($ext=="") $ext=$infoout->teng_lname;
	  //	  print_r($infoout);
		  // print_r($ext);	  
	  if ($name!="") {
	    $newname=$name;
	  } else {
	    $pp=strrpos($infoin->name,'.');
	    $newname=substr($infoin->name,0,$pp).'.'.$ext;
	  }


	  $vf->Rename($vidout,$newname);
	  $vf->storage->teng_state=1;
	  $vf->storage->modify();
	  
	  
	  if ($engine=="pdf") {
	    createPdf2Png($infoout->path,$vidout );
	  }

	  @unlink($filename);

	} else {
	  $vf = newFreeVaultFile($dbaccess);
	  $err=$vf->Retrieve($vidout, $vinfo);

	  
	  $filename= uniqid("/var/tmp/txt-".$vidout.'-');
	  file_put_contents($filename,print_r($info,true));
	  //$vf->rename($vidout,"toto.txt");
	  $vf->Retrieve($vidout, $vinfo);
	  $err=$vf->Save($filename, false , $vidout);
	  $basename=_("conversion error").".txt";
	  $vf->Rename($vidout,$basename);
	  $vf->storage->teng_state=-1;
	  $vf->storage->modify();;
	  
	}
	
      

    }
  }

  if ($err != '')     $action->lay->template=$err;
  else $action->lay->template="OK : ".sprintf(_("vid %d stored"),$vidout);

}


/**
 * return filename where is stored produced file
 * need to delete after use it
 */
function getTEFile($tid,$filename,&$info) {  
  global $action;
  $dbaccess = $action->GetParam("FREEDOM_DB");
  $ot=new TransformationEngine($action->getParam("TE_HOST"),$action->getParam("TE_PORT"));

  $err=$ot->getInfo($tid,$info);
  if ($err=="") {
    $tr=new TaskRequest($dbaccess,$tid);
    if ($tr->isAffected()) {	
      $outfile=$info["outfile"];
      $status=$info["status"];
	
      if (($status=='D') && ($outfile != '')) {
	$err=$ot->getTransformation($tid,$filename);
	//$err=$ot->getAndLeaveTransformation($tid,$filename); // to debug	
      } 		
    } else {
      $err=sprintf(_("task %s is not recorded"),$tid);
    }
  }
  return $err;
}
function createPdf2Png($file,$vid) {
  if (file_exists($file) && ($vid>0)) {
    $density=200;
    $width=1200;
    $nbpages=trim(`grep -c "/Type[[:space:]]*/Page\>" $file`);
    $cmd[]=sprintf("/bin/rm -f %s/vid-%d*.png;",DEFAULT_PUBDIR."/img-cache",$vid);
    
    for ($i=0;$i<$nbpages;$i++) {
      $cible=DEFAULT_PUBDIR."/img-cache/vid-${vid}-${i}.png";
            print $cible;
      $cmd[]=sprintf("nice convert -interlace plane -thumbnail %d  -density %d %s[%d] %s",
		   $width,$density,$file,$i,$cible);
    }

    bgexec($cmd,$result,$err);

  }
}

?>