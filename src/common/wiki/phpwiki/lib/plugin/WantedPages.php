<?php
// -*-php-*-
rcs_id('$Id: WantedPages.php,v 1.16 2004/11/23 15:17:19 rurban Exp $');
/*
 Copyright (C) 2002, 2004 $ThePhpWikiProgrammingTeam

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
 * Rewrite of WantedPages, which uses PageList and prints the references, not just the count.
 * It disables r1.6 but is more explicit, and of comparable convenience.
 *
 * A plugin which returns a list of referenced pages which do not exist yet.
 * All empty pages which are linked from any page - with an ending question mark,
 * or for just a single page, when the page argument is present.
 *
 * TODO: sort pagename col: disable backend fallback
 **/
include_once('lib/PageList.php');

class WikiPlugin_WantedPages extends WikiPlugin
{
    public function getName()
    {
        return _("WantedPages");
    }
    public function getDescription()
    {
        return _("Lists referenced page names which do not exist yet.");
    }
    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.16 $"
        );
    }
    public function getDefaultArguments()
    {
        return array_merge(
            PageList::supportedArgs(),
            array('page'     => '[pagename]', // just for a single page.
                   'noheader' => false,
                   'exclude_from'  => _("PgsrcTranslation") . ',' . _("InterWikiMap"),
                   'limit'    => '100',
            'paging'   => 'auto')
        );
    }

    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor,markup or all
    // exclude arg allows multiple pagenames exclude=HomePage,RecentChanges
    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        if (!empty($args['exclude_from'])) {
            $args['exclude_from'] = is_string($args['exclude_from'])
                ? explodePageList($args['exclude_from'])
                : $args['exclude_from']; // <! plugin-list !>
        }
        extract($args);
        if ($page == _("WantedPages")) {
            $page = "";
        }

        // There's probably a more memory-efficient way to do this (eg
        // a tailored SQL query via the backend, but this gets the job
        // done.
        // TODO: Move this to backend/dumb/WantedPagesIter.php

        if (!$page) {
            $GLOBALS['WikiTheme']->addPageListColumn(
                array('wanted' => array('_PageList_Column_WantedPages_wanted', 'custom:wanted', _("Wanted From"), 'left'))
            );
        }
        $pagelist = new PageList($page ? '' : 'pagename,wanted', $exclude, $args); // search button?
        $pagelist->_wpagelist = array();

        if (!$page) {
            list($offset, $maxcount) = $pagelist->limit($limit);
            $wanted_iter = $dbi->wantedPages($exclude_from, $exclude, $sortby, $limit);
            while ($row = $wanted_iter->next()) {
                $wanted = $row['pagename'];
                $wantedfrom = $row['wantedfrom'];
                // ignore duplicates:
                if (empty($pagelist->_wpagelist[$wanted])) {
                    $pagelist->addPage($wanted);
                }
                $pagelist->_wpagelist[$wanted][] = $wantedfrom;
            }
            $wanted_iter->free();
            // update limit, but it's still a hack.
            $pagelist->_options['limit'] = "$offset," . min($pagelist->getTotal(), $maxcount);
        } elseif ($dbi->isWikiPage($page)) {
            //only get WantedPages links for one page
            $page_handle = $dbi->getPage($page);
            $links = $page_handle->getPageLinks(true); // include_empty
            while ($link_handle = $links->next()) {
                if (! $dbi->isWikiPage($linkname = $link_handle->getName())) {
                    $pagelist->addPage($linkname);
                    //if (!array_key_exists($linkname, $this->_wpagelist))
                    $pagelist->_wpagelist[$linkname][] = 1;
                }
            }
        }
        /*
        if ($sortby) {
            ksort($this->_wpagelist);
            arsort($this->_wpagelist);
        }*/
        if (!$noheader) {
            if ($page) {
                $pagelist->setCaption(sprintf(_("Wanted Pages for %s:"), $page));
            } else {
                $pagelist->setCaption(sprintf(_("Wanted Pages in this wiki:")));
            }
        }
        // reference obviously doesn't work, so force an update to add _wpagelist to parentobj
        if (isset($pagelist->_columns[1]) and $pagelist->_columns[1]->_field == 'wanted') {
            $pagelist->_columns[1]->parentobj = $pagelist;
        }
        return $pagelist;
    }
}

// which links to the missing page
class _PageList_Column_WantedPages_wanted extends _PageList_Column
{
    public function __construct($params)
    {
        $this->parentobj = $params[3];
        parent::__construct($params[0], $params[1], $params[2]);
    }
    public function _getValue(&$page, $revision_handle)
    {
        $html = false;
        foreach ($this->parentobj->_wpagelist[$page->getName()] as $page) {
            if ($html) {
                $html->pushContent(', ', WikiLink($page));
            } else {
                $html = HTML(WikiLink($page));
            }
        }
        return $html;
    }
}

// $Log: WantedPages.php,v $
// Revision 1.16  2004/11/23 15:17:19  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.15  2004/11/23 13:35:49  rurban
// add case_exact search
//
// Revision 1.14  2004/11/20 17:35:58  rurban
// improved WantedPages SQL backends
// PageList::sortby new 3rd arg valid_fields (override db fields)
// WantedPages sql pager inexact for performance reasons:
//   assume 3 wantedfrom per page, to be correct, no getTotal()
// support exclude argument for get_all_pages, new _sql_set()
//
// Revision 1.13  2004/11/20 11:28:49  rurban
// fix a yet unused PageList customPageListColumns bug (merge class not decl to _types)
// change WantedPages to use PageList
// change WantedPages to print the list of referenced pages, not just the count.
//   the old version was renamed to WantedPagesOld
//   fix and add handling of most standard PageList arguments (limit, exclude, ...)
// TODO: pagename sorting, dumb/WantedPagesIter and SQL optimization
//
// Revision 1.12  2004/10/04 23:39:34  rurban
// just aesthetics
//
// Revision 1.11  2004/04/20 00:56:00  rurban
// more paging support and paging fix for shorter lists
//
// Revision 1.10  2004/04/18 01:44:02  rurban
// more sortby+limit support
//
// Revision 1.9  2004/04/10 04:15:06  rurban
// sf.net 927122 Suggestion
//
// Revision 1.8  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.7  2003/12/19 06:57:49  carstenklapp
// Bugfix: Enclose FullTextSearch query with quotes when the [Wiki Word]
// contains spaces.
//
// Revision 1.6  2003/11/19 17:08:23  carstenklapp
// New feature: Clicking on the number of citations in the links column
// now does a FullTextSearch for the WantedPage link!
//
// Revision 1.5  2003/03/25 21:05:27  dairiki
// Ensure pagenames are strings.
//
// Revision 1.4  2003/01/18 22:14:24  carstenklapp
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
