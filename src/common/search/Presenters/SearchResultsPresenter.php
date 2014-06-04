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

class Search_SearchResultsPresenter {

    /** @var array */
    private $results;

    /** @var  Search_SearchResultsIntroPresenter*/
    private $results_intro_presenter;

    public function __construct(Search_SearchResultsIntroPresenter $results_intro_presenter, array $results) {
        $this->results_intro_presenter = $results_intro_presenter;
        $this->results                 = $results;
    }

    public function results_intro() {
        return $this->results_intro_presenter;
    }

    public function has_results() {
        return count($this->results) > 0;
    }

    public function results() {
        return $this->results;
    }
}