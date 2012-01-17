<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

class CheckEnd extends CheckData
{
    /**
     * @var Doc
     */
    protected $doc;
    /**
     * @param array $data
     * @param Doc $doc
     * @return CheckEnd
     */
    public function check(array $data, &$doc = null)
    {
        $this->doc = $doc;
        if ($doc->usefor == 'W') {
            $checkW = new CheckWorkflow($doc->classname, $doc->name);
            $checkCr = $checkW->verifyWorkflowComplete();
            if (count($checkCr) > 0) {
                $this->addError(implode("\n", $checkCr));
            }
        }
        
        $this->checkSetAttributes();
        $this->checkComputedConstraintAttributes();
        return $this;
    }
    
    protected function checkSetAttributes()
    {
        $this->doc->getAttributes(); // force reattach attributes
        $la = $this->doc->GetNormalAttributes();
        foreach ($la as & $oa) {
            $foa = $oa->fieldSet;
            if (!$foa) {
                $this->addError(ErrorCode::getError('ATTR0203', $oa->id));
            } elseif ((!is_a($foa, "FieldSetAttribute")) && ($foa->type != 'array')) {
                $this->addError(ErrorCode::getError('ATTR0204', $foa->id, $oa->id));
            } else {
                $type = $oa->type;
                $ftype = $oa->fieldSet->type;
                if (($ftype != 'frame') && ($ftype != 'array')) {
                    $this->addError(ErrorCode::getError('ATTR0205', $foa->id, $oa->id));
                } elseif ($ftype == 'array') {
                    if ($oa->needed) {
                        $this->addError(ErrorCode::getError('ATTR0902', $oa->id));
                    }
                }
            }
        }
        
        $la = $this->doc->GetFieldAttributes();
        foreach ($la as & $oa) {
            $foa = $oa->fieldSet;
            if ($foa) {
                $type = $oa->type;
                $ftype = $oa->fieldSet->type;
                if (($type == 'frame') && ($ftype != 'tab') && ($oa->fieldSet->id != BasicAttribute::hiddenFieldId)) {
                    $this->addError(ErrorCode::getError('ATTR0207', $foa->id, $oa->id));
                }
            }
        }
    }
    
    protected function checkComputedConstraintAttributes()
    {
        $this->doc->getAttributes(); // force reattach attributes
        $la = $this->doc->GetNormalAttributes();
        foreach ($la as & $oa) {
            if (($oa->phpfile == '' || $oa->phpfile == '-') && (preg_match('/^[a-z0-9_]*::/', $oa->phpfunc))) {
                $this->checkMethod($oa);
            }
            if (preg_match('/^[a-z0-9_]*::/', $oa->phpconstraint)) {
                $this->checkConstraint($oa);
            }
        }
    }
    /**
     * check method validity for phpfunc property
     * @param NormalAttribute $oa
     */
    private function checkMethod(NormalAttribute & $oa)
    {
        $oParse = new parseFamilyMethod();
        $strucFunc = $oParse->parse($oa->phpfunc);
        $error = $oParse->getError();
        if ($error) {
            $this->addError(ErrorCode::getError('ATTR1262', $oa->phpfunc, $oa->id, $error));
        } else {
            
            $phpMethName = $strucFunc->methodName;
            if ($strucFunc->className) {
                $phpClassName = $strucFunc->className;
            } else {
                $phpClassName = sprintf("Doc%d", $this->doc->id);
            }
            $phpLongName = $strucFunc->className . '::' . $phpMethName;
            try {
                $refMeth = new ReflectionMethod($phpClassName, $phpMethName);
                $numArgs = $refMeth->getNumberOfRequiredParameters();
                if ($numArgs > count($strucFunc->inputs)) {
                    $this->addError(ErrorCode::getError('ATTR1261', $phpLongName, $numArgs, $oa->id));
                } else {
                    if ($strucFunc->className && (!$refMeth->isStatic())) {
                        $this->addError(ErrorCode::getError('ATTR1263', $phpLongName, $oa->id));
                    }
                }
            }
            catch(Exception $e) {
                $this->addError(ErrorCode::getError('ATTR1260', $phpLongName, $oa->id));
            }
        }
    }
    /**
     * check method validity for constraint property
     * @param NormalAttribute $oa
     */
    private function checkConstraint(NormalAttribute & $oa)
    {
        $oParse = new parseFamilyMethod();
        $strucFunc = $oParse->parse($oa->phpconstraint, true);
        $error = $oParse->getError();
        if ($error) {
            $this->addError(ErrorCode::getError('ATTR1404', $oa->phpconstraint, $oa->id, $error));
        } else {
            
            $phpMethName = $strucFunc->methodName;
            if ($strucFunc->className) {
                $phpClassName = $strucFunc->className;
            } else {
                $phpClassName = sprintf("Doc%d", $this->doc->id);
            }
            $phpLongName = $strucFunc->className . '::' . $phpMethName;
            try {
                $refMeth = new ReflectionMethod($phpClassName, $phpMethName);
                $numArgs = $refMeth->getNumberOfRequiredParameters();
                if ($numArgs > count($strucFunc->inputs)) {
                    $this->addError(ErrorCode::getError('ATTR1401', $phpLongName, $numArgs, $oa->id));
                } else {
                    if ($strucFunc->className && (!$refMeth->isStatic())) {
                        $this->addError(ErrorCode::getError('ATTR1403', $phpLongName, $oa->id));
                    }
                }
            }
            catch(Exception $e) {
                $this->addError(ErrorCode::getError('ATTR1402', $phpLongName, $oa->id));
            }
        }
    }
}
