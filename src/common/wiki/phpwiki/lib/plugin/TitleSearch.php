<?php // -*-php-*-
rcs_id('$Id$');
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

require_once('lib/TextSearchQuery.php');
require_once('lib/PageList.php');
/**
 */
class WikiPlugin_TitleSearch
extends WikiPlugin
{
    function getName () {
        return _("TitleSearch");
    }

    function getDescription () {
        return _("Search the titles of all pages in this wiki.");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision$");
    }

    function getDefaultArguments() {
        return array('s'             => false,
                     'auto_redirect' => false,
                     'noheader'      => false,
                     'exclude'       => '',
                     'info'          => false
                     );
    }
    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor
    // exclude arg allows multiple pagenames exclude=HomePage,RecentChanges

    function run($dbi, $argstr, &$request, $basepage) {
        $args = $this->getArgs($argstr, $request);
        if (empty($args['s']))
            return '';

        extract($args);

        $query = new TextSearchQuery($s);
        $pages = $dbi->titleSearch($query);

        $pagelist = new PageList($info, $exclude);

        while ($page = $pages->next()) {
            $pagelist->addPage($page);
            $last_name = $page->getName();
        }
        // Provide an unknown WikiWord link to allow for page creation
        // when a search returns no results
        if (!$noheader)
            $pagelist->setCaption(fmt("Title search results for '%s'",
                                      $pagelist->getTotal() == 0
                                      ? WikiLink($s, 'auto') : $s));

        if ($auto_redirect && ($pagelist->getTotal() == 1)) {
            return HTML($request->redirect(WikiURL($last_name, false, 'absurl'), false),
                        $pagelist);
        }

        return $pagelist;
    }
};

// $Log$
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
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
