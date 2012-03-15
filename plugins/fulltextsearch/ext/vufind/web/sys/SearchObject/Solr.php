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
require_once 'sys/Solr.php';
require_once 'sys/SearchObject/Base.php';
require_once 'RecordDrivers/Factory.php';

/**
 * Search Object class
 *
 * This is the default implementation of the SearchObjectBase class, providing the
 * Solr-driven functionality used by VuFind's standard Search module.
 */
class SearchObject_Solr extends SearchObject_Base
{
    // SOLR QUERY
    // Parsed query
    private $query = null;
    // Facets
    private $facetLimit = 30;
    private $facetOffset = null;
    private $facetPrefix = null;
    private $facetSort = null;

    // Index
    private $index = null;
    // Field List
    private $fields = 'score';
    // HTTP Method
//    private $method = HTTP_REQUEST_METHOD_GET;
    private $method = HTTP_REQUEST_METHOD_POST;
    // Result
    private $indexResult;

    // OTHER VARIABLES
    // Index
    private $indexEngine = null;
    // Facets information
    private $allFacetSettings = array();    // loaded from facets.ini
    // Optional, used on author screen for example
    private $searchSubType  = '';
    // Used to pass hidden filter queries to Solr
    private $hiddenFilters = array();

    // Spelling
    private $spellingLimit = 3;
    private $spellQuery    = array();
    private $dictionary    = 'default';
    private $spellSimple   = false;
    private $spellSkipNumeric = true;

    /**
     * Constructor. Initialise some details about the server
     *
     * @access  public
     */
    public function __construct()
    {
        // Call base class constructor
        parent::__construct();
        
        global $configArray;
        // Include our solr index
        $class = $configArray['Index']['engine'];
        require_once "sys/$class.php";
        // Initialise the index
        $this->indexEngine = new $class($configArray['Index']['url']);

        // Get default facet settings
        $this->allFacetSettings = getExtraConfigArray('facets');
        $this->facetConfig = array();
        $facetLimit = $this->getFacetSetting('Results_Settings', 'facet_limit');
        if (is_numeric($facetLimit)) {
            $this->facetLimit = $facetLimit;
        }
        $translatedFacets = $this->getFacetSetting('Advanced_Settings', 'translated_facets');
        if (is_array($translatedFacets)) {
            $this->translatedFacets = $translatedFacets;
        }

        // Load search preferences:
        $searchSettings = getExtraConfigArray('searches');
        if (isset($searchSettings['General']['default_handler'])) {
            $this->defaultIndex = $searchSettings['General']['default_handler'];
        }
        if (isset($searchSettings['General']['default_sort'])) {
            $this->defaultSort = $searchSettings['General']['default_sort'];
        }
        if (isset($searchSettings['DefaultSortingByType']) && 
            is_array($searchSettings['DefaultSortingByType'])) {
            $this->defaultSortByType = $searchSettings['DefaultSortingByType'];
        }
        if (isset($searchSettings['Basic_Searches'])) {
            $this->basicTypes = $searchSettings['Basic_Searches'];
        }
        if (isset($searchSettings['Advanced_Searches'])) {
            $this->advancedTypes = $searchSettings['Advanced_Searches'];
        }

        // Load sort preferences (or defaults if none in .ini file):
        if (isset($searchSettings['Sorting'])) {
            $this->sortOptions = $searchSettings['Sorting'];
        } else {
            $this->sortOptions = array('relevance' => 'sort_relevance', 
                'year' => 'sort_year', 'year asc' => 'sort_year asc', 
                'callnumber' => 'sort_callnumber', 'author' => 'sort_author',
                'title' => 'sort_title');
        }

        // Load Spelling preferences
        $this->spellcheck    = $configArray['Spelling']['enabled'];
        $this->spellingLimit = $configArray['Spelling']['limit'];
        $this->spellSimple   = $configArray['Spelling']['simple'];
        $this->spellSkipNumeric = isset($configArray['Spelling']['skip_numeric']) ?
            $configArray['Spelling']['skip_numeric'] : true;

        // Debugging
        if ($configArray['System']['debug']) {
            $this->indexEngine->debug = true;
        } else {
            $this->indexEngine->debug = false;
        }
    }

    /**
     * Add filters to the object based on values found in the $_REQUEST superglobal.
     *
     * @access  protected
     */
    protected function initFilters()
    {
        // Use the default behavior of the parent class, but add support for the
        // special illustrations filter.
        parent::initFilters();
        if (isset($_REQUEST['illustration'])) {
            if ($_REQUEST['illustration'] == 1) {
                $this->addFilter('illustrated:Illustrated');
            } else if ($_REQUEST['illustration'] == 0) {
                $this->addFilter('illustrated:"Not Illustrated"');
            }
        }
    }

    /**
     * Initialise the object from the global
     *  search parameters in $_REQUEST.
     *
     * @access  public
     * @return  boolean
     */
    public function init()
    {
        global $module;
        global $action;

        // Call the standard initialization routine in the parent:
        parent::init();

        //********************
        // Check if we have a saved search to restore -- if restored successfully,
        // our work here is done; if there is an error, we should report failure;
        // if restoreSavedSearch returns false, we should proceed as normal.
        $restored = $this->restoreSavedSearch();
        if ($restored === true) {
            return true;
        } else if (PEAR::isError($restored)) {
            return false;
        }

        //********************
        // Initialize standard search parameters
        $this->initView();
        $this->initPage();
        $this->initSort();
        $this->initFilters();

        //********************
        // Basic Search logic
        if ($this->initBasicSearch()) {
            // If we found a basic search, we don't need to do anything further.
        } else if (isset($_REQUEST['tag']) && $module != 'MyResearch') {
            // Tags, just treat them as normal searches for now.
            // The search processer knows what to do with them.
            if ($_REQUEST['tag'] != '') {
                $this->searchTerms[] = array(
                    'index'   => 'tag',
                    'lookfor' => $_REQUEST['tag']
                );
            }
        } else {
            $this->initAdvancedSearch();
        }

        //********************
        // Author screens - handled slightly differently
        if ($module == 'Author') {
            // *** Things in common to both screens
            // Log a special type of search
            $this->searchType = 'author';
            // We don't spellcheck this screen
            //   it's not for free user intput anyway
            $this->spellcheck  = false;
            
            // *** Author/Home
            if ($action == 'Home') {
                $this->searchSubType = 'home';
                // Remove our empty basic search (default)
                $this->searchTerms = array();
                // Prepare the search as a normal author search
                $this->searchTerms[] = array(
                    'index'   => 'Author',
                    'lookfor' => $_REQUEST['author']
                );
            }

            // *** Author/Search
            if ($action == 'Search') {
                $this->searchSubType = 'search';
                // We already have the 'lookfor', just set the index
                $this->searchTerms[0]['index'] = 'Author';
                // We really want author facet data
                $this->facetConfig = array();
                $this->addFacet('authorStr');
                // Offset the facet list by the current page of results, and
                // allow up to ten total pages of results -- since we can't
                // get a total facet count, this at least allows the paging
                // mechanism to automatically add more pages to the end of the
                // list so that users can browse deeper and deeper as they go.
                // TODO: Make this better in the future if Solr offers a way
                //       to get a total facet count (currently not possible).
                $this->facetOffset = ($this->page - 1) * $this->limit;
                $this->facetLimit = $this->limit * 10;
                // Sorting - defaults to off with unlimited facets, so let's
                //           be explicit here for simplicity. 
                if (isset($_REQUEST['sort']) && ($_REQUEST['sort'] == 'author')) {
                    $this->setFacetSortOrder('index');
                } else {
                    $this->setFacetSortOrder('count');
                }
            }
        } else if ($module == 'Search' && 
            ($action == 'NewItem' || $action == 'Reserves')) {
            // We don't need spell checking
            $this->spellcheck = false;
            $this->searchType = strtolower($action);
        } else if ($module == 'MyResearch') {
            $this->spellcheck = false;
            $this->searchType = ($action == 'Home') ? 'favorites' : 'list';
        }

        // If a query override has been specified, log it here
        if (isset($_REQUEST['q'])) {
            $this->query = $_REQUEST['q'];
        }

        return true;
    } // End init()

    /**
     * Initialise the object for retrieving advanced
     *   search screen facet data from inside solr.
     *
     * @access  public
     * @return  boolean
     */
    public function initAdvancedFacets()
    {
        // Call the standard initialization routine in the parent:
        parent::init();

        //********************
        // Adjust facet options to use advanced settings
        $this->facetConfig = isset($this->allFacetSettings['Advanced']) ?
            $this->allFacetSettings['Advanced'] : array();
        $facetLimit = $this->getFacetSetting('Advanced_Settings', 'facet_limit');
        if (is_numeric($facetLimit)) {
            $this->facetLimit = $facetLimit;
        }

        // Spellcheck is not needed for facet data!
        $this->spellcheck = false;
        
        //********************
        // Basic Search logic
        $this->searchTerms[] = array(
            'index'   => $this->defaultIndex,
            'lookfor' => ""
        );

        return true;
    }

    /**
     * Initialise the object for retrieving dynamic data
     *    for the browse screen to function.
     *
     * We don't know much at this stage, the browse AJAX
     *   calls need to supply the queries and facets.          
     *
     * @access  public
     * @return  boolean
     */
    public function initBrowseScreen()
    {
        global $configArray;
        
        // Call the standard initialization routine in the parent:
        parent::init();

        $this->facetConfig = array();
        // Use the facet limit specified in config.ini (or default to 100):
        $this->facetLimit = isset($configArray['Browse']['result_limit']) ?
            $configArray['Browse']['result_limit'] : 100;
        // Sorting defaults to off with unlimited facets
        $this->setFacetSortOrder('count');

        // We don't need spell checking
        $this->spellcheck = false;
        
        //********************
        // Basic Search logic
        $this->searchTerms[] = array(
            'index'   => $this->defaultIndex,
            'lookfor' => ""
        );

        return true;
    }

    /**
     * Return the specified setting from the facets.ini file.
     *
     * @access  public
     * @param   string  $section    The section of the facets.ini file to look at.
     * @param   string  $setting    The setting within the specified file to return.
     * @return  string              The value of the setting (blank if none).
     */
    public function getFacetSetting($section, $setting)
    {
        return isset($this->allFacetSettings[$section][$setting]) ?
            $this->allFacetSettings[$section][$setting] : '';
    }
    
    /**
     * Used during repeated deminification (such as search history).
     *   To scrub fields populated above.
     *
     * @access  private
     */
    protected function purge()
    {
        // Call standard purge:
        parent::purge();
        
        // Make some Solr-specific adjustments:
        $this->query        = null;
    }

    /**
     * Switch the spelling dictionary to basic
     *
     * @access  public
     */
    public function useBasicDictionary() {
        $this->dictionary = 'basicSpell';
    }

    /**
     * Basic 'getters'
     *
     * @access  public
     * @param   various internal variables
     */
    public function getQuery()          {return $this->query;}
    public function getIndexEngine()    {return $this->indexEngine;}

    /**
     * Return the field (index) searched by a basic search
     *
     * @access  public
     * @return  string   The searched index
     */
    public function getSearchIndex()
    {
        // Use normal parent method for non-advanced searches.
        if ($this->searchType == $this->basicSearchType || 
            $this->searchType == 'author') {
            return parent::getSearchIndex();
        } else {
            return null;
        }
    }

    /**
     * Use the record driver to build an array of HTML displays from the search
     * results suitable for use on a user's "favorites" page.
     *
     * @access  public
     * @param   object  $user       User object owning tag/note metadata.
     * @param   int     $listId     ID of list containing desired tags/notes (or 
     *                              null to show tags/notes from all user's lists).
     * @param   bool    $allowEdit  Should we display edit controls?
     * @return  array   Array of HTML chunks for individual records.
     */
    public function getResultListHTML($user, $listId = null, $allowEdit = true)
    {
        global $interface;
        
        $html = array();
        for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
            $current = & $this->indexResult['response']['docs'][$x];
            $record = RecordDriverFactory::initRecordDriver($current);
            $html[] = $interface->fetch($record->getListEntry($user, $listId,
                $allowEdit));
        }
        return $html;
    }

    /**
     * Use the record driver to build an array of HTML displays from the search
     * results.
     *
     * @access  public
     * @return  array   Array of HTML chunks for individual records.
     */
    public function getResultRecordHTML()
    {
        global $interface;
        
        $html = array();
        for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
            $current = & $this->indexResult['response']['docs'][$x];
            $record = RecordDriverFactory::initRecordDriver($current);
            $html[] = $interface->fetch($record->getSearchResult());
        }
        return $html;
    }

    /**
     * Set an overriding array of record IDs.
     *
     * @access  public
     * @param   array   $ids        Record IDs to load
     */
    public function setQueryIDs($ids)
    {
        $this->query = 'id:(' . implode(' OR ', $ids) . ')';
    }

    /**
     * Set an overriding string.
     *
     * @access  public
     * @param   string  $newQuery   Query string
     */
    public function setQueryString($newQuery)
    {
        $this->query = $newQuery;
    }

    /**
     * Set an overriding facet sort order.
     *
     * @access  public
     * @param   string  $newSort   Sort string
     */
    public function setFacetSortOrder($newSort)
    {
        // As of Solr 1.4 valid values are:
        // 'count' = relevancy ranked
        // 'index' = index order, most likely alphabetical
        // more info : http://wiki.apache.org/solr/SimpleFacetParameters#facet.sort
        if ($newSort == 'count' || $newSort == 'index') $this->facetSort = $newSort;
    }

    /**
     * Add a prefix to facet requirements. Serves to
     *    limits facet sets to smaller subsets.
     *    
     *  eg. all facet data starting with 'R'          
     *
     * @access  public
     * @param   string  $prefix   Data for prefix
     */
    public function addFacetPrefix($prefix)
    {
        $this->facetPrefix = $prefix;
    }

    /**
     * Turn the list of spelling suggestions into an array of urls
     *   for on-screen use to implement the suggestions.
     *
     * @access  public
     * @return  array     Spelling suggestion data arrays
     */
    public function getSpellingSuggestions()
    {
        global $configArray;

        $returnArray = array();
        if (count($this->suggestions) == 0) return $returnArray;
        $tokens = $this->spellingTokens($this->buildSpellingQuery());

        foreach ($this->suggestions as $term => $details) {
            // Find out if our suggestion is part of a token
            $inToken = false;
            $targetTerm = "";
            foreach ($tokens as $token) {
                // TODO - Do we need stricter matching here?
                //   Similar to that in replaceSearchTerm()?
                if (stripos($token, $term) !== false) {
                    $inToken = true;
                    // We need to replace the whole token
                    $targetTerm = $token;
                    // Go and replace this token
                    $returnArray = $this->doSpellingReplace($term,
                        $targetTerm, $inToken, $details, $returnArray);
                }
            }
            // If no tokens we found, just look
            //    for the suggestion 'as is'
            if ($targetTerm == "") {
                $targetTerm = $term;
                $returnArray = $this->doSpellingReplace($term,
                    $targetTerm, $inToken, $details, $returnArray);
            }
        }
        return $returnArray;
    }

    /**
     * Process one instance of a spelling replacement and modify the return
     *   data structure with the details of what was done.
     *
     * @access  public
     * @param   string   $term        The actually term we're replacing
     * @param   string   $targetTerm  The term above, or the token it is inside
     * @param   boolean  $inToken     Flag for whether the token or term is used
     * @param   array    $details     The spelling suggestions
     * @param   array    $returnArray Return data structure so far
     * @return  array    $returnArray modified
     */
    private function doSpellingReplace($term, $targetTerm, $inToken, $details, $returnArray)
    {
        global $configArray;

        $returnArray[$targetTerm]['freq'] = $details['freq'];
        foreach ($details['suggestions'] as $word => $freq) {
            // If the suggested word is part of a token
            if ($inToken) {
                // We need to make sure we replace the whole token
                $replacement = str_replace($term, $word, $targetTerm);
            } else {
                $replacement = $word;
            }
            //  Do we need to show the whole, modified query?
            if ($configArray['Spelling']['phrase']) {
                $label = $this->getDisplayQueryWithReplacedTerm($targetTerm, $replacement);
            } else {
                $label = $replacement;
            }
            // Basic spelling suggestion data
            $returnArray[$targetTerm]['suggestions'][$label] = array(
                'freq'        => $freq,
                'replace_url' => $this->renderLinkWithReplacedTerm($targetTerm, $replacement)
            );
            // Only generate expansions if enabled in config
            if ($configArray['Spelling']['expand']) {
                // Parentheses differ for shingles
                if (strstr($targetTerm, " ") !== false) {
                    $replacement = "(($targetTerm) OR ($replacement))";
                } else {
                    $replacement = "($targetTerm OR $replacement)";
                }
                $returnArray[$targetTerm]['suggestions'][$label]['expand_url'] =
                    $this->renderLinkWithReplacedTerm($targetTerm, $replacement);
            }
        }

        return $returnArray;
    }

    /**
     * Return a list of valid sort options -- overrides the base class with
     * custom behavior for Author/Search screen.
     *
     * @access  public
     * @return  array    Sort value => description array.
     */
    protected function getSortOptions()
    {
        // Author/Search screen
        if ($this->searchType == 'author' && $this->searchSubType == 'search') {
            // It's important to remember here we are talking about on-screen
            //   sort values, not what is sent to Solr, since this screen
            //   is really using facet sorting.
            return array('relevance' => 'sort_author_relevance', 
                'author' => 'sort_author_author');
        }

        // Everywhere else -- use normal default behavior
        return parent::getSortOptions();
    }
    
    /**
     * Return a url of the current search as an RSS feed.
     *
     * @access  public
     * @return  string    URL
     */
    public function getRSSUrl()
    {
        // Stash our old data for a minute
        $oldView = $this->view;
        $oldPage = $this->page;
        // Add the new view
        $this->view = 'rss';
        // Remove page number
        $this->page = 1;
        // Get the new url
        $url = $this->renderSearchUrl();
        // Restore the old data
        $this->view = $oldView;
        $this->page = $oldPage;
        // Return the URL
        return $url;
    }

    /**
     * Get the base URL for search results (including ? parameter prefix).
     *
     * @access  protected
     * @return  string   Base URL
     */
    protected function getBaseUrl()
    {
        // Base URL is different for author searches:
        if ($this->searchType == 'author') {
            if ($this->searchSubType == 'home')   return $this->serverUrl."/Author/Home?";
            if ($this->searchSubType == 'search') return $this->serverUrl."/Author/Search?";
        } else if ($this->searchType == 'newitem') {
            return $this->serverUrl . '/Search/NewItem?';
        } else if ($this->searchType == 'reserves') {
            return $this->serverUrl . '/Search/Reserves?';
        } else if ($this->searchType == 'favorites') {
            return $this->serverUrl . '/MyResearch/Home?';
        } else if ($this->searchType == 'list') {
            return $this->serverUrl . '/MyResearch/MyList/' . 
                urlencode($_GET['id']) . '?';
        }
        
        // If none of the special cases were met, use the default from the parent:
        return parent::getBaseUrl();
    }

    /**
     * Get an array of strings to attach to a base URL in order to reproduce the
     * current search.
     *
     * @access  protected
     * @return  array    Array of URL parameters (key=url_encoded_value format)
     */
    protected function getSearchParams()
    {
        $params = array();
        switch ($this->searchType) {
            // Author Home screen
            case "author":
                if ($this->searchSubType == 'home')   $params[] = "author="  . urlencode($this->searchTerms[0]['lookfor']);
                if ($this->searchSubType == 'search') $params[] = "lookfor=" . urlencode($this->searchTerms[0]['lookfor']);
                break;
            // New Items or Reserves modules may have a few extra parameters to preserve:
            case "newitem":
            case "reserves":
            case "favorites":
            case "list":
                $preserveParams = array(
                    // for newitem:
                    'range', 'department', 
                    // for reserves:
                    'course', 'inst', 'dept',
                    // for favorites/list:
                    'tag'
                );
                foreach($preserveParams as $current) {
                    if (isset($_GET[$current])) {
                        if (is_array($_GET[$current])) {
                            foreach($_GET[$current] as $value) {
                                $params[] = $current . '[]=' . urlencode($value);
                            }
                        } else {
                            $params[] = $current . '=' . urlencode($_GET[$current]);
                        }
                    }
                }
                break;
            // Basic search -- use default from parent class.
            default:
                $params = parent::getSearchParams();
                break;
        }
        
        return $params;
    }

    /**
     * Process a search for a particular tag.
     *
     * @access  private
     * @param   string  $lookfor    The tag to search for
     * @return  array   A revised searchTerms array to get matching Solr records
     *                  (empty if no tag matches found).
     */
    private function processTagSearch($lookfor)
    {
        // Include the app database objects
        require_once 'services/MyResearch/lib/Tags.php';
        require_once 'services/MyResearch/lib/Resource.php';

        // Find our tag in the database
        $tag = new Tags();
        $tag->tag = $lookfor;
        $newSearch = array();
        if ($tag->find(true)) {
            // Grab the list of records tagged with this tag
            $resourceList = array();
            $resourceList = $tag->getResources();
            if (count($resourceList)) {
                $newSearch[0] = array('join' => 'OR', 'group' => array());
                foreach ($resourceList as $resource) {
                    $newSearch[0]['group'][] = array(
                        'field' => 'id',
                        'lookfor' => $resource->record_id,
                        'bool' => 'OR'
                    );
                }
            }
        }
        
        return $newSearch;
    }
    
    /**
     * Get error message from index response, if any.  This will only work if 
     * processSearch was called with $returnIndexErrors set to true!
     *
     * @access  public
     * @return  mixed       false if no error, error string otherwise.
     */
    public function getIndexError()
    {
        return isset($this->indexResult['error']) ?
            $this->indexResult['error'] : false;
    }

    /**
     * Load all recommendation settings from the relevant ini file.  Returns an
     * associative array where the key is the location of the recommendations (top
     * or side) and the value is the settings found in the file (which may be either
     * a single string or an array of strings).
     *
     * @access  protected
     * @return  array           associative: location (top/side) => search settings
     */
    protected function getRecommendationSettings()
    {
        // Special hard-coded case for author module.  We should make this more
        // flexible in the future!
        if ($this->searchType == 'author') {
            return array('side' => array('ExpandFacets:Author'));
        }
        
        // Use default case from parent class the rest of the time:
        return parent::getRecommendationSettings();
    }

    /**
     * Add a hidden (i.e. not visible in facet controls) filter query to the object.
     *
     * @access  public
     * @param   string $fq                 Filter query for Solr.
     */
    public function addHiddenFilter($fq)
    {
        $this->hiddenFilters[] = $fq;
    }

    /**
     * Actually process and submit the search
     *
     * @access  public
     * @param   bool   $returnIndexErrors  Should we die inside the index code if
     *                                     we encounter an error (false) or return
     *                                     it for access via the getIndexError() 
     *                                     method (true)?
     * @param   bool   $recommendations    Should we process recommendations along
     *                                     with the search itself?
     * @return  object solr result structure (for now)
     */
    public function processSearch($returnIndexErrors = false, $recommendations = false)
    {
        // Our search has already been processed in init()
        $search = $this->searchTerms;

        // Build a recommendations module appropriate to the current search:
        if ($recommendations) {
            $this->initRecommendations();
        }

        // Tag searches need to be handled differently
        if (count($search) == 1 && isset($search[0]['index']) && $search[0]['index'] == 'tag') {
            // If we managed to find some tag matches, let's override the search
            // array.  If we didn't find any tag matches, we should return an
            // empty record set.
            $newSearch = $this->processTagSearch($search[0]['lookfor']);
            // Save search so it displays correctly on the "no hits" page:
            if (empty($newSearch)) {
                return array('response' => array('numFound' => 0, 'docs' => array()));
            } else {
                $search = $newSearch;
            }
        }

        // Build Query
        $query = $this->indexEngine->buildQuery($search);
        if (PEAR::isError($query)) {
            return $query;
        }

        // Only use the query we just built if there isn't an override in place.
        if ($this->query == null) {
            $this->query = $query;
        }

        // Define Filter Query
        $filterQuery = $this->hiddenFilters;
        foreach ($this->filterList as $field => $filter) {
            foreach ($filter as $value) {
                // Special case -- allow trailing wildcards:
                if (substr($value, -1) == '*') {
                    $filterQuery[] = "$field:$value";
                } else {
                    $filterQuery[] = "$field:\"$value\"";
                }
            }
        }

        // If we are only searching one field use the DisMax handler
        //    for that field. If left at null let solr take care of it
        if (count($search) == 1 && isset($search[0]['index'])) {
            $this->index = $search[0]['index'];
        }

        // Build a list of facets we want from the index
        $facetSet = array();
        if (!empty($this->facetConfig)) {
            $facetSet['limit'] = $this->facetLimit;
            foreach ($this->facetConfig as $facetField => $facetName) {
                $facetSet['field'][] = $facetField;
            }
            if ($this->facetOffset != null) {
                $facetSet['offset'] = $this->facetOffset;
            }
            if ($this->facetPrefix != null) {
                $facetSet['prefix'] = $this->facetPrefix;
            }
            if ($this->facetSort != null) {
                $facetSet['sort'] = $this->facetSort;
            }
        }

        // Build our spellcheck query
        if ($this->spellcheck) {
            if ($this->spellSimple) {
                $this->useBasicDictionary();
            }
            $spellcheck = $this->buildSpellingQuery();
            
            // If the spellcheck query is purely numeric, skip it if
            // the appropriate setting is turned on.
            if ($this->spellSkipNumeric && is_numeric($spellcheck)) {
                $spellcheck = "";
            }
        } else {
            $spellcheck = "";
        }

        // Get time before the query
        $this->startQueryTimer();

        // The "relevance" sort option is a VuFind reserved word; we need to make
        // this null in order to achieve the desired effect with Solr:
        $finalSort = ($this->sort == 'relevance') ? null : $this->sort;

        // The first record to retrieve:
        //  (page - 1) * limit = start
        $recordStart = ($this->page - 1) * $this->limit;
        $this->indexResult = $this->indexEngine->search(
            $this->query,      // Query string
            $this->index,      // DisMax Handler
            $filterQuery,      // Filter query
            $recordStart,      // Starting record
            $this->limit,      // Records per page
            $facetSet,         // Fields to facet on
            $spellcheck,       // Spellcheck query
            $this->dictionary, // Spellcheck dictionary
            $finalSort,        // Field to sort on
            $this->fields,     // Fields to return
            $this->method,     // HTTP Request method
            $returnIndexErrors // Include errors in response?
        );

        // Get time after the query
        $this->stopQueryTimer();

        // How many results were there?
        $this->resultsTotal = $this->indexResult['response']['numFound'];

        // Process spelling suggestions if no index error resulted from the query
        if ($this->spellcheck && !isset($this->indexResult['error'])) {
            // Shingle dictionary
            $this->processSpelling();
            // Make sure we don't endlessly loop
            if ($this->dictionary == 'default') {
                // Expand against the basic dictionary
                $this->basicSpelling();
            }
        }

        // If extra processing is needed for recommendations, do it now:
        if ($recommendations && is_array($this->recommend)) {
            foreach($this->recommend as $currentSet) {
                foreach($currentSet as $current) {
                    $current->process();
                }
            }
        }

        // Return the result set
        return $this->indexResult;
    }

    /**
     * Adapt the search query to a spelling query
     *
     * @access  private
     * @return  string    Spelling query
     */
    private function buildSpellingQuery()
    {
        $this->spellQuery = array();
        // Basic search
        if ($this->searchType == $this->basicSearchType) {
            // Just the search query is fine
            return $this->query;

        // Advanced search
        } else {
            foreach ($this->searchTerms as $search) {
                foreach ($search['group'] as $field) {
                    // Add just the search terms to the list
                    $this->spellQuery[] = $field['lookfor'];
                }
            }
            // Return the list put together as a string
            return join(" ", $this->spellQuery);
        }
    }
    
    /**
     * Process spelling suggestions from the results object
     *
     * @access  private
     */
    private function processSpelling()
    {
        global $configArray;

        // Do nothing if spelling is disabled
        if (!$configArray['Spelling']['enabled']) {
            return;
        }
        
        // Do nothing if there are no suggestions
        $suggestions = isset($this->indexResult['spellcheck']['suggestions']) ?
            $this->indexResult['spellcheck']['suggestions'] : array();
        if (count($suggestions) == 0) {
            return;
        }

        // Loop through the array of search terms we have suggestions for
        $suggestionList = array();
        foreach ($suggestions as $suggestion) {
            $ourTerm = $suggestion[0];

            // Skip numeric terms if numeric suggestions are disabled
            if ($this->spellSkipNumeric && is_numeric($ourTerm)) {
                continue;
            }

            $ourHit  = $suggestion[1]['origFreq'];
            $count   = $suggestion[1]['numFound'];
            $newList = $suggestion[1]['suggestion'];

            $validTerm = true;

            // Make sure the suggestion is for a valid search term.
            // Sometimes shingling will have bridged two search fields (in
            // an advanced search) or skipped over a stopword.
            if (!$this->findSearchTerm($ourTerm)) {
                $validTerm = false;
            }

            // Unless this term had no hits
            if ($ourHit != 0) {
                // Filter out suggestions we are already using
                $newList = $this->filterSpellingTerms($newList);
            }

            // Make sure it has suggestions and is valid
            if (count($newList) > 0 && $validTerm) {
                // Did we get more suggestions then our limit?
                if ($count > $this->spellingLimit) {
                    // Cut the list at the limit
                    array_splice($newList, $this->spellingLimit);
                }
                $suggestionList[$ourTerm]['freq'] = $ourHit;
                // Format the list nicely
                foreach ($newList as $item) {
                    if (is_array($item)) {
                        $suggestionList[$ourTerm]['suggestions'][$item['word']] = $item['freq'];
                    } else {
                        $suggestionList[$ourTerm]['suggestions'][$item] = 0;
                    }
                }
            }
        }
        $this->suggestions = $suggestionList;
    }

    /**
     * Filter a list of spelling suggestions to remove suggestions
     *   we are already searching for
     *
     * @access  private
     * @param   array    List of suggestions
     * @return  array    Filtered list
     */
    private function filterSpellingTerms($termList) {
        $newList = array();
        if (count($termList) == 0) return $newList;

        foreach ($termList as $term) {
            if (!$this->findSearchTerm($term['word'])) {
                $newList[] = $term;
            }
        }
        return $newList;
    }

    /**
     * Try running spelling against the basic dictionary.
     *   This function should ensure it doesn't return
     *   single word suggestions that have been accounted
     *   for in the shingle suggestions above.
     *
     * @access  private
     * @return  array     Suggestions array
     */
    private function basicSpelling()
    {
        // TODO: There might be a way to run the
        //   search against both dictionaries from
        //   inside solr. Investigate. Currently
        //   submitting a second search for this.

        // Create a new search object
        $newSearch = SearchObjectFactory::initSearchObject('Solr');
        $newSearch->deminify($this->minify());

        // Activate the basic dictionary
        $newSearch->useBasicDictionary();
        // We don't want it in the search history
        $newSearch->disableLogging();

        // Run the search
        $newSearch->processSearch();
        // Get the spelling results
        $newList = $newSearch->getRawSuggestions();

        // If there were no shingle suggestions
        if (count($this->suggestions) == 0) {
            // Just use the basic ones as provided
            $this->suggestions = $newList;

        // Otherwise
        } else {
            // For all the new suggestions
            foreach ($newList as $word => $data) {
                // Check the old suggestions
                $found = false;
                foreach ($this->suggestions as $k => $v) {
                    // Make sure it wasn't part of a shingle
                    //   which has been suggested at a higher
                    //   level.
                    $found = preg_match("/\b$word\b/", $k) ? true : $found;
                }
                if (!$found) {
                    $this->suggestions[$word] = $data;
                }
            }
        }
    }

    /**
     * Process facets from the results object
     *
     * @access  public
     * @param   array   $filter         Array of field => on-screen description
     *                                  listing all of the desired facet fields;
     *                                  set to null to get all configured values.
     * @param   bool    $expandingLinks If true, we will include expanding URLs
     *                                  (i.e. get all matches for a facet, not
     *                                  just a limit to the current search) in
     *                                  the return array.
     * @return  array   Facets data arrays
     */
    public function getFacetList($filter = null, $expandingLinks = false)
    {
        // If there is no filter, we'll use all facets as the filter:
        if (is_null($filter)) {
            $filter = $this->facetConfig;
        }
        
        // Start building the facet list:
        $list = array();
        
        // If we have no facets to process, give up now
        if (!is_array($this->indexResult['facet_counts']['facet_fields'])) {
            return $list;
        }
        
        // Loop through every field returned by the result set
        $validFields = array_keys($filter);
        foreach ($this->indexResult['facet_counts']['facet_fields'] as $field => $data) {
            // Skip filtered fields and empty arrays:
            if (!in_array($field, $validFields) || count($data) < 1) {
                continue;
            }
            // Initialize the settings for the current field
            $list[$field] = array();
            // Add the on-screen label
            $list[$field]['label'] = $filter[$field];
            // Build our array of values for this field
            $list[$field]['list']  = array();
            // Should we translate values for the current facet?
            $translate = in_array($field, $this->translatedFacets);
            // Loop through values:
            foreach ($data as $facet) {
                // Initialize the array of data about the current facet:
                $currentSettings = array();
                $currentSettings['value'] = $translate ? translate($facet[0]) : $facet[0];
                $currentSettings['count'] = $facet[1];
                $currentSettings['isApplied'] = false;
                $currentSettings['url'] = $this->renderLinkWithFilter("$field:".$facet[0]);
                // If we want to have expanding links (all values matching the facet)
                // in addition to limiting links (filter current search with facet),
                // do some extra work:
                if ($expandingLinks) {
                    $currentSettings['expandUrl'] = 
                        $this->getExpandingFacetLink($field, $facet[0]);
                }
                // Is this field a current filter?
                if (in_array($field, array_keys($this->filterList))) {
                    // and is this value a selected filter?
                    if (in_array($facet[0], $this->filterList[$field])) {
                        $currentSettings['isApplied'] = true;
                    }
                }
                
                // Store the collected values:
                $list[$field]['list'][] = $currentSettings;
            }
        }
        return $list;
    }

    /**
     * Load all available facet settings.  This is mainly useful for showing
     * appropriate labels when an existing search has multiple filters associated
     * with it.
     *
     * @access  public
     * @param   string      $preferredSection       Section to favor when loading
     *                                              settings; if multiple sections
     *                                              contain the same facet, this
     *                                              section's description will be
     *                                              favored.
     */
    public function activateAllFacets($preferredSection = false)
    {
        foreach($this->allFacetSettings as $section => $values) {
            foreach($values as $key => $value) {
                $this->addFacet($key, $value);
            }
        }
        
        if ($preferredSection && 
            is_array($this->allFacetSettings[$preferredSection])) {
            foreach($this->allFacetSettings[$preferredSection] as $key => $value) {
                $this->addFacet($key, $value);
            }
        }
    }

    /**
     * Turn our results into an RSS feed
     *
     * @access  public
     * @public  array      $result      Existing result set (null to do new search)
     * @return  string                  XML document
     */
    public function buildRSS($result = null)
    {
        // XML HTTP header
        header('Content-type: text/xml', true);

        // First, get the search results if none were provided
        // (we'll go for 50 at a time)
        if (is_null($result)) {
            $this->limit = 50;
            $result = $this->processSearch(false, false);
        }

        // Now prepare the serializer
        $serializer_options = array (
            'addDecl'  => TRUE,
            'encoding' => 'UTF-8',
            'indent'   => '  ',
            'rootName' => 'json',
            'mode'     => 'simplexml'
        );
        $serializer = new XML_Serializer($serializer_options);

        // The XML parsers have trouble with the control characters
        //   inside the marc data, so lets get rid of the 'fullrecord'
        //   nodes. Not sure what we'll do if these are needed for some
        //   reason
        for ($i = 0; $i < count($result['response']['docs']); $i++) {
            if (isset($result['response']['docs'][$i]['fullrecord'])) {
                unset($result['response']['docs'][$i]['fullrecord']);
            }
        }

        // Serialize our results from PHP arrays to XML
        if ($serializer->serialize($result)) {
            $xmlResults = $serializer->getSerializedData();
        }

        // Prepare an XSLT processor and pass it some variables
        $xsl = new XSLTProcessor();
        $xsl->registerPHPFunctions('urlencode');
        $xsl->registerPHPFunctions('translate');

        // On-screen display value for our search
        if ($this->searchType == 'newitem') {
            $lookfor = translate('New Items');
        } else if ($this->searchType == 'reserves') {
            $lookfor = translate('Course Reserves');
        } else {
            $lookfor = $this->displayQuery();
        }
        if (count($this->filterList) > 0) {
            // TODO : better display of filters
            $xsl->setParameter('', 'lookfor', $lookfor . " (" . translate('with filters') . ")");
        } else {
            $xsl->setParameter('', 'lookfor', $lookfor);
        }
        // The full url to recreate this search
        $xsl->setParameter('', 'searchUrl', $this->renderSearchUrl());
        // Stub of a url for a records screen
        $xsl->setParameter('', 'baseUrl',   $this->serverUrl."/Record/");

        // Load up the style sheet
        $style = new DOMDocument;
        $style->load('services/Search/xsl/json-rss.xsl');
        $xsl->importStyleSheet($style);

        // Load up the XML document
        $xml = new DOMDocument;
        $xml->loadXML($xmlResults);

        // Process and return the xml through the style sheet
        return $xsl->transformToXML($xml);
    }
}
?>