<?php
// -*-php-*-
rcs_id('$Id: OrphanedPages.php,v 1.10 2004/07/09 13:05:34 rurban Exp $');
/**
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
 * A plugin which returns a list of pages which are not linked to by
 * any other page
 *
 * Initial version by Lawrence Akka
 *
 **/
require_once('lib/PageList.php');

class WikiPlugin_OrphanedPages extends WikiPlugin
{
    public function getName()
    {
        return _("OrphanedPages");
    }

    public function getDescription()
    {
        return _("List pages which are not linked to by any other page.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.10 $"
        );
    }

    public function getDefaultArguments()
    {
        return array('noheader'      => false,
                     'include_empty' => false,
                     'exclude'       => '',
                     'info'          => '',
                     'sortby'        => false,
                     'limit'         => 0,
                     'paging'        => 'auto',
                     );
    }
    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor,markup or all
    // exclude arg allows multiple pagenames exclude=HomePage,RecentChanges

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        extract($args);

        // There's probably a more efficient way to do this (eg a
        // tailored SQL query via the backend, but this does the job

        $allpages_iter = $dbi->getAllPages($include_empty);
        $pages = array();
        while ($page = $allpages_iter->next()) {
            $links_iter = $page->getBackLinks();
            // Test for absence of backlinks. If a page is linked to
            // only by itself, it is still an orphan
            $parent = $links_iter->next();
            if (!$parent               // page has no parents
                or (($parent->getName() == $page->getName())
                    and !$links_iter->next())) { // or page has only itself as a parent
                $pages[] = $page;
            }
        }
        $args['count'] = count($pages);
        $pagelist = new PageList($info, $exclude, $args);
        if (!$noheader) {
            $pagelist->setCaption(_("Orphaned Pages in this wiki (%d total):"));
        }
        // deleted pages show up as version 0.
        if ($include_empty) {
            $pagelist->_addColumn('version');
        }
        list($offset,$pagesize) = $pagelist->limit($args['limit']);
        if (!$pagesize) {
            $pagelist->addPageList($pages);
        } else {
            for ($i = $offset; $i < $offset + $pagesize - 1; $i++) {
                if ($i >= $args['count']) {
                    break;
                }
                $pagelist->addPage($pages[$i]);
            }
        }
        return $pagelist;
    }
}

// $Log: OrphanedPages.php,v $
// Revision 1.10  2004/07/09 13:05:34  rurban
// just aesthetics
//
// Revision 1.9  2004/07/09 12:49:46  rurban
// no limit, no sorting
//
// Revision 1.8  2004/04/20 00:56:00  rurban
// more paging support and paging fix for shorter lists
//
// Revision 1.7  2004/04/20 00:34:15  rurban
// more paging support
//
// Revision 1.6  2004/04/18 01:44:02  rurban
// more sortby+limit support
//
// Revision 1.5  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.4  2003/01/18 21:49:00  carstenklapp
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
