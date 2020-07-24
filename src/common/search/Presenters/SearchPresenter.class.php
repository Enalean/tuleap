<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

class Search_Presenter_SearchPresenter
{

    public $template = 'site-search';

    public $type_of_search;

    public $words;

    public $search_result;

    public $search_panes = [];

    public $group_id = false;

    public $number_of_page_results;

    public function __construct($type_of_search, $words, $search_result, array $search_panes, $project)
    {
        $this->type_of_search         = $type_of_search;
        $this->words                  = $words;
        $this->search_result          = $search_result;
        $this->search_panes           = $search_panes;

        if ($project && ! $project->isError()) {
            $this->group_id   = $project->getId();
        }

        $this->number_of_page_results = Search_SearchPlugin::RESULTS_PER_QUERY;
    }

    public function classic_search_tab_label()
    {
        return $GLOBALS['Language']->getText('search_index', 'search_tab');
    }
}
