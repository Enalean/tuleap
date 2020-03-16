<?php
// -*-php-*-
rcs_id('$Id: OldStyleTable.php,v 1.11 2005/09/14 05:56:21 rurban Exp $');
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
 * OldStyleTable: Layout tables using the old table style.
 *
 * Usage:
 * <pre>
 *  <?plugin OldStyleTable border||=0 summary=""
 *  ||  __Name__               |v __Cost__   |v __Notes__
 *  | __First__   | __Last__
 *  |> Jeff       |< Dairiki   |^  Cheap     |< Not worth it
 *  |> Marco      |< Polo      | Cheaper     |< Not available
 *  ?>
 * </pre>
 *
 * Note that multiple <code>|</code>'s lead to spanned columns,
 * and <code>v</code>'s can be used to span rows.  A <code>&gt;</code>
 * generates a right justified column, <code>&lt;</code> a left
 * justified column and <code>^</code> a centered column
 * (which is the default.)
 */

class WikiPlugin_OldStyleTable extends WikiPlugin
{
    public function getName()
    {
        return _("OldStyleTable");
    }

    public function getDescription()
    {
        return _("Layout tables using the old markup style.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.11 $"
        );
    }

    public function getDefaultArguments()
    {
        return array(
                     'caption'     => '',
                     'cellpadding' => '1',
                     'cellspacing' => '1',
                     'border'      => '1',
                     'summary'     => '',
                     );
    }

    public function handle_plugin_args_cruft($argstr, $args)
    {
        return;
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        global $WikiTheme;
        include_once('lib/InlineParser.php');

        $args = $this->getArgs($argstr, $request);
        $default = $this->getDefaultArguments();
        foreach (array('cellpadding','cellspacing','border') as $arg) {
            if (!is_numeric($args[$arg])) {
                $args[$arg] = $default[$arg];
            }
        }
        $lines = preg_split('/\s*?\n\s*/', $argstr);
        $table_args = array();
        $default_args = array_keys($default);
        foreach ($default_args as $arg) {
            if ($args[$arg] == '' and $default[$arg] == '') {
                continue;            // ignore '' arguments
            }
            if ($arg == 'caption') {
                $caption = $args[$arg];
            } else {
                $table_args[$arg] = $args[$arg];
            }
        }
        $table = HTML::table($table_args);
        if (!empty($caption)) {
            $table->pushContent(HTML::caption(array('valign' => 'top'), $caption));
        }
        if (preg_match("/^\s*(cellpadding|cellspacing|border|caption|summary)/", $lines[0])) {
            $lines[0] = '';
        }
        foreach ($lines as $line) {
            if (!$line) {
                continue;
            }
            if (strstr($line, "=")) {
                $tmp = explode("=", $line);
                if (in_array(trim($tmp[0]), $default_args)) {
                    continue;
                }
            }
            if ($line[0] != '|') {
                // bogus error if argument
                trigger_error(sprintf(_("Line %s does not begin with a '|'."), $line), E_USER_WARNING);
            } else {
                $table->pushContent($this->_parse_row($line, $basepage));
            }
        }

        return $table;
    }

    public function _parse_row($line, $basepage)
    {
        $brkt_link = "\\[ .*? [^]\s] .*? \\]";
        $cell_content  = "(?: [^[] | " . ESCAPE_CHAR . "\\[ | $brkt_link )*?";

        preg_match_all(
            "/(\\|+) (v*) ([<>^]?) \s* ($cell_content) \s* (?=\\||\$)/x",
            $line,
            $matches,
            PREG_SET_ORDER
        );

        $row = HTML::tr();

        foreach ($matches as $m) {
            $attr = array();

            if (strlen($m[1]) > 1) {
                $attr['colspan'] = strlen($m[1]);
            }
            if (strlen($m[2]) > 0) {
                $attr['rowspan'] = strlen($m[2]) + 1;
            }

            if ($m[3] == '^') {
                $attr['align'] = 'center';
            } elseif ($m[3] == '>') {
                $attr['align'] = 'right';
            } else {
                $attr['align'] = 'left';
            }

            // Assume new-style inline markup.
            $content = TransformInline($m[4], 2.0, $basepage);

            $row->pushContent(HTML::td(
                $attr,
                HTML::raw('&nbsp;'),
                $content,
                HTML::raw('&nbsp;')
            ));
        }
        return $row;
    }
}

// $Log: OldStyleTable.php,v $
// Revision 1.11  2005/09/14 05:56:21  rurban
// fixed OldStyleTables plugin with args
//
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
// Revision 1.8  2004/01/24 23:37:08  rurban
// Support more options: caption (seperate tag), border, summary, cellpadding,
// cellspacing
// Fixes some errors from the version from the mailinglist.
//
// Revision 1.7  2003/02/21 23:00:35  dairiki
// Fix SF bug #676309.
//
// Also fix new bugs introduced with cached markup changes.
//
// Revision 1.6  2003/02/21 04:12:06  dairiki
// Minor fixes for new cached markup.
//
// Revision 1.5  2003/01/18 21:48:59  carstenklapp
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
