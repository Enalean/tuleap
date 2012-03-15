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
require_once 'sys/Summon.php';
require_once 'sys/SearchObject/Base.php';

/* A derivative of the Search Object for use with Summon.
 */
class SearchObject_Summon extends SearchObject_Base
{
    // OTHER VARIABLES
    private $summon;        // Summon API Interface
    private $indexResult;   // Summon API Response
    
    // In the Summon configuration, facets may have extra parameters appended;
    // in most cases, we want to strip these off, but this array lets us store
    // all the extra parameters so they can be passed to the Summon class.
    private $fullFacetSettings = array();

    /**
     * Constructor. Initialise some details about the server
     *
     * @access  public
     */
    public function __construct()
    {
        global $configArray;
        
        // Standard logic from parent class:
        parent::__construct();
        
        // Set up appropriate results action:
        $this->resultsModule = 'Summon';
        $this->resultsAction = 'Search';
        
        // Set up basic and advanced Summon search types; default to basic.
        $this->searchType = $this->basicSearchType = 'Summon';
        $this->advancedSearchType = 'SummonAdvanced';
        
        // Set up facet configuration -- note that we may need to strip extra 
        // parameters from the facet names (hence the extra processing):
        $config = getExtraConfigArray('Summon');
        foreach($config['Facets'] as $key => $value) {
            $parts = explode(',', $key);
            $facetName = trim($parts[0]);
            $this->facetConfig[$facetName] = $value;
        }
        
        // Set up spelling preference
        if (isset($config['Spelling']['enabled'])) {
            $this->spellcheck    = $config['Spelling']['enabled'];
        }
        
        // Set up sort options
        $this->sortOptions = $config['Sorting'];
        if (isset($config['General']['default_sort'])) {
            $this->defaultSort = $config['General']['default_sort'];
        }
        
        // Set up search options
        $this->basicTypes = $config['Basic_Searches'];
        if (isset($config['Advanced_Searches'])) {
            $this->advancedTypes = $config['Advanced_Searches'];
        }
        
        // Set up recommendations options -- settings are found in Summon.ini:
        $this->recommendIni = 'Summon';
        
        // Connect to Summon
        $this->summon = new Summon($configArray['Summon']['apiId'], 
            $configArray['Summon']['apiKey']);
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

        $this->initView();
        $this->initPage();
        $this->initSort();
        $this->initFilters();
        
        // Try to find a basic search first; check for advanced if no basic found.
        if (!$this->initBasicSearch()) {
            $this->initAdvancedSearch();
        }
        return true;
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
        // Save the full field name (which may include extra parameters);
        // we'll need these to do the proper search using the Summon class:
        $this->fullFacetSettings[] = $newField;
        
        // Strip parameters from field name if necessary (since they get
        // in the way of most Search Object functionality):
        $newField = explode(',', $newField);
        $newField = trim($newField[0]);
        parent::addFacet($newField, $newAlias);
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
     * @return  array     Facets data arrays
     */
    public function getFacetList($filter = null, $expandingLinks = false)
    {
        // If there is no filter, we'll use all facets as the filter:
        if (is_null($filter)) {
            $filter = $this->facetConfig;
        // If there is a filter, make sure the field names are properly
        // stripped of extra parameters:
        } else {
            $oldFilter = $filter;
            $filter = array();
            foreach($oldFilter as $key => $value) {
                $key = explode(',', $key);
                $key = trim($key[0]);
                $filter[$key] = $value;
            }
        }

        // We want to sort the facets to match the order in the .ini file.  Let's
        // create a lookup array to determine order:
        $i = 0;
        $order = array();
        foreach($filter as $key => $value) {
            $order[$key] = $i++;
        }
        
        // Loop through the facets returned by Summon.
        $facetResult = array();
        if (is_array($this->indexResult['facetFields'])) {
            foreach($this->indexResult['facetFields'] as $current) {
                // The "displayName" value is actually the name of the field on
                // Summon's side -- we'll probably need to translate this to a
                // different value for actual display!
                $field = $current['displayName'];
                
                // Is this one of the fields we want to display?  If so, do work...
                if (isset($filter[$field])) {
                    // Loop through all the facet values to see if any are applied.
                    foreach($current['counts'] as $facetIndex => $facetDetails) {
                        $isApplied = false;
                        // Is this field a current filter?
                        if (in_array($field, array_keys($this->filterList))) {
                            // and is this value a selected filter?
                            if (in_array($facetDetails['value'], $this->filterList[$field])) {
                                $isApplied = true;
                            }
                        }
                        
                        // Inject "applied" value and "add filter" link into Summon results:
                        $current['counts'][$facetIndex]['isApplied'] = $isApplied;
                        $current['counts'][$facetIndex]['url'] = 
                            $this->renderLinkWithFilter("$field:".$facetDetails['value']);
                        // If we want to have expanding links (all values matching the facet)
                        // in addition to limiting links (filter current search with facet),
                        // do some extra work:
                        if ($expandingLinks) {
                            $current['counts'][$facetIndex]['expandUrl'] = 
                                $this->getExpandingFacetLink($field, $facetDetails['value']);
                        }
                    }
                    
                    // Put the current facet cluster in order based on the .ini settings,
                    // then override the display name again using .ini settings.
                    $i = $order[$field];
                    $current['label'] = $filter[$field];
                    
                    // Create a reference to counts called list for consistency with Solr
                    // output format -- this allows the facet recommendations modules to
                    // be shared between the Search and Summon modules.
                    $current['list'] = & $current['counts'];
                    $facetResult[$i] = $current;
                }
            }
        }
        ksort($facetResult);
        return $facetResult;
    }
    
    /**
     * Process spelling suggestions from the results object
     *
     * @access  private
     */
    private function processSpelling()
    {
        if (isset($this->indexResult['didYouMeanSuggestions']) &&
            is_array($this->indexResult['didYouMeanSuggestions'])) {
            foreach($this->indexResult['didYouMeanSuggestions'] as $current) {
                if (!isset($this->suggestions[$current['originalQuery']])) {
                    $this->suggestions[$current['originalQuery']] = array();
                }
                if (!isset($this->suggestions[$current['originalQuery']]['suggestions'])) {
                    $this->suggestions[$current['originalQuery']]['suggestions'] = array();
                }
                $this->suggestions[$current['originalQuery']]['suggestions'][] =
                    $current['suggestedQuery'];
            }
        }
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
     * @return  object Summon result structure (for now)
     */
    public function processSearch($returnIndexErrors = false, $recommendations = false)
    {
        // Build a recommendations module appropriate to the current search:
        if ($recommendations) {
            $this->initRecommendations();
        }

        // Get time before the query
        $this->startQueryTimer();

        // The "relevance" sort option is a VuFind reserved word; we need to make
        // this null in order to achieve the desired effect with Summon:
        $finalSort = ($this->sort == 'relevance') ? null : $this->sort;

        // Perform the actual search
        $this->indexResult = $this->summon->query($this->searchTerms, 
            $this->getFilterList(), $this->page, $this->limit, $finalSort, 
            $this->fullFacetSettings, $returnIndexErrors);
        if (PEAR::isError($this->indexResult)) {
            PEAR::raiseError($this->indexResult);
        }

        // Save spelling details if they exist.
        if ($this->spellcheck) {
            $this->processSpelling();
        }

        // Get time after the query
        $this->stopQueryTimer();

        // Store relevant details from the search results:
        $this->resultsTotal = $this->indexResult['recordCount'];

        // If extra processing is needed for recommendations, do it now:
        if ($recommendations && is_array($this->recommend)) {
            foreach($this->recommend as $currentSet) {
                foreach($currentSet as $current) {
                    $current->process();
                }
            }
        }

        // Send back all the details:
        return $this->indexResult;
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
        return isset($this->indexResult['errors']) ?
            $this->indexResult['errors'] : false;
    }

    /**
     * Get database recommendations from Summon, if any.
     *
     * @access  public
     * @return  mixed       false if no recommendations, detailed array otherwise.
     */
    public function getDatabaseRecommendations()
    {
        return isset($this->indexResult['recommendationLists']['database']) ?
            $this->indexResult['recommendationLists']['database'] : false;
    }

    /**
     * Generate a URL for a basic Summon "all fields" search for a specific query.
     *
     * @access  private
     * @param   string  $lookfor        The search query.
     * @return  string                  The search URL.
     */
    private function renderBasicSummonSearch($lookfor)
    {
        // Save original settings:
        $oldType = $this->searchType;
        $oldTerms = $this->searchTerms;
        $oldPage = $this->page;

        // Create a basic search:
        $this->page = 1;
        $this->searchType = $this->basicSearchType;
        $this->searchTerms = array(array('lookfor' => $lookfor));
        $url = $this->renderSearchUrl();

        // Restore original settings:
        $this->page = $oldPage;
        $this->searchTerms = $oldTerms;
        $this->searchType = $oldType;

        // Send back generated URL:
        return $url;
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
        $returnArray = array();
        if (count($this->suggestions) == 0) return $returnArray;

        foreach ($this->suggestions as $term => $details) {
            foreach ($details['suggestions'] as $word) {
                // Strip escaped characters in the search term (for example, "\:")
                $term = stripcslashes($term);
                $word = stripcslashes($word);
                // Basic spelling suggestion data -- since the Summon API currently
                // returns completely rewritten queries, we'll simply generate a new
                // search URL rather than trying to replace components of the
                // existing one.  This may need to become more sophisticated in the
                // future if the Summon API begins to offer suggestions in more
                // contexts.
                $returnArray[$term]['suggestions'][$word] = array(
                    'replace_url' => $this->renderBasicSummonSearch($word)
                );
            }
        }
        return $returnArray;
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
        // All Summon facets are loaded through recommendations modules; we can
        // activate the settings by starting up recommendations.  This is not a
        // very elegant solution to the problem, and we should probably revisit
        // this in the future.
        $this->initRecommendations();
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
        // The default use of "Other" for undefined facets doesn't work well with
        // checkbox facets -- we'll use field names as the default within the Summon
        // search object.
        return isset($this->facetConfig[$field]) ? 
            $this->facetConfig[$field] : $field;
    }

    /**
     * Get information on the current state of the boolean checkbox facets.
     *
     * @access  public
     * @return  array
     */
    public function getCheckboxFacets()
    {
        // Grab checkbox facet details using the standard method:
        $facets = parent::getCheckboxFacets();

        // Special case -- if we have a "holdings only" facet, we want this to
        // always appear, even on the "no results" screen, since setting this
        // facet actually EXPANDS the result set, rather than reducing it:
        if (isset($facets['holdingsOnly'])) {
            $facets['holdingsOnly']['alwaysVisible'] = true;
        }

        // Return modified list:
        return $facets;
    }
}

?>