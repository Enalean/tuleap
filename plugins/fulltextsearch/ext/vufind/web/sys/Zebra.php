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

require_once 'XML/Unserializer.php';
require_once 'XML/Serializer.php';
require_once 'sys/Proxy_Request.php';

/**
 * Zebra SRU Search Interface
 *
 * SRU Interface for the Zebra Index Engine
 *
 * @version     $Revision$
 * @author      Andrew S. Nagy <andrew.nagy@villanova.edu>
 * @access      public
 */
class Zebra implements IndexEngine {
    /**
     * A boolean value detemrining whether to print debug information
     * @var bool
     */
    public $debug = false;

    /**
     * Whether to Serialize to a PHP Array or not.
     * @var bool
     */
    public $raw = false;

    /**
     * The HTTP_Request object used for REST transactions
     * @var object HTTP_Request
     */
    public $client;
    
    /**
     * The host to connect to
     * @var string
     */
    public $host;

    /**
     * The version to specify in the URL
     * @var string
     */
    public $sruVersion = '1.1';

    /**
     * Constructor
     *
     * Sets up the SOAP Client
     *
     * @param   string  $host       The URL of the eXist Server
     * @access  public
     */     
    function __construct($host)
    {
        global $configArray;
    
        $this->host = $host;
        $this->client = new Proxy_Request(null, array('useBrackets' => false));
        
        if ($configArray['System']['debug']) {
            $this->debug = true;
        }
    }

    /**
     * Retrieves a document specified by the ID and returns a MARC record.
     *
     * @param   string  $id         The document to retrieve from Solr
     * @access  public
     * @throws  object              PEAR Error
     * @return  string              The requested resource
     */
    function getRecord($id)
    {
        if ($this->debug) {
            echo "<pre>Get Record: $id</pre>\n";
        }

        // Query String Parameters
        $options = array('operation' => 'searchRetrieve',
                         'query' => "rec.id=$id",
                         'maximumRecords' => 1,
                         'startRecord' => 1,
                         'recordSchema' => 'marcxml');

    	$result = $this->_call('GET', 'select', $options, false);
    	if (PEAR::isError($result)) {
            PEAR::raiseError($result);
        }
        
        $style = new DOMDocument;
        $style->load('xsl/zebra-marcxml.xsl');
        $xsl = new XSLTProcessor();
        $xsl->importStyleSheet($style);
        $xml = new DOMDocument;
        $xml->loadXML($result);
        $marcxml = $xsl->transformToXML($xml);
        
        // Bad Hack - Use a marcxml conversion tool!
        file_put_contents('/tmp/marcxml.xml', $marcxml);
        $marc = shell_exec("yaz-marcdump -i marcxml -o marc /tmp/marcxml.xml");
        unlink('/tmp/marcxml.xml');
        
        return $marc;
    }

    /**
     * Build Query string from search parameters
     *
     * @access  public
     * @param   array               An array of search parameters
     * @throws  object              PEAR Error
     * @static
     * @return  array               An array of query results
     */
    function buildQuery($search)
    {
        foreach ($search as $params) {
            if ($params['lookfor'] != '') {
                $query = (isset($query)) ? $query . ' ' . $params['bool'] . ' ' : '';
                switch ($params['field']) {
                    case 'title':
                        $query .= 'dc.title="' . $params['lookfor'] . '" OR ';
                        $query .= 'dc.title=' . $params['lookfor'];
                        break;
                    case 'id':
                        $query .= 'rec.id=' . $params['lookfor'];
                        break;
                    case 'author':
                        preg_match_all('/"[^"]*"|[^ ]+/', $params['lookfor'], $wordList);
                        $author = array();
                        foreach ($wordList[0] as $phrase) {
                            if (substr($phrase, 0, 1) == '"') {
                                $arr = explode(' ', substr($phrase, 1, strlen($phrase)-2));
                                $author[] = implode(' AND ', $arr);
                            } else {
                                $author[] = $phrase;
                            }
                        }
                        $author = implode(' ', $author);
                        $query .= 'dc.creator any "' . $author . '" OR';
                        $query .= 'dc.creator any ' . $author;
                        break;
                    case 'callnumber':
                        break;
                    case 'publisher':
                        break;
                    case 'year':
                        $query = 'dc.date=' . $params['lookfor'];
                        break;
                    case 'series':
                        break;
                    case 'language':
                        break;
                    case 'toc':
                        break;
                    case 'topic':
                        break;
                    case 'geo':
                        break;
                    case 'era':
                        break;
                    case 'genre':
                        break;
                    case 'subject':
                        break;
                    case 'isn':
                        break;
                    case 'all':
                    default:
                        $query = 'dc.title="' . $params['lookfor'] . '" OR dc.title=' . $params['lookfor'] . ' OR ' .
                                 'dc.creator="' . $params['lookfor'] . '" OR dc.creator=' . $params['lookfor'] . ' OR ' .
                                 'dc.subject="' . $params['lookfor'] . '" OR dc.subject=' . $params['lookfor'] . ' OR ' .
                                 'dc.description=' . $params['lookfor'] . ' OR ' .
                                 'dc.date=' . $params['lookfor'];
                        break;
                }
            }
        }

        return $query;
    }

    /**
     * Get records similiar to one record
     *
     * @access  public
     * @param   array       An associative array of the record data
     * @param   id          The record id
     * @param   max         The maximum records to return; Default is 5
     * @throws  object      PEAR Error
     * @return  array       An array of query results
     */
    function getMoreLikeThis($record, $id, $max = 5)
    {
        global $configArray;

        // More Like This Query
        $query = 'title="' . $record['245']['a'] . '" ' .
                 "NOT rec.id=$id";
    
        // Query String Parameters
        $options = array('operation' => 'searchRetrieve',
                         'query' => $query,
                         'maximumRecords' => $max,
                         'startRecord' => 1,
                         'recordSchema' => 'marcxml');

        if ($this->debug) {
            echo '<pre>More Like This Query: ';
            print_r($query);
            echo "</pre>\n";
        }

    	$result = $this->_call('GET', 'select', $options);
    	if (PEAR::isError($result)) {
            PEAR::raiseError($result);
        }

        return $result;
    }

    /**
     * Search
     *
     * @param   string  $query      The XQuery script in binary encoding.
     * @param   array   $filter     The fields and values to filter results on
     * @param   string  $start      The record to start with
     * @param   string  $limit      The amount of records to return
     * @param   string  $sortBy     The value to be used by for sorting
     * @param   array   $facet      An array of fields to return as facets
     * @access  public
     * @throws  object              PEAR Error
     * @return  array               An array of query results
     */
	function search($query, $filter = null, $start = 1, $limit = null, $sortBy = null, $facet = null)
	{
        if ($this->debug) {
            echo '<pre>Query: ';
            print_r($query);
            if ($filter) {
                echo "\nFilter: ";
                foreach ($filter as $filterItem) {
                    echo " $filterItem";
                }
            }
            echo "</pre>\n";
                        
        }

        // Query String Parameters
        $options = array('operation' => 'searchRetrieve',
                         'query' => $query,
                         'maximumRecords' => $limit,
                         'startRecord' => ($start) ? $start : 1,
                         'recordSchema' => 'marcxml');
                         
        // Sorting
        if ($sortBy) {
            $options['sortKeys'] = $sortBy;
        }
        
        // Build Filter Query
        if (is_array($filter) && count($filter)) {
            $options['query'] = $options['query'] . ' AND ' . $filter;
        }

        // Build Facet Options
        if ($facet) {
            foreach ($facet['field'] as $field) {
                $options['recordSchema'] = "zebra::facet::$field";
            }
        }

    	$result = $this->_call('GET', 'select', $options);
    	if (PEAR::isError($result)) {
            PEAR::raiseError($result);
        }
        
        return $result;
	}

    /**
     * Submit REST Request
     *
     * @param   string      $method     HTTP Method to use: GET, POST, 
     * @param   string      $action     A string to determine which SRU action
     *                                  to process
     * @param   array       $params     An array of parameters for the request
     * @param   bool        $process    A boolean value to determine whether or
     *                                  not to convert the MARCXML
     * @return  string                  The response from the XServer
     * @access  private
     */	
    function _call($method = HTTP_REQUEST_METHOD_GET, $action, $params = null,
                   $process = true)
	{
        if ($params) {
            $query = array('version='.$this->sruVersion);
            foreach ($params as $function => $value) {
                if(is_array($value)) {
                    foreach ($value as $additional) {
                        $additional = urlencode($additional);
                        $query[] = "$function=$additional";
                    }
                } else {
                    $value = urlencode($value);
                    $query[] = "$function=$value";
                }
            }
            $url = implode('&', $query);
        }
        
        if ($this->debug) {
            echo '<pre>Connect: ';
            print_r($this->host . '?' . $url);
            echo "</pre>\n";
        }
        
        $this->client->setMethod($method);
    	$this->client->setURL($this->host);
    	$this->client->addRawQueryString($url);
        $result = $this->client->sendRequest();
        
        if (!PEAR::isError($result)) {
            if ($process) {
                return $this->_process($this->client->getResponseBody());
            } else {
                return $this->client->getResponseBody();
            }
        } else {
            return $result;
        }
	}

    
	function _process($result)
	{
        global $configArray;
	
        $xsl = new XSLTProcessor();

        $style = new DOMDocument;
        $style->load('xsl/zebra-convert.xsl');
        $xsl->importStyleSheet($style);
        
        $xml = new DOMDocument;
        $xml->loadXML($result);
    	
        $result = $xsl->transformToXML($xml);
        
        if ($this->raw) {
            return $result;
        } else {
        	$unxml = new XML_Unserializer();
        	$result = $unxml->unserialize($result);
        	if (!PEAR::isError($result)) {
                return $unxml->getUnserializedData();
            } else {
                PEAR::raiseError($result);
            }
        }
        
        return null;
	}
}

?>
