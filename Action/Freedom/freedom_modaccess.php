<?php
/**
 * Generated Header (not documented yet)
 *
 * @author Anakeen 2000 
 * @version $Id: freedom_modaccess.php,v 1.15 2008/10/22 16:14:42 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FREEDOM
 * @subpackage GED
 */
 /**
 */

// ---------------------------------------------------------------
// $Id: freedom_modaccess.php,v 1.15 2008/10/22 16:14:42 eric Exp $
// $Source: /home/cvsroot/anakeen/freedom/freedom/Action/Freedom/freedom_modaccess.php,v $
// ---------------------------------------------------------------

include_once("FDL/Class.Doc.php");
include_once("FDL/Lib.Dir.php");

// -----------------------------------
function freedom_modaccess(&$action) {
  // -----------------------------------

  global $_SERVER; 
  // get all parameters

  $acls=GetHttpVars("acls", array()); 
  $docid=GetHttpVars("docid"); // id for controlled object

  $dbaccess = $action->GetParam("FREEDOM_DB"); 

  $doc = new_Doc($dbaccess, $docid);

  // test if current user can modify ACL 
  $err = $doc->Control("modifyacl");
  if ($err != "") $action->exitError($err);

  $before=array();
  $after=array();

  if (count($acls) > 0) {

    foreach ($acls as $userid => $aclon) {     
  
      // modif permission for a particular user
      $perm = new DocPerm($dbaccess, array($docid,$userid));

      $before[$userid]=array($perm->upacl,$perm->unacl);
      $perm->UnsetControl();


      foreach ($aclon as $k=>$pos) { 
	if (intval($pos) > 0)  $perm->SetControlP($pos);
      }
      if ($perm->isAffected()) $err=$perm ->modify();
      else $err=$perm->Add();
      if ($err!="") {
	if ($perm->isAffected()) $err=$perm->delete();
      }   
      $after[$userid]=array($perm->upacl,$perm->unacl);
    }
    if ($err!="") $action->exitError($err);
    // recompute all related profile
    $pfamid=$doc->getValue("DPDOC_FAMID");
    if ($pfamid > 0) {
    
      $filter = array("dprofid = ".$doc->id);
      $tdoc = getChildDoc($dbaccess, 0,0,"ALL", $filter,1,"TABLE",
			  $pfamid	);
      if (count($tdoc)>0) {
	if (intval(ini_get("max_execution_time")) < 300) ini_set("max_execution_time", 3600);
	$kdoc = createDoc($dbaccess,$pfamid,true);
	$kc=count($kdoc);
	foreach( $tdoc as $k=>$v) {
	  $kdoc->Affect($v);
	  $kdoc->computeDProfil(); 
	  if ($_SERVER['HTTP_HOST'] == "")  print ($kc-$k).")".$kdoc->title."\n";
	}
      }
    }
  

    // find username
    $tuid=array();
    foreach ($acls as $userid => $aclon) { 
      $tuid[]=$userid;
    }
    $q=new QueryDb("","User");
    $q->AddQuery(getsqlcond($tuid,"id"));
    $l=$q->Query(0,0,"TABLE");

    $tuname=array();
    if ($q->nb > 0) {
      foreach ($l as $k=>$v) {
	$tuname[$v["id"]]=$v["firstname"].' '.$v["lastname"];
      }
    }


    $tc=array();
    $posacls=array();
    foreach ($doc->dacls as $k=>$v) {
      $posacls[$k]=$v["pos"];
    }

    foreach ($before as $k=>$v) {
      $a=$after[$k][0];
      $b=$before[$k][0];
      if ($b != $a) {
	$tadd=array();
	$tdel=array();
	foreach ($doc->acls as $acl) {
	  $pos=$posacls[$acl];

	  $a0=($a & (1 << $pos ));
	  $b0=($b & (1 << $pos ));
	  if ($a0 != $b0) {
	    if ($a0) $tadd[]=$acl;
	    else $tdel[]=$acl;	
	  }
	
	}
      


	if (count($tadd)>0) $tc[]=sprintf(_("Add acl %s for %s"),implode(", ",$tadd),$tuname[$k]);
	if (count($tdel)>0) $tc[]=sprintf(_("Delete acl %s for %s"),implode(", ",$tdel),$tuname[$k]);
      
      }
    }
    if (count($tc) > 0) $doc->addComment(sprintf(_("Change control :\n %s"), implode("\n",$tc)));
  }
  RedirectSender($action); // return to sender
}
?>