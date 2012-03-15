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

class Reserves extends Action {
    
    function launch()
    {
        global $interface;
        global $configArray;

        $catalog = new CatalogConnection($configArray['Catalog']['driver']);
        if (!$catalog->status) {
            PEAR::raiseError(new PEAR_Error('Cannot Load Catalog Driver'));
        }

        if (count($_GET) > 2) {
            // Initialise from the current search globals
            $searchObject = SearchObjectFactory::initSearchObject();
            $searchObject->init();

            // Must have atleast Action and Module set to continue
            $interface->setPageTitle('Reserves Search Results');
            $interface->assign('subpage', 'Search/list-list.tpl');
            $interface->setTemplate('reserves-list.tpl');
            $interface->assign('sortList', $searchObject->getSortList());
            $interface->assign('rssLink', $searchObject->getRSSUrl());

            // Get reserve info from the catalog and catch any fatal errors:
            $result = $catalog->findReserves($_GET['course'], $_GET['inst'], $_GET['dept']);
            if (PEAR::isError($result)) {
                PEAR::raiseError($result);
            }

            // Perform a Solr query to get details on the reserve items, assuming
            // we found at least one.
            if (count($result) > 0) {
                $bibIDs = array();
                foreach ($result as $record) {
                    // Avoid duplicate IDs (necessary for Voyager ILS driver):
                    if (!in_array($record['BIB_ID'], $bibIDs)) {
                        $bibIDs[] = $record['BIB_ID'];
                    }
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

            $interface->assign('recordSet', $searchObject->getResultRecordHTML());
            $summary = $searchObject->getResultSummary();
            $interface->assign('recordCount', $summary['resultTotal']);
            $interface->assign('recordStart', $summary['startRecord']);
            $interface->assign('recordEnd',   $summary['endRecord']);

            $link = $searchObject->renderLinkPageTemplate();
            $total = isset($result['response']['numFound']) ? 
                $result['response']['numFound'] : 0;
            $options = array('totalItems' => $total,
                             'perPage' => $summary['perPage'],
                             'fileName' => $link);
            $pager = new VuFindPager($options);
            $interface->assign('pageLinks', $pager->getLinks());

            // Save the URL of this search to the session so we can return to it easily:
            $_SESSION['lastSearchURL'] = $searchObject->renderSearchUrl();
        } else {
            $interface->setPageTitle('Reserves Search');
            if ($catalog->status) {
                $interface->assign('deptList', $catalog->getDepartments());
                $interface->assign('instList', $catalog->getInstructors());
                $interface->assign('courseList', $catalog->getCourses());
            }
            $interface->setTemplate('reserves.tpl');
        }
        $interface->display('layout.tpl');
    }
}
