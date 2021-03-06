<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Layout Class
 * @author Anakeen
 * @version $Id: Class.Layout.php,v 1.49 2009/01/14 14:48:14 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage CORE
 */
/**
 */

include_once ('Class.Log.php');
include_once ('Class.Action.php');
include_once ('Class.Application.php');
/**
 *
 * @class Layout
 * @brief  Layout is a template generator
 *
 * Layout Class can manage three kind of datas :
 * @par
 * - 1) Simple tags :
 *    those tags are enclosed into brackets [] and can be replaced with any
 *    dynamic data given with the Set method.
 *    e.g : [MYDATA]  => $this->Set("MYDATA","this is my text");
 * @par
 * - 2) Block of Data :
 *    those tags are used to manage repeated set of data (as table for instance)
 *    You can assign a table of data to a specific block.
 * @code
 *     $table = array ( "0" => array ( "NAME" => "John",
 *                                          "SURNAME" => "Smith"),
 *                           "1" => array ( "NAME" => "Robert",
 *                                          "SURNAME" => "Martin"));
 * @endcode
 *    the block :
 * @code [BLOCK IDENTITY]
 *                <tr><td align="left">[NAME]</td>
 *                    <td align="right">[SURNAME]</td>
 *                </tr>
 *       [ENDBLOCK IDENTITY]
 * @endcode
 *    the code :
 * @code $lay = new Layout ("file containing the block");
 *                $lay->SetBlockData("IDENTITY",$table);
 *
 *                $out = $lay->gen();
 * @endcode
 *      $out  :
 * @code
 *     <tr><td align="left">John</td>
 *                    <td align="right">Smith</td>
 *                </tr>
 *                <tr><td align="left">Robert</td>
 *                    <td align="right">Martin</td>
 *                </tr>
 * @endcode
 * - 3) Call a specific script (need Core App Environment to work)
 *   tag syntax : [ZONE zonename]
 *     the zone name is linked to a specific application/function
 *          eg :  [ZONE CORE:APPLIST]
 *     then the APPLIST function in the CORE Application is called
 *     this function can then use another layout etc......
 */
class Layout
{
    private $strip = 'Y';
    public $encoding = "";
    /**
     * set to true to not parse template when it is generating
     * @var bool
     */
    public $noparse = false;
    protected $corresp;
    protected $data = null;
    /**
     * @var array
     */
    protected $rif = array();
    /**
     * @var array
     */
    protected $rkey = array();
    /**
     * @var array
     */
    protected $pkey = array();
    /**
     * @var Action
     */
    public $action = null;
    //########################################################################
    //# Public methods
    //#
    //#
    
    
    /**
     * construct layout to identify template
     *
     *
     * @param string $caneva file path of the template
     * @param Action $action current action
     * @param string $template if no $caneva found or is empty use this template.
     */
    public function __construct($caneva = "", $action = null, $template = "[OUT]")
    {
        $this->LOG = new Log("", "Layout");
        if (($template == "[OUT]") && ($caneva != "")) $this->template = sprintf(_("Template [%s] not found") , $caneva);
        else $this->template = $template;
        if ($action) $this->action = & $action;
        $this->generation = "";
        $file = $caneva;
        $this->file = "";
        if ($caneva != "") {
            if ((!file_exists($file)) && ($file[0] != '/')) {
                $file = GetParam("CORE_PUBDIR") . "/$file"; // try absolute
                
            }
            if (file_exists($file)) {
                $this->file = $file;
                $this->template = file_get_contents($file);
            }
        }
    }
    /**
     * set reference between array index and layout key
     *
     * use these data
     * @code
     *   $table = array ( "0" => array ( "name" => "John",
     "surname" => "Smith"),
     "1" => array ( "name" => "Robert",
     "surname" => "Martin"));
     * @endcode
     * with the code
     * @code
     * $lay = new Layout ("file containing the block");
     $lay->setBlockCorresp("IDENTITY","MYNAME","name");
     $lay->setBlockCorresp("IDENTITY","MYSURNAME","surname");
     $lay->SetBlockData("IDENTITY",$table);
     $out = $lay->gen();
     * @endcode
     * for template
     * @code[BLOCK IDENTITY]
     <tr><td align="left">[MYNAME]</td>
     <td align="right">[MYSURNAME]</td>
     </tr>
     [ENDBLOCK IDENTITY]
     * @endcode
     * @param string $p_nom_block
     * @param string $p_nom_modele
     * @param string $p_nom
     */
    public function setBlockCorresp($p_nom_block, $p_nom_modele, $p_nom = NULL)
    {
        $this->corresp["$p_nom_block"]["[$p_nom_modele]"] = ($p_nom == NULL ? $p_nom_modele : "$p_nom");
    }
    /**
     * set data to fill a block
     * @api set data to fill a block
     * @param string $p_nom_block block name
     * @param array $data data to fill the block
     */
    public function setBlockData($p_nom_block, $data = NULL)
    {
        $this->data["$p_nom_block"] = $data;
        // affect the $corresp block if not
        if (is_array($data)) {
            reset($data);
            $elem = current($data);
            if (isset($elem) && is_array($elem)) {
                foreach ($elem as $k => $v) {
                    if (!isset($this->corresp["$p_nom_block"]["[$k]"])) {
                        $this->setBlockCorresp($p_nom_block, $k);
                    }
                }
            }
        }
    }
    /**
     * return data set in block name
     * @see setBlockData
     *
     * @param string $p_nom_block block name
     * @return array|bool return data or false if no data are set yet
     */
    public function getBlockData($p_nom_block)
    {
        if (isset($this->data["$p_nom_block"])) return $this->data["$p_nom_block"];
        return false;
    }
    
    protected function SetBlock($name, $block)
    {
        if ($this->strip == 'Y') {
            //      $block = StripSlashes($block);
            $block = str_replace("\\\"", "\"", $block);
        }
        $out = "";
        
        if (isset($this->data) && isset($this->data["$name"]) && is_array($this->data["$name"])) {
            foreach ($this->data["$name"] as $k => $v) {
                $loc = $block;
                if (!is_array($this->corresp["$name"])) return sprintf(_("SetBlock:error [%s]") , $name);
                foreach ($this->corresp["$name"] as $k2 => $v2) {
                    $vv2 = (isset($v[$v2])) ? $v[$v2] : '';
                    if ((!is_object($vv2)) && (!is_array($vv2))) $loc = str_replace($k2, $vv2, $loc);
                }
                $this->rif = & $v;
                $this->ParseIf($loc);
                $out.= $loc;
            }
        }
        $this->ParseBlock($out);
        return ($out);
    }
    /**
     * need for php5.3 - not accept anonymous method which use this
     * @param array $matches
     * @return string
     */
    private function pregSetBlock($matches)
    {
        return $this->SetBlock($matches[1], $matches[2]);
    }
    protected function ParseBlock(&$out)
    {
        $out = preg_replace_callback('/(?m)\[BLOCK\s*([^\]]*)\](.*?)\[ENDBLOCK\s*\\1\]/s', array(
            $this,
            "pregSetBlock"
        ) , $out);
    }
    
    protected function TestIf($name, $block, $not = false)
    {
        $out = "";
        if (array_key_exists($name, $this->rif) || isset($this->rkey[$name])) {
            $n = (array_key_exists($name, $this->rif)) ? $this->rif[$name] : $this->rkey[$name];
            if ($n xor $not) {
                if ($this->strip == 'Y') {
                    $block = str_replace("\\\"", "\"", $block);
                }
                $out = $block;
                $this->ParseBlock($out);
                $this->ParseIf($out);
            }
        } else {
            if ($this->strip == 'Y') $block = str_replace("\\\"", "\"", $block);
            
            if ($not) $out = "[IFNOT $name]" . $block . "[ENDIF $name]";
            else $out = "[IF $name]" . $block . "[ENDIF $name]";
        }
        return ($out);
    }
    /**
     * need for php5.3 - not accept anonymous method which use this
     * @param array $matches
     * @return string
     */
    private function pregTestIf($matches)
    {
        return $this->TestIf($matches[2], $matches[3], $matches[1]);
    }
    
    protected function ParseIf(&$out)
    {
        $out = preg_replace_callback('/(?m)\[IF(NOT)?\s+([^\]]*)\](.*?)\[ENDIF\s*\\2\]/s', array(
            $this,
            "pregTestIf"
        ) , $out);
    }
    /**
     * need for php5.3 - not accept anonymous method which use this
     * @param array $matches
     * @return string
     */
    private function pregExecute($matches)
    {
        return $this->execute($matches[1], $matches[2]);
    }
    
    protected function ParseZone(&$out)
    {
        $out = preg_replace_callback('/\[ZONE\s+([^:]*):([^\]]*)\]/', array(
            $this,
            "pregExecute"
        ) , $out);
    }
    
    protected function ParseKey(&$out)
    {
        if (isset($this->rkey)) {
            
            $out = str_replace($this->pkey, $this->rkey, $out);
        }
    }
    /**
     * define new encoding text
     * default is utf-8
     * @param string $enc encoding (only 'utf-8' is allowed)
     * @deprecated not need always utf-8
     */
    public function setEncoding($enc)
    {
        if ($enc == "utf-8") {
            $this->encoding = $enc;
            // bind_textdomain_codeset("what", 'UTF-8');
            
        }
    }
    
    protected function execute($appname, $actionargn)
    {
        $limit = getParam('CORE_LAYOUT_EXECUTE_RECURSION_LIMIT', 0);
        if (is_numeric($limit) && $limit > 0) {
            $loop = $this->getRecursionCount(__CLASS__, __FUNCTION__);
            if ($loop['count'] >= $limit) {
                $this->printRecursionCountError(__CLASS__, __FUNCTION__, $loop['count']);
            }
        }
        
        if ($this->action == "") return ("Layout not used in a core environment");
        // analyse action & its args
        $actionargn = str_replace(":", "--", $actionargn); //For buggy function parse_url in PHP 4.3.1
        $acturl = parse_url($actionargn);
        $actionname = $acturl["path"];
        
        global $ZONE_ARGS;
        $OLD_ZONE_ARGS = $ZONE_ARGS;
        if (isset($acturl["query"])) {
            $acturl["query"] = str_replace("--", ":", $acturl["query"]); //For buggy function parse_url in PHP 4.3.1
            $zargs = explode("&", $acturl["query"]);
            while (list($k, $v) = each($zargs)) {
                if (preg_match("/([^=]*)=(.*)/", $v, $regs)) {
                    // memo zone args for next action execute
                    $ZONE_ARGS[$regs[1]] = urldecode($regs[2]);
                }
            }
        }
        
        if ($appname != $this->action->parent->name) {
            $appl = new Application();
            $appl->Set($appname, $this->action->parent);
        } else {
            $appl = & $this->action->parent;
        }
        
        if (($actionname != $this->action->name) || ($OLD_ZONE_ARGS != $ZONE_ARGS)) {
            $act = new Action();
            $res = '';
            if ($act->Exists($actionname, $appl->id)) {
                
                $act->Set($actionname, $appl);
            } else {
                // it's a no-action zone (no ACL, cannot be call directly by URL)
                $act->name = $actionname;
                
                $res = $act->CompleteSet($appl);
            }
            if ($res == "") {
                $res = $act->execute();
            }
            
            $jsRefs = $act->parent->getJsRef();
            foreach ($jsRefs as $jsRefe) {
                $this->action->parent->addJsRef($jsRefe);
            }
            $cssRefs = $act->parent->getCssRef();
            foreach ($cssRefs as $cssRefe) {
                $this->action->parent->addCssRef($cssRefe);
            }
            
            $ZONE_ARGS = $OLD_ZONE_ARGS; // restore old zone args
            return ($res);
        } else {
            return ("Fatal loop : $actionname is called in $actionname");
        }
    }
    /**
     * add a simple key /value in template
     * the key will be replaced by value when [KEY] is found in template
     * @api affect value to a key
     * @param string $tag
     * @param string $val
     */
    public function set($tag, $val)
    {
        $this->pkey[$tag] = "[$tag]";
        $this->rkey[$tag] = $val;
    }
    /**
     * return the value set for a key
     * @see Layout::set()
     *
     * @param string $tag
     * @return string
     */
    function get($tag)
    {
        if (isset($this->rkey[$tag])) return $this->rkey[$tag];
        return "";
    }
    
    protected function ParseRef(&$out)
    {
        if (!$this->action) return;
        $out = preg_replace_callback('/\[IMG:([^\|\]]+)\|([0-9]+)\]/', function ($matches)
        {
            global $action;
            return $action->parent->getImageLink($matches[1], true, $matches[2]);
        }
        , $out);
        
        $out = preg_replace_callback('/\[IMG:([^\]\|]+)\]/', function ($matches)
        {
            global $action;
            return $action->parent->getImageLink($matches[1]);
        }
        , $out);
        
        $out = preg_replace_callback('/\[IMGF:([^\]]*)\]/', function ($matches)
        {
            global $action;
            return $action->parent->GetFilteredImageUrl($matches[1]);
        }
        , $out);
    }
    
    protected function ParseText(&$out)
    {
        $out = preg_replace_callback('/\[TEXT(\([^\)]*\))?:([^\]]*)\]/', function ($matches)
        {
            $s = $matches[2];
            if ($s == "") return $s;
            if (!$matches[1]) {
                return _($s);
            } else {
                return ___($s, trim($matches[1], '()'));
            }
        }
        , $out);
    }
    
    protected function Text($s)
    {
        if ($s == "") return $s;
        return _($s);
    }
    
    protected function GenJsRef()
    {
        $js = "";
        $list[] = $this->action->GetParam("CORE_JSURL") . "/logmsg.js?wv=" . $this->action->GetParam("WVERSION");
        if (!empty($this->action->parent)) $list = array_merge($list, $this->action->parent->GetJsRef());
        
        reset($list);
        
        foreach ($list as $k => $v) {
            $js.= "\n" . sprintf('<script type="text/javascript" language="JavaScript" src="%s"></script>', $v);
        }
        return $js;
    }
    /**
     * get js code for notification (internal usage)
     * @param $showlog
     * @param bool $onlylog
     * @return string
     */
    public function GenJsCode($showlog, $onlylog = false)
    {
        $out = "";
        if (empty($this->action->parent)) return $out;
        if (!$onlylog) {
            
            $list = $this->action->parent->GetJsCode();
            foreach ($list as $k => $v) {
                $out.= $v . "\n";
            }
        }
        if ($showlog) {
            // Add log messages
            $list = $this->action->parent->GetLogMsg();
            reset($list);
            $out.= "var logmsg=new Array();\n";
            while (list($k, $v) = each($list)) {
                if (($v[0] == '{')) $out.= "logmsg[$k]=$v;\n";
                else $out.= "logmsg[$k]=" . json_encode($v) . ";\n";
            }
            
            $out.= "if ('displayLogMsg' in window) displayLogMsg(logmsg);\n";
            $this->action->parent->ClearLogMsg();
            // Add warning messages
            $list = $this->action->parent->GetWarningMsg();
            if (count($list) > 0) $out.= "displayWarningMsg('" . implode("\\n---------\\n", array_unique($list)) . "');\n";
            $this->action->parent->ClearWarningMsg();
        }
        if (!$onlylog) {
            // Add action notification messages
            $actcode = $actarg = array();
            $this->action->getActionDone($actcode, $actarg);
            if (count($actcode) > 0) {
                $out.= "var actcode=new Array();\n";
                $out.= "var actarg=new Array();\n";
                foreach ($actcode as $k => $v) {
                    $out.= "actcode[$k]='$v';\n";
                    $out.= "actarg[$k]='" . $actarg[$k] . "';\n";
                }
                $out.= "sendActionNotification(actcode,actarg);\n";
                $this->action->clearActionDone();
            }
        }
        return ($out);
    }
    private function pregGenJsCodeTrue($matches)
    {
        return $this->GenJsCode(true);
    }
    private function pregGenJsCodeFalse($matches)
    {
        return $this->GenJsCode(false);
    }
    protected function ParseJs(&$out)
    {
        $out = preg_replace_callback('/\[JS:REF\]/', array(
            $this,
            "GenJsRef"
        ) , $out);
        
        $out = preg_replace_callback('/\[JS:CODE\]/', array(
            $this,
            "pregGenJsCodeTrue"
        ) , $out);
        
        $out = preg_replace_callback('/\[JS:CODENLOG\]/', array(
            $this,
            "pregGenJsCodeFalse"
        ) , $out);
    }
    
    protected function GenCssRef($oldCompatibility = true)
    {
        $css = "";
        if (empty($this->action->parent)) return "";
        if ($oldCompatibility) {
            $cssLink = $this->action->parent->getCssLink("css/dcp/system.css", true);
            $css.= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$cssLink\">\n";
        }
        $list = $this->action->parent->GetCssRef();
        foreach ($list as $k => $v) {
            $css.= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$v\">\n";
        }
        return $css;
    }
    
    protected function GenCssCode()
    {
        if (empty($this->action->parent)) return "";
        $list = $this->action->parent->GetCssCode();
        reset($list);
        $out = "";
        while (list($k, $v) = each($list)) {
            $out.= $v . "\n";
        }
        return ($out);
    }
    private function pregGenCssRefFalse($matches)
    {
        return $this->GenCssRef(false);
    }
    protected function ParseCss(&$out)
    {
        $out = preg_replace_callback('/\[CSS:REF\]/', array(
            $this,
            "GenCssRef"
        ) , $out);
        $out = preg_replace_callback('/\[CSS:CUSTOMREF\]/', array(
            $this,
            "pregGenCssRefFalse"
        ) , $out);
        
        $out = preg_replace_callback('/\[CSS:CODE\]/', array(
            $this,
            "GenCssCode"
        ) , $out);
    }
    /**
     * Generate text from template with data included
     * @api generate text from template
     * @return string the complete text
     */
    public function gen()
    {
        if ($this->noparse) return $this->template;
        // if used in an app , set the app params
        if (is_object($this->action) && (!empty($this->action->parent))) {
            $list = $this->action->parent->GetAllParam();
            foreach ($list as $k => $v) {
                if (!is_scalar($v)) $v = "notScalar";
                $this->set($k, $v);
            }
        }
        $out = $this->template;
        
        $this->ParseBlock($out);
        $this->rif = & $this->rkey;
        $this->ParseIf($out);
        // Parse IMG: and LAY: tags
        $this->ParseText($out);
        $this->ParseKey($out);
        $this->ParseRef($out);
        $this->ParseZone($out);
        $this->ParseJs($out);
        $this->ParseCss($out);
        
        return ($out);
    }
    /**
     * Count number of execute() calls on the stack to detect infinite recursive loops
     * @param string $class name to track
     * @param string $function/method name to track
     * @return array array('count' => $callCount, 'delta' => $callDelta, 'depth' => $stackDepth)
     */
    protected function getRecursionCount($class, $function)
    {
        $count = 0;
        $curDepth = 0;
        $prevDepth = 0;
        $delta = 0;
        
        $bt = debug_backtrace(false);
        $btCount = count($bt);
        for ($i = $btCount - 2; $i >= 0; $i--) {
            $curDepth++;
            $bClass = isset($bt[$i]['class']) ? $bt[$i]['class'] : '';
            $bFunction = isset($bt[$i]['function']) ? $bt[$i]['function'] : '';
            if ($class == $bClass && $function == $bFunction) {
                $delta = $curDepth - $prevDepth;
                $prevDepth = $curDepth;
                $count++;
            }
        }
        
        return array(
            'count' => $count,
            'delta' => $delta,
            'depth' => $curDepth
        );
    }
    /**
     * Print a recursion count error message and stop execution
     * @param string $class name to display
     * @param string $function/method name to display
     * @param int $count the call count that triggered the error
     */
    protected function printRecursionCountError($class, $function, $count)
    {
        include_once ('WHAT/Lib.Prefix.php');
        
        $http_code = 500;
        $http_reason = "Recursion Count Error";
        header(sprintf("HTTP/1.1 %s %s", $http_code, $http_reason));
        
        print "<html><head>\n";
        print "<title>" . htmlspecialchars($http_reason) . "</title>\n";
        print "</head></body>\n";
        
        print "<h1>" . sprintf("%s %s", htmlspecialchars($http_code) , htmlspecialchars($http_reason)) . "</h1>\n";
        
        $message = sprintf("Infinite recursive loop in %s::%s() (call count = '%s')", $class, $function, $count);
        print "<h2>" . htmlspecialchars($message) . "</h2>\n";
        error_log(sprintf("%s::%s Error: %s", $class, $function, $message));
        
        print "</body></html>\n";
        exit;
    }
}
