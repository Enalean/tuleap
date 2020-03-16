<?php
// -*-php-*-
rcs_id('$Id: UnfoldSubpages.php,v 1.21 2005/09/11 13:20:07 rurban Exp $');
/*
 Copyright 2002,2004,2005 $ThePhpWikiProgrammingTeam

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
 * UnfoldSubpages:  Lists the content of all SubPages of the current page.
 *   This is e.g. useful for the CalendarPlugin, to see all entries at once.
 *   Warning: Better don't use it with non-existant sections!
 *          The section extractor is currently quite unstable.
 * Usage:   <?plugin UnfoldSubpages sortby=-mtime words=50 maxpages=5 ?>
 * Author:  Reini Urban <rurban@x-ray.at>
 *
 * Todo: follow RedirectTo
 */

require_once("lib/PageList.php");
require_once("lib/TextSearchQuery.php");
require_once("lib/plugin/IncludePage.php");

class WikiPlugin_UnfoldSubpages extends WikiPlugin_IncludePage
{
    public function getName()
    {
        return _("UnfoldSubpages");
    }

    public function getDescription()
    {
        return _("Includes the content of all SubPages of the current page.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.22 $"
        );
    }

    public function getDefaultArguments()
    {
        return array_merge(
            PageList::supportedArgs(),
            array(
                   'pagename' => '[pagename]', // default: current page
                   //'header'  => '',  // expandable string
                   'quiet'   => false, // print no header
                   'sortby'   => '',    // [+|-]pagename, [+|-]mtime, [+|-]hits
                   'maxpages' => false, // maximum number of pages to include (== limit)
                   'smalltitle' => false, // if set, hide transclusion-title,
                               //  just have a small link at the start of
                            //  the page.
                   'words'   => false,     // maximum number of words
                                    //  per page to include
                   'lines'   => false,     // maximum number of lines
                                    //  per page to include
                   'bytes'   => false,     // maximum number of bytes
                                    //  per page to include
                   'sections' => false, // maximum number of sections per page to include
                   'section' => false,     // this named section per page only
                   'sectionhead' => false // when including a named
                               //  section show the heading
            )
        );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        static $included_pages = false;
        if (!$included_pages) {
            $included_pages = array($basepage);
        }

        $args = $this->getArgs($argstr, $request);
        extract($args);
        $query = new TextSearchQuery($pagename . SUBPAGE_SEPARATOR . '*', true, 'glob');
        $subpages = $dbi->titleSearch($query, $sortby, $limit, $exclude);
        //if ($sortby)
        //    $subpages = $subpages->applyFilters(array('sortby' => $sortby, 'limit' => $limit, 'exclude' => $exclude));
        //$subpages = explodePageList($pagename . SUBPAGE_SEPARATOR . '*', false,
        //                            $sortby, $limit, $exclude);
        if (is_string($exclude) and !is_array($exclude)) {
            $exclude = PageList::explodePageList($exclude, false, false, $limit);
        }
        $content = HTML();

        include_once('lib/BlockParser.php');
        $i = 0;
        while ($page = $subpages->next()) {
            $cpagename = $page->getName();
            if ($maxpages and ($i++ > $maxpages)) {
                return $content;
            }
            if (in_array($cpagename, $exclude)) {
                continue;
            }
            // A page cannot include itself. Avoid doublettes.
            if (in_array($cpagename, $included_pages)) {
                $content->pushContent(HTML::p(sprintf(
                    _("recursive inclusion of page %s ignored"),
                    $cpagename
                )));
                continue;
            }
            // trap any remaining nonexistant subpages
            if ($page->exists()) {
                $r = $page->getCurrentRevision();
                $c = $r->getContent();   // array of lines
                // follow redirects
                if (preg_match(
                    '/<' . '\?plugin\s+RedirectTo\s+page=(\w+)\s+\?' . '>/',
                    implode("\n", $c),
                    $m
                )) {
                    // trap recursive redirects
                    if (in_array($m[1], $included_pages)) {
                        if (!$quiet) {
                            $content->pushContent(
                                HTML::p(sprintf(
                                    _("recursive inclusion of page %s ignored"),
                                    $cpagename . ' => ' . $m[1]
                                ))
                            );
                        }
                        continue;
                    }
                    $cpagename = $m[1];
                    $page = $dbi->getPage($cpagename);
                    $r = $page->getCurrentRevision();
                    $c = $r->getContent();   // array of lines
                }

                // moved to IncludePage
                $ct = $this->extractParts($c, $cpagename, $args);

                array_push($included_pages, $cpagename);
                if ($smalltitle) {
                    $pname = array_pop(explode(SUBPAGE_SEPARATOR, $cpagename)); // get last subpage name
                    // Use _("%s: %s") instead of .": ". for French punctuation
                    $ct = TransformText(
                        sprintf(
                            _("%s: %s"),
                            "[$pname|$cpagename]",
                            $ct
                        ),
                        $r->get('markup'),
                        $cpagename
                    );
                } else {
                    $ct = TransformText($ct, $r->get('markup'), $cpagename);
                }
                array_pop($included_pages);
                if (! $smalltitle) {
                    $content->pushContent(HTML::p(
                        array('class' => $quiet ?
                                                        '' : 'transclusion-title'),
                        fmt(
                            "Included from %s:",
                            WikiLink($cpagename)
                        )
                    ));
                }
                $content->pushContent(HTML(HTML::div(
                    array('class' => $quiet ?
                                                           '' : 'transclusion'),
                    false,
                    $ct
                )));
            }
        }
        if (! $cpagename) {
            return $this->error(sprintf(_("%s has no subpages defined."), $pagename));
        }
        return $content;
    }
}

// $Log: UnfoldSubpages.php,v $
// Revision 1.22  2007/06/03 21:58:51  rurban
// Fix for Bug #1713784
// Includes this patch and a refactoring.
// RedirectTo is still not handled correctly.
//
// Revision 1.21  2005/09/11 13:20:07  rurban
// use TitleSearch and iterators instead of get_all_pages
//
// Revision 1.20  2005/04/11 19:45:17  rurban
// proper linebreaks
//
// Revision 1.19  2005/01/21 14:12:48  rurban
// clarify $ct
//
// Revision 1.18  2004/12/06 19:50:05  rurban
// enable action=remove which is undoable and seeable in RecentChanges: ADODB ony for now.
// renamed delete_page to purge_page.
// enable action=edit&version=-1 to force creation of a new version.
// added BABYCART_PATH config
// fixed magiqc in adodb.inc.php
// and some more docs
//
// Revision 1.17  2004/11/23 15:17:19  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.16  2004/09/25 16:35:09  rurban
// use stdlib firstNWordsOfContent, extractSection
//
// Revision 1.15  2004/07/03 14:48:18  rurban
// Tested new mysql 4.1.3-beta: binary search bug as fixed.
// => fixed action=upgrade,
// => version check in PearDB also (as in ADODB)
//
// Revision 1.14  2004/07/03 08:19:40  rurban
// trap recursive redirects
//
// Revision 1.13  2004/03/12 15:48:08  rurban
// fixed explodePageList: wrong sortby argument order in UnfoldSubpages
// simplified lib/stdlib.php:explodePageList
//
// Revision 1.12  2004/02/22 23:20:33  rurban
// fixed DumpHtmlToDir,
// enhanced sortby handling in PageList
//   new button_heading th style (enabled),
// added sortby and limit support to the db backends and plugins
//   for paging support (<<prev, next>> links on long lists)
//
// Revision 1.11  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.10  2004/01/27 12:10:45  rurban
// fixed UnfoldSubpages and added docs.
// new arguments: pagename, maxpages
// some arguments are deprecated: sort (use sortby), pages (use maxpages)
// fixed sortby, added docs
//
// Revision 1.9  2004/01/26 09:18:00  rurban
// * changed stored pref representation as before.
//   the array of objects is 1) bigger and 2)
//   less portable. If we would import packed pref
//   objects and the object definition was changed, PHP would fail.
//   This doesn't happen with an simple array of non-default values.
// * use $prefs->retrieve and $prefs->store methods, where retrieve
//   understands the interim format of array of objects also.
// * simplified $prefs->get() and fixed $prefs->set()
// * added $user->_userid and class '_WikiUser' portability functions
// * fixed $user object ->_level upgrading, mostly using sessions.
//   this fixes yesterdays problems with loosing authorization level.
// * fixed WikiUserNew::checkPass to return the _level
// * fixed WikiUserNew::isSignedIn
// * added explodePageList to class PageList, support sortby arg
// * fixed UserPreferences for WikiUserNew
// * fixed WikiPlugin for empty defaults array
// * UnfoldSubpages: added pagename arg, renamed pages arg,
//   removed sort arg, support sortby arg
//
// Revision 1.8  2004/01/25 10:52:16  rurban
// added sortby support to explodePageList() and UnfoldSubpages
// fixes [ 758044 ] Plugin UnfoldSubpages does not sort (includes fix)
//
// Revision 1.7  2003/02/21 04:12:06  dairiki
// Minor fixes for new cached markup.
//
// Revision 1.6  2003/02/11 09:34:34  rurban
// fix by Steven D. Brewer <sbrewer@bio.umass.edu> to respect the $pages argument
//
// Revision 1.5  2003/01/18 22:11:44  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
//
// Revision 1.4  2003/01/05 02:37:30  carstenklapp
// New: Implemented 'smalltitle' argument and date sorting fix from
// Cuthbert Cat's sf patch 655095. Added getVersion & getDescription;
// code rewrapping.
//
// Revision 1.3  2003/01/04 22:46:07  carstenklapp
// Workaround: when page has no subpages avoid include of nonexistant pages.
// KNOWN ISSUES:
// - line & word limit doesn't work if the included page itself
//   includes a plugin

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
