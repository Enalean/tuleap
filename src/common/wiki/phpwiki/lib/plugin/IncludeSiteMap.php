<?php
// -*-php-*-
rcs_id('$Id: IncludeSiteMap.php,v 1.2 2004/03/09 12:26:20 rurban Exp $');
/**
 Copyright 2003,2004 $ThePhpWikiProgrammingTeam

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
 * http://sourceforge.net/tracker/?func=detail&aid=537380&group_id=6121&atid=306121
 *
 * Submitted by: Cuthbert Cat (cuthbertcat)
 * Redesigned by Reini Urban
 *
 * This is a quick mod of BackLinks to do the job recursively. If your
 * site is categorized correctly, and all the categories are listed in
 * CategoryCategory, then a RecBackLinks there will produce one BIG(!)
 * contents page for the entire site.
 * The list is as deep as the recursion level ('reclimit').
 *
 * 'includepages': passed verbatim to the IncludePage plugin. Default: "words=50"
 *                 To disable words=50 use e.g. something like includepages="quiet=0"
 * 'reclimit':     Max Recursion depth. Default: 2
 * 'direction':    Get BackLinks or forward links (links listed on the page)
 * 'firstreversed': If true, get BackLinks for the first page and forward
 *                 links for the rest. Only applicable when direction = 'forward'.
 * 'excludeunknown': If true (default) then exclude any mentioned pages
 *                 which don't exist yet.  Only applicable when direction='forward'.
 */

require_once('lib/PageList.php');
require_once('lib/plugin/SiteMap.php');

class WikiPlugin_IncludeSiteMap extends WikiPlugin_SiteMap
{
    public function getName()
    {
        return _("IncludeSiteMap");
    }

    public function getDescription()
    {
        return sprintf(
            _("Include recursively all linked pages starting at %s"),
            $this->_pagename
        );
    }
    public function getDefaultArguments()
    {
        return array('exclude'        => '',
                   'include_self'   => 0,
                   'noheader'       => 0,
                   'page'           => '[pagename]',
                   'description'    => $this->getDescription(),
                   'reclimit'       => 2,
                   'info'           => false,
                   'direction'      => 'back',
                   'firstreversed'  => false,
                   'excludeunknown' => true,
                   'includepages'   => 'words=50'
                   );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        return WikiPlugin_SiteMap::run($dbi, $argstr, $request, $basepage);
    }
}

// $Log: IncludeSiteMap.php,v $
// Revision 1.2  2004/03/09 12:26:20  rurban
// better docs how to disable words=50 limitation
//
// Revision 1.1  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
