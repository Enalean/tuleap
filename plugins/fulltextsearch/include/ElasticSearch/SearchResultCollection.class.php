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

    private $result_factory;

    private $result;

    private $submitted_facets;

    private $nb_documents_found = 0;

    private $query_time = 0;

    private $search_results = array();

    
    public function __construct(
        array $result,
        array $submitted_facets,
        ElasticSearch_1_2_ResultFactory $result_factory
    ) {
        $this->result_factory     = $result_factory;
        $this->result             = $result;
        $this->submitted_facets   = $submitted_facets;
        $this->query_time         = $this->result_factory->getQueryTime($this->result);
        $this->search_results     = $this->result_factory->getSearchResults($this->result);
        $this->nb_documents_found = count($this->search_results);
    }

    public function getProjectsFacet() {
        return $this->result_factory->getSearchResultProjectsFacet($this->result, $this->submitted_facets);
    }

    public function getMyProjectsFacet() {
        return $this->result_factory->getSearchResultMyProjectsFacet($this->result, $this->submitted_facets);
    }
    
    public function count() {
        return $this->nb_documents_found;
    }
    
    public function getQueryTime() {
        return $this->query_time;
    }
    
    public function getResults() {
        return $this->search_results;
    }
}
