<?php
// -*-php-*-
rcs_id('$Id: Transclude.php,v 1.9 2004/06/14 11:31:39 rurban Exp $');
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
 * Transclude:  Include an external web page within the body of a wiki page.
 *
 * Usage:
 *  <?plugin Transclude
 *           src=http://www.internet-technology.de/fourwins_de.htm
 *  ?>
 *
 * @see http://www.cs.tut.fi/~jkorpela/html/iframe.html
 *
 * KNOWN ISSUES
 *  Will only work if the browser supports <iframe>s (which is a recent,
 *  but standard tag)
 *
 *  The auto-vertical resize javascript code only works if the transcluded
 *  page comes from the PhpWiki server.  Otherwise (due to "tainting"
 *  security checks in JavaScript) I can't figure out how to deduce the
 *  height of the transcluded page via JavaScript... :-/
 *
 *  Sometimes the auto-vertical resize code doesn't seem to make the iframe
 *  quite big enough --- the scroll bars remain.  Not sure why.
 */
class WikiPlugin_Transclude extends WikiPlugin
{
    public function getName()
    {
        return _("Transclude");
    }

    public function getDescription()
    {
        return _("Include an external web page within the body of a wiki page.");
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
        return array( 'src'     => false, // the src url to include
                      'height'  => 450 // height of the iframe
                    );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        global $WikiTheme;

        $args = ($this->getArgs($argstr, $request));
        extract($args);

        if (!$src) {
            return $this->error(fmt("%s parameter missing", "'src'"));
        }
        // FIXME: Better recursion detection.
        // FIXME: Currently this doesnt work at all.
        if ($src == $request->getURLtoSelf()) {
            return $this->error(fmt("recursive inclusion of url %s", $src));
        }

        if (! IsSafeURL($src)) {
            return $this->error(_("Bad url in src: remove all of <, >, \""));
        }

        $uri_sanitizer = new \Tuleap\Sanitizer\URISanitizer(new Valid_LocalURI(), new Valid_FTPURI());
        $sanitized_src = $uri_sanitizer->sanitizeForHTMLAttribute($src);

        $params = array('title' => _("Transcluded page"),
                        'src' => $sanitized_src,
                        'width' => "100%",
                        'height' => $height,
                        'marginwidth' => 0,
                        'marginheight' => 0,
                        'class' => 'transclude',
                        "onload" => "adjust_iframe_height(this);");

        $noframe_msg[] = fmt("See: %s", HTML::a(array('href' => $sanitized_src), $src));

        $noframe_msg = HTML::div(
            array('class' => 'transclusion'),
            HTML::p(array(), $noframe_msg)
        );

        $iframe = HTML::div(HTML::iframe($params, $noframe_msg));

        /* This doesn't work very well...  maybe because CSS screws up NS4 anyway...
        $iframe = new HtmlElement('ilayer', array('src' => $src), $iframe);
        */

        return HTML(
            HTML::p(
                array('class' => 'transclusion-title'),
                fmt("Transcluded from %s", LinkURL($sanitized_src))
            ),
            $this->_js(),
            $iframe
        );
    }

    /**
     * Produce our javascript.
     *
     * This is used to resize the iframe to fit the content.
     * Currently it only works if the transcluded document comes
     * from the same server as the wiki server.
     *
     * @access private
     */
    public function _js()
    {
        static $seen = false;

        if ($seen) {
            return '';
        }
        $seen = true;

        return JavaScript('
          function adjust_iframe_height(frame) {
            var content = frame.contentDocument;
            try {
                frame.height = content.height + 2 * frame.marginHeight;
            }
            catch (e) {
              // Can not get content.height unless transcluded doc
              // is from the same server...
              return;
            }
          }

          window.addEventListener("resize", function() {
            f = this.document.body.getElementsByTagName("iframe");
            for (var i = 0; i < f.length; i++)
              adjust_iframe_height(f[i]);
          }, false);
          ');
    }
}

// $Log: Transclude.php,v $
// Revision 1.9  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.8  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.7  2003/02/27 22:47:27  dairiki
// New functions in HtmlElement:
//
// JavaScript($js)
//    Helper for generating javascript.
//
// IfJavaScript($if_content, $else_content)
//    Helper for generating
//       <script>document.write('...')</script><noscript>...</noscript>
//    constructs.
//
// Revision 1.6  2003/02/25 05:45:34  carstenklapp
// Added "See: " in front of url, so for browsers that do not support
// <iframe> at least there is an indication to the user that this
// plugin is actually doing something while at the same time without
// being (subjectively) too disruptive to page content.
//
// Revision 1.5  2003/02/24 14:34:44  carstenklapp
// Added iframe title (bobby.org accessibility guidelines).
// Simplified output for non-iframe and non-visual browsers (as suggested
// by http://www.uwosh.edu/programs/accessibility/tutorial.html).
//
// Revision 1.4  2003/01/18 22:08:01  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
