<?php
// -*-php-*-
rcs_id('$Id: SyntaxHighlighter.php,v 1.7 2004/07/08 20:30:07 rurban Exp $');
/**
 Copyright 2004 $ThePhpWikiProgrammingTeam

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
 * The SyntaxHighlighter plugin passes all its arguments through a C++
 * highlighter called "highlight" (available at http://www.andre-simon.de/).
 *
 * @author: alecthomas
 *
 * syntax: See http://www.andre-simon.de/doku/highlight/highlight.html
 * style = ["ansi", "gnu", "kr", "java", "linux"]

<?plugin SyntaxHighlighter syntax=c style=kr
 #include <stdio.h>

 int main() {
 printf("Lalala\n");
 }
?>

 I did not use beautifier, because it used up more than 8M of memory on
 my system and PHP killed it. I'm not sure whether this is a problem
 with my integration, or with beautifier itself.

Fixes by Reini Urban:
  support options: syntax, style, color.
  php version switch
  HIGHLIGHT_DATA_DIR, HIGHLIGHT_EXE
*/
if (!defined('HIGHLIGHT_EXE')) {
    define('HIGHLIGHT_EXE', 'highlight');
}
//define('HIGHLIGHT_EXE','/usr/local/bin/highlight');
//define('HIGHLIGHT_EXE','/home/groups/p/ph/phpwiki/bin/highlight');

// highlight requires two subdirs themes and langDefs somewhere.
// Best by highlight.conf in $HOME, but the webserver user usually
// doesn't have a $HOME
if (!defined('HIGHLIGHT_DATA_DIR')) {
    if (isWindows()) {
        define('HIGHLIGHT_DATA_DIR', 'f:\cygnus\usr\local\share\highlight');
    } else {
        define('HIGHLIGHT_DATA_DIR', '/usr/share/highlight');
    }
}
        //define('HIGHLIGHT_DATA_DIR','/home/groups/p/ph/phpwiki/share/highlight');

class WikiPlugin_SyntaxHighlighter extends WikiPlugin
{
    public function getName()
    {
        return _("SyntaxHighlighter");
    }
    public function getDescription()
    {
        return _("Source code syntax highlighter (via http://www.andre-simon.de)");
    }
    public function managesValidators()
    {
        return true;
    }
    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.7 $"
        );
    }
    public function getDefaultArguments()
    {
        return array(
                     'syntax' => null, // required argument
                     'style'  => null, // optional argument ["ansi", "gnu", "kr", "java", "linux"]
                     'color'  => null, // optional, see highlight/themes
                     'number' => 0,
                     'wrap'   => 0,
                     );
    }
    public function handle_plugin_args_cruft(&$argstr, &$args)
    {
        $this->source = $argstr;
    }

    private function filterThroughCmd($input, $commandLine)
    {
        $descriptorspec = array(
               0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
               1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
               2 => array("pipe", "w"),  // stdout is a pipe that the child will write to
        );

        $process = proc_open("$commandLine", $descriptorspec, $pipes);
        if (is_resource($process)) {
            // $pipes now looks like this:
            // 0 => writeable handle connected to child stdin
            // 1 => readable  handle connected to child stdout
            // 2 => readable  handle connected to child stderr
            fwrite($pipes[0], $input);
            fclose($pipes[0]);
            $buf = "";
            while (!feof($pipes[1])) {
                $buf .= fgets($pipes[1], 1024);
            }
            fclose($pipes[1]);
            $stderr = '';
            while (!feof($pipes[2])) {
                $stderr .= fgets($pipes[2], 1024);
            }
            fclose($pipes[2]);
            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $return_value = proc_close($process);
            if (empty($buf)) {
                printXML($this->error($stderr));
            }
            return $buf;
        }
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        extract($this->getArgs($argstr, $request));
        $source = $this->source;
        if (empty($syntax)) {
            return $this->error(_("Syntax language not specified."));
        }
        if (!empty($source)) {
            $args = "";
            if (defined('HIGHLIGHT_DATA_DIR')) {
                $args .= " --data-dir " . escapeshellarg(HIGHLIGHT_DATA_DIR);
            }
            if ($number != 0) {
                $args .= " -l";
            }
            if ($wrap != 0) {
                $args .= " -V";
            }
            $html = HTML();

            if (!empty($style)) {
                $args .= ' -F ' . escapeshellarg($style);
            }
            $commandLine = HIGHLIGHT_EXE . "$args -q -f -S " . escapeshellarg($syntax);
            $code = $this->filterThroughCmd($source, $commandLine);
            if (empty($code)) {
                return $this->error(fmt("Couldn't start commandline"));
            }
            $pre = HTML::pre(HTML::raw($code));
            $pre->setAttr('class', 'tightenable top bottom');
            $html->pushContent($pre);
            $css = $GLOBALS['WikiTheme']->_CSSlink('', 'highlight.css', '');
            return HTML($css, $html);
        } else {
            return $this->error(fmt("empty source"));
        }
    }
}

// $Log: SyntaxHighlighter.php,v $
// Revision 1.7  2004/07/08 20:30:07  rurban
// plugin->run consistency: request as reference, added basepage.
// encountered strange bug in AllPages (and the test) which destroys ->_dbi
//
// Revision 1.6  2004/06/29 18:47:40  rurban
// use predefined constants, and added sf.net defaults
//
// Revision 1.5  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.4  2004/05/18 14:49:52  rurban
// Simplified strings for easier translation
//
// Revision 1.3  2004/05/14 17:33:12  rurban
// new plugin RecentChanges
//
// Revision 1.2  2004/05/14 15:56:16  rurban
// protect color argument, more error handling, added default css
//
// Revision 1.1  2004/05/14 14:55:52  rurban
// Alec Thomas original plugin, which comes with highlight http://www.andre-simon.de/,
// plus some extensions by Reini Urban
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
