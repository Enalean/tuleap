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

    private $purifier;

    public $document_types;

    public function __construct(FullTextSearch_SearchResultCollection $query_result, $maybe_more_results, $keywords, $document_types) {
        $this->query_result       = $query_result;
        $this->template           = 'results';
        $this->more_results       = $GLOBALS['Language']->getText('search_index', 'more_results');
        $this->maybe_more_results = $maybe_more_results;
        $this->keywords           = $keywords;
        $this->purifier           = Codendi_HTMLPurifier::instance();
        $this->document_types     = $document_types;
    }

    public function has_results() {
        return ($this->query_result->count() > 0);
    }

    public function project_facets_label() {
        return $GLOBALS['Language']->getText('plugin_fulltextsearch', 'project_facets_label');
    }

    public function docman_wiki_facets_label() {
        return $GLOBALS['Language']->getText('plugin_fulltextsearch', 'docman_wiki_facets_label');
    }

    public function projects_facet() {
        return $this->query_result->getProjectsFacet();
    }

    public function owner_facet() {
        return $this->query_result->getOwnerFacet();
    }

    public function update_date_facet() {
        return $this->query_result->getUpdateDateFacet();
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
        return $GLOBALS['Language']->getText('search_index', 'no_match_found', $this->purifier->purify($this->keywords));
    }

    public function categories() {
        return $GLOBALS['Language']->getText('plugin_fulltextsearch', 'categories');
    }
}