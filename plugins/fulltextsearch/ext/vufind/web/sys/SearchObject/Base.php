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
require_once 'services/MyResearch/lib/Search.php';
require_once 'sys/Recommend/RecommendationFactory.php';

/**
 * Search Object abstract base class.
 *
 * Generic base class for abstracting search URL generation and other standard 
 * functionality.  This should be extended to implement functionality for specific
 * VuFind modules (i.e. standard Solr search vs. Summon, etc.).
 */
abstract class SearchObject_Base
{
    // SEARCH PARAMETERS
    // RSS feed?
    protected $view = null;
    // Search terms
    protected $searchTerms = array();
    // Sorting
    protected $sort = null;
    protected $defaultSort = 'relevance';
    protected $defaultSortByType = array();

    // Filters
    protected $filterList = array();
    // Page number
    protected $page = 1;
    // Result limit
    protected $limit = 20;

    // STATS
    protected $resultsTotal = 0;

    // OTHER VARIABLES
    // Server URL
    protected $serverUrl = "";
    // Module and Action for building search results URLs
    protected $resultsModule = 'Search';
    protected $resultsAction = 'Results';
    // Facets information
    protected $facetConfig = array();    // Array of valid facet fields=>labels
    protected $checkboxFacets = array(); // Boolean facets represented as checkboxes
    protected $translatedFacets = array();  // Facets that need to be translated
    // Default Search Handler
    protected $defaultIndex = null;
    // Available sort options
    protected $sortOptions = array();
    // An ID number for saving/retrieving search
    protected $searchId    = null;
    protected $savedSearch = false;
    protected $searchType  = 'basic';
    // Possible values of $searchType:
    protected $basicSearchType = 'basic';
    protected $advancedSearchType = 'advanced';
    // Flag for logging/search history
    protected $disableLogging = false;
    // Debugging flag
    protected $debug;
    // Search options for the user
    protected $advancedTypes = array();
    protected $basicTypes = array();
    // Spelling
    protected $spellcheck    = true;
    protected $suggestions   = array();
    // Recommendation modules associated with the search:
    protected $recommend     = false;
    // The INI file to load recommendations settings from:
    protected $recommendIni = 'searches';

    // STATS
    protected $initTime = null;
    protected $endTime = null;
    protected $totalTime = null;

    protected $queryStartTime = null;
    protected $queryEndTime = null;
    protected $queryTime = null;

    /**
     * Constructor. Initialise some details about the server
     *
     * @access  public
     */
    public function __construct()
    {
        global $configArray;

        // Get the start of the server URL and store
        $this->serverUrl = $configArray['Site']['url'];
        
        // Set appropriate debug mode:
        $this->debug = $configArray['System']['debug'];
    }

    /* Parse apart the field and value from a URL filter string.
     *
     * @access  protected
     * @param   string  $filter     A filter string from url : "field:value"
     * @return  array               Array with elements 0 = field, 1 = value.
     */
    protected function parseFilter($filter)
    {
        // Split the string
        $temp = explode(':', $filter);
        // $field is the first value
        $field = array_shift($temp);
        // join them incase the value contained colons as well.
        $value = join(":", $temp);

        // Remove quotes from the value if there are any
        if (substr($value, 0, 1)  == '"') $value = substr($value, 1);
        if (substr($value, -1, 1) == '"') $value = substr($value, 0, -1);
        // One last little clean on whitespace
        $value = trim($value);
        
        // Send back the results:
        return array($field, $value);
    }

    /**
     * Does the object already contain the specified filter?
     *
     * @access  public
     * @param   string  $filter     A filter string from url : "field:value"
     * @return  bool
     */
    public function hasFilter($filter)
    {
        // Extract field and value from URL string:
        list($field, $value) = $this->parseFilter($filter);

        if (isset($this->filterList[$field]) && 
            in_array($value, $this->filterList[$field])) {
            return true;
        }
        return false;
    }

    /**
     * Take a filter string and add it into the protected
     *   array checking for duplicates.
     *
     * @access  public
     * @param   string  $newFilter   A filter string from url : "field:value"
     */
    public function addFilter($newFilter)
    {
        // Extract field and value from URL string:
        list($field, $value) = $this->parseFilter($newFilter);
        
        // Check for duplicates -- if it's not in the array, we can add it
        if (!$this->hasFilter($newFilter)) {
            $this->filterList[$field][] = $value;
        }
    }

    /**
     * Remove a filter from the list.
     *
     * @access  public
     * @param   string  $oldFilter   A filter string from url : "field:value"
     */
    public function removeFilter($oldFilter)
    {
        // Extract field and value from URL string:
        list($field, $value) = $this->parseFilter($oldFilter);

        // Make sure the field exists
        if (isset($this->filterList[$field])) {
            // Loop through all filters on the field
            for ($i = 0; $i < count($this->filterList[$field]); $i++) {
                // Does it contain the value we don't want?
                if ($this->filterList[$field][$i] == $value) {
                    // If so remove it.
                    unset($this->filterList[$field][$i]);
                }
            }
        }
    }

    /**
     * Get a user-friendly string to describe the provided facet field.
     *
     * @access  protected
     * @param   string  $field                  Facet field name.
     * @return  string                          Human-readable description of field.
     */
    protected function getFacetLabel($field)
    {
        return isset($this->facetConfig[$field]) ? 
            $this->facetConfig[$field] : "Other";
    }

    /**
     * Return an array structure containing all current filters
     *    and urls to remove them.
     *
     * @access  public
     * @param   bool   $excludeCheckboxFilters  Should we exclude checkbox filters
     *                                          from the list (to be used as a
     *                                          complement to getCheckboxFacets()).
     * @return  array    Field, values and removal urls
     */
    public function getFilterList($excludeCheckboxFilters = false)
    {
        // Get a list of checkbox filters to skip if necessary:
        $skipList = $excludeCheckboxFilters ?
            array_keys($this->checkboxFacets) : array();

        $list = array();
        // Loop through all the current filter fields
        foreach ($this->filterList as $field => $values) {
            // and each value currently used for that field
            $translate = in_array($field, $this->translatedFacets);
            foreach ($values as $value) {
                // Add to the list unless it's in the list of fields to skip:
                if (!in_array($field, $skipList)) {
                    $facetLabel = $this->getFacetLabel($field);
                    $display = $translate ? translate($value) : $value;
                    $list[$facetLabel][] = array(
                        'value'      => $value,     // raw value for use with Solr
                        'display'    => $display,   // version to display to user
                        'field'      => $field,
                        'removalUrl' => $this->renderLinkWithoutFilter("$field:$value")
                    );
                }
            }
        }
        return $list;
    }

    /**
     * Return a url for the current search with an additional filter
     *
     * @access  public
     * @param   string   $new_filter   A filter to add to the search url
     * @return  string   URL of a new search
     */
    public function renderLinkWithFilter($newFilter)
    {
        // Stash our old data for a minute
        $oldFilterList = $this->filterList;
        $oldPage       = $this->page;
        // Add the new filter
        $this->addFilter($newFilter);
        // Remove page number
        $this->page = 1;
        // Get the new url
        $url = $this->renderSearchUrl();
        // Restore the old data
        $this->filterList = $oldFilterList;
        $this->page       = $oldPage;
        // Return the URL
        return $url;
    }

    /**
     * Return a url for the current search without one of the current filters
     *
     * @access  public
     * @param   string   $old_filter   A filter to remove from the search url
     * @return  string   URL of a new search
     */
    public function renderLinkWithoutFilter($oldFilter)
    {
        return $this->renderLinkWithoutFilters(array($oldFilter));
    }

    /**
     * Return a url for the current search without several of the current filters
     *
     * @access  public
     * @param   array    $filters      The filters to remove from the search url
     * @return  string   URL of a new search
     */
    public function renderLinkWithoutFilters($filters)
    {
        // Stash our old data for a minute
        $oldFilterList = $this->filterList;
        $oldPage       = $this->page;
        // Remove the old filter
        foreach($filters as $oldFilter) {
            $this->removeFilter($oldFilter);
        }
        // Remove page number
        $this->page = 1;
        // Get the new url
        $url = $this->renderSearchUrl();
        // Restore the old data
        $this->filterList = $oldFilterList;
        $this->page       = $oldPage;
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
        return $this->serverUrl."/{$this->resultsModule}/{$this->resultsAction}?";
    }

    /**
     * Get the URL to load a saved search from the current module.
     *
     * @access  public
     * @return  string   Saved search URL.
     */
    public function getSavedUrl($id)
    {
        return $this->getBaseUrl() . 'saved=' . urlencode($id);
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
            // Advanced search
            case $this->advancedSearchType:
                $params[] = "join=" . urlencode($this->searchTerms[0]['join']);
                for ($i = 0; $i < count($this->searchTerms); $i++) {
                    $params[] = "bool".$i."[]=" . urlencode($this->searchTerms[$i]['group'][0]['bool']);
                    for ($j = 0; $j < count($this->searchTerms[$i]['group']); $j++) {
                        $params[] = "lookfor".$i."[]=" . urlencode($this->searchTerms[$i]['group'][$j]['lookfor']);
                        $params[] = "type".$i."[]="    . urlencode($this->searchTerms[$i]['group'][$j]['field']);
                    }
                }
                break;
            // Basic search
            default:
                if (isset($this->searchTerms[0]['lookfor'])) {
                    $params[] = "lookfor=" . urlencode($this->searchTerms[0]['lookfor']);
                }
                if (isset($this->searchTerms[0]['index'])) {
                    $params[] = "type="    . urlencode($this->searchTerms[0]['index']);
                }
                break;
        }
        return $params;
    }

    /**
     * Initialize the object's search settings for a basic search found in the
     * $_REQUEST superglobal.
     *
     * @access  protected
     * @return  boolean  True if search settings were found, false if not.
     */
    protected function initBasicSearch()
    {
        // If no lookfor parameter was found, we have no search terms to
        // add to our array!
        if (!isset($_REQUEST['lookfor'])) {
            return false;
        }
        
        // If lookfor is an array, we may be dealing with a legacy Advanced
        // Search URL.  If there's only one parameter, we can flatten it,
        // but otherwise we should treat it as an error -- no point in going
        // to great lengths for compatibility.
        if (is_array($_REQUEST['lookfor'])) {
            if (count($_REQUEST['lookfor']) == 1) {
                $_REQUEST['lookfor'] = $_REQUEST['lookfor'][0];
            } else {
                PEAR::RaiseError(new PEAR_Error("Unsupported search URL."));
                die();
            }
        }
        
        // If no type defined use default
        if ((isset($_REQUEST['type'])) && ($_REQUEST['type'] != '')) {
            $type = $_REQUEST['type'];
            
            // Flatten type arrays for backward compatibility:
            if (is_array($type)) {
                $type = $type[0];
            }
        } else {
            $type = $this->defaultIndex;
        }

        $this->searchTerms[] = array(
            'index'   => $type,
            'lookfor' => $_REQUEST['lookfor']
        );
        
        return true;
    }

    /**
     * Initialize the object's search settings for an advanced search found in the
     * $_REQUEST superglobal.  Advanced searches have numeric subscripts on the
     * lookfor and type parameters -- this is how they are distinguished from basic
     * searches.
     *
     * @access  protected
     */
    protected function initAdvancedSearch()
    {
        //********************
        // Advanced Search logic
        //  'lookfor0[]' 'type0[]'
        //  'lookfor1[]' 'type1[]' ...
        $this->searchType = $this->advancedSearchType;
        $groupCount = 0;
        // Loop through each search group
        while (isset($_REQUEST['lookfor'.$groupCount])) {
            $group = array();
            // Loop through each term inside the group
            for ($i = 0; $i < count($_REQUEST['lookfor'.$groupCount]); $i++) {
                // Ignore advanced search fields with no lookup
                if ($_REQUEST['lookfor'.$groupCount][$i] != '') {
                    // Use default fields if not set
                    if (isset($_REQUEST['type'.$groupCount][$i]) && $_REQUEST['type'.$groupCount][$i] != '') {
                        $type = $_REQUEST['type'.$groupCount][$i];
                    } else {
                        $type = $this->defaultIndex;
                    }

                    // Add term to this group
                    $group[] = array(
                        'field'   => $type,
                        'lookfor' => $_REQUEST['lookfor'.$groupCount][$i],
                        'bool'    => $_REQUEST['bool'.$groupCount][0]
                    );
                }
            }

            // Make sure we aren't adding groups that had no terms
            if (count($group) > 0) {
                // Add the completed group to the list
                $this->searchTerms[] = array(
                    'group' => $group,
                    'join'  => $_REQUEST['join']
                );
            }

            // Increment 
            $groupCount++;
        }

        // Finally, if every advanced row was empty
        if (count($this->searchTerms) == 0) {
            // Treat it as an empty basic search
            $this->searchType = $this->basicSearchType;
            $this->searchTerms[] = array(
                'index'   => $this->defaultIndex,
                'lookfor' => ''
            );
        }
    }

    /**
     * Add view mode to the object based on the $_REQUEST superglobal.
     *
     * @access  protected
     */
    protected function initView()
    {
        //********************
        // RSS feed? We should only see this in GET from a url.
        if (isset($_REQUEST['view']) && ($_REQUEST['view'] == 'rss')) {
          $this->view = 'rss';
        }
    }

    /**
     * Add page number to the object based on the $_REQUEST superglobal.
     *
     * @access  protected
     */
    protected function initPage()
    {
        if (isset($_REQUEST['page'])) {
            $this->page = $_REQUEST['page'];
        }
        $this->page = intval($this->page);
        if ($this->page < 1) {
            $this->page = 1;
        }
    }

    /**
     * Add sort value to the object based on the $_REQUEST superglobal.
     *
     * @access  protected
     */
    protected function initSort()
    {
        if (isset($_REQUEST['sort'])) {
            $this->sort = $_REQUEST['sort'];
        } else {
            // Is there a search-specific sort type set?
            $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : false;
            if ($type && isset($this->defaultSortByType[$type])) {
                $this->sort = $this->defaultSortByType[$type];
            // If no search-specific sort type was found, use the overall default:
            } else {
                $this->sort = $this->defaultSort;
            }
        }
    }

    /**
     * Add filters to the object based on values found in the $_REQUEST superglobal.
     *
     * @access  protected
     */
    protected function initFilters()
    {
        if (isset($_REQUEST['filter'])) {
            if (is_array($_REQUEST['filter'])) {
                foreach($_REQUEST['filter'] as $filter) {
                    $this->addFilter($filter);
                }
            } else {
                $this->addFilter($_REQUEST['filter']);
            }
        }
    }

    /**
     * Build a url for the current search
     *
     * @access  public
     * @return  string   URL of a search
     */
    public function renderSearchUrl()
    {
        // Get the base URL and initialize the parameters attached to it:
        $url = $this->getBaseUrl();
        $params = $this->getSearchParams();

        // Add any filters
        if (count($this->filterList) > 0) {
            foreach ($this->filterList as $field => $filter) {
                foreach ($filter as $value) {
                    $params[] = "filter[]=" . urlencode("$field:\"$value\"");
                }
            }
        }

        // Sorting
        if ($this->sort != null && $this->sort != $this->defaultSort) {
            $params[] = "sort=" . urlencode($this->sort);
        }

        // Page number
        if ($this->page != 1) {
            // Don't url encode if it's the paging template
            if ($this->page == '%d') {
                $params[] = "page=" . $this->page;
            // Otherwise... encode to prevent XSS.
            } else {
                $params[] = "page=" . urlencode($this->page);
            }
        }

        // View
        if ($this->view != null) {
            $params[] = "view=" . urlencode($this->view);
        }

        // Join all parameters with an escaped ampersand,
        //   add to the base url and return
        return $url . join("&", $params);
    }

    /**
     * Return a url for use by pagination template
     *
     * @access  public
     * @return  string   URL of a new search
     */
    public function renderLinkPageTemplate()
    {
        // Stash our old data for a minute
        $oldPage = $this->page;
        // Add the page template
        $this->page = '%d';
        // Get the new url
        $url = $this->renderSearchUrl();
        // Restore the old data
        $this->page = $oldPage;
        // Return the URL
        return $url;
    }

    /**
     * Return a url for the current search with a new sort
     *
     * @access  public
     * @param   string   $new_sort   A field to sort by
     * @return  string   URL of a new search
     */
    public function renderLinkWithSort($newSort)
    {
        // Stash our old data for a minute
        $oldSort = $this->sort;
        // Add the new sort
        $this->sort = $newSort;
        // Get the new url
        $url = $this->renderSearchUrl();
        // Restore the old data
        $this->sort = $oldSort;
        // Return the URL
        return $url;
    }

    /**
     * Return a list of urls for sorting, along with which option
     *    should be currently selected.
     *
     * @access  public
     * @return  array    Sort urls, descriptions and selected flags
     */
    public function getSortList()
    {
        // Loop through all the current filter fields
        $valid = $this->getSortOptions();
        $list = array();
        foreach ($valid as $sort => $desc) {
            $list[$sort] = array(
                'sortUrl'  => $this->renderLinkWithSort($sort),
                'desc' => $desc,
                'selected' => ($sort == $this->sort)
            );
        }
        return $list;
    }

    /**
     * Basic 'getters'
     *
     * @access  public
     * @return  mixed    various internal variables
     */
    public function getAdvancedTypes()  {return $this->advancedTypes;}
    public function getBasicTypes()     {return $this->basicTypes;}
    public function getFilters()        {return $this->filterList;}
    public function getPage()           {return $this->page;}
    public function getQuerySpeed()     {return $this->queryTime;}
    public function getRawSuggestions() {return $this->suggestions;}
    public function getResultTotal()    {return $this->resultsTotal;}
    public function getSearchId()       {return $this->searchId;}
    public function getSearchTerms()    {return $this->searchTerms;}
    public function getSearchType()     {return $this->searchType;}
    public function getStartTime()      {return $this->initTime;}
    public function getTotalSpeed()     {return $this->totalTime;}
    public function getView()           {return $this->view;}
    public function isSavedSearch()     {return $this->savedSearch;}
    public function getLimit()          {return $this->limit;}

    /**
     * Protected 'getters' for values not intended for use outside the class.
     *
     * @access  protected
     * @return  mixed    various internal variables
     */
    protected function getSortOptions() { return $this->sortOptions; }
    
    /**
     * Reset a simple query against the default index.
     *
     * @access  public
     * @param   string  $query   Query string
     * @param   string  $index   Index to search (exclude to use default)
     */
    public function setBasicQuery($query, $index = null)
    {
        if (is_null($index)) {
            $index = $this->defaultIndex;
        }
        $this->searchTerms = array();
        $this->searchTerms[] = array(
            'index'   => $index,
            'lookfor' => $query
        );
    }

    /**
     * Set the number of search results returned per page.
     *
     * @access  public
     * @param   int     $limit      New page limit value
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * Set the result page, override the page parameter in  $_REQUEST.
     *
     * @access  public
     * @param   int     $page      New page value
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

    /**
     * Add a field to facet on.
     *
     * @access  public
     * @param   string  $newField   Field name
     * @param   string  $newAlias   Optional on-screen display label
     */
    public function addFacet($newField, $newAlias = null)
    {
        if ($newAlias == null) {
            $newAlias = $newField;
        }
        $this->facetConfig[$newField] = $newAlias;
    }

    /**
     * Add a checkbox facet.  When the checkbox is checked, the specified filter
     * will be applied to the search.  When the checkbox is not checked, no filter
     * will be applied.
     *
     * @access  public
     * @param   string  $filter     [field]:[value] pair to associate with checkbox
     * @param   string  $desc       Description to associate with the checkbox
     */
    public function addCheckboxFacet($filter, $desc) {
        // Extract the facet field name from the filter, then add the
        // relevant information to the array.
        list($fieldName) = explode(':', $filter);
        $this->checkboxFacets[$fieldName] = 
            array('desc' => $desc, 'filter' => $filter);
    }

    /**
     * Get information on the current state of the boolean checkbox facets.
     *
     * @access  public
     * @return  array
     */
    public function getCheckboxFacets()
    {
        // Create a lookup array of filter removal URLs -- this will tell us
        // if any of the boxes are checked, and help us uncheck them if they are.
        //
        // Note that this assumes that each boolean filter's field name will only
        // show up once anywhere in the filter list -- this is why you can't use
        // the same field both in the checkbox facet list and the regular facet
        // list.
        $filters = $this->getFilterList();
        $deselect = array();
        foreach($filters as $currentSet) {
            foreach($currentSet as $current) {
                $deselect[$current['field']] = $current['removalUrl'];
            }
        }
        
        // Now build up an array of checkbox facets with status booleans and
        // toggle URLs.
        $facets = array();
        foreach($this->checkboxFacets as $field => $details) {
            $facets[$field] = $details;
            if (isset($deselect[$field])) {
                $facets[$field]['selected'] = true;
                $facets[$field]['toggleUrl'] = $deselect[$field];
            } else {
                $facets[$field]['selected'] = false;
                $facets[$field]['toggleUrl'] = 
                    $this->renderLinkWithFilter($details['filter']);
            }
            // Is this checkbox always visible, even if non-selected on the
            // "no results" screen?  By default, no (may be overridden by
            // child classes).
            $facets[$field]['alwaysVisible'] = false;
        }
        return $facets;
    }

    /**
     * Return an array of data summarising the results of a search.
     *
     * @access  public
     * @return  array   summary of results
     */
    public function getResultSummary()
    {
        $summary = array();

        $summary['page']        = $this->page;
        $summary['perPage']     = $this->limit;
        $summary['resultTotal'] = $this->resultsTotal;
        // 1st record is easy, work out the start of this page
        $summary['startRecord'] = (($this->page - 1) * $this->limit) + 1;
        // Last record needs more care
        if ($this->resultsTotal < $this->limit) {
            // There are less records returned then one page, use total results
            $summary['endRecord'] = $this->resultsTotal;
        } else if (($this->page * $this->limit) > $this->resultsTotal) {
            // The end of the curent page runs past the last record, use total results
            $summary['endRecord'] = $this->resultsTotal;
        } else {
            // Otherwise use the last record on this page
            $summary['endRecord'] = $this->page * $this->limit;
        }

        return $summary;
    }

    /**
     * Get a link to a blank search restricted by the specified facet value.
     *
     * @access  protected
     * @param   string      $field      The facet field to limit on
     * @param   string      $value      The facet value to limit with
     * @return  string                  The URL to the desired search
     */
    protected function getExpandingFacetLink($field, $value)
    {
        // Stash our old search
        $temp_data = $this->searchTerms;
        $temp_type = $this->searchType;

        // Add an empty search
        $this->searchType = $this->basicSearchType;
        $this->setBasicQuery('');

        // Get the link:
        $url = $this->renderLinkWithFilter("{$field}:{$value}");

        // Restore our old search
        $this->searchTerms = $temp_data;
        $this->searchType  = $temp_type;

        // Send back the requested link>
        return $url;
    }

    /**
     * Returns the stored list of facets for the last search
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
        // Assume no facets by default -- child classes can override this to extract
        // the necessary details from the results saved by processSearch().
        return array();
    }
    
    /**
     * Disable logging. Used to stop administrative searches
     *    appearing in search histories
     *
     * @access  public
     */
    public function disableLogging() {
        $this->disableLogging = true;
    }

    /**
     * Used during repeated deminification (such as search history).
     *   To scrub fields populated above.
     *
     * @access  protected
     */
    protected function purge()
    {
        $this->searchType   = $this->basicSearchType;
        $this->searchId     = null;
        $this->resultsTotal = null;
        $this->filterList   = null;
        $this->initTime     = null;
        $this->queryTime    = null;
        // An array so we don't have to initialise
        //   the empty array during population.
        $this->searchTerms  = array();
    }

    /**
     * Create a minified copy of this object for storage in the database.
     *
     * @access  protected
     * @return  object     A SearchObject instance
     */
    protected function minify()
    {
        // Clone ourself as a minified object
        $newObject = new minSO($this);
        // Return the new object
        return $newObject;
    }

    /**
     * Initialise the object from a minified one stored in a cookie or database.
     *  Needs to be kept up-to-date with the minSO object at the end of this file.
     *
     * @access  public
     * @param   object     A minSO object
     */
    public function deminify($minified)
    {
        // Clean the object
        $this->purge();

        // Most values will transfer without changes
        $this->searchId     = $minified->id;
        $this->initTime     = $minified->i;
        $this->queryTime    = $minified->s;
        $this->resultsTotal = $minified->r;
        $this->filterList   = $minified->f;
        $this->searchType   = $minified->ty;

        // Search terms, we need to expand keys
        $tempTerms = $minified->t;
        foreach ($tempTerms as $term) {
            $newTerm = array();
            foreach ($term as $k => $v) {
                switch ($k) {
                  case 'j' :  $newTerm['join']    = $v; break;
                  case 'i' :  $newTerm['index']   = $v; break;
                  case 'l' :  $newTerm['lookfor'] = $v; break;
                  case 'g' :
                      $newTerm['group'] = array();
                      foreach ($v as $line) {
                          $search = array();
                          foreach ($line as $k2 => $v2) {
                              switch ($k2) {
                                  case 'b' :  $search['bool']    = $v2; break;
                                  case 'f' :  $search['field']   = $v2; break;
                                  case 'l' :  $search['lookfor'] = $v2; break;
                              }
                          }
                          $newTerm['group'][] = $search;
                      }
                      break;
                }
            }
            $this->searchTerms[] = $newTerm;
        }
    }

    /**
     * Add into the search table (history)
     *
     * @access  protected
     */
    protected function addToHistory()
    {
        global $user;

        // Get the list of all old searches for this session and/or user
        $s = new SearchEntry();
        $searchHistory = $s->getSearches(session_id(), is_object($user) ? $user->id : null);

        // Duplicate elimination
        $dupSaved  = false;
        foreach ($searchHistory as $oldSearch) {
            // Deminify the old search
            $minSO = unserialize($oldSearch->search_object);
            $dupSearch = SearchObjectFactory::deminify($minSO);
            // See if the classes and urls match
            if (get_class($dupSearch) && get_class($this) &&
                $dupSearch->renderSearchUrl() == $this->renderSearchUrl()) {
                // Is the older search saved?
                if ($oldSearch->saved) {
                    // Flag for later
                    $dupSaved = true;
                    // Record the details
                    $this->searchId    = $oldSearch->id;
                    $this->savedSearch = true;
                } else {
                    // Delete this search
                    $oldSearch->delete();
                }
            }
        }

        // Save this search unless we found a 'saved' duplicate
        if (!$dupSaved) {
            $search = new SearchEntry();
            $search->session_id = session_id();
            $search->created = date('Y-m-d');
            $search->search_object = serialize($this->minify());

            $search->insert();
            // Record the details
            $this->searchId    = $search->id;
            $this->savedSearch = false;

            // Chicken and egg... We didn't know the id before insert
            $search->search_object = serialize($this->minify());
            $search->update();
        }
    }

    /**
     * If there is a saved search being loaded through $_REQUEST, redirect to the
     * URL for that search.  If no saved search was requested, return false.  If 
     * unable to load a requested saved search, return a PEAR_Error object.
     *
     * @access  protected
     * @return  mixed               Does not return on successful load, returns 
     *                              false if no search to restore, returns
     *                              PEAR_Error object in case of trouble.
     */
    protected function restoreSavedSearch()
    {
        global $user;

        // Is this is a saved search?
        if (isset($_REQUEST['saved'])) {
            // Yes, retrieve it
            $search = new SearchEntry();
            $search->id = $_REQUEST['saved'];
            if ($search->find(true)) {
                // Found, make sure the user has the
                //   rights to view this search
                if ($search->session_id == session_id() || $search->user_id == $user->id) {
                    // They do, deminify it to a new object.
                    $minSO = unserialize($search->search_object);
                    $savedSearch = SearchObjectFactory::deminify($minSO);

                    // Now redirect to the URL associated with the saved search;
                    // this simplifies problems caused by mixing different classes
                    // of search object, and it also prevents the user from ever
                    // landing on a "?saved=xxxx" URL, which may not persist beyond
                    // the current session.  (We want all searches to be 
                    // persistent and bookmarkable).
                    header('Location: ' . $savedSearch->renderSearchUrl());
                    die();
                } else {
                    // They don't
                    // TODO : Error handling -
                    //    User is trying to view a saved search from
                    //    another session (deliberate or expired) or
                    //    associated with another user.
                    return new PEAR_Error("Attempt to access invalid search ID");
                }
            }
        }
        
        // Report no saved search to restore.
        return false;
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
        // Start the timer
        $mtime = explode(' ', microtime());
        $this->initTime = $mtime[1] + $mtime[0];
        return true;
    }

    /**
     * An optional de-initialise function.
     * 
     *   At this stage it's used for finish of timing calculations
     *   and logged search history.
     * 
     *   Possible future uses will including closing child resources if
     *     required (database connections) or finish database writes
     *     (audit tables). Remember the destructor() can also be used
     *     for mandatory stuff.
     *   Might need parameters for logging level and whether to keep
     *     the search in history etc.
     *
     * @access  public
     */
    public function close()
    {
        // Finish timing
        $mtime = explode(" ", microtime());
        $this->endTime = $mtime[1] + $mtime[0];
        $this->totalTime = $this->endTime - $this->initTime;

        if (!$this->disableLogging) {
            // Add to search history
            $this->addToHistory();
        }
        
        //if ($this->debug) {
        //    echo $this->debugOutput();
        //}
    }

    /**
     * DEBUGGING. Support function for debugOutput().
     *
     * @access  protected
     * @return  string
     */
    protected function debugOutputSearchTerms()
    {
        // Advanced search
        if (isset($this->searchTerms[0]['group'])) {
            $output = "GROUP JOIN : " . $this->searchTerms[0]['join'] . "<br/>\n";
            for ($i = 0; $i < count($this->searchTerms); $i++) {
                $output .= "BOOL ($i) : " . $this->searchTerms[$i]['group'][0]['bool'] . "<br/>\n";
                for ($j = 0; $j < count($this->searchTerms[$i]['group']); $j++) {
                    $output .= "TERMS ($i)($j) : " . $this->searchTerms[$i]['group'][$j]['lookfor'] . "<br/>\n";
                    $output .= "INDEX ($i)($j) : " . $this->searchTerms[$i]['group'][$j]['field'] . "<br/>\n";
                }
            }
        // Basic search
        } else {
            $output = "TERMS : " . $this->searchTerms[0]['lookfor'] . "<br/>\n";
            $output .= "INDEX : " . $this->searchTerms[0]['index']   . "<br/>\n";
        }
        
        return $output;
    }

    /**
     * DEBUGGING. Use this to print out your search.
     *
     * @access  public
     * @return  string
     */
    public function debugOutput()
    {
        $output = "VIEW : " . $this->view . "<br/>\n";
        $output .= $this->debugOutputSearchTerms();

        foreach ($this->filterList as $field => $filter) {
            foreach ($filter as $value) {
                $output .= "FILTER : $field => $value<br/>\n";
            }
        }
        $output .= "PAGE : "   . $this->page         . "<br/>\n";
        $output .= "SORT : "   . $this->sort         . "<br/>\n";
        $output .= "TIMING : START : "   . $this->initTime       . "<br/>\n";
        $output .= "TIMING : QUERY.S : " . $this->queryStartTime . "<br/>\n";
        $output .= "TIMING : QUERY.E : " . $this->queryEndTime   . "<br/>\n";
        $output .= "TIMING : FINISH : "  . $this->endTime        . "<br/>\n";

        return $output;
    }

    /**
     * Start the timer to figure out how long a query takes.  Complements
     * stopQueryTimer().
     *
     * @access protected
     */
    protected function startQueryTimer()
    {
        // Get time before the query
        $time = explode(" ", microtime());
        $this->queryStartTime = $time[1] + $time[0];
    }

    /**
     * End the timer to figure out how long a query takes.  Complements
     * startQueryTimer().
     *
     * @access protected
     */
    protected function stopQueryTimer()
    {
        $time = explode(" ", microtime());
        $this->queryEndTime = $time[1] + $time[0];
        $this->queryTime = $this->queryEndTime - $this->queryStartTime;
    }

    /**
     * Return the field (index) searched by a basic search
     *
     * @access  public
     * @return  string   The searched index
     */
    public function getSearchIndex()
    {
        // Single search index does not apply to advanced search:
        if ($this->searchType == $this->advancedSearchType) {
            return null;
        }
        return $this->searchTerms[0]['index'];
    }

    /**
     * Find a word amongst the current search terms
     *
     * @access  protected
     * @param   string   Search term to find
     * @return  bool     True/False if the word was found
     */
    protected function findSearchTerm($needle) {
        // Advanced search
        if (isset($this->searchTerms[0]['group'])) {
            foreach ($this->searchTerms as $group) {
                foreach ($group['group'] as $search) {
                    if (preg_match("/\b$needle\b/", $search['lookfor'])) {
                        return true;
                    }
                }
            }

        // Basic search
        } else {
            foreach ($this->searchTerms as $haystack) {
                if (preg_match("/\b$needle\b/", $haystack['lookfor'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Replace a search term in the query
     *
     * @access  protected
     * @param   string   $from  Search term to find
     * @param   string   $to    Search term to insert
     */
    protected function replaceSearchTerm($from, $to) {
        // Escape $from so it is regular expression safe (just in case it
        // includes any weird punctuation -- unlikely but possible):
        $from = addcslashes($from, '\^$.[]|()?*+{}/');

        // If we are looking for a quoted phrase
        // we can't use word boundaries
        if (strpos($from, '"') === false) {
            $pattern = "/\b$from\b/i";
        } else  {
            $pattern = "/$from/i";
        }

        // Advanced search
        if (isset($this->searchTerms[0]['group'])) {
            for ($i = 0; $i < count($this->searchTerms); $i++) {
                for ($j = 0; $j < count($this->searchTerms[$i]['group']); $j++) {
                    $this->searchTerms[$i]['group'][$j]['lookfor'] = 
                        preg_replace($pattern, $to, 
                            $this->searchTerms[$i]['group'][$j]['lookfor']);
                }
            }
        // Basic search
        } else {
            for ($i = 0; $i < count($this->searchTerms); $i++) {
                // Perform the replacement:
                $this->searchTerms[$i]['lookfor'] = preg_replace($pattern, 
                    $to, $this->searchTerms[$i]['lookfor']);
            }
        }
    }

    /**
     * Return a query string for the current search with
     *   a search term replaced
     *
     * @access  public
     * @param   string   $oldTerm   The old term to replace
     * @param   string   $newTerm   The new term to search
     * @return  string   query string
     */
    public function getDisplayQueryWithReplacedTerm($oldTerm, $newTerm)
    {
        // Stash our old data for a minute
        $oldTerms = $this->searchTerms;
        // Replace the search term
        $this->replaceSearchTerm($oldTerm, $newTerm);
        // Get the new query string
        $query = $this->displayQuery();
        // Restore the old data
        $this->searchTerms = $oldTerms;
        // Return the query string
        return $query;
    }

    /**
     * Input Tokenizer - Specifically for spelling purposes
     *
     * Because of its focus on spelling, these tokens are unsuitable
     * for actual searching. They are stripping important search data
     * such as joins and groups, simply because they don't need to be
     * spellchecked.
     *
     * @return  array               Tokenized array
     * @access  public
     */
    public function spellingTokens($input)
    {
        $joins = array("AND", "OR", "NOT");
        $paren = array("(" => "", ")" => "");

        // Base of this algorithm comes straight from
        // PHP doco examples & benighted at gmail dot com
        // http://php.net/manual/en/function.strtok.php
        $tokens = array();
        $token = strtok($input,' ');
        while ($token) {
            // find bracketed tokens
            if ($token{0}=='(') {$token .= ' '.strtok(')').')';}
            // find double quoted tokens
            if ($token{0}=='"') {$token .= ' '.strtok('"').'"';}
            // find single quoted tokens
            if ($token{0}=="'") {$token .= ' '.strtok("'")."'";}
            $tokens[] = $token;
            $token = strtok(' ');
        }
        // Some cleaning of tokens that are just boolean joins
        //  and removal of brackets
        $return = array();
        foreach ($tokens as $token) {
            // Ignore join
            if (!in_array($token, $joins)) {
                // And strip parentheses
                $final = trim(strtr($token, $paren));
                if ($final != "") $return[] = $final; 
            }
        }
        return $return;
    }

    /**
     * Return a url for the current search with a search term replaced
     *
     * @access  public
     * @param   string   $oldTerm   The old term to replace
     * @param   string   $newTerm   The new term to search
     * @return  string   URL of a new search
     */
    public function renderLinkWithReplacedTerm($oldTerm, $newTerm)
    {
        // Stash our old data for a minute
        $oldTerms = $this->searchTerms;
        $oldPage = $this->page;
        // Switch to page 1 -- it doesn't make sense to maintain the current page
        // when changing the contents of the search
        $this->page = 1;
        // Replace the term
        $this->replaceSearchTerm($oldTerm, $newTerm);
        // Get the new url
        $url = $this->renderSearchUrl();
        // Restore the old data
        $this->searchTerms = $oldTerms;
        $this->page = $oldPage;
        // Return the URL
        return $url;
    }

    /**
     * Get the templates used to display recommendations for the current search.
     *
     * @access  public
     * @param   string      $location           'top' or 'side'
     * @return  array       Array of templates to display at the specified location.
     */
    public function getRecommendationsTemplates($location = 'top')
    {
        $retval = array();
        if (isset($this->recommend[$location]) && 
            !empty($this->recommend[$location])) {
            foreach($this->recommend[$location] as $current) {
                $retval[] = $current->getTemplate();
            }
        }
        return $retval;
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
        // Load the necessary settings to determine the appropriate recommendations
        // module:
        $search = $this->searchTerms;
        $searchSettings = getExtraConfigArray($this->recommendIni);
        
        // If we have just one search type, save it so we can try to load a 
        // type-specific recommendations module:
        if (count($search) == 1 && isset($search[0]['index'])) {
            $searchType = $search[0]['index'];
        } else {
            $searchType = false;
        }
        
        // Load a type-specific recommendations setting if possible, or the default
        // otherwise:
        $recommend = array();
        if ($searchType && 
            isset($searchSettings['TopRecommendations'][$searchType])) {
            $recommend['top'] = $searchSettings['TopRecommendations'][$searchType];
        } else {
            $recommend['top'] = 
                isset($searchSettings['General']['default_top_recommend']) ?
                $searchSettings['General']['default_top_recommend'] : false;
        }
        if ($searchType && 
            isset($searchSettings['SideRecommendations'][$searchType])) {
            $recommend['side'] = $searchSettings['SideRecommendations'][$searchType];
        } else {
            $recommend['side'] = 
                isset($searchSettings['General']['default_side_recommend']) ?
                $searchSettings['General']['default_side_recommend'] : false;
        }
        
        return $recommend;
    }

    /**
     * Initialize the recommendations module based on current searchTerms.
     *
     * @access  protected
     */
    protected function initRecommendations()
    {
        // If no settings were found, quit now:
        $settings = $this->getRecommendationSettings();
        if (empty($settings)) {
            $this->recommend = false;
            return;
        }
        
        // Process recommendations for each location:
        $this->recommend = array('top' => array(), 'side' => array());
        foreach($settings as $location => $currentSet) {
            // If the current location is disabled, skip processing!
            if (empty($currentSet)) {
                continue;
            }
            // Make sure the current location's set of recommendations is an array;
            // if it's a single string, this normalization will simplify processing.
            if (!is_array($currentSet)) {
                $currentSet = array($currentSet);
            }
            // Now loop through all recommendation settings for the location.
            foreach($currentSet as $current) {
                // Break apart the setting into module name and extra parameters:
                $current = explode(':', $current);
                $module = array_shift($current);
                $params = implode(':', $current);
                
                // Can we build a recommendation module with the provided settings?
                // If the factory throws an error, we'll assume for now it means we
                // tried to load a non-existent module, and we'll ignore it.
                $obj = RecommendationFactory::initRecommendation($module, $this,
                    $params);
                if ($obj && !PEAR::isError($obj)) {
                    $obj->init();
                    $this->recommend[$location][] = $obj;
                }
            }
        }
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
        // By default, there is only set of facet settings, so this function isn't
        // really necessary.  However, in the Search History screen, we need to
        // use this for Solr-based Search Objects, so we need this dummy method to
        // allow other types of Search Objects to co-exist with Solr-based ones.
        // See the Solr Search Object for details of how this works if you need to
        // implement context-sensitive facet settings in another module.
    }

    /**
     * Translate a field name to a displayable string for rendering a query in
     * human-readable format:
     *
     * @access  protected
     * @param   string      $field          Field name to display.
     * @return  string                      Human-readable version of field name.
     */
    protected function getHumanReadableFieldName($field)
    {
        if (isset($this->basicTypes[$field])) {
            return translate($this->basicTypes[$field]);
        } else if (isset($this->advancedTypes[$field])) {
            return translate($this->advancedTypes[$field]);
        } else {
            return $field;
        }
    }

    /**
     * Get a human-readable presentation version of the advanced search query
     * stored in the object.  This will not work if $this->searchType is not
     * 'advanced.'
     *
     * @access  protected
     * @return  string
     */
    protected function buildAdvancedDisplayQuery()
    {
        // Groups and exclusions. This mirrors some logic in Solr.php
        $groups   = array();
        $excludes = array();

        foreach ($this->searchTerms as $search) {
            $thisGroup = array();
            // Process each search group
            foreach ($search['group'] as $group) {
                // Build this group individually as a basic search
                $thisGroup[] = $this->getHumanReadableFieldName($group['field']) .
                    ":{$group['lookfor']}";
            }
            // Is this an exclusion (NOT) group or a normal group?
            if ($search['group'][0]['bool'] == 'NOT') {
                $excludes[] = join(" OR ", $thisGroup);
            } else {
                $groups[] = join(" ".$search['group'][0]['bool']." ", $thisGroup);
            }
        }

        // Base 'advanced' query
        $output = "(" . join(") " . $this->searchTerms[0]['join'] . " (", $groups) . ")";
        // Concatenate exclusion after that
        if (count($excludes) > 0) {
            $output .= " NOT ((" . join(") OR (", $excludes) . "))";
        }
        
        return $output;
    }

    /**
     * Build a string for onscreen display showing the
     *   query used in the search (not the filters).
     * 
     * @access  public
     * @return  string   user friendly version of 'query'
     */
    public function displayQuery()
    {
        // Advanced search?
        if ($this->searchType == $this->advancedSearchType) {
            return $this->buildAdvancedDisplayQuery();
        }
        // Default -- Basic search:
        return $this->searchTerms[0]['lookfor'];
    }

    /**
     * Turn the list of spelling suggestions into an array of urls
     *   for on-screen use to implement the suggestions.
     *
     * @access  public
     * @return  array     Spelling suggestion data arrays
     */
    abstract public function getSpellingSuggestions();

    /**
     * Actually process and submit the search; in addition to returning results,
     * this method is responsible for populating various class properties that
     * are returned by other get methods (i.e. getFacetList).
     *
     * @access  public
     * @param   bool   $returnIndexErrors  Should we die inside the index code if
     *                                     we encounter an error (false) or return
     *                                     it for access via the getIndexError() 
     *                                     method (true)?
     * @param   bool   $recommendations    Should we process recommendations along
     *                                     with the search itself?
     * @return  object   Search results (format may vary from class to class).
     */
    abstract public function processSearch($returnIndexErrors = false, 
        $recommendations = false);

    /**
     * Get error message from index response, if any.  This will only work if 
     * processSearch was called with $returnIndexErrors set to true!
     *
     * @access  public
     * @return  mixed       false if no error, error string otherwise.
     */
    abstract public function getIndexError();
}

/**
 * ****************************************************
 * 
 * A minified search object used exclusively for trimming
 *  a search object down to it's barest minimum size
 *  before storage in a cookie or database.
 * 
 * It's still contains enough data granularity to
 *  programmatically recreate search urls.
 * 
 * This class isn't intended for general use, but simply
 *  a way of storing/retrieving data from a search object:
 * 
 * eg. Store
 * $searchHistory[] = serialize($this->minify());
 * 
 * eg. Retrieve
 * $searchObject  = SearchObjectFactory::initSearchObject();
 * $searchObject->deminify(unserialize($search));
 * 
 */
class minSO
{
    public $t = array();
    public $f = array();
    public $id, $i, $s, $r, $ty;

    /**
     * Constructor. Building minified object from the
     *    searchObject passed in. Needs to be kept
     *    up-to-date with the deminify() function on
     *    searchObject.
     *
     * @access  public
     */
    public function __construct($searchObject)
    {
        // Most values will transfer without changes
        $this->id = $searchObject->getSearchId();
        $this->i  = $searchObject->getStartTime();
        $this->s  = $searchObject->getQuerySpeed();
        $this->r  = $searchObject->getResultTotal();
        $this->ty = $searchObject->getSearchType();

        // Search terms, we'll shorten keys
        $tempTerms = $searchObject->getSearchTerms();
        foreach ($tempTerms as $term) {
            $newTerm = array();
            foreach ($term as $k => $v) {
                switch ($k) {
                  case 'join'    :  $newTerm['j'] = $v; break;
                  case 'index'   :  $newTerm['i'] = $v; break;
                  case 'lookfor' :  $newTerm['l'] = $v; break;
                  case 'group' :
                      $newTerm['g'] = array();
                      foreach ($v as $line) {
                          $search = array();
                          foreach ($line as $k2 => $v2) {
                              switch ($k2) {
                                  case 'bool'    :  $search['b'] = $v2; break;
                                  case 'field'   :  $search['f'] = $v2; break;
                                  case 'lookfor' :  $search['l'] = $v2; break;
                              }
                          }
                          $newTerm['g'][] = $search;
                      }
                      break;
                }
            }
            $this->t[] = $newTerm;
        }

        // It would be nice to shorten filter fields too, but
        //      it would be a nightmare to maintain.
        $this->f = $searchObject->getFilters();
    }
}
?>