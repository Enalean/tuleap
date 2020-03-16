<?php
// -*-php-*-
rcs_id('$Id: TitleSearch.php,v 1.28 2005/09/10 21:33:08 rurban Exp $');
/**
 Copyright 1999,2000,2001,2002,2004,2005 $ThePhpWikiProgrammingTeam

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
require_once('lib/PageList.php');

/**
 * Display results of pagename search.
 * Provides no own input box, just <?plugin-form TitleSearch ?> is enough.
 * Fancier Inputforms can be made using WikiForm Rich, to support regex and case_exact args.
 *
 * If only one pages is found and auto_redirect is true, this page is displayed immediatly,
 * otherwise the found pagelist is displayed.
 * The workhorse TextSearchQuery converts the query string from google-style words
 * to the required DB backend expression.
 *   (word and word) OR word, -word, "two words"
 * regex=auto tries to detect simple glob-style wildcards and expressions,
 * like xx*, *xx, ^xx, xx$, ^word$.
 */
class WikiPlugin_TitleSearch extends WikiPlugin
{
    public function getName()
    {
        return _("TitleSearch");
    }

    public function getDescription()
    {
        return _("Search the titles of all pages in this wiki.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.28 $"
        );
    }

    public function getDefaultArguments()
    {
        return array_merge(
            PageList::supportedArgs(), // paging and more.
            array('s'             => false,
                   'auto_redirect' => false,
                   'noheader'      => false,
                   'exclude'       => false,
                   'info'          => false,
                   'case_exact'    => false,
                   'regex'            => 'auto',
                   'format'           => false,
            )
        );
    }
    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor
    // exclude arg allows multiple pagenames exclude=Php*,RecentChanges

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        if (empty($args['s'])) {
            return '';
        }

        $query = new TextSearchQuery($args['s'], $args['case_exact'], $args['regex']);
        $pages = $dbi->titleSearch($query, $args['sortby'], $args['limit'], $args['exclude']);

        $pagelist = new PageList($args['info'], $args['exclude'], $args);
        while ($page = $pages->next()) {
            $pagelist->addPage($page);
            $last_name = $page->getName();
        }
        if ($args['format'] == 'livesearch') {
            $request->discardOutput();
            $request->buffer_output(false);
            echo '<div class="LSRes">';
            echo $pagelist->asXml();
            echo '</div>';
            if (empty($WikiTheme->DUMP_MODE)) {
                unset($GLOBALS['ErrorManager']->_postponed_errors);
                $request->finish();
            }
        }
        // Provide an unknown WikiWord link to allow for page creation
        // when a search returns no results
        if (!$args['noheader']) {
            $s = $args['s'];
            if (!$pagelist->getTotal() and !$query->_regex) {
                $s = WikiLink($args['s'], 'auto');
            }
            $pagelist->setCaption(fmt("Title search results for '%s'", $s));
        }

        if ($args['auto_redirect'] && ($pagelist->getTotal() == 1)) {
            return HTML(
                $request->redirect(WikiURL($last_name, false, 'absurl'), false),
                $pagelist
            );
        }

        return $pagelist;
    }
}

// $Log: TitleSearch.php,v $
// Revision 1.28  2005/09/10 21:33:08  rurban
// support enhanced API
//
// Revision 1.27  2005/02/03 05:09:57  rurban
// livesearch.js support
//
// Revision 1.26  2004/11/27 14:39:05  rurban
// simpified regex search architecture:
//   no db specific node methods anymore,
//   new sql() method for each node
//   parallel to regexp() (which returns pcre)
//   regex types bitmasked (op's not yet)
// new regex=sql
// clarified WikiDB::quote() backend methods:
//   ->quote() adds surrounsing quotes
//   ->qstr() (new method) assumes strings and adds no quotes! (in contrast to ADODB)
//   pear and adodb have now unified quote methods for all generic queries.
//
// Revision 1.25  2004/11/26 18:39:02  rurban
// new regex search parser and SQL backends (90% complete, glob and pcre backends missing)
//
// Revision 1.24  2004/11/25 08:30:58  rurban
// dont extract args
//
// Revision 1.23  2004/11/23 15:17:19  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.22  2004/11/23 13:35:49  rurban
// add case_exact search
//
// Revision 1.21  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.20  2003/11/02 20:42:35  carstenklapp
// Allow for easy page creation when search returns no matches.
// Based on cuthbertcat's patch, SF#655090 2002-12-17.
//
// Revision 1.19  2003/03/07 02:50:16  dairiki
// Fixes for new javascript redirect.
//
// Revision 1.18  2003/02/21 04:16:51  dairiki
// Don't NORETURN from redirect.
//
// Revision 1.17  2003/01/18 22:08:01  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
