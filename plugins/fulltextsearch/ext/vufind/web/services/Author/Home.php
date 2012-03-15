<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'Action.php';

require_once 'sys/Proxy_Request.php';
require_once 'sys/Pager.php';

class Home extends Action
{
    private $db;
    private $lang;

    function launch()
    {
        global $configArray;
        global $interface;
        global $user;

        // Initialise from the current search globals
        $searchObject = SearchObjectFactory::initSearchObject();
        $searchObject->init();

        // Build RSS Feed for Results (if requested)
        if ($searchObject->getView() == 'rss') {
            // Throw the XML to screen
            echo $searchObject->buildRSS();
            // And we're done
            exit();
        }
// TODO : Stats

        $interface->caching = false;

        if (!isset($_GET['author'])) {
            PEAR::raiseError(new PEAR_Error('Unknown Author'));
        } else {
            $interface->assign('author', $_GET['author']);
        }

        // What language should we use?
        $this->lang = $configArray['Site']['language'];

        // Retrieve User Search History -- note that we only want to offer a
        // "back to search" link if the saved URL is not for the current action;
        // when users first reach this page from search results, the "last URL"
        // will be their original search, which we want to link to.  However,
        // since this module will later set the "last URL" value in order to
        // allow the user to return from a record view to this page, after they
        // return here, we will no longer have access to the last non-author
        // search, and it is better to display nothing than to provide an infinite
        // loop of links.  Perhaps this can be solved more elegantly with a stack
        // or with multiple session variables, but for now this seems okay.
        $interface->assign('lastsearch', 
            (isset($_SESSION['lastSearchURL']) && 
            !strstr($_SESSION['lastSearchURL'], 'Author/Home')) ? 
            $_SESSION['lastSearchURL'] : false);

        if (!$interface->is_cached('layout.tpl|Author' . $_GET['author'])) {
            // Clean up author string
            $author = $_GET['author'];
            if (substr($author, strlen($author) - 1, 1) == ",") {
                $author = substr($author, 0, strlen($author) - 1);
            }
            $author = explode(',', $author);
            $interface->assign('author', $author);

            // Create First Name
            $fname = '';
            if (isset($author[1])) {
                $fname = $author[1];
                if (isset($author[2])) {
                    // Remove punctuation
                    if ((strlen($author[2]) > 2) && (substr($author[2], -1) == '.')) {
                        $author[2] = substr($author[2], 0, -1);
                    }
                    $fname = $author[2] . ' ' . $fname;
                }
            }

            // Remove dates
            $fname = preg_replace('/[0-9]+-[0-9]*/', '', $fname);            

            // Build Author name to display.
            if (substr($fname, -3, 1) == ' ') {
                // Keep period after initial
                $authorName = $fname . ' ';
            } else {
                // No initial so strip any punctuation from the end
                if ((substr(trim($fname), -1) == ',') ||
                    (substr(trim($fname), -1) == '.')) {
                    $authorName = substr(trim($fname), 0, -1) . ' ';
                } else {
                    $authorName = $fname . ' ';
                }
            }
            $authorName .= $author[0];
            $interface->assign('authorName', trim($authorName));

            // Pull External Author Content
            if ($searchObject->getPage() == 1) {
                // Only load Wikipedia info if turned on in config file:
                if (isset($configArray['Content']['authors']) &&
                    stristr($configArray['Content']['authors'], 'wikipedia')) {
                    // Only use first two characters of language string; Wikipedia 
                    // uses language domains but doesn't break them up into regional
                    // variations like pt-br or en-gb.
                    $wiki_lang = substr($configArray['Site']['language'], 0, 2);
                    $authorInfo = $this->getWikipedia($authorName, $wiki_lang);
                    $interface->assign('wiki_lang', $wiki_lang);
                    if (!PEAR::isError($authorInfo)) {
                        $interface->assign('info', $authorInfo);
                    }
                }
            }
        }

        // Set Interface Variables
        //   Those we can construct BEFORE the search is executed
        $interface->setPageTitle('Author Search Results');
        $interface->assign('sortList',   $searchObject->getSortList());
        $interface->assign('rssLink',    $searchObject->getRSSUrl());

        // Process Search
        $result = $searchObject->processSearch(false, true);
        if (PEAR::isError($result)) {
            PEAR::raiseError($result->getMessage());
        }

        // Some more variables
        //   Those we can construct AFTER the search is executed, but we need
        //   no matter whether there were any results
        $interface->assign('qtime', round($searchObject->getQuerySpeed(), 2));

        // Assign interface variables
        $summary = $searchObject->getResultSummary();
        $interface->assign('recordCount', $summary['resultTotal']);
        $interface->assign('recordStart', $summary['startRecord']);
        $interface->assign('recordEnd',   $summary['endRecord']);
        $interface->assign('sideRecommendations',
            $searchObject->getRecommendationsTemplates('side'));

        // Big one - our results
        $interface->assign('recordSet', $searchObject->getResultRecordHTML());

        // Setup Display
        $interface->assign('sitepath', $configArray['Site']['path']);

        // Process Paging
        $link = $searchObject->renderLinkPageTemplate();
        $options = array('totalItems' => $summary['resultTotal'],
                         'fileName'   => $link,
                         'perPage'    => $summary['perPage']);
        $pager = new VuFindPager($options);
        $interface->assign('pageLinks', $pager->getLinks());

        // Save the URL of this search to the session so we can return to it easily:
        $_SESSION['lastSearchURL'] = $searchObject->renderSearchUrl();

        $interface->setTemplate('home.tpl');
        $interface->display('layout.tpl', 'Author' . $_GET['author']);
    }

    /**
     * getWikipedia
     *
     * This method is responsible for connecting to Wikipedia via the REST API
     * and pulling the content for the relevant author.
     *
     * @param   string  $lang   The language code of the language to use
     * @return  null
     * @access  public
     * @author  Andrew Nagy <andrew.nagy@villanova.edu>
     */
    public function getWikipedia($author, $lang = null)
    {
        if ($lang) {
            $this->lang = $lang;
        }

        $url = "http://$this->lang.wikipedia.org/w/api.php" .
               '?action=query&prop=revisions&rvprop=content&format=php' .
               '&list=allpages&titles=' . urlencode($author);
        $client = new Proxy_Request();
        $client->setMethod(HTTP_REQUEST_METHOD_GET);
        $client->setURL($url);

        $result = $client->sendRequest();
        if (PEAR::isError($result)) {
            return $result;
        }

        $info = $this->parseWikipedia(unserialize($client->getResponseBody()));
        if (!PEAR::isError($info)) {
            return $info;
        }
    }

    /**
     * getWikipediaImageURL
     *
     * This method is responsible for obtaining an image URL based on a name.
     *
     * @param   string  $imageName  The image name to look up
     * @return  mixed               URL on success, false on failure
     * @access  private
     */
    private function getWikipediaImageURL($imageName)
    {
        $url = "http://$this->lang.wikipedia.org/w/api.php" .
               '?prop=imageinfo&action=query&iiprop=url&iiurlwidth=150&format=php' .
               '&titles=Image:' . $imageName;

        $client = new Proxy_Request();
        $client->setMethod(HTTP_REQUEST_METHOD_GET);
        $client->setURL($url);
        $result = $client->sendRequest();
        if (PEAR::isError($result)) {
            return false;
        }

        if ($response = $client->getResponseBody()) {
            if ($imageinfo = unserialize($response)) {
                if (isset($imageinfo['query']['pages']['-1']['imageinfo'][0]['url'])) {
                    $imageUrl = $imageinfo['query']['pages']['-1']['imageinfo'][0]['url'];
                }

                // Hack for wikipedia api, just in case we couldn't find it
                //   above look for a http url inside the response.
                if (!isset($imageUrl)) {
                    preg_match('/\"http:\/\/(.*)\"/', $response, $matches);
                    if (isset($matches[1])) {
                        $imageUrl = 'http://' . substr($matches[1], 0, strpos($matches[1], '"'));
                    }
                }
            }
        }

        return isset($imageUrl) ? $imageUrl : false;
    }
    
    /**
     * parseWikipedia
     *
     * This method is responsible for parsing the output from the Wikipedia
     * REST API.
     *
     * @param   string  $lang   The language code of the language to use
     * @return  null
     * @access  private
     * @author  Rushikesh Katikar <rushikesh.katikar@gmail.com>
     */
    private function parseWikipedia($body)
    {
        global $configArray;

        // Check if data exists or not
        if(isset($body['query']['pages']['-1'])) {
            return new PEAR_Error('No page found');
        }

        // Get the default page
        $body = array_shift($body['query']['pages']);
        $info['name'] = $body['title'];

        // Get the latest revision
        $body = array_shift($body['revisions']);
        // Check for redirection
        $as_lines = explode("\n", $body['*']);
        if (stristr($as_lines[0], '#REDIRECT')) {
            preg_match('/\[\[(.*)\]\]/', $as_lines[0], $matches);
            return $this->getWikipedia($matches[1]);
        }

/**
 * **************
 * 
 *   Infobox
 * 
 */
        // We are looking for the infobox inside "{{...}}"
        //   It may contain nested blocks too, thus the recursion
        preg_match_all('/\{([^{}]++|(?R))*\}/s', $body['*'], $matches);
        // print "<p>".htmlentities($body['*'])."</p>\n";
        foreach ($matches[1] as $m) {
            // If this is the Infobox
            if (substr($m, 0, 8) == "{Infobox") {
                // Keep the string for later, we need the body block that follows it
                $infoboxStr = "{".$m."}";
                // Get rid of the last pair of braces and split
                $infobox = explode("\n|", substr($m, 1, -1));
                // Look through every row of the infobox
                foreach ($infobox as $row) {
                    $data  = explode("=", $row);
                    $key   = trim(array_shift($data));
                    $value = trim(join("=", $data));

                    // At the moment we only want stuff related to the image.
                    switch (strtolower($key)) {
                      case "img":
                      case "image":
                      case "image:":
                      case "image_name":
                            $imageName = str_replace(' ', '_', $value);
                            break;
                      case "caption":
                      case "img_capt":
                      case "image_caption":
                            $image_caption = $value;
                            break;
                      default:         /* Nothing else... yet */ break;
                    }
                }
            }
        }

/**
 * **************
 * 
 *   Image
 * 
 */
        // If we didn't successfully extract an image from the infobox, let's see if we
        // can find one in the body -- we'll just take the first match:
        if (!isset($imageName)) {
            $pattern = '/(\x5b\x5b)Image:([^\x5d]*)(\x5d\x5d)/U';
            preg_match_all($pattern, $body['*'], $matches);
            if (isset($matches[2][0])) {
                $parts = explode('|', $matches[2][0]);
                $imageName = str_replace(' ', '_', $parts[0]);
                if (count($parts) > 1) {
                    $image_caption = strip_tags(preg_replace('/({{).*(}})/U', '', 
                        $parts[count($parts) - 1]));
                }
            }
        }
 
        // Given an image name found above, look up the associated URL:
        if (isset($imageName)) {
            $imageUrl = $this->getWikipediaImageURL($imageName);
        }

/**
 * **************
 * 
 *   Body
 * 
 */
        if (isset($infoboxStr)) {
            // Start of the infobox
            $start  = strpos($body['*'], $infoboxStr);
            // + the length of the infobox
            $offset = strlen($infoboxStr);
            // Every after the infobox
            $body   = substr($body['*'], $start + $offset);
        } else {
            // No infobox -- use whole thing:
            $body = $body['*'];
        }
        // Find the first heading
        $end    = strpos($body, "==");
        // Now cull our content back to everything before the first heading
        $body   = trim(substr($body, 0, $end));

        // Remove unwanted image/file links
        // Nested brackets make this annoying: We can't add 'File' or 'Image' as mandatory
        //    because the recursion fails, or as optional because then normal links get hit.
        //    ... unless there's a better pattern? TODO
        // eg. [[File:Johann Sebastian Bach.jpg|thumb|Bach in a 1748 portrait by [[Elias Gottlob Haussmann|Haussmann]]]]
        $open    = "\\[";
        $close   = "\\]";
        $content = "(?>[^\\[\\]]+)";  // Anything but [ or ]
        $recursive_match = "($content|(?R))*"; // We can either find content or recursive brackets
        preg_match_all("/".$open.$recursive_match.$close."/Us", $body, $new_matches);
        // Loop through every match (link) we found
        if (is_array($new_matches)) {
            foreach ($new_matches as $nm) {
                // Might be an array of arrays
                if (is_array($nm)) {
                    foreach ($nm as $n) {
                        // If it's a file link get rid of it
                        if (strtolower(substr($n, 0, 7)) == "[[file:" ||
                            strtolower(substr($n, 0, 8)) == "[[image:") {
                            $body = str_replace($n, "", $body);
                        }
                    }
                // Or just a normal array
                } else {
                    // If it's a file link get rid of it
                    if (strtolower(substr($n, 0, 7)) == "[[file:" ||
                        strtolower(substr($n, 0, 8)) == "[[image:") {
                        $body = str_replace($nm, "", $body);
                    }
                }
            }
        }

        // Initialize arrays of processing instructions
        $pattern = array();
        $replacement = array();

        // Convert wikipedia links
        $pattern[] = '/(\x5b\x5b)([^\x5d|]*)(\x5d\x5d)/Us';
        $replacement[] = '<a href="' . $configArray['Site']['url'] . '/Search/Results?lookfor=%22$2%22&amp;type=AllFields">$2</a>';
        $pattern[] = '/(\x5b\x5b)([^\x5d]*)\x7c([^\x5d]*)(\x5d\x5d)/Us';
        $replacement[] = '<a href="' . $configArray['Site']['url'] . '/Search/Results?lookfor=%22$2%22&amp;type=AllFields">$3</a>';

        // Fix pronunciation guides
        $pattern[] = '/({{)pron-en\|([^}]*)(}})/Us';
        $replacement[] = translate("pronounced") . " /$2/";

        // Removes citations
        $pattern[] = '/({{)[^}]*(}})/Us';
        $replacement[] = "";
        //  <ref ... > ... </ref> OR <ref> ... </ref>
        $pattern[] = '/<ref[^\/]*>.*<\/ref>/Us';
        $replacement[] = "";
        //    <ref ... />
        $pattern[] = '/<ref.*\/>/Us';
        $replacement[] = "";

        // Removes comments followed by carriage returns to avoid excess whitespace
        $pattern[] = '/<!--.*-->\n*/Us';
        $replacement[] = '';

        // Formatting
        $pattern[] = "/'''([^']*)'''/Us";
        $replacement[] = '<strong>$1</strong>';
        
        // Trim leading newlines (which can result from leftovers after stripping
        // other items above).  We want this to be greedy.
        $pattern[] = '/^\n*/s';
        $replacement[] = '';
        
        // Convert multiple newlines into two breaks
        // We DO want this to be greedy
        $pattern[] = "/\n{2,}/s";
        $replacement[] = '<br/><br/>';

        $body = preg_replace($pattern, $replacement, $body);

        if (isset($imageUrl) && $imageUrl != false) {
            $info['image'] = $imageUrl;
            if (isset($image_caption)) {
                $info['altimage'] = $image_caption;
            }
        }
        $info['description'] = $body;

        return $info;
    }

}

?>
