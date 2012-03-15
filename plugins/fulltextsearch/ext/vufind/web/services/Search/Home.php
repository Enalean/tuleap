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

class Home extends Action {
    
    function launch()
    {
        global $interface;
        global $configArray;

        // Cache homepage
        $interface->caching = 1; 
        $cacheId = 'homepage|' . $interface->lang;
        if (!$interface->is_cached('layout.tpl', $cacheId)) {
            $interface->setPageTitle('Search Home');
            $interface->assign('searchTemplate', 'search.tpl');
            $interface->setTemplate('home.tpl');

            // Create our search object
            $searchObject = SearchObjectFactory::initSearchObject();
            // Re-use the advanced search facets method,
            //   it is (for now) calling the same facets.
            // The template however is looking for specific
            //   facets. Bear in mind for later.
            $searchObject->initAdvancedFacets();
            // We don't want this search in the search history
            $searchObject->disableLogging();
            // Go get the facets
            $searchObject->processSearch();
            $facetList = $searchObject->getFacetList();
            // Shutdown the search object
            $searchObject->close();

            // Add a sorted version to the facet list:
            if (count($facetList) > 0) {
                $facets = array();
                foreach ($facetList as $facet => $details) {
                    $facetList[$facet]['sortedList'] = array();
                    foreach ($details['list'] as $value) {
                        $facetList[$facet]['sortedList'][$value['value']] = 
                            $value['url'];
                    }
                    natcasesort($facetList[$facet]['sortedList']);
                }
                $interface->assign('facetList', $facetList);
            }
        }
        $interface->display('layout.tpl', $cacheId);
    }

}

?>
