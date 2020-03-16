<?php
// -*-php-*-
rcs_id('$Id: PageTrail.php,v 1.8 2005/08/06 13:23:14 rurban Exp $');
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

/**
 * A simple PageTrail WikiPlugin.
 * Put this at the begin/end of each page to store the trail,
 * or better in a template (body or bottom) to support it for all pages.
 * But Cache should be turned off then.
 *
 * Usage:
 * <?plugin PageTrail?>
 * <?plugin PageTrail numberlinks=5?>
 * <?plugin PageTrail invisible=1?>
 */

if (!defined('PAGETRAIL_ARROW')) {
    define('PAGETRAIL_ARROW', " => ");
}

class WikiPlugin_PageTrail extends WikiPlugin
{
    // Four required functions in a WikiPlugin.
    public $def_numberlinks = 5;

    public function getName()
    {
        return _("PageTrail");
    }

    public function getDescription()
    {
        return _("PageTrail Plugin");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.8 $"
        );
    }

    // default values
    public function getDefaultArguments()
    {
        return array('numberlinks' => $this->def_numberlinks,
                     'invisible'   => false,
                     'duplicates'  => false,
                     );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        extract($this->getArgs($argstr, $request));

        if ($numberlinks > 10 || $numberlinks < 0) {
            $numberlinks = $this->def_numberlinks;
        }

        // Get name of the current page we are on
        $thispage = $request->getArg('pagename');
        $Pages = $request->session->get("PageTrail");
        if (!is_array($Pages)) {
            $Pages = array();
        }

        if ($duplicates || ($thispage != $Pages[0])) {
            array_unshift($Pages, $thispage);
            $request->session->set("PageTrail", $Pages);
        }

        $numberlinks = min(count($Pages), $numberlinks);
        if (! $invisible and $numberlinks) {
            $html = HTML::tt(WikiLink($Pages[$numberlinks - 1], 'auto'));
            for ($i = $numberlinks - 2; $i >= 0; $i--) {
                if (!empty($Pages[$i])) {
                    $html->pushContent(
                        PAGETRAIL_ARROW,
                        WikiLink($Pages[$i], 'auto')
                    );
                }
            }
            return $html;
        } else {
            return HTML();
        }
    }
}

// $Log: PageTrail.php,v $
// Revision 1.8  2005/08/06 13:23:14  rurban
// improved empty cookie
//
// Revision 1.7  2005/02/28 21:24:34  rurban
// ignore forbidden ini_set warnings. Bug #1117254 by Xavier Roche
//
// Revision 1.6  2005/02/27 21:34:10  rurban
// Fix error with : in pagenames. Thanks to Dan Frankowski. bug #1115479
//
// Revision 1.5  2005/02/02 19:38:42  rurban
// shorter default trail
//
// Revision 1.4  2004/02/27 02:49:40  rurban
// patch #680562 "PageTrail Duplicates Patch (1.3.4)"
//
// Revision 1.3  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.2  2003/01/18 22:22:36  carstenklapp
// defined constant for arrow, eliminate use of fmt()
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
