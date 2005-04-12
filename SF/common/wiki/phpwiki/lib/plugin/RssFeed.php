<?php // -*-php-*-
rcs_id('$Id$');
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

class WikiPlugin_RssFeed
extends WikiPlugin
{
    // Five required functions in a WikiPlugin.
    function getName () {
        return _("RssFeed");
    }

    function getDescription () {
        return _("Simple RSS Feed aggregator Plugin");

    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision$");
    }

    // Establish default values for each of this plugin's arguments.
    function getDefaultArguments() {
        return array('feed' 		=> "",
                     'description' 	=> "",
                     'url' 		=> "", //"http://phpwiki.sourceforge.net/phpwiki/RecentChanges?format=rss",
                     'maxitem' 		=> 0,
                     'debug' 		=> false,
                     );
   }

    function run($dbi, $argstr, $request, $basepage) {
        extract($this->getArgs($argstr, $request));

        $xml_parser = xml_parser_create();
        $rss_parser = new RSSParser();
        if (!empty($url))
            $rss_parser->parse_results( $xml_parser, &$rss_parser, $url,$debug);

        if (!empty($rss_parser->channel['title'])) $feed = $rss_parser->channel['title'];
        if (!empty($rss_parser->channel['link']))  $url  = $rss_parser->channel['link'];
        if (!empty($rss_parser->channel['description'])) 
            $description = $rss_parser->channel['description'];
        
        if (!empty($feed)) {
            if (!empty($url)) {
                $titre = HTML::span(HTML::a(array('href'=>$rss_parser->channel['link']),
                                            $rss_parser->channel['title'])); 
            } else {
                $titre = HTML::span($rss_parser->channel['title']);
            }
            $th = HTML::div(array('class'=> 'feed'), $titre);
            if (!empty($description))
                $th->pushContent(HTML::p(array('class' => 'chandesc'),
                                         HTML::raw($description)));
        } else {
            $th = HTML();
        }

        if (!empty($rss_parser->channel['date']))
            $th->pushContent(HTML::raw("<!--".$rss_parser->channel['date']."-->"));
        $html = HTML::div(array('class'=> 'rss'), $th);

        // limitation du nombre d'items affichs
        if ($maxitem > 0) $rss_parser->items = array_slice($rss_parser->items, 0, $maxitem);

        foreach ($rss_parser->items as $item) {
            $cell_title = HTML::div(array('class'=> 'itemname'),
                                    HTML::a(array('href'=>$item['link']),
                                            HTML::raw($item['title'])));
            $cell_content = HTML::div(array('class'=> 'itemdesc'),
                                      HTML::raw($item['description']));
            $cell = HTML::div(array('class'=> 'rssitem'));
            $cell->pushContent($cell_title);
            $cell->pushContent($cell_content);
            $html->pushContent($cell);
        }
        return $html;
    }

    function box($args=false, $request=false, $basepage=false) {
        if (!$request) $request =& $GLOBALS['request'];
        extract($args);
        if (empty($title)) $title = _("RssFeed");
        if (empty($url))   $url = 'http://phpwiki.sourceforge.net/phpwiki/RecentChanges?format=rss';
        $argstr = "url=$url";
        if (isset($maxitem) and is_numeric($maxitem)) $argstr .=  " maxitem=$maxitem";
        return $this->makeBox($title,
                              $this->run($request->_dbi, $argstr, $request, $basepage));
    }

};

// $Log$
// Revision 1.1  2005/04/12 13:33:33  guerin
// First commit for wiki integration.
// Added Manuel's code as of revision 13 on Partners.
// Very little modification at the moment:
// - removed use of DOCUMENT_ROOT and SF_LOCAL_INC_PREFIX
// - simplified require syntax
// - removed ST-specific code (for test phase)
//
// Revision 1.4  2004/04/18 01:11:52  rurban
// more numeric pagename fixes.
// fixed action=upload with merge conflict warnings.
// charset changed from constant to global (dynamic utf-8 switching)
//

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>