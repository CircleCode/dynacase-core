<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Grouped searches
 */
namespace Dcp\Core;
class GroupedSearch extends \Dcp\Family\Search
{
    var $defaultedit = "FDL:EDITBODYCARD";
    var $defaultview = "FDL:VIEWBODYCARD";
    
    function ComputeQuery($keyword = "", $famid = - 1, $latest = "yes", $sensitive = false, $dirid = - 1, $subfolder = true, $full = false)
    {
        $tidsearch = $this->getMultipleRawValues("SEG_IDCOND");
        $wsql = array();
        $query[] = "select * from doc1 limit 0;"; // null query
        foreach ($tidsearch as $k => $v) {
            /**
             * @var \Dcp\Family\Search $doc
             */
            $doc = new_Doc($this->dbaccess, $v);
            
            if (method_exists($doc, "getQuery")) {
                $doc->setValue("SE_IDCFLD", $this->getRawValue("SE_IDCFLD"));
                $q = $doc->getQuery();
                
                $wsql[] = $q[0];
            }
        }
        if (count($wsql) > 0) {
            $query = $wsql;
        }
        
        return $query;
    }
    /**
     * return false : is never staticSql
     * @return bool
     */
    function isStaticSql()
    {
        return false;
    }
}