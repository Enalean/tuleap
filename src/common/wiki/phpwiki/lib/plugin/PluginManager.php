<?php
// -*-php-*-
rcs_id('$Id: PluginManager.php,v 1.19 2005/10/12 06:15:25 rurban Exp $');
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

// Set this to true if you don't want regular users to view this page.
// So far there are no known security issues.
define('REQUIRE_ADMIN', false);

class WikiPlugin_PluginManager extends WikiPlugin
{
    public function getName()
    {
        return _("PluginManager");
    }

    public function getDescription()
    {
        return _("Description: Provides a list of plugins on this wiki.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.19 $"
        );
    }

    public function getDefaultArguments()
    {
        return array('info' => 'args');
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        extract($this->getArgs($argstr, $request));

        $h = HTML();
        $this->_generatePageheader($info, $h);

        if (! REQUIRE_ADMIN || $request->_user->isadmin()) {
            $h->pushContent(HTML::h2(_("Plugins")));

            $table = HTML::table(array('class' => "pagelist"));
            $this->_generateColgroups($info, $table);
            $this->_generateColheadings($info, $table);
            $this->_generateTableBody($info, $dbi, $request, $table);
            $h->pushContent($table);

            //$h->pushContent(HTML::h2(_("Disabled Plugins")));
        } else {
            $h->pushContent(fmt(
                "You must be an administrator to %s.",
                _("use this plugin")
            ));
        }
        return $h;
    }

    public function _generatePageheader(&$info, &$html)
    {
        $html->pushContent(HTML::p($this->getDescription()));
    }

    public function _generateColgroups(&$info, &$table)
    {
        // specify last two column widths
        $colgroup = HTML::colgroup();
        $colgroup->pushContent(HTML::col(array('width' => '0*')));
        $colgroup->pushContent(HTML::col(array('width' => '0*',
                                               'align' => 'right')));
        $colgroup->pushContent(HTML::col(array('width' => '9*')));
        if ($info == 'args') {
            $colgroup->pushContent(HTML::col(array('width' => '2*')));
        }
        $table->pushcontent($colgroup);
    }

    public function _generateColheadings(&$info, &$table)
    {
        // table headings
        $tr = HTML::tr();
        $headings = array(_("Plugin"), _("Version"), _("Description"));
        if ($info == 'args') {
            $headings[] = _("Arguments");
        }
        foreach ($headings as $title) {
            $tr->pushContent(HTML::td($title));
        }
        $table->pushContent(HTML::thead($tr));
    }

    public function _generateTableBody(&$info, &$dbi, &$request, &$table)
    {
        $plugin_dir = 'lib/plugin';
        if (defined('PHPWIKI_DIR')) {
            $plugin_dir = PHPWIKI_DIR . "/$plugin_dir";
        }
        $pd = new fileSet($plugin_dir, '*.php');
        $plugins = $pd->getFiles();
        unset($pd);
        sort($plugins);

        // table body
        $tbody = HTML::tbody();
        $row_no = 0;

        $w = new WikiPluginLoader;
        foreach ($plugins as $pluginName) {
            // instantiate a plugin
            $pluginName = str_replace(".php", "", $pluginName);
            $temppluginclass = "<? plugin $pluginName ?>"; // hackish
            $p = $w->getPlugin($pluginName, false); // second arg?
            // trap php files which aren't WikiPlugin~s
            if (!strtolower(substr(get_parent_class($p), 0, 10)) == 'wikiplugin') {
                // Security: Hide names of extraneous files within
                // plugin dir from non-admins.
                if ($request->_user->isAdmin()) {
                    trigger_error(sprintf(
                        _("%s does not appear to be a WikiPlugin."),
                        $pluginName . ".php"
                    ));
                }
                continue; // skip this non WikiPlugin file
            }
            $desc = $p->getDescription();
            $ver = $p->getVersion();
            $arguments = $p->getArgumentsDescription();
            unset($p); //done querying plugin object, release from memory

            // This section was largely improved by Pierrick Meignen:
            // make a link if an actionpage exists
            $pluginNamelink = $pluginName;
            $pluginDocPageName = $pluginName . "Plugin";

            $pluginDocPageNamelink = false;
            $localizedPluginName = '';
            $localizedPluginDocPageName = '';

            if ($GLOBALS['LANG'] != "en") {
                if (_($pluginName) != $pluginName) {
                    $localizedPluginName = _($pluginName);
                }
                if ($localizedPluginName && $dbi->isWikiPage($localizedPluginName)) {
                    $pluginDocPageNamelink = WikiLink($localizedPluginName, 'if_known');
                }

                if (_($pluginDocPageName) != $pluginDocPageName) {
                    $localizedPluginDocPageName = _($pluginDocPageName);
                }
                if ($localizedPluginDocPageName &&
                   $dbi->isWikiPage($localizedPluginDocPageName)) {
                    $pluginDocPageNamelink =
                    WikiLink($localizedPluginDocPageName, 'if_known');
                }
            } else {
                $pluginNamelink = WikiLink($pluginName, 'if_known');

                if ($dbi->isWikiPage($pluginDocPageName)) {
                    $pluginDocPageNamelink = WikiLink($pluginDocPageName, 'if_known');
                }
            }

            // highlight alternate rows
            $row_no++;
            $group = (int) ($row_no / 1); //_group_rows
            $class = ($group % 2) ? 'evenrow' : 'oddrow';
            // generate table row
            $tr = HTML::tr(array('class' => $class));
            if ($pluginDocPageNamelink) {
                // plugin has a description page 'PluginName' . 'Plugin'
                $tr->pushContent(HTML::td(
                    $pluginNamelink,
                    HTML::br(),
                    $pluginDocPageNamelink
                ));
                $pluginDocPageNamelink = false;
            } else {
                // plugin just has an actionpage
                $tr->pushContent(HTML::td($pluginNamelink));
            }
            $tr->pushContent(HTML::td($ver), HTML::td($desc));
            if ($info == 'args') {
                // add Arguments column
                $style = array('style'
                               => 'font-family:monospace;font-size:smaller');
                $tr->pushContent(HTML::td($style, $arguments));
            }
            $tbody->pushContent($tr);
        }
        $table->pushContent($tbody);
    }
}

// $Log: PluginManager.php,v $
// Revision 1.19  2005/10/12 06:15:25  rurban
// just aesthetics
//
// Revision 1.18  2005/09/26 06:38:00  rurban
// use the new method
//
// Revision 1.17  2005/01/25 06:58:22  rurban
// reformatting
//
// Revision 1.16  2004/06/04 20:32:54  rurban
// Several locale related improvements suggested by Pierrick Meignen
// LDAP fix by John Cole
// reanable admin check without ENABLE_PAGEPERM in the admin plugins
//
// Revision 1.15  2004/05/25 13:17:12  rurban
// fixed Fatal error: Call to a member function on a non-object in PluginManager.php on line 222
//
// Revision 1.14  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.13  2004/01/25 03:58:44  rurban
// use stdlib:isWikiWord()
//
// Revision 1.12  2004/01/04 18:14:49  wainstead
// Added "Description:" to the beginning of the description string, so
// the plugin plays nice with surrounding text.
//
// Revision 1.11  2003/12/10 01:01:24  carstenklapp
// New features: Also show plugin pages for localized variants.
// Gracefully handle broken plugins in the plugins folder (such as other
// lingering php files).
//
// Bugfix: Cleaned up Php warnings related to oddities of UserPreference
// plugin (whose default value contains an array).
//
// Internal changes: Gave GoodVariableNames to the nightmarish
// ones. Simplified some code with WikiLink 'if_known'.
//
// Revision 1.10  2003/11/30 18:23:48  carstenklapp
// Code housekeeping: PEAR coding standards reformatting only.
//
// Revision 1.9  2003/11/19 00:02:42  carstenklapp
// Include found locale-specific pages for the current (non-English)
// locale.
//
// Revision 1.8  2003/11/15 21:53:53  wainstead
// Minor change: list plugins in asciibetical order. It'd be better if
// they were alphabetical.
//
// Revision 1.7  2003/02/24 01:36:25  dairiki
// Don't use PHPWIKI_DIR unless it's defined.
// (Also typo/bugfix in SystemInfo plugin.)
//
// Revision 1.6  2003/02/24 00:56:53  carstenklapp
// Updated to work with recent changes to WikiLink function (fix
// "==Object(wikipagename)==" for unknown wiki links).
//
// Revision 1.5  2003/02/22 20:49:56  dairiki
// Fixes for "Call-time pass by reference has been deprecated" errors.
//
// Revision 1.4  2003/02/20 18:13:38  carstenklapp
// Workaround for recent changes to WikiPlugin->getPlugin.
// Made admin restriction for viewing this page optional.
// Now defaults to any user may view this page (mainly for PhpWiki Demo site).
// Minor code changes & reformatting.
//
// Revision 1.3  2003/01/04 02:30:12  carstenklapp
// Added 'info' argument to show / hide plugin "Arguments"
// column. Improved row highlighting and error message when viewed by
// non-admin user. Code refactored. Added copyleft.

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
