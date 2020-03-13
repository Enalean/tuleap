<?php
// -*-php-*-
rcs_id('$Id: IncludePage.php,v 1.27 2004/11/17 20:07:18 rurban Exp $');
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


/**
 * IncludePage:  include text from another wiki page in this one
 * usage:   <?plugin IncludePage page=OtherPage rev=6 quiet=1 words=50 lines=6?>
 * author:  Joe Edelman <joe@orbis-tertius.net>
 */

class WikiPlugin_IncludePage extends WikiPlugin
{
    public function getName()
    {
        return _("IncludePage");
    }

    public function getDescription()
    {
        return _("Include text from another wiki page.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.27 $"
        );
    }

    public function getDefaultArguments()
    {
        return array( 'page'    => false, // the page to include
                      'rev'     => false, // the revision (defaults to most recent)
                      'quiet'   => false, // if set, inclusion appears as normal content
                      'words'   => false, // maximum number of words to include
                      'lines'   => false, // maximum number of lines to include
                      'section' => false, // include a named section
                      'sectionhead' => false // when including a named section show the heading
                      );
    }

    public function getWikiPageLinks($argstr, $basepage)
    {
        extract($this->getArgs($argstr));

        if (isset($page) && $page) {
            // Expand relative page names.
            $page = new WikiPageName($page, $basepage);
        }
        if (!isset($page) or !$page or !$page->name) {
            return false;
        }
        return array($page->name);
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        extract($this->getArgs($argstr, $request));
        if ($page) {
            // Expand relative page names.
            $page = new WikiPageName($page, $basepage);
            $page = $page->name;
        }
        if (!$page) {
            return $this->error(_("no page specified"));
        }

        // A page can include itself once (this is needed, e.g.,  when editing
        // TextFormattingRules).
        static $included_pages = array();
        if (in_array($page, $included_pages)) {
            return $this->error(sprintf(
                _("recursive inclusion of page %s"),
                $page
            ));
        }

        $p = $dbi->getPage($page);
        if ($rev) {
            $r = $p->getRevision($rev);
            if (!$r) {
                return $this->error(sprintf(
                    _("%s(%d): no such revision"),
                    $page,
                    $rev
                ));
            }
        } else {
            $r = $p->getCurrentRevision();
        }
        $c = $r->getContent();

        if ($section) {
            $c = extractSection($section, $c, $page, $quiet, $sectionhead);
        }
        if ($lines) {
            $c = array_slice($c, 0, $lines);
        }
        if ($words) {
            $c = firstNWordsOfContent($words, $c);
        }

        array_push($included_pages, $page);

        include_once('lib/BlockParser.php');
        $content = TransformText(implode("\n", $c), $r->get('markup'), $page);

        array_pop($included_pages);

        if ($quiet) {
            return $content;
        }

        return HTML(
            HTML::p(
                array('class' => 'transclusion-title'),
                fmt("Included from %s", WikiLink($page))
            ),
            HTML::div(
                array('class' => 'transclusion'),
                false,
                $content
            )
        );
    }

    /**
     * handles the arguments: section, sectionhead, lines, words, bytes,
     * for UnfoldSubpages, IncludePage, ...
     */
    public function extractParts($c, $pagename, $args)
    {
        extract($args);

        if ($section) {
            $c = extractSection(
                $section,
                $c,
                $pagename,
                $quiet,
                $sectionhead
            );
        }
        if ($lines) {
            $c = array_slice($c, 0, $lines);
            $c[] = sprintf(_(" ... first %d lines"), $bytes);
        }
        if ($words) {
            $c = firstNWordsOfContent($words, $c);
        }
        if ($bytes) {
            $ct = implode("\n", $c); // one string
            if (strlen($ct) > $bytes) {
                $ct = substr($c, 0, $bytes);
                $c = array($ct, sprintf(_(" ... first %d bytes"), $bytes));
            }
        }
        $ct = implode("\n", $c); // one string
        return $ct;
    }
}

// This is an excerpt from the css file I use:
//
// .transclusion-title {
//   font-style: oblique;
//   font-size: 0.75em;
//   text-decoration: underline;
//   text-align: right;
// }
//
// DIV.transclusion {
//   background: lightgreen;
//   border: thin;
//   border-style: solid;
//   padding-left: 0.8em;
//   padding-right: 0.8em;
//   padding-top: 0px;
//   padding-bottom: 0px;
//   margin: 0.5ex 0px;
// }

// KNOWN ISSUES:
// - line & word limit doesn't work if the included page itself
//   includes a plugin


// $Log: IncludePage.php,v $
// Revision 1.27  2004/11/17 20:07:18  rurban
// just whitespace
//
// Revision 1.26  2004/09/25 16:35:09  rurban
// use stdlib firstNWordsOfContent, extractSection
//
// Revision 1.25  2004/07/08 20:30:07  rurban
// plugin->run consistency: request as reference, added basepage.
// encountered strange bug in AllPages (and the test) which destroys ->_dbi
//
// Revision 1.24  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.23  2003/03/25 21:01:52  dairiki
// Remove debugging cruft.
//
// Revision 1.22  2003/03/13 18:57:56  dairiki
// Hack so that (when using the IncludePage plugin) the including page shows
// up in the BackLinks of the included page.
//
// Revision 1.21  2003/02/21 04:12:06  dairiki
// Minor fixes for new cached markup.
//
// Revision 1.20  2003/01/18 21:41:02  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
