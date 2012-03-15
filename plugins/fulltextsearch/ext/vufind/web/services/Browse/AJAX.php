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

class AJAX extends Action {

    private $db;
    private $searchObject;

    function __construct()
    {
        $this->searchObject = SearchObjectFactory::initSearchObject();
    }

    function launch()
    {
        header ('Content-type: application/json');
        $response = array();
        if (is_callable(array($this, $_GET['method']))) {
            $this->searchObject->initBrowseScreen();
            $this->searchObject->disableLogging();
            $this->$_GET['method']();
            $result = $this->searchObject->processSearch();
            $response['AJAXResponse'] = $result['facet_counts']['facet_fields'];
        } else {
            $response['AJAXResponse'] = array('Error' => 'Invalid Method');
        }
        // Shutdown the search object
        $this->searchObject->close();

        echo json_encode($response);
    }

    function GetOptions()
    {
        if (isset($_GET['field']))        $this->searchObject->addFacet($_GET['field']);
        if (isset($_GET['facet_prefix'])) $this->searchObject->addFacetPrefix($_GET['facet_prefix']);
        if (isset($_GET['query']))        $this->searchObject->setQueryString($_GET['query']);
    }

    function GetAlphabet()
    {
        if (isset($_GET['field'])) $this->searchObject->addFacet($_GET['field']);
        if (isset($_GET['query'])) $this->searchObject->setQueryString($_GET['query']);
        $this->searchObject->setFacetSortOrder(false);
    }

    function GetSubjects()
    {
        if (isset($_GET['field'])) $this->searchObject->addFacet($_GET['field']);
        if (isset($_GET['query'])) $this->searchObject->setQueryString($_GET['query']);
    }
}
?>