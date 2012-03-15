<?php
/**
 *
 * Copyright (C) Villanova University 2010.
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
require_once 'sys/Proxy_Request.php';

/**
 * ExternalExcerpts Class
 *
 * This class fetches excerpts from various services for presentation to
 * the user.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class ExternalExcerpts
{
    private $isbn;
    
    /**
     * Constructor
     *
     * Do the actual work of loading the excerpts.
     *
     * @access  public
     * @param   string      $isbn           ISBN of book to find excerpts for
     */
    public function __construct($isbn)
    {
        global $configArray;

        $this->isbn = $isbn;
        $this->results = array();

        // We can't proceed without an ISBN:
        if (empty($this->isbn)) {
            return;
        }

        // Fetch from provider
        if (isset($configArray['Content']['excerpts'])) {
            $providers = explode(',', $configArray['Content']['excerpts']);
            foreach ($providers as $provider) {
                $provider = explode(':', trim($provider));
                $func = strtolower($provider[0]);
                $key = $provider[1];
                $this->results[$func] = method_exists($this, $func) ? 
                    $this->$func($key) : false;

                // If the current provider had no valid excerpts, store nothing:
                if (empty($this->results[$func]) || PEAR::isError($this->results[$func])) {
                    unset($this->results[$func]);
                }
            }
        }
    }
    
    /**
     * Get the excerpt information.
     *
     * @access  public
     * @return  array                       Associative array of excerpts.
     */
    public function fetch()
    {
        return $this->results;
    }

    /**
     * syndetics
     *
     * This method is responsible for connecting to Syndetics and abstracting
     * excerpts.
     *
     * It first queries the master url for the ISBN entry seeking an excerpt URL.
     * If an excerpt URL is found, the script will then use HTTP request to
     * retrieve the script. The script will then parse the excerpt according to
     * US MARC (I believe). It will provide a link to the URL master HTML page
     * for more information.
     * Configuration:  Sources are processed in order - refer to $sourceList.
     *
     * @param   string  $id Client access key
     * @return  array       Returns array with excerpt data, otherwise a
     *                      PEAR_Error.
     * @access  private
     * @author  Joel Timothy Norman <joel.t.norman@wmich.edu>
     * @author  Andrew Nagy <andrew.nagy@villanova.edu>
     */
    private function syndetics($id)
    {
        global $configArray;

        //list of syndetic revies
        $sourceList = array('DBCHAPTER' => array('title' => 'First Chapter or Excerpt',
                                                'file' => 'DBCHAPTER.XML'));
                            
        //first request url
        $baseUrl = isset($configArray['Syndetics']['url']) ? 
            $configArray['Syndetics']['url'] : 'http://syndetics.com';
        $url = $baseUrl . '/index.aspx?isbn=' . $this->isbn . '/' .
               'index.xml&client=' . $id . '&type=rw12,hw7';

        //find out if there are any reviews
        $client = new Proxy_Request();
        $client->setMethod(HTTP_REQUEST_METHOD_GET);
        $client->setURL($url);
        if (PEAR::isError($http = $client->sendRequest())) {
            return $http;
        }

        // Test XML Response
        if (!($xmldoc = @DOMDocument::loadXML($client->getResponseBody()))) {
            return new PEAR_Error('Invalid XML');
        }

        $review = array();
        $i = 0;
        foreach ($sourceList as $source => $sourceInfo) {
            $nodes = $xmldoc->getElementsByTagName($source);
            if ($nodes->length) {
                // Load reviews
                $url = $baseUrl . '/index.aspx?isbn=' . $this->isbn . '/' .
                       $sourceInfo['file'] . '&client=' . $id . '&type=rw12,hw7';
                $client->setURL($url);
                if (PEAR::isError($http = $client->sendRequest())) {
                    return $http;
                }

                // Test XML Response
                if (!($xmldoc2 = @DOMDocument::loadXML($client->getResponseBody()))) {
                    return new PEAR_Error('Invalid XML');
                }

                // Get the marc field for excerpts (520)
                $nodes = $xmldoc2->GetElementsbyTagName("Fld520");
                if (!$nodes->length) {
                    // Skip excerpts with missing text
                    continue;
                }
                $review[$i]['Content'] = html_entity_decode($xmldoc2->saveXML($nodes->item(0)));

                // Get the marc field for copyright (997)
                $nodes = $xmldoc->GetElementsbyTagName("Fld997");
                if ($nodes->length) {
                    $review[$i]['Copyright'] = html_entity_decode($xmldoc2->saveXML($nodes->item(0)));
                } else {
                    $review[$i]['Copyright'] = null;
                }

                if ($review[$i]['Copyright']) {  //stop duplicate copyrights
                    $location = strripos($review[0]['Content'], $review[0]['Copyright']);
                    if ($location > 0) {
                        $review[$i]['Content'] = substr($review[0]['Content'], 0, $location);
                    }
                }

                $review[$i]['Source'] = $sourceInfo['title'];  //changes the xml to actual title
                $review[$i]['ISBN'] = $this->isbn; //show more link
                $review[$i]['username'] = $id;
                
                $i++;
            }
        }

        return $review;
    }
}
?>