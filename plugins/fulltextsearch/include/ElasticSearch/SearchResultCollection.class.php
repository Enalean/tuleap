<?php
/**
 * Copyright (c) Enalean, 2012 - 2014. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class ElasticSearch_SearchResultCollection implements FullTextSearch_SearchResultCollection {
    private $nb_documents_found = 0;
    private $query_time         = 0;
    private $results            = array();
    private $facets             = array();
    
    public function __construct(
        array $result,
        array $submitted_facets,
        ElasticSearch_1_2_ResultFactory $result_factory
    ) {
        $this->query_time = $result_factory->getQueryTime($result);

        $this->results = $result_factory->getSearchResults($result);
        $this->nb_documents_found = count($this->results);

        $this->facets[] = $result_factory->getSearchResultProjectsFacetCollection($result, $submitted_facets);
    }
    
    public function count() {
        return $this->nb_documents_found;
    }
    
    public function getQueryTime() {
        return $this->query_time;
    }
    
    public function getResults() {
        return $this->results;
    }
    
    public function getFacets() {
        return $this->facets;
    }
}
