<?php
// -*-php-*-
rcs_id('$Id: RedirectTo.php,v 1.13 2004/02/17 12:11:36 rurban Exp $');
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
 * Redirect to another page or external uri. Kind of PageAlias.
 * Usage:
 * <?plugin RedirectTo href="http://www.internet-technology.de/fourwins_de.htm" ?>
 *      or  <?plugin RedirectTo page=AnotherPage ?>
 * at the VERY FIRST LINE in the content! Otherwise it will be ignored.
 *
 * Author:  Reini Urban <rurban@x-ray.at>
 *
 * BUGS/COMMENTS:
 * Todo: fix with USE_PATH_INFO = false
 *
 * This plugin could probably result in a lot of confusion, especially when
 * redirecting to external sites.  (Perhaps it can even be used for dastardly
 * purposes?)  Maybe it should be disabled by default.
 *
 * It would be nice, when redirecting to another wiki page, to (as
 * UseModWiki does) add a note to the top of the target page saying
 * something like "(Redirected from SomeRedirectingPage)".
 */
class WikiPlugin_RedirectTo extends WikiPlugin
{
    public function getName()
    {
        return _("RedirectTo");
    }

    public function getDescription()
    {
        return _("Redirects to another url or page.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.13 $"
        );
    }

    public function getDefaultArguments()
    {
        return array( 'href' => '',
                      // 'type' => 'Temp' // or 'Permanent' // so far ignored
                      'page' => false,
                      );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = ($this->getArgs($argstr, $request));

        $href = $args['href'];
        $page = $args['page'];
        if ($href) {
            /*
             * Use quotes on the href argument value, like:
             *   <?plugin RedirectTo href="http://funky.com/a b \" c.htm" ?>
             *
             * Do we want some checking on href to avoid malicious
             * uses of the plugin? Like stripping tags or hexcode.
             */
            $url = preg_replace('/%\d\d/', '', strip_tags($href));
            $thispage = $request->getPage();
            if (! $thispage->get('locked')) {
                return $this->disabled(fmt(
                    "%s is only allowed in locked pages.",
                    _("Redirect to an external url")
                ));
            }
        } elseif ($page) {
            $url = WikiURL(
                $page,
                array('redirectfrom' => $request->getArg('pagename')),
                'abs_path'
            );
        } else {
            return $this->error(fmt(
                "%s or %s parameter missing",
                "'href'",
                "'page'"
            ));
        }

        if ($page == $request->getArg('pagename')) {
            return $this->error(fmt("Recursive redirect to self: '%s'", $url));
        }

        if ($request->getArg('action') != 'browse') {
            return $this->disabled("(action != 'browse')");
        }

        $redirectfrom = $request->getArg('redirectfrom');
        if ($redirectfrom !== false) {
            if ($redirectfrom) {
                return $this->disabled(_("Double redirect not allowed."));
            } else {
                // Got here by following the "Redirected from ..." link
                // on a browse page.
                return $this->disabled(_("Viewing redirecting page."));
            }
        }

        return $request->redirect($url);
    }
}

// $Log: RedirectTo.php,v $
// Revision 1.13  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.12  2004/02/03 09:45:39  rurban
// LDAP cleanup, start of new Pref classes
//
// Revision 1.11  2003/11/22 17:54:50  carstenklapp
// Minor internal change: Removed redundant call to gettext within
// fmt(). (locale make: RedirectTo.php:81: warning: keyword nested in
// keyword arg)
//
// Revision 1.10  2003/02/24 00:40:09  carstenklapp
// PHP's closing tag \?\> within // cvs log comments caused the trailing comments to display as literal text on the PluginManager page.
//
// Revision 1.9  2003/02/21 22:59:00  dairiki
// Add new argument $basepage to WikiPlugin::run() and WikiPluginLoader::expandPI().
// Plugins need to know what page they were invoked from so that they can handle
// relative page links (like [/Subpage]) correctly.  ($request->getArg('pagename')
// is not always the right page to use --- think included pages...)
//
// Many plugins don't need the $basepage, in which case, I think they can just ignore
// the extra argument.  (I don't think PHP will generate any warnings.)
//
//
// Also: deleted <?plugin-head? > code.  It's not needed any more, now that
// we always cache output.
//
// The FrameInclude plugin seems broken now, though I'm not convinced it's
// my fault.  If it is, sorry...   (I'll try to look at it a bit more
// within a few days, to see if I can figure out the problem.)
//
// Revision 1.8  2003/02/16 19:49:18  dairiki
// When redirecting to a page, use an absolute URL.
// This fixes a bug when redirecting from a sub-page (since,
// in that case the redirect happens before the <base> element gets
// sent.)
//
// Revision 1.7  2003/02/15 23:32:56  dairiki
// Usability improvements for the RedirectTo plugin.
//
// (Mostly this applies when using RedirectTo with a page=OtherPage
// argument to redirect to another page in the same wiki.)
//
// (Most of these ideas are stolen verbatim from UseModWiki.)
//
//  o Multiple redirects (PageOne -> PageTwo -> PageThree) not allowed.
//
//  o Redirects are not activated except when action == 'browse'.
//
//  o When redirections are disabled, (hopefully understandable)
//    diagnostics are displayed.
//
//  o A link to the redirecting page is displayed after the title
//    of the target page.  If the user follows this link, redirects
//    are disabled.  This allows for easy editing of the redirecting
//    page.
//
// FIXME: Stylesheets, and perhaps templates other than the defaults
// will probably have to be updated before this works well in other
// styles and/or themes.
//
// Revision 1.6  2003/01/18 22:01:44  carstenklapp
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
