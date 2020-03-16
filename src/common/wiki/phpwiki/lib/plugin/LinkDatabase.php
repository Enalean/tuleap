<?php
// -*-php-*-
rcs_id('$Id: LinkDatabase.php,v 1.7 2004/12/26 17:17:25 rurban Exp $');
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

require_once('lib/PageList.php');
require_once('lib/WikiPluginCached.php');

/**
 * - To be used by WikiBrowser at http://touchgraph.sourceforge.net/
 *   Only via a static text file yet. (format=text)
 * - Or the Hypergraph applet (format=xml)
 *   http://hypergraph.sourceforge.net/
 *   So far also only for a static xml file, but I'll fix the applet and test
 *   the RPC2 interface.
 *
 * TODO: Currently the meta-head tags disturb the touchgraph java browser a bit.
 * Maybe add a theme without that much header tags.
 */
class WikiPlugin_LinkDatabase extends WikiPluginCached
{
    public function getName()
    {
        return _("LinkDatabase");
    }
    public function getPluginType()
    {
        return PLUGIN_CACHED_HTML;
    }
    public function getDescription()
    {
        return _("List all pages with all links in various formats for some Java Visualization tools");
    }
    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.7 $"
        );
    }
    public function getExpire($dbi, $argarray, $request)
    {
        return '+900'; // 15 minutes
    }

    public function getDefaultArguments()
    {
        return array_merge(
            PageList::supportedArgs(),
            array(
                   'format'        => 'html', // 'html', 'text', 'xml'
                   'noheader'      => false,
                   'include_empty' => false,
                   'exclude_from'  => false,
                   'info'          => '',
            )
        );
    }

    public function getHtml($dbi, $argarray, $request, $basepage)
    {
        $this->run($dbi, WikiPluginCached::glueArgs($argarray), $request, $basepage);
    }

    public function run($dbi, $argstr, $request, $basepage)
    {
        global $WikiTheme;
        $args = $this->getArgs($argstr, $request);
        $caption = _("All pages with all links in this wiki (%d total):");

        if (!empty($args['owner'])) {
            $pages = PageList::allPagesByOwner(
                $args['owner'],
                $args['include_empty'],
                $args['sortby'],
                $args['limit']
            );
            if ($args['owner']) {
                $caption = fmt(
                    "List of pages owned by [%s] (%d total):",
                    WikiLink($args['owner'], 'if_known'),
                    count($pages)
                );
            }
        } elseif (!empty($args['author'])) {
            $pages = PageList::allPagesByAuthor(
                $args['author'],
                $args['include_empty'],
                $args['sortby'],
                $args['limit']
            );
            if ($args['author']) {
                $caption = fmt(
                    "List of pages last edited by [%s] (%d total):",
                    WikiLink($args['author'], 'if_known'),
                    count($pages)
                );
            }
        } elseif (!empty($args['creator'])) {
            $pages = PageList::allPagesByCreator(
                $args['creator'],
                $args['include_empty'],
                $args['sortby'],
                $args['limit']
            );
            if ($args['creator']) {
                $caption = fmt(
                    "List of pages created by [%s] (%d total):",
                    WikiLink($args['creator'], 'if_known'),
                    count($pages)
                );
            }
        } else {
            if (! $request->getArg('count')) {
                $args['count'] = $dbi->numPages($args['include_empty'], $args['exclude_from']);
            } else {
                $args['count'] = $request->getArg('count');
            }
            $pages = $dbi->getAllPages(
                $args['include_empty'],
                $args['sortby'],
                $args['limit'],
                $args['exclude_from']
            );
        }
        if ($args['format'] == 'html') {
            $args['types']['links'] =
                new _PageList_Column_LinkDatabase_links('links', _("Links"), 'left');
            $pagelist = new PageList($args['info'], $args['exclude_from'], $args);
            if (!$args['noheader']) {
                $pagelist->setCaption($caption);
            }
            return $pagelist;
        } elseif ($args['format'] == 'text') {
            $request->discardOutput();
            $request->buffer_output(false);
            if (!headers_sent()) {
                header("Content-Type: text/plain");
            }
            $request->checkValidators();
            while ($page = $pages->next()) {
                echo $page->getName();
                $links = $page->getPageLinks(
                    false,
                    $args['sortby'],
                    $args['limit'],
                    $args['exclude']
                );
                while ($link = $links->next()) {
                    echo " ", $link->getName();
                }
                echo "\n";
            }
            flush();
            if (empty($WikiTheme->DUMP_MODE)) {
                $request->finish();
            }
        } elseif ($args['format'] == 'xml') {
            // For hypergraph.jar. Best dump it to a local sitemap.xml periodically
            global $WikiTheme, $charset;
            $currpage = $request->getArg('pagename');
            $request->discardOutput();
            $request->buffer_output(false);
            if (!headers_sent()) {
                header("Content-Type: text/xml");
            }
            $request->checkValidators();
            echo "<?xml version=\"1.0\" encoding=\"$charset\"?>";
            // As applet it prefers only "GraphXML.dtd", but then we must copy it to the webroot.
            $dtd = $WikiTheme->_findData("GraphXML.dtd");
            echo "<!DOCTYPE GraphXML SYSTEM \"$dtd\">\n";
            echo "<GraphXML xmlns:xlink=\"http://www.w3.org/1999/xlink\">\n";
            echo "<graph id=\"",MangleXmlIdentifier(WIKI_NAME),"\">\n";
            echo '<style><line tag="node" class="main" colour="#ffffff"/><line tag="node" class="child" colour="blue"/><line tag="node" class="relation" colour="green"/></style>',"\n\n";
            while ($page = $pages->next()) {
                $pageid = MangleXmlIdentifier($page->getName());
                $pagename = $page->getName();
                echo "<node name=\"$pageid\"";
                if ($pagename == $currpage) {
                    echo " class=\"main\"";
                }
                echo "><label>$pagename</label>";
                echo "<dataref><ref xlink:href=\"",WikiURL($pagename, '', true),"\"/></dataref></node>\n";
                $links = $page->getPageLinks(false, $args['sortby'], $args['limit'], $args['exclude']);
                while ($link = $links->next()) {
                    $edge = MangleXmlIdentifier($link->getName());
                    echo "<edge source=\"$pageid\" target=\"$edge\" />\n";
                }
                echo "\n";
            }
            echo "</graph>\n";
            echo "</GraphXML>\n";
            if (empty($WikiTheme->DUMP_MODE)) {
                unset($GLOBALS['ErrorManager']->_postponed_errors);
                $request->finish();
            }
        } else {
            return $this->error(fmt("Unsupported format argument %s", $args['format']));
        }
    }
}

class _PageList_Column_LinkDatabase_links extends _PageList_Column
{
    public function _getValue($page, &$revision_handle)
    {
        $out = HTML();
        $links = $page->getPageLinks();
        while ($link = $links->next()) {
            $out->pushContent(" ", WikiLink($link));
        }
        return $out;
    }
}

// $Log: LinkDatabase.php,v $
// Revision 1.7  2004/12/26 17:17:25  rurban
// announce dumps - mult.requests to avoid request::finish, e.g. LinkDatabase, PdfOut, ...
//
// Revision 1.6  2004/12/22 18:48:10  rurban
// default format=html for unit-tests and DumpHtml/Zip breakage
//
// Revision 1.5  2004/12/17 16:39:03  rurban
// minor reformatting
//
// Revision 1.4  2004/12/06 19:50:05  rurban
// enable action=remove which is undoable and seeable in RecentChanges: ADODB ony for now.
// renamed delete_page to purge_page.
// enable action=edit&version=-1 to force creation of a new version.
// added BABYCART_PATH config
// fixed magiqc in adodb.inc.php
// and some more docs
//
// Revision 1.3  2004/11/30 23:44:00  rurban
// some comments
//
// Revision 1.2  2004/11/30 23:02:45  rurban
// format=xml for hypergraph.sf.net applet
//
// Revision 1.1  2004/11/30 21:02:16  rurban
// A simple plugin for WikiBrowser at http://touchgraph.sourceforge.net/
// List all pages with all links as text file (with some caching tricks).
//   format=html currently unstable.
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
