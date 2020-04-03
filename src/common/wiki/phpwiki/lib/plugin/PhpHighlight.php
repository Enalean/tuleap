<?php
// -*-php-*-
rcs_id('$Id: PhpHighlight.php,v 1.9 2004/04/10 07:25:24 rurban Exp $');
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
 * A plugin that runs the highlight_string() function in PHP on it's
 * arguments to pretty-print PHP code.
 *
 * Usage:
 * <?plugin PhpHighlight default='#FF0000' comment='#0000CC'
 * code that should be highlighted
 * ?>
 *
 * You do not have to add '<?php' and '?>' to the code - the plugin
 * does this automatically if you do not set wrap to 0.
 *
 * If you do set wrap to 0, then you'll have to start and stop PHP
 * mode in the source yourself, or you wont see any highlighting. But
 * you cannot use '<?php' and '?>' in the source, because this
 * interferes with PhpWiki, you'll have use '< ?php' and '? >'
 * instead.
 *
 * Author: Martin Geisler <gimpster@gimpster.com>.
 *
 * Added compatibility for PHP < 4.2.0, where the highlight_string()
 * function has no second argument.
 * Added ability to override colors defined in php.ini --Carsten Klapp
 *
 * Known Problems:
 *   <?plugin PhpHighlight
 *   testing[somearray];
 *   testing~[badworkaround~];
 *   ?>
 * will swallow "[somearray]"
 */

class WikiPlugin_PhpHighlight extends WikiPlugin
{
    // Four required functions in a WikiPlugin.

    public function getName()
    {
        return _("PhpHighlight");
    }

    public function getDescription()
    {
        return _("PHP syntax highlighting");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.9 $"
        );
    }

    // Establish default values for each of this plugin's arguments.
    public function getDefaultArguments()
    {
        // TODO: results of ini_get() should be static for multiple
        // invocations of plugin on one WikiPage
        return array('wrap'    => true,
                     'string'  => ini_get("highlight.string"),  //'#00CC00',
                     'comment' => ini_get("highlight.comment"), //'#FF9900',
                     'keyword' => ini_get("highlight.keyword"), //'#006600',
                     'default' => ini_get("highlight.default"), //'#0000CC',
                     'html'    => ini_get("highlight.html")     //'#000000'
                     );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        extract($this->getArgs($argstr, $request));
        $source = $this->source;

        $this->sanify_colors($string, $comment, $keyword, $bg, $default, $html);
        $this->set_colors($string, $comment, $keyword, $bg, $default, $html);

        if ($wrap) {
            /* Wrap with "<?php\n" and "\n?>" required by
             * highlight_string(): */
            $source = "<?php\n" . $source . "\n?>";
        } else {
            $source = str_replace(
                array('< ?php', '? >'),
                array('<?php', '?>'),
                $source
            );
        }

        $str = highlight_string($source, true);

        if ($wrap) {
            /* Remove "<?php\n" and "\n?>" again: */
            $str = str_replace(array('&lt;?php<br />', '?&gt;'), '', $str);
        }

        /**
         * We might have made some empty font tags. (The following
         * str_replace string does not produce results on my system,
         * maybe a php bug? '<font color="$color"></font>')
         */
        foreach (array($string, $comment, $keyword, $bg, $default, $html) as $color) {
            $search = "<font color=\"$color\"></font>";
            $str = str_replace($search, '', $str);
        }

        /* restore default colors in case of multiple invocations of
           this plugin on one page */
        $this->restore_colors();
        return new RawXml($str);
    }

    public function handle_plugin_args_cruft(&$argstr, &$args)
    {
        $this->source = $argstr;
    }

    /**
     * Make sure color argument is valid
     * See http://www.w3.org/TR/REC-html40/types.html#h-6.5
     */
    public function sanify_colors($string, $comment, $keyword, $bg, $default, $html)
    {
        static $html4colors = array("black", "silver", "gray", "white",
                                    "maroon", "red", "purple", "fuchsia",
                                    "green", "lime", "olive", "yellow",
                                    "navy", "blue", "teal", "aqua");
        /* max(strlen("fuchsia"), strlen("#00FF00"), ... ) = 7 */
        static $MAXLEN = 7;
        foreach (array($string, $comment, $keyword, $bg, $default, $html) as $color) {
            $length = strlen($color);
            //trigger_error(sprintf(_("DEBUG: color '%s' is length %d."), $color, $length), E_USER_NOTICE);
            if (
                ($length == 7 || $length == 4) && substr($color, 0, 1) == "#"
                && "#" == preg_replace("/[a-fA-F0-9]/", "", $color)
            ) {
                //trigger_error(sprintf(_("DEBUG: color '%s' appears to be hex."), $color), E_USER_NOTICE);
                // stop checking, ok to go
            } elseif (($length < $MAXLEN + 1) && in_array($color, $html4colors)) {
                //trigger_error(sprintf(_("DEBUG color '%s' appears to be an HTML 4 color."), $color), E_USER_NOTICE);
                // stop checking, ok to go
            } else {
                trigger_error(sprintf(
                    _("Invalid color: %s"),
                    $color
                ), E_USER_NOTICE);
                // FIXME: also change color to something valid like "black" or ini_get("highlight.xxx")
            }
        }
    }

    public function set_colors($string, $comment, $keyword, $bg, $default, $html)
    {
        // set highlight colors
        $this->oldstring = ini_set('highlight.string', $string);
        $this->oldcomment = ini_set('highlight.comment', $comment);
        $this->oldkeyword = ini_set('highlight.keyword', $keyword);
        $this->olddefault = ini_set('highlight.default', $default);
        $this->oldhtml = ini_set('highlight.html', $html);
    }

    public function restore_colors()
    {
        // restore previous default highlight colors
        ini_set('highlight.string', $this->oldstring);
        ini_set('highlight.comment', $this->oldcomment);
        ini_set('highlight.keyword', $this->oldkeyword);
        ini_set('highlight.default', $this->olddefault);
        ini_set('highlight.html', $this->oldhtml);
    }
}

// $Log: PhpHighlight.php,v $
// Revision 1.9  2004/04/10 07:25:24  rurban
// fixed sf bug #928230
//
// Revision 1.8  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.7  2003/01/18 22:01:43  carstenklapp
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
