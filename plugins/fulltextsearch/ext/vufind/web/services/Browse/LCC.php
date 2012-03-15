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

require_once 'services/Browse/Browse.php';

class LCC extends Browse {
    
    function launch()
    {
        global $interface;

        // Initialise the search object
        $searchObject = SearchObjectFactory::initSearchObject();
        $searchObject->initBrowseScreen();
        $searchObject->disableLogging();

        // Query all records and facet on the first character
        //     of their callnumber (field = callnumber-first)
        $searchObject->addFacet('callnumber-first');
        $searchObject->setFacetSortOrder(false);
        $searchObject->setQueryString('*:*');
        $result = $searchObject->processSearch();

        // Retrieve the facet data and assign to the interface
        $defaultList = $result['facet_counts']['facet_fields']['callnumber-first'];
        $interface->assign('defaultList', $defaultList);

        // Finish off the interface and display
        $interface->setPageTitle('Browse the Collection');
        $interface->setTemplate('lcc.tpl');
        $interface->display('layout.tpl');
    }
}

?>