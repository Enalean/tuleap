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

require_once('lib/PageList.php');

class WikiPlugin_RandomPage
extends WikiPlugin
{
    function getName () {
        return _("RandomPage");
    }

    function getDescription () {
        return _("Displays a list of randomly chosen pages or redirects to a random page.");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision$");
    }

    function getDefaultArguments() {
        return array('pages'        => 1,
                     'redirect'     => false,
                     'hidename'     => false, // only for pages=1
                     'exclude'      => $this->default_exclude(),
                     'info'         => '');
    }

    function run($dbi, $argstr, &$request, $basepage) {
        extract($this->getArgs($argstr, $request));

        $allpages = $dbi->getAllPages();

        $exclude = $exclude ? explode(",", $exclude) : array();
        foreach ($exclude as $e) {
            $_exclude []= trim($e);
        }

        while ($page = $allpages->next()) {
            if (!in_array($page->getName(), $_exclude))
                $pagearray[] = $page;
        }

        better_srand(); // Start with a good seed.

        if ($pages == 1 && $pagearray) {
            $page = $pagearray[array_rand($pagearray)];
            if ($redirect)
                $request->redirect(WikiURL($page, false, 'absurl')); // noreturn
            if ($hidename)
                return WikiLink($page, false, _("RandomPage"));
            else
                return WikiLink($page);
        }

        $pages = min( max(1, (int)$pages), 20, count($pagearray));
        $pagelist = new PageList($info);
        $shuffle = array_rand($pagearray, $pages);
        foreach ($shuffle as $i)
            $pagelist->addPage($pagearray[$i]);
        return $pagelist;
    }

    function default_exclude() {
        // Some useful default pages to exclude.
        $default_exclude = 'RandomPage, HomePage, AllPages, RecentChanges, RecentEdits, FullRecentChanges';
        foreach (explode(",", $default_exclude) as $e) {
            $_exclude[] = gettext(trim($e));
        }
        return implode(", ", $_exclude);
    }
};


// $Log$
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
?>
