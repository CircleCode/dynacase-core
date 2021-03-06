<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Refresh groups to recompute members and mail attributes
 * @subpackage
 */
/**
 */
// refreah for a classname
// use this only if you have changed title attributes
include_once ("FDL/Class.Doc.php");
include_once ("FDL/Lib.Dir.php");
include_once ("FDL/Lib.Usercard.php");

$usage = new ApiUsage();

$usage->setDefinitionText("Refresh groups to recompute members and mail attributes");
$force = $usage->addOptionalParameter("force", "force a refresh", array(
    "yes",
    "no"
)); // force a refresh if set to 'yes'
$fbar = $usage->addOptionalParameter("bar", "for progress bar"); // for progress bar
$usage->verify();

$appl = new Application();
$appl->Set("FDL", $core);

$dbaccess = $appl->GetParam("FREEDOM_DB");
if ($dbaccess == "") {
    print "Database not found : param FREEDOM_DB";
    return;
}
$filter = array();
if ($force != 'yes') $filter[] = "grp_isrefreshed = '0'";
$tdoc = internalGetDocCollection($dbaccess, 0, 0, "ALL", $filter, 1, "TABLE", "IGROUP");

$tgid = array();
$nd = count($tdoc);
print sprintf(_("%d group(s) to update\n") , $nd);

foreach ($tdoc as $k => $v) {
    $tgid[] = getv($v, "us_whatid");
    print "\t" . $v["title"] . "\n";
}

wbar($nd, $nd, "processing");
if ($nd > 0) {
    print _("processing...\n");
    refreshGroups($tgid, true);
    print _("done\n");
}
wbar(0, $nd, "done");
?>
