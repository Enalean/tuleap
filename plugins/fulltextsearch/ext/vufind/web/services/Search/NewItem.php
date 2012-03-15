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

require_once 'CatalogConnection.php';

require_once 'sys/Pager.php';
require_once 'sys/ConfigArray.php';

class NewItem extends Action {
    
    function launch()
    {
        global $configArray;
        global $interface;

        $catalog = new CatalogConnection($configArray['Catalog']['driver']);
        if (!$catalog->status) {
            PEAR::raiseError(new PEAR_Error('Cannot Load Catalog Driver'));
        }

        // Read in search-specific configurations:
        $searchSettings = getExtraConfigArray('searches');

        if (count($_GET) > 2) {
            // Initialise from the current search globals
            $searchObject = SearchObjectFactory::initSearchObject();
            $searchObject->init();

            // Are there "new item" filter queries specified in the config file?
            // If so, we should apply them as hidden filters so they do not show
            // up in the user-selected facet list.
            if (isset($searchSettings['NewItem']['filter'])) {
                $filter = is_array($searchSettings['NewItem']['filter']) ?
                    $searchSettings['NewItem']['filter'] : 
                    array($searchSettings['NewItem']['filter']);
                foreach($filter as $current) {
                    $searchObject->addHiddenFilter($current);
                }
            }

            // Must have atleast Action and Module set to continue
            $interface->setPageTitle('New Item Search Results');
            $interface->setTemplate('newitem-list.tpl');
            $interface->assign('sortList', $searchObject->getSortList());
            $interface->assign('rssLink', $searchObject->getRSSUrl());

            // This code was originally designed to page through the results 
            // retrieved from the catalog in parallel with paging through the
            // Solr results.  The logical flaw in this approach is that if
            // we only retrieve one page of results from the catalog, we never
            // know the full extent of available results there!
            //
            // The code has now been changed to always pull in enough catalog 
            // results to get a fixed number of pages worth of Solr results.  Note 
            // that if the Solr index is out of sync with the ILS, we may see fewer 
            // results than expected.
            $tmp = $searchObject->getResultSummary();
            $limit = $tmp['perPage'];
            if (isset($searchSettings['NewItem']['result_pages'])) {
                $resultPages = intval($searchSettings['NewItem']['result_pages']);
                if ($resultPages < 1) {
                    $resultPages = 10;
                }
            } else {
                $resultPages = 10;
            }
            $newItems = $catalog->getNewItems(1, $limit * $resultPages, $_GET['range'], 
                isset($_GET['department']) ? $_GET['department'] : null);
            
            // Special case -- if no new items were found, don't bother hitting
            // the index engine:
            if ($newItems['count'] > 0) {
                // Query Index for BIB Data
                $bibIDs = array();
                for ($i=0; $i<count($newItems['results']); $i++) {
                    $bibIDs[] = $newItems['results'][$i]['id'];
                }
                $searchObject->setQueryIDs($bibIDs);

                // Build RSS Feed for Results (if requested)
                if ($searchObject->getView() == 'rss') {
                    // Throw the XML to screen
                    echo $searchObject->buildRSS();
                    // And we're done
                    exit();
                }

                // Process Search
                $result = $searchObject->processSearch(false, true);
                if (PEAR::isError($result)) {
                    PEAR::raiseError($result->getMessage());
                }
                
                // Store recommendations (facets, etc.)
                $interface->assign('topRecommendations',
                    $searchObject->getRecommendationsTemplates('top'));
                $interface->assign('sideRecommendations',
                    $searchObject->getRecommendationsTemplates('side'));
            // Special case -- empty RSS feed:
            } else if ($searchObject->getView() == 'rss') {
                // Throw the XML to screen
                echo $searchObject->buildRSS(array(
                    'response' => array('numFound' => 0),
                    'responseHeader' => array('params' => array('rows' => 0)),
                    ));
                // And we're done
                exit();
            }

            // Send the new items to the template
            $interface->assign('recordSet', $searchObject->getResultRecordHTML());

            // Setup Record Count Display
            $summary = $searchObject->getResultSummary();
            $interface->assign('recordCount', $summary['resultTotal']);
            $interface->assign('recordStart', $summary['startRecord']);
            $interface->assign('recordEnd',   $summary['endRecord']);

            // Setup Paging
            $link = $searchObject->renderLinkPageTemplate();
            $total = isset($result['response']['numFound']) ? 
                $result['response']['numFound'] : 0;
            $options = array('totalItems' => $total,
                             'perPage' => $limit,
                             'fileName' => $link);
            $pager = new VuFindPager($options);
            $interface->assign('pageLinks', $pager->getLinks());

            // Save the URL of this search to the session so we can return to it easily:
            $_SESSION['lastSearchURL'] = $searchObject->renderSearchUrl();
        } else {
            $interface->setPageTitle('New Item Search');
            $interface->setTemplate('newitem.tpl');
            
            $list = $catalog->getFunds();
            $interface->assign('fundList', $list);
            
            // Find out if there are user configured range options; if not,
            // default to the standard 1/5/30 days:
            $ranges = array();
            if (isset($searchSettings['NewItem']['ranges'])) {
                $tmp = explode(',', $searchSettings['NewItem']['ranges']);
                foreach($tmp as $range) {
                    $range = intval($range);
                    if ($range > 0) {
                        $ranges[] = $range;
                    }
                }
            }
            if (empty($ranges)) {
                $ranges = array(1, 5, 30);
            }
            $interface->assign('ranges', $ranges);
        }
        $interface->display('layout.tpl');
    }
}

?>