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

class Search_SearchPlugin {

    /**
     * @var EventManager
     */
    private $event_manager;

    private $results = '';

    public function __construct(EventManager $event_manager) {
        $this->event_manager = $event_manager;
    }

    public function search(Search_SearchQuery $query) {
        $matchingSearchTypeFound = false;
        $pagination_handled = false;
        $rows_returned = 0;
        $rows = 0;

        $params = array(
            'words'              => $query->getWords(),
            'offset'             => $query->getOffset(),
            'nbRows'             => 25,
            'type_of_search'     => $query->getTypeOfSearch(),
            'search_type'        => &$matchingSearchTypeFound,
            'rows_returned'      => &$rows_returned,
            'rows'               => &$rows,
            'pagination_handled' => &$pagination_handled,
            'group_id'           => $query->getProject()->getId(),
            'results'            => &$this->results,
        );

        $this->event_manager->processEvent('search_type', $params);
    }

    public function getResults() {
        return $this->results;
    }
}
