<?php
/**
 * Full Text Search document
 *
 * @author Anakeen 2007
 * @version $Id: fullsearch.php,v 1.10 2008/01/04 17:56:37 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FREEDOM
 * @subpackage GED
 */
/**
 */

include_once ("FDL/Class.SearchDoc.php");
include_once ("FDL/Class.DocSearch.php");

include_once ("FDL/freedom_util.php");

/**
 * Fulltext Search document
 * @param Action &$action current action
 * @global keyword Http var : word to search in any values
 * @global famid Http var : restrict to this family identioficator
 * @global start Http var : page number
 * @global dirid Http var : search identificator
 */
function fullsearchresult(&$action)
{

    $famid = GetHttpVars("famid", 0);
    $keyword = GetHttpVars("_se_key", GetHttpVars("keyword")); // keyword to search
    $target = GetHttpVars("target"); // target window when click on document
    $page = GetHttpVars("page", 0); // page number
    $dirid = GetHttpVars("dirid", 0); // special search

    $slice = 10;
    $start = $page * $slice;



    $action->lay->set("isdetail", false);
    $action->lay->set("page", $page + 1);
    $action->lay->set("dirid", $dirid);
    $action->lay->set("SUBSEARCH", ($start > 0));
    $initpage = false;
    $action->parent->AddJsRef($action->GetParam("CORE_JSURL") . "/resizeimg.js");
    $orderby = "title";
    $dbaccess = $action->GetParam("FREEDOM_DB");
    if (!is_numeric($famid)) $famid = getFamIdFromName($dbaccess, $famid);

    if ($detailsearch) {
        $search=createTmpDoc($dbaccess,16);

        $search->setValue("se_famid",$famid);
        $search->setValue("se_latest","yes");
        $search->lay=$action->lay;
        $search->editdsearch();
    }
    $fdoc=new_doc($dbaccess,$famid);
    $nosearch = false;
    if (($keyword == "") && ($dirid == 0) && ($famid == 0)) {
        if ($initpage) {
            $action->lay = new Layout(getLayoutFile("FGSEARCH", "fullsearch_empty.xml"), $action);
            return;
        }
        $nosearch = true;
    }
    $action->lay->set("INITSEARCH", $nosearch);
    $kfams = array();
    $fkeyword = $keyword;
    if ($keyword != "") {
        // process family specification
        $kl = explode(":", $keyword);
        if (count($kl) > 1) {
            $keyword = $kl[1];
            $faml = $kl[0];
            $tf = explode(",", $faml);
            foreach ( $tf as $k => $v ) {
                if ($v == "") continue;
                $v = trim($v);
                if ($v[0] != "~") {
                    $b = true;
                    $n = $v;
                } else {
                    $b = false;
                    $n = substr($v, 1);
                }
                $kfams[] = array(
                    "include" => $b,
                    "kfam" => $n
                );
            }
        }
    }

    /* $bfam = array(); */
    $tclassdoc = GetClassesDoc($dbaccess, $action->user->id, array(
    1,
    2
    ), "TABLE");
    if (!$nosearch) {

        $sqlfilters = array();
        $famfilter = $or = $and = "";
        if (count($kfams) > 0) {
            $famid = 0;
            $tmpdoc = new Doc($dbaccess);
            foreach ( $kfams as $k => $v ) {
                foreach ( $tclassdoc as $kdoc => $cdoc ) {
                    if (strstr(strtolower($cdoc["title"]), $v["kfam"]) != false) {
                        if ($v["include"]) $or .= ($or != "" ? " OR " : "") . "(fromid" . ($v["include"] ? "=" : "!=") . $cdoc["initid"] . ")";
                        else $and .= ($and != "" ? " AND " : "") . "(fromid" . ($v["include"] ? "=" : "!=") . $cdoc["initid"] . ")";
                    }
                }
            }
            if ($or != "") $famfilter = "($or)";
            if ($and != "") $famfilter .= ($famfilter != "" ? " AND " : "") . " ($and)";
        }

        if ($keyword != "") {
            if ($keyword[0] == '~') {
                $sqlfilters[] = "svalues ~* '" . pg_escape_string(substr($keyword, 1)) . "'";
            } else {
                DocSearch::getFullSqlFilters($keyword, $sqlfilters, $orderby, $keys);
            }
        } else {
            $sdoc = new_doc($dbaccess, $dirid);
            $tkeys = $sdoc->getTValue("se_keys");
            foreach ( $tkeys as $k => $v )
            if (!$v) unset($tkeys[$k]);
            $keys = implode('|', $tkeys);
        }
        if ($famfilter != "") $sqlfilters[] = $famfilter;

        $s = new SearchDoc($dbaccess, $famid);
        if ($dirid) {
            $s->useCollection($dirid);
            $vardids="did_$dirid";
        } else  {
            $vardids="did_$famid$keys";
            foreach ( $sqlfilters as $filter ) {
                $s->addFilter($filter);
            }
        }
        $displayedIds=array();
        if ($start > 0) {
            $displayedIds=$action->read($vardids);
            if ($displayedIds && count($displayedIds)>0) {
                $sqlExclude=sprintf("initid not in (%s)",implode(",", $displayedIds));
                $s->addFilter($sqlExclude);
            } else {
                $s->setStart($start);
            }
        }
        $s->setOrder($orderby . ', id desc');
        $s->setSlice($slice + 1);
        $s->excludeConfidential();

        $tdocs = $s->search();
        if ($s->getError()) addLogMsg($s->getSearchInfo());
        //print_r2($s->getSearchInfo());
        if ($start == 0) {
            if ($s->count() < ($slice + 1)) $globalCount = $s->count();
            else {
                $sc = new SearchDoc($dbaccess, $famid);
                if ($dirid) {
                    $sc->useCollection($dirid);
                } else {
                    foreach ( $sqlfilters as $filter ) $sc->addFilter($filter);
                }
                $sc->excludeConfidential();
                $globalCount = $sc->onlyCount();
            }
        }

        $workdoc = new Doc($dbaccess);
        if ($famid) $famtitle = $workdoc->getTitle($famid);
        else $famtitle = "";
        $dbid = getDbid($dbaccess);
        if ($s->count() == ($slice + 1)) {
            array_pop($tdocs);
            $action->lay->set("notthenend", true);
        } else {
            $action->lay->set("notthenend", false);
        }

        $action->lay->set("notfirst", ($start != 0));
        $action->lay->set("theFollowingText", _("View next results"));
        $c=0;
        foreach ( $tdocs as $k => $tdoc ) {
            if ($tdoc["confidential"]) {
                if (($tdoc["profid"] > 0) && ($workdoc->controlId($tdoc["profid"], "confidential") != "")) {
                    unset($tdocs[$k]);
                    continue;
                }
            }
            $displayedIds[]=$tdoc["initid"];
            $c++;
            $tdocs[$k]["number"]=$c+$start;
            $tdoc["values"] .= getFileTxt($dbid, $tdoc);
            $tdocs[$k]["htext"] = nl2br(str_replace(array('[b]', '[/b]', ),
            array(  '<b>', '</b>'), (str_replace("<", "&lt;", preg_replace("/<\\/?(\\w+[^:]?|\\w+\\s.*?)>/", "",
            str_replace(array(
                '<b>',
                '</b>'
                ), array(
                '[b]',
                '[/b]'
                ), nl2br(wordwrap(nobr(highlight_text($dbid, $tdoc["values"], $keys), 80)))))))));
                $tdocs[$k]["iconsrc"] = $workdoc->getIcon($tdoc["icon"]);
                $tdocs[$k]["mdate"] = strftime("%a %d %b %Y", $tdoc["revdate"]);
        }
        $action->register($vardids, $displayedIds);

        if ($start > 0) {
            for($i = 0; $i < $start; $i += $slice) {
                $tpages[] = array(
                    "xpage" => $i / $slice + 1,
                    "xstart" => $i
                );
            }

            $action->lay->setBlockData("PAGES", $tpages);
        }

        $action->lay->setBlockData("DOCS", $tdocs);

        $action->lay->set("dirid", $dirid);
        if ($dirid != 0) {
            $sdoc = new_doc($dbaccess, $dirid);
            if ($sdoc->isAffected()) {
                $action->lay->set("isdetail", false);
                $action->lay->set("searchtitle", $sdoc->title);
                $action->lay->set("dirid", $sdoc->id);
            }
        }
    } else {
        $action->lay->set("cpage", "0");
        $action->lay->set("notfirst", false);
        $action->lay->set("notthenend", false);
    }
    $action->lay->set("famid", $famid);
    $action->lay->set("searchtitle", sprintf(_("Search %s"), $keyword));
    if ($fkeyword == "") $action->lay->set("key", _("search dynacase documents"));
    else $action->lay->set("key", str_replace("\"", "&quot;", $fkeyword));

    $famsuffix = ($famid==0?"":sprintf("<span class=\"families\">(%s %s)</span>",_("family search result"),$famtitle));
    if ($globalCount == 0) {
        $action->lay->set("resulttext", sprintf(_("No document found for <b>%s</b>%s"), $keyword, $famsuffix));
    } else if ($globalCount == 1) {
        $action->lay->set("resulttext", sprintf(_("One document for <b>%s</b>%s"), $keyword, $famsuffix));
    } else {
        $action->lay->set("resulttext", sprintf(_("Found <b>%d</b>  Result for <b>%s</b>%s"), $globalCount, $keyword, $famsuffix));
    }
    $action->lay->set("displayBottomBar", ($globalCount==0?false:true));
    $action->lay->set("displayTopBar", ($page == 0));


    foreach ( $tclassdoc as $k => $cdoc ) {
        $selectclass[$k]["idcdoc"] = $cdoc["initid"];
        $selectclass[$k]["classname"] = $cdoc["title"];
        $selectclass[$k]["famselect"] = ($cdoc["initid"] == $famid) ? "selected" : "";
    }
    $action->lay->SetBlockData("SELECTCLASS", $selectclass);
    $action->lay->set("searchdate", Doc::getDate(0,0,0,0,true));

}

/**
 * return file text values from  _txt column
 */
function getFileTxt($dbid, &$tdoc)
{

    $sqlselect = 'svalues';
    $sqlfrom = 'doc' . $tdoc["fromid"];
    $sqlwhere = 'id=' . $tdoc["id"];

    $result = pg_query($dbid, "select $sqlselect from $sqlfrom where $sqlwhere ;");
    if (pg_numrows($result) > 0) {
        $arr = pg_fetch_array($result, 0, PGSQL_ASSOC);
        return implode(' - ', $arr);
    }

}
function strtr8($s, $c1, $c2)
{
    $s9 = utf8_decode($s);
    $s9 = strtr($s9, utf8_decode($c1), utf8_decode($c2));
    return utf8_encode($s9);
}
/**
 * return part of text where are found keywords
 * Due to unaccent fulltext vectorisation need to transpose original text with highlight text done by headline tsearch2 sql function
 * @param resource $dbid database access
 * @param string $s original text
 * @param string $k keywords
 * @return string HTML text with <b> tags
 */
function highlight_text($dbid, &$s, $k)
{
    if ($k == "") {
        $h = str_replace('£', ' - ', substr($s, 0, 100));
        $pos1 = mb_strpos($h, ' ');
        $pos2 = mb_strrpos($h, ' ');
        $headline = substr($h, $pos1, ($pos2 - $pos1));
    } else if ((strlen($s) / 1024) > getParam("FULLTEXT_HIGHTLIGHTSIZE", 200)) {
        $headline = sprintf(_("document too big (%dKo): no highlight"), (strlen($s) / 1024));
    } else {
        $s = strtr8($s, "£", " ");
        $result = pg_query($dbid, "select ts_headline('french','" . pg_escape_string(unaccent($s)) . "',to_tsquery('french','$k'))");
        if (pg_numrows($result) > 0) {
            $arr = pg_fetch_array($result, 0, PGSQL_ASSOC);
            $headline = $arr["ts_headline"];
        }

        // $headline=str_replace('  ',' ',$headline);
        $headline = preg_replace('/[ ]+ /', ' ', $headline);
        $headline = str_replace(array(
            " \r",
            "\n "
            ), array(
            '',
            "\n"
            ), $headline);
            $pos = mb_strpos($headline, '<b>');

            //    print "<hr> POSBEG:".$pos;
            if ($pos !== false) {
                $sw = (str_replace(array(
                "<b>",
                "</b>"
                ), array(
                '',
                ''
                ), $headline));
                $s = preg_replace('/[ ]+ /', ' ', $s);
                $s = preg_replace('/<[a-z][^>]+>/', '', $s);
                $s = str_replace(array(
                "<br />",
                "\r"
                ), array(
                '',
                ''
                ), $s);
                $offset = mb_strpos($s, $sw);

                if ($offset === false) return $headline; // case mismatch in characters


                $before = 20; // 20 characters before;
                if (($pos + $offset) < $before) $p0 = 0;
                else $p0 = $pos + $offset - $before;
                $h = mb_substr($s, $p0, $pos + $offset - $p0); // begin of text
                $possp = mb_strpos($h, ' ');
                if ($possp > 0) $h = mb_substr($h, $possp); // first word


                $pe = mb_strpos($headline, '</b>', $pos);
                if ($pe > 0) {
                    $h .= "<b>";
                    $h .= mb_substr($s, $pos + $offset, $pe - $pos - 3);
                    $h .= "</b>";
                }
                //      print "<br> POS:$pos [ $pos : $pe ]";
                $pos = $pe + 1;
                $i = 1;
                // 7 is strlen('<b></b>');


                while ( $pe > 0 ) {
                    $pb = mb_strpos($headline, '<b>', $pos);
                    $pe = mb_strpos($headline, '</b>', $pos);
                    //	print "<br> POS:$pos [ $pb : $pe ]";
                    if (($pe) && ($pb < $pe)) {
                        $pb--;
                        $pe; //
                        $h .= mb_substr($s, $pos - 4 - (7 * ($i - 1)) + $offset, $pb - $pos - 3);
                        $h .= "<b>";
                        $h .= mb_substr($s, $pb - (7 * $i) + $offset, $pe - $pb - 3);
                        $h .= "</b>";
                        $pos = $pe + 1;
                        $i++;
                    } else {
                        $cur = $pos - (7 * $i) + 3 + $offset;
                        if (($cur - $offset) > 150) $pend = 30;
                        else $pend = 180 - $cur + $offset;
                        $send = mb_substr($s, $cur, $pend);
                        $possp = mb_strrpos($send, ' ');
                        $send = mb_substr($send, 0, $possp);
                        $pe = 0;
                        $h .= $send;

                        //  print "<br> POSEND: $cur $pend";
                    }

                }
                //print "<br>[$headline]";
                return $h;

            }

    }
    return $headline;
}
function nobr($text)
{
    return strtr8(preg_replace('/<br\\s*?\/??>/i', '', $text), "\n\t£", "  -");
}


?>
