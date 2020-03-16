<?php
// -*-php-*-
rcs_id('$Id: RssFeed.php,v 1.10 2005/04/10 10:24:58 rurban Exp $');
/*
 Copyright 2003 Arnaud Fontaine

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
 * @author: Arnaud Fontaine
 */
include("lib/RssParser.php");

class WikiPlugin_RssFeed extends WikiPlugin
{
    // Five required functions in a WikiPlugin.
    public function getName()
    {
        return _("RssFeed");
    }

    public function getDescription()
    {
        return _("Simple RSS Feed aggregator Plugin");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.10 $"
        );
    }

    // Establish default values for each of this plugin's arguments.
    public function getDefaultArguments()
    {
        return array('feed'         => "",
                     'description'     => "",
                     'url'         => "", //"http://phpwiki.org/RecentChanges?format=rss",
                     'maxitem'         => 0,
                     'debug'         => false,
                     );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        extract($this->getArgs($argstr, $request));

        $rss_parser = new RSSParser();
        if (!empty($url)) {
            $rss_parser->parse_url($url, $debug);
        }

        if (!empty($rss_parser->channel['title'])) {
            $feed = $rss_parser->channel['title'];
        }
        if (!empty($rss_parser->channel['link'])) {
            $url  = $rss_parser->channel['link'];
        }
        if (!empty($rss_parser->channel['description'])) {
            $description = $rss_parser->channel['description'];
        }

        if (!empty($feed)) {
            if (!empty($url)) {
                $titre = HTML::span(HTML::a(
                    array('href' => $rss_parser->channel['link']),
                    $rss_parser->channel['title']
                ));
            } else {
                $titre = HTML::span($rss_parser->channel['title']);
            }
            $th = HTML::div(array('class' => 'feed'), $titre);
            if (!empty($description)) {
                $th->pushContent(HTML::p(
                    array('class' => 'chandesc'),
                    HTML::raw($description)
                ));
            }
        } else {
            $th = HTML();
        }

        if (!empty($rss_parser->channel['date'])) {
            $th->pushContent(HTML::raw("<!--" . $rss_parser->channel['date'] . "-->"));
        }
        $html = HTML::div(array('class' => 'rss'), $th);
        if ($rss_parser->items) {
            // only maxitem's
            if ($maxitem > 0) {
                $rss_parser->items = array_slice($rss_parser->items, 0, $maxitem);
            }
            foreach ($rss_parser->items as $item) {
                $cell = HTML::div(array('class' => 'rssitem'));
                if ($item['link'] and empty($item['title'])) {
                    $item['title'] = $item['link'];
                }
                $cell_title = HTML::div(
                    array('class' => 'itemname'),
                    HTML::a(
                        array('href' => $item['link']),
                        HTML::raw($item['title'])
                    )
                );
                $cell->pushContent($cell_title);
                if (!empty($item['description'])) {
                    $cell->pushContent(HTML::div(
                        array('class' => 'itemdesc'),
                        HTML::raw($item['description'])
                    ));
                }
                $html->pushContent($cell);
            }
        } else {
            $html = HTML::div(array('class' => 'rss'), HTML::em(_("no RSS items")));
        }
        return $html;
    }

    public function box($args = false, $request = false, $basepage = false)
    {
        if (!$request) {
            $request = $GLOBALS['request'];
        }
        extract($args);
        if (empty($title)) {
            $title = _("RssFeed");
        }
        if (empty($url)) {
            $url = 'http://phpwiki.sourceforge.net/phpwiki/RecentChanges?format=rss';
        }
        $argstr = "url=$url";
        if (isset($maxitem) and is_numeric($maxitem)) {
            $argstr .=  " maxitem=$maxitem";
        }
        return $this->makeBox(
            $title,
            $this->run($request->_dbi, $argstr, $request, $basepage)
        );
    }
}

// $Log: RssFeed.php,v $
// Revision 1.10  2005/04/10 10:24:58  rurban
// fix for RSS feeds without detailled <item> tags:
//   just list the <items> urls then (Bug #1180027)
//
// Revision 1.9  2004/11/03 16:34:10  rurban
// proper msg if rss connection is broken or no items found
//
// Revision 1.8  2004/07/08 20:30:07  rurban
// plugin->run consistency: request as reference, added basepage.
// encountered strange bug in AllPages (and the test) which destroys ->_dbi
//
// Revision 1.7  2004/06/08 21:03:20  rurban
// updated RssParser for XmlParser quirks (store parser object params in globals)
//
// Revision 1.6  2004/05/24 17:36:06  rurban
// new interface
//
// Revision 1.5  2004/05/18 16:18:37  rurban
// AutoSplit at subpage seperators
// RssFeed stability fix for empty feeds or broken connections
//
// Revision 1.4  2004/04/18 01:11:52  rurban
// more numeric pagename fixes.
// fixed action=upload with merge conflict warnings.
// charset changed from constant to global (dynamic utf-8 switching)
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
