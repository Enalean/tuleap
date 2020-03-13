<?php
// -*-php-*-
rcs_id('$Id: text2png.php,v 1.13 2004/02/17 12:11:36 rurban Exp $');
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
 * File loading and saving diagnostic messages, to see whether an
 * image was saved to or loaded from the cache and what the path is
 *
 * PHP must be compiled with support for the GD library version 1.6 or
 * later to create PNG image files:
 *
 * ./configure --with-gd
 *
 * See <http://www.php.net/manual/pl/ref.image.php> for more info.
 */
define('TEXT2PNG_DEBUG', true);


class WikiPlugin_text2png extends WikiPlugin
{
    public function getName()
    {
        return "text2png";
    }

    public function getDescription()
    {
        return _("Convert text into a png image using GD.");
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
        global $LANG;
        return array('text' => "Hello WikiWorld!",
                     'l'    => $LANG );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        if (ImageTypes() & IMG_PNG) {
            // we have gd & png so go ahead.
            extract($this->getArgs($argstr, $request));
            return $this->text2png($text, $l);
        } else {
            // we don't have png and/or gd.
            $error_html = _("Sorry, this version of PHP cannot create PNG image files.");
            $link = "http://www.php.net/manual/pl/ref.image.php";
            $error_html .= sprintf(_("See %s"), $link) . ".";
            trigger_error($error_html, E_USER_NOTICE);
            return;
        }
    }

    public function text2png($text, $l)
    {
        /**
         * Basic image creation and caching
         *
         * You MUST delete the image cache yourself in /images if you
         * change the drawing routines!
         */

        $filename = $text . ".png";

        /**
         * FIXME: need something more elegant, and a way to gettext a
         *        different language depending on any individual
         *        user's locale preferences.
         */

        if ($l == "C") {
            $l = "en";
        } //english=C
        $filepath = getcwd() . "/images/$l";

        if (!file_exists($filepath . "/" . $filename)) {
            if (!file_exists($filepath)) {
                $oldumask = umask(0);
                // permissions affected by user the www server is running as
                mkdir($filepath, 0777);
                umask($oldumask);
            }

            // add trailing slash to save some keystrokes later
            $filepath .= "/";

            /**
             * prepare a new image
             *
             * FIXME: needs a dynamic image size depending on text
             *        width and height
             */
            $im = @ImageCreate(150, 50);

            if (empty($im)) {
                $error_html = _("PHP was unable to create a new GD image stream. Read 'lib/plugin/text2png.php' for details.");
                // FIXME: Error manager does not transform URLs passed
                //        through it.
                $link = "http://www.php.net/manual/en/function.imagecreate.php";
                $error_html .= sprintf(_("See %s"), $link) . ".";
                trigger_error($error_html, E_USER_NOTICE);
                return;
            }
            // get ready to draw
            $bg_color = ImageColorAllocate($im, 255, 255, 255);
            $ttfont   = "/System/Library/Frameworks/JavaVM.framework/Versions/1.3.1/Home/lib/fonts/LucidaSansRegular.ttf";

            /* http://download.php.net/manual/en/function.imagettftext.php
             * array imagettftext (int im, int size, int angle, int x, int y,
             *                      int col, string fontfile, string text)
             */

            // draw shadow
            $text_color = ImageColorAllocate($im, 175, 175, 175);
            // shadow is 1 pixel down and 2 pixels right
            ImageTTFText($im, 10, 0, 12, 31, $text_color, $ttfont, $text);

            // draw text
            $text_color = ImageColorAllocate($im, 0, 0, 0);
            ImageTTFText($im, 10, 0, 10, 30, $text_color, $ttfont, $text);

            /**
             * An alternate text drawing method in case ImageTTFText
             * doesn't work.
             **/
            //ImageString($im, 2, 10, 40, $text, $text_color);

            // To dump directly to browser:
            //header("Content-type: image/png");
            //ImagePng($im);

            // to save to file:
            $success = ImagePng($im, $filepath . $filename);
        } else {
            $filepath .= "/";
            $success = 2;
        }

        // create an <img src= tag to show the image!
        $html = HTML();
        if ($success > 0) {
            if (defined('TEXT2PNG_DEBUG')) {
                switch ($success) {
                    case 1:
                        trigger_error(
                            sprintf(
                                _("Image saved to cache file: %s"),
                                $filepath . $filename
                            ),
                            E_USER_NOTICE
                        );
                        break;
                    case 2:
                        trigger_error(
                            sprintf(
                                _("Image loaded from cache file: %s"),
                                $filepath . $filename
                            ),
                            E_USER_NOTICE
                        );
                }
            }
            $url = "images/$l/$filename";
            if (defined('DATA_PATH')) {
                $url = DATA_PATH . "/$url";
            }
            $html->pushContent(HTML::img(array('src' => $url,
                                               'alt' => $text)));
        } else {
            trigger_error(sprintf(
                _("couldn't open file '%s' for writing"),
                $filepath . $filename
            ), E_USER_NOTICE);
        }
        return $html;
    }
}

// $Log: text2png.php,v $
// Revision 1.13  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.12  2003/02/22 19:21:47  dairiki
// If DATA_PATH is not defined (by user in index.php), then use
// relative URLs to data.
//
// Revision 1.11  2003/01/18 22:08:01  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
// For emacs users
// Local Variables:
// mode: php
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
