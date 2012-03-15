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

require_once 'Base.php';

class Advanced extends Base {
    
    function launch()
    {
        global $interface;
        global $configArray;
        global $user;

        // Load a saved search, if any:
        $savedSearch = $this->loadSavedSearch();

        // Send search type settings to the template
        $interface->assign('advSearchTypes', $this->searchObject->getAdvancedTypes());

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
                    if ($savedSearch->getSearchType() == 'WorldCatAdvanced') {
                        // Activate facets so we get appropriate descriptions
                        // in the filter list:
                        $savedSearch->activateAllFacets();
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
}
?>