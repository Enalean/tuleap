<?php
// -*-php-*-
rcs_id('$Id: NoCache.php,v 1.3 2004/06/18 14:42:17 rurban Exp $');
/*
 Copyright 2004 $ThePhpWikiProgrammingTeam

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
 * Don't cache the following page. Mostly used for plugins, which
 * display dynamic content.
 *
 * Usage:
 *   <?plugin NoCache ?>
 * or to delete the whole cache for this page:
 *   <?plugin NoCache nocache||=purge ?>
 *
 * Author:  Reini Urban <rurban@x-ray.at>
 *
 */
class WikiPlugin_NoCache extends WikiPlugin
{
    public function getName()
    {
        return _("NoCache");
    }

    public function getDescription()
    {
        return _("Don't cache this page.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.3 $"
        );
    }

    public function getDefaultArguments()
    {
        return array( 'nocache' => 1 );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        // works regardless of WIKIDB_NOCACHE_MARKUP
        // if WIKIDB_NOCACHE_MARKUP is false it doesn't hurt
        return $request->setArg('nocache', $args['nocache']);
    }
}

// $Log: NoCache.php,v $
// Revision 1.3  2004/06/18 14:42:17  rurban
// added wikilens libs (not yet merged good enough, some work for DanFr)
//
// Revision 1.2  2004/02/25 16:21:25  rurban
// fixed parse error on line 71
//
// Revision 1.1  2004/02/24 17:34:26  rurban
// Don't cache the following page. Mostly used for plugins, which
// display dynamic content.
//
// ----------------------------------------------------------------------
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
