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

class Search_SearchResults
{

    private $results_html;
    private $has_more;
    private $results_count;

    public function getResultsHtml()
    {
        return ($this->results_html) ? $this->results_html : '';
    }

    public function getFacetsHtml()
    {
        return $this->facets_html;
    }

    public function hasMore()
    {
        return $this->has_more;
    }

    public function setResultsHtml($results_html)
    {
        $this->results_html = $results_html;
        return $this;
    }

    public function setHasMore($has_more)
    {
        $this->has_more = $has_more;
        return $this;
    }

    public function getCountResults()
    {
        return (int) $this->results_count;
    }

    public function setCountResults($count)
    {
        $this->results_count = (int) $count;
        return $this;
    }
}
