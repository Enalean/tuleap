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
require_once 'sys/Amazon.php';
require_once 'sys/Proxy_Request.php';

/**
 * ExternalReviews Class
 *
 * This class fetches reviews from various services for presentation to
 * the user.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class ExternalReviews
{
    private $isbn;
    
    /**
     * Constructor
     *
     * Do the actual work of loading the reviews.
     *
     * @access  public
     * @param   string      $isbn           ISBN of book to find reviews for
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
        if (isset($configArray['Content']['reviews'])) {
            $providers = explode(',', $configArray['Content']['reviews']);
            foreach ($providers as $provider) {
                $provider = explode(':', trim($provider));
                $func = strtolower($provider[0]);
                $key = $provider[1];
                $this->results[$func] = method_exists($this, $func) ? 
                    $this->$func($key) : false;

                // If the current provider had no valid reviews, store nothing:
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
     * Amazon Reviews
     *
     * This method is responsible for connecting to Amazon AWS and abstracting
     * customer reviews for the specific ISBN
     *
     * @param   string      $id             Amazon access key
     * @return  array       Returns array with review data, otherwise a
     *                      PEAR_Error.
     * @access  private
     * @author  Andrew Nagy <andrew.nagy@villanova.edu>
     */
    private function amazon($id)
    {
        $params = array('ResponseGroup' => 'Reviews', 'ItemId' => $this->isbn);
        $request = new AWS_Request($id, 'ItemLookup', $params);
        $response = $request->sendRequest();
        if (!PEAR::isError($response)) {
            $unxml = new XML_Unserializer();
            $result = $unxml->unserialize($response);
            if (!PEAR::isError($result)) {
                $data = $unxml->getUnserializedData();
                if ($data['Items']['Item']['CustomerReviews']['Review']['ASIN']) {
                    $data['Items']['Item']['CustomerReviews']['Review'] = array($data['Items']['Item']['CustomerReviews']['Review']);
                }
                $i = 0;
                $result = array();
                if (!empty($data['Items']['Item']['CustomerReviews']['Review'])) {
                    foreach ($data['Items']['Item']['CustomerReviews']['Review'] as $review) {
                        $customer = $this->getAmazonCustomer($id, $review['CustomerId']);
                        if (!PEAR::isError($customer)) {
                            $result[$i]['Source'] = $customer;
                        }
                        $result[$i]['Rating'] = $review['Rating'];
                        $result[$i]['Summary'] = $review['Summary'];
                        $result[$i]['Content'] = $review['Content'];
                        $i++;
                    }
                }
                return $result;
            } else {
                return $result;
            }
        } else {
            return $result;
        }
    }

    /**
     * Amazon Editorial
     *
     * This method is responsible for connecting to Amazon AWS and abstracting
     * editorial reviews for the specific ISBN
     *
     * @param   string      $id             Amazon access key
     * @return  array       Returns array with review data, otherwise a
     *                      PEAR_Error.
     * @access  private
     * @author  Andrew Nagy <andrew.nagy@villanova.edu>
     */
    private function amazoneditorial($id)
    {
        $params = array('ResponseGroup' => 'EditorialReview', 'ItemId' => $this->isbn);
        $request = new AWS_Request($id, 'ItemLookup', $params);
        $response = $request->sendRequest();
        if (!PEAR::isError($response)) {
            $unxml = new XML_Unserializer();
            $result = $unxml->unserialize($response);
            if (!PEAR::isError($result)) {
                $data = $unxml->getUnserializedData();
                if (isset($data['Items']['Item']['EditorialReviews']['EditorialReview']['Source'])) {
                    $data['Items']['Item']['EditorialReviews']['EditorialReview'] = array($data['Items']['Item']['EditorialReviews']['EditorialReview']);
                }
                
                // Filter out product description
                for ($i=0; $i<=count($data['Items']['Item']['EditorialReviews']['EditorialReview']); $i++) {
                    if ($data['Items']['Item']['EditorialReviews']['EditorialReview'][$i]['Source'] == 'Product Description') {
                        unset($data['Items']['Item']['EditorialReviews']['EditorialReview'][$i]);
                    }
                }
                
                return $data['Items']['Item']['EditorialReviews']['EditorialReview'];
            } else {
                return $result;
            }
        } else {
            return $result;
        }
    }

    
    /**
     * syndetics
     *
     * This method is responsible for connecting to Syndetics and abstracting
     * reviews from multiple providers.
     *
     * It first queries the master url for the ISBN entry seeking a review URL.
     * If a review URL is found, the script will then use HTTP request to
     * retrieve the script. The script will then parse the review according to
     * US MARC (I believe). It will provide a link to the URL master HTML page
     * for more information.
     * Configuration:  Sources are processed in order - refer to $sourceList.
     * If your library prefers one reviewer over another change the order.
     * If your library does not like a reviewer, remove it.  If there are more
     * syndetics reviewers add another entry.
     *
     * @param   string  $id Client access key
     * @return  array       Returns array with review data, otherwise a
     *                      PEAR_Error.
     * @access  private
     * @author  Joel Timothy Norman <joel.t.norman@wmich.edu>
     * @author  Andrew Nagy <andrew.nagy@villanova.edu>
     */
    private function syndetics($id)
    {
        global $configArray;

        //list of syndetic reviews
        $sourceList = array('CHREVIEW' => array('title' => 'Choice Review',
                                                'file' => 'CHREVIEW.XML'),
                            'BLREVIEW' => array('title' => 'Booklist Review',
                                                'file' => 'BLREVIEW.XML'),
                            'PWREVIEW' => array('title' => "Publisher's Weekly Review",
                                                'file' => 'PWREVIEW.XML'),
                            'SLJREVIEW' => array('title' => 'School Library Journal Review',
                                                'file' => 'SLJREVIEW.XML'),
                            'HBREVIEW' => array('title' => 'Horn Book Review',
                                                'file' => 'HBREVIEW.XML'),
                            'KIREVIEW' => array('title' => 'Kirkus Book Review',
                                                'file' => 'KIREVIEW.XML'),
                            'CRITICASEREVIEW' => array('title' => 'Criti Case Review',
                                                'file' => 'CRITICASEREVIEW.XML'));
                            
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
                $responseBody = $client->getResponseBody();
                if (!($xmldoc2 = @DOMDocument::loadXML($responseBody))) {
                    return new PEAR_Error('Invalid XML');
                }

                // Get the marc field for reviews (520)
                $nodes = $xmldoc2->GetElementsbyTagName("Fld520");
                if (!$nodes->length) {
                    // Skip reviews with missing text
                    continue;
                }
                $review[$i]['Content'] = html_entity_decode($xmldoc2->saveXML($nodes->item(0)));

                // Get the marc field for copyright (997)
                $nodes = $xmldoc2->GetElementsbyTagName("Fld997");
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
                $review[$i]['username'] = $configArray['BookReviews']['id'];
                
                $i++;
            }
        }

        return $review;
    }
    
    /**
     * Get the name of an Amazon customer.
     *
     * @access  private
     * @param   string      $id             Amazon access key
     * @param   string      $customerId     Amazon customer to look up
     * @return  string                      Customer name, if available.
     */
    private function getAmazonCustomer($id, $customerId)
    {
        $params = array('ResponseGroup' => 'CustomerInfo', 'CustomerId' => $customerId);
        $request = new AWS_Request($id, 'CustomerContentLookup', $params);
        $response = $request->sendRequest();
        if (!PEAR::isError($response)) {
            $unxml = new XML_Unserializer();
            $result = $unxml->unserialize($response);
            if (!PEAR::isError($result)) {
                $data = $unxml->getUnserializedData();
                if (isset($data['Customers']['Customer']['Name'])) {
                    return $data['Customers']['Customer']['Name'];
                } elseif (isset($data['Customers']['Customer']['Nickname'])) {
                    return $data['Customers']['Customer']['Nickname'];
                } else {
                    return 'Anonymous';
                }
            } else {
                return $result;
            }
        } else {
            return $result;
        }
    }
}
?>