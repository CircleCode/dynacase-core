<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Ooo Layout test
 *
 * @author Anakeen 2010
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package DCP
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
    /**
     * @begin-method-ignore
     * this part will be deleted when construct document class until end-method-ignore
     */
}
/**
 * @end-method-ignore
 */
?>
