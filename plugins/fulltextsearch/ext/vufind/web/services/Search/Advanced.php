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

class Advanced extends Action {
    
    function launch()
    {
        global $interface;
        global $configArray;
        global $user;

        // Create our search object
        $searchObject = SearchObjectFactory::initSearchObject();
        $searchObject->initAdvancedFacets();
        // We don't want this search in the search history
        $searchObject->disableLogging();
        // Go get the facets
        $searchObject->processSearch();
        $facetList = $searchObject->getFacetList();
        // Shutdown the search object
        $searchObject->close();

        // Load a saved search, if any:
        $savedSearch = $this->loadSavedSearch();

        // Process the facets for appropriate display on the Advanced Search screen:
        $facets = $this->processFacets($facetList, $savedSearch);
        $interface->assign('facetList', $facets);

        // Integer for % width of each column (be careful to avoid divide by zero!)
        $columnWidth = (count($facets) > 1) ? round(100 / count($facets), 0) : 0;
        $interface->assign('columnWidth', $columnWidth);

        // Process settings to control special-purpose facets not supported by the
        //     more generic configuration options.
        $specialFacets = $searchObject->getFacetSetting('Advanced_Settings', 'special_facets');
        if (stristr($specialFacets, 'illustrated')) {
            $interface->assign('illustratedLimit',
                $this->getIllustrationSettings($savedSearch));
        }

        // Send search type settings to the template
        $interface->assign('advSearchTypes', $searchObject->getAdvancedTypes());

        // If we found a saved search, let's assign some details to the interface:
        if ($savedSearch) {
            $interface->assign('searchDetails', $savedSearch->getSearchTerms());
            $interface->assign('searchFilters', $savedSearch->getFilterList());
        }

        $interface->setPageTitle('Advanced Search');
        $interface->setTemplate('advanced.tpl');
        $interface->display('layout.tpl');
    }
    
    /**
     * Get the possible legal values for the illustration limit radio buttons.
     *
     * @access  private
     * @param   object  $savedSearch    Saved search object (false if none)
     * @return  array                   Legal options, with selected value flagged.
     */
    private function getIllustrationSettings($savedSearch = false)
    {
        $illYes = array('text' => 'Has Illustrations', 'value' => 1, 'selected' => false);
        $illNo = array('text' => 'Not Illustrated', 'value' => 0, 'selected' => false);
        $illAny = array('text' => 'No Preference', 'value' => -1, 'selected' => false);
        
        // Find the selected value by analyzing facets -- if we find match, remove
        // the offending facet to avoid inappropriate items appearing in the
        // "applied filters" sidebar!
        if ($savedSearch && $savedSearch->hasFilter('illustrated:Illustrated')) {
            $illYes['selected'] = true;
            $savedSearch->removeFilter('illustrated:Illustrated');
        } else if ($savedSearch && $savedSearch->hasFilter('illustrated:"Not Illustrated"')) {
            $illNo['selected'] = true;
            $savedSearch->removeFilter('illustrated:"Not Illustrated"');
        } else {
            $illAny['selected'] = true;
        }
        return array($illYes, $illNo, $illAny);
    }

    /**
     * Load a saved search, if appropriate and legal; assign an error to the
     * interface if necessary.
     *
     * @access  private
     * @return  mixed           Search Object on successful load, false otherwise
     */
    private function loadSavedSearch()
    {
        global $interface;
        
        // Are we editing an existing search?
        if (isset($_REQUEST['edit'])) {
            // Go find it
            $search = new SearchEntry();
            $search->id = $_REQUEST['edit'];
            if ($search->find(true)) {
                // Check permissions
                if ($search->session_id == session_id() || $search->user_id == $user->id) {
                    // Retrieve the search details
                    $minSO = unserialize($search->search_object);
                    $savedSearch = SearchObjectFactory::deminify($minSO);
                    // Make sure it's an advanced search
                    if ($savedSearch->getSearchType() == 'advanced') {
                        // Activate facets so we get appropriate descriptions
                        // in the filter list:
                        $savedSearch->activateAllFacets('Advanced');
                        return $savedSearch;
                    } else {
                        $interface->assign('editErr', 'notAdvanced');
                    }
                // No permissions
                } else {
                    $interface->assign('editErr', 'noRights');
                }
            // Not found
            } else {
                $interface->assign('editErr', 'notFound');
            }
        }
        
        return false;
    }

    /**
     * Process the facets to be used as limits on the Advanced Search screen.
     *
     * @access  private
     * @param   array   $facetList      The advanced facet values
     * @param   object  $searchObject   Saved search object (false if none)
     * @return  array                   Sorted facets, with selected values flagged.
     */
    private function processFacets($facetList, $searchObject = false)
    {
        // Process the facets, assuming they came back
        $facets = array();
        foreach ($facetList as $facet => $list) {
            $currentList = array();
            foreach ($list['list'] as $value) {
                // Build the filter string for the URL:
                $fullFilter = $facet.':"'.$value['value'].'"';
                
                // If we haven't already found a selected facet and the current
                // facet has been applied to the search, we should store it as
                // the selected facet for the current control.
                if ($searchObject && $searchObject->hasFilter($fullFilter)) {
                    $selected = true;
                    // Remove the filter from the search object -- we don't want
                    // it to show up in the "applied filters" sidebar since it
                    // will already be accounted for by being selected in the
                    // filter select list!
                    $searchObject->removeFilter($fullFilter);
                } else {
                    $selected = false;
                }
                $currentList[$value['value']] = 
                    array('filter' => $fullFilter, 'selected' => $selected);
            }
            
            // Perform a natural case sort on the array of facet values:
            $keys = array_keys($currentList);
            natcasesort($keys);
            foreach($keys as $key) {
                $facets[$list['label']][$key] = $currentList[$key];
            }
        }
        return $facets;
    }
}
?>