<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Generated Header (not documented yet)
 *
 * @subpackage
 */
/**
 */
// remove all tempory doc and orphelines values
include_once ("FDL/Class.Doc.php");
include_once ("WHAT/Class.SessionUtils.php");
global $action;

$usage = new ApiUsage();

$usage->setDefinitionText("Clean base");
$real = ($usage->addHiddenParameter("real", "real (yes or no)") == "yes");
$full = ($usage->addEmptyParameter("full", "clean also obsolete permission, log, folder contains"));

if ($full !== true) {
    $full = ($full == "yes");
}

$usage->verify();

$dbaccess = $action->GetParam("FREEDOM_DB");
if ($dbaccess == "") {
    print "Database not found : param FREEDOM_DB";
    exit;
}

$duration = intval($action->GetParam("CORE_LOGDURATION", 60)); // default 60 days
$logdelete = sprintf("DELETE FROM doclog where date < '%s'", Doc::getDate(-($duration)));

simpleQuery($dbaccess, $logdelete);

global $_SERVER;

if ($real || $full) {
    print "Full clean.\n";
    fullDbClean($action, $dbaccess);
} else {
    print "Basic clean.\n";
    basicDbClean($action, $dbaccess);
}
// Cleanup session files
$core_db = $action->GetParam('CORE_DB');
$sessionUtils = new SessionUtils($core_db);
$sessionUtils->deleteExpiredSessionFiles();

cleanTmpFiles();
\Dcp\VaultManager::destroyTmpFiles($action->GetParam('CORE_TMPDIR_MAXAGE', '2'));

function mkTmpScript($script, $prefix)
{
    $tmpDir = getTmpDir();
    $tmpScript = tempnam($tmpDir, $prefix);
    if ($tmpScript === false) {
        throw new Exception(sprintf("Error creating temporary file in '%s'.", $tmpDir));
    }
    if (file_put_contents($tmpScript, $script) === false) {
        throw new Exception(sprintf("Error writing to temporary file '%s'.", $tmpScript));
    }
    return $tmpScript;
}

function fullDbClean(Action & $action, $dbaccess)
{
    $dbfreedom = getServiceName($dbaccess);
    $script = <<<'EOF'
#!/bin/bash
PGSERVICE=%s psql -a -f %s/API/cleanFullContext.sql | logger -t %s
exit ${PIPESTATUS[0]}
EOF;
    $script = sprintf($script, escapeshellarg($dbfreedom) , escapeshellarg(DEFAULT_PUBDIR) , escapeshellarg("cleanContext(" . $action->GetParam("CORE_CLIENT") . ")"));
    $tmpScript = mkTmpScript($script, 'fullDbClean');
    $out = array();
    $ret = 0;
    exec(sprintf("bash %s 2>&1", $tmpScript) , $out, $ret);
    if ($ret !== 0) {
        throw new Exception(sprintf("Error executing '%s': %s", $tmpScript, join("\n", $out)));
    }
    unlink($tmpScript);
}

function basicDbClean(Action & $action, $dbaccess)
{
    $dbfreedom = getServiceName($dbaccess);
    $script = <<<'EOF'
#!/bin/bash
PGSERVICE=%s psql -a -f %s/API/cleanContext.sql | logger -t %s
exit ${PIPESTATUS[0]}
EOF;
    $script = sprintf($script, escapeshellarg($dbfreedom) , escapeshellarg(DEFAULT_PUBDIR) , escapeshellarg("cleanContext(" . $action->GetParam("CORE_CLIENT") . ")"));
    $tmpScript = mkTmpScript($script, 'basicDbClean');
    $out = array();
    $ret = 0;
    exec(sprintf("bash %s 2>&1", $tmpScript) , $out, $ret);
    if ($ret !== 0) {
        throw new Exception(sprintf("Error executing '%s': %s", $tmpScript, join("\n", $out)));
    }
    unlink($tmpScript);
}

function cleanTmpFiles()
{
    global $action;
    global $pubdir;
    
    if ($pubdir == '') {
        echo sprintf("Error: Yikes! we got an empty pubdir?");
        return;
    }
    
    $tmpDir = getTmpDir('');
    if ($tmpDir == '') {
        echo sprintf("Error: empty directory returned by getTmpDir().");
        return;
    }
    if (!is_dir($tmpDir)) {
        echo sprintf("Error: temporary directory '%s' does not exists.", $tmpDir);
        return;
    }
    
    $maxAge = $action->GetParam('CORE_TMPDIR_MAXAGE', '');
    if ($maxAge == '') {
        echo sprintf("Error: empty CORE_TMPDIR_MAXAGE parameter.");
        return;
    }
    if (!is_numeric($maxAge)) {
        echo sprintf("Error: found non-numeric value '%s' for CORE_TMPDIR_MAXAGE.", $maxAge);
        return;
    }
    /* Values < 0 disable tmp file cleaning */
    if ($maxAge < 0) {
        return;
    }
    /* We use find & xargs shell commands to do the cleaning. */
    /* First pass: remove expired files */
    $cmd = sprintf('find %s -type f -mtime +%s -print0 | xargs -0 --no-run-if-empty rm', escapeshellarg($tmpDir) , $maxAge);
    exec($cmd, $output, $ret);
    if ($ret != 0) {
        echo sprintf("Error: removal of temporary files from '%s' returned with error: %s", $tmpDir, join("\n", $output));
        return;
    }
    /* Second pass: remove expired empty directories */
    $cmd = sprintf('find %s -type d -empty -mtime +%s -print0 | xargs -0 --no-run-if-empty rmdir', escapeshellarg($tmpDir) , $maxAge);
    exec($cmd, $output, $ret);
    if ($ret != 0) {
        echo sprintf("Error: removal of empty temporary directories from '%s' returned with error: %s", $tmpDir, join("\n", $output));
        return;
    }
    
    return;
}
?>
