<?php
// -*-php-*-
rcs_id('$Id: SearchHighlight.php,v 1.1 2004/09/26 14:58:36 rurban Exp $');
/*
Copyright 2004 $ThePhpWikiProgrammingTeam

This file is NOT part of PhpWiki.

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

require_once("lib/TextSearchQuery.php");
require_once("lib/PageList.php");

/** When someone is referred from a search engine like Google, Yahoo
 * or our own fulltextsearch, the terms they search for are highlighted.
 * See http://wordpress.org/about/shots/1.2/plugins.png
 *
 * Could be hooked from lib/display.php (but then not possible for actionpages)
 * or at request->flush or on a template. (if google referrer, search)
 */
class WikiPlugin_SearchHighlight extends WikiPlugin
{
    public function getName()
    {
        return _("SearchHighlight");
    }

    public function getDescription()
    {
        return _("Hilight referred search terms.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.1 $"
        );
    }

    public function getDefaultArguments()
    {
        return array('s'        => false,
                     'case_exact' => false,  //not yet supported
                     'regex'    => false,    //not yet supported
                     );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        if (empty($args['s'])) {
            return '';
        }

        extract($args);

        $query = new TextSearchQuery($s, $case_exact, $regex);
        //$pages = $dbi->fullSearch($query);
        $lines = array();
        $hilight_re = $query->getHighlightRegexp();
        $page = $request->getPage();
        return $this->showhits($page, $hilight_re);
    }

    public function showhits($page, $hilight_re)
    {
        $current = $page->getCurrentRevision();
        $matches = preg_grep("/$hilight_re/i", $current->getContent());
        $html = array();
        foreach ($matches as $line) {
            $line = $this->highlight_line($line, $hilight_re);
            $html[] = HTML::dd(HTML::small(
                array('class' => 'search-context'),
                $line
            ));
        }
        return $html;
    }

    public function highlight_line($line, $hilight_re)
    {
        while (preg_match("/^(.*?)($hilight_re)/i", $line, $m)) {
            $line = substr($line, strlen($m[0]));
            $html[] = $m[1];    // prematch
            $html[] = HTML::strong(array('class' => 'search-term'), $m[2]); // match
        }
        $html[] = $line;        // postmatch
        return $html;
    }
}

// $Log: SearchHighlight.php,v $
// Revision 1.1  2004/09/26 14:58:36  rurban
// naive SearchHighLight implementation
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
