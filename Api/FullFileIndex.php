<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Detect file which are not indexed and index them
 *
 * @author Anakeen 2004
 * @version $Id: FullFileIndex.php,v 1.2 2007/09/07 09:40:21 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage
 */
/**
 */
ini_set("max_execution_time", "36000");

include_once ('FDL/Class.Doc.php');
include_once ("FDL/Lib.Dir.php");
global $action;

define("REDCOLOR", "\033".'[1;31;40m');
define("UPDTCOLOR", "\033".'[1;32;40m');
define("STOPCOLOR", "\033".'[0m');


$usage = new ApiUsage();

$usage->setText("Detect file which are not indexed and index them");
$force = ($usage->addOption("force", "force (yes or no)") == "yes");

$usage->verify();

$dbaccess = GetParam("FREEDOM_DB");
if ($dbaccess == "") {
    print "Database not found : param FREEDOM_DB";
    exit;
}
$o = new DbObj($dbaccess);
$q = new QueryDb($dbaccess, "DocAttr");
$q->AddQuery("type = 'file'");
$q->AddQuery("usefor != 'Q'");
//$q->AddQuery("frameid not in (select id from docattr where type~'array')");
$la = $q->Query(0, 0, "TABLE");

foreach ($la as $k => $v) {
    $docid = $v["docid"];
    $aid = $v["id"];
    
    $filter = array();
    $filter[] = "$aid is not null";
    if (!$force) $filter[] = "{$aid}_txt is null";
    $ldoc = getChildDoc($dbaccess, 0, 0, "ALL", $filter, $action->user->id, "ITEM", $docid);
    $c = countDocs($ldoc);
    
    print "\n-- Family $docid, Attribute : $aid, count:$c\n";
    while ($doc = getNextDoc($dbaccess, $ldoc)) {
        print "$c)" . $doc->title . "- $aid -" . $doc->id . '- ' . $doc->fromid . "\n";
        $c--;
        $err = $doc->recomputeTextFiles($aid);
        if ($err) print REDCOLOR . $err . STOPCOLOR;
    }
}
//print "$sqlicon\n";

?>
