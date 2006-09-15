<?php // -*-php-*-
rcs_id('$Id$');
/*
Copyright 1999,2000,2001,2002,2004 $ThePhpWikiProgrammingTeam

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

require_once('lib/TextSearchQuery.php');
require_once("lib/PageList.php");

/**
 * Simple case insensitive fulltext search
 * TODO: case-sensitivity argument, regex argument
 *
 * See https://sourceforge.net/tracker/index.php?func=detail&aid=927395&group_id=6121&atid=106121
 * Wordaround to let the dead locks occur somewhat later:
 * increased the memory limit of PHP4 from 8 MB to 32 MB
 * php.ini: memory_limit = 32 MB
 */
class WikiPlugin_FullTextSearch
extends WikiPlugin
{
    function getName() {
        return _("FullTextSearch");
    }

    function getDescription() {
        return _("Search the content of all pages in this wiki.");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision$");
    }

    function getDefaultArguments() {
        return array('s'        => false,
                     'case_exact' => false,  //not yet supported
                     'regex'    => false,    //not yet supported
                     'noheader' => false,
                     'exclude'  => false,  //comma-seperated list of glob
                     'limit'    => false,
                     'quiet'    => false); // be less verbose
    }

    function run($dbi, $argstr, &$request, $basepage) {

        $args = $this->getArgs($argstr, $request);
        if (empty($args['s']))
            return '';

        extract($args);

        $query = new TextSearchQuery($s, $case_exact, $regex);
        $pages = $dbi->fullSearch($query);
        $lines = array();
        $hilight_re = $query->getHighlightRegexp();
        $count = 0;
        $found = 0;

        if ($quiet) { // see how easy it is with PageList...
            $list = new PageList(false,$exclude,$args);
            while ($page = $pages->next() and (!$limit or ($count < $limit))) {
                $list->addPage( $page );
            }
            return $list;
        }

        // Todo: we should better define a new PageListDL class for dl/dt/dd lists
        // But the new column types must have a callback then. (showhits)
        // See e.g. WikiAdminSearchReplace for custom pagelist columns
        $list = HTML::dl();
        if (!$limit or !is_int($limit))
            $limit = 0;
        // expand all page wildcards to a list of pages which should be ignored
        if ($exclude) $exclude = explodePageList($exclude); 
        while ($page = $pages->next() and (!$limit or ($count < $limit))) {
            $name = $page->getName();
            if ($exclude and in_array($name,$exclude)) continue;
            $count++;
            $list->pushContent(HTML::dt(WikiLink($name)));
            if ($hilight_re)
                $list->pushContent($this->showhits($page, $hilight_re));
            unset($page);
        }
        if ($limit and $count >= $limit) //todo: pager link to list of next matches
            $list->pushContent(HTML::dd(fmt("only %d pages displayed",$limit)));
        if (!$list->getContent())
            $list->pushContent(HTML::dd(_("<no matches>")));

        if ($noheader)
            return $list;
        return HTML(HTML::p(fmt("Full text search results for '%s'", $s)),
                    $list);
    }

    function showhits($page, $hilight_re) {
        $current = $page->getCurrentRevision();
        $matches = preg_grep("/$hilight_re/i", $current->getContent());
        $html = array();
        foreach ($matches as $line) {
            $line = $this->highlight_line($line, $hilight_re);
            $html[] = HTML::dd(HTML::small(array('class' => 'search-context'),
                                           $line));
        }
        return $html;
    }

    function highlight_line ($line, $hilight_re) {
        while (preg_match("/^(.*?)($hilight_re)/i", $line, $m)) {
            $line = substr($line, strlen($m[0]));
            $html[] = $m[1];    // prematch
            $html[] = HTML::strong(array('class' => 'search-term'), $m[2]); // match
        }
        $html[] = $line;        // postmatch
        return $html;
    }
};

// $Log$
// Revision 1.21  2004/04/18 01:11:52  rurban
// more numeric pagename fixes.
// fixed action=upload with merge conflict warnings.
// charset changed from constant to global (dynamic utf-8 switching)
//
// Revision 1.20  2004/02/28 21:14:08  rurban
// generally more PHPDOC docs
//   see http://xarch.tu-graz.ac.at/home/rurban/phpwiki/xref/
// fxied WikiUserNew pref handling: empty theme not stored, save only
//   changed prefs, sql prefs improved, fixed password update,
//   removed REPLACE sql (dangerous)
// moved gettext init after the locale was guessed
// + some minor changes
//
// Revision 1.19  2004/02/26 04:27:39  rurban
// wrong limit notification
//
// Revision 1.18  2004/02/26 04:24:03  rurban
// simplify quiet handling by using PageList
//
// Revision 1.17  2004/02/26 04:03:39  rurban
// added quiet, limit and exclude to FullTextSearch,
// fixed explodePageList with previously unloaded PageList
//
// Revision 1.16  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.15  2003/01/18 21:41:01  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
