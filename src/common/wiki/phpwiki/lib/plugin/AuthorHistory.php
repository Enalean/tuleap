<?php
// -*-php-*-
rcs_id('$Id: AuthorHistory.php,v 1.6 2004/06/14 11:31:38 rurban Exp $');
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


/*
 *** EXPERIMENTAL PLUGIN ******************
 Needs a lot of work! Use at your own risk.
 ******************************************

 try this in a page called AuthorHistory:

<?plugin AuthorHistory page=username includeminor=true ?>
----
<?plugin AuthorHistory page=all ?>


 try this in a subpage of your UserName: (UserName/AuthorHistory)

<?plugin AuthorHistory page=all includeminor=true ?>


* Displays a list of revision edits by one particular user, for the
* current page, a specified page, or all pages.

* This is a big hack to create a PageList like table. (PageList
* doesn't support page revisions yet, only pages.)

* Make a new subclass of PageHistory to filter changes of one (or all)
* page(s) by a single author?

*/

/*
 reference
 _PageHistory_PageRevisionIter
 WikiDB_PageIterator(&$wikidb, &$pages
 WikiDB_PageRevisionIterator(&$wikidb, &$revisions)
*/


require_once('lib/PageList.php');

//include_once('lib/debug.php');

class WikiPlugin_AuthorHistory extends WikiPlugin
{
    public function getName()
    {
        return _("AuthorHistory");
    }

    public function getDescription()
    {
        return sprintf(_("List all page revisions edited by one user with diff links, or show a PageHistory-like list of a single page for only one user."));
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.6 $"
        );
    }

    public function getDefaultArguments()
    {
        global $request;
        return array('exclude'      => '',
                     'noheader'     => false,
                     'includeminor' => false,
                     'includedeleted' => false,
                     'author'       => $request->_user->UserName(),
                     'page'         => '[pagename]',
                     'info'         => 'version,minor,author,summary,mtime'
                     );
    }
    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor
    // exclude arg allows multiple pagenames exclude=HomePage,RecentChanges

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $this->_args = $this->getArgs($argstr, $request);
        extract($this->_args);
        //trigger_error("1 p= $page a= $author");
        if ($page && $page == 'username') { //FIXME: use [username]!!!!!
            $page = $author;
        }
        //trigger_error("2 p= $page a= $author");
        if (!$page || !$author) { //user not signed in or no author specified
            return '';
        }
        //$pagelist = new PageList($info, $exclude);
        ///////////////////////////

        $nbsp = HTML::raw('&nbsp;');

        global $WikiTheme; // date & time formatting

        if (! ($page == 'all')) {
            $p = $dbi->getPage($page);

            $t = HTML::table(array('class' => 'pagelist',
                                   'style' => 'font-size:smaller'));
            $th = HTML::thead();
            $tb = HTML::tbody();

            $th->pushContent(HTML::tr(
                HTML::td(
                    array('align' => 'right'),
                    _("Version")
                ),
                $includeminor ? HTML::td(_("Minor")) : "",
                HTML::td(_("Author")),
                HTML::td(_("Summary")),
                HTML::td(_("Modified"))
            ));

            $allrevisions_iter = $p->getAllRevisions();
            while ($rev = $allrevisions_iter->next()) {
                $isminor = $rev->get('is_minor_edit');
                $authordoesmatch = $author == $rev->get('author');

                if ($authordoesmatch && (!$isminor || ($includeminor && $isminor))) {
                    $difflink = Button(
                        array('action' => 'diff',
                                             'previous' => 'minor'),
                        $rev->getversion(),
                        $rev
                    );
                    $tr = HTML::tr(
                        HTML::td(
                            array('align' => 'right'),
                            $difflink,
                            $nbsp
                        ),
                        $includeminor ? (HTML::td($nbsp, ($isminor ? "minor" : "major"), $nbsp)) : "",
                        HTML::td($nbsp, WikiLink(
                            $rev->get('author'),
                            'if_known'
                        ), $nbsp),
                        HTML::td($nbsp, $rev->get('summary')),
                        HTML::td(
                            array('align' => 'right'),
                            $WikiTheme->formatdatetime($rev->get('mtime'))
                        )
                    );

                    $class = $isminor ? 'evenrow' : 'oddrow';
                    $tr->setAttr('class', $class);
                    $tb->pushContent($tr);
                    //$pagelist->addPage($rev->getPage());
                }
            }
            $captext = fmt(
                $includeminor ? "History of all major and minor edits by %s to page %s."  : "History of all major edits by %s to page %s.",
                WikiLink($author, 'auto'),
                WikiLink($page, 'auto')
            );
            $t->pushContent(HTML::caption($captext));
            $t->pushContent($th, $tb);
        } else {
            //search all pages for all edits by this author

            /////////////////////////////////////////////////////////////

            $t = HTML::table(array('class' => 'pagelist',
                                   'style' => 'font-size:smaller'));
            $th = HTML::thead();
            $tb = HTML::tbody();

            $th->pushContent(HTML::tr(
                HTML::td(_("Page Name")),
                HTML::td(
                    array('align' => 'right'),
                    _("Version")
                ),
                $includeminor ? HTML::td(_("Minor")) : "",
                HTML::td(_("Summary")),
                HTML::td(_("Modified"))
            ));
            /////////////////////////////////////////////////////////////

            $allpages_iter = $dbi->getAllPages($includedeleted);
            while ($p = $allpages_iter->next()) {
                /////////////////////////////////////////////////////////////

                $allrevisions_iter = $p->getAllRevisions();
                while ($rev = $allrevisions_iter->next()) {
                    $isminor = $rev->get('is_minor_edit');
                    $authordoesmatch = $author == $rev->get('author');
                    if ($authordoesmatch && (!$isminor || ($includeminor && $isminor))) {
                        $difflink = Button(
                            array('action' => 'diff',
                                                 'previous' => 'minor'),
                            $rev->getversion(),
                            $rev
                        );
                        $tr = HTML::tr(
                            HTML::td(
                                $nbsp,
                                ($isminor ? $rev->_pagename : WikiLink($rev->_pagename, 'auto'))
                            ),
                            HTML::td(
                                array('align' => 'right'),
                                $difflink,
                                $nbsp
                            ),
                            $includeminor ? (HTML::td($nbsp, ($isminor ? "minor" : "major"), $nbsp)) : "",
                            HTML::td($nbsp, $rev->get('summary')),
                            HTML::td(
                                array('align' => 'right'),
                                $WikiTheme->formatdatetime($rev->get('mtime')),
                                $nbsp
                            )
                        );

                        $class = $isminor ? 'evenrow' : 'oddrow';
                        $tr->setAttr('class', $class);
                        $tb->pushContent($tr);
                        //$pagelist->addPage($rev->getPage());
                    }
                }

                /////////////////////////////////////////////////////////////
            }

            $captext = fmt(
                $includeminor ? "History of all major and minor modifications for any page edited by %s."  : "History of major modifications for any page edited by %s.",
                WikiLink($author, 'auto')
            );
            $t->pushContent(HTML::caption($captext));
            $t->pushContent($th, $tb);
        }

        //        if (!$noheader) {
        // total minor, major edits. if include minoredits was specified
        //        }
        return $t;

        //        if (!$noheader) {
        //            $pagelink = WikiLink($page, 'auto');
        //
        //            if ($pagelist->isEmpty())
        //                return HTML::p(fmt("No pages link to %s.", $pagelink));
        //
        //            if ($pagelist->getTotal() == 1)
        //                $pagelist->setCaption(fmt("One page links to %s:",
        //                                          $pagelink));
        //            else
        //                $pagelist->setCaption(fmt("%s pages link to %s:",
        //                                          $pagelist->getTotal(), $pagelink));
        //        }
        //
        //        return $pagelist;
    }
}

// $Log: AuthorHistory.php,v $
// Revision 1.6  2004/06/14 11:31:38  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.5  2004/02/28 21:14:08  rurban
// generally more PHPDOC docs
//   see http://xarch.tu-graz.ac.at/home/rurban/phpwiki/xref/
// fxied WikiUserNew pref handling: empty theme not stored, save only
//   changed prefs, sql prefs improved, fixed password update,
//   removed REPLACE sql (dangerous)
// moved gettext init after the locale was guessed
// + some minor changes
//
// Revision 1.4  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.3  2004/01/26 09:18:00  rurban
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
// Revision 1.2  2003/12/08 22:44:58  carstenklapp
// Code cleanup: fixed rcsid
//
// Revision 1.1  2003/12/08 22:43:30  carstenklapp
// New experimental plugin to provide a different kind of
// PageHistory. Functional as-is, but is in need of much cleanup and
// refactoring. Probably very, very slow on wikis with many pages!
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
