<?php
/**
 * Detect external referrers
 * Currently only search engines, and highlight the searched item.
 *
 * Todo:
 *   store all external referrers in (rotatable) log/db for a RecentReferrers plugin.
 */

if (! function_exists('isExternalReferrer')) { // better define that in stdlib.php
    function isExternalReferrer(&$request)
    {
        if ($referrer = $request->get('HTTP_REFERER')) {
            $home = SCRIPT_NAME; // was SERVER_URL, check sister wiki's: same host but other other script url
            if (substr(strtolower($referrer), 0, strlen($home)) == strtolower($home)) {
                return false;
            }
            require_once('lib/ExternalReferrer.php');
            $se = new SearchEngines();
            return $se->parseSearchQuery($referrer);
        }
        return false;
    }
}

class SearchEngines
{
    public $searchEngines =
    [
        'search.sli.sympatico.ca/' => ['engine' => 'Sympatico', 'query1' => 'query=', 'query2' => '', 'url' => 'http://www1.sympatico.ca/'],
        'www.search123.com/cgi-bin/' => ['engine' => 'Search123', 'query1' => 'query=', 'query2' => '', 'url' => 'http://www.search123.com/'],
        'search.dogpile.com' => ['engine' => 'Dogpile', 'query1' => 'q=', 'query2' => '', 'url' => 'http://www.dogpile.com'],
        'vivisimo.' => ['engine' => 'Vivisimo', 'query1' => 'query=', 'query2' => '', 'url' => 'http://www.vivisimo.com'],
        'webindex.sanook.com' => ['engine' => 'Sanook', 'query1' => 'd1=', 'query2' => '', 'url' => 'http://www.sanook.com/'],
        'tiscali.cz/search' => ['engine' => 'JANAS', 'query1' => 'query=', 'query2' => '', 'url' => 'http://www.tiscali.cz/'],
        'teoma.com/gs?' => ['engine' => 'Teoma', 'query1' => 'terms=', 'query2' => '', 'url' => 'http://www.teoma.com/'],
        'redbox.' => ['engine' => 'RedBox', 'query1' => 'srch=', 'query2' => '', 'url' => 'http://www.redbox.cz/'],
        'globetrotter.net' => ['engine' => 'Telus Network - Globetrotter.net', 'query1' => 'string=', 'query2' => '', 'url' => 'http://www.globetrotter.net/'],
        'myto.com' => ['engine' => 'Telus Network - myTO.com', 'query1' => 'string=', 'query2' => '', 'url' => 'http://www.myto.com/'],
        'alberta.com' => ['engine' => 'Telus Network - Alberta.com', 'query1' => 'string=', 'query2' => '', 'url' => 'http://www.alberta.com/'],
        'mybc.com' => ['engine' => 'Telus Network - myBC.com', 'query1' => 'string=', 'query2' => '', 'url' => 'http://www.mybc.com/'],
        'monstercrawler.' => ['engine' => 'MonsterCrawler', 'query1' => 'qry=', 'query2' => '', 'url' => 'http://www.monstercrawler.com/'],
        'allthesites.' => ['engine' => 'All the Sites', 'query1' => 'query=', 'query2' => '', 'url' => 'http://www.althesites.com/'],
        'suche.web' => ['engine' => 'Web.de', 'query1' => 'su=', 'query2' => '', 'url' => 'http://www.web.de/'],
        'rediff.' => ['engine' => 'reDiff', 'query1' => 'MT=', 'query2' => '', 'url' => 'http://www.rediff.com/'],
        'evreka.' => ['engine' => 'Evreka', 'query1' => 'q=', 'query2' => '', 'url' => 'http://evreka.suomi24.fi/'],
        'findia.' => ['engine' => 'Findia', 'query1' => 'query=', 'query2' => '', 'url' => 'http://www.findia.net/'],
        'av.yahoo' => ['engine' => 'Yahoo', 'query1' => 'p=', 'query2' => '', 'url' => 'http://www.yahoo.com/'],
        'google.yahoo' => ['engine' => 'Yahoo', 'query1' => 'p=', 'query2' => '', 'url' => 'http://www.yahoo.com/'],
        'yahoo.' => ['engine' => 'Yahoo', 'query1' => 'q=', 'query2' => '', 'url' => 'http://www.yahoo.com/'],
        'aol.' => ['engine' => 'AOL Search', 'query1' => 'query=', 'query2' => '', 'url' => 'http://search.aol.com/'],
        'about.' => ['engine' => 'About', 'query1' => 'terms=', 'query2' => '', 'url' => 'http://www.about.com/'],
        'altavista.' => ['engine' => 'Altavista', 'query1' => 'q=', 'query2' => '', 'url' => 'http://www.altavista.com/'],
        'directhit.' => ['engine' => 'DirectHit', 'query1' => 'qry=', 'query2' => '', 'url' => 'http://www.directhit.com/'],
        'lk=webcrawler' => ['engine' => 'Webcrawler', 'query1' => 's=', 'query2' => '', 'url' => 'http://www.webcrawler.com/'],
        'excite.' => ['engine' => 'Excite', 'query1' => 'search=', 'query2' => '', 'url' => 'http://www.excite.com/'],
        'alltheweb.' => ['engine' => 'All the Web', 'query1' => 'query=', 'query2' => 'q=', 'url' => 'http://www.alltheweb.com/'],
        'netscape.' => ['engine' => 'Netscape', 'query1' => 'search=', 'query2' => '', 'url' => 'http://search.netscape.com/'],
        'google.' => ['engine' => 'Google', 'query1' => 'q=', 'query2' => 'query=', 'url' => 'http://www.google.com/'],
        '?partner=go_home' => ['engine' => 'Infoseek/Go', 'query1' => 'Keywords=', 'query2' => '', 'url' => 'http://www.go.com/'],
        'nbci.' => ['engine' => 'NBCi', 'query1' => 'Keywords=', 'query2' => '', 'url' => 'http://www.nbci.com/'],
        'goto.' => ['engine' => 'GoTo', 'query1' => 'Keywords=', 'query2' => '', 'url' => 'http://www.goto.com/'],
        'hotbot.' => ['engine' => 'HotBot', 'query1' => 'MT=', 'query2' => '', 'url' => 'http://hotbot.lycos.com/'],
        'iwon.' => ['engine' => 'IWon', 'query1' => 'searchfor=', 'query2' => '', 'url' => 'http://home.iwon.com/index_gen.html'],
        'looksmart.' => ['engine' => 'Looksmart', 'query1' => 'key=', 'query2' => '', 'url' => 'http://www.looksmart.com/'],
        'lycos.' => ['engine' => 'Lycos', 'query1' => 'query=', 'query2' => '', 'url' => 'http://www.lycos.com/'],
        'msn.' => ['engine' => 'MSN', 'query1' => 'q=', 'query2' => '', 'url' => 'http://search.msn.com/'],
        'dmoz.' => ['engine' => 'Dmoz', 'query1' => 'search=', 'query2' => '', 'url' => 'http://www.dmoz.org/'],

    ];

    /**
     * parseSearchQuery(url)
     * Parses the passed refering url looking for search engine data.  If search info is found,
     * the method determines the name of the search engine, it's URL, and the search keywords
     * used in the search. This information is returned in an associative array with the following
     * keys:
     * @returns array engine, engine_url, query
     * @public
     */
    public function parseSearchQuery($url)
    {
        // test local referrers
        if (DEBUG) {
            $this->searchEngines[SERVER_URL] = ['engine' => 'DEBUG', 'query1' => 's=', 'query2' => '', 'url' => SCRIPT_NAME];
        }
        $ref = $url;
        foreach ($this->searchEngines as $key => $var) {
            if (stristr($ref, $key)) {
                unset($ref);
                $ref['engine']     = $var['engine'];
                $query1            =  $var['query1'];
                $query2            =  $var['query2'];
                $ref['engine_url'] = $var['url'];
            }
        }
        reset($this->searchEngines);
        if ($ref == $url) {
            return false;
        }
        $url = @parse_url(strtolower($url));
        if (! empty($url['query'])) {
            $url = $url['query'];
        }
        if ($query1 and @stristr($url, $query1)) {
             $query = @explode($query1, $url);
        } elseif ($query2 and @stristr($url, $query2)) {
            $query = explode($query2, $url);
        }
        if (! empty($query)) {
            $query        = @explode('&', $query[1]);
            $ref['query'] = @urldecode($query[0]);
        }
        return $ref;
    }
}

// $Log: ExternalReferrer.php,v $
// Revision 1.3  2004/10/12 14:22:14  rurban
// lib/ExternalReferrer.php:99: Notice[8]: Undefined index: query
//
// Revision 1.2  2004/09/26 14:55:55  rurban
// fixed warning
//
// Revision 1.1  2004/09/26 12:20:28  rurban
// Detect external referrers, handle search engines
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
