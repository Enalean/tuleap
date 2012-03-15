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
require_once 'sys/Worldcat.php';
require_once 'sys/SearchObject/Base.php';

/* A derivative of the Search Object for use with WorldCat.
 */
class SearchObject_WorldCat extends SearchObject_Base
{
    // OTHER VARIABLES
    private $worldcat;      // WorldCat API Interface
    private $indexResult;   // WorldCat API Response
    
    /**
     * Constructor. Initialise some details about the server
     *
     * @access  public
     */
    public function __construct()
    {
        // Standard logic from parent class:
        parent::__construct();
        
        // Set up appropriate results action:
        $this->resultsModule = 'WorldCat';
        $this->resultsAction = 'Search';
        
        // Set up basic and advanced WorldCat search types; default to basic.
        $this->searchType = $this->basicSearchType = 'WorldCat';
        $this->advancedSearchType = 'WorldCatAdvanced';
        
        // Set up sort options
        $config = getExtraConfigArray('WorldCat');
        $this->sortOptions = $config['Sorting'];
        if (isset($config['General']['default_sort'])) {
            $this->defaultSort = $config['General']['default_sort'];
        }
        
        // Set up search options
        $this->basicTypes = $config['Basic_Searches'];
        if (isset($config['Advanced_Searches'])) {
            $this->advancedTypes = $config['Advanced_Searches'];
        }
        $this->defaultIndex = 'srw.kw';
        
        // Set up recommendations options -- settings are found in WorldCat.ini:
        $this->recommendIni = 'WorldCat';
        
        // Connect to WorldCat
        $this->worldcat = new Worldcat();
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

        // Try to find a basic search first; check for advanced if no basic found.
        if (!$this->initBasicSearch()) {
            $this->initAdvancedSearch();
        }
        return true;
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
        global $configArray;

        // Build a recommendations module appropriate to the current search:
        if ($recommendations) {
            $this->initRecommendations();
        }

        // Get time before the query
        $this->startQueryTimer();

        // Perform the actual search
        $query = $this->worldcat->buildQuery($this->searchTerms);
        $this->indexResult = $this->worldcat->search($query,
            $configArray['WorldCat']['OCLCCode'], $this->page, $this->limit,
            $this->sort);
        if (PEAR::isError($this->indexResult)) {
            PEAR::raiseError($this->indexResult);
        }

        // Get time after the query
        $this->stopQueryTimer();

        // Store relevant details from the search results:
        $this->resultsTotal = $this->indexResult['RecordCount'];

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
     * Turn the list of spelling suggestions into an array of urls
     *   for on-screen use to implement the suggestions.
     *
     * @access  public
     * @return  array     Spelling suggestion data arrays
     */
    public function getSpellingSuggestions()
    {
        // Not currently supported.
        return array();
    }
}

?>