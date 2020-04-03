<?php
// -*-php-*-
rcs_id('$Id: CreateToc.php,v 1.36 2007/07/19 12:41:25 labbenes Exp $');
/*
 Copyright 2004,2005 $ThePhpWikiProgrammingTeam

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
 *
 * Known problems:
 * - MacIE will not work with jshide.
 * - it will crash with old markup and Apache2 (?)
 * - Certain corner-edges will not work with TOC_FULL_SYNTAX.
 *   I believe I fixed all of them now, but who knows?
 * - bug #969495 "existing labels not honored" seems to be fixed.
 */

if (!defined('TOC_FULL_SYNTAX')) {
    define('TOC_FULL_SYNTAX', 1);
}

class WikiPlugin_CreateToc extends WikiPlugin
{
    public function getName()
    {
        return _("CreateToc");
    }

    public function getDescription()
    {
        return _("Automatically link headers at the top");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.36 $"
        );
    }

    public function getDefaultArguments()
    {
        return array( 'pagename'  => '[pagename]', // TOC of another page here?
                      // or headers=1,2,3 is also possible.
                      'headers'   => "!!!,!!,!",   // "!!!"=>h1, "!!"=>h2, "!"=>h3
                      'noheader'  => 0,            // omit <h1>Table of Contents</h1>
                      'position'  => 'right',      // or left
                      'with_toclink' => 0,         // link back to TOC
                      'jshide'    => 0,            // collapsed TOC as DHTML button
              'extracollapse' => 1,        // provide an entry +/- link to collapse
                      'liststyle' => 'dl',         // 'dl' or 'ul' or 'ol'
                      'indentstr' => '&nbsp;&nbsp;&nbsp;&nbsp;',
              'with_counter' => 1,
                      );
    }
    // Initialisation of toc counter
    public function _initTocCounter()
    {
        $counter = array(1 => 0, 2 => 0, 3 => 0);
        return $counter;
    }

    // Update toc counter with a new title
    public function _tocCounter(&$counter, $level)
    {
        $counter[$level]++;
        $level--;
        for ($i = $level; $i > 0; $i--) {
            $counter[$i] = 0;
        }
    }

    // Get string corresponding to the current title
    public function _getCounter(&$counter, $level)
    {
        $str = $counter[3];
        for ($i = 2; $i > 0; $i--) {
            if ($counter[$i] != 0) {
                $str .= '.' . $counter[$i];
            }
        }
        return $str;
    }

    public function preg_quote($heading)
    {
        return str_replace(
            array("/",".","?","*"),
            array('\/','\.','\?','\*'),
            $heading
        );
    }

    // Get HTML header corresponding to current level (level is set of !)
    public function _getHeader($level)
    {
        $count = substr_count($level, '!');
        switch ($count) {
            case 1:
                $h = "h4";
                break;
            case 2:
                $h = "h3";
                break;
            case 3:
                $h = "h2";
                break;
        }
        return $h;
    }

    public function _quote($heading)
    {
        if (TOC_FULL_SYNTAX) {
            $theading = TransformInline($heading);
            if ($theading) {
                return preg_quote($theading->asXML(), "/");
            } else {
                return XmlContent::_quote(preg_quote($heading, "/"));
            }
        } else {
            return XmlContent::_quote(preg_quote($heading, "/"));
        }
    }

    /*
     * @param $hstart id (in $content) of heading start
     * @param $hend   id (in $content) of heading end
     */
    public function searchHeader(
        $content,
        $start_index,
        $heading,
        $level,
        &$hstart,
        &$hend,
        $basepage = false
    ) {
        $hstart = 0;
        $hend = 0;
        $h = $this->_getHeader($level);
        $qheading = $this->_quote($heading);
        for ($j = $start_index; $j < count($content); $j++) {
            if (is_string($content[$j])) {
                if (
                    preg_match(
                        "/<$h>$qheading<\/$h>/",
                        $content[$j]
                    )
                ) {
                    return $j;
                }
            } elseif (isa($content[$j], 'cached_link')) {
                if (method_exists($content[$j], 'asXML')) {
                    $content[$j]->_basepage = $basepage;
                    $content[$j] = $content[$j]->asXML();
                } else {
                    $content[$j] = $content[$j]->asString();
                }
        // shortcut for single wikiword or link headers
                if (
                    $content[$j] == $heading
                    and substr($content[$j - 1], -4, 4) == "<$h>"
                    and substr($content[$j + 1], 0, 5) == "</$h>"
                ) {
                    $hstart = $j - 1;
                    $hend = $j + 1;
                    return $j; // single wikiword
                } elseif (TOC_FULL_SYNTAX) {
                    //DONE: To allow "!! WikiWord link" or !! http://anylink/
                    // Check against joined content (after cached_plugininvocation).
                    // The first link is the anchor then.
                    if (preg_match("/<$h>(?!.*<\/$h>)/", $content[$j - 1])) {
                        $hstart = $j - 1;
                        $joined = '';
                        for ($k = max($j - 1, $start_index); $k < count($content); $k++) {
                            if (is_string($content[$k])) {
                                $joined .= $content[$k];
                            } elseif (method_exists($content[$k], 'asXML')) {
                                $joined .= $content[$k]->asXML();
                            } else {
                                $joined .= $content[$k]->asString();
                            }
                            if (preg_match("/<$h>$qheading<\/$h>/", $joined)) {
                                $hend = $k;
                                return $k;
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
    public function _nextAnchor($s)
    {
        static $anchors = array();

        $s = str_replace(' ', '_', $s);
        $i = 1;
        $anchor = $s;
        while (!empty($anchors[$anchor])) {
            $anchor = sprintf("%s_%d", $s, $i++);
        }
        $anchors[$anchor] = $i;
        return $anchor;
    }

    // Feature request: proper nesting; multiple levels (e.g. 1,3)
    public function extractHeaders(
        &$content,
        &$markup,
        $backlink = 0,
        $counter = 0,
        $levels = false,
        $basepage = ''
    ) {
        if (!$levels) {
            $levels = array(1,2);
        }
        $tocCounter = $this->_initTocCounter();
        reset($levels);
        sort($levels);
        $headers = array();
        $j = 0;
        for ($i = 0; $i < count($content); $i++) {
            foreach ($levels as $level) {
                if ($level < 1 or $level > 3) {
                    continue;
                }
                if (
                    preg_match(
                        '/^\s*(!{' . $level . ',' . $level . '})([^!].*)$/',
                        $content[$i],
                        $match
                    )
                ) {
                    $this->_tocCounter($tocCounter, $level);
                    if (!strstr($content[$i], '#[')) {
                        $s = trim($match[2]);

                        // Remove escape char from toc titles.
                        $s = str_replace('~', '', $s);

                        $anchor = $this->_nextAnchor($s);
                        $manchor = MangleXmlIdentifier($anchor);
                        $texts = $s;
                        if ($counter) {
                            $texts = $this->_getCounter($tocCounter, $level) . ' - ' . $s;
                        }
                        $headers[] = array('text' => $texts,
                                           'anchor' => $anchor,
                                           'level' => $level);
                        // Change original wikitext, but that is useless art...
                        $content[$i] = $match[1] . " #[|$manchor][$s|#TOC]";
                        // And now change the to be printed markup (XmlTree):
                        // Search <hn>$s</hn> line in markup
                        /* Url for backlink */
                        $url = WikiURL(new WikiPageName($basepage, false, "TOC"));
                        $j = $this->searchHeader(
                            $markup->_content,
                            $j,
                            $s,
                            $match[1],
                            $hstart,
                            $hend,
                            $markup->_basepage
                        );
                        if ($j and isset($markup->_content[$j])) {
                            $x = $markup->_content[$j];
                            $qheading = $this->_quote($s);
                            if ($counter) {
                                $counterString = $this->_getCounter($tocCounter, $level);
                            }
                            if (($hstart === 0) && is_string($markup->_content[$j])) {
                                if ($backlink) {
                                    if ($counter) {
                                        $anchorString = "<a href=\"$url\" name=\"$manchor\">$counterString</a> - \$2";
                                    } else {
                                        $anchorString = "<a href=\"$url\" name=\"$manchor\">\$2</a>";
                                    }
                                } else {
                                    $anchorString = "<a name=\"$manchor\"></a>";
                                    if ($counter) {
                                        $anchorString .= "$counterString - ";
                                    }
                                }
                                if (
                                    $x = preg_replace(
                                        '/(<h\d>)(' . $qheading . ')(<\/h\d>)/',
                                        "\$1$anchorString\$2\$3",
                                        $x,
                                        1
                                    )
                                ) {
                                    if ($backlink) {
                                        $x = preg_replace(
                                            '/(<h\d>)(' . $qheading . ')(<\/h\d>)/',
                                            "\$1$anchorString\$3",
                                            $markup->_content[$j],
                                            1
                                        );
                                    }
                                    $markup->_content[$j] = $x;
                                }
                            } else {
                                $x = $markup->_content[$hstart];
                                $h = $this->_getHeader($match[1]);

                                if ($backlink) {
                                    if ($counter) {
                                        $anchorString = "\$1<a href=\"$url\" name=\"$manchor\">$counterString</a> - ";
                                    } else {
                                        /* Not possible to make a backlink on a
                                         * title with a WikiWord */
                                        $anchorString = "\$1<a name=\"$manchor\"></a>";
                                    }
                                } else {
                                    $anchorString = "\$1<a name=\"$manchor\"></a>";
                                    if ($counter) {
                                        $anchorString .= "$counterString - ";
                                    }
                                }
                                $x = preg_replace(
                                    "/(<$h>)(?!.*<\/$h>)/",
                                    $anchorString,
                                    $x,
                                    1
                                );
                                if ($backlink) {
                                    $x =  preg_replace(
                                        "/(<$h>)(?!.*<\/$h>)/",
                                        $anchorString,
                                        $markup->_content[$hstart],
                                        1
                                    );
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

    public function run($dbi, $argstr, &$request, $basepage)
    {
        global $WikiTheme;
        extract($this->getArgs($argstr, $request));
        if ($pagename) {
            // Expand relative page names.
            $page = new WikiPageName($pagename, $basepage);
            $pagename = $page->name;
        }
        if (!$pagename) {
            return $this->error(_("no page specified"));
        }
        if ($jshide and isBrowserIE() and browserDetect("Mac")) {
            //trigger_error(_("jshide set to 0 on Mac IE"), E_USER_NOTICE);
            $jshide = 0;
        }
        $page = $dbi->getPage($pagename);
        $current = $page->getCurrentRevision();
        //FIXME: I suspect this only to crash with Apache2
        if (!$current->get('markup') or $current->get('markup') < 2) {
            if (in_array(php_sapi_name(), array('apache2handler','apache2filter'))) {
                trigger_error(_("CreateToc disabled for old markup"), E_USER_WARNING);
                return '';
            }
        }
        $content = $current->getContent();
        $html = HTML::div(array('class' => 'toc', 'id' => 'toc'));
        /*if ($liststyle == 'dl')
            $list = HTML::dl(array('id'=>'toclist','class' => 'toc'));
        elseif ($liststyle == 'ul')
            $list = HTML::ul(array('id'=>'toclist','class' => 'toc'));
        elseif ($liststyle == 'ol')
            $list = HTML::ol(array('id'=>'toclist','class' => 'toc'));
            */
        $list = HTML::ul(array('id' => 'toclist','class' => 'toc'));

        if (!strstr($headers, ",")) {
            $headers = array($headers);
        } else {
            $headers = explode(",", $headers);
        }
        $levels = array();
        foreach ($headers as $h) {
            //replace !!! with level 1, ...
            if (strstr($h, "!")) {
                $hcount = substr_count($h, '!');
                $level = min(max(1, $hcount), 3);
                $levels[] = $level;
            } else {
                $level = min(max(1, (int) $h), 3);
                $levels[] = $level;
            }
        }
        if (TOC_FULL_SYNTAX) {
            require_once("lib/InlineParser.php");
        }
        if (
            $headers = $this->extractHeaders(
                $content,
                $dbi->_markup,
                $with_toclink,
                $with_counter,
                $levels,
                $basepage
            )
        ) {
            $container     = $list;
            $levelRefs     = array();
            $previousLevel = 3;
            foreach ($headers as $k => $h) {
                if ($h['level'] < $previousLevel) {
                    // h2 -> h3 (level 3 -> level 2)

                    // Keep track of previous points
                    $levelRefs[$previousLevel] = $container;

                    // Create new container
                    $ul = HTML::ul();
                    $container->pushContent($ul);
                    $container = $ul;
                } elseif ($h['level'] > $previousLevel) {
                    // h4 -> h3 (level 1 -> level 2)
                    if (isset($levelRefs[$h['level']])) {
                        $container = $levelRefs[$h['level']];
                    }
                }

                $h    = $headers[$k];
                $link = new WikiPageName($pagename, $page, $h['anchor']);
                $li   = WikiLink($link, 'known', $h['text']);
                $container->pushContent(HTML::li($li));

                $previousLevel = $h['level'];
            }
        }
        $list->setAttr('style', 'display:' . ($jshide ? 'none;' : 'block;'));
        $open = DATA_PATH . '/' . $WikiTheme->_findFile("images/folderArrowOpen.png");
        $close = DATA_PATH . '/' . $WikiTheme->_findFile("images/folderArrowClosed.png");
        $html->pushContent(Javascript("
function toggletoc(a) {
  var toc=document.getElementById('toclist')
  //toctoggle=document.getElementById('toctoggle')
  var open='" . $open . "'
  var close='" . $close . "'
  if (toc.style.display=='none') {
    toc.style.display='block'
    a.title='" . _("Click to hide the TOC") . "'
    a.src = open
  } else {
    toc.style.display='none';
    a.title='" . _("Click to display") . "'
    a.src = close
  }
}"));
        if ($extracollapse) {
            $toclink = HTML(
                _("Table Of Contents"),
                " ",
                HTML::a(array('name' => 'TOC')),
                HTML::img(array(
                                            'id' => 'toctoggle',
                                            'class' => 'wikiaction',
                                            'title' => _("Click to display to TOC"),
                                            'onClick' => "toggletoc(this)",
                                            'height' => 15,
                                            'width' => 15,
                                            'border' => 0,
                'src' => $jshide ? $close : $open ))
            );
        } else {
            $toclink = HTML::a(
                array('name' => 'TOC',
                     'class' => 'wikiaction',
                     'title' => _("Click to display"),
                     'onclick' => "toggletoc(this)"),
                _("Table Of Contents"),
                HTML::span(
                    array('style' => 'display:none',
                    'id' => 'toctoggle'),
                    " "
                )
            );
        }
        $html->pushContent(HTML::h4($toclink));
        $html->pushContent($list);
        return $html;
    }
}

// $Log: CreateToc.php,v $
// Revision 1.36  2007/07/19 12:41:25  labbenes
// Correct TOC numbering. It should start from '1' not from '1.1'.
//
// Revision 1.35  2007/02/17 14:17:48  rurban
// declare vars for IE6
//
// Revision 1.34  2007/01/28 22:47:06  rurban
// fix # back link
//
// Revision 1.33  2007/01/28 22:37:04  rurban
// beautify +/- collapse icon
//
// Revision 1.32  2007/01/20 11:25:30  rurban
// remove align
//
// Revision 1.31  2007/01/09 12:35:05  rurban
// Change align to position. Add extracollapse. js now always active, jshide just denotes the initial state.
//
// Revision 1.30  2006/12/22 17:49:38  rurban
// fix quoting
//
// Revision 1.29  2006/04/15 12:26:54  rurban
// need basepage for subpages like /Remove (within CreateTOC)
//
// Revision 1.28  2005/10/12 06:15:25  rurban
// just aesthetics
//
// Revision 1.27  2005/10/10 19:50:45  rurban
// fix the missing formatting problems, add with_counter arg by ?? (20050106), Thanks to ManuelVacelet for the testcase
//
// Revision 1.26  2004/09/20 14:07:16  rurban
// fix Constant toc_full_syntax already defined warning
//
// Revision 1.25  2004/07/08 20:30:07  rurban
// plugin->run consistency: request as reference, added basepage.
// encountered strange bug in AllPages (and the test) which destroys ->_dbi
//
// Revision 1.24  2004/06/28 13:27:03  rurban
// CreateToc disabled for old markup and Apache2 only
//
// Revision 1.23  2004/06/28 13:13:58  rurban
// CreateToc disabled for old markup
//
// Revision 1.22  2004/06/15 14:56:37  rurban
// more allow_call_time_pass_reference false fixes
//
// Revision 1.21  2004/06/13 09:45:23  rurban
// display bug workaround for MacIE browsers, jshide: 0
//
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
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
