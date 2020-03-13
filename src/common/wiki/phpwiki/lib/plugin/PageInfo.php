<?php
// -*-php-*-
rcs_id('$Id: PageInfo.php,v 1.5 2004/02/17 12:11:36 rurban Exp $');
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

/**
 * An ActionPage plugin which returns extra information about a page.
 * This plugin just passes a page revision handle to the Template
 * 'info.tmpl', which does all the real work.
 */
class WikiPlugin_PageInfo extends WikiPlugin
{
    public function getName()
    {
        return _("PageInfo");
    }

    public function getDescription()
    {
        return sprintf(
            _("Show extra page Info and statistics for %s."),
            '[pagename]'
        );
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.5 $"
        );
    }

    public function getDefaultArguments()
    {
        return array('page' => '[pagename]',
                     'version' => '[version]');
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        extract($args);

        $pagename = $page;
        $page = $request->getPage();
        $current = $page->getCurrentRevision();

        if ($current->getVersion() < 1) {
            return fmt(
                "I'm sorry, there is no such page as %s.",
                WikiLink($pagename, 'unknown')
            );
        }

        if (!empty($version)) {
            if (!($revision = $page->getRevision($version))) {
                NoSuchRevision($request, $page, $version);
            }
        } else {
            $revision = $current;
        }

        $template = new Template(
            'info',
            $request,
            array('revision' => $revision)
        );
        return $template;
    }
}

// $Log: PageInfo.php,v $
// Revision 1.5  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.4  2003/02/17 02:18:30  dairiki
// Fix so that PageInfo will work when current version of page
// has been "deleted".
//
// Fix so that PageInfo will work on an old version of a page.
//
// Revision 1.3  2003/01/18 21:49:01  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
//
// Revision 1.2  2003/01/04 23:27:39  carstenklapp
// New: Gracefully handle non-existant pages. Added copyleft;
// getVersion() for PluginManager.
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
