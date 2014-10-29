<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
include_once ("FDL/import_file.php");
class ImportDocument
{
    
    private $begtime = 0;
    /**
     * @var array report
     */
    private $cr = array();
    /**
     * @var bool strict mode
     */
    private $strict = true;
    
    private $onlyAnalyze = false;
    /**
     * @var int folder to insert documents
     */
    private $dirid = 0;
    /**
     * @var string csv separator character
     */
    protected $csvSeparator = ';';
    /**
     * @var string csv enclose character
     */
    protected $csvEnclosure = '';
    /**
     * @var string csv line-break sequence
     */
    protected $csvLinebreak = '\n';
    /**
     * @var string update|add|keep
     */
    protected $policy = "update";
    /**
     * To verify visibility "I" of atttribute
     * @var bool
     */
    protected $verifyAttributeAccess = true;
    
    protected $reset = array();
    /**
     * set strict mode
     * @param bool $strict set to false to accept error when import
     * @return void
     */
    public function setStrict($strict)
    {
        $this->strict = ($strict && true);
    }
    public function setPolicy($policy)
    {
        $this->policy = $policy;
    }
    public function setReset($reset)
    {
        if (is_array($reset)) {
            $this->reset = $reset;
        } elseif (is_string($reset)) {
            $this->reset[] = $reset;
        }
    }
    
    public function setCsvOptions($csvSeparator = ';', $csvEnclosure = '"', $csvLinebreak = '\n')
    {
        $this->csvSeparator = $csvSeparator;
        $this->csvEnclosure = $csvEnclosure;
        $this->csvLinebreak = $csvLinebreak;
    }
    
    public function setTargetDirectory($dirid)
    {
        $this->dirid = $dirid;
    }
    /**
     * @param Action $action current action
     * @param string $file filename path to import
     * @param bool $onlyAnalyze if true only analyze not import really
     * @param bool $archive if true to import file like an standard archive
     * @return array analyze report
     */
    public function importDocuments(Action & $action, $file, $onlyAnalyze = false, $archive = false)
    {
        $point = '';
        if ($this->strict) {
            $point = 'importDocument';
            //$action->debug=true;
            $action->savePoint($point);
        }
        $this->onlyAnalyze = $onlyAnalyze;
        if ($archive) {
            include_once ("FREEDOM/freedom_ana_tar.php");
            $untardir = getTarExtractDir($action, basename($file));
            $mime = getSysMimeFile($file, basename($file));
            //print_r(array($untardir, $file, $mime));
            $status = extractTar($file, $untardir, $mime);
            if ($status != 0) {
                $err = sprintf(_("cannot extract archive %s: status : %s") , $file, $status);
                $this->cr[] = array(
                    "err" => $err,
                    "msg" => "",
                    "specmsg" => "",
                    "folderid" => 0,
                    "foldername" => "",
                    "filename" => "",
                    "title" => "",
                    "id" => "",
                    "values" => array() ,
                    "familyid" => 0,
                    "familyname" => "",
                    "action" => " "
                );
            } else {
                $onlycsv = hasfdlpointcsv($untardir);
                $simpleFamilyFile = 7; // file
                $simpleFamilyFolder = 2; // folder
                $dirid = $this->dirid; // directory to insert imported doc
                $this->cr = import_directory($action, $untardir, $dirid, $simpleFamilyFile, $simpleFamilyFolder, $onlycsv, $onlyAnalyze, $this->csvLinebreak);
            }
        } else {
            $ext = substr($file, strrpos($file, '.') + 1);
            $this->begtime = Doc::getTimeDate(0, true);
            if ($ext == "xml") {
                include_once ("FREEDOM/freedom_import_xml.php");
                $this->cr = freedom_import_xml($action, $file);
            } else if ($ext == "zip") {
                include_once ("FREEDOM/freedom_import_xml.php");
                $this->cr = freedom_import_xmlzip($action, $file);
            } else {
                $this->cr = $this->importSingleFile($file);
            }
        }
        if ($this->strict) {
            if ($this->getErrorMessage()) {
                $action->rollbackPoint($point);
            } else {
                $action->commitPoint($point);
            }
        }
        return $this->cr;
    }
    /**
     * @param boolean $verifyAttributeAccess
     */
    public function setVerifyAttributeAccess($verifyAttributeAccess)
    {
        $this->verifyAttributeAccess = $verifyAttributeAccess;
    }
    public function importSingleFile($file)
    {
        $if = new importDocumentDescription($file);
        $if->setImportDirectory($this->dirid);
        $if->analyzeOnly($this->onlyAnalyze);
        $if->setPolicy($this->policy);
        $if->setVerifyAttributeAccess($this->verifyAttributeAccess);
        $if->reset($this->reset);
        $if->setCsvOptions($this->csvSeparator, $this->csvEnclosure, $this->csvLinebreak);
        return $if->import();
    }
    /**
     * return all error message concatenated
     * @return string
     */
    public function getErrorMessage()
    {
        $terr = array();
        foreach ($this->cr as $cr) {
            if ($cr["err"]) $terr[] = $cr["err"];
        }
        if (count($terr) > 0) {
            return '[' . implode("]\n[", $terr) . ']';
        } else {
            return '';
        }
    }
    /**
     * write report in file
     * @param string $log filename path to write in
     * @return void
     */
    public function writeHTMLImportLog($log)
    {
        if ($log) {
            $flog = fopen($log, "w");
            if (!$flog) {
                addWarningMsg(sprintf(_("cannot write log in %s") , $log));
            } else {
                global $action;
                $lay = new Layout(getLayoutFile("FREEDOM", "freedom_import.xml") , $action);
                $this->writeHtmlCr($lay);
                fputs($flog, $lay->gen());
                fclose($flog);
            }
        }
    }
    /**
     * internal method use only from freedom_import
     * @param Layout $lay
     * @return void
     */
    public function writeHtmlCr(Layout & $lay)
    {
        $hasError = false;
        $haswarning = false;
        foreach ($this->cr as $k => $v) {
            if (!isset($v["msg"])) $v["msg"] = '';
            if (!isset($v["values"])) $v["values"] = null;
            $this->cr[$k]["taction"] = _($v["action"]); // translate action
            $this->cr[$k]["order"] = $k; // translate action
            $this->cr[$k]["svalues"] = "";
            $this->cr[$k]["msg"] = nl2br($v["msg"]);
            if (is_array($v["values"])) {
                foreach ($v["values"] as $ka => $va) {
                    $this->cr[$k]["svalues"].= "<LI" . (($va == "/no change/") ? ' class="no"' : '') . ">[$ka:$va]</LI>"; //
                    
                }
            }
            if ($v["action"] == "ignored") $hasError = true;
            if ($v["action"] == "warning") $haswarning = true;
        }
        $nbdoc = count(array_filter($this->cr, array(
            $this,
            "isdoc"
        )));
        $lay->SetBlockData("ADDEDDOC", $this->cr);
        $lay->set("haserror", $hasError);
        $lay->set("haswarning", $haswarning);
        $lay->Set("nbdoc", $nbdoc);
        $lay->set("analyze", ($this->onlyAnalyze));
        $lay->Set("nbprof", count(array_filter($this->cr, array(
            $this,
            "isprof"
        ))));
    }
    /**
     * record a log file from import results
     *
     * @param string $log output file path
     */
    public function writeImportLog($log)
    {
        if ($log) {
            $flog = fopen($log, "w");
            if (!$flog) {
                addWarningMsg(sprintf(_("cannot write log in %s") , $log));
            } else {
                fputs($flog, sprintf("IMPORT BEGIN OK : %s\n", $this->begtime));
                $countok = 0;
                $counterr = 0;
                foreach ($this->cr as $v) {
                    
                    if (!isset($v["msg"])) $v["msg"] = '';
                    if (!isset($v["values"])) $v["values"] = null;
                    $chg = "";
                    if (is_array($v["values"])) {
                        foreach ($v["values"] as $ka => $va) {
                            if ($va != "/no change/") $chg.= "{" . $ka . ":" . str_replace("\n", "-", $va) . '}';
                        }
                    }
                    fputs($flog, sprintf("IMPORT DOC %s : [title:%s] [id:%d] [action:%s] [changes:%s] [message:%s] %s\n", $v["err"] ? "KO" : "OK", $v["title"], $v["id"], $v["action"], $chg, str_replace("\n", "-", $v["msg"]) , $v["err"] ? ('[error:' . str_replace("\n", "-", $v["err"]) . ']') : ""));
                    if ($v["err"]) $counterr++;
                    else $countok++;
                }
                fputs($flog, sprintf("IMPORT COUNT OK : %d\n", $countok));
                fputs($flog, sprintf("IMPORT COUNT KO : %d\n", $counterr));
                fputs($flog, sprintf("IMPORT END OK : %s\n", Doc::getTimeDate(0, true)));
                fclose($flog);
            }
        }
    }
    
    public static function isdoc($var)
    {
        return (($var["action"] == "added") || ($var["action"] == "updated"));
    }
    
    public static function isprof($var)
    {
        return (($var["action"] == "modprofil"));
    }
}
