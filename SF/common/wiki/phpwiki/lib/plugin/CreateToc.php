<?php // -*-php-*-
rcs_id('$Id: CreateToc.php 2691 2006-03-02 15:31:51Z guerin $');
/*
 Copyright 2004 $ThePhpWikiProgrammingTeam

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * CreateToc:  Automatically link to headers
 *
 * Usage:   
 *  <?plugin CreateToc headers=!!!,!! with_toclink||=1 
 *                     jshide||=1 ?>
 * @author:  Reini Urban
 */

define('TOC_FULL_SYNTAX',1);

class WikiPlugin_CreateToc
extends WikiPlugin
{
    function getName() {
        return _("CreateToc");
    }

    function getDescription() {
        return _("Automatically link headers at the top");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 2691 $");
    }

    function getDefaultArguments() {
        return array( 'pagename'  => '[pagename]', // not sure yet. TOC of another page here?
                      // or headers=1,2,3 is also possible.
                      'headers'   => "!!!,!!,!",   // "!!!" => h1, "!!" => h2, "!" => h3
                      'noheader'  => 0,            // omit <h1>Table of Contents</h1>
                      'align'     => 'left',
                      'with_toclink' => 0,         // link back to TOC
                      'jshide'    => 0,            // collapsed TOC as DHTML button
                      'liststyle' => 'dl',         // or 'ul' or 'ol'
                      'indentstr' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
                      'with_counter'   => 1,
                      );
    }

    
    // Initialisation of toc counter
    function _initTocCounter() {
        $counter=array(1=>0, 2=>0, 3=>0);
        return $counter;
    }

    // Update toc counter with a new title
    function _tocCounter(&$counter, $level) {
        $counter[$level]++;
        $level--;
        for($i = $level; $i >= 0; $i--) {
            $counter[$i] = 0;
        }
    }

    // Get string corresponding to the current title
    function _getCounter(&$counter, $level) {
        $str=$counter[3];
        for($i = 2; $i > 0; $i--) {
            if($counter[$i] != 0)
                $str .= '.'.$counter[$i];
        }
        return $str;
    }

    function preg_quote ($heading) {
        return str_replace(array("/",".","?","*"),
    		           array('\/','\.','\?','\*'), $heading);
    }
    
    // Get HTML header corresponding to current level (level is set of !)
    function _getHeader($level) {
        $count = substr_count($level,'!');
    	switch ($count) {
    		case 1: $h = "h4"; break;
    		case 2: $h = "h3"; break;
    		case 3: $h = "h2"; break;
    	}
        return $h;
    }

    /*
     * @param $hstart id (in $content) of heading start
     * @param $hend   id (in $content) of heading end
     */
    function searchHeader ($content, $start_index, $heading, $level, &$hstart, &$hend) {
        $hstart=0;
        $hend=0;

    	$h = $this->_getHeader($level);

        /* Test if heading contain wiki word */
        $cWikiWord = false;
        $hexp = explode(" ", $heading);
        foreach($hexp as $hpart) {
            if(isWikiWord($hpart)) {
                $cWikiWord = true;
                break;
            }
        }

        if (defined('TOC_FULL_SYNTAX') and TOC_FULL_SYNTAX) {
            $theading = TransformInline($heading);
            $qheading = preg_quote($theading->asXml(), "/");
        } else {
            $qheading = preg_quote($heading, "/");
        }
    	for ($j=$start_index; $j < count($content); $j++) {
            if (is_string($content[$j])) {
    		if (preg_match("/<$h>$qheading<\/$h>/",$content[$j])) {
    		    return $j;
    		}
            } elseif ($cWikiWord) {
                if (isa($content[$j],'cached_wikilink')) {
                    // shortcut for single wikiword headers
                    $content[$j] = $content[$j]->asXml();
                    if ($content[$j] == $heading and 
                        substr($content[$j-1],-4,4) == "<$h>" and
                        substr($content[$j+1],0,5) == "</$h>") {
                        $hstart=$j-1;
                        $hend=$j+1;
                        return $j; // single wikiword
                    } elseif (defined('TOC_FULL_SYNTAX') and TOC_FULL_SYNTAX) {
                        //DONE: To allow "!! WikiWord link"
                        // Split heading into WikiWords and check against 
                        // joined content (after cached_plugininvocation).
                        // The first wikilink is the anchor then.
                        if(preg_match("/<$h>(?!.*<\/$h>)/", $content[$j-1])) {
                            $hstart = $j-1;
                            $joined = '';
                            for ($k=max($j-1,$start_index); $k < count($content); $k++) {
                                $joined .= is_string($content[$k]) ? $content[$k] 
                                    : $content[$k]->asXml();
                                if (preg_match("/<$h>$qheading<\/$h>/",$joined)) {
                                    $hend=$k;
                                    return $k;
                                }
                            }
                        }                    
                    }
                }
    	    }
    	}
    	trigger_error("Heading <$h> $heading </$h> not found\n", E_USER_NOTICE);
    	return 0;
    }

    /** prevent from duplicate anchors,
     *  beautify spaces: " " => "_" and not "x20."
     */
    function _nextAnchor($s) {
        static $anchors = array();

        $s = str_replace(' ','_',$s);
        $i = 1;
        $anchor = $s;
        while (!empty($anchors[$anchor])) {
            $anchor = sprintf("%s_%d",$s,$i++);
        }
        $anchors[$anchor] = $i;
        return $anchor;
    }

    // Feature request: proper nesting; multiple levels (e.g. 1,3)
    function extractHeaders (&$content, &$markup, $backlink=0, $counter=0, $levels=false, $basepage='') {
        if (!$levels) $levels = array(1,2);
        $tocCounter=$this->_initTocCounter();
        reset($levels);
        sort($levels);
        $headers = array();
        $j = 0;
        for ($i=0; $i<count($content); $i++) {
            foreach ($levels as $level) {
                if ($level < 1 or $level > 3) continue;
                if (preg_match('/^\s*(!{'.$level.','.$level.'})([^!].*)$/',$content[$i],$match)) {
                    $this->_tocCounter($tocCounter, $level);
                    if (!strstr($content[$i],'#[')) {
                        $s = trim($match[2]);
                        $anchor = $this->_nextAnchor($s);
                        $manchor = MangleXmlIdentifier($anchor);
                        $theading = TransformInline($s);
                        $texts = $theading->asString();
                        if($counter) {
                            $texts = $this->_getCounter($tocCounter, $level).' '.$theading->asString(); 
                        }
                        $headers[] = array('text' => $texts, 'anchor' => $anchor, 'level' => $level);
                        // Change original wikitext, but that is useless art...
                        $content[$i] = $match[1]." #[|$manchor][$s|#TOC]";
                        // And now change the to be printed markup (XmlTree):
                        // Search <hn>$s</hn> line in markup

                        /* Url for backlink */
                        $url = WikiURL(new WikiPageName($basepage,false,"TOC"));

                        $j = $this->searchHeader($markup->_content, $j, $s, $match[1], $hstart, $hend);
                        if ($j and isset($markup->_content[$j])) {
                            $x = $markup->_content[$j];                            
                            
                            if (($hstart === 0) && is_string($markup->_content[$j])) {
                                $heading = preg_quote($theading->asXml(), "/");
                                
                                $counterString = $this->_getCounter($tocCounter, $level);                                
                                if($backlink) {
                                    if($counter) {
                                        $anchorString = "<a href=\"$url\" name=\"$manchor\">$counterString</a> - \$2";
                                    }
                                    else {
                                        $anchorString = "<a href=\"$url\" name=\"$manchor\">\$2</a>";
                                    }
                                }
                                else {
                                    if($counter) {
                                        $anchorString = "<a name=\"$manchor\"></a>$counterString - ";
                                    }
                                    else {
                                        $anchorString = "<a name=\"$manchor\"></a>";
                                    }
                                }

                                if ($x = preg_replace('/(<h\d>)('.$heading.')(<\/h\d>)/',
                                                      "\$1$anchorString\$2\$3",$x,1)) {
                                    if ($backlink) {                                        
                                        $x = preg_replace('/(<h\d>)('.$heading.')(<\/h\d>)/',
                                                          "\$1$anchorString\$3",
                                                          $markup->_content[$j],1);
                                    }
                                    $markup->_content[$j] = $x;
                                }
                            }
                            else {
                                $x = $markup->_content[$hstart];
                                $h = $this->_getHeader($match[1]);

                                $counterString = $this->_getCounter($tocCounter, $level);
                                if($backlink) {
                                    if($counter) {
                                        $anchorString = "\$1<a href=\"$url\" name=\"$manchor\">$counterString</a> - ";
                                    }
                                    else {
                                        /* Not possible to make a backlink on a
                                         * title with a WikiWord */
                                        $anchorString = "\$1<a name=\"$manchor\"></a>";
                                    }
                                }
                                else {
                                    if($counter) {
                                        $anchorString = "\$1<a name=\"$manchor\"></a>$counterString - ";
                                    }
                                    else {
                                        $anchorString = "\$1<a name=\"$manchor\"></a>";
                                    }
                                }
                                $x = preg_replace("/(<$h>)(?!.*<\/$h>)/", 
                                                  $anchorString, $x, 1);
                                if ($backlink) {
                                    $url = WikiURL(new WikiPageName($basepage,false,"TOC"));
                                    $x =  preg_replace("/(<$h>)(?!.*<\/$h>)/", 
                                                      $anchorString,
                                                      $markup->_content[$hstart],1);
                                }
                                $markup->_content[$hstart] = $x;                          
                            }
                        }
                    }
                }
            }
        }
        return $headers;
    }
                
    function run($dbi, $argstr, $request, $basepage) {
        extract($this->getArgs($argstr, $request));
        if ($pagename) {
            // Expand relative page names.
            $page = new WikiPageName($pagename, $basepage);
            $pagename = $page->name;
        }
        if (!$pagename) {
            return $this->error(_("no page specified"));
        }
        $page = $dbi->getPage($pagename);
        $current = $page->getCurrentRevision();
        $content = $current->getContent();
        $html = HTML::div(array('class' => 'toc','align' => $align));
        if ($liststyle == 'dl')
            $list = HTML::dl(array('name'=>'toclist','id'=>'toclist','class' => 'toc'));
        elseif ($liststyle == 'ul')
            $list = HTML::ul(array('name'=>'toclist','id'=>'toclist','class' => 'toc'));
        elseif ($liststyle == 'ol')
            $list = HTML::ol(array('name'=>'toclist','id'=>'toclist','class' => 'toc'));
        if (!strstr($headers,",")) {
            $headers = array($headers);	
        } else {
            $headers = explode(",",$headers);
        }
        $levels = array();
        foreach ($headers as $h) {
            //replace !!! with level 1, ...
            if (strstr($h,"!")) {
                $hcount = substr_count($h,'!');
                $level = min(max(1, $hcount),3);
                $levels[] = $level;
            } else {
                $level = min(max(1, (int) $h), 3);
                $levels[] = $level;
            }
        }
        if (defined('TOC_FULL_SYNTAX') and TOC_FULL_SYNTAX)
            require_once("lib/InlineParser.php");
        if ($headers = $this->extractHeaders(&$content, &$dbi->_markup, $with_toclink, $with_counter, $levels, $basepage)) {
            foreach ($headers as $h) {
                // proper heading indent
                $level = $h['level'];
                $indent = 3 - $level;
                $link = new WikiPageName($pagename,$page,$h['anchor']);
                $li = WikiLink($link,'known',$h['text']);
                if ($liststyle == 'dl')
                    $list->pushContent(HTML::dt(HTML::raw(str_repeat($indentstr,$indent)),$li));
                else
                    $list->pushContent(HTML::li(HTML::raw(str_repeat($indentstr,$indent)),$li));
            }
        }
        if ($jshide) {
            $list->setAttr('style','display:none;');
            $html->pushContent(Javascript("
function toggletoc(a) {
  toc=document.getElementById('toclist');
  if (toc.style.display=='none') {
    toc.style.display='block';
    a.title='"._("Click to hide the TOC")."';
  } else {
    toc.style.display='none';
    a.title='"._("Click to display")."';
  }
}"));
            $html->pushContent(HTML::h4(HTML::a(array('name'=>'TOC',
                                                      'class'=>'wikiaction',
                                                      'title'=>_("Click to display"),
                                                      'onclick'=>"toggletoc(this)"),
                                                _("Table Of Contents"))));
        } else {
            if (!$noheader)
                $html->pushContent(HTML::h2(HTML::a(array('name'=>'TOC'),_("Table Of Contents"))));
            else 
                $html->pushContent(HTML::a(array('name'=>'TOC'),""));
        }
        $html->pushContent($list);
        return $html;
    }
};

// $Log$
// Revision 1.20  2004/05/11 13:57:46  rurban
// enable TOC_FULL_SYNTAX per default
// don't <a name>$header</a> to disable css formatting for such anchors
//   => <a name></a>$header
//
// Revision 1.19  2004/05/08 16:59:27  rurban
// requires optional TOC_FULL_SYNTAX constnat to enable full link and
// wikiword syntax in headers.
//
// Revision 1.18  2004/04/29 21:55:15  rurban
// fixed TOC backlinks with USE_PATH_INFO false
//   with_toclink=1, sf.net bug #940682
//
// Revision 1.17  2004/04/26 19:43:03  rurban
// support most cases of header markup. fixed duplicate MangleXmlIdentifier name
//
// Revision 1.16  2004/04/26 14:46:14  rurban
// better comments
//
// Revision 1.14  2004/04/21 04:29:50  rurban
// write WikiURL consistently (not WikiUrl)
//
// Revision 1.12  2004/03/22 14:13:53  rurban
// fixed links to equal named headers
//
// Revision 1.11  2004/03/15 09:52:59  rurban
// jshide button: dynamic titles
//
// Revision 1.10  2004/03/14 20:30:21  rurban
// jshide button
//
// Revision 1.9  2004/03/09 19:24:20  rurban
// custom indentstr
// h2 toc header
//
// Revision 1.8  2004/03/09 19:05:12  rurban
// new liststyle arg. default: dl (no bullets)
//
// Revision 1.7  2004/03/09 11:51:54  rurban
// support jshide=1: DHTML button hide/unhide TOC
//
// Revision 1.6  2004/03/09 10:25:37  rurban
// slightly better formatted TOC indentation
//
// Revision 1.5  2004/03/09 08:57:10  rurban
// convert space to "_" instead of "x20." in anchors
// proper heading indent
// handle duplicate headers
// allow multiple headers like "!!!,!!" or "1,2"
//
// Revision 1.4  2004/03/02 18:21:29  rurban
// typo: ref=>href
//
// Revision 1.1  2004/03/01 18:10:28  rurban
// first version, without links, anchors and jscript folding
//
//

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
