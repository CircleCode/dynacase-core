<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Ooo Layout test
 *
 * @author Anakeen
 *
 * @package Dcp\Pu
 */
/**
 */
/**
 */
/**
 * @begin-method-ignore
 * this part will be deleted when construct document class until end-method-ignore
 */
class _TST_FAMILYDEFAULT extends Doc
{
    /**
     * @end-method-ignore
     */
    public function isOne()
    {
        return 1;
    }
    
    public function oneMore($x)
    {
        return $x + 1;
    }
    
    public function itself($x)
    {
        return $x;
    }
    
    public function simpleAdd()
    {
        $tx = func_get_args();
        
        return array_sum($tx);
    }
    
    public function commaConcat()
    {
        $tx = func_get_args();
        
        return implode(',', $tx);
    }
    
    public function searchOnFamily()
    {
        include_once ("FDL/Class.SearchDoc.php");
        $s = new SearchDoc($this->dbaccess, "TST_DEFAULTFAMILY1");
        $s->search();
        return $s->count();
    }
    
    public function completeMyArray()
    {
        $t[] = array(
            "tst_text2" => "First",
            "tst_number2" => 10,
            "tst_docm2" => "9\n11"
        );
        $t[] = array(
            "tst_text2" => "Second",
            "tst_number2" => 20,
            "tst_docm2" => "12\n13"
        );
        return $t;
    }
    public function completeWrongArray()
    {
        $t = "not an array is a string";
        return $t;
    }
    public function completeWrongAttributeArray()
    {
        $t[] = array(
            "tst_text2" => "First",
            "tst_number2" => "pi",
            "tst_docm2" => "9\n11"
        );
        $t[] = array(
            "tst_text2" => "Seven",
            "tst_number2" => 20,
            "tst_docm2" => "12\n13"
        );
        return $t;
    }
    /**
     * @begin-method-ignore
     * this part will be deleted when construct document class until end-method-ignore
     */
}
/**
 * @end-method-ignore
 */
?>
