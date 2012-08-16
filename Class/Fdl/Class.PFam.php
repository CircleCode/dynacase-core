<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Profil for family document
 *
 * @author Anakeen
 * @version $Id: Class.PFam.php,v 1.6 2008/06/03 12:57:28 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 */
/**
 */

include_once ("FDL/Class.Doc.php");

class PFam extends Doc
{
    // --------------------------------------------------------------------
    //---------------------- OBJECT CONTROL PERMISSION --------------------
    var $acls = array(
        "view",
        "edit",
        "create",
        "icreate"
    );
    
    var $defDoctype = 'P';
    var $defProfFamId = FAM_ACCESSFAM;
    
    function __construct($dbaccess = '', $id = '', $res = '', $dbid = 0)
    {
        // don't use Doc constructor because it could call this constructor => infinitive loop
        DocCtrl::__construct($dbaccess, $id, $res, $dbid);
    }
    
    function preImport(array $extra = array())
    {
        if ($this->getValue("dpdoc_famid")) {
            return ErrorCode::getError('PRFL0202', $this->getValue('ba_title'));
        }
        return '';
    }
}
?>