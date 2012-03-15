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
require_once 'sys/IndexEngine.php';
require_once 'sys/Proxy_Request.php';
require_once 'sys/ConfigArray.php';
require_once 'sys/SolrUtils.php';

require_once 'XML/Unserializer.php';
require_once 'XML/Serializer.php';

/**
 * Solr HTTP Interface
 *
 * @version     $Revision: 1.13 $
 * @author      Andrew S. Nagy <andrew.nagy@villanova.edu>
 * @access      public
 */
class Solr implements IndexEngine {
    /**
     * A boolean value determining whether to print debug information
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
     * The status of the connection to Solr
     * @var string
     */
    public $status = false;
    
    /**
     * An array of characters that are illegal in search strings
     */
    private $illegal = array('!', ':', ';', '[', ']', '{', '}');

    /**
     * The path to the YAML file specifying available search types:
     */
    protected $searchSpecsFile = 'conf/searchspecs.yaml';

    /**
     * An array of search specs pulled from $searchSpecsFile (above)
     */
    private $searchSpecs = false;

    /**
     * Should boolean operators in the search string be treated as
     * case-insensitive (false), or must they be ALL UPPERCASE (true)?
     */
    private $caseSensitiveBooleans = true;
    
    /**
     * Constructor
     *
     * Sets up the SOAP Client
     *
     * @param   string  $host       The URL for the local Solr Server
     * @access  public
     */     
    function __construct($host, $index = '')
    {
        global $configArray;

        // Set a default Solr index if none is provided to the constructor:
        if (empty($index)) {
            $index = isset($configArray['Index']['default_core']) ? 
                $configArray['Index']['default_core'] : "biblio";
        }
     
        $this->host = $host . '/' . $index;

        // Test to see solr is online
        $test_url = $this->host . "/admin/ping";
        $test_client = new Proxy_Request();
        $test_client->setMethod(HTTP_REQUEST_METHOD_GET);
        $test_client->setURL($test_url);
        $result = $test_client->sendRequest();
        if (!PEAR::isError($result)) {
            // Even if we get a response, make sure it's a 'good' one.
            if ($test_client->getResponseCode() != 200) {
                PEAR::raiseError('Solr index is offline.');
            }
        } else {
            PEAR::raiseError($result);
        }

        // If we're still processing then solr is online
        $this->client = new Proxy_Request(null, array('useBrackets' => false));

        // Read in preferred boolean behavior:
        $searchSettings = getExtraConfigArray('searches');
        if (isset($searchSettings['General']['case_sensitive_bools'])) {
            $this->caseSensitiveBooleans = 
                $searchSettings['General']['case_sensitive_bools'];
        }
    }

    /**
     * Is this object configured with case-sensitive boolean operators?
     *
     * @access  public
     * @return  boolean
     */
    public function hasCaseSensitiveBooleans()
    {
        return $this->caseSensitiveBooleans;
    }

    /**
     * Get the search specifications loaded from the specified YAML file.
     *
     * @access  private
     * @param   string  $handler    The named search to provide information about
     *                              (set to null to get all search specifications)
     * @return  mixed               Search specifications array if available, false
     *                              if an invalid search is specified.
     */
    private function getSearchSpecs($handler = null)
    {
        // Only load specs once:
        if ($this->searchSpecs === false) {
            $this->searchSpecs = 
                Horde_Yaml::load(file_get_contents($this->searchSpecsFile));
        }
        
        // Special case -- null $handler means we want all search specs.
        if (is_null($handler)) {
            return $this->searchSpecs;
        }
        
        // Return specs on the named search if found (easiest, most common case).
        if (isset($this->searchSpecs[$handler])) {
            return $this->searchSpecs[$handler];
        }
        
        // Check for a case-insensitive match -- this provides backward 
        // compatibility with different cases used in early VuFind versions
        // and allows greater tolerance of minor typos in config files.
        foreach($this->searchSpecs as $name => $specs) {
            if (strcasecmp($name, $handler) == 0) {
                return $specs;
            }
        }
        
        // If we made it this far, no search specs exist -- return false.
        return false;
    }
    
    /**
     * Retrieves a document specified by the ID.
     *
     * @param   string  $id         The document to retrieve from Solr
     * @access  public
     * @throws  object              PEAR Error
     * @return  string              The requested resource (or null if bad ID)
     */
    function getRecord($id)
    {
        if ($this->debug) {
            echo "<pre>Get Record: $id</pre>\n";
        }

        // Query String Parameters
        $options = array('q' => "id:\"$id\"");
        $result = $this->_select('GET', $options);
        if (PEAR::isError($result)) {
            PEAR::raiseError($result);
        }

        return isset($result['response']['docs'][0]) ?
            $result['response']['docs'][0] : null;
    }

    /**
     * Get records similiar to one record
     * Uses MoreLikeThis Request Handler
     *
     * Uses SOLR MLT Query Handler
     *
     * @access  public
     * @throws  object              PEAR Error
     * @return  array               An array of query results
     *
     */
    function getMoreLikeThis($id)
    {
        // Query String Parameters
        $options = array('q' => "id:$id", 'qt' => 'morelikethis');
        $result = $this->_select('GET', $options);
        if (PEAR::isError($result)) {
            PEAR::raiseError($result);
        }

        return $result;
    }
    
    /**
     * Get record data based on the provided field and phrase.
     * Used for AJAX suggestions.
     *
     * @access  public
     * @param   string  $phrase     The input phrase
     * @param   string  $field      The field to search on
     * @param   int     $limit      The number of results to return
     * @return  array   An array of query results
     */
    function getSuggestion($phrase, $field, $limit)
    {
        if (!strlen($phrase)) {
            return null;
        }

        // Ignore illegal characters
        $phrase = str_replace($this->illegal, '', $phrase);

        // Process Search
        $query = "$field:($phrase*)";
        $result = $this->search($query, null, null, 0, $limit, array('field' => $field, 'limit' => $limit));
        return $result['facet_counts']['facet_fields'][$field];
    }
    
    /**
     * Get spelling suggestions based on input phrase.
     *
     * @access  public
     * @param   string  $phrase     The input phrase
     * @return  array   An array of spelling suggestions
     */
    function checkSpelling($phrase)
    {
        if ($this->debug) {
            echo "<pre>Spell Check: $phrase</pre>\n";
        }

        // Query String Parameters
        $options = array(
            'q'          => $phrase,
            'rows'       => 0,
            'start'      => 1,
            'indent'     => 'yes',
            'spellcheck' => 'true'
        );

        $result = $this->_select($method, $options);
        if (PEAR::isError($result)) {
            PEAR::raiseError($result);
        }

        return $result;
    }

     /**
      * applySearchSpecs -- internal method to build query string from search parameters
      *
      * @access  private
      * @param   structure           the SearchSpecs-derived structure or substructure defining the search, derived from the yaml file
      * @param   values              the various values in an array with keys 'onephrase', 'and', 'or' (and perhaps others)
      * @throws  object              PEAR Error
      * @static
      * @return  string              A search string suitable for adding to a query URL
      */
    private function applySearchSpecs($structure, $values, $joiner = "OR") 
    {
        $clauses = array();
        foreach ($structure as $field => $clausearray) {
            if (is_numeric($field)) {
                // shift off the join string and weight
                $sw = array_shift($clausearray);
                $internalJoin = ' ' . $sw[0] . ' ';
                // Build it up recursively 
                $sstring = '(' .  $this->applySearchSpecs($clausearray, $values, $internalJoin) . ')';
                // ...and add a weight if we have one
                $weight = $sw[1];
                if(!is_null($weight) && $weight && $weight > 0) {
                   $sstring .= '^' . $weight;
                }
                // push it onto the stack of clauses
                $clauses[] = $sstring;
            } else {
                // Otherwise, we've got a (list of) [munge, weight] pairs to deal with
                foreach ($clausearray as $spec) {
                    // build a string like title:("one two")
                    $sstring = $field . ':(' . $values[$spec[0]] . ')';
                    // Add the weight it we have one. Yes, I know, it's redundant code.
                    $weight = $spec[1];
                    if(!is_null($weight) && $weight && $weight > 0) {
                        $sstring .= '^' . $weight;
                    }
                    // ..and push it on the stack of clauses
                    $clauses[] = $sstring;
                }
            }
        }
       
        // Join it all together
        return implode(' ' . $joiner . ' ', $clauses);
    }

    /**
     * Given a field name and search string, return an array containing munged
     * versions of the search string for use in applySearchSpecs().
     *
     * @access  private
     * @param   string  $field      The YAML search spec field name to search
     * @param   string  $lookfor    The string to search for in the field
     * @param   array   $custom     Custom munge settings from YAML search specs
     * @param   bool    $tokenize   Should we tokenize $lookfor or pass it through?
     * @return  array               Array for use as applySearchSpecs() values param
     */
    private function buildMungeValues($field, $lookfor, $custom = null, $tokenize = true)
    {
        if ($tokenize) {
            // Tokenize Input
            $tokenized = $this->tokenizeInput($lookfor);
            
            // Create AND'd and OR'd queries
            $andQuery = implode(' AND ', $tokenized);
            $orQuery = implode(' OR ', $tokenized);
            
            // Build possible inputs for searching:
            $values = array();
            $values['onephrase'] = '"' . str_replace('"', '', implode(' ', $tokenized)) . '"';
            $values['and'] = $andQuery;
            $values['or'] = $orQuery;
        } else {
            // If we're skipping tokenization, we just want to pass $lookfor through
            // unmodified (it's probably an advanced search that won't benefit from
            // tokenization).  We'll just set all possible values to the same thing,
            // except that we'll try to do the "one phrase" in quotes if possible.
            $onephrase = strstr($lookfor, '"') ? $lookfor : '"' . $lookfor . '"';
            $values = array('onephrase' => $onephrase, 'and' => $lookfor, 'or' => $lookfor);
        }
        
        // Apply custom munge operations if necessary:
        if (is_array($custom)) {
            foreach($custom as $mungeName => $mungeOps) {
                $values[$mungeName] = $lookfor;
                
                // Skip munging if tokenization is disabled.
                if ($tokenize) {
                    foreach($mungeOps as $operation) {
                        switch($operation[0]) {
                            case 'append':
                                $values[$mungeName] .= $operation[1];
                                break;
                            case 'lowercase':
                                $values[$mungeName] = strtolower($values[$mungeName]);
                                break;
                            case 'preg_replace':
                                $values[$mungeName] = preg_replace($operation[1], 
                                    $operation[2], $values[$mungeName]);
                                break;
                            case 'uppercase':
                                $values[$mungeName] = strtoupper($values[$mungeName]);
                                break;
                        }
                    }
                }
            }
        }
        
        return $values;
    }
    
    /**
     * Given a field name and search string, expand this into the necessary Lucene
     * query to perform the specified search on the specified field(s).
     *
     * @access  private
     * @param   string  $field      The YAML search spec field name to search
     * @param   string  $lookfor    The string to search for in the field
     * @param   bool    $tokenize   Should we tokenize $lookfor or pass it through?
     * @return  string              The query
     */
    private function buildQueryComponent($field, $lookfor, $tokenize = true)
    {
        // Load the YAML search specifications:
        $ss = $this->getSearchSpecs($field);
        
        // If we received a field spec that wasn't defined in the YAML file,
        // let's try simply passing it along to Solr.
        if ($ss === false) {
            return $field . ':(' . $lookfor . ')';
        }

        // Munge the user query in a few different ways:
        $customMunge = isset($ss['CustomMunge']) ? $ss['CustomMunge'] : null;
        $values = $this->buildMungeValues($field, $lookfor, $customMunge, $tokenize);
                
        // Apply the $searchSpecs property to the data:
        $baseQuery = $this->applySearchSpecs($ss['QueryFields'], $values);
        
        // Apply filter query if applicable:
        if (isset($ss['FilterQuery'])) {
            return "({$baseQuery}) AND ({$ss['FilterQuery']})";
        }

        return "($baseQuery)";
    }
    
    /**
     * Given a field name and search string known to contain advanced features
     * (as identified by isAdvanced()), expand this into the necessary Lucene 
     * query to perform the specified search on the specified field(s).
     *
     * @access  private
     * @param   string  $field      The YAML search spec field name to search
     * @param   string  $lookfor    The string to search for in the field
     * @return  string              The query
     */
    private function buildAdvancedQuery($handler, $query)
    {
        // Special case -- if the user wants all records but the current handler
        // has a filter query, apply the filter query:
        if (trim($query) == '*:*') {
            $ss = $this->getSearchSpecs($handler);
            if (isset($ss['FilterQuery'])) {
                return $ss['FilterQuery'];
            }
        }

        // Strip out any colons that are NOT part of a field specification:
        $query = preg_replace('/(\:\s+|\s+:)/', ' ', $query);

        // If the query already includes field specifications, we can't easily
        // apply it to other fields through our defined handlers, so we'll leave
        // it as-is:
        if (strstr($query, ':')) {
            return $query;
        }

        // Convert empty queries to return all values in a field:
        if (empty($query)) {
            $query = '[* TO *]';
        }

        // If the query ends in a question mark, the user may not really intend to
        // use the question mark as a wildcard -- let's account for that possibility
        if (substr($query, -1) == '?') {
            $query = "({$query}) OR (" . substr($query, 0, strlen($query) - 1) . ")";
        }

        // We're now ready to use the regular YAML query handler but with the 
        // $tokenize parameter set to false so that we leave the advanced query
        // features unmolested.
        return $this->buildQueryComponent($handler, $query, false);
    }
    
    /**
     * Build Query string from search parameters
     *
     * @access  public
     * @param   array   $search     An array of search parameters
     * @throws  object              PEAR Error
     * @static
     * @return  string              The query
     */
    function buildQuery($search)
    {
        $groups   = array();
        $excludes = array();
        if (is_array($search)) {
            $query = '';

            foreach ($search as $params) {

                // Advanced Search
                if (isset($params['group'])) {
                    $thisGroup = array();
                    // Process each search group
                    foreach ($params['group'] as $group) {
                        // Build this group individually as a basic search
                        $thisGroup[] = $this->buildQuery(array($group));
                    }
                    // Is this an exclusion (NOT) group or a normal group?
                    if ($params['group'][0]['bool'] == 'NOT') {
                        $excludes[] = join(" OR ", $thisGroup);
                    } else {
                        $groups[] = join(" ".$params['group'][0]['bool']." ", $thisGroup);
                    }
                }

                // Basic Search
                if (isset($params['lookfor']) && $params['lookfor'] != '') {
                    // Clean and validate input
                    $lookfor = $this->validateInput($params['lookfor']);

                    // Force boolean operators to uppercase if we are in a case-insensitive
                    // mode:
                    if (!$this->caseSensitiveBooleans) {
                        $lookfor = SolrUtils::capitalizeBooleans($lookfor);
                    }

                    if (isset($params['field']) && ($params['field'] != '')) {
                        if ($this->isAdvanced($lookfor)) {
                            $query .= $this->buildAdvancedQuery($params['field'], $lookfor);
                        } else {
                            $query .= $this->buildQueryComponent($params['field'], $lookfor);
                        }
                    } else {
                        $query .= $lookfor;
                    }
                }
            }
        }

        // Put our advanced search together
        if (count($groups) > 0) {
            $query = "(" . join(") " . $search[0]['join'] . " (", $groups) . ")";
        }
        // and concatenate exclusion after that
        if (count($excludes) > 0) {
            $query .= " NOT ((" . join(") OR (", $excludes) . "))";
        }

        // Ensure we have a valid query to this point
        if (!isset($query) || $query  == '') {
            $query = '*:*';
        }

        return $query;
    }

    /**
     * Normalize a sort option.
     *
     * @param   string  $sort       The sort option.
     * @access  protected
     * @return  string              The normalized sort value.
     */
    private function normalizeSort($sort)
    {
        // Break apart sort into field name and sort direction (note error
        // suppression to prevent notice when direction is left blank):
        @list($sortField, $sortDirection) = explode(' ', $sort);

        // Default sort order (may be overridden by switch below):
        $defaultSortDirection = 'asc';

        // Translate special sort values into appropriate Solr fields:
        switch ($sortField) {
            case 'year':
            case 'publishDate':
                $sortField = 'publishDate';
                $defaultSortDirection = 'desc';
                break;
            case 'author':
                $sortField = 'authorStr';
                break;
            case 'title':
                $sortField = 'title_sort';
                break;
        }

        // Normalize sort direction to either "asc" or "desc":
        $sortDirection = strtolower(trim($sortDirection));
        if ($sortDirection != 'desc' && $sortDirection != 'asc') {
            $sortDirection = $defaultSortDirection;
        }

        return $sortField . ' ' . $sortDirection;
    }

    /**
     * Execute a search.
     *
     * @param   string  $query      The XQuery script in binary encoding.
     * @param   string  $handler    The Query Handler to use (null for default)
     * @param   array   $filter     The fields and values to filter results on
     * @param   string  $start      The record to start with
     * @param   string  $limit      The amount of records to return
     * @param   array   $facet      An array of faceting options
     * @param   string  $spell      Phrase to spell check
     * @param   string  $dictionary Spell check dictionary to use
     * @param   string  $sort       Field name to use for sorting
     * @param   string  $fields     A list of fields to be returned
     * @param   string  $method     Method to use for sending request (GET/POST)
     * @param   bool    $returnSolrError    If Solr reports a syntax error, 
     *                                      should we fail outright (false) or
     *                                      treat it as an empty result set with
     *                                      an error key set (true)?
     * @access  public
     * @throws  object              PEAR Error
     * @return  array               An array of query results
     * @todo    Change solr to lookup an explicit list of fields to optimize
     *          memory load
     */
    function search($query, $handler = null, $filter = null, $start = 0,
                    $limit = 20, $facet = null, $spell = '', $dictionary = null,
                    $sort = null, $fields = null, 
                    $method = HTTP_REQUEST_METHOD_POST, $returnSolrError = false)
    {
        // Query String Parameters
        $options = array('q' => $query, 'rows' => $limit, 'start' => $start, 'indent' => 'yes');

        // Add Sorting
        if ($sort && !empty($sort)) {
            // There may be multiple sort options (ranked, with tie-breakers); process
            // each individually, then assemble them back together again:
            $sortParts = explode(',', $sort);
            for($x = 0; $x < count($sortParts); $x++) {
                $sortParts[$x] = $this->normalizeSort($sortParts[$x]);
            }
            $options['sort'] = implode(',', $sortParts);
        }

        // Determine which handler to use
        if (!$this->isAdvanced($query)) {
            $ss = is_null($handler) ? null : $this->getSearchSpecs($handler);
            // Is this a Dismax search?
            if (isset($ss['DismaxFields'])) {
                // Specify the fields to do a Dismax search on:
                $options['qf'] = implode(' ', $ss['DismaxFields']);

                // Specify the default dismax search handler so we can use any 
                // global settings defined by the user:
                $options['qt'] = 'dismax';

                // Load any custom Dismax parameters from the YAML search spec file:
                if (isset($ss['DismaxParams']) && 
                    is_array($ss['DismaxParams'])) {
                    foreach($ss['DismaxParams'] as $current) {
                        $options[$current[0]] = $current[1];
                    }
                }

                // Apply search-specific filters if necessary:
                if (isset($ss['FilterQuery'])) {
                    if (is_array($filter)) {
                        $filter[] = $ss['FilterQuery'];
                    } else {
                        $filter = array($ss['FilterQuery']);
                    }
                }
            } else {
                // Not DisMax... but do we need to format the query based on
                // a setting in the YAML search specs?  If $ss is an array
                // at this point, it indicates that we found YAML details.
                if (is_array($ss)) {
                    $options['q'] = $this->buildQueryComponent($handler, $query);
                } else if (!empty($handler)) {
                    $options['q'] = "({$handler}:{$query})";
                }
            }
        } else {
            // Force boolean operators to uppercase if we are in a case-insensitive
            // mode:
            if (!$this->caseSensitiveBooleans) {
                $query = SolrUtils::capitalizeBooleans($query);
            }
        
            // Process advanced search -- if a handler was specified, let's see
            // if we can adapt the search to work with the appropriate fields.
            if (!empty($handler)) {
                $options['q'] = $this->buildAdvancedQuery($handler, $query);
            }
        }
        
        // Limit Fields
        if ($fields) {
            $options['fl'] = $fields;
        } else {
            // This should be an explicit list
            $options['fl'] = '*,score';
        }

        // Build Facet Options
        if ($facet && !empty($facet['field'])) {
            $options['facet'] = 'true';
            $options['facet.mincount'] = 1;
            $options['facet.limit'] = (isset($facet['limit'])) ? $facet['limit'] : null;
            unset($facet['limit']);
            $options['facet.field'] = (isset($facet['field'])) ? $facet['field'] : null;
            unset($facet['field']);
            $options['facet.prefix'] = (isset($facet['prefix'])) ? $facet['prefix'] : null;
            unset($facet['prefix']);
            $options['facet.sort'] = (isset($facet['sort'])) ? $facet['sort'] : null;
            unset($facet['sort']);
            if (isset($facet['offset'])) {
                $options['facet.offset'] = $facet['offset'];
                unset($facet['offset']);
            }
            foreach($facet as $param => $value) {
                $options[$param] = $value;
            }
        }
        
        // Build Filter Query
        if (is_array($filter) && count($filter)) {
            $options['fq'] = $filter;
        }

        // Enable Spell Checking
        if ($spell != '') {
            $options['spellcheck'] = 'true';
            $options['spellcheck.q'] = $spell;
            if ($dictionary != null) {
                $options['spellcheck.dictionary'] = $dictionary;
            }
        }

        if ($this->debug) {
            echo '<pre>Search options: ' . print_r($options, true) . "\n";
            
            if ($filter) {
                echo "\nFilterQuery: ";
                foreach ($filter as $filterItem) {
                    echo " $filterItem";
                }
            }
            
            if ($sort) {
                echo "\nSort: " . $options['sort'];
            }
            
            echo "</pre>\n";
        }

        $result = $this->_select($method, $options, $returnSolrError);
        if (PEAR::isError($result)) {
            PEAR::raiseError($result);
        }
    
        return $result;
    }

    /**
     * Convert an array of fields into XML for saving to Solr.
     *
     * @param   array   $fields     Array of fields to save
     * @return  string              XML document ready for posting to Solr.
     * @access  public
     */
    public function getSaveXML($fields)
    {
        // Create XML Document
        $doc = new DOMDocument('1.0', 'UTF-8');

        // Create add node
        $node = $doc->createElement('add');
        $addNode = $doc->appendChild($node);

        // Create doc node
        $node = $doc->createElement('doc');
        $docNode = $addNode->appendChild($node);

        // Add fields to XML docuemnt
        foreach ($fields as $field => $value) {
            // Normalize current value to an array for convenience:
            if (!is_array($value)) {
                $value = array($value);
            }
            // Add all non-empty values of the current field to the XML:
            foreach($value as $current) {
                if ($current != '') {
                    $node = $doc->createElement('field', htmlspecialchars($current, ENT_COMPAT, 'UTF-8'));
                    $node->setAttribute('name', $field);
                    $docNode->appendChild($node);
                }
            }
        }
        
        return $doc->saveXML();
    }
    
    /**
     * Save Record to Database
     *
     * @param   string  $xml        XML document to post to Solr
     * @return  mixed               Boolean true on success or PEAR_Error
     * @access  public
     */
    function saveRecord($xml)
    {
        if ($this->debug) {
            echo "<pre>Add Record</pre>\n";
        }

        $result = $this->_update($xml);
        if (PEAR::isError($result)) {
            PEAR::raiseError($result);
        }

        return $result;
    }
    
    /**
     * Delete Record from Database
     *
     * @param   string  $id         ID for record to delete
     * @return  boolean             
     * @access  public
     */
    function deleteRecord($id)
    {
        if ($this->debug) {
            echo "<pre>Delete Record: $id</pre>\n";
        }

        $body = "<delete><id>$id</id></delete>";

        $result = $this->_update($body);
        if (PEAR::isError($result)) {
            PEAR::raiseError($result);
        }
        
        return $result;
    }

    /**
     * Delete Record from Database
     *
     * @param   string  $idList     Array of IDs for record to delete
     * @return  boolean
     * @access  public
     */
    function deleteRecords($idList)
    {
        if ($this->debug) {
            echo "<pre>Delete Record List</pre>\n";
        }

        // Delete XML
        $body = '<delete>';
        foreach ($idList as $id) {
            $body .= "<id>$id</id>";
        }
        $body .= '</delete>';

        $result = $this->_update($body);
        if (PEAR::isError($result)) {
            PEAR::raiseError($result);
        }

        return $result;
    }

    /**
     * Commit
     *
     * @return  string
     * @access  public
     */
    function commit()
    {
        if ($this->debug) {
            echo "<pre>Commit</pre>\n";
        }

        $body = '<commit/>';

        $result = $this->_update($body);
        if (PEAR::isError($result)) {
            PEAR::raiseError($result);
        }

        return $result;
    }

    /**
     * Optimize
     *
     * @return  string
     * @access  public
     */
    function optimize()
    {
        if ($this->debug) {
            echo "<pre>Optimize</pre>\n";
        }

        $body = '<optimize/>';

        $result = $this->_update($body);
        if (PEAR::isError($result)) {
            PEAR::raiseError($result);
        }

        return $result;
    }

    /**
     * Submit REST Request to read data
     *
     * @param   string      $method             HTTP Method to use: GET, POST, 
     * @param   array       $params             Array of parameters for the request
     * @param   bool        $returnSolrError    If Solr reports a syntax error, 
     *                                          should we fail outright (false) or
     *                                          treat it as an empty result set with
     *                                          an error key set (true)?
     * @return  array                           The Solr response (or a PEAR error)
     * @access  private
     */
    private function _select($method = HTTP_REQUEST_METHOD_GET, $params = array(), $returnSolrError = false)
    {
        $this->client->setMethod($method);
        $this->client->setURL($this->host . "/select/");
    
        $params['wt'] = 'json';
        $params['json.nl'] = 'arrarr';

        // Build query string for use with GET or POST:
        $query = array();
        if ($params) {
            foreach ($params as $function => $value) {
                if ($function != '') {
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
            }
        }
        $queryString = implode('&', $query);

        if ($this->debug) {
            echo "<pre>$method: ";
            print_r($this->host . "/select/?" . $queryString);
            echo "</pre>\n";
        }
        
        if ($method == 'GET') {
            $this->client->addRawQueryString($queryString);
        } elseif ($method == 'POST') {
            $this->client->setBody($queryString);
        }

        // Send Request
        $result = $this->client->sendRequest();
        $this->client->clearPostData();

        if (!PEAR::isError($result)) {
            return $this->_process($this->client->getResponseBody(), 
                $returnSolrError);
        } else {
            return $result;
        }
    }

    /**
     * Submit REST Request to write data
     *
     * @param   string      $xml        The command to execute
     * @return  mixed                   Boolean true on success or PEAR_Error
     * @access  private
     */
    private function _update($xml)
    {
        $this->client->setMethod('POST');
        $this->client->setURL($this->host . "/update/");
    
        if ($this->debug) {
            echo "<pre>POST: ";
            print_r($this->host . "/update/");
            echo "XML:\n";
            print_r($xml);
            echo "</pre>\n";
        }

        // Set up XML        
        $this->client->addHeader('Content-Type', 'text/xml; charset=utf-8');
        $this->client->addHeader('Content-Length', strlen($xml));
        $this->client->setBody($xml);

        // Send Request
        $result = $this->client->sendRequest();
        $responseCode = $this->client->getResponseCode();
        $this->client->clearPostData();

        if ($responseCode == 500) {
            $detail = $this->client->getResponseBody();
            // Attempt to extract the most useful error message from the response:
            if (preg_match("/<title>(.*)<\/title>/msi", $detail, $matches)) {
                $errorMsg = $matches[1];
            } else {
                $errorMsg = $detail;
            }
            return new PEAR_Error("Unexpected response -- " . $errorMsg);
        }
        
        if (!PEAR::isError($result)) {
            return true;
        } else {
            return $result;
        }
    }
    
    /**
     * Perform normalization and analysis of Solr return value.
     *
     * @param   array       $result             The raw response from Solr
     * @param   bool        $returnSolrError    If Solr reports a syntax error, 
     *                                          should we fail outright (false) or
     *                                          treat it as an empty result set with
     *                                          an error key set (true)?
     * @return  array                           The processed response from Solr
     * @access  private
     */
    private function _process($result, $returnSolrError = false)
    {
        // Catch errors from SOLR
        if (substr(trim($result), 0, 2) == '<h') {
            $errorMsg = substr($result, strpos($result, '<pre>'));
            $errorMsg = substr($errorMsg, strlen('<pre>'), strpos($result, "</pre>"));
            if ($returnSolrError) {
                return array('response' => array('numfound' => 0, 'docs' => array()),
                    'error' => $errorMsg);
            } else {
                PEAR::raiseError(new PEAR_Error('Unable to process query<br />' .
                    'Solr Returned: ' . $errorMsg));
            }
        }
        $result = json_decode($result, true);
        return $result;
    }

    /**
     * Input Tokenizer
     *
     * Tokenizes the user input based on spaces and quotes.  Then joins phrases
     * together that have an AND, OR, NOT present.
     *
     * @param   string  $input      User's input string
     * @return  array               Tokenized array
     * @access  public
     */
    public function tokenizeInput($input)
    {
        // Tokenize on spaces and quotes
        //preg_match_all('/"[^"]*"|[^ ]+/', $input, $words);
        preg_match_all('/"[^"]*"[~[0-9]+]*|"[^"]*"|[^ ]+/', $input, $words);
        $words = $words[0];

        // Join words with AND, OR, NOT
        $newWords = array();
        for ($i=0; $i<count($words); $i++) {
            if (($words[$i] == 'OR') || ($words[$i] == 'AND') || ($words[$i] == 'NOT')) {
                if (count($newWords)) {
                    $newWords[count($newWords)-1] .= ' ' . $words[$i] . ' ' . $words[$i+1];
                    $i = $i+1;
                }
            } else {
                $newWords[] = $words[$i];
            }
        }
        
        return $newWords;
    }

    /**
     * Input Validater
     *
     * Cleans the input based on the Lucene Syntax rules.
     *
     * @param   string  $input      User's input string
     * @return  bool                Fixed input
     * @access  public
     */
    public function validateInput($input)
    {
        // Normalize fancy quotes:
        $quotes = array(
            "\xC2\xAB"     => '"', // « (U+00AB) in UTF-8
            "\xC2\xBB"     => '"', // » (U+00BB) in UTF-8
            "\xE2\x80\x98" => "'", // ‘ (U+2018) in UTF-8
            "\xE2\x80\x99" => "'", // ’ (U+2019) in UTF-8
            "\xE2\x80\x9A" => "'", // ‚ (U+201A) in UTF-8
            "\xE2\x80\x9B" => "'", // ‛ (U+201B) in UTF-8
            "\xE2\x80\x9C" => '"', // “ (U+201C) in UTF-8
            "\xE2\x80\x9D" => '"', // ” (U+201D) in UTF-8
            "\xE2\x80\x9E" => '"', // „ (U+201E) in UTF-8
            "\xE2\x80\x9F" => '"', // ‟ (U+201F) in UTF-8
            "\xE2\x80\xB9" => "'", // ‹ (U+2039) in UTF-8
            "\xE2\x80\xBA" => "'", // › (U+203A) in UTF-8
        );
        $input = strtr($input, $quotes);

        // If the user has entered a lone BOOLEAN operator, convert it to lowercase
        // so it is treated as a word (otherwise it will trigger a fatal error):
        switch(trim($input)) {
            case 'OR':
                return 'or';
            case 'AND':
                return 'and';
            case 'NOT':
                return 'not';
        }

        // If the string consists only of control characters and/or BOOLEANs with no 
        // other input, wipe it out entirely to prevent weird errors:
        $operators = array('AND', 'OR', 'NOT', '+', '-', '"', '&', '|');
        if (trim(str_replace($operators, '', $input)) == '') {
            return '';
        }

        // Translate "all records" search into a blank string
        if (trim($input) == '*:*') {
            return '';
        }

        // Ensure wildcards are not at beginning of input
        if ((substr($input, 0, 1) == '*') ||
            (substr($input, 0, 1) == '?')) {
            $input = substr($input, 1);
        }

        // Ensure all parens match
        $start = preg_match_all('/\(/', $input, $tmp);
        $end = preg_match_all('/\)/', $input, $tmp);
        if ($start != $end) {
            $input = str_replace(array('(', ')'), '', $input);
        }

        // Ensure ^ is used properly
        $cnt = preg_match_all('/\^/', $input, $tmp);
        $matches = preg_match_all('/.+\^[0-9]/', $input, $tmp);
        if (($cnt) && ($cnt !== $matches)) {
            $input = str_replace('^', '', $input);
        }

        // Remove unwanted brackets/braces that are not part of range queries.
        // This is a bit of a shell game -- first we replace valid brackets and
        // braces with tokens that cannot possibly already be in the query (due
        // to ^ normalization in the step above).  Next, we remove all remaining
        // invalid brackets/braces, and transform our tokens back into valid ones.
        // Obviously, the order of the patterns/merges array is critically 
        // important to get this right!!
        $patterns = array(
            // STEP 1 -- escape valid brackets/braces
            '/\[([^\[\]\s]+\s+TO\s+[^\[\]\s]+)\]/',
            '/\{([^\{\}\s]+\s+TO\s+[^\{\}\s]+)\}/',
            // STEP 2 -- destroy remaining brackets/braces
            '/[\[\]\{\}]/', 
            // STEP 3 -- unescape valid brackets/braces
            '/\^\^lbrack\^\^/', '/\^\^rbrack\^\^/', 
            '/\^\^lbrace\^\^/', '/\^\^rbrace\^\^/');
        $matches = array(
            // STEP 1 -- escape valid brackets/braces
            '^^lbrack^^$1^^rbrack^^', '^^lbrace^^$1^^rbrace^^',
            // STEP 2 -- destroy remaining brackets/braces
            '',
            // STEP 3 -- unescape valid brackets/braces
            '[', ']', '{', '}');
        $input = preg_replace($patterns, $matches, $input);
        return $input;
    }

    public function isAdvanced($query)
    {
        // Check for various conditions that flag an advanced Lucene query:
        if ($query == '*:*') {
            return true;
        }
        
        // The following conditions do not apply to text inside quoted strings,
        // so let's just strip all quoted strings out of the query to simplify
        // detection.  We'll replace quoted phrases with a dummy keyword so quote
        // removal doesn't interfere with the field specifier check below.
        $query = preg_replace('/"[^"]*"/', 'quoted', $query);

        // Check for field specifiers:
        if (preg_match("/[^\s]\:[^\s]/", $query)) {
            return true;
        }

        // Check for parentheses and range operators:
        if (strstr($query, '(') && strstr($query, ')')) {
            return true;
        }
        $rangeReg = '/(\[.+\s+TO\s+.+\])|(\{.+\s+TO\s+.+\})/';
        if (preg_match($rangeReg, $query)) {
            return true;
        }

        // Build a regular expression to detect booleans -- AND/OR/NOT surrounded
        // by whitespace, or NOT leading the query and followed by whitespace.
        $boolReg = '/((\s+(AND|OR|NOT)\s+)|^NOT\s+)/';
        if (!$this->caseSensitiveBooleans) {
            $boolReg .= "i";
        }
        if (preg_match($boolReg, $query)) {
            return true;
        }

        // Check for wildcards and fuzzy matches:
        if (strstr($query, '*') || strstr($query, '?') || strstr($query, '~')) {
            return true;
        }

        // Check for boosts:
        if (preg_match('/[\^][0-9]+/', $query)) {
            return true;
        }

        return false;
    }

    public function cleanInput($query)
    {
        $query = trim(str_replace($this->illegal, '', $query));
        $query = strtolower($query);
        
        return $query;
    }

}

?>
