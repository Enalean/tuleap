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

class Search_SearchPresenter {

    public $type_of_search;

    public $words;

    public $blob;

    public function __construct($type_of_search, $words, $blob) {
        $this->type_of_search = $type_of_search;
        $this->words          = $words;
        $this->blob           = $blob;
    }

    public function simple_search() {
        return $GLOBALS['Language']->getText('search_index', 'simple_search');
    }

    public function fulltext_search() {
        return $GLOBALS['Language']->getText('search_index', 'fulltext_search');
    }
}
