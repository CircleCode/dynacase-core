<?php
/**
 * Generated Header (not documented yet)
 *
 * @author Anakeen 2000 
 * @version $Id: Method.Mask.php,v 1.23 2008/09/12 10:14:48 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FREEDOM
 * @subpackage GED
 */
 /**
 */

// ---------------------------------------------------------------
// $Id: Method.Mask.php,v 1.23 2008/09/12 10:14:48 eric Exp $
// $Source: /home/cvsroot/anakeen/freedom/freedom/Class/Freedom/Method.Mask.php,v $
// ---------------------------------------------------------------


var $defaultedit= "FREEDOM:EDITMASK";
var $defaultview= "FREEDOM:VIEWMASK";

function SpecRefresh() {
 
  //  gettitle(D,AR_IDCONST):AR_CONST,AR_IDCONST
  $this->refreshDocTitle("MSK_FAMID","MSK_FAM");

  
  return $err;
}

function getLabelVis() {
  return  array("-" => " ",
		    "R" => _("read only"),
		    "W" => _("read write"),
		    "O" => _("write only"),
		    "H" => _("hidden"),
		    "S" => _("read disabled"),
		    "U" => _("static array"),
		    "I" => _("invisible"));
}
function getLabelNeed() {
  return  array("-" => " ",
		"Y" => _("Y"),
		"N" => _("N"));
}

/**
 * suppress unmodified attributes visibilities
 * to simplify the mask structure 
 */
function postModify() {
  $tneed = $this->getTValue("MSK_NEEDEEDS");
  $tattrid = $this->getTValue("MSK_ATTRIDS");
  $tvis = $this->getTValue("MSK_VISIBILITIES");

  $tvisibilities=array();
  foreach ($tattrid as $k=>$v) {
    if (($tneed[$k]=='-') && ($tvis[$k]=='-')) {
      unset($tneed[$k]);
      unset($tvis[$k]);
      unset($tattrid[$k]);
    }
  }

  
  $this->setValue("MSK_NEEDEEDS",$tneed);
  $this->setValue("MSK_ATTRIDS",$tattrid);
  $this->setValue("MSK_VISIBILITIES",$tvis);
  $err=$this->modify();
  return $err;
}

function getVisibilities() {
  $tvisid = $this->getTValue("MSK_VISIBILITIES");
  $tattrid = $this->getTValue("MSK_ATTRIDS");

  $tvisibilities=array();
  while (list($k,$v)= each ($tattrid)) {
    $tvisibilities[$v]=$tvisid[$k];    
  }
  return $tvisibilities;
}

function getCVisibilities() {
  $tvisid = $this->getTValue("MSK_VISIBILITIES");
  $tattrid = $this->getTValue("MSK_ATTRIDS");
  $docid = $this->getValue("MSK_FAMID",1);
  $doc= new_Doc($this->dbaccess,$docid);

  $tsvis = $this->getVisibilities();
  $tvisibilities=array();

  foreach($tattrid as $k=>$v) {
    $attr = $doc->getAttribute($v);
    $fvisid=$attr->fieldSet->id;
    if ($tvisid[$k]=="-") $vis=$attr->visibility;
    else $vis=$tvisid[$k];

    $tvisibilities[$v]=ComputeVisibility($vis,$tvisibilities[$fvisid]);    
  }
  return $tvisibilities;
}
function getNeedeeds() {
  $tvisid = $this->getTValue("MSK_NEEDEEDS");
  $tattrid = $this->getTValue("MSK_ATTRIDS");

  $tvisibilities=array();
  while (list($k,$v)= each ($tattrid)) {
    $tvisibilities[$v]=$tvisid[$k];    
  }
  return $tvisibilities;
}

function viewmask($target="_self",$ulink=true,$abstract=false) {
 
  $docid = $this->getValue("MSK_FAMID",1);

  $tvisibilities=$this->getCVisibilities();
  $tkey_visibilities=array_keys($tvisibilities);
  $tinitvisibilities=$tvisibilities;

  
  $tneedeeds=$this->getNeedeeds();

  $this->lay->Set("docid",$docid);

  $doc= new_Doc($this->dbaccess,$docid);
  $doc->applyMask();
  $origattr=$doc->attributes->attr;

  $tmpdoc=createTmpDoc($this->dbaccess,$docid);
  $tmpdoc->applyMask($this->id);
  
  // display current values
  $tmask=array();
  
  $labelvis = $this->getLabelVis();
  

  uasort($tmpdoc->attributes->attr,"tordered"); 

  foreach($tmpdoc->attributes->attr as $k=>$attr) {
    if (!$attr->visibility) continue;
    $tmask[$k]["attrname"]=$attr->getLabel();
    $tmask[$k]["type"]=$attr->type;
    $tmask[$k]["visibility"]=$labelvis[$attr->visibility];
    $tmask[$k]["wneed"]=($origattr[$k]->needed)?"bold":"normal";
    $tmask[$k]["bgcolor"]=getParam("COLOR_A9");
    $tmask[$k]["mvisibility"] = $labelvis[$attr->mvisibility];
    
    
    if ($tmask[$k]["visibility"] != $tmask[$k]["mvisibility"]) {
      if ($tmask[$k]["mvisibility"] == $labelvis[$origattr[$k]->mvisibility] ) $tmask[$k]["bgcolor"]=getParam("COLOR_A7");      
      else $tmask[$k]["bgcolor"]=getParam("COLOR_B7"); 
    }
    if (in_array($k, $tkey_visibilities)) {      
      $tmask[$k]["bgcolor"]=getParam("COLOR_B5");      
    } 
    
    if (isset($tneedeeds[$attr->id])) {
      if (($tneedeeds[$attr->id]=="Y") || (($tneedeeds[$attr->id]=="-") && ($attr->needed)))  $tmask[$k]["waneed"] = "bold";
      else $tmask[$k]["waneed"] = "normal";
      if ($tneedeeds[$attr->id] != "-") $tmask[$k]["bgcolor"]=getParam("CORE_BGCOLORALTERN");
    } else $tmask[$k]["waneed"] = "normal";

 
 
    if ($tmask[$k]["wneed"] != $tmask[$k]["waneed"]) {
      $tmask[$k]["bgcolor"]=getParam("COLOR_B5");      
    }

    if ($attr->fieldSet && $attr->fieldSet->id ) $tmask[$k]["framelabel"]=$attr->fieldSet->getLabel();
    else $tmask[$k]["framelabel"]="";
    if ($attr->waction!="") $tmask[$k]["framelabel"]=_("Action");

  }

  $this->lay->SetBlockData("MASK",$tmask);  
}



function editmask() {
 
  $docid = $this->getValue("MSK_FAMID",1);


  $this->lay->Set("docid",$docid);

  $doc= new_Doc($this->dbaccess,$docid);


  $tvisibilities=$this->getVisibilities();
  $tneedeeds=$this->getNeedeeds();
  
  $selectclass=array();
  $tclassdoc = GetClassesDoc($this->dbaccess, $this->userid,0,"TABLE");
  while (list($k,$cdoc)= each ($tclassdoc)) {
    $selectclass[$k]["idcdoc"]=$cdoc["id"];
    $selectclass[$k]["classname"]=$cdoc["title"];
    $selectclass[$k]["selected"]="";
  }


  $selectframe= array();

  $nbattr=0; // if new document 

  // display current values
  $newelem=array();

   

  // selected the current class document
  while (list($k,$cdoc)= each ($selectclass)) {

    if ($docid == $selectclass[$k]["idcdoc"]) {

      $selectclass[$k]["selected"]="selected";
    }
    
  }

  $this->lay->SetBlockData("SELECTCLASS", $selectclass);


  $ka = 0; // index attribute

  
  $labelvis=$this->getLabelVis();      
  while(list($k,$v) = each($labelvis))  {
    $selectvis[] = array("visid" =>$k ,
			 "vislabel" => $v);
  }
  $labelneed=$this->getLabelNeed();      
  while(list($k,$v) = each($labelneed))  {
    $selectneed[] = array("needid" =>$k ,
			  "needlabel" => $v);
  }
		     

  //    ------------------------------------------
  //  -------------------- NORMAL ----------------------
  $tattr = $doc->GetNormalAttributes();
  $tattr += $doc->GetFieldAttributes();
  $tattr += $doc->GetActionAttributes();
  uasort($tattr,"tordered"); 
  foreach($tattr as $k=>$attr) {
    if ($attr->usefor=="Q") continue; // not parameters
    if ($attr->docid ==0) continue; // not parameters
    $newelem[$k]["attrid"]=$attr->id;
    $newelem[$k]["attrname"]=$attr->getLabel();
    $newelem[$k]["visibility"]=$labelvis[$attr->visibility];

    $newelem[$k]["wneed"]=($attr->needed)?"bold":"normal";
    $newelem[$k]["neweltid"]=$k;
    
    if (($attr->type=="array") || (strtolower(get_class($attr)) == "fieldsetattribute"))$newelem[$k]["fieldweight"]="bold";
    else $newelem[$k]["fieldweight"]="";

    if ($attr->docid == $docid) {
      $newelem[$k]["disabled"]="";
    } else {
      $newelem[$k]["disabled"]="disabled";
    }

    if ($attr->fieldSet->docid >0)   $newelem[$k]["framelabel"]=$attr->fieldSet->getLabel();
    else  $newelem[$k]["framelabel"]="";
    if ($attr->waction!="") $newelem[$k]["framelabel"]=_("Action");

    reset($selectvis);
    while(list($kopt,$opt) = each($selectvis))  {
      if ($opt["visid"] == $tvisibilities[$attr->id]) {
	$selectvis[$kopt]["selected"]="selected"; 
      } else{
	$selectvis[$kopt]["selected"]=""; 
      }		  
    }
    // idem for needed
    reset($selectneed);
    while(list($kopt,$opt) = each($selectneed))  {
      if ($opt["needid"] == $tneedeeds[$attr->id]) {
	$selectneed[$kopt]["selectedneed"]="selected"; 
      } else{
	$selectneed[$kopt]["selectedneed"]=""; 
      }		  

    }


    $newelem[$k]["SELECTVIS"]="SELECTVIS_$k";
    $this->lay->SetBlockData($newelem[$k]["SELECTVIS"],
			     $selectvis);
    $newelem[$k]["SELECTNEED"]="SELECTNEED_$k";
    $this->lay->SetBlockData($newelem[$k]["SELECTNEED"],
			     $selectneed);
	      
    $ka++;
  }
          

  $this->lay->SetBlockData("NEWELEM",$newelem);

  $this->editattr();
}
?>