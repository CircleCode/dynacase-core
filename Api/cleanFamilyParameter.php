<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Generate Php Document Classes
 *
 * @author Anakeen
 * @version $Id: fdl_adoc.php,v 1.20 2008/10/30 17:34:31 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage
 */
/**
 */
// refreah for a classname
// use this only if you have changed title attributes
include_once ("FDL/Lib.Attr.php");
include_once ("FDL/Class.DocFam.php");

$appl = new Application();
$appl->Set("FDL", $core);

$dbaccess = $appl->GetParam("FREEDOM_DB");
if ($dbaccess == "") {
    print "Database not found : param FREEDOM_DB";
    exit;
}
$usage = new ApiUsage();
$usage->setText("Delete parameter values which are not real parameters");
$verifyOnly=$usage->addEmpty("verify-only","only verify, do not changes");
$usage->verify();
/**
 * @var Action $action
 */
// First Part: Workflow
print "\t === Deleting parasite parameters ===\n";
if ($verifyOnly) print "\nJust Verify...\n";
$s = new SearchDoc($action->dbaccess, "-1");
$s->setObjectReturn(true);
$s->search();
$deleting = array();
/**
 * @var DocFam $fam
 */
while ($fam = $s->nextDoc()) {
    print ("\n" . $fam->getTitle() . " : #" . $fam->id);
    $pa = $fam->getOwnParams();
    
    $before = $fam->param;
    foreach ($pa as $aid => $val) {
        $oa = $fam->getAttribute($aid);
        if (!$oa) {
            $deleting[] = $aid;
            $fam->setParam($aid, '');
        } else {
            if ($oa->usefor != 'Q') {
                $deleting[] = $aid;
                $fam->setParam($aid, '');
            } else {
                // it's a good param
                
            }
        }
    }
    $after = $fam->param;
    if ($before != $after) {
        printf("Change from \n\t%s to \n\t%s", $before, $after);
        if (! $verifyOnly) {
        $err=$fam->modify();
        $err = '';
        if (!$err) print "changed";
        else print $err;
        }
    } else print ": clean - nothing to do";
}
//print_r2($deleting);
print "\n";