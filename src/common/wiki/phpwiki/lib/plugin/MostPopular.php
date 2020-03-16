<?php
// -*-php-*-
rcs_id('$Id: MostPopular.php,v 1.32 2004/12/26 17:14:03 rurban Exp $');
/*
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

class WikiPlugin_MostPopular extends WikiPlugin
{
    public function getName()
    {
        return _("MostPopular");
    }

    public function getDescription()
    {
        return _("List the most popular pages.");
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
            array('pagename' => '[pagename]', // hackish
                   //'exclude'  => '',
                   'limit'    => 20, // limit <0 returns least popular pages
                   'noheader' => 0,
                   'sortby'   => '-hits',
                   'info'     => false,
                   //'paging'   => 'auto'
            )
        );
    }

    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor
    // exclude arg allows multiple pagenames exclude=HomePage,RecentChanges
    // sortby: only pagename or hits. mtime not!

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        extract($args);
        if (strstr($sortby, 'mtime')) {
            trigger_error(
                _("sortby=mtime not supported with MostPopular"),
                E_USER_WARNING
            );
            $sortby = '';
        }
        $columns = $info ? explode(",", $info) : array();
        array_unshift($columns, 'hits');

        if (! $request->getArg('count')) {
            //$args['count'] = $dbi->numPages(false,$exclude);
            $allpages = $dbi->mostPopular(0, $sortby);
            $args['count'] = $allpages->count();
        } else {
            $args['count'] = $request->getArg('count');
        }
        //$dbi->touch();
        $pages = $dbi->mostPopular($limit, $sortby);
        $pagelist = new PageList($columns, $exclude, $args);
        while ($page = $pages->next()) {
            $hits = $page->get('hits');
            // don't show pages with no hits if most popular pages
            // wanted
            if ($hits == 0 && $limit > 0) {
                break;
            }
            $pagelist->addPage($page);
        }
        $pages->free();

        if (! $noheader) {
            if ($limit > 0) {
                $pagelist->setCaption(_("The %d most popular pages of this wiki:"));
            } else {
                if ($limit < 0) {
                    $pagelist->setCaption(_("The %d least popular pages of this wiki:"));
                } else {
                    $pagelist->setCaption(_("Visited pages on this wiki, ordered by popularity:"));
                }
            }
        }

        return $pagelist;
    }
}

// $Log: MostPopular.php,v $
// Revision 1.32  2004/12/26 17:14:03  rurban
// fix ADODB MostPopular, avoid limit -1, pass hits on empty data
//
// Revision 1.31  2004/11/23 15:17:19  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.30  2004/10/14 19:19:34  rurban
// loadsave: check if the dumped file will be accessible from outside.
// and some other minor fixes. (cvsclient native not yet ready)
//
// Revision 1.29  2004/09/25 16:33:52  rurban
// add support for all PageList options
//
// Revision 1.28  2004/04/20 18:10:55  rurban
// config refactoring:
//   FileFinder is needed for WikiFarm scripts calling index.php
//   config run-time calls moved to lib/IniConfig.php:fix_configs()
//   added PHPWIKI_DIR smart-detection code (Theme finder)
//   moved FileFind to lib/FileFinder.php
//   cleaned lib/config.php
//
// Revision 1.27  2004/04/20 00:06:53  rurban
// paging support
//
// Revision 1.26  2004/04/18 01:34:21  rurban
// protect most_popular from sortby=mtime
//
// Revision 1.25  2004/03/30 02:38:06  rurban
// RateIt support (currently no recommendation engine yet)
//
// Revision 1.24  2004/03/01 13:48:46  rurban
// rename fix
// p[] consistency fix
//
// Revision 1.23  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.22  2003/01/18 21:48:56  carstenklapp
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
