<?php
// -*-php-*-
rcs_id('$Id: InterWikiSearch.php,v 1.8 2004/06/28 12:51:41 rurban Exp $');
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
 * @description
 */
require_once('lib/PageType.php');

class WikiPlugin_InterWikiSearch extends WikiPlugin
{
    public function getName()
    {
        return _("InterWikiSearch");
    }

    public function getDescription()
    {
        return _("Perform searches on InterWiki sites listed in InterWikiMap.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.8 $"
        );
    }

    public function getDefaultArguments()
    {
        return array();
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        extract($args);

        if (defined('DEBUG') && !DEBUG) {
            return $this->disabled("Sorry, this plugin is currently out of order.");
        }

        $page = $dbi->getPage($request->getArg('pagename'));
        return new TransformedText(
            $page,
            _('InterWikiMap'),
            array('markup' => 2),
            'searchableInterWikiMap'
        );
        /*
        return new PageType($pagerevisionhandle,
                            $pagename = _('InterWikiMap'),
                            $markup = 2,
                            $overridePageType = 'PageType_searchableInterWikiMap');
        */
    }
}


/**
 * @desc
 */
if (defined('DEBUG') && DEBUG) {
    class PageFormatter_searchableInterWikiMap extends PageFormatter_interwikimap
    {
    }

    class PageType_searchableInterWikiMap extends PageType_interwikimap
    {
        public function format($text)
        {
            return HTML::div(
                array('class' => 'wikitext'),
                $this->_transform($this->_getHeader($text)),
                $this->_formatMap(),
                $this->_transform($this->_getFooter($text))
            );
        }

        public function _formatMap()
        {
            return $this->_arrayToTable($this->_getMap(), $GLOBALS['request']);
        }

        public function _arrayToTable($array, &$request)
        {
            $thead = HTML::thead();
            $label[0] = _("Wiki Name");
            $label[1] = _("Search");
            $thead->pushContent(HTML::tr(
                HTML::th($label[0]),
                HTML::th($label[1])
            ));

            $tbody = HTML::tbody();
            $dbi = $request->getDbh();
            if ($array) {
                foreach ($array as $moniker => $interurl) {
                    $monikertd = HTML::td(
                        array('class' => 'interwiki-moniker'),
                        $dbi->isWikiPage($moniker)
                                      ? WikiLink($moniker)
                        : $moniker
                    );

                    $w = new WikiPluginLoader;
                    $p = $w->getPlugin('ExternalSearch');
                    $argstr = sprintf('url="%s"', addslashes($interurl));
                    $searchtd = HTML::td($p->run($dbi, $argstr, $request, $basepage));

                    $tbody->pushContent(HTML::tr($monikertd, $searchtd));
                }
            }
            $table = HTML::table();
            $table->setAttr('class', 'interwiki-map');
            $table->pushContent($thead);
            $table->pushContent($tbody);

            return $table;
        }
    }
}


// $Log: InterWikiSearch.php,v $
// Revision 1.8  2004/06/28 12:51:41  rurban
// improved dumphtml and virgin setup
//
// Revision 1.7  2004/06/15 14:56:37  rurban
// more allow_call_time_pass_reference false fixes
//
// Revision 1.6  2004/04/19 23:13:03  zorloc
// Connect the rest of PhpWiki to the IniConfig system.  Also the keyword regular expression is not a config setting
//
// Revision 1.5  2004/02/19 22:06:53  rurban
// use new class, to be able to get rid of lib/interwiki.php
//
// Revision 1.4  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.3  2003/02/23 20:10:48  dairiki
// Disable currently broken plugin to prevent fatal PHP errors.
// (Sorry.)
//
// Revision 1.2  2003/02/22 20:49:56  dairiki
// Fixes for "Call-time pass by reference has been deprecated" errors.
//
// Revision 1.1  2003/01/31 22:56:21  carstenklapp
// New plugin which provides entry forms to search any site listed in the InterWikiMap.
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
