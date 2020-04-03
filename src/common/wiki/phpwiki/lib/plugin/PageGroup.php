<?php
// -*-php-*-
rcs_id('$Id: PageGroup.php,v 1.9 2004/09/25 16:35:09 rurban Exp $');
/**
 Copyright 1999,2000,2001,2002,2004 $ThePhpWikiProgrammingTeam

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
 * Usage:
 *
 * <?plugin PageGroup parent=MyTableOfContents ?>
 *
 * <?plugin PageGroup
 *          parent=MyTableOfContents
 *          label="Visit more pages in MyTableOfContents"
 * ?>
 *
 * <?plugin PageGroup parent=MyTableOfContents section=PartTwo loop=true ?>
 *
 * <?plugin PageGroup parent=MyTableOfContents loop=1 ?>
 *
 *
 * Updated to use new HTML(). It mostly works, but it's still a giant hackish mess.
 */
class WikiPlugin_PageGroup extends WikiPlugin
{
    public function getName()
    {
        return _("PageGroup");
    }

    public function getDescription()
    {
        return sprintf(_("PageGroup for %s"), '[pagename]');
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.9 $"
        );
    }

    public function getDefaultArguments()
    {
        return array(
                     'parent'  => '',
                     'rev'     => false,
                     'section' => _("Contents"),
                     'label'   => '',
                     'loop'    => false,
                     );
    }

    // Stolen from IncludePage.php
    public function extractGroupSection($section, $content, $page)
    {
        $qsection = preg_replace('/\s+/', '\s+', preg_quote($section, '/'));
        if (
            preg_match(
                "/ ^(!{1,})\\s*$qsection" // section header
                       . "  \\s*$\\n?"           // possible blank lines
                       . "  ( (?: ^.*\\n? )*? )" // some lines
                       . "  (?= ^\\1 | \\Z)/xm", // sec header (same or higher level) (or EOF)
                implode("\n", $content),
                $match
            )
        ) {
            $result = array();
            //FIXME: return list of Wiki_Pagename objects
            foreach (explode("\n", $match[2]) as $line) {
                $text = trim($line);
                // Strip trailing blanks lines and ---- <hr>s
                $text = preg_replace("/\\s*^-{4,}\\s*$/", "", $text);
                // Strip leading list chars: * or #
                $text = preg_replace("/^[\*#]+\s*(\S.+)$/", "\\1", $text);
                // Strip surrounding []
                // FIXME: parse [ name | link ]
                $text = preg_replace("/^\[\s*(\S.+)\s*\]$/", "\\1", $text);
                if (!empty($text)) {
                    $result[] = $text;
                }
            }
            return $result;
        }
        return array(sprintf(_("<%s: no such section>"), $page . " " . $section));
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        extract($args);
        $html = "";
        if (empty($parent)) {
            // FIXME: WikiPlugin has no way to report when
            // required args are missing?
            $error_text = fmt(
                "%s: %s",
                "WikiPlugin_" . $this->getName(),
                $error_text
            );
            $error_text .= " " . sprintf(_("A required argument '%s' is missing."), 'parent');
            $html = $error_text;
            return $html;
        }
        $directions = array ('next'     => _("Next"),
                             'previous' => _("Previous"),
                             'contents' => _("Contents"),
                             'first'    => _("First"),
                             'last'     => _("Last")
                             );

        global $WikiTheme;
        $sep = $WikiTheme->getButtonSeparator();
        if (!$sep) {
            $sep = " | "; // force some kind of separator
        }

        // default label
        if (!$label) {
            $label = $WikiTheme->makeLinkButton($parent);
        }

        // This is where the list extraction occurs from the named
        // $section on the $parent page.

        $p = $dbi->getPage($parent);
        if ($rev) {
            $r = $p->getRevision($rev);
            if (!$r) {
                $this->error(sprintf(
                    _("%s(%d): no such revision"),
                    $parent,
                    $rev
                ));
                return '';
            }
        } else {
            $r = $p->getCurrentRevision();
        }

        $c = $r->getContent();
        $c = $this->extractGroupSection($section, $c, $parent);

        $pagename = $request->getArg('pagename');

        // The ordered list of page names determines the page
        // ordering. Right now it doesn't work with a WikiList, only
        // normal lines of text containing the page names.

        $thispage = array_search($pagename, $c);

        $go = array ('previous','next');
        $links = HTML();
        $links->pushcontent($label);
        $links->pushcontent(" [ "); // an experiment
        $lastindex = count($c) - 1; // array is 0-based, count is 1-based!

        foreach ($go as $go_item) {
            //yuck this smells, needs optimization.
            if ($go_item == 'previous') {
                if ($loop) {
                    if ($thispage == 0) {
                        $linkpage  = $c[$lastindex];
                    } else {
                        $linkpage  = $c[$thispage - 1];
                    }
                    // mind the French : punctuation
                    $text = fmt(
                        "%s: %s",
                        $directions[$go_item],
                        $WikiTheme->makeLinkButton($linkpage)
                    );
                    $links->pushcontent($text);
                    $links->pushcontent($sep); // this works because
                                               // there are only 2 go
                                               // items, previous,next
                } else {
                    if ($thispage == 0) {
                        // skip it
                    } else {
                        $linkpage  = $c[$thispage - 1];
                        $text = fmt(
                            "%s: %s",
                            $directions[$go_item],
                            $WikiTheme->makeLinkButton($linkpage)
                        );
                        $links->pushcontent($text);
                        $links->pushcontent($sep); //this works
                                                   //because there are
                                                   //only 2 go items,
                                                   //previous,next
                    }
                }
            } elseif ($go_item == 'next') {
                if ($loop) {
                    if ($thispage == $lastindex) {
                        $linkpage  = $c[1];
                    } else {
                        $linkpage  = $c[$thispage + 1];
                    }
                    $text = fmt(
                        "%s: %s",
                        $directions[$go_item],
                        $WikiTheme->makeLinkButton($linkpage)
                    );
                } else {
                    if ($thispage == $lastindex) {
                        // skip it
                    } else {
                        $linkpage = $c[$thispage + 1];
                        $text = fmt(
                            "%s: %s",
                            $directions[$go_item],
                            $WikiTheme->makeLinkButton($linkpage)
                        );
                    }
                }
                $links->pushcontent($text);
            }
        }
        $links->pushcontent(" ] "); // an experiment
        return $links;
    }
}

// $Log: PageGroup.php,v $
// Revision 1.9  2004/09/25 16:35:09  rurban
// use stdlib firstNWordsOfContent, extractSection
//
// Revision 1.8  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.7  2004/05/03 15:53:20  rurban
// Support [] links, but no [name|page] links yet
// Support subpages
//
// Revision 1.6  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.5  2003/01/18 21:49:00  carstenklapp
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
