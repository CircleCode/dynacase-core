<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Get database coordonate for freedom access by psql
 *
 * @author Anakeen 2000
 * @version $Id: fdl_dbaccess.php,v 1.2 2006/02/03 16:03:13 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage
 */
/**
 */

$usage = new ApiUsage();
$usage->setText("Get database coordonate for freedom access by psql");
$usage->verify();

$dbaccess = getParam("FREEDOM_DB");
$dbpsql = "";
if ($dbaccess != "") {
    $dbhost = "";
    $dbport = "";
    $dbname = "";
    if (preg_match('/dbname=[ ]*([a-z_0-9]*)/', $dbaccess, $reg)) {
        $dbname = $reg[1];
    }
    if (preg_match('/host=[ ]*([a-z_0-9\.]*)/', $dbaccess, $reg)) {
        $dbhost = $reg[1];
    }
    if (preg_match('/port=[ ]*([a-z_0-9]*)/', $dbaccess, $reg)) {
        $dbport = $reg[1];
    }
    if ($dbhost != "") $dbpsql.= "--host $dbhost ";
    if ($dbport != "") $dbpsql.= "--port $dbport ";
    $dbpsql.= "--username anakeen --dbname $dbname ";
}

print $dbpsql;
?>