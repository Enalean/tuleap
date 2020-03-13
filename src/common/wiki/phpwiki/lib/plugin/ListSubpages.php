<?php
// -*-php-*-
rcs_id('$Id: ListSubpages.php,v 1.6 2004/11/23 15:17:19 rurban Exp $');
/*
 Copyright 2002 $ThePhpWikiProgrammingTeam

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
 * ListSubpages:  Lists the names of all SubPages of the current page.
 *                Based on UnfoldSubpages.
 * Usage:   <?plugin ListSubpages noheader=1 info=pagename,hits,mtime ?>
 */
require_once('lib/PageList.php');

class WikiPlugin_ListSubpages extends WikiPlugin
{
    public function getName()
    {
        return _("ListSubpages");
    }

    public function getDescription()
    {
        return _("Lists the names of all SubPages of the current page.");
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
        return array_merge(
            PageList::supportedArgs(),
            array('noheader' => false, // no header
                     'basepage' => false, // subpages of which page, default: current
                     'maxpages' => '',    // maximum number of pages to include, change that to limit
                     //'exclude'  => '',
                     /*'relative' => false, */
                     'info'     => ''
            )
        );
    }
    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor,count
    // exclude arg allows multiple pagenames exclude=HomePage,RecentChanges

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        if ($args['basepage']) {
            $pagename = $args['basepage'];
        } else {
            $pagename = $request->getArg('pagename');
        }

        // FIXME: explodePageList from stdlib doesn't seem to work as
        // expected when there are no subpages. (see also
        // UnfoldSubPages plugin)
        $subpages = explodePageList($pagename . SUBPAGE_SEPARATOR . '*');
        if (! $subpages) {
            return $this->error(_("The current page has no subpages defined."));
        }
        extract($args);

        $content = HTML();
        $subpages = array_reverse($subpages);
        if ($maxpages) {
            $subpages = array_slice($subpages, 0, $maxpages);
        }

        $descrip = fmt(
            "SubPages of %s:",
            WikiLink($pagename, 'auto')
        );
        if ($info) {
            $info = explode(",", $info);
            if (in_array('count', $info)) {
                $args['types']['count'] = new _PageList_Column_ListSubpages_count('count', _("#"), 'center');
            }
        }
        $pagelist = new PageList($info, $exclude, $args);
        if (!$noheader) {
            $pagelist->setCaption($descrip);
        }

        foreach ($subpages as $page) {
            // A page cannot include itself. Avoid doublettes.
            static $included_pages = array();
            if (in_array($page, $included_pages)) {
                $content->pushContent(HTML::p(sprintf(
                    _("recursive inclusion of page %s ignored"),
                    $page
                )));
                continue;
            }
            array_push($included_pages, $page);
            //if ($relative) {
            // TODO: add relative subpage name display to PageList class
            //}
            $pagelist->addPage($page);

            array_pop($included_pages);
        }
        $content->pushContent($pagelist);
        return $content;
    }
}

// how many backlinks for this subpage
class _PageList_Column_ListSubpages_count extends _PageList_Column
{
    public function _getValue($page, &$revision_handle)
    {
        $iter = $page->getBackLinks();
        $count = $iter->count();
        return $count;
    }
}

// $Log: ListSubpages.php,v $
// Revision 1.6  2004/11/23 15:17:19  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.5  2004/09/13 14:59:56  rurban
// info=count: number of backlinks for this subpage
//
// Revision 1.4  2004/08/18 11:15:11  rurban
// added basepage argument. Default current
//
// Revision 1.3  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.2  2003/11/30 18:23:48  carstenklapp
// Code housekeeping: PEAR coding standards reformatting only.
//
// Revision 1.1  2003/11/23 16:33:02  carstenklapp
// New plugin to list names of SubPages of the currrent
// page. (Unfortunately this plugin reveals a bug in
// stdlib/explodePageList(), the function doesn't seem to work as
// expected when there are no subpages (see also UnfoldSubPages plugin).
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
