<?php
// -*-php-*-
rcs_id('$Id: PrevNext.php,v 1.4 2004/06/14 11:31:39 rurban Exp $');
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
 * Usage: <?plugin PrevNext prev=PrevLink next=NextLink ?>
 * See also PageGroup which automatically tries to extract the various links
 *
 */
class WikiPlugin_PrevNext extends WikiPlugin
{
    public function getName()
    {
        return _("PrevNext");
    }

    public function getDescription()
    {
        return sprintf(_("Easy navigation buttons for %s"), '[pagename]');
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.4 $"
        );
    }

    public function getDefaultArguments()
    {
        return array(
                     'prev'    => '',
                     'next'    => '',
                     'up'      => '',
                     'contents' => '',
                     'index'   => '',
                     'up'      => '',
                     'first'   => '',
                     'last'    => '',
                     'order'   => '',
                     'style'   => 'button', // or 'text'
                     'class'   => 'wikiaction'
                     );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        extract($args);
        $directions = array ('first'    => _("First"),
                             'prev'     => _("Previous"),
                             'next'     => _("Next"),
                             'last'     => _("Last"),
                             'up'       => _("Up"),
                             'contents'  => _("Contents"),
                             'index'    => _("Index")
                             );
        if ($order) { // reorder the buttons: comma-delimited
            $new_directions = array();
            foreach (explode(',', $order) as $o) {
                $new_directions[$o] = $directions[$o];
            }
            $directions = $new_directions;
            unset($new_directions); // free memory
        }

        global $WikiTheme;
        $sep = $WikiTheme->getButtonSeparator();
        $links = HTML();
        if ($style == 'text') {
            if (!$sep) {
                $sep = " | "; // force some kind of separator
            }
            $links->pushcontent(" [ ");
        }
        $last_is_text = false;
        $this_is_first = true;
        foreach ($directions as $dir => $label) {
            // if ($last_is_text) $links->pushContent($sep);
            if (!empty($args[$dir])) {
                $url = $args[$dir];
                if ($style == 'button') {
                    // localized version: _("Previous").gif
                    if ($imgurl = $WikiTheme->getButtonURL($label)) {
                        if ($last_is_text) {
                            $links->pushContent($sep);
                        }
                        $links->pushcontent(new ImageButton(
                            $label,
                            $url,
                            false,
                            $imgurl
                        ));
                        $last_is_text = false;
                        // generic version: prev.gif
                    } elseif ($imgurl = $WikiTheme->getButtonURL($dir)) {
                        if ($last_is_text) {
                            $links->pushContent($sep);
                        }
                        $links->pushContent(new ImageButton(
                            $label,
                            $url,
                            false,
                            $imgurl
                        ));
                        $last_is_text = false;
                    } else { // text only
                        if (! $this_is_first) {
                            $links->pushContent($sep);
                        }
                        $links->pushContent(new Button($label, $url, $class));
                        $last_is_text = true;
                    }
                } else {
                    if (! $this_is_first) {
                        $links->pushContent($sep);
                    }
                    $links->pushContent(new Button($label, $url, $class));
                    $last_is_text = true;
                }
                $this_is_first = false;
            }
        }
        if ($style == 'text') {
            $links->pushcontent(" ] ");
        }
        return $links;
    }
}

// $Log: PrevNext.php,v $
// Revision 1.4  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.3  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.2  2003/01/18 22:01:43  carstenklapp
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
