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

class Search_SearchProjectPresenter {

    /** @var array */
    private $results;

    /** @var  string */
    private $words;

    public function __construct(array $results, $words) {
        $this->results = $results;
        $this->words   = $words;
    }

    public function has_results() {
        return count($this->results) > 0;
    }

    public function no_match_found_string() {
        return $GLOBALS['Language']->getText('search_index', 'no_match_found', $this->words);
    }

    public function search_result_title() {
        return $GLOBALS['Language']->getText('search_index', 'search_res', array($this->words, $this->getNumberRowsReturned()));
    }

    public function project_name_column_title() {
        return $GLOBALS['Language']->getText('search_index', 'project_name');
    }

    public function description_column_title() {
        return $GLOBALS['Language']->getText('search_index', 'description');
    }

    public function results() {
        return $this->results;
    }

    private function getNumberRowsReturned() {
        return count($this->results);
    }
}