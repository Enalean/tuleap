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

class Search_SearchSnippetResultPresenter {

    /** @var  int */
    private $snippet_id;

    /** @var  string */
    private $snippet_name;

    /** @var  string */
    private $snippet_description;

    public function __construct(array $result) {
        $this->snippet_id          = $result['snippet_id'];
        $this->snippet_name        = $result['name'];
        $this->snippet_description = $result['description'];
    }

    public function snippet_name() {
        return $this->snippet_name;
    }

    public function snippet_uri() {
        return "/snippet/detail.php?type=snippet&id=" . $this->snippet_id;
    }

    public function snippet_description() {
        return $this->snippet_description;
    }
}