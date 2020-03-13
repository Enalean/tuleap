<?php
// -*-php-*-
rcs_id('$Id: FrameInclude.php,v 1.10 2004/06/14 11:31:39 rurban Exp $');
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
 * FrameInclude:  Displays a url or page in a seperate frame inside our body.
 *
 * Usage:
 *  <?plugin FrameInclude src=http://www.internet-technology.de/fourwins_de.htm ?>
 *  <?plugin FrameInclude page=OtherPage ?>
 *  at the VERY BEGINNING in the content!
 *
 * Author:  Reini Urban <rurban@x-ray.at>, rewrite by Jeff Dairiki <dairiki@dairiki.org>
 *
 * KNOWN ISSUES:
 *
 * This is a dirty hack into the whole system. To display the page as
 * frameset we:
 *
 *  1. Discard any output buffered so far.
 *  2. Recursively call displayPage with magic arguments to generate
 *     the frameset (or individual frame contents.)
 *  3. Exit early.  (So this plugin is usually a no-return.)
 *
 *  In any cases we can now serve only specific templates with the new
 *  frame argument. The whole page is now ?frame=html (before it was
 *  named "top") For the Sidebar theme (or derived from it) we provide
 *  a left frame also, otherwise only top, content and bottom.
 */
class WikiPlugin_FrameInclude extends WikiPlugin
{
    public function getName()
    {
        return _("FrameInclude");
    }

    public function getDescription()
    {
        return _("Displays a url in a seperate frame inside our body. Only one frame allowed.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.10 $"
        );
    }

    public function getDefaultArguments()
    {
        return array( 'src'         => false,       // the src url to include
                      'page'        => false,
                      'name'        => 'content',   // name of our frame
                      'title'       => false,
                      'rows'        => '18%,*,15%', // names: top, $name, bottom
                      'cols'        => '20%,*',     // names: left, $name
                                                    // only useful on Theme "Sidebar"
                      'frameborder' => 1,
                      'marginwidth'  => false,
                      'marginheight' => false,
                      'noresize'    => false,
                      'scrolling'   => 'auto',  // '[ yes | no | auto ]'
                    );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        global $WikiTheme;

        $args = ($this->getArgs($argstr, $request));
        extract($args);

        if ($request->getArg('action') != 'browse') {
            return $this->disabled("(action != 'browse')");
        }
        if (! $request->isGetOrHead()) {
            return $this->disabled("(method != 'GET')");
        }

        if (!$src and $page) {
            if ($page == $request->get('pagename')) {
                return $this->error(sprintf(
                    _("recursive inclusion of page %s"),
                    $page
                ));
            }
            $src = WikiURL($page);
        }
        if (!$src) {
            return $this->error(sprintf(
                _("%s or %s parameter missing"),
                'src',
                'page'
            ));
        }

        // FIXME: How to normalize url's to compare against recursion?
        if ($src == $request->getURLtoSelf()) {
            return $this->error(sprintf(
                _("recursive inclusion of url %s"),
                $src
            ));
        }

        if (($which = $request->getArg('frame'))) {
            // Generate specialized frame output (header, footer, etc...)
            $request->discardOutput();
            displayPage($request, new Template("frame-$which", $request));
            $request->finish(); //noreturn
        }

        $uri_sanitizer = new \Tuleap\Sanitizer\URISanitizer(new Valid_LocalURI(), new Valid_FTPURI());
        $sanitized_src = $uri_sanitizer->sanitizeForHTMLAttribute($src);

        // Generate the outer frameset
        $frame = HTML::frame(array('name' => $name,
                                   'src' => $sanitized_src,
                                   'title' => $title,
                                   'frameborder' => (int) $frameborder,
                                   'scrolling' => (string) $scrolling,
                                   'noresize' => (bool) $noresize,
                                   ));

        if ($marginwidth) {
            $frame->setArg('marginwidth', $marginwidth);
        }
        if ($marginheight) {
            $frame->setArg('marginheight', $marginheight);
        }

        $tokens = array('CONTENT_FRAME' => $frame,
                        'ROWS' => $rows,
                        'COLS' => $cols,
                        'FRAMEARGS' => sprintf('frameborder="%d"', $frameborder),
                        );

        // Produce the frameset.
        $request->discardOutput();
        displayPage($request, new Template('frameset', $request, $tokens));
        $request->finish(); //noreturn
    }
}

// $Log: FrameInclude.php,v $
// Revision 1.10  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.9  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.8  2003/02/26 22:32:06  dairiki
// Wups.  Delete disused cruft.
//
// Revision 1.7  2003/02/26 22:27:19  dairiki
// Fix and refactor FrameInclude plugin (more or less).
//
// (This should now generate valid HTML.  Woohoo!)
//
// The output when using the Sidebar theme is ugly enough that it should
// be considered broken.  (But the Sidebar theme appears pretty broken in
// general right now.)
//
// (Personal comment (not to be taken personally): I must say that I
// remain unconvinced of the usefulness of this plugin.)
//
// Revision 1.6  2003/01/18 21:41:01  carstenklapp
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
