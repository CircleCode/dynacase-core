<?php
/**
 * Document Object Definition
 *
 * @author Anakeen 2002
 * @version $Id:  $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package API
 */
/**
 */
include_once("DATA/Class.Document.php");
include_once("DATA/Lib.FullSearch.php");

/**
 * Document Class
 *
 */
Class Fdl_Collection extends Fdl_Document {


    /**
     * return documents list
     * @param boolean $onlyvalues
     * @param boolean $completeprop
     * @param string $filter
     * @param int $start
     * @param int $slice
     * @param string $orderby
     * @param boolean $verifyhaschild
     * @return array of raw documents
     */
    public function getContent($onlyvalues=true,$completeprop=false,$filter=false,$start=0,$slice=100,$orderby="",$verifyhaschild=false,$key="",$keymode="word",$keyproperty="svalues") {
        include_once("FDL/Class.SearchDoc.php");
        $s=new SearchDoc($this->dbaccess);
        $s->dirid=$this->getProperty('initid');
        if ($orderby) $s->orderby=$orderby;
        $s->slice=$slice;
        $s->start=$start;
        $s->addFilter("confidential is null or hasdocprivilege(%d,profid,1024)",Doc::getSystemUserId());
        
        $out=false;
        $content=array();
        if ($s->dirid > 0) {
            $s->setObjectReturn();  
            if ($key) {
            if ($keymode=="word") {
                DocSearch::getFullSqlFilters($key,$sqlfilters,$fullorderby,$keyword);
                foreach ($sqlfilters as $vfilter) $s->addFilter($vfilter);
                if (! $orderby) $orderby=$fullorderby;
            } else {
                $s->addFilter("%s ~* '%s'",($keyproperty?$keyproperty:"svalues"),$key);
            }
        }
            if ($filter){
                if (is_string($filter)) {
                    $lfilter=strtolower($filter);
                    if ((! strstr($lfilter,'--')) &&
                    (! strstr($lfilter,';')) &&
                    (! strstr($lfilter,'insert')) &&
                    (! strstr($lfilter,'alter')) &&
                    (! strstr($lfilter,'delete')) &&
                    (! strstr($lfilter,'update'))
                    ) {
                        // try to prevent sql injection
                        $s->addFilter($filter);
                    }
                } elseif (is_object($filter)) {
                    $err=$this->doc->object2SqlFilter($filter,$ofamid,$sfilter);
                    $this->setError($err);
                    if ($ofamid) $s->fromid=$ofamid;
                    $s->addFilter($sfilter);
                }
            }
            if ($err=="") {
                $s->setDebugMode();
                $s->search();
                $out->info=$s->getDebugInfo();
                $this->setError($out->info["error"]);
                $tmpdoc=new Fdl_Document();
                $kd=0;
                while ($doc=$s->nextDoc()) {
                    $tmpdoc->affect($doc);
                    if ($verifyhaschild) {
                        $tmpdoc->setVolatileProperty("haschildfolder",hasChildFld($this->dbaccess, $tmpdoc->getProperty('initid'),($doc->doctype=='S')));

                    }
                    $content[$kd]=$tmpdoc->getDocument($onlyvalues,$completeprop);
                    $kd++;
                }

                $out->totalCount=$s->count();
                if (($out->totalCount == $slice) || ($start > 0)) {
                    $s->slice='ALL';
                    $s->start=0;
                    $s->reset();
                    $s->setDebugMode();
                    $oc=$s->onlyCount();
                    $out->info["totalCount"]=$s->getDebugInfo();
                    if ($oc) $out->totalCount=$oc;
                }
            }
        } else $this->error=sprintf(_("document not initialized"));
        $out->error=$this->error;
        $out->content=$content;
        $out->slice=$slice;
        $out->start=$start;
        $out->date=date('Y-m-d H:i:s');

        return $out;
    }


    /**
     * return document list from a keyword and optionnaly family identificator
     * @param string $key
     * @param string $mode search method : regexp or word
     * @param int $famid filter on family
     * @param object $filter additionnal filter
     * @param int $start offset to start search (default is 0)
     * @param int $slice number of document returned
     * @param string $orderby order by property or attribute
     * @param boolean $onlyvalues set to true is want return attribute definition also
     * @param string $searchproperty property use where key is applied
     * @param boolean $whl with highlight return also text including keyword. the keyword is between HTML B tag.
     * @return array of raw document
     */
    public function simpleSearch($key,$mode="word",$famid=0,$filter="",$start=0,$slice=100,$orderby="",$onlyvalues=true,$searchproperty="svalues",$whl=false) {
        include_once("FDL/Class.SearchDoc.php");
        static $sw=null;

        $tfid=array();
        if (strstr($famid,'|')) {
            // multi family search
            $tfamids=explode('|',$famid);
            foreach ($tfamids as $fid) {
                if (! is_numeric($fid)) $fid=getFamidFromName($this->dbaccess,$fid);
                if ($fid>0) $tfid[]=$fid;
            }

            $famid=0;
        }
        if (preg_match("/([\w:]*)\s?strict/",trim($famid),$reg)) {
            if (! is_numeric($reg[1])) $reg[1]=getFamIdFromName($this->dbaccess,$reg[1]);
            $famid='-'.$reg[1];
        }
        $s=new SearchDoc($this->dbaccess,$famid);
        if ($key) {
            if ($mode=="word") {
                DocSearch::getFullSqlFilters($key,$sqlfilters,$fullorderby,$keyword);
                foreach ($sqlfilters as $vfilter) $s->addFilter($vfilter);
                if (! $orderby) $orderby=$fullorderby;
            } else {
                $s->addFilter(sprintf("%s ~* '%s'",$searchproperty,$key));
            }
        }
        if ($filter) {
            if (is_string($filter)) {
                $lfilter=strtolower($filter);
                if ((! strstr($lfilter,'--')) &&
                (! strstr($lfilter,';')) &&
                (! stristr($lfilter,'insert')) &&
                (! stristr($lfilter,'alter')) &&
                (! stristr($lfilter,'delete')) &&
                (! stristr($lfilter,'update'))
                ) {
                    // try to prevent sql injection
                    $s->addFilter($filter);
                }
            } elseif (is_object($filter)) {
                if (! $sw) {
                    $sw=createTmpDoc($this->dbaccess,"DSEARCH");
                    $sw->setValue("se_famid",$famid);
                }
                $err=$sw->object2SqlFilter($filter,$ofamid,$sfilter);
                $this->setError($err);
                if ($ofamid) {
                    $s->fromid=$ofamid;
                }
                $s->addFilter($sfilter);
            }
        }
        if ($err=="") {
            if (count($tfid) >0) $s->addFilter(getSqlCond($tfid,'fromid',true));
            $completeprop=false;
            $content=array();
            $s->slice=$slice;
            $s->start=$start;
            if ($orderby) $s->orderby=$orderby;
            $s->setDebugMode();
            $s->setObjectReturn();
            $s->addFilter("confidential is null or hasdocprivilege(%d,profid,1024)",Doc::getSystemUserId());
            $s->search();
            $info=$s->getDebugInfo();
            $out->error=$info["error"];
            $out->info=$info;

            if (! $out->error) {                 
                $ws=createTmpDoc($this->dbaccess,"DSEARCH");
                $ws->setValue("ba_title",sprintf(_("search %s"),$key));
                $ws->add();
                $ws->addStaticQuery($s->getOriginalQuery());
                $tmpdoc=new Fdl_Document($ws->id);
                $out->document=$tmpdoc->getDocument(true,false);
                $idx=0;
                if (! $keyword) $keyword=str_replace(" ","|",$key);
                while ($doc=$s->nextDoc()) {
                    $tmpdoc->affect($doc);
                    $content[$idx]=$tmpdoc->getDocument($onlyvalues,$completeprop);
                    if ($whl) $content[$idx]['highlight']=getHighlight($doc,$keyword);
                    $idx++;
                }

                $out->totalCount=$s->count();
                if (($out->totalCount == $slice) || ($start > 0)) {
                    $s->slice='ALL';
                    $s->start=0;
                    $s->setDebugMode();
                    $s->reset();
                    $out->totalCount=$s->onlyCount();
                    $info=$s->getDebugInfo();

                    $out->delay.=' count:'.$info["delay"];
                }
                $out->content=$content;
            }
        } else {
            $out->error=$err;
        }
        $out->slice=$slice;
        $out->start=$start;
        $out->date=date('Y-m-d H:i:s');
        return $out;
    }
    /**
     * return child families
     * @param int $famid the family root
     * @return array of families where $famid is an ancestor
     */
    public function getSubFamilies($famid, $controlcreate=false) {
        $fam=new_doc($this->dbaccess,$famid);
        if (! $fam->isAlive()) {
            $out->error=sprintf(_("data:family %s not alive"),$famid);
        } elseif ($fam->doctype!='C') {
            $out->error=sprintf(_("data:document %s is not a family"),$famid);
        } else {
            $fld=new Dir($this->dbaccess);
            if (!is_numeric($famid)) $famid=getFamIdFromName($this->dbaccess,$famid);
            $tfam=$fld->GetChildFam($famid, $controlcreate);
            if (count($tfam) > 0) {
                $tmpdoc=new Fdl_Document();
                $onlyvalues=true;
                $completeprop=false;
                $content=array();
                foreach ($tfam as $id=>$rawfam) {
                    $fam->affect($rawfam);
                    $tmpdoc->affect($fam);
                    if (! $tmpdoc->error) {
                        $content[]=$tmpdoc->getDocument($onlyvalues,$completeprop);
                    }
                }
            }
            $out->content=$content;
            $out->totalCount=count($content);
        }
        return $out;
    }

    /**
     * insert a document into folder
     * @param int $docid the document identificator to insert to
     * @return object with error or message field
     */
    public function insertDocument($docid) {
        if ($this->docisset()) {
            $err=$this->doc->addFile($docid);
            if ($err!="") {
                $this->setError($err);
                $out->error=$err;
            } else {
                $out->message=sprintf(_("document %d inserted"),$docid);
            }
        } else $out->error=sprintf(_("document not set"));
        return $out;
    }
    /**
     * unlink a document from folder
     * @param int $docid the document identificator to unlink
     * @return object with error or message field
     */
    function unlinkDocument($docid) {
        if ($this->docisset()) {
            $err=$this->doc->delFile($docid);
            if ($err!="") {
                $this->setError($err);
                $out->error=$err;
            } else {
                $out->message=sprintf(_("document %d deleted"),$docid);
            }
        } else $out->error=sprintf(_("document not set"));
        return $out;
    }

    /**
     * unlink several documents from folder
     * @param object $selection selection of document
     * @return object with error or message field
     */
    function unlinkDocuments($selection) {
        include_once("DATA/Class.DocumentSelection.php");
        $os=new Fdl_DocumentSelection($selection);
        $ids=$os->getIdentificators();

        if ($this->docisset()) {
            $out->notunlinked=array();
            $out->unlinked=array();
            $err=$this->doc->canModify();
            if ($err!="") {
                $out->error=$err;
            } else {
                foreach ($ids as $docid) {
                    $err=$this->doc->delFile($docid);
                    if ($err!="") {
                        $out->notunlinked[$docid]=$err;
                    } else {
                        $out->unlinked[$docid]=sprintf(_("document %d unlinked"),$docid)."\n";
                    }
                }
                $out->unlinkedCount=count($out->unlinked);
                $out->notUnlinkedCount=count($out->notunlinked);
            }

        } else $out->error=sprintf(_("document not set"));
        return $out;
    }/**
    * unlink all documents from folder
    * @param object $selection selection of document
    * @return object with error or message field
    */
    function unlinkAllDocuments() {
        if ($this->docisset()) {
            $out->error="";
            $err=$this->doc->canModify();
            if ($err!="") {
                $out->error=$err;
            } else {
                $out->error=$this->doc->clear();
            }

        } else $out->error=sprintf(_("document not set"));
        return $out;
    }
    /**
     * move several documents from folder
     * @param object $selection selection of document
     * @return object with error or message field
     */
    function moveDocuments($selection, $targetId) {
        include_once("DATA/Class.DocumentSelection.php");
        $os=new Fdl_DocumentSelection($selection);
        $ids=$os->getIdentificators();



        if ($this->docisset()) {
            $out->notmoved=array();
            $out->moved=array();
            $err=$this->doc->canModify();
            if ($err=="") {
                $targetDoc=new_doc($this->dbaccess,$targetId);
                if ($targetDoc->isAlive()) {
                    if ($targetDoc->defDoctype != 'D') $err=sprintf(_("target folder [%s] is not a folder"),$targetDoc->getTitle());
                    else {
                        $err=$targetDoc->canModify();
                    }
                } else {
                    $err=sprintf(_("target folder [%s] is not set"),$targetId);
                }
                if ($err!="") {
                    $out->error=$err;
                } else {
                    foreach ($ids as $docid) {
                        $err=$this->doc->moveDocument($docid,$targetDoc->initid);
                        if ($err!="") {
                            $out->notmoved[$docid]=$err;
                        } else {
                            $out->moved[$docid]=sprintf(_("document %d moved"),$docid)."\n";
                        }
                    }
                    $out->movedCount=count($out->moved);
                    $out->notMovedCount=count($out->notmoved);
                }
            } else {
                $out->error=$err;
            }
        } else $out->error=sprintf(_("document not set"));
        return $out;
    }


    /**
     * insert several documents to folder
     * @param object $selection selection of document
     * @return object with error or message field
     */
    function insertDocuments($selection) {
        include_once("DATA/Class.DocumentSelection.php");
        $os=new Fdl_DocumentSelection($selection);
        if ($this->docisset()) {
            $tdocs=$os->getRawDocuments();
            $out->notinserted=array();
            $out->inserted=array();
            $err=$this->doc->insertMDoc($tdocs,"latest",false,$out->inserted,$out->notinserted);
            $out->insertedCount=count($out->inserted);
            $out->notInsertedCount=count($out->notinserted);
            $out->error=$err;
        } else $out->error=sprintf(_("document not set"));
        return $out;
    }


    function getAuthorizedFamilies() {
        if ($this->docisset()) {
            if (method_exists($this->doc,"getAuthorizedFamilies")) {

                return array("restriction"=>$this->doc->hasNoRestriction()?false:true,
		     "families"=>$this->doc->getAuthorizedFamilies());
            }
        }
        return null;
    }


}

?>