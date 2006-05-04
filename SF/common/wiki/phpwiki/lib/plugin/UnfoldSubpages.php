<?php // -*-php-*-
rcs_id('$Id: UnfoldSubpages.php 2691 2006-03-02 15:31:51Z guerin $');
/*
 Copyright 2002 $ThePhpWikiProgrammingTeam

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
 * UnfoldSubpages:  Lists the content of all SubPages of the current page.
 *   This is e.g. useful for the CalendarPlugin, to see all entries at once.
 *   Warning: Don't use it with subpages where the RedirectTo plugin is used
 *            or with non-existant sections!
 *	      The section extractor is currently quite unstable.
 * Usage:   <?plugin UnfoldSubpages sortby=-mtime words=50 maxpages=5 ?>
 * Author:  Reini Urban <rurban@x-ray.at>
 */
class WikiPlugin_UnfoldSubpages
extends WikiPlugin
{
    function getName() {
        return _("UnfoldSubpages");
    }

    function getDescription () {
        return _("Includes the content of all SubPages of the current page.");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 2691 $");
    }

    function getDefaultArguments() {
        return array(
            'pagename' => '[pagename]', // default: current page
            //'header'  => '',  // expandable string
            'quiet'   => false, // print no header
            //'sort'    => 'asc', // deprecated: use sortby=+pagename or 
            			//   sortby=-mtime instead,
            'sortby'   => 'pagename', // [+|-]pagename, [+|-]mtime, [+|-]hits
            'limit'    => 0,    
            'pages'    => false,    // deprecated. use maxpages instead
            'maxpages' => false,   // maximum number of pages to include
            'sections' => false,// maximum number of sections per page to
            			//  include
            'smalltitle' => false, // if set, hide transclusion-title,
                                //  just have a small link at the start of 
            			//  the page.
            'words'   => false, // maximum number of words
                                //  per page to include
            'lines'   => false, // maximum number of lines
                                //  per page to include
            'bytes'   => false, // maximum number of bytes
                                //  per page to include
            'section' => false, // this named section per page only
            'sectionhead' => false // when including a named
                                //  section show the heading
            );
    }

    // from IncludePage
    function firstNWordsOfContent($n, $content) {
        $wordcount = 0;
        $new = array( );
        foreach ($content as $line) {
            $words = explode(' ', $line);
            if ($wordcount + count($words) > $n) {
                $new[] = implode(' ', array_slice($words, 0, $n - $wordcount))
                         . sprintf(_("... first %d words"), $n);
                return $new;
            }
            else {
                $wordcount += count($words);
                $new[] = $line;
            }
        }
        return $new;
    }

    //TODO: move this to stdlib.php
    function extractSection ($section, $content, $page, $quiet, $sectionhead) {
        $qsection = preg_replace('/\s+/', '\s+', preg_quote($section, '/'));

        if (preg_match("/ ^(!{1,})\\s*$qsection" // section header
                       . "  \\s*$\\n?"           // possible blank lines
                       . "  ( (?: ^.*\\n? )*? )" // some lines
                       . "  (?= ^\\1 | \\Z)/xm", // sec header (same
                                                 //  or higher level)
                                                 //  (or EOF)
                       implode("\n", $content),
                       $match)) {
            // Strip trailing blanks lines and ---- <hr>s
            $text = preg_replace("/\\s*^-{4,}\\s*$/m", "", $match[2]);
            if ($sectionhead)
                $text = $match[1] . $section ."\n". $text;
            return explode("\n", $text);
        }
        if ($quiet)
            $mesg = $page ." ". $section;
        else
            $mesg = $section;
        return array(sprintf(_("<%s: no such section>"), $mesg));
    }

    function run($dbi, $argstr, &$request, $basepage) {
        include_once('lib/BlockParser.php');

        $args = $this->getArgs($argstr, $request);
        extract($args);
        $subpages = explodePageList($pagename . SUBPAGE_SEPARATOR . '*',false,$sortby,$limit);
        if (! $subpages ) {
            return $this->error(_("The current page has no subpages defined."));
        }           
        $content = HTML();
        if ($maxpages) {
          $subpages = array_slice ($subpages, 0, $maxpages);
        }
        foreach ($subpages as $page) {
            // A page cannot include itself. Avoid doublettes.
            static $included_pages = array();
            if (in_array($page, $included_pages)) {
                $content->pushContent(HTML::p(sprintf(_("recursive inclusion of page %s ignored"),
                                                      $page)));
                continue;
            }
            // trap any remaining nonexistant subpages
            if ($dbi->isWikiPage($page)) {
                $p = $dbi->getPage($page);
                $r = $p->getCurrentRevision();
                $c = $r->getContent();

                if ($section)
                    $c = $this->extractSection($section, $c, $page, $quiet,
                                               $sectionhead);
                if ($lines)
                    $c = array_slice($c, 0, $lines)
                        . sprintf(_(" ... first %d lines"), $bytes);
                if ($words)
                    $c = $this->firstNWordsOfContent($words, $c);
                if ($bytes) {
                    if (strlen($c) > $bytes)
                        $c = substr($c, 0, $bytes)
                            . sprintf(_(" ... first %d bytes"), $bytes);
                }

                array_push($included_pages, $page);
                if ($smalltitle) {
                    $pname = array_pop(explode("/", $page)); // get last subpage name
                    // Use _("%s: %s") instead of .": ". for French punctuation
                    $ct = TransformText(sprintf(_("%s: %s"), "[$pname|$page]",
                                                implode("\n", $c)),
                                        $r->get('markup'), $page);
                }
                else {
                    $ct = TransformText(implode("\n", $c), $r->get('markup'), $page);
                }
                array_pop($included_pages);
                if (! $smalltitle) {
                    $content->pushContent(HTML::p(array('class' => $quiet ?
                                                        '' : 'transclusion-title'),
                                                  fmt("Included from %s:",
                                                      WikiLink($page))));
                }
                $content->pushContent(HTML(HTML::div(array('class' => $quiet ?
                                                           '' : 'transclusion'),
                                                     false, $ct)));
            }
        }
        return $content;
    }
};

// $Log$
// Revision 1.13  2004/03/12 15:48:08  rurban
// fixed explodePageList: wrong sortby argument order in UnfoldSubpages
// simplified lib/stdlib.php:explodePageList
//
// Revision 1.12  2004/02/22 23:20:33  rurban
// fixed DumpHtmlToDir,
// enhanced sortby handling in PageList
//   new button_heading th style (enabled),
// added sortby and limit support to the db backends and plugins
//   for paging support (<<prev, next>> links on long lists)
//
// Revision 1.11  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.10  2004/01/27 12:10:45  rurban
// fixed UnfoldSubpages and added docs.
// new arguments: pagename, maxpages
// some arguments are deprecated: sort (use sortby), pages (use maxpages)
// fixed sortby, added docs
//
// Revision 1.9  2004/01/26 09:18:00  rurban
// * changed stored pref representation as before.
//   the array of objects is 1) bigger and 2)
//   less portable. If we would import packed pref
//   objects and the object definition was changed, PHP would fail.
//   This doesn't happen with an simple array of non-default values.
// * use $prefs->retrieve and $prefs->store methods, where retrieve
//   understands the interim format of array of objects also.
// * simplified $prefs->get() and fixed $prefs->set()
// * added $user->_userid and class '_WikiUser' portability functions
// * fixed $user object ->_level upgrading, mostly using sessions.
//   this fixes yesterdays problems with loosing authorization level.
// * fixed WikiUserNew::checkPass to return the _level
// * fixed WikiUserNew::isSignedIn
// * added explodePageList to class PageList, support sortby arg
// * fixed UserPreferences for WikiUserNew
// * fixed WikiPlugin for empty defaults array
// * UnfoldSubpages: added pagename arg, renamed pages arg,
//   removed sort arg, support sortby arg
//
// Revision 1.8  2004/01/25 10:52:16  rurban
// added sortby support to explodePageList() and UnfoldSubpages
// fixes [ 758044 ] Plugin UnfoldSubpages does not sort (includes fix)
//
// Revision 1.7  2003/02/21 04:12:06  dairiki
// Minor fixes for new cached markup.
//
// Revision 1.6  2003/02/11 09:34:34  rurban
// fix by Steven D. Brewer <sbrewer@bio.umass.edu> to respect the $pages argument
//
// Revision 1.5  2003/01/18 22:11:44  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
//
// Revision 1.4  2003/01/05 02:37:30  carstenklapp
// New: Implemented 'smalltitle' argument and date sorting fix from
// Cuthbert Cat's sf patch 655095. Added getVersion & getDescription;
// code rewrapping.
//
// Revision 1.3  2003/01/04 22:46:07  carstenklapp
// Workaround: when page has no subpages avoid include of nonexistant pages.
//

// KNOWN ISSUES:
// - line & word limit doesn't work if the included page itself
//   includes a plugin

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
