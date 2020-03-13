<?php
// -*-php-*-
rcs_id('$Id: LikePages.php,v 1.22 2004/11/23 15:17:19 rurban Exp $');
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

class WikiPlugin_LikePages extends WikiPlugin
{
    public function getName()
    {
        return _("LikePages");
    }

    public function getDescription()
    {
        return sprintf(
            _("List page names which share an initial or final title word with '%s'."),
            '[pagename]'
        );
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
            array('page'     => '[pagename]',
                   'prefix'   => false,
                   'suffix'   => false,
                   'noheader' => false,
            )
        );
    }
    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor
    // exclude arg allows multiple pagenames exclude=HomePage,RecentChanges

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        extract($args);
        if (empty($page) && empty($prefix) && empty($suffix)) {
            return '';
        }

        if ($prefix) {
            $suffix = false;
            $descrip = fmt("Page names with prefix '%s'", $prefix);
        } elseif ($suffix) {
            $descrip = fmt("Page names with suffix '%s'", $suffix);
        } elseif ($page) {
            $words = preg_split(
                '/[\s:-;.,]+/',
                SplitPagename($page)
            );
            $words = preg_grep('/\S/', $words);

            $prefix = reset($words);
            $suffix = end($words);

            $descrip = fmt(
                "These pages share an initial or final title word with '%s'",
                WikiLink($page, 'auto')
            );
        }

        // Search for pages containing either the suffix or the prefix.
        $search = $match = array();
        if (!empty($prefix)) {
            $search[] = $this->_quote($prefix);
            $match[]  = '^' . preg_quote($prefix, '/');
        }
        if (!empty($suffix)) {
            $search[] = $this->_quote($suffix);
            $match[]  = preg_quote($suffix, '/') . '$';
        }

        if ($search) {
            $query = new TextSearchQuery(join(' OR ', $search));
        } else {
            $query = new NullTextSearchQuery; // matches nothing
        }

        $match_re = '/' . join('|', $match) . '/';

        $pagelist = new PageList($info, $exclude, $args);
        if (!$noheader) {
            $pagelist->setCaption($descrip);
        }
        $pages = $dbi->titleSearch($query);
        while ($page = $pages->next()) {
            $name = $page->getName();
            if (!preg_match($match_re, $name)) {
                continue;
            }
            $pagelist->addPage($page);
        }

        return $pagelist;
    }

    public function _quote($str)
    {
        return "'" . str_replace("'", "''", $str) . "'";
    }
}

// $Log: LikePages.php,v $
// Revision 1.22  2004/11/23 15:17:19  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.21  2004/09/25 16:33:52  rurban
// add support for all PageList options
//
// Revision 1.20  2004/05/18 16:23:40  rurban
// rename split_pagename to SplitPagename
//
// Revision 1.19  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.18  2003/01/18 21:48:52  carstenklapp
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
