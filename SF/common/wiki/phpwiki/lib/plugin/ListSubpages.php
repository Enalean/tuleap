<?php // -*-php-*-
rcs_id('$Id: ListSubpages.php 2691 2006-03-02 15:31:51Z guerin $');
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

class WikiPlugin_ListSubpages
extends WikiPlugin
{
    function getName() {
        return _("ListSubpages");
    }

    function getDescription () {
        return _("Lists the names of all SubPages of the current page.");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 2691 $");
    }

    function getDefaultArguments() {
        return array('noheader' => false, // no header
                     'pages'    => '',    // maximum number of pages
                                          //  to include
                     'exclude'  => '',
                   /*'relative' => false, */
                     'info'     => ''
                     );
    }
    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor
    // exclude arg allows multiple pagenames exclude=HomePage,RecentChanges

    function run($dbi, $argstr, &$request, $basepage) {
        $pagename = $request->getArg('pagename');


        // FIXME: explodePageList from stdlib doesn't seem to work as
        // expected when there are no subpages. (see also
        // UnfoldSubPages plugin)
        $subpages = explodePageList($pagename . SUBPAGE_SEPARATOR . '*');
        if (! $subpages) {
            return $this->error(_("The current page has no subpages defined."));
        }


        extract($this->getArgs($argstr, $request));

        $content = HTML();
        $subpages = array_reverse($subpages);
        if($pages) {
            $subpages = array_slice ($subpages, 0, $pages);        
        }

        $descrip = fmt("SubPages of %s:",
                       WikiLink($pagename, 'auto'));
        $pagelist = new PageList($info, $exclude);
        if (!$noheader)
            $pagelist->setCaption($descrip);

        foreach ($subpages as $page) {
            // A page cannot include itself. Avoid doublettes.
            static $included_pages = array();
            if (in_array($page, $included_pages)) {
                $content->pushContent(HTML::p(sprintf(_("recursive inclusion of page %s ignored"),
                                                      $page)));
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
};

// $Log$
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
