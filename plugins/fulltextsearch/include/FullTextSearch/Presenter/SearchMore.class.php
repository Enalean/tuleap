<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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


class FullTextSearch_Presenter_SearchMore {
    public $template;
    /**
     * @var FullTextSearch_SearchResultCollection
     */
    private $query_result;

    public $more_results;

    public function __construct(FullTextSearch_SearchResultCollection $query_result) {
        $this->query_result = $query_result;
        $this->template     = 'search-results-more';
        $this->more_results = $GLOBALS['Language']->getText('plugin_fulltextsearch', 'more_results');
    }

    public function has_results() {
        return ($this->query_result->count() > 0);
    }

    public function search_results() {
        return $this->query_result->getResults();
    }

    public function display_more_results_option() {
        return $this->has_results();
    }

    public function no_more_results() {
        return $GLOBALS['Language']->getText('search_index', 'no_more_results');
    }
}