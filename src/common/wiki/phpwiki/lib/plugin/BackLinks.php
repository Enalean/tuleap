<?php
// -*-php-*-
rcs_id('$Id: BackLinks.php,v 1.32 2004/12/06 19:50:05 rurban Exp $');
/**
 Copyright 1999, 2000, 2001, 2002 $ThePhpWikiProgrammingTeam

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

class WikiPlugin_BackLinks extends WikiPlugin
{
    public function getName()
    {
        return _("BackLinks");
    }

    public function getDescription()
    {
        return sprintf(_("List all pages which link to %s."), '[pagename]');
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.32 $"
        );
    }

    public function getDefaultArguments()
    {
        return array_merge(
            PageList::supportedArgs(),
            array('include_self' => false,
                   'noheader'     => false,
                   'page'         => '[pagename]',
            )
        );
    }

    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor
    // exclude arg allows multiple pagenames exclude=HomePage,RecentChanges
    // NEW: info=count : number of links
    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        extract($args);
        if (empty($page) and $page != '0') {
            return '';
        }
        // exclude is now already expanded in WikiPlugin::getArgs()
        if (empty($exclude)) {
            $exclude = array();
        }
        if (!$include_self) {
            $exclude[] = $page;
        }
        if ($info) {
            $info = explode(",", $info);
            if (in_array('count', $info)) {
                $args['types']['count'] =
                    new _PageList_Column_BackLinks_count('count', _("#"), 'center');
            }
        }
        $args['dosort'] = !empty($args['sortby']); // override DB sort (??)
        $pagelist = new PageList($info, $exclude, $args);
        $p = $dbi->getPage($page);
        $pagelist->addPages($p->getBackLinks(false, $sortby, $limit, $exclude));

        // Localization note: In English, the differences between the
        // various phrases spit out here may seem subtle or negligible
        // enough to tempt you to combine/normalize some of these
        // strings together, but the grammar employed most by other
        // languages does not always end up with so subtle a
        // distinction as it does with English in this case. :)
        if (!$noheader) {
            if (
                $page == $request->getArg('pagename')
                and !$dbi->isWikiPage($page)
            ) {
                    // BackLinks plugin is more than likely being called
                    // upon for an empty page on said page, while either
                    // 'browse'ing, 'create'ing or 'edit'ing.
                    //
                    // Don't bother displaying a WikiLink 'unknown', just
                    // the Un~WikiLink~ified (plain) name of the uncreated
                    // page currently being viewed.
                    $pagelink = $page;

                if ($pagelist->isEmpty()) {
                    return HTML::p(fmt("No other page links to %s yet.", $pagelink));
                }

                if ($pagelist->getTotal() == 1) {
                    $pagelist->setCaption(fmt(
                        "One page would link to %s:",
                        $pagelink
                    ));
                } else {
                    // Some future localizations will actually require
                    // this... (BelieveItOrNot, English-only-speakers!(:)
                    //
                    // else if ($pagelist->getTotal() == 2)
                    //     $pagelist->setCaption(fmt("Two pages would link to %s:",
                    //                               $pagelink));
                    $pagelist->setCaption(fmt(
                        "%s pages would link to %s:",
                        $pagelist->getTotal(),
                        $pagelink
                    ));
                }
            } else {
                // BackLinks plugin is being displayed on a normal page.
                $pagelink = WikiLink($page, 'auto');

                if ($pagelist->isEmpty()) {
                    return HTML::p(fmt("No page links to %s.", $pagelink));
                }

                //trigger_error("DEBUG: " . $pagelist->getTotal());

                if ($pagelist->getTotal() == 1) {
                    $pagelist->setCaption(fmt(
                        "One page links to %s:",
                        $pagelink
                    ));
                } else {
                    // Some future localizations will actually require
                    // this... (BelieveItOrNot, English-only-speakers!(:)
                    //
                    // else if ($pagelist->getTotal() == 2)
                    //     $pagelist->setCaption(fmt("Two pages link to %s:",
                    //                               $pagelink));
                    $pagelist->setCaption(fmt(
                        "%s pages link to %s:",
                        $pagelist->getTotal(),
                        $pagelink
                    ));
                }
            }
        }
        return $pagelist;
    }
}

// how many links from this backLink to other pages
class _PageList_Column_BackLinks_count extends _PageList_Column
{
    public function _getValue($page, &$revision_handle)
    {
        $iter = $page->getPageLinks();
        $count = $iter->count();
        return $count;
    }
}

// $Log: BackLinks.php,v $
// Revision 1.32  2004/12/06 19:50:05  rurban
// enable action=remove which is undoable and seeable in RecentChanges: ADODB ony for now.
// renamed delete_page to purge_page.
// enable action=edit&version=-1 to force creation of a new version.
// added BABYCART_PATH config
// fixed magiqc in adodb.inc.php
// and some more docs
//
// Revision 1.31  2004/11/26 18:39:02  rurban
// new regex search parser and SQL backends (90% complete, glob and pcre backends missing)
//
// Revision 1.30  2004/11/25 17:20:52  rurban
// and again a couple of more native db args: backlinks
//
// Revision 1.29  2004/11/23 15:17:19  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.28  2004/10/14 17:16:22  rurban
// override DB sort: not applicable
//
// Revision 1.27  2004/09/25 16:33:52  rurban
// add support for all PageList options
//
// Revision 1.26  2004/09/14 10:32:32  rurban
// support exclude = plugin-list
//
// Revision 1.25  2004/09/13 15:00:50  rurban
// info=count: number of links at this subpage
//
// Revision 1.24  2004/04/18 05:42:17  rurban
// more fixes for page="0"
// better WikiForum support
//
// Revision 1.23  2004/02/22 23:20:33  rurban
// fixed DumpHtmlToDir,
// enhanced sortby handling in PageList
//   new button_heading th style (enabled),
// added sortby and limit support to the db backends and plugins
//   for paging support (<<prev, next>> links on long lists)
//
// Revision 1.22  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.21  2003/12/22 07:31:57  carstenklapp
// Bugfix: commented out debugging code that snuck into the release.
//
// Revision 1.20  2003/12/14 05:36:31  carstenklapp
// Internal changes to prepare for an upcoming feature: Added some
// conditions and alternate phrases (alternate wording of text srings
// when referring to a non-existant page (i.e. WikiLink 'unknown')) when
// calling the BackLinks plugin *within* a non-existant page, such as
// from within an editpage or browse template while editing a new page.
//
// Revision 1.19  2003/01/18 21:19:25  carstenklapp
// Code cleanup:
// Reformatting; added copyleft, getVersion, getDescription
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
