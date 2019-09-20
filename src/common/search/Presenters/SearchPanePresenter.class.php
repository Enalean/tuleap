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

class Search_SearchPanePresenter
{

    /** @var string */
    public $title;

    /** @var array */
    public $search_types;

    /** @var array */
    public $has_search_types;

    /** @var string */
    public $no_search_types;

    /**
     * @param string $title
     * @param array $search_types
     * @param string $no_search_types
     */
    public function __construct($title, array $search_types, $no_search_types)
    {
        $this->title            = $title;
        $this->search_types     = $search_types;
        $this->has_search_types = ! empty($search_types);
        $this->no_search_types  = $no_search_types;
    }
}
