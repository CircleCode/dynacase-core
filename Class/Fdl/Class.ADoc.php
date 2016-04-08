<?php
/*
 * @author Anakeen
 * @package FDL
*/
/**
 * Attribute Document Object Definition
 *
 * @author Anakeen
 * @version $Id: Class.ADoc.php,v 1.13 2006/04/03 14:56:26 eric Exp $
 * @package FDL
 */
/**
 */

include_once ("FDL/Class.DocAttribute.php");
/**
 * Attribute Document Class
 *
 */
class ADoc
{
    /**
     * @var BasicAttribute[]
     */
    public $attr = array();
    public $fromname = '';
    public $isOrdered = false;
    /**
     * @var Array
     */
    public $fields = array();
    /**
     * @var array family ancestors ids
     */
    public $fromids = array();
    
    function __construct()
    {
        $this->attr["FIELD_HIDDENS"] = new FieldSetAttribute("FIELD_HIDDENS", 0, "hiddens");
    }
    /**
     * @param string $id attribute identifier
     * @return BasicAttribute
     */
    function getAttr($id)
    {
        if (isset($this->attr[$id])) return $this->attr[$id];
        if (isset($this->attr[strtolower($id) ])) return $this->attr[$id];
        
        return false;
    }
    /**
     * get attributes ids
     */
    function getAttrIds($id)
    {
        return array_keys($this->attr);
    }
    /**
     * return all the attributes except frame & menu & action
     * @param bool $onlyopt
     * @return NormalAttribute[]
     */
    function GetNormalAttributes($onlyopt = false)
    {
        $tsa = array();
        if (isset($this->attr)) {
            foreach ($this->attr as & $v) {
                if ((isset($v->isNormal)) && ((($v->usefor != "Q") && (!$onlyopt)) || (($v->usefor == "O") && ($onlyopt)))) $tsa[$v->id] = & $v;
            }
        }
        return $tsa;
    }
    /**
     * return action attributes
     *
     * @return  ActionAttribute[]
     */
    public function GetActionAttributes()
    {
        $tsa = array();
        
        foreach ($this->attr as $k => $v) {
            if (get_class($v) == "ActionAttribute") $tsa[$v->id] = $v;
        }
        return $tsa;
    }
    /**
     * return all the family parameters except frame & menu & action
     * @return NormalAttribute[]
     */
    function getParamAttributes()
    {
        $tsa = array();
        if (isset($this->attr)) {
            reset($this->attr);
            foreach ($this->attr as $k => $v) {
                if ((get_class($v) == "NormalAttribute") && ($v->usefor == "Q")) $tsa[$v->id] = $v;
            }
        }
        return $tsa;
    }
    /**
     * get attributes included in an arrary
     * @return NormalAttribute[]
     */
    function getArrayElements($id)
    {
        $a = $this->getAttr($id);
        
        if ($a && ($a->type == "array")) {
            if ($a->usefor != "Q") {
                $tsa = $this->GetNormalAttributes();
            } else {
                $tsa = $this->getParamAttributes();
            }
            $ta = array();
            foreach ($tsa as $k => $v) {
                if ($v->fieldSet->id == $id) $ta[$v->id] = $v;
            }
            return $ta;
        }
        return false;
    }
}
?>
