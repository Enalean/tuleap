<?php
// -*-php-*-
rcs_id('$Id: AllPages.php,v 1.36 2005/01/28 12:08:42 rurban Exp $');
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

require_once('lib/PageList.php');

/**
 * DONE: support author=[] (current user) and owner, creator
 * to be able to have pages:
 * AllPagesCreatedByMe, AllPagesOwnedByMe, AllPagesLastAuthoredByMe
 */
class WikiPlugin_AllPages extends WikiPlugin
{
    public function getName()
    {
        return _("AllPages");
    }

    public function getDescription()
    {
        return _("List all pages in this wiki.");
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
        return array_merge(
            PageList::supportedArgs(),
            array(
                   'noheader'      => false,
                   'include_empty' => false,
                   //'pages'         => false, // DONT, this would be ListPages then.
                   'info'          => '',
                   'debug'         => false
            )
        );
    }

    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor,markup or all
    // exclude arg allows multiple pagenames exclude=HomePage,RecentChanges
    // sortby: [+|-] pagename|mtime|hits

    // 2004-07-08 22:05:35 rurban: turned off &$request to prevent from strange bug below
    public function run($dbi, $argstr, $request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        $pages = false;
        // Todo: extend given _GET args
        if ($args['debug']) {
            $timer = new DebugTimer();
        }
        $caption = _("All pages in this wiki (%d total):");

        if (!empty($args['owner'])) {
            $pages = PageList::allPagesByOwner(
                $args['owner'],
                $args['include_empty'],
                $args['sortby'],
                $args['limit']
            );
            if ($args['owner']) {
                $caption = fmt(
                    "List of pages owned by [%s] (%d total):",
                    WikiLink(
                        $args['owner'] == '[]'
                                        ? $request->_user->getAuthenticatedId()
                                        : $args['owner'],
                        'if_known'
                    ),
                    count($pages)
                );
            }
        } elseif (!empty($args['author'])) {
            $pages = PageList::allPagesByAuthor(
                $args['author'],
                $args['include_empty'],
                $args['sortby'],
                $args['limit']
            );
            if ($args['author']) {
                $caption = fmt(
                    "List of pages last edited by [%s] (%d total):",
                    WikiLink(
                        $args['author'] == '[]'
                                        ? $request->_user->getAuthenticatedId()
                                        : $args['author'],
                        'if_known'
                    ),
                    count($pages)
                );
            }
        } elseif (!empty($args['creator'])) {
            $pages = PageList::allPagesByCreator(
                $args['creator'],
                $args['include_empty'],
                $args['sortby'],
                $args['limit']
            );
            if ($args['creator']) {
                $caption = fmt(
                    "List of pages created by [%s] (%d total):",
                    WikiLink(
                        $args['creator'] == '[]'
                                        ? $request->_user->getAuthenticatedId()
                                        : $args['creator'],
                        'if_known'
                    ),
                    count($pages)
                );
            }
        //} elseif ($pages) {
        //    $args['count'] = count($pages);
        } else {
            if (! $request->getArg('count')) {
                $args['count'] = $dbi->numPages($args['include_empty'], $args['exclude']);
            } else {
                $args['count'] = $request->getArg('count');
            }
        }
        if (empty($args['count']) and !empty($pages)) {
            $args['count'] = count($pages);
        }
        $pagelist = new PageList($args['info'], $args['exclude'], $args);
        if (!$args['noheader']) {
            $pagelist->setCaption($caption);
        }

        // deleted pages show up as version 0.
        if ($args['include_empty']) {
            $pagelist->_addColumn('version');
        }

        if ($pages !== false) {
            $pagelist->addPageList($pages);
        } else {
            $pagelist->addPages($dbi->getAllPages(
                $args['include_empty'],
                $args['sortby'],
                $args['limit']
            ));
        }
        if ($args['debug']) {
            return HTML(
                $pagelist,
                HTML::p(fmt("Elapsed time: %s s", $timer->getStats()))
            );
        } else {
            return $pagelist;
        }
    }

    public function getmicrotime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return (float) $usec + (float) $sec;
    }
}

// $Log: AllPages.php,v $
// Revision 1.36  2005/01/28 12:08:42  rurban
// dont print [] as user
//
// Revision 1.35  2004/12/06 19:50:04  rurban
// enable action=remove which is undoable and seeable in RecentChanges: ADODB ony for now.
// renamed delete_page to purge_page.
// enable action=edit&version=-1 to force creation of a new version.
// added BABYCART_PATH config
// fixed magiqc in adodb.inc.php
// and some more docs
//
// Revision 1.34  2004/11/23 15:17:19  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.33  2004/11/01 10:43:59  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.32  2004/10/05 17:00:04  rurban
// support paging for simple lists
// fix RatingDb sql backend.
// remove pages from AllPages (this is ListPages then)
//
// Revision 1.31  2004/09/17 14:25:45  rurban
// update comments
//
// Revision 1.30  2004/07/09 10:06:50  rurban
// Use backend specific sortby and sortable_columns method, to be able to
// select between native (Db backend) and custom (PageList) sorting.
// Fixed PageList::AddPageList (missed the first)
// Added the author/creator.. name to AllPagesBy...
//   display no pages if none matched.
// Improved dba and file sortby().
// Use &$request reference
//
// Revision 1.29  2004/07/08 21:32:36  rurban
// Prevent from more warnings, minor db and sort optimizations
//
// Revision 1.28  2004/07/08 20:30:07  rurban
// plugin->run consistency: request as reference, added basepage.
// encountered strange bug in AllPages (and the test) which destroys ->_dbi
//
// Revision 1.27  2004/07/08 17:31:43  rurban
// improve numPages for file (fixing AllPagesTest)
//
// Revision 1.26  2004/06/21 16:22:32  rurban
// add DEFAULT_DUMP_DIR and HTML_DUMP_DIR constants, for easier cmdline dumps,
// fixed dumping buttons locally (images/buttons/),
// support pages arg for dumphtml,
// optional directory arg for dumpserial + dumphtml,
// fix a AllPages warning,
// show dump warnings/errors on DEBUG,
// don't warn just ignore on wikilens pagelist columns, if not loaded.
// RateIt pagelist column is called "rating", not "ratingwidget" (Dan?)
//
// Revision 1.25  2004/06/14 11:31:38  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.24  2004/06/13 16:02:12  rurban
// empty list of pages if user=[] and not authenticated.
//
// Revision 1.23  2004/06/13 15:51:37  rurban
// Support pagelist filter for current author,owner,creator by []
//
// Revision 1.22  2004/06/13 15:33:20  rurban
// new support for arguments owner, author, creator in most relevant
// PageList plugins. in WikiAdmin* via preSelectS()
//
// Revision 1.21  2004/04/20 00:06:53  rurban
// paging support
//
// Revision 1.20  2004/02/22 23:20:33  rurban
// fixed DumpHtmlToDir,
// enhanced sortby handling in PageList
//   new button_heading th style (enabled),
// added sortby and limit support to the db backends and plugins
//   for paging support (<<prev, next>> links on long lists)
//
// Revision 1.19  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.18  2004/01/25 07:58:30  rurban
// PageList sortby support in PearDB and ADODB backends
//
// Revision 1.17  2003/02/27 20:10:30  dairiki
// Disable profiling output when DEBUG is defined but false.
//
// Revision 1.16  2003/02/21 04:08:26  dairiki
// New class DebugTimer in prepend.php to help report timing.
//
// Revision 1.15  2003/01/18 21:19:25  carstenklapp
// Code cleanup:
// Reformatting; added copyleft, getVersion, getDescription
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
