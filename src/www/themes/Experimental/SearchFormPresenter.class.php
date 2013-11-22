<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Experimental_SearchFormPresenter {

    private $search_options;

    private $hidden_fields;

    public function __construct($search_options, $hidden_fields) {
        $this->search_options = $search_options;
        $this->hidden_fields  = $hidden_fields;
    }

    public function search_options() {
        return $this->search_options;
    }

    public function hidden_fields() {
        return $this->hidden_fields;
    }
}
