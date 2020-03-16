<?php
// -*-php-*-
rcs_id('$Id: RandomPage.php,v 1.13 2005/01/25 08:09:26 rurban Exp $');
/**
 Copyright 1999,2000,2001,2002,2005 $ThePhpWikiProgrammingTeam

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

/**
 * With 1.3.11 the "pages" argument was renamed to "numpages".
 * action=upgrade should deal with pages containing RandomPage modified earlier than 2005-01-24
 */
class WikiPlugin_RandomPage extends WikiPlugin
{
    public function getName()
    {
        return _("RandomPage");
    }

    public function getDescription()
    {
        return _("Displays a list of randomly chosen pages or redirects to a random page.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.13 $"
        );
    }

    public function getDefaultArguments()
    {
        return array_merge(
            PageList::supportedArgs(),
            array('numpages'     => 20,     // was pages
                   'pages'        => false, // deprecated
                   'redirect'     => false,
                   'hidename'     => false, // only for numpages=1
                   'exclude'      => $this->default_exclude(),
            'info'         => '')
        );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        extract($args);

        // fix deprecated arg
        if (is_integer($pages)) {
            $numpages = $pages;
            $pages = false;
        // fix new pages handling in arg preprozessor.
        } elseif (is_array($pages)) {
            $numpages = (int) $pages[0];
            if ($numpages > 0 and !$dbi->isWikiPage($numpages)) {
                $pages = false;
            } else {
                $numpages = 1;
            }
        }

        $allpages = $dbi->getAllPages(false, $sortby, $limit, $exclude);
        $pagearray = $allpages->asArray();
        better_srand(); // Start with a good seed.

        if (($numpages == 1) && $pagearray) {
            $page = $pagearray[array_rand($pagearray)];
            $pagename = $page->getName();
            if ($redirect) {
                $request->redirect(WikiURL($pagename, false, 'absurl')); // noreturn
            }
            if ($hidename) {
                return WikiLink($pagename, false, _("RandomPage"));
            } else {
                return WikiLink($pagename);
            }
        }

        $numpages = min(max(1, (int) $numpages), 20, count($pagearray));
        $pagelist = new PageList($info, $exclude, $args);
        $shuffle = array_rand($pagearray, $numpages);
        if (is_array($shuffle)) {
            foreach ($shuffle as $i) {
                if (isset($pagearray[$i])) {
                    $pagelist->addPage($pagearray[$i]);
                }
            }
        } else { // if $numpages = 1
            if (isset($pagearray[$shuffle])) {
                $pagelist->addPage($pagearray[$shuffle]);
            }
        }
        return $pagelist;
    }

    public function default_exclude()
    {
        // Some useful default pages to exclude.
        $default_exclude = 'RandomPage,HomePage,AllPages,RecentChanges,RecentEdits,FullRecentChanges';
        foreach (explode(",", $default_exclude) as $e) {
            $exclude[] = gettext($e);
        }
        return implode(",", $exclude);
    }
}


// $Log: RandomPage.php,v $
// Revision 1.13  2005/01/25 08:09:26  rurban
// deprecate pages, replaced by numpages; use complicated transition code; improved pagelist args support
//
// Revision 1.12  2004/11/25 08:30:15  rurban
// dont mess around with spaces
//
// Revision 1.11  2004/10/04 23:42:42  rurban
// fix for pages=1
//
// Revision 1.10  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.9  2003/01/18 22:01:43  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
//
// Revision 1.8  2003/01/04 02:25:41  carstenklapp
// Added copyleft and plugin description & version, tweaked default
// exclude list code to allow spaces (a cosmetic workaround for
// PluginManager plugin).

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
