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

class Search_SearchQuery
{

    private $project;
    private $type_of_search;
    private $words;
    private $offset;
    private $exact;
    private $trackerv3id;
    private $forum_id;
    private $is_ajax;
    private $number_of_results;

    public function __construct(Codendi_Request $request)
    {
        $this->project        = $request->getProject();
        $this->words          = $request->get('words');
        $this->offset         = intval($request->getValidated('offset', 'uint', 0));
        $this->exact          = $request->getValidated('exact', 'uint', false);
        $this->trackerv3id    = $request->getValidated('atid', 'uint', 0);
        $this->forum_id       = $request->getValidated('forum_id', 'uint', 0);
        $this->is_ajax        = $request->isAjax();
        $this->type_of_search = $request->get('type_of_search');
    }

    public function isValid()
    {
        return strlen($this->words) > 2;
    }

    public function getTypeOfSearch()
    {
        return $this->type_of_search;
    }

    public function getWords()
    {
        return $this->words;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function getExact()
    {
        return $this->exact;
    }

    public function getProject()
    {
        return $this->project;
    }

    public function getForumId()
    {
        return $this->forum_id;
    }

    public function getTrackerV3Id()
    {
        return $this->trackerv3id;
    }

    public function isAjax()
    {
        return $this->is_ajax;
    }

    public function setNumberOfResults($number_of_results)
    {
        $this->number_of_results = $number_of_results;
    }

    public function getNumberOfResults()
    {
        return $this->number_of_results;
    }
}
