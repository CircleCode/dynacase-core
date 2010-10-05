<?php

/**
 * Incorporate scripts for extjs
 *
 * @author Anakeen 2006
 * @version $Id:  $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FREEDOM
 */
 /**
 */



function addextscript(Action &$action) {
  $debug = $action->getArgument("debug");
  $action->lay->set("debug", $debug);
  $lang=strtolower(strtok($action->getParam("CORE_LANG"),"_"));
    
  if (file_exists(sprintf("%s/lib/ext/src/locale/ext-lang-%s.js",DEFAULT_PUBDIR,$lang))) {
      $action->lay->set("lang",$lang);
  } else {
      $action->lay->set("lang",false);
  }
  
  
}
?>