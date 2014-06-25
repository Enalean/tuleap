<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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


class FullTextSearch_Presenter_Search {
    public $template;
    /**
     * @var FullTextSearch_SearchResultCollection
     */
    private $query_result;

    public $more_results;

    public $maybe_more_results;

    public $keywords;


    public function __construct(FullTextSearch_SearchResultCollection $query_result, $maybe_more_results, $keywords) {
        $this->query_result       = $query_result;
        $this->template           = 'results';
        $this->more_results       = $GLOBALS['Language']->getText('search_index', 'more_results');
        $this->maybe_more_results = $maybe_more_results;
        $this->keywords           = $keywords;
    }

    public function has_results() {
        return ($this->query_result->count() > 0);
    }

    public function has_facets() {
        return (count($this->facets()) > 0);
    }

    public function facets() {
        return $this->query_result->getFacets();
    }

    public function result_count() {
        return $GLOBALS['Language']->getText('plugin_fulltextsearch', 'result_count', array($this->query_result->count(), number_format($this->query_result->getQueryTime(), 2, '.', '')));
    }

    public function search_results() {
        return $this->query_result->getResults();
    }

    public function elapsed_time() {
        return $this->query_result->getQueryTime();
    }

    public function no_more_results() {
        return $GLOBALS['Language']->getText('search_index', 'no_more_results');
    }

    public function no_match_found_string() {
        return $GLOBALS['Language']->getText('search_index', 'no_match_found', $this->keywords);
    }
}