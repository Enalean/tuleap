<?php
/**
 *
 * Copyright (C) Andrew Nagy 2009
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
require_once 'services/MyResearch/lib/User.php';
require_once 'services/MyResearch/lib/Search.php';

require_once 'sys/SolrStats.php';
require_once 'sys/Pager.php';
require_once 'sys/ResultScroller.php';

class Results extends Action {
    
    private $solrStats = false;
    private $query;

    function launch()
    {
        global $interface;
        global $configArray;

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

// TODO : Stats, move inside the search object
        // Setup Statistics Index Connection
        if ($configArray['Statistics']['enabled']) {
            $this->solrStats = new SolrStats($configArray['Statistics']['solr']);
            if ($configArray['System']['debug']) {
                $this->solrStats->debug = true;
            }
        }

// TODO : Investigate this... do we still need
        // If user wants to print record show directly print-dialog box
        if (isset($_GET['print'])) {
           $interface->assign('print', true);
        }

        // Set Interface Variables
        //   Those we can construct BEFORE the search is executed
        $interface->setPageTitle('Search Results');
        $interface->assign('sortList',   $searchObject->getSortList());
        $interface->assign('rssLink',    $searchObject->getRSSUrl());

        // Process Search
        $result = $searchObject->processSearch(true, true);
        if (PEAR::isError($result)) {
            PEAR::raiseError($result->getMessage());
        }

        // Some more variables
        //   Those we can construct AFTER the search is executed, but we need
        //   no matter whether there were any results
        $interface->assign('qtime',               round($searchObject->getQuerySpeed(), 2));
        $interface->assign('spellingSuggestions', $searchObject->getSpellingSuggestions());
        $interface->assign('lookfor',             $searchObject->displayQuery());
        $interface->assign('searchType',          $searchObject->getSearchType());
        // Will assign null for an advanced search
        $interface->assign('searchIndex',         $searchObject->getSearchIndex());
        
        // We'll need recommendations no matter how many results we found:
        $interface->assign('topRecommendations',
            $searchObject->getRecommendationsTemplates('top'));
        $interface->assign('sideRecommendations',
            $searchObject->getRecommendationsTemplates('side'));

        if ($searchObject->getResultTotal() < 1) {
            // No record found
            $interface->setTemplate('list-none.tpl');
            $interface->assign('recordCount', 0);
            
            // Was the empty result set due to an error?
            $error = $searchObject->getIndexError();
            if ($error !== false) {
                // If it's a parse error or the user specified an invalid field, we 
                // should display an appropriate message:
                if (stristr($error, 'org.apache.lucene.queryParser.ParseException') ||
                    preg_match('/^undefined field/', $error)) {
                    $interface->assign('parseError', true);
                    
                // Unexpected error -- let's treat this as a fatal condition.
                } else {
                    PEAR::raiseError(new PEAR_Error('Unable to process query<br />' .
                        'Solr Returned: ' . $error));
                }
            }
            
// TODO : Stats, move inside the search object
            // Save no records found stat
            if ($this->solrStats) {
                $this->solrStats->saveNoHits($_GET['lookfor'], $_GET['type']);
            }
        } else {
// TODO : Stats, move inside the search object
            // Save search stat
            if ($this->solrStats) {
                $this->solrStats->saveSearch($_GET['lookfor'], $_GET['type']);
            }

            // If the "jumpto" parameter is set, jump to the specified result index:
            $this->processJumpto($result);

            // Assign interface variables
            $summary = $searchObject->getResultSummary();
            $interface->assign('recordCount', $summary['resultTotal']);
            $interface->assign('recordStart', $summary['startRecord']);
            $interface->assign('recordEnd',   $summary['endRecord']);

            // Big one - our results
            $interface->assign('recordSet', $searchObject->getResultRecordHTML());

            // Setup Display
            $interface->assign('sitepath', $configArray['Site']['path']);
            $interface->assign('subpage', 'Search/list-list.tpl');
            $interface->setTemplate('list.tpl');

            // Process Paging
            $link = $searchObject->renderLinkPageTemplate();
            $options = array('totalItems' => $summary['resultTotal'],
                             'fileName'   => $link,
                             'perPage'    => $summary['perPage']);
            $pager = new VuFindPager($options);
            $interface->assign('pageLinks', $pager->getLinks());
        }

        // 'Finish' the search... complete timers and log search history.
        $searchObject->close();
        $interface->assign('time', round($searchObject->getTotalSpeed(), 2));
        // Show the save/unsave code on screen
        // The ID won't exist until after the search has been put in the search history
        //    so this needs to occur after the close() on the searchObject
        $interface->assign('showSaved',   true);
        $interface->assign('savedSearch', $searchObject->isSavedSearch());
        $interface->assign('searchId',    $searchObject->getSearchId());
        
        // Save the URL of this search to the session so we can return to it easily:
        $_SESSION['lastSearchURL'] = $searchObject->renderSearchUrl();

        // initialize the search result scroller for this search
        $scroller = new ResultScroller();
        $scroller->init($searchObject, $result);

        // Done, display the page
        $interface->display('layout.tpl');
    } // End launch()

    /**
     * Process the "jumpto" parameter.
     *
     * @access  private
     * @param   array       $result         Solr result returned by SearchObject
     */
    private function processJumpto($result)
    {
        if (isset($_REQUEST['jumpto']) && is_numeric($_REQUEST['jumpto'])) {
            $i = intval($_REQUEST['jumpto'] - 1);
            if (isset($result['response']['docs'][$i])) {
                $record = RecordDriverFactory::initRecordDriver($result['response']['docs'][$i]);
                $jumpUrl = '../Record/' . urlencode($record->getUniqueID());
                header('Location: ' . $jumpUrl);
                die();
            }
        }
    }
}

?>
